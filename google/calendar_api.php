<?php
/**
 * Functions for interacting with Google Calendar API
 */

/**
 * Get list of user's calendars
 * 
 * @param Google\Client $client Authorized Google client
 * @return array List of calendars
 */
function getCalendarList($client) {
    $calendarService = new Google_Service_Calendar($client);
    $calendarList = $calendarService->calendarList->listCalendarList();
    
    $calendars = [];
    foreach ($calendarList->getItems() as $calendar) {
        $calendars[] = [
            'id' => $calendar->getId(),
            'summary' => $calendar->getSummary(),
            'description' => $calendar->getDescription(),
            'primary' => $calendar->getPrimary()
        ];
    }
    
    return $calendars;
}

/**
 * Get events from a specific calendar within a date range
 * 
 * @param Google\Client $client Authorized Google client
 * @param string $calendarId ID of the calendar to fetch events from
 * @param DateTime $startDate Start date for events
 * @param DateTime $endDate End date for events
 * @return array List of events
 */
function getCalendarEvents($client, $calendarId, $startDate, $endDate) {
    $calendarService = new Google_Service_Calendar($client);
    
    $optParams = [
        'timeMin' => $startDate->format('c'),
        'timeMax' => $endDate->format('c'),
        'singleEvents' => true,
        'orderBy' => 'startTime'
    ];
    
    $events = $calendarService->events->listEvents($calendarId, $optParams);
    
    $eventsList = [];
    foreach ($events->getItems() as $event) {
        // Skip all-day events
        if (!$event->getStart()->dateTime || !$event->getEnd()->dateTime) {
            continue;
        }
        
        $startTime = new DateTime($event->getStart()->dateTime);
        $endTime = new DateTime($event->getEnd()->dateTime);
        
        $eventData = [
            'DTSTART' => $startTime,
            'DTEND' => $endTime,
            'SUMMARY' => $event->getSummary(),
            'UID' => $event->getId()
        ];
        
        // Handle description and parse tags
        if ($description = $event->getDescription()) {
            $description = str_replace('\,', ',', $description);
            $descLines = array_map('trim', explode("\n", $description));
            $descLower = array_map('strtolower', $descLines);
            
            $eventData['DESCRIPTION'] = $descLines;
            
            if (in_array('program', $descLower)) {
                $eventData['PROGRAM'] = true;
                unset($eventData['DESCRIPTION'][array_search('program', $descLower)]);
            }
            if (in_array('povinný', $descLower)) {
                $eventData['REQUIRED'] = true;
                unset($eventData['DESCRIPTION'][array_search('povinný', $descLower)]);
            }
            if (in_array('aktivita', $descLower)) {
                $eventData['ACTIVITY'] = true;
                unset($eventData['DESCRIPTION'][array_search('aktivita', $descLower)]);
            }
            if (in_array('téma', $descLower)) {
                $eventData['TOPIC'] = true;
                unset($eventData['DESCRIPTION'][array_search('téma', $descLower)]);
            }
            if (in_array('organizační', $descLower)) {
                $eventData['ORGANIZATIONAL'] = true;
                unset($eventData['DESCRIPTION'][array_search('organizační', $descLower)]);
            }
        }
        
        $eventsList[] = $eventData;
    }
    
    return $eventsList;
}
?> 
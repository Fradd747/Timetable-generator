<?php

session_start();

//redirect back if file, daterange or template is missing. template must be default or ursus
if($_FILES['file']['size'] == 0 || !isset($_POST['daterange']) || !isset($_POST['template']) || !in_array($_POST['template'], ['default', 'ursus'])) {
    header('Location: index.php');
    exit();
}

//get data from file
$ics = file_get_contents($_FILES['file']['tmp_name']);
//split by events
$ics = explode('BEGIN:VEVENT', $ics);
unset($ics[0]);

//get date range
$dateRange = explode(' - ', $_POST['daterange']);
$startDate = new DateTime($dateRange[0]);
#$endDate = new DateTime($dateRange[1]);
//add one day to include the end date
$endDate = new DateTime($dateRange[1]);
$endDate->add(new DateInterval('P1D')); //add one day

//get events in date range
$events = [];

foreach($ics as $event) {
    $event = array_map('trim', explode("\n", trim($event)));
    $eventData = [];

    //parse event data
    foreach ($event as $line) {
        $parts = explode(':', $line, 2);
    
        if (count($parts) == 2) {
            $key = $parts[0];
            $value = $parts[1];
    
            // Handle the case where the key contains a semicolon
            $semicolonPos = strpos($key, ';');
            if ($semicolonPos !== false) {
                $key = substr($key, 0, $semicolonPos);
            }
    
            if (!isset($eventData[$key])) {
                $eventData[$key] = $value;
            } else {
                if (!is_array($eventData[$key])) {
                    $eventData[$key] = [$eventData[$key]];
                }
                $eventData[$key][] = $value;
            }
        }
    }

    //filter only events in year of time range
    if (isset($eventData['DTSTART']) && isset($eventData['DTEND'])) {
        $eventData['DTSTART'] = new DateTime($eventData['DTSTART'], new DateTimeZone('Europe/Prague'));
        $eventData['DTEND'] = new DateTime($eventData['DTEND'], new DateTimeZone('Europe/Prague'));    

        //if dtstart or dtend time is not 00:00:00, skip the event
        if ($eventData['DTSTART']->format('H:i:s') == '00:00:00' || $eventData['DTEND']->format('H:i:s') == '00:00:00') {
            continue;
        }

        // Calculate the start and end dates for the month range
        $startRange = clone $startDate;
        $startRange->modify('-1 month');
        $endRange = clone $endDate;
        $endRange->modify('+1 month');

        // Check if the event falls within the month range
        if ($eventData['DTSTART'] < $startRange || $eventData['DTEND'] > $endRange) {
            continue;
        }
    } else {
        continue;
    }

    if (isset($eventData['SUMMARY'])) {
        $eventData['SUMMARY'] = str_replace('\,', ',', $eventData['SUMMARY']);
    }

    if (isset($eventData['DESCRIPTION'])) {
        $eventData['DESCRIPTION'] = str_replace('\,', ',', $eventData['DESCRIPTION']);
        $eventData['DESCRIPTION'] = array_map('trim', explode('\n', $eventData['DESCRIPTION']));
        $descLower = array_map('strtolower', $eventData['DESCRIPTION']);
        if (in_array('program', $descLower)) {
            $eventData['PROGRAM'] = true;
            unset($eventData['DESCRIPTION'][array_search('program', $descLower)]);
        }
        if (in_array('povinný', $descLower)) {
            $eventData['REQUIRED'] = true;
            unset($eventData['DESCRIPTION'][array_search('povinný', $descLower)]);
        }
    }

    
    if (isset($eventData['RRULE'])) {
        $rrule = explode(';', $eventData['RRULE']);
        $rrule = explode('=', $rrule[1]);

        //Second part could be "COUNT=X" (number of repeats) or "UNTIL=20240817T215959Z" (timestamp)
        $rruleType = $rrule[0];
        $rruleValue = $rrule[1];

        //collect all EXDATES (if there are multiple EXDATE is array, if there is only one, it's string)
        if (isset($eventData['EXDATE'])) {
            if (is_array($eventData['EXDATE'])) {
            $exdates = [];
            foreach ($eventData['EXDATE'] as $exdate) {
                $exdates[] = (new DateTime($exdate, new DateTimeZone('Europe/Prague')))->format('Y-m-d');
            }
            } else {
                $exdates = [(new DateTime($eventData['EXDATE'], new DateTimeZone('Europe/Prague')))->format('Y-m-d')];
            }
        } else {
            $exdates = [];
        }

        unset($eventData['RRULE']);

        if ($rruleType == 'UNTIL') {
            $until = (new DateTime($rruleValue, new DateTimeZone('Europe/Prague')))->add(new DateInterval('P1D'));
            //count number of days between DTSTART and UNTIL
            $count = $eventData['DTSTART']->diff($until)->days;
        } else {
            $count = $rruleValue;
        }

        for ($i=1; $i < $count; $i++) { 
            $parsedEvent = $eventData;
            $parsedEvent['DTSTART'] = clone $eventData['DTSTART'];
            $parsedEvent['DTEND'] = clone $eventData['DTEND'];
            $parsedEvent['DTSTART']->add(new DateInterval('P' . $i . 'D'));
            $parsedEvent['DTEND']->add(new DateInterval('P' . $i . 'D'));

            // check if the parsed event date is not in the exdates array and also in selected range
            if (!in_array($parsedEvent['DTSTART']->format('Y-m-d'), $exdates) && $parsedEvent['DTSTART'] >= $startDate && $parsedEvent['DTSTART'] <= $endDate) {
                $events[] = $parsedEvent;
            }
        }

        #check if event is not exception, then continue
        if (in_array($eventData['DTSTART']->format('Y-m-d'), $exdates)) {
            continue;
        }
    }

    //check if the event date is in selected range
    if ($eventData['DTSTART'] >= $startDate && $eventData['DTSTART'] <= $endDate) {
        $events[] = $eventData;
    }
}

/* var_dump($events);
exit; */

//if no events, redirect back
if(empty($events)) {
    $_SESSION['error'] = 'Nebyly nalezeny žádné události v zadaném rozmezí.';
    header('Location: index.php');
    exit();
} else {
    unset($_SESSION['error']);
}

//group events by single days
$days = [];
foreach($events as $event) {
    $day = $event['DTSTART']->format('Y-m-d');
    $days[$day][] = $event;
}

//sort events by time
foreach($days as $key => $day) {
    usort($day, function($a, $b) {
        return $a['DTSTART'] <=> $b['DTSTART'];
    });
    $days[$key] = $day;
}

/* var_dump($days);
exit; */

$groupedEvents = [];
//group events happening at the same time
foreach ($days as &$day) {
    $grouped = [];
    foreach ($day as $event) {
        foreach ($grouped as $key => $group) {
            foreach ($group as $groupEvent) {
                if($event['DTSTART'] < $groupEvent['DTEND'] && $event['DTEND'] > $groupEvent['DTSTART']) {
                    $grouped[$key][] = $event;
                    continue 3;
                }
            }
        }
        $grouped[] = [$event];
    }
    $day = $grouped;
}

//restructure events happening at the same time
foreach ($days as &$day) {
    foreach ($day as &$group) {
        if (count($group) > 2) {
            //find the longest event key
            $longest = 0;
            for ($i=0; $i < count($group); $i++) { 
                if ($group[$i]['DTEND']->getTimestamp() - $group[$i]['DTSTART']->getTimestamp() > $group[$longest]['DTEND']->getTimestamp() - $group[$longest]['DTSTART']->getTimestamp()) {
                    $longest = $i;
                }
            }

            $group[] = $group;
            unset($group[array_key_last($group)][$longest]);
            //remove all keys except the longest
            $group = [$group[$longest], $group[array_key_last($group)]];
        }
    }
}

//do not remove. It's for reference binding
unset($day);

/* var_dump($days);
exit; */

$week_days = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
$food = ['snídaně', 'svačina', 'oběd', 'večeře'];

//load template
include 'templates/' . $_POST['template'] . '.php';
?>
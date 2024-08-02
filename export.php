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
$endDate = new DateTime($dateRange[1]);
$endDate->add(new DateInterval('P1D')); //add one day

//get events in date range
$events = [];
$recurrences = [];

foreach ($ics as $event) {
    $event = array_map('rtrim', explode("\n", $event));
    //delete empty lines and renumerate array
    $event = array_values(array_filter($event));

    /* var_dump($event);
    exit; */

    $eventParsed = [];

    // Merge lines starting with space to the last line without space. Add correct data to $eventParsed
    $mergedLine = "";
    for ($i=0; $i < count($event); $i++) {
        if ($i == 0) {
            $mergedLine = $event[$i];
        }
        if (substr($event[$i], 0, 1) != ' ') {
            $mergedLine = $event[$i];
            $eventParsed[] = $mergedLine;
        } else {
            $mergedLine .= substr($event[$i], 1);
        }
    }

    $keywords = ['DTSTART', 'DTEND', 'RRULE', 'EXDATE', 'SUMMARY', 'DESCRIPTION', 'RECURRENCE-ID'];
    
    //keep only lines with keywords
    $eventParsed = array_filter($eventParsed, function($line) use ($keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($line, $keyword) === 0) {
                return true;
            }
        }
    });

    /* var_dump($eventParsed);
    exit; */

    $eventData = [];

    // Parse event data
    foreach ($eventParsed as $line) {
        $parts = explode(';', $line);

        //if part 0 contains ":"
        if (count($parts) != 2 || (count($parts) == 2 && strpos($parts[0], ':'))) {
            $parts = explode(':', $line);
        }

        if (!array_key_exists(1, $parts)) {
            var_dump("error with line parts");
            var_dump($parts);
            var_dump($line);
            var_dump($eventParsed);
            exit;
        }

        //if array key already exists, create array
        #$eventData[$parts[0]] = $parts[1];        
        if (array_key_exists($parts[0], $eventData)) {
            if (is_array($eventData[$parts[0]])) {
                $eventData[$parts[0]][] = $parts[1];
            } else {
                $eventData[$parts[0]] = [$eventData[$parts[0]], $parts[1]];
            }
        } else {
            $eventData[$parts[0]] = $parts[1];
        }
    }

    /* var_dump($eventData);
    exit; */

    if (isset($eventData['DTSTART']) && isset($eventData['DTEND'])) {
        //handle dtstart and dtend with or without timezone
        if (isset($eventData['EXDATE']) && is_array($eventData['EXDATE'])) {
            var_dump("exdate is array");
            var_dump($eventData['EXDATE']);
            exit;
        } else {
            continue;
        }

        $dates = ['DTSTART', 'DTEND'];
        if (isset($eventData['EXDATE'])) {
            $dates[] = 'EXDATE';
        }

        foreach ($dates as $key) {
            $parts = explode(':', $eventData[$key]);

            if (count($parts) == 2) {
                if (strpos($parts[0], 'DATE')) { //event doesn't have time set
                    //skip the event if it's all day event
                    continue 2;
                } else {
                    $eventData[$key] = new DateTime($parts[1], new DateTimeZone('Europe/Prague'));
                }
            } else {
                //event has GMT+0 timezone and need to be converted to Europe/Prague
                $eventData[$key] = new DateTime($parts[0], new DateTimeZone('GMT'));
                $eventData[$key]->setTimezone(new DateTimeZone('Europe/Prague'));
            }
        }

        // If DTSTART or DTEND time is 00:00:00, skip the event
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
                var_dump($eventData['EXDATE']);
                exit;
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

        // check if event is not exception, then continue
        if (in_array($eventData['DTSTART']->format('Y-m-d'), $exdates)) {
            continue;
        }
    }

    // Handle recurrence exceptions
    if (isset($eventData['RECURRENCE-ID'])) {
        $recurrenceId = new DateTime($eventData['RECURRENCE-ID'], new DateTimeZone('Europe/Prague'));
        $recurrences[$recurrenceId->format('Y-m-d H:i:s')] = $eventData;
        continue;
    }

    // Check if the event date is in the selected range
    if ($eventData['DTSTART'] >= $startDate && $eventData['DTSTART'] <= $endDate) {
        $events[] = $eventData;
    }
}

foreach ($events as &$event) {
    $eventKey = $event['DTSTART']->format('Y-m-d H:i:s');
    if (isset($recurrences[$eventKey])) {
        $event = $recurrences[$eventKey];
    }
}

var_dump('end of events');
var_dump($events);
exit;

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
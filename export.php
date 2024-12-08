<?php

session_start();

//redirect back if file, daterange or template is missing. template must be default or ursus
if($_FILES['file']['size'] == 0 || !isset($_POST['daterange']) || !isset($_POST['template']) || !in_array($_POST['template'], ['default', 'ursus', 'pecka'])) {
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

function convertDate($date) {
    $parts = explode(':', $date);

    if (count($parts) == 2) {
        if (strpos($parts[0], 'DATE')) { //event doesn't have time set
            return False;
        } else {
            return new DateTime($parts[1], new DateTimeZone('Europe/Prague'));
        }
    } else {
        //event has GMT+0 timezone and need to be converted to Europe/Prague
        $date = new DateTime($parts[0], new DateTimeZone('GMT'));
        $date->setTimezone(new DateTimeZone('Europe/Prague'));
    }

    return $date;
}

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

    $keywords = ['DTSTART', 'DTEND', 'RRULE', 'EXDATE', 'SUMMARY', 'DESCRIPTION', 'RECURRENCE-ID', 'UID'];
    
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
        $dates = ['DTSTART', 'DTEND'];

        foreach ($dates as $key) {
            $date = convertDate($eventData[$key]);
            if ($date) {
                $eventData[$key] = $date;
            } else {
                continue 2;
            }
        }                        

        // If DTSTART or DTEND time is 00:00:00, skip the event
        if ($eventData['DTSTART']->format('H:i:s') == '00:00:00' || $eventData['DTEND']->format('H:i:s') == '00:00:00') {
            continue;
        }

        // parse rrule
        if (isset($eventData['RRULE'])) {
            $rrule = explode('=', explode(';', $eventData['RRULE'])[1]);

            $rruleType = $rrule[0];
            $rruleValue = $rrule[1];

            if ($rruleType == 'UNTIL') {
                $eventData['RRULE'] = convertDate($rruleValue);
            } else {
                // minus one, bcs first event is already in the array
                $eventData['RRULE'] = clone $eventData['DTSTART'];                
                $eventData['RRULE'] = $eventData['RRULE']->add(new DateInterval('P' . ($rruleValue - 1) . 'D'));
            }

            //if exdate exists, convert it to datetime
            if (isset($eventData['EXDATE'])) {
                if (is_array($eventData['EXDATE'])) {
                    $exdates = [];
                    foreach ($eventData['EXDATE'] as $exdate) {
                        $exdates[] = convertDate($exdate)->format('Y-m-d');
                    }
                } else {
                    $exdates = [convertDate($eventData['EXDATE'])->format('Y-m-d')];
                }
                $eventData['EXDATE'] = $exdates;
            }
            
            //check if event is in the selected range. Must count with rule, which is recurring event from DTSTART to UNTIL (RRULE)
            if ($eventData['RRULE'] < $startDate || $eventData['DTSTART'] > $endDate) {
                /* if ($eventData['DTSTART']->format('y-m') == '24-08') {
                    var_dump($eventData);
                    var_dump($eventData['DTSTART'] < $startDate && $eventData['DTEND'] < $startDate, $eventData['DTSTART'] > $endDate && $eventData['DTEND'] > $endDate);
                    exit;
                } */
                continue;
            }
        } else {            
            //check if event is in the selected range            
            if ($eventData['DTSTART'] > $endDate || $eventData['DTEND'] < $startDate) {
                continue;
            }
        }

        /* if ($eventData['SUMMARY'] == 'Budíček' && $eventData['DTSTART']->format('y-m') == '24-08') {
            var_dump($eventData);
            exit;
        } else {
            continue;
        } */
    } else {
        continue;
    }

    /* if ($eventData['SUMMARY'] == 'Budíček') {
        var_dump($eventData);
        exit;
    } else {
        continue;
    } */

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
    
    if (isset($eventData['RRULE'])) {
        for ($i=1; $i <= $eventData['DTSTART']->diff($eventData['RRULE'])->days; $i++) { 
            $parsedEvent = $eventData;
            $parsedEvent['DTSTART'] = clone $eventData['DTSTART'];
            $parsedEvent['DTEND'] = clone $eventData['DTEND'];
            $parsedEvent['DTSTART']->add(new DateInterval('P' . $i . 'D'));
            $parsedEvent['DTEND']->add(new DateInterval('P' . $i . 'D'));

            // check if the parsed event date is not in the exdates array and also in selected range
            if (!in_array($parsedEvent['DTSTART']->format('Y-m-d'), $eventData['EXDATE'] ?? []) && $parsedEvent['DTSTART'] >= $startDate && $parsedEvent['DTSTART'] <= $endDate) {
                $events[] = $parsedEvent;
            }
        }

        // check if event is not exception, then continue
        if (in_array($eventData['DTSTART']->format('Y-m-d'), $eventData['EXDATE'] ?? [])) {
            continue;
        }
    }

    // Handle recurrence exceptions
    if (isset($eventData['RECURRENCE-ID'])) {
        $recurrences[convertDate($eventData['RECURRENCE-ID'])->format('Y-m-d H:i:s')][$eventData['UID']] = $eventData;
        continue;
    }    

    #var_dump($eventData);

    #check if event dtstart and dtend are in date range
    if ($eventData['DTSTART'] >= $startDate && $eventData['DTEND'] <= $endDate) {
        $events[] = $eventData;
    }
}

#var_dump($events);
#var_dump($recurrences);
#exit;

// Handle recurrence exceptions
foreach ($events as &$event) {
    $eventKey = $event['DTSTART']->format('Y-m-d H:i:s');
    if (isset($recurrences[$eventKey][$event['UID']])) {
        $event = $recurrences[$eventKey][$event['UID']];
    }
}
unset($event);

#var_dump('end of events');
#exit;
/* var_dump($events);
exit; */


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

ksort($days);


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
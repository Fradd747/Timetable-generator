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

//get events in date range
$events = [];

foreach($ics as $event) {
    $event = explode("\n", $event);
    $eventData = [];

    /* var_dump($event);
    exit; */

    foreach($event as $key => $line) {
        if (empty(trim($line))) {
            unset($event[$key]);
            continue;
        }
        $line = array_map('trim', explode(':', $line, 2));
        if ($line[0] == 'DESCRIPTION') {
            $data = array_map('trim', explode('\n', $line[1]));
            //fix escaped commas and trim spaces
            foreach($data as &$d) {
                $d = str_replace('\,', ',', $d);
                $d = trim($d);
            }
            if (in_array('program', array_map('strtolower', $data))) {
                $eventData['PROGRAM'] = true;
                unset($data[array_search('program', $data)]); 
            }

            if (in_array('povinný', $data)) {
                $eventData['REQUIRED'] = true;
                unset($data[array_search('povinný', $data)]); 
            }
            $eventData[$line[0]] = $data;
            continue;
        }
        if (count($line) == 2) {
            $eventData[$line[0]] = trim($line[1]);
        }
    }

    if(isset($eventData['DTSTART']) && isset($eventData['DTEND'])) {
        //change timezone to CET
        $eventData['DTSTART'] = new DateTime($eventData['DTSTART'], new DateTimeZone('Europe/Prague'));
        $eventData['DTEND'] = new DateTime($eventData['DTEND'], new DateTimeZone('Europe/Prague'));
        $eventData['DTSTART']->setTimezone(new DateTimeZone('Europe/Prague'));
        $eventData['DTEND']->setTimezone(new DateTimeZone('Europe/Prague'));
        /* var_dump($eventData);
        exit; */
        if($eventData['DTSTART'] >= $startDate && $eventData['DTSTART'] <= $endDate) {
            $events[] = $eventData;
        }
    }
}

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
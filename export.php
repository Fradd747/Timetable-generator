<?php

//redirect back if file or daterange is missing
if($_FILES['file']['size'] == 0 || !isset($_POST['daterange'])) {
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
            if (in_array('program', $data)) {
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


/* foreach ($events as $event) {
    echo $event['DTSTART']->format('d') . '<br>';
} */

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

//group events happening at the same time
foreach ($days as $events) {
    /* var_dump($events);
    exit; */
    $groupedEvents = [];
    foreach ($events as $event) {
        $added = false;
        foreach ($groupedEvents as $groupedEvent) {
            var_dump($groupedEvents);
            exit;
            if ($event['DTSTART'] < $groupedEvent['DTEND'] && $event['DTEND'] > $groupedEvent['DTSTART']) {
                $groupedEvent[] = $event;
                $added = true;
                break;
            }
        }
        if (!$added) {
            $groupedEvents[] = [$event];
        }
    }
    $day = $groupedEvents;
}

var_dump($groupedEvents);
exit;








/* foreach($days as &$day) {
    $groupedEvents = [];
    foreach($day as $event) {
        $added = false;
        foreach($groupedEvents as $group) {
            foreach ($group as $groupEvent) {
                if($event['DTSTART'] < $groupEvent['DTEND'] && $event['DTEND'] > $groupEvent['DTSTART']) {
                    $group[] = $event;
                    $added = true;
                    break;
                }
            }
        }
        if(!$added) {
            $groupedEvents[] = [$event];
        }
    }
    $day = $groupedEvents;
} */


var_dump($days);
exit;

//group events happening at the same time, Independently of their start and end time



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

unset($day);

var_dump($days);
exit;

$dny_v_tydnu = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
$jidla = ['snídaně', 'svačina', 'oběd', 'večeře'];

function printEvent(bool $program = false, bool $required = false, DateTime $start, DateTime $end, string $summary, array $description = null, string $classes = '') {    
    //according to time, calculate height of box. 2rem = 30min
    $height = (($end->getTimestamp() - $start->getTimestamp()) / 900) * 0.8;
    $time = $start->format('H:i') .' - '. $end->format('H:i');
    $food = in_array(trim(strtolower($summary)), $GLOBALS['jidla']);
    //var_dump($height);
    echo '
        <div class="box '. ($program ? 'gray_box' : '') . ($food ? 'black_box' : '') . ' min-h-['. $height .'rem] ' . $classes .'">
            <div class="inner_box">        
                <p>'. $time .'</p>
                <div>
                    <p class="font-bold">'. $summary .'</p>';
                    if (!is_null($description)) {
                        echo '<p style="line-height: 17px">';
                        foreach ($description as $line) {
                            echo $line . '<br>';
                        }
                    }
          echo '</div>
            </div>';
            if ($required) {
                echo '
                <div class="flex justify-end items-center pr-3">
                    <img class="!h-11 !w-11" src="pozdrav.png">
                </div>';
            }
        echo '</div>';
}

?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com/3.3.0"></script>
    <style>
        body {
            -webkit-print-color-adjust:exact !important;
            print-color-adjust:exact !important;
        }
        @media print {
            .pagebreak { page-break-before: always; 
            }
        }
        @font-face {
            font-family: 'themix';
            src: url('fonts/themix-bold.ttf') format('truetype');
            font-style: bold;
            font-weight: normal;
        }
        @font-face {
            font-family: 'themix';
            src: url('fonts/themix-normal.ttf') format('truetype');
            font-style: normal;
            font-weight: normal;
        }
        @font-face {
            font-family: 'skautbold';
            src: url('fonts/skautbold.ttf') format('truetype');
            font-style: normal;
            font-weight: normal;
        }
        .box {
            padding-top: 0.25rem;
            padding-bottom: 0.25rem; 
            padding-left: 0.5rem;
            padding-right: 0.5rem; 
            padding-left: 0.75rem; 
            border-radius: 0.5rem;
            border-width: 1px; 
            border-color: #1F2937;
            min-height: 2rem;
            /* height: fit-content; */
            display: grid; 
            font-size: 0.9rem;
            grid-template-columns: 82% 18%;
        }
        .inner_box {
            display: flex;
            flex-direction: row;
            gap: 0.5rem;
            align-items: start;
            justify-content: start;
        }
        .black_box {
            color: #ffffff; 
            background-color: #1F2937; 
        }
        .gray_box {
            color: #1F2937; 
            background-color: #cecece;
        }
    </style>
    <title>Document</title>
</head>
<body>
    <?php foreach ($days as $day => $events) { ?>
        <h1 class="text-center font-['skautbold'] text-3xl mb-5"><?= $dny_v_tydnu[(new DateTime($day))->format('w')] . ' ' . (new DateTime($day))->format('d. m.') ?></h1>
        <div id="boxes" class="flex gap-1 flex-col font-['themix']">
        <div class="grid grid-cols-[70%_30%]">
                <div class="flex flex-col gap-1">
                    <?php for ($i=0; $i < 2; $i++) { 
                        printEvent(($events[$i][0]['PROGRAM'] ?? false), ($events[$i][0]['REQUIRED'] ?? false), $events[$i][0]['DTSTART'], $events[$i][0]['DTEND'], $events[$i][0]['SUMMARY'], $events[$i][0]['DESCRIPTION'] ?? null);
                    } ?>
            </div>
                <div class="flex justify-center items-center">
                    <div class="flex flex-row gap-1 justify-center items-center w-[85%]">
                        <img class="h-12 aspect-square" src="pozdrav.png">
                        <p class="text-[0.5rem]">Takto označený program souvisí s povinným úkolem ve stezce. K jeho splnění nebude další příležitost.</p>
                    </div>
                </div>
            </div>
        <?php for ($i=2; $i < count($events); $i++) { 
            if (count($events[$i]) > 1) {
                /* number of events in one group */
                /* two events at the same time */
                echo '<div class="grid grid-cols-2 gap-1">';
                    //check if any of the arrays in the $events[$i] array is an array of arrays
                    $isArrayOfArrays = false;
                    foreach ($events[$i] as $event) {
                        if (array_filter($event, 'is_array') === $event) {
                            $isArrayOfArrays = true;
                            break;
                        }            
                    }
                    foreach ($events[$i] as $event) {
                        if (array_filter($event, 'is_array') === $event) {
                            echo '<div class="flex flex-col gap-1">';
                            foreach ($event as $event) {
                                printEvent(($event['PROGRAM'] ?? false), ($event['REQUIRED'] ?? false), $event['DTSTART'], $event['DTEND'], $event['SUMMARY'], $event['DESCRIPTION'] ?? null);
                            }
                            echo '</div>';
                        } else {
                            printEvent(($event['PROGRAM'] ?? false), ($event['REQUIRED'] ?? false), $event['DTSTART'], $event['DTEND'], $event['SUMMARY'], $event['DESCRIPTION'] ?? null, !$isArrayOfArrays ? 'h-fit' : '');
                        }
                    }
                echo '</div>';
                continue;
            }
            printEvent(($events[$i][0]['PROGRAM'] ?? false), ($events[$i][0]['REQUIRED'] ?? false), $events[$i][0]['DTSTART'], $events[$i][0]['DTEND'], $events[$i][0]['SUMMARY'], $events[$i][0]['DESCRIPTION'] ?? null);    
        }
        //if it's not last iteration, add page break
        if($day != array_key_last($days)) {
            echo '<div class="pagebreak"></div>';
        }
    }
    ?>    
</body>
<script>
    //window.onload = function() { window.print(); }
</script>
</html>
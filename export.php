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
$event = explode("\n", $ics[1]);

foreach($ics as $event) {
    $event = explode("\n", $event);
    $eventData = [];
    /* var_dump($event);
    exit; */
    foreach($event as &$line) {
        if (empty(trim($line))) {
            continue;
        }
        $line = explode(':', $line);
        if ($line[0] == 'DESCRIPTION') {
            $data = explode('\n', $line[1]);
            //fix escaped commas and trim spaces
            foreach($data as &$d) {
                $d = str_replace('\,', ',', $d);
                $d = trim($d);
            }
            /* var_dump($data, in_array('program', $data));
            exit; */
            if (in_array('program', $data)) {
                /* var_dump($data);
                exit; */
                $eventData['PROGRAM'] = true;
                unset($data[array_search('program', $data)]); 
            } else {
                $eventData['PROGRAM'] = false;
            }
            $eventData[$line[0]] = $data;
            continue;
        }
        if (count($line) == 2) {
            $eventData[$line[0]] = trim($line[1]);
        }
    }
    /* var_dump($eventData);
    exit; */

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

//group events by single days and sort the by time
$days = [];
foreach($events as $event) {
    $day = $event['DTSTART']->format('Y-m-d');
    if(!isset($days[$day])) {
        $days[$day] = [];
    }
    $days[$day][] = $event;
}
foreach($days as &$day) {
    usort($day, function($a, $b) {
        return $a['DTSTART'] <=> $b['DTSTART'];
    });
}

//group events that happen at the same time
foreach($days as &$day) {
    $grouped = [];
    $grouped[] = [$day[0]];
    for($i = 1; $i < count($day); $i++) {
        if($day[$i]['DTSTART'] == $day[$i-1]['DTSTART']) {
            $grouped[count($grouped)-1][] = $day[$i];
        } else {
            $grouped[] = [$day[$i]];
        }
    }
    $day = $grouped;
}


$dny_v_tydnu = ['neděle', 'pondělí', 'úterý', 'středa', 'čtvrtek', 'pátek', 'sobota'];
$jidla = ['snídaně', 'svačina', 'oběd', 'večeře'];

/* var_dump($days);
exit; */

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
            display: flex;
            align-items: start;
            justify-content: start;
            gap: 0.5rem;
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
    <?php 
        foreach ($days as $day => $events) {
            echo '<h1 class="text-center font-[\'skautbold\'] text-3xl mb-5">'. $dny_v_tydnu[(new DateTime($day))->format('w')] . ' ' . (new DateTime($day))->format('d. m.') .'</h1>';
            echo '<div id="boxes" class="flex gap-1 flex-col font-[\'themix\']">';
            /* box for first two rows */
            echo '<div class="grid grid-cols-[70%_30%]">
                    <div class="flex flex-col gap-1">';
            /* foreach ($events as $event) { */
            for ($i=0; $i < 2; $i++) { 
                /* var_dump($event);
                exit; */
                echo '<div class="box ' . ((in_array(trim(strtolower($events[$i][0]['SUMMARY'])), $jidla)) ? 'black_box' : '') . '">
                        <p>'. $events[$i][0]['DTSTART']->format('H:i') .' - '. $events[$i][0]['DTEND']->format('H:i') .'</p>
                        <p class="font-bold">'.$events[$i][0]['SUMMARY'].'</p>
                    </div>';
            }
            echo '</div>
                <div class="flex justify-center items-center">
                        <div class="flex flex-row gap-1 justify-center items-center w-[85%]">
                            <img class="h-12 aspect-square" src="pozdrav.png">
                            <p class="text-[0.5rem]">Takto označený program souvisí s povinným úkolem ve stezce. K jeho splnění nebude další příležitost.</p>
                        </div>
                    </div>
                </div>';
            /* end of box first two rows */
            for ($i=2; $i < count($events); $i++) { 
                if (count($events[$i]) > 1) {
                    echo '<div class="grid grid-cols-2 gap-1">';
                    for ($j=0; $j < count($events[$i]); $j++) { 
                        /* var_dump($events[$i]);
                        exit; */
                        echo '<div class="box '. (($events[$i][$j]['PROGRAM'] ?? false) ? 'gray_box' : '') .' min-h-[6rem]">
                                <p class="p-0 m-0 float-left">'. $events[$i][$j]['DTSTART']->format('H:i') .' - '. $events[$i][$j]['DTEND']->format('H:i') .'</p>
                                <div class="p-0 m-0 float-left">
                                    <p class="font-bold">'. $events[$i][$j]['SUMMARY'] .'</p>';
                                    if (isset($events[$i][$j]['DESCRIPTION'])) {
                                        echo '<p style="line-height: 17px; margin-top: -0.2rem">';
                                        foreach ($events[$i][$j]['DESCRIPTION'] as $line) {
                                            echo $line . '<br>';
                                        }
                                    }
                                echo '</div>
                            </div>';
                    }
                    echo '</div>';
                    continue;
                }
                echo '<div class="box ' . 
                        ((in_array(trim(strtolower($events[$i][0]['SUMMARY'])), $jidla)) 
                        ? (($events[$i][0]['PROGRAM'] ?? false) ? 'gray_box' : 'black_box') : '') . '">
                        
                        <p>'. $events[$i][0]['DTSTART']->format('H:i') .' - '. $events[$i][0]['DTEND']->format('H:i') .'</p>
                        <p class="font-bold">'.$events[$i][0]['SUMMARY'].'</p>
                    </div>';
            }
            //if it's not last iteration, add page break
            if($day != array_key_last($days)) {
                echo '<div class="pagebreak"></div>';
            }
        }
    ?>    
</body>
</html>
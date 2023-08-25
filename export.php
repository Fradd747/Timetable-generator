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
        if (count($line) == 2) {
            $eventData[$line[0]] = $line[1];
        }
    }
    /* var_dump($eventData);
    exit; */
    if(isset($eventData['DTSTART']) && isset($eventData['DTEND'])) {
        $eventData['DTSTART'] = new DateTime($eventData['DTSTART']);
        $eventData['DTEND'] = new DateTime($eventData['DTEND']);
        /* var_dump($eventData);
        exit; */
        if($eventData['DTSTART'] >= $startDate && $eventData['DTSTART'] <= $endDate) {
            $events[] = $eventData;
        }
    }
}

//group events by single days
$days = [];
foreach($events as $event) {
    $day = $event['DTSTART']->format('Y-m-d');
    if(!isset($days[$day])) {
        $days[$day] = [];
    }
    $days[$day][] = $event;
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
            echo '<div id="boxes" class="flex gap-1 flex-col font-[\'themix\']">
                <div class="grid grid-cols-[70%_30%]">
                    <div class="flex flex-col gap-1">';
            foreach ($events as $event) {
                /* var_dump($event);
                exit; */
                echo '<div class="box ' . ((in_array(trim(strtolower($event['SUMMARY'])), $jidla)) ? 'black_box' : '') . '">
                        <p>'. $event['DTSTART']->format('h:i') .' - '. $event['DTEND']->format('h:i') .'</p>
                        <p class="font-bold">'.$event['SUMMARY'].'</p>
                    </div>';
            }
            /* format
                <div class="box">
                    <p>07:30 - 08:00</p>
                    <p class="font-bold">Budíček, rozcvička</p>
                </div>
            */
            
        }
    ?>
    <div class="box">
                    <p>07:30 - 08:00</p>
                    <p class="font-bold">Budíček, rozcvička</p>
                </div>
                <div class="box black_box">
                    <p>07:30 - 08:00</p>
                    <p class="font-bold">Snídaně</p>
                </div>
            </div>
            <div class="flex justify-center items-center">
                <div class="flex flex-row gap-1 justify-center items-center w-[85%]">
                    <img class="h-12 aspect-square" src="pozdrav.png">
                    <p class="text-[0.5rem]">Takto označený program souvisí s povinným úkolem ve stezce. K jeho splnění nebude další příležitost.</p>
                </div>
            </div>
        </div>
        <!-- one hour -->
        <div class="box min-h-[4rem]">
            <p>07:00 - 08:00</p>
            <p class=" font-bold">Na hodinu</p>
        </div>
        <!-- gray comment -->
        <div class="box gray_box min-h-[6rem]">
            <p class="p-0 m-0 float-left">07:00 - 09:00</p>
            <div class="p-0 m-0 float-left">
                <p class="font-bold">Nazev akce</p>
                <p style="line-height: 14px; margin-top: -0.3rem">sraz u recepce<br>TNT, KAR</p>
            </div>
        </div>
        <!-- two next to each other -->
        <div class="grid grid-cols-2 gap-1">
            <div class="box gray_box min-h-[6rem]">
                <p class="p-0 m-0 float-left">07:00 - 09:00</p>
                <div class="p-0 m-0 float-left">
                    <p class="font-bold">Nazev akce</p>
                    <p style="line-height: 14px; margin-top: -0.3rem">sraz u recepce<br>TNT, KAR</p>
                </div>
            </div>
            <div class="box gray_box min-h-[6rem]">
                <p class="p-0 m-0 float-left">07:00 - 09:00</p>
                <div class="p-0 m-0 float-left">
                    <p class="font-bold">Nazev akce</p>
                    <p style="line-height: 14px; margin-top: -0.3rem">sraz u recepce<br>TNT, KAR</p>
                </div>
            </div>
        </div>
    </div>
    <div class="pagebreak"></div>
    <div>this is a kitten</div>
</body>
</html>
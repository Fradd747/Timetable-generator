<?php 

    function printEvent(DateTime $start, DateTime $end, string $summary, array $description = null, string $classes = '', bool $activity = false, $topic = false, $organizational = false) {    
        //according to time, calculate height of box. 2rem = 30min
        $height = (($end->getTimestamp() - $start->getTimestamp()) / 900) * 0.75;
        $time = $start->format('H:i') .' - '. $end->format('H:i');
        //search $summary for any word from global array $food
        $food = false;
        foreach ($GLOBALS['food'] as $foodType) {
            if (mb_stripos($summary, $foodType) !== false) {
                $food = true;
            }
        }
        echo '
            <div class="box '. ($activity ? 'bg-[#5E2281] border-[#5E2281] text-[#FCC11E]' : '') . ($topic ? 'bg-[#FCC11E] border-[#FCC11E] text-[#5E2281]' : '') . ($organizational ? 'bg-[#000000] border-[#000000] text-[#FCC11E]' : '') . ' min-h-['. $height .'rem] ' . $classes .'">
                <div class="inner_box">        
                    <p class="font-bold">'. $time .'</p>
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
            echo '</div>';
    }

?>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generátor harmonogramu</title>
    <link rel="icon" href="images/logo_without_text.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php foreach ($days as $day => $events) { ?>
        <h1 class="text-center font-['skautbold'] text-3xl mb-5 text-[#5E2281]"><?= $week_days[(new DateTime($day))->format('w')] . ' ' . (new DateTime($day))->format('d. m. Y') ?></h1>
        <div id="boxes" class="flex gap-1 flex-col font-['themix']">
        
        <?php for ($i=0; $i < count($events); $i++) { 
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
                                printEvent($event['DTSTART'], $event['DTEND'], $event['SUMMARY'], $event['DESCRIPTION'] ?? null, '', ($event['ACTIVITY'] ?? false), ($event['TOPIC'] ?? false), ($event['ORGANIZATIONAL'] ?? false));
                            }
                            echo '</div>';
                        } else {
                            printEvent($event['DTSTART'], $event['DTEND'], $event['SUMMARY'], $event['DESCRIPTION'] ?? null, !$isArrayOfArrays ? 'h-fit' : '', ($event['ACTIVITY'] ?? false), ($event['TOPIC'] ?? false), ($event['ORGANIZATIONAL'] ?? false));
                        }
                    }
                echo '</div>';
                continue;
            }
            printEvent($events[$i][0]['DTSTART'], $events[$i][0]['DTEND'], $events[$i][0]['SUMMARY'], $events[$i][0]['DESCRIPTION'] ?? null, '', ($events[$i][0]['ACTIVITY'] ?? false), ($events[$i][0]['TOPIC'] ?? false), ($events[$i][0]['ORGANIZATIONAL'] ?? false));
        }
        //if it's not last iteration, add page break
        if($day != array_key_last($days)) {
            echo '<div class="pagebreak"></div>';
        }
    }
    ?>    
</body>
<script>
    window.onload = function() { window.print(); }
</script>
</html>
<?php 

    function printEvent(bool $program = false, bool $required = false, DateTime $start, DateTime $end, string $summary, array $description = null, string $classes = '') {    
        //according to time, calculate height of box. 2rem = 30min
        $height = (($end->getTimestamp() - $start->getTimestamp()) / 900) * 0.8;
        $time = $start->format('H:i') .' - '. $end->format('H:i');
        $food = in_array(trim(strtolower($summary)), $GLOBALS['food']);
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
                        <img class="!h-11 !w-11" src="icons/hand.png">
                    </div>';
                }
            echo '</div>';
    }

?>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <title>Generátor harmonogramu</title>
</head>
<body>
    <?php foreach ($days as $day => $events) { ?>
        <h1 class="text-center font-['skautbold'] text-3xl mb-5"><?= $week_days[(new DateTime($day))->format('w')] . ' ' . (new DateTime($day))->format('d. m.') ?></h1>
        <div id="boxes" class="flex gap-1 flex-col font-['themix']">
        <!-- first two columns -->
        <div class="grid grid-cols-[70%_30%]">
                <div class="flex flex-col gap-1">
                    <?php for ($i=0; $i < 2; $i++) { 
                        printEvent(($events[$i][0]['PROGRAM'] ?? false), ($events[$i][0]['REQUIRED'] ?? false), $events[$i][0]['DTSTART'], $events[$i][0]['DTEND'], $events[$i][0]['SUMMARY'], $events[$i][0]['DESCRIPTION'] ?? null);
                    } ?>
            </div>
                <div class="flex justify-center items-center">
                    <div class="flex flex-row gap-1 justify-center items-center w-[85%]">
                        <img class="h-12 aspect-square" src="icons/hand.png">
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
    window.onload = function() { window.print(); }
</script>
</html>
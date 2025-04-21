<?php 

    function printEvent(DateTime $start, DateTime $end, string $summary, array $description = null, string $classes = '', bool $program = false, bool $required = false) {    
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
                        <img class="!h-11 !w-11" src="images/icons/mark.svg">
                    </div>';
                }
            echo '</div>';
    }

?>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generátor harmonogramu</title>
    <link rel="icon" href="images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php foreach ($days as $day => $events) { ?>
        <h1 class="text-center font-['skautbold'] text-3xl mb-5"><?= $week_days[(new DateTime($day))->format('w')] . ' ' . (new DateTime($day))->format('d. m.') ?></h1>
        <div id="boxes" class="flex gap-1 flex-col font-['themix']">
        
        <?php for ($i=0; $i < count($events); $i++) { 
            if (count($events[$i]) > 1) {
                /* number of events in one group */
                /* two events at the same time */
                echo '<div class="grid grid-cols-2 gap-1">';
                    //check if any of the arrays in the $events[$i] array is an array of arrays
                    $isArrayOfArrays = false;
                    foreach ($events[$i] as $event) {
                        // Check if $event is an array and all its elements are also arrays
                        if (is_array($event) && count(array_filter($event, 'is_array')) === count($event) && count($event) > 0) {
                            $isArrayOfArrays = true;
                            break;
                        }
                    }
                    foreach ($events[$i] as $event) {
                         // Check if $event is an array and all its elements are also arrays
                        if (is_array($event) && count(array_filter($event, 'is_array')) === count($event) && count($event) > 0) {
                            echo '<div class="flex flex-col gap-1">';
                            foreach ($event as $sub_event) { // Use a different variable name here
                                printEvent($sub_event['DTSTART'], $sub_event['DTEND'], $sub_event['SUMMARY'], $sub_event['DESCRIPTION'] ?? null, '', ($sub_event['PROGRAM'] ?? false), ($sub_event['REQUIRED'] ?? false));
                            }
                            echo '</div>';
                        } else if (is_array($event)) { // Ensure $event is an array before accessing keys
                            printEvent($event['DTSTART'], $event['DTEND'], $event['SUMMARY'], $event['DESCRIPTION'] ?? null, !$isArrayOfArrays ? 'h-fit' : '', ($event['PROGRAM'] ?? false), ($event['REQUIRED'] ?? false));
                        }
                    }
                echo '</div>';
                continue;
            }
             if (isset($events[$i][0]) && is_array($events[$i][0])) { // Check if the first element exists and is an array
                printEvent($events[$i][0]['DTSTART'], $events[$i][0]['DTEND'], $events[$i][0]['SUMMARY'], $events[$i][0]['DESCRIPTION'] ?? null, '', ($events[$i][0]['PROGRAM'] ?? false), ($events[$i][0]['REQUIRED'] ?? false));
             }
        }
        //if it's not last iteration, add page break
        if($day != array_key_last($days)) {
            echo '<div class="pagebreak"></div>';
        }
    }
    ?>    
    <div class="footer">
        Vygenerováno na harmonogram.skauting.cz
    </div>
</body>
<script>
    window.onload = function() { window.print(); }
</script>
</html>
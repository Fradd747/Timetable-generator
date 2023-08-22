<?php

use Dompdf\Dompdf;

//composer autoload
require 'vendor/autoload.php';

$file = file_get_contents('template.html');

$pdf = new Dompdf();
$pdf->loadHtml($file);

$pdf->render();

$pdf->stream("dompdf_out.pdf", array("Attachment" => false));

exit;


/* //read .ics file
$ical = file_get_contents('calendar.ics');

//group events by every begin:vevent and end:vevent
$events = explode("BEGIN:VEVENT", $ical);

//group events by days
$days = array();
foreach ($events as $event) {
    $day = substr($event, 8, 8);
    $days[$day][] = $event;
}
var_dump($events); */
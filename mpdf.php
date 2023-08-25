<?php

use Mpdf\Mpdf;
use Mpdf\Config\FontVariables;
use Mpdf\Config\ConfigVariables;

require 'vendor/autoload.php';

$html = file_get_contents('template.html');

$defaultConfig = (new ConfigVariables())->getDefaults();
$fontDirs = $defaultConfig['fontDir'];

$defaultFontConfig = (new FontVariables())->getDefaults();
$fontData = $defaultFontConfig['fontdata'];

$mpdf = new Mpdf([
    'fontDir' => array_merge($fontDirs, [
        __DIR__ . '/fonts',
    ]),
    'fontdata' => $fontData + [ // lowercase letters only in font key
        'themix' => [
            'R' => 'themix-normal.ttf',
            'B' => 'themix-bold.ttf',
        ]
    ],
    'default_font' => 'themix'
]);
$mpdf->WriteHTML($html);
$mpdf->Output();
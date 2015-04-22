<?php

require('../charset/charset.php');
require('../spider/functions.php');
require('../../lib/simple_html_dom.php');

$charsetDataDir = '../charset/data';

$samples=Array(
    0=>Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-utf8.stats"))),
    1=>Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-utf8.stats"))),
    2=>Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-utf8.stats"))),
    3=>Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-utf8.stats"))),
    4=>Array('charset' => 'WINDOWS-1251', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp1251.stats"))),
    5=>Array('charset' => 'KOI8-R', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-koi8.stats"))),
    6=>Array('charset' => 'CP866', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp866.stats"))),
    7=>Array('charset' => 'ISO-8859-5', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-iso-8859-5.stats"))),
    8=>Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-cp1252.stats"))),
    9=>Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-cp1252.stats"))),
    10=>Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-cp1252.stats"))),
    11=>Array('charset' => 'ISO-8859-1', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-iso-8859-1.stats"))),
    12=>Array('charset' => 'ISO-8859-1', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-iso-8859-1.stats"))),
    13=>Array('charset' => 'ISO-8859-1', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-iso-8859-1.stats"))),
);
//echo "<pre>"; print_r($samples); echo "</pre>"; exit();


$detector = new charsetdetector($samples);


$dirname='/home/dobro/wwwroot/cms/_/learning-set/out';

$logfile='./detect_encoding2.log';

$logfile=fopen($logfile, 'w');

$filelist = array_diff(scandir($dirname), array('..', '.'));
foreach($filelist as $filename){
    $str1 = file_get_contents($dirname.'/'.$filename);
    $html = str_get_html($str1);
    $text = preg_replace("/\\s+/",' ',$html->plaintext);

    
    $encoding = $detector->detect($text);


    $title = '';
    foreach ($html->find('meta') as $element) {
        if ($element->property == 'og:title') {
            $title = $element->content;
        }
    }
    if (!$title) {
        $title = $html->find("title", 0);
        if ($title) {
            $title = $title->plaintext;
        } else {
            $title = '';
        }
    }
    if ($encoding != 'UTF-8') {
        try {
            $title = iconv($encoding, 'UTF-8', $title);
        } catch (Exception $e) {
            
        }
    }

    echo $filename."\n{$encoding} \n{$title}\n\n";
    fwrite ($logfile , "{$filename}\t{$encoding}\t{$title}\n");
}

fclose($logfile);

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


//
//$filename = "rus.txt";
//$str = file_get_contents($filename);
//$encoding = $detector->detect($str);
//echo "<hr>{$filename} => " . $encoding . "<hr>";
//echo iconv($encoding, 'UTF-8', $str) . "<hr>";
//echo "<hr><hr><hr><hr>";
//
//
//
//
//$str1 = iconv('UTF-8','CP1251',$str);
//$encoding = $detector->detect($str1);
//echo "<hr>{$filename} => " . $encoding . "<hr>";
//echo iconv($encoding, 'UTF-8', $str1) . "<hr>";
//echo "<hr><hr><hr><hr>";
//
//
//$filename = "rus-koi8r.txt";
//$str1 = file_get_contents($filename);
//$encoding = $detector->detect($str1);
//echo "<hr>{$filename} => " . $encoding . "<hr>";
//echo iconv($encoding, 'UTF-8', $str1) . "<hr>";
//echo "<hr><hr><hr><hr>";
//
//
//
//
//
//$filename = "charset-utf8-1.txt";
//$str1 = file_get_contents($filename);
//$encoding = $detector->detect($str1);
//echo "<hr>{$filename} => " . $encoding . "<hr>";
//echo iconv($encoding, 'UTF-8', $str1) . "<hr>";
//echo "<hr><hr><hr><hr>";
//
//exit('1');

// $filename = "http://www.washingtonpost.com/world/europe/russias-anti-us-sentiment-now-is-even-worse-than-it-was-in-soviet-union/2015/03/08/b7d534c4-c357-11e4-a188-8e4971d37a8d_story.html";
// $filename = "http://www.worldaffairsjournal.org/blog/elisabeth-braw/kremlin%E2%80%99s-influence-game";
// $filename = 'http://geopolitika.ru/article/rossiya-i-latinskaya-amerika-na-fone-zapadnyh-sankciy';
// $filename = 'http://inopressa.ru/article/11Mar2015/times/fin_putin.html';
// $filename = 'http://mobile.nytimes.com/2015/03/10/business/dealbook/in-russia-the-well-for-corporate-bailouts-might-run-dry.html';
// $filename = 'http://www.worldaffairsjournal.org/blog/elisabeth-braw/kremlin%E2%80%99s-influence-game';
// $filename = 'http://www.novayagazeta.ru/inquests/67574.html';
// $filename = 'http://www.newsbalt.ru/detail/?ID=17584';
// $filename = 'http://geopolitika.ru/article/rossiya-i-latinskaya-amerika-na-fone-zapadnyh-sankciy';
$filename = 'http://www.newtimes.ru/articles/detail/95732';


$str1 = file_get_contents($filename);
$html = str_get_html($str1);
$text=$html->plaintext;

$encoding = $detector->detect($text);

echo "<hr>{$filename} => " . $encoding . "<hr>";
echo iconv($encoding, 'UTF-8', $text) . "<hr>";
echo "<hr><hr><hr><hr>";

<?php

include('../charset/charset.php');


$charsetDataDir='../charset/data';

$detector = new charsetdetector(Array(
    Array(
        'charset' => 'UTF-8',
        'stats' => unserialize(file_get_contents("$charsetDataDir/rus-utf8.stats"))
    ),
    Array(
        'charset' => 'UTF-8',
        'stats' => unserialize(file_get_contents("$charsetDataDir/deu-utf8.stats"))
    ),
    Array(
        'charset' => 'UTF-8',
        'stats' => unserialize(file_get_contents("$charsetDataDir/fra-utf8.stats"))
    ),
    Array(
        'charset' => 'UTF-8',
        'stats' => unserialize(file_get_contents("$charsetDataDir/eng-utf8.stats"))
    ),
    Array(
        'charset' => 'WINDOWS-1251',
        'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp1251.stats"))
    ),
    Array(
        'charset' => 'KOI8-R',
        'stats' => unserialize(file_get_contents("$charsetDataDir/rus-koi8.stats"))
    ),
    Array(
        'charset' => 'CP866',
        'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp866.stats"))
    ),
    Array(
        'charset' => 'ISO-8859-5',
        'stats' => unserialize(file_get_contents("$charsetDataDir/rus-iso-8859-5.stats"))
    ),
    Array(
        'charset' => 'WINDOWS-1252',
        'stats' => unserialize(file_get_contents("$charsetDataDir/deu-cp1252.stats"))
    ),
    Array(
        'charset' => 'WINDOWS-1252',
        'stats' => unserialize(file_get_contents("$charsetDataDir/fra-cp1252.stats"))
    ),
    Array(
        'charset' => 'WINDOWS-1252',
        'stats' => unserialize(file_get_contents("$charsetDataDir/eng-cp1252.stats"))
    ),
    Array(
        'charset' => 'ISO-8859-1',
        'stats' => unserialize(file_get_contents("$charsetDataDir/deu-iso-8859-1.stats"))
    ),
    Array(
        'charset' => 'ISO-8859-1',
        'stats' => unserialize(file_get_contents("$charsetDataDir/eng-iso-8859-1.stats"))
    ),
    Array(
        'charset' => 'ISO-8859-1',
        'stats' => unserialize(file_get_contents("$charsetDataDir/fra-iso-8859-1.stats"))
    ),
));



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



$filename = "charset-utf8-2.txt";
$str1 = file_get_contents($filename);
$encoding = $detector->detect($str1);
echo "<hr>{$filename} => " . $encoding . "<hr>";
echo iconv($encoding, 'UTF-8', $str1) . "<hr>";
echo "<hr><hr><hr><hr>";
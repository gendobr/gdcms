<?php

include('../charset/charset.php');




$detector=new charsetdetector();

$filename="charset-utf-8.txt";
$str=file_get_contents($filename);
echo "<hr>{$filename} => ".$detector->detect($str)."<hr>";



$filename="charset-cp1251.txt";
$str=file_get_contents($filename);
echo "<hr>{$filename} => ".$detector->detect($str)."<hr>";
echo iconv($detector->detect($str),'UTF-8',$str)."<hr>";
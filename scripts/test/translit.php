<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$str="34 - Деталі замовлення - Зразки";
echo $str.';<br>';
echo iconv('cp1251', "ascii//IGNORE", $str)."<br>";
//echo iconv('cp1251', "ascii//TRANSLIT", $str)."<br>"; // error
//echo iconv('cp1251', "UTF-8//TRANSLIT", $str); // error
//echo iconv('UTF-8','cp1252//TRANSLIT',iconv('cp1251', "UTF-8", $str)); // error
echo transliterate($str);
die();
?>

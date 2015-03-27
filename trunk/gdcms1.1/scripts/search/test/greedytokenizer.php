<?php

include ("../tokenizer/tokenizer2.php");
include ("../tokenizer/tokenizer2_ukr.php");
include ("../tokenizer/tokenizer2_rus.php");
include ("../tokenizer/tokenizer2_eng.php");
include ("../tokenizer/greedytokenizer.php");

//$str = file_get_contents("ukr.txt");
$str = file_get_contents("rus1.txt");
//$str = file_get_contents("ukr1.txt");

$start=  microtime(true);
$t=new greedytokenizer([
    new tokenizer2_ukr(),
    new tokenizer2_rus(),
    new tokenizer2_eng()
]);
$res=$t->getTokens($str);
echo ( microtime(true) -$start)."s\n";
print_r($res);

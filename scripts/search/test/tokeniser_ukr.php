<?php

include ("../tokenizer/tokenizer2.php");
include ("../tokenizer/tokenizer2_ukr.php");

$str = file_get_contents("ukr.txt");

$t = new tokenizer2_ukr();

$res=$t->getFirstToken($str);
print_r($res);
echo "\n\n\n ". ( strlen($str) - strlen($res['token'])  - strlen($res['remainder']) ." \n\n\n" );


$res=$t->getFirstToken($res['remainder']);
print_r($res);

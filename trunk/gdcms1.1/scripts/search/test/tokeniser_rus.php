<?php

include ("../tokenizer/tokenizer2.php");
include ("../tokenizer/tokenizer2_rus.php");

$str = file_get_contents("rus.txt");

$t = new tokenizer2_rus();

$res=$t->getFirstToken($str);
print_r($res);

echo "\n\n\n ". ( strlen($str) - strlen($res['token'])  - strlen($res['remainder']) ." \n\n\n" );

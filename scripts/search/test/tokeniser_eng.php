<?php

include ("../tokenizer/tokenizer2.php");
include ("../tokenizer/tokenizer2_eng.php");

$str = file_get_contents("eng.txt");

$t = new tokenizer2_eng();

$res=$t->getFirstToken($str);
print_r($res);

echo "\n\n\n ". ( strlen($str) - strlen($res['token'])  - strlen($res['remainder']) ." \n\n\n" );

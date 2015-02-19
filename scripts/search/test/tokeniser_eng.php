<?php

include ("../tokenizer/tokenizer.php");
include ("../tokenizer/tokenizer_eng.php");

$str = file_get_contents("eng.txt");

$t = new tokenizer_eng();

print_r($t->getTokens($str));

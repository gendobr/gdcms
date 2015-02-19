<?php

include ("../tokenizer/tokenizer.php");
include ("../tokenizer/tokenizer_rus.php");

$str = file_get_contents("rus.txt");

$t = new tokenizer_rus();

print_r($t->getTokens($str));

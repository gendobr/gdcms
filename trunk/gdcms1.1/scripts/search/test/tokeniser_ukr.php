<?php

include ("../tokenizer/tokenizer.php");
include ("../tokenizer/tokenizer_ukr.php");

$str = file_get_contents("ukr.txt");

$t = new tokenizer_ukr();

print_r($t->getTokens($str));

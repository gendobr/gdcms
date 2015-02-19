<?php
include ("../getlanguage.php");
include('../../lib/simple_html_dom.php');
include('../../lib/url_to_absolute.php');
include('../htmlparser.php');


//$html=file_get_contents('eng.html');
$html=file_get_contents('ukr.html');
//$html=file_get_contents('rus.html');

$parser=new GDSearchParser(
        function(){
            $symbol = file('../data/common_words.txt');
            $cnt = count($symbol);
            for ($i = 0; $i < $cnt; $i++) {
                $symbol[$i] = str_replace(Array("\n", "\r"), '', $symbol[$i]);
            }
            return $symbol;    
        }
);
$pageData=$parser->extractData($parser->dom_from_str($html));
//print_r($pageData);

echo $pageData['title']."\n=============\n";
//echo $pageData['description']."\n=============\n";
//echo $pageData['keywords']."\n=============\n";
//echo $pageData['text']."\n=============\n";

$langSelector=new getTextLang(Array(
    'files'=>Array('eng'=>'../data/stats_eng.txt','rus'=>'../data/stats_rus.txt','ukr'=>'../data/stats_ukr.txt')
));    

print_r($langSelector->getTextLang($pageData['text']));
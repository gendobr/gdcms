<?php

$langs = Array(
    //'rus-utf8', 
    //'deu-utf8', 
    //'fra-cp1252',
    //'fra-utf8',
    //'rus-cp866',
    //'rus-koi8',
    //'deu-iso-8859-1',
    //'eng-utf8',
    //'eng-iso-8859-1',
    //'eng-cp1252',
    //'fra-iso-8859-1',
    //'rus-cp1251', 
    //'rus-iso-8859-5', 
    //'deu-cp1252',   
);
foreach ($langs as $lang) {

    $filename = "/home/dobro/wwwroot/cms/_/learning-set/{$lang}.txt";

    $file = file_get_contents($filename);

    $st = $file;
    $st = str_replace([chr(hexdec("c2")) . chr(hexdec("a0")), '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', "\\n", "_", "+", '@', '–', "*", "\n", "\r", "\t", "|", "=", '%', '&', '^', '#', ',', '.', ':', '-', '!', '?', '"', "'", ';', '(', ')', '{', '}', '[', ']', "\\", '/', '”', '“', '«', '»', '_'], ' ', $st);
    $st = ' ' . trim(preg_replace("/\\s+/", ' ', $st)) . ' ';


    $chunks = [];
    $chunksize = 30000;
    $cnt = strlen($st);
    for ($i = 0; $i < $cnt; $i+=$chunksize) {
        $chunks[] = substr($st, $i, $chunksize);
    }
    $cnt = count($chunks);
    for ($i = 1; $i < $cnt; $i++) {
        $append = substr($chunks[$i], 0, 1);
        $prepend = substr($chunks[$i - 1], -1, 1);
        $chunks[$i - 1].=$append;
        $chunks[$i] = $prepend . $chunks[$i];
    }
    unset($st);

    //print_r($chunks);exit;

    $cntK = count($chunks);
    for ($K = 0; $K < $cntK; $K++) {
        echo "\n\n$K / $cntK\n";
        $pst = $chunks[$K];
        $cnt = strlen($pst);
        $pre = substr($pst, 0, 1);
        $v = 0;
        for ($i = 1; $i < $cnt; $i++) {
            $cur = substr($pst, $i, 1);
            if ($v == 1000) {
                echo "$i-";
                $v = 0;
            }
            $v++;

            if (!isset($stats[$key = ord($pre) . '.' . ord($cur)])) {
                $stats[$key] = 0;
            }
            $stats[$key] ++;
            $n++;
            $pre = $cur;
        }
    }

    $keys = array_keys($stats);
    $norm = 1.0 / $n;
    foreach ($keys as $key) {
        $stats[$key]*=$norm;
    }
    asort($stats);


    $summa = 0;
    $keys = array_keys($stats);
    foreach ($keys as $key) {
        $summa+=$stats[$key];
        if ($summa < 0.01) {
            unset($stats[$key]);
        }
    }
//print_r($stats);
//echo "$n\n\n";
//exit;
//echo '<pre>'; print_r($stats);echo '</pre>';
    echo "\n\n$lang - $n bigramms\n";
    file_put_contents(dirname($filename) . "/{$lang}.stats", serialize($stats));
// $st=explode('<style',$file);
}



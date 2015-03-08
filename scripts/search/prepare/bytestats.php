<?php

$filename = '/home/dobro/wwwroot/cms/_hidden/search-learning-set/rus-utf8.txt';
$file = file_get_contents($filename);

$st=$file;
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
    $chunks[$i]=$prepend . $chunks[$i];
}
unset($st);

$cntK = count($chunks);
for ($K = 0; $K < $cntK; $K++) {
    echo "\n\n$K / $cntK\n";
    $pst= $chunks[$K];
    $cnt=strlen($pst);
    $pre = substr($pst, 0, 1);
    $v = 0;
    for ($i = 1; $i < $cnt; $i++) {
        $cur = substr($pst, $i, 1);
        if ($v == 1000) {
            echo "$i-";
            $v = 0;
        }
        $v++;
        
        if (!isset($stats[$key = $pre . $cur])) {
            $stats[$key]=0;
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

// echo '<pre>'; print_r($stats);echo '</pre>';
echo "\n\n$n bigramms\n";
file_put_contents($filename . '.stats', serialize($stats));
// $st=explode('<style',$file);
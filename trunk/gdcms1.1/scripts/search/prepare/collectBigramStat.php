<?php

$filename='/home/dobro/wwwroot/cms/_hidden/search-learning-set/ukr.csv';
// OK $filename='/home/dobro/wwwroot/cms/_hidden/search-learning-set/eng.csv';

// OK $filename = '/home/dobro/wwwroot/cms/_hidden/search-learning-set/rus.csv'; 

$file = file_get_contents($filename);

// remove comments
$st = explode('<!--', $file);
$cnt = count($st);
for ($i = 1; $i < $cnt; $i+=1) {
    $tmp = explode('-->', $st[$i]);
    $st[$i] = $tmp[1];
}
$st = join(' ', $st);


// remove styles
$st = explode('<style', $file);
$cnt = count($st);
for ($i = 1; $i < $cnt; $i+=1) {
    $tmp = explode('</style>', $st[$i]);
    $st[$i] = $tmp[1];
}
$st = join(' ', $st);


// remove scripts
$st = explode('<script', $file);
$cnt = count($st);
for ($i = 1; $i < $cnt; $i+=1) {
    $tmp = explode('</script>', $st[$i]);
    $st[$i] = $tmp[1];
}
$st = join(' ', $st);

$st = preg_replace("/<[^>]+>/", ' ', $st);
$st = html_entity_decode($st);
$st = str_replace([chr(hexdec("c2")) . chr(hexdec("a0")), '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', "\\n", "_", "+", '@', '–', "*", "\n", "\r", "\t", "|", "=", '%', '&', '^', '#', ',', '.', ':', '-', '!', '?', '"', "'", ';', '(', ')', '{', '}', '[', ']', "\\", '/', '”', '“', '«', '»', '_'], ' ', $st);
$st = mb_strtolower(' ' . trim(preg_replace("/\\s+/", ' ', $st)) . ' ', 'utf-8');


$ukr_aphabet = explode(',', ' ,а,б,в,г,ґ,д,е,є,ж,з,и,і,ї,й,к,л,м,н,о,п,р,с,т,у,ф,х,ц,ч,ш,щ,ь,ю,я');
$rus_aphabet = explode(',', ' ,а,б,в,г,д,е,ё,ж,з,и,й,к,л,м,н,о,п,р,с,т,у,ф,х,ц,ч,ш,щ,ъ,ы,ь,э,ю,я');
$eng_aphabet = explode(',', ' ,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z');

$stats = array();

$alp = &$ukr_aphabet;
for ($i = 0, $cnt = count($alp); $i < $cnt; $i++) {
    for ($j = $i; $j < $cnt; $j++) {
        $stats["{$alp[$i]}{$alp[$j]}"] = 0;
    }
}
$alp = &$rus_aphabet;
for ($i = 0, $cnt = count($alp); $i < $cnt; $i++) {
    for ($j = $i; $j < $cnt; $j++) {
        $stats["{$alp[$i]}{$alp[$j]}"] = 0;
    }
}
$alp = &$eng_aphabet;
for ($i = 0, $cnt = count($alp); $i < $cnt; $i++) {
    for ($j = $i; $j < $cnt; $j++) {
        $stats["{$alp[$i]}{$alp[$j]}"] = 0;
    }
}

$letters = array_flip(array_merge($ukr_aphabet, $rus_aphabet, $eng_aphabet));


// collect stats
// split a very long string into chunks


$chunks = [];
$chunksize = 30000;
$cnt = mb_strlen($st,'UTF-8');
for ($i = 0; $i < $cnt; $i+=$chunksize) {
    $chunks[] = mb_substr($st, $i, $chunksize, 'utf-8');
}
$cnt = count($chunks);
for ($i = 1; $i < $cnt; $i++) {
    $append = mb_substr($chunks[$i], 0, 1, 'utf-8');
    $prepend = mb_substr($chunks[$i - 1], -1, 1, 'utf-8');
    $chunks[$i - 1].=$append;
    $chunks[$i]=$prepend . $chunks[$i];
}
//$cnt = count($chunks);
//for ($i = 0; $i < $cnt; $i++) {
//    echo "{$chunks[$i]}\n";
//}
//exit();
file_put_contents($filename . '.txt', $st);
unset($st);

$n = 0; // total nunber of bigrams

$cntK = count($chunks);
for ($K = 0; $K < $cntK; $K++) {
    echo "\n\n$K / $cntK\n";
    $pst= $chunks[$K];
    $cnt=mb_strlen($pst,'UTF-8');
    // echo 'strlen='.$cnt."\n";
    // echo $pst.";\n";
    $pre = mb_substr($pst, 0, 1, 'utf-8');
    $v = 0;
    for ($i = 1; $i < $cnt; $i++) {
        $cur = mb_substr($pst, $i, 1, 'utf-8');
        if ($v == 1000) {
            echo "$i-";
            $v = 0;
        }
        $v++;
        if (isset($letters[$pre]) && isset($letters[$cur])) {
            if (isset($stats[$key = $pre . $cur])) {
                $stats[$key] ++;
                $n++;
            } elseif (isset($stats[$key = $cur . $pre])) {
                $stats[$key] ++;
                $n++;
            }
        }
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
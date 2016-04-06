<?php

global $main_template_name;
$main_template_name = '';


$log=file(\e::config('SCRIPT_ROOT').'/test/dbload.txt');

$cnt=count($log);

$prev=explode("\t",$log[0]);
$prev[0]=strtotime($prev[0]);
$prev[1]=(int)$prev[1];

for($i=1; $i<$cnt;$i++){
    $tmp=explode("\t",$log[$i]);
    $tmp[0]=strtotime($tmp[0]);
    $tmp[1]=(int)$tmp[1];
    
    if($tmp[1]>0){
        if($tmp[1]>$prev[1]){
            echo $tmp[0]."\t".( ($tmp[1]-$prev[1])/($tmp[0]-$prev[0]) );
        }
        //else{
        //    echo $tmp[0]."\t".($tmp[1]);
        //}
        $prev=$tmp;
    }else{
        echo $tmp[0]."\t-1";
    }
    
}
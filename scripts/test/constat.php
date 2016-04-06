<?php

global $main_template_name;
$main_template_name = '';
$stst = \e::db_getonerow("SHOW STATUS LIKE 'Connect%'");
if($stst){
    echo date('Y-m-d H:i:s')."\t".$stst['Value']."\n";
}else{
    echo date('Y-m-d H:i:s')."\tDBError\n";
}


<?php

if(!is_admin()) return 0;


// get list of existing updates
$dirname=\e::config('SCRIPT_ROOT').'/update/sqls';
$list = \core\fileutils::ls($dirname);
//$list = array_filter(\core\fileutils::ls(), function($r){return true;});
$list=$list['files'];
sort($list);
// \e::info($list);
$files=[];
$lastFile='';
foreach($list as $f){
    $files[$f]=$f;
    $lastFile=$f;
}

// get applied updates
$appliedUpdates=\e::db_getrows("SELECT * FROM <<tp>>updates ORDER BY update_file");
if($appliedUpdates){
    for($cnt=count($appliedUpdates), $i=0; $i<$cnt; $i++){
        unset($files[$appliedUpdates[$i]['update_file']]);
    }
}

// apply new updates
$html="";
foreach($files as $f){
    $sqls=include("$dirname/$f");
    foreach($sqls as $sql){
        $html.="<pre>$sql</pre>";
        \e::db_execute($sql);
    }
    \e::db_execute("INSERT INTO <<tp>>updates(update_file) VALUES (<<string update_file>>)",['update_file'=>$f]);
}

$html.="<div>Last update is $lastFile</div>";
//prn($form);
$input_vars['page_title']   = 
$input_vars['page_header']  = text('DB_updates');
$input_vars['page_content'] = $html;
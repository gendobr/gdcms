<?php

/*
   удалить:
   POST http://sites.znu.edu.ua/cms/index.php
 * api_key='....'
 * action=calendar/api/delete
 * id                bigint(20) 
 


  Возврат как JSON - удалённая запись
  status: error|success
  message: "....."
  calendar:
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'номер запису',
  `site_id` int(11) NOT NULL COMMENT 'номер сайта користувача',
  `nazva` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'назва події',
  `pochrik` int(11) NOT NULL COMMENT 'рік початку події',
  `pochmis` int(11) NOT NULL COMMENT 'місяць  початку події',
  `pochtyzh` int(11) NOT NULL COMMENT 'день тижня  початку події',
  `pochday` int(11) NOT NULL COMMENT 'день  початку події',
  `pochgod` int(11) NOT NULL COMMENT 'година  початку події',
  `pochhv` int(11) NOT NULL COMMENT 'хвилина  початку події',
  `kinrik` int(11) NOT NULL COMMENT 'рік кінця події',
  `kinmis` int(11) NOT NULL COMMENT 'місяць кінця події',
  `kintyzh` int(11) NOT NULL COMMENT 'день тижня  кінця події',
  `kinday` int(11) NOT NULL COMMENT 'день  кінця події',
  `kingod` int(11) NOT NULL COMMENT 'година кінця події',
  `kinhv` int(11) NOT NULL COMMENT 'хвилина  кінця події',
  `adresa` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'адреса сторінки з описом події',
  `kartynka` varchar(1024) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'адреса іконки події',
  `vis` int(11) NOT NULL COMMENT 'видима подія чи ні',
  `description` text CHARACTER SET utf8 COLLATE utf8_bin COMMENT 'Текстова інформація про подію',
  `tags` text
   calendar_url
   categories=[ 
       '{$category_id}'=>[
          'category_id'=>'...',
          'category_code'=>'...',
          'category_title' => '...',
          ...
        ]
   ]
 */




$debug = false;
global $main_template_name;
$main_template_name = '';

run('site/menu');
run('calendar/functions');

$calendar_id=(int)$input_vars['id'];
$calendar_info = db_getonerow("SELECT * FROM {$table_prefix}calendar WHERE id={$calendar_id}");
if (!$calendar_info) {
    $feedback = Array(
        'status' => 'error',
        'message' => 'Invalid calendar record'
    );
    echo json_encode($feedback);
    return '';
}

//------------------- get site info - begin ------------------------------------
$site_id = checkInt($calendar_info['site_id']);
$this_site_info = get_site_info($site_id);
if ($debug) {
    prn($this_site_info);
}
if (checkInt($this_site_info['id']) <= 0) {
    $feedback = Array(
        'status' => 'error',
        'message' => 'Site not found'
    );
    echo json_encode($feedback);
    return '';
}
//------------------- get site info - end --------------------------------------
$api_key = $input_vars['api_key'];
if ($this_site_info['salt'] != $input_vars['api_key']) {
    $feedback = Array(
        'status' => 'error',
        'message' => 'Invalid API key'
    );
    echo json_encode($feedback);
    return '';
}


$calendar_info = db_execute("DELETE FROM {$table_prefix}calendar WHERE id=$calendar_id");


// ----------------- re-create categories - begin ------------------------------
if(isset($input_vars['categories'])){
    $categories = preg_split("/,|;|\\./", $input_vars['categories']);
    $cnt = count($categories);
    for ($i = 0; $i < $cnt; $i++) {
        $categories[$i] = trim($categories[$i]);
        if(strlen($categories[$i])==0){
            unset($categories[$i]);
            continue;
        }
        $categories[$i] = preg_replace("/ +/", " ", $categories[$i]);
        $categories[$i] = DbStr($categories[$i]);    
    }
    $categories=array_values($categories);
    if(count($categories)>0){
        $query="DELETE FROM {$GLOBALS['table_prefix']}calendar_category WHERE event_id={$calendar_id}";
        db_execute($query);

    }    
}
// ----------------- re-create categories - end --------------------------------

// clear cache
   $query="DELETE FROM {$table_prefix}calendar_cache WHERE uid between {$site_id}000000 AND {$site_id}999990";
   db_execute($query);

$feedback = Array(
    'status' => 'success'
);
echo json_encode($feedback);
exit();
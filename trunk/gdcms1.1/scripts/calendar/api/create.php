<?php

/*
   содать:
   POST http://sites.znu.edu.ua/cms/index.php
 * api_key='....'
 * action=calendar/api/create
 * site_id           bigint(20) 
 * nazva             varchar(1024)           
   description       text
 * vis               tinyint(2) = 1|0  опубликовано(1) или скрыто(0)
   tags              text
 * pochrik        рік початку події  або -1 для будь-якого року
 * pochmis        місяць  початку події або -1 для будь-якого місяця
 * pochtyzh       день тижня  початку події або -1 для будь-якого для тижня
        0 => sunday
        1 => monday
        2 => tuesday
        3 => wednesday
        4 => thursday
        5 => friday
        6 => saturday
 * pochday        день місяця початку події  або -1 для будь-якого дня місяця
 * pochgod        година  початку події або -1 для будь-якої години
 * pochhv         хвилина  початку події або -1 для будь-якої хвилини
 * kinrik         рік кінця події  або -1 для будь-якого року
 * kinmis         місяць кінця події або -1 для будь-якого місяця
 * kintyzh        день тижня  кінця події або -1 для будь-якого дня тижня
 * kinday         день місяця кінця події     або -1 для будь-якого дня місяця                        
 * kingod         година кінця події або -1 для будь-якої години
 * kinhv          хвилина  кінця події або -1 для будь-якої хвилини

  Возврат как JSON - созданная запись
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

   url
 */




$debug = false;
global $main_template_name;
$main_template_name = '';

run('site/menu');
//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
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

run('calendar/functions');



////
//$lang = $input_vars['lang'];
//$lang_list = list_of_languages();
//$found = false;
//foreach ($lang_list as $ln) {
//    if ($lang == $ln['lang']) {
//        $found = true;
//    }
//}
//if (!$found) {
//    $lang = default_language;
//}

// 
$site_id = $this_site_info['id'];




// * nazva             varchar(1024)           
$nazva = DbStr($input_vars['nazva']);


//   description       text
$description = DbStr($input_vars['description']);

// * vis               tinyint(2) = 1|0  опубликовано(1) или скрыто(0)
$vis = $input_vars['vis'] ? 1 : 0;
 
//   tags              text
// ----------------- clear tags - begin ------------------------------------
if(isset($input_vars['tags'])){
    $tags = preg_split("/,|;|\\./", $input_vars['tags']);
    $cnt = count($tags);
    for ($i = 0; $i < $cnt; $i++) {
        $tags[$i] = trim($tags[$i]);
        if(strlen($tags[$i])==0){
            unset($tags[$i]);
            continue;
        }
        $tags[$i] = preg_replace("/ +/", " ", $tags[$i]);
    }
    $tags = join(',', $tags);    
}else{
    $tags = '';
}
// ----------------- clear tags - end --------------------------------------


$adresa='';
$kartynka='';


$query = "
INSERT INTO {$GLOBALS['table_prefix']}calendar 
	( `site_id`, `nazva`, `adresa`, `kartynka`, `vis`, `description`, `tags`)
	VALUES ( {$site_id}, '{$nazva}', '{$adresa}',  '{$kartynka}', {$vis},  '{$description}', '{$tags}')";
// prn($query);
db_execute($query);
$calendar_info = db_getonerow("SELECT * FROM {$table_prefix}calendar WHERE id=last_insert_id()");



foreach($input_vars['dates'] as $dt){
        // --------------- add new date - begin --------------------------------
        $new_pochrik = $dt['pochrik'];
        $new_pochmis = $dt['pochmis'];
        $new_pochday = $dt['pochday'];
        $new_pochtyzh = $dt['pochtyzh'];
        $new_pochgod = $dt['pochgod'];
        $new_pochhv = $dt['pochhv'];

        $new_kinrik = $dt['kinrik'];
        $new_kinmis = $dt['kinmis'];
        $new_kinday = $dt['kinday'];
        $new_kintyzh = $dt['kintyzh'];
        $new_kingod = $dt['kingod'];
        $new_kinhv = $dt['kinhv'];
        if (   strlen($new_pochrik)>0 && strlen($new_pochmis)>0
            && strlen($new_pochday)>0 && strlen($new_pochtyzh)>0
            && strlen($new_pochgod)>0 && strlen($new_pochhv)>0
            && strlen($new_kinrik)>0 && strlen($new_kinmis)>0
            && strlen($new_kinday)>0 && strlen($new_kintyzh)>0
            && strlen($new_kingod)>0 && strlen($new_kinhv)>0) {
            $new_pochrik = (int) $dt['pochrik'];
            $new_pochmis = (int) $dt['pochmis'];
            $new_pochday = (int) $dt['pochday'];
            $new_pochtyzh = (int) $dt['pochtyzh'];
            $new_pochgod = (int) $dt['pochgod'];
            $new_pochhv = (int) $dt['pochhv'];

            $new_kinrik = (int) $dt['kinrik'];
            $new_kinmis = (int) $dt['kinmis'];
            $new_kinday = (int) $dt['kinday'];
            $new_kintyzh = (int) $dt['kintyzh'];
            $new_kingod = (int) $dt['kingod'];
            $new_kinhv = (int) $dt['kinhv'];
            
            $query="
                INSERT INTO {$table_prefix}calendar_date 
                        ( site_id,   calendar_id          ,      pochrik,      pochmis,      pochtyzh,      pochday,      pochgod,      pochhv,      kinrik,      kinmis,      kintyzh,      kinday,      kingod,     kinhv  )
                VALUES  ( $site_id, {$calendar_info['id']}, $new_pochrik, $new_pochmis, $new_pochtyzh, $new_pochday, $new_pochgod, $new_pochhv, $new_kinrik, $new_kinmis, $new_kintyzh, $new_kinday, $new_kingod, $new_kinhv );
                ";
            //prn($query); exit();
            db_execute($query);
        }else{
            $feedback = Array(
                'status' => 'error',
                'message'=>'Fill-in all date fields'
            );
            return;
        }
        // --------------- add new date - end ----------------------------------
}
$tmp = db_getrows("SELECT * FROM {$table_prefix}calendar_date WHERE calendar_id={$calendar_info['id']}");
$calendar_info['dates'] = Array();
foreach ($tmp as $tm) {
    $calendar_info['dates'][$tm['id']] = $tm;
}



// ----------------- re-create categories - begin ------------------------------
if(isset($input_vars['categories'])){
    $calendar_id=$calendar_info['id'];

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

        $query="INSERT INTO {$GLOBALS['table_prefix']}calendar_category(category_id,event_id) SELECT category_id, {$calendar_id} FROM {$GLOBALS['table_prefix']}category WHERE category_code IN('".join("','", $categories)."')";
        db_execute($query);
    }    
}
// ----------------- re-create categories - end --------------------------------


$calendar_info['url'] = site_public_URL."/index.php?action=calendar/month&site_id={$site_id}&month={month}&year={year}&day={day}";


$feedback = Array(
    'status' => 'success',
    'calendar' => $calendar_info
);
echo json_encode($feedback);
// exit();
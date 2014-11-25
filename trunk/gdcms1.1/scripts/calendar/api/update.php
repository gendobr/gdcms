<?php

/*
   изменить:
   POST http://sites.znu.edu.ua/cms/index.php
 * api_key='....'
 * action=calendar/api/update
 * id                bigint(20) 
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
   categories     коди категорій


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

        $query="INSERT INTO {$GLOBALS['table_prefix']}calendar_category(category_id,event_id) SELECT category_id, {$calendar_id} FROM {$GLOBALS['table_prefix']}category WHERE category_code IN('".join("','", $categories)."')";
        db_execute($query);
    }    
}
// ----------------- re-create categories - end --------------------------------



// pochrik        рік початку події  або -1 для будь-якого року
$pochrik = (int) $input_vars['pochrik'];

// * pochmis        місяць  початку події або -1 для будь-якого місяця
$pochmis = (int) $input_vars['pochmis'];

// * pochtyzh       день тижня  початку події або -1 для будь-якого для тижня
$pochtyzh = (int) $input_vars['pochtyzh'];

// * pochday        день місяця початку події  або -1 для будь-якого дня місяця
$pochday = (int) $input_vars['pochday'];

// * pochgod        година  початку події або -1 для будь-якої години
$pochgod = (int) $input_vars['pochgod'];

// * pochhv         хвилина  початку події або -1 для будь-якої хвилини
$pochhv = (int) $input_vars['pochhv'];



// kinrik        рік кінця події  або -1 для будь-якого року
$kinrik = (int) $input_vars['kinrik'];

// * kinmis        місяць  кінця події або -1 для будь-якого місяця
$kinmis = (int) $input_vars['kinmis'];

// * kintyzh       день тижня  кінця події або -1 для будь-якого для тижня
$kintyzh = (int) $input_vars['kintyzh'];

// * kinday        день місяця кінця події  або -1 для будь-якого дня місяця
$kinday = (int) $input_vars['kinday'];

// * kingod        година  кінця події або -1 для будь-якої години
$kingod = (int) $input_vars['kingod'];

// * kinhv         хвилина  кінця події або -1 для будь-якої хвилини
$kinhv = (int) $input_vars['kinhv'];


$adresa='';
$kartynka='';






$query = "
UPDATE {$GLOBALS['table_prefix']}calendar 
SET
    `nazva`='{$nazva}',
    `pochrik`={$pochrik},
    `pochmis`={$pochmis},
    `pochtyzh`={$pochtyzh},
    `pochday`={$pochday},
    `pochgod`={$pochgod},
    `pochhv`={$pochhv},
    `kinrik`={$kinrik},
    `kinmis`={$kinmis},
    `kintyzh`={$kintyzh},
    `kinday`={$kinday},
    `kingod`={$kingod},
    `kinhv`={$kinhv},
    `adresa`='{$adresa}',
    `kartynka`='{$kartynka}',
    `vis`={$vis},
    `description`='{$description}',
    `tags`='{$tags}'
WHERE  `id`=$calendar_id
";
db_execute($query);

$calendar_info = db_getonerow("SELECT * FROM {$table_prefix}calendar WHERE id=$calendar_id");

//$calendar_info['calendar_url'] = str_replace(
//                Array('{news_id}','{lang}','{news_code}'),
//                Array($news_info['id'],$lang,$news_info['news_code']),
//                url_template_news_details);

// clear cache 
$query="DELETE FROM {$table_prefix}calendar_cache WHERE uid BETWEEN {$calendar_id}000000 AND {$calendar_id}999999";
db_execute($query);

$feedback = Array(
    'status' => 'success',
    'calendar' => $calendar_info
);
echo json_encode($feedback);
exit();
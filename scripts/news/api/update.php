<?php

/*

Изменить
POST http://sites.znu.edu.ua/cms/index.php
* api_key
* action=new/api/update
* id                bigint(20)
* lang              char(3)
site_id           bigint(20) 
title             varchar(512)           
content           longtext           
cense_level       tinyint(2)
last_change_date  datetime
abstract          text
category_id       bigint(20)
tags              text
expiration_date   datetime
weight            int(11)
creation_date     datetime
news_code         varchar(1024)           
news_meta_info    text
news_extra_1      varchar(1024)           
news_extra_2      varchar(1024)           



Возврат как JSON - обновленная запись
status: error|success
message: "....."
news:
  id                bigint(20) 
  lang              char(3)
  site_id           bigint(20) 
  title             varchar(512)           
  content           longtext           
  cense_level       tinyint(2)
  last_change_date  datetime
  abstract          text
  category_id       bigint(20)
  tags              text
  expiration_date   datetime
  weight            int(11)
  creation_date     datetime
  news_code         varchar(1024)           
  news_meta_info    text
  news_extra_1      varchar(1024)           
  news_extra_2      varchar(1024)    

+ news_url

 */




$debug = false;
global $main_template_name;
$main_template_name = '';

run('site/menu');

// get news info
//
$lang = $input_vars['lang'];
$lang_list = list_of_languages();
$found = false;
foreach ($lang_list as $ln) {
    if ($lang == $ln['lang']) {
        $found = true;
    }
}
if (!$found) {
    $lang = default_language;
}

$news_id=(int)$input_vars['id'];
$news_info = db_getonerow("SELECT * FROM {$table_prefix}news WHERE id={$news_id} AND lang='{$lang}'");

if (!$news_info) {
    $feedback = Array(
        'status' => 'error',
        'message' => 'News not found'
    );
    echo json_encode($feedback);
    return '';
}


//------------------- get site info - begin ------------------------------------
$site_id = checkInt($news_info['site_id']);
$this_site_info = get_site_info($site_id);
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


// 
$site_id = $this_site_info['id'];

//
$title = DbStr($input_vars['title']);

//
$content = DbStr($input_vars['content']);

//
$cense_level = $input_vars['cense_level'] ? 1 : 0;

//
$last_change_date = $input_vars['last_change_date'];
if (checkDatetime($last_change_date)) {
    $last_change_date = date("Y-m-d H:i:s", strtotime($last_change_date));
} else {
    $last_change_date = date("Y-m-d H:i:s");
}

//
$abstract = DbStr($input_vars['abstract']);

//
$category_id = 0;

//
// ----------------- clear tags - begin ------------------------------------
$tags = preg_split("/,|;|\\./", $input_vars['tags']);
$cnt = count($tags);
for ($i = 0; $i < $cnt; $i++) {
    $tags[$i] = trim($tags[$i]);
    $tags[$i] = preg_replace("/ +/", " ", $tags[$i]);
}
$tags = join(',', $tags);
// ----------------- clear tags - end --------------------------------------
//  expiration_date   datetime
$expiration_date = $input_vars['expiration_date'];
if (checkDatetime($expiration_date)) {
    $expiration_date = date("Y-m-d H:i:s", strtotime($expiration_date));
} else {
    $expiration_date = '';
}

$weight = (int) $input_vars['news_code'];
$creation_date = date('Y-m-d H:i:s');
$news_code = DbStr($input_vars['news_code']);
$news_meta_info = DbStr($input_vars['news_meta_info']);
$news_extra_1 = DbStr($input_vars['news_extra_1']);
$news_extra_2 = DbStr($input_vars['news_extra_2']);







$query = "
UPDATE {$GLOBALS['table_prefix']}news 
SET 
    title='{$title}', 
    content='{$content}', 
    cense_level='{$cense_level}', 
    last_change_date='{$last_change_date}', 
    abstract='{$abstract}', 
    category_id='{$category_id}', 
    tags='{$tags}', 
    expiration_date=" . ($expiration_date ? "'{$expiration_date}'" : 'null') . ", 
    weight='{$weight}', 
    creation_date='{$creation_date}', 
    news_code='{$news_code}', 
    news_meta_info='{$news_meta_info}', 
    news_extra_1='{$news_extra_1}', 
    news_extra_2='{$news_extra_2}'
WHERE id={$news_id} AND lang='{$lang}'
";
//prn($query);
db_execute($query);



# ------------------ rebuild tags - begin -------------------------------
db_execute("DELETE FROM {$table_prefix}news_tags WHERE news_id={$news_id} AND lang='{$lang}'");
if (strlen(trim($tags)) > 0) {
    // $query=explode(',',$this_news_info['tags']);
    $query = preg_split("/,|;|\\./", $tags);
    $cnt = count($query);
    if ($cnt > 0) {
        for ($i = 0; $i < $cnt; $i++) {
            $query[$i] = trim($query[$i]);
            if (strlen($query[$i]) > 0) {
                $query[$i] = "({$news_id},'{$lang}','" . DbStr($query[$i]) . "')";
            }
        }
        $query = "INSERT INTO {$table_prefix}news_tags(news_id,lang,tag) VALUES" . join(',', $query);
        db_execute($query);
    }
}
# ------------------ rebuild tags - end ---------------------------------
//echo "SELECT * FROM {$table_prefix}news WHERE id={$news_id} AND lang='{$lang}'";exit('115');
$news_info = db_getonerow("SELECT * FROM {$table_prefix}news WHERE id={$news_id} AND lang='{$lang}'");

    $feedback = Array(
        'status' => 'success',
        'news' => $news_info
    );
echo json_encode($feedback);
exit();
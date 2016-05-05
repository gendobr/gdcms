<?php


$main_template_name = '';

run('news/menu');
run('site/menu');


$news_id = checkInt((isset($input_vars['news_id']) ? $input_vars['news_id'] : 0));
$lang = get_language('lang');

$this_news_info = news_info($news_id, $lang);


if (checkInt($this_news_info['id']) <= 0) {
    
    //prn($input_vars);
    if (!isset($input_vars['site_id'])) {
        $input_vars['site_id'] = 0;
    }
    //prn("Location: index.php?action=news/list&site_id=".( (int)$input_vars['site_id'] ) );
    header("Location: index.php?action=news/list&site_id=" . ( (int) $input_vars['site_id'] ));
    die();
    return 0;
}
# ------------------- get permission - begin -----------------------------------
$user_cense_level = get_level($this_news_info['site_id']);
if ($user_cense_level <= 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
#if($debug) prn('$user_cense_level='.$user_cense_level);
# ------------------- get permission - end -------------------------------------


# ------------------- site info - begin ----------------------------------------
$site_id = checkInt($this_news_info['site_id']);
$this_site_info = get_site_info($site_id);
# ------------------- site info - end ------------------------------------------


foreach($this_news_info['news_icon'] as $pt){
    $pt=trim($pt);
    if(strlen($pt)>0){
        $path=realpath("{$this_site_info['site_root_dir']}/{$pt}");
        if($path && strncmp( $path , $this_site_info['site_root_dir'] , strlen($this_site_info['site_root_dir']) )==0){
            unlink($path);
        }
    }
}

$sql="UPDATE <<tp>>news SET news_icon=NULL WHERE id={$this_news_info['id']} AND lang='{$this_news_info['lang']}'";
\e::db_execute($sql);


header("Location: index.php?action=news/edit&site_id={$this_news_info['site_id']}&news_id={$this_news_info['id']}&lang={$this_news_info['lang']}");

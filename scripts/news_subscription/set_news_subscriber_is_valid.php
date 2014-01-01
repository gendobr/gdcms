<?php

/*
 * Set image category
 */
$GLOBALS['main_template_name'] = '';

//prn($input_vars);

$news_subscriber_id = (int) str_replace('news_subscriber_is_valid_', '', $input_vars['id']);
$news_subscriber_is_valid = (int)$input_vars['value'];
// echo "news_subscriber_id=$news_subscriber_id; news_subscriber_is_valid=$news_subscriber_is_valid";
// exit();
// get subscriber info
$subscriber_info = db_getonerow("SELECT * FROM {$table_prefix}news_subscriber  WHERE news_subscriber_id=$news_subscriber_id");
if (!$subscriber_info) {
    //echo htmlspecialchars(iconv('cp1251','UTF-8',$news_subscriber_is_valid));
    echo htmlspecialchars(iconv(site_charset,'UTF-8',$news_subscriber_is_valid));
    exit();
}
// prn($subscriber_info); exit();


$site_id = (int) $subscriber_info['site_id'];
run('site/menu');
$this_site_info = get_site_info($site_id);
if($this_site_info['id']!=$site_id){
    //echo htmlspecialchars(iconv('cp1251','UTF-8',$news_subscriber_is_valid));
    echo htmlspecialchars(iconv(site_charset,'UTF-8',$news_subscriber_is_valid));
    exit();
}

# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Access_denied');
    return 0;
}
# ------------------- check permission - end -----------------------------------

$value  = DbStr($news_subscriber_is_valid);
db_execute("UPDATE {$table_prefix}news_subscriber SET news_subscriber_is_valid='$value' WHERE news_subscriber_id=$news_subscriber_id");
//echo htmlspecialchars(iconv('cp1251','UTF-8',$news_subscriber_is_valid));
echo htmlspecialchars(iconv(site_charset,'UTF-8',$news_subscriber_is_valid));
?>
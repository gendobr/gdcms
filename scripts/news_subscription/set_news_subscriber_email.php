<?php

/*
 * Set image category
 */
$GLOBALS['main_template_name'] = '';

//prn($input_vars);

$news_subscriber_id = (int) str_replace('news_subscriber_email_', '', $input_vars['id']);
//$news_subscriber_email = trim(iconv('UTF-8','cp1251',$input_vars['value']));
$news_subscriber_email = trim(iconv('UTF-8',site_charset,$input_vars['value']));
//echo "news_subscriber_id=$news_subscriber_id; news_subscriber_email=$news_subscriber_email";

// get subscriber info
$subscriber_info = \e::db_getonerow("SELECT * FROM <<tp>>news_subscriber  WHERE news_subscriber_id=$news_subscriber_id");
if (!$subscriber_info) {
    //echo htmlspecialchars(iconv('cp1251','UTF-8',$news_subscriber_email));
    echo htmlspecialchars(iconv(site_charset,'UTF-8',$news_subscriber_email));
    exit();
}
// prn($subscriber_info); exit();


$site_id = (int) $subscriber_info['site_id'];
run('site/menu');
$this_site_info = get_site_info($site_id);
if($this_site_info['id']!=$site_id){
    //echo htmlspecialchars(iconv('cp1251','UTF-8',$news_subscriber_email));
    echo htmlspecialchars(iconv(site_charset,'UTF-8',$news_subscriber_email));
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

$value  = \e::db_escape($news_subscriber_email);
\e::db_execute("UPDATE <<tp>>news_subscriber SET news_subscriber_email='$value' WHERE news_subscriber_id=$news_subscriber_id");
//echo htmlspecialchars(iconv('cp1251','UTF-8',$news_subscriber_email));
echo htmlspecialchars(iconv(site_charset,'UTF-8',$news_subscriber_email));

?>
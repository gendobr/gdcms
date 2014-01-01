<?php

/*
 * Set image category
 */
$GLOBALS['main_template_name'] = '';

//prn($input_vars);

$news_subscriber_id = (int) $input_vars['id'];

// get subscriber info
$subscriber_info = db_getonerow("SELECT * FROM {$table_prefix}news_subscriber  WHERE news_subscriber_id=$news_subscriber_id");
if (!$subscriber_info) {
    echo "news_subscriber $news_subscriber_id not found";
    return 0;
}
// prn($subscriber_info); exit();


$site_id = (int) $subscriber_info['site_id'];
run('site/menu');
$this_site_info = get_site_info($site_id);
if($this_site_info['id']!=$site_id){
    echo 'site not found';
    return 0;
}

# ------------------- check permission - begin ---------------------------------
echo "site_id=$site_id";
if (get_level($site_id) == 0) {
    echo 'permission error';
    return 0;
}
# ------------------- check permission - end -----------------------------------

db_execute("DELETE FROM {$table_prefix}news_subscriber WHERE news_subscriber_id=$news_subscriber_id and site_id=$site_id");
echo "news_subscriber $news_subscriber_id deleted";
?>
<?php

global $main_template_name;
$main_template_name='';
run('site/menu');
// ------------------ get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    header("HTTP/1.0 404 Not Found");
    die(text('Site_not_found'));
}
// ------------------ get site info - end --------------------------------------
run('rss_aggregator/functions');
$rsssourceitem_id=isset($input_vars['rsssourceitem_id'])?( (int)$input_vars['rsssourceitem_id'] ):0;
$rsssourceitem_info=  get_rsssourceitem_info($rsssourceitem_id);
if (!$rsssourceitem_info || $rsssourceitem_info['site_id']!=$site_id) {
    header("HTTP/1.0 404 Not Found");
    die(text('rsssourceitem_not_found'));
}

// read visibility status from input data
$rsssourceitem_is_visiblle=isset($input_vars['rsssourceitem_is_visiblle'])?( (int)$input_vars['rsssourceitem_is_visiblle'] ):0;
$rsssourceitem_is_visiblle=($rsssourceitem_is_visiblle==1)?1:0;

// update visibility status
$query = "UPDATE {$GLOBALS['table_prefix']}rsssourceitem SET rsssourceitem_is_visiblle=$rsssourceitem_is_visiblle WHERE site_id=$site_id AND rsssourceitem_id=" . ( (int)$rsssourceitem_id);
db_execute($query);
echo 'OK';
?>
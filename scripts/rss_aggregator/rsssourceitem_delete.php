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
// prn($this_site_info);
// ------------------ get site info - end --------------------------------------
run('rss_aggregator/functions');
$rsssourceitem_id=isset($input_vars['rsssourceitem_id'])?( (int)$input_vars['rsssourceitem_id'] ):0;
$rsssourceitem_info=  get_rsssourceitem_info($rsssourceitem_id);
// prn($rsssourceitem_id, $rsssourceitem_info);exit();
if (!$rsssourceitem_info || $rsssourceitem_info['site_id']!=$site_id) {
    header("HTTP/1.0 404 Not Found");
    die(text('rsssourceitem_not_found'));
}

// update visibility status
$query = "DELETE FROM <<tp>>rsssourceitem WHERE site_id=$site_id AND rsssourceitem_id=" . ( (int)$rsssourceitem_id);
\e::db_execute($query);
echo 'OK';
?>
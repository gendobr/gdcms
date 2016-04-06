<?php
/**
 * delete RSS source
 */
global $main_template_name;
$main_template_name='';

run('site/menu');
run('rss_aggregator/functions');


//------------------- $this_rsssource_info - begin -----------------------------
$rsssource_id = isset($input_vars['rsssource_id'])?checkInt($input_vars['rsssource_id']):0;
$this_rsssource_info = get_rsssource_info($rsssource_id);
// prn('$this_rsssource_info=',$this_rsssource_info);
if(!$this_rsssource_info){
    echo "ERROR: RSS Source not found";
    exit();
}
//------------------- $this_rsssource_info - end -------------------------------

//------------------- get site info - begin ------------------------------------
$site_id = checkInt($this_rsssource_info['site_id']);
$this_site_info = get_site_info($site_id);

// prn('$this_site_info=',$this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
//------------------- get site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------


// ------------------ deleting - begin -----------------------------------------
$query="delete from {$table_prefix}rsssourceitem WHERE site_id={$site_id} AND rsssource_id={$rsssource_id}";
\e::db_execute($query);
$query="delete from {$table_prefix}rsssource WHERE site_id={$site_id} AND rsssource_id={$rsssource_id}";
\e::db_execute($query);
// ------------------ deleting - end -------------------------------------------

echo "OK";
exit();
?>
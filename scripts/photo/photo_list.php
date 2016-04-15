<?php

$debug = false;
run('site/menu');

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------

run("lib/class_report");
run("lib/class_report_extended");
$re = new report_generator;
// $re->db = $db;
$re->distinct = false;

$re->from = "<<tp>>photo AS photo";

$re->add_where(" photo.site_id={$site_id} ");


//
$re->add_field($field = 'photo.photo_id'
        , $alias = 'photo_id'
        , $type = 'id:hidden=no'
        , $label = '#'
        , $_group_operation = false);

$LL = join('&', array_map(
    function($it){
     
    },
    \e::db_getrows("SELECT photo_category_id,photo_category_title,photo_category_path FROM <<tp>>photo_category WHERE site_id={$site_id} ORDER BY photo_category_path ASC")
    ));
$re->add_field($field = 'news.lang'
        , $alias = 'lang'
        , $type = 'enum:' . $LL
        , $label = $text['Language']
        , $_group_operation = false);
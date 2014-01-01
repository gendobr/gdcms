<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

global $main_template_name;
$main_template_name = '';
run('site/menu');
// ------------------ get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    header("HTTP/1.0 404 Not Found");
    die(text('Site_not_found'));
}
// ------------------ get site info - end --------------------------------------
// ------------------ get language - begin -------------------------------------
if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
// ------------------ get language - end ---------------------------------------
// ------------------ get list of RSS items
run('rss_aggregator/get_public_list');

$_start = isset($input_vars['start']) ? ( (int) $input_vars['start'] ) : 0;
$_rows_per_page = isset($input_vars['rows_per_page']) ? ( (int) $input_vars['rows_per_page'] ) : rows_per_page;

$result = rss_aggregator_get_list($site_id, $input_vars['lang'], $_start, $_rows_per_page, $filter = Array());
//prn($result);

//------------------------ draw using SMARTY template - begin ----------------
$template = false;
if (isset($_REQUEST['template'])) {
    $template = site_get_template($this_site_info, $_REQUEST['template']);
}
if (!$template) {
    $template = site_get_template($this_site_info, 'template_rss_aggregator_items');
}
if (!function_exists('db_get_template')) {
    run('site/page/page_view_functions');
}


// previous
for($i=0, $cnt=count($result['paging']); $i<$cnt;$i++){

    if($result['paging'][$i]['innerHTML']=='Previous'){
        $result['paging'][$i]['innerHTML']=$txt['Previous'];
    }
    if($result['paging'][$i]['innerHTML']=='Next'){
        $result['paging'][$i]['innerHTML']=$txt['Next'];
    }
}


#prn('$news_template',$news_template);
$vyvid = process_template($template
        , Array('text' => $txt
    , 'items' => $result['rows']
    , 'items_found' => $result['rows_found']
    , 'paging' => $result['paging']
    , 'start' => $_start + 1
    , 'finish' => $_start + count($result['rows'])
        )
);


$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

$lang_list = list_of_languages('lang');
// prn($lang_list);

$this_site_info['title'] = get_langstring($this_site_info['title'], $input_vars['lang']);
$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array('title' => $txt['News']
        , 'content' => $vyvid
        , 'abstract' => ( isset($txt['news_manual']) ? $txt['news_manual'] : '')
        , 'site_id' => $site_id
        , 'lang' => $input_vars['lang']
    )
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;
?>
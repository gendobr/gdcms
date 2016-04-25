<?php

/*
 * ���������� ������ ������ ��������� ��� ��������� �����
 */


global $main_template_name;
$main_template_name = '';


//------------------- site info - begin ----------------------------------------
run('site/menu');
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
// get language
if (isset($input_vars['interface_lang'])) {
    if ($input_vars['interface_lang']) {
        $input_vars['lang'] = $input_vars['interface_lang'];
    }
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = \e::config('default_language');
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = \e::config('default_language');
}
$txt = load_msg($input_vars['lang']);
// $lang = $input_vars['lang'];
$lang = get_language('lang');


//// ------------------ get list of categories - begin ---------------------------
//$query = "
//select ch.*, bit_and(pa.is_visible) as visible
//from {$table_prefix}category pa, {$table_prefix}category ch
//where pa.start<=ch.start and ch.finish<=pa.finish
//  and pa.site_id={$this_site_info['id']} and ch.site_id={$this_site_info['id']}
//group by ch.category_id
//having visible>0
//order by  ch.start";
//$caterory_list = \e::db_getrows($query);
//// ------------------ get list of categories - end -----------------------------
//
//// ------------------ adjust list of categories - begin ------------------------
//$category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$lang}&category_id=";
//$cnt = count($caterory_list);
//for ($i = 0; $i < $cnt; $i++) {
//    $caterory_list[$i]['category_title'] = get_langstring($caterory_list[$i]['category_title'], $lang);
//    $caterory_list[$i]['category_description'] = get_langstring($caterory_list[$i]['category_description'], $lang);
//    $caterory_list[$i]['URL'] = $category_url_prefix . $caterory_list[$i]['category_id'];
//}
//// prn($caterory_list);
//// ------------------ adjust list of categories - end --------------------------
run('category/functions');
$caterory_list=category_public_list($site_id, $lang);

run('site/page/page_view_functions');


// ------------------ load site menu - begin -----------------------------------
$menu_groups = get_menu_items($site_id, 0, $lang);
// ------------------ load site menu - end -------------------------------------
// ------------------ get list of languages - begin ----------------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
    $lang_list[$i]['url'] = $lang_list[$i]['href'];
}
// ------------------ get list of languages - end ------------------------------
// ------------------ draw tree - begin ----------------------------------------
$_template = site_get_template($this_site_info, 'template_category_tree');
$vyvid = process_template($_template, Array(
            'caterory_list' => $caterory_list
            , 'text' => $txt
            , 'site' => $this_site_info
            , 'lang' => $lang
                )
);
// ------------------ draw tree - end ------------------------------------------


// ------------------ draw page - begin ----------------------------------------
$this_site_info['title'] = get_langstring($this_site_info['title'], $input_vars['lang']);
$file_content = process_template($this_site_info['template'], Array(
            'page' => Array('title' => $txt['Site_map']
                , 'content' => $vyvid
                , 'abstract' => ''
                , 'site_id' => $site_id
                , 'lang' => $input_vars['lang']
            )
            , 'lang' => $lang_list
            , 'site' => $this_site_info
            , 'menu' => $menu_groups
            , 'site_root_url' => site_root_URL
            , 'text' => $txt
        ));
echo $file_content;
// ------------------ draw page - end ------------------------------------------
?>
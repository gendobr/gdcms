<?php
header('Content-Type:text/html; charset='.site_charset);
run('site/menu');
$GLOBALS['main_template_name'] = '';
//------------------- site info - begin ----------------------------------------
$site_id = (int) $input_vars['site_id'];
$this_site_info = get_site_info($site_id);

#//prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
$site_root_dir = preg_replace("/\\/\$/", '', $this_site_info['site_root_dir']);
$site_root_url = preg_replace("/\\/\$/", '', $this_site_info['site_root_url']);
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
// get list of pages
$pagelist = \e::db_getrows("select pa.category_id, pa.site_id, pa.category_code, pa.category_title,
                          pa.start, pa.finish, pa.is_deleted, pa.deep, pa.is_part_of,
                          pa.see_also, pa.is_visible, pa.path
                        from <<tp>>category pa
                        WHERE site_id=$site_id ORDER BY pa.start");
// prn($pagelist);
$json = Array('files' => Array(), 'dirs' => Array(), 'parents' => Array());
$cnt = count($pagelist);
$this_category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$_SESSION['lang']}&category_id=";
for ($i = 0; $i < $cnt; $i++) {
    $page_url=$this_category_url_prefix . $pagelist[$i]['category_id'];
    $json['files'][] = Array(
        'name' => get_langstring($pagelist[$i]['category_title']),
        'url' => $page_url,
        'prefix'=>str_repeat('&nbsp+&nbsp', $pagelist[$i]['deep'])
    );
}
echo json_data($json);
?>
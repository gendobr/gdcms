<?php
header('Content-Type:text/html; charset='.site_charset);
run('site/menu');
$GLOBALS['main_template_name'] = '';
//------------------- site info - begin ----------------------------------------
$site_id = (int) $input_vars['site_id'];
$this_site_info = get_site_info($site_id);
#$this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
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




// prn($pagelist);
$json = Array('files' => Array(), 'dirs' => Array(), 'parents' => Array());



$list = Array();
// browse categories

$json['files'][] = Array(
    'name' => text('Site_home_page'),
    'url' => $site_root_url
);

if ($this_site_info['is_poll_enabled']) {
    $json['files'][] = Array(
        'name' => $text['Polls_manage'],
        'url' => site_URL . "?action=poll/ask&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}"
    );
}
if ($this_site_info['is_forum_enabled']) {
    $json['files'][] = Array(
        'name' => $text['View_forums'],
        'url' => site_URL . "?action=forum/forum&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}"
    );
}

if ($this_site_info['is_gb_enabled']) {
    $json['files'][] = Array(
        'name' => $text['View_Guestbook'],
        'url' => site_URL . "?action=gb/guestbook&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}"
    );
}
if ($this_site_info['is_gallery_enabled']) {
    $json['files'][] = Array(
        'name' => $text['image_gallery_view'],
        'url' => site_URL . "?action=gallery/photogallery&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}"
    );
}

if ($this_site_info['is_news_line_enabled']) {
    $json['files'][] = Array(
        'name' => $text['View_news'],
        'url' => site_URL . "?action=news/view&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}"
    );
}

if ($this_site_info['is_ec_enabled']) {
    $json['files'][] = Array(
        'name' => $text['EC_item_search'],
        'url' => site_URL . "?action=ec/item/search&site_id={$this_site_info['id']}"
    );
    $json['files'][] = Array(
        'name' => $text['EC_item_search_advanced'],
        'url' => site_URL . "?action=ec/item/search_advanced&site_id={$this_site_info['id']}"
    );
    $json['files'][] = Array(
        'name' => $text['EC_item_list_by_tag'],
        'url' => site_URL . "?action=ec/item/list_by_tag&site_id={$this_site_info['id']}"
    );
    $json['files'][] = Array(
        'name' => $text['EC_categories'],
        'url' => site_URL . "?action=ec/category/list&site_id={$this_site_info['id']}"
    );
    $json['files'][] = Array(
        'name' => $text['EC_item_browse'],
        'url' => site_URL . "?action=ec/item/browse&site_id={$this_site_info['id']}"
    );
}


$json['files'][] = Array(
    'name' => $text['Browse_categories'],
    'url' => site_root_URL . "/index.php?action=category/browse&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}"
);




// get list of pages
$pagelist = db_getrows("SELECT id,lang,title,path FROM {$GLOBALS['table_prefix']}page WHERE site_id=$site_id ORDER BY id,lang");
$cnt = count($pagelist);
for ($i = 0; $i < $cnt; $i++) {
    $page_url=preg_replace("/\\/\$/", '', $site_root_url.'/'.$pagelist[$i]['path'])."/{$pagelist[$i]['id']}.{$pagelist[$i]['lang']}.html";
    $json['files'][] = Array(
        'name' => $pagelist[$i]['title'],
        'url' => $page_url
    );
}
echo json_data($json);
?>
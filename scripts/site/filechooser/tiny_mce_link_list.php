<?php

run('site/menu');
$GLOBALS['main_template_name'] = '';
//------------------- site info - begin ----------------------------------------
$site_id = (int) $input_vars['site_id'];
$this_site_info = get_site_info($site_id);
#$this_site_info = \e::db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
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

$list = Array();
// browse categories
$page_url = site_URL . "?action=poll/ask&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}";
$list[] = "[\"" . str_replace("\"", "\\\"", $text['Polls_manage']) . "\",\"$page_url\"]";

if ($this_site_info['is_poll_enabled']) {
    $page_url = site_URL . "?action=poll/ask&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", $text['Polls_manage']) . "\",\"$page_url\"]";
}
if ($this_site_info['is_forum_enabled']) {
    $page_url = site_URL . "?action=forum/forum&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", $text['View_forums']) . "\",\"$page_url\"]";
}
if ($this_site_info['is_gb_enabled']) {
    $page_url = site_URL . "?action=gb/guestbook&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", $text['View_Guestbook']) . "\",\"$page_url\"]";
}
if ($this_site_info['is_gallery_enabled']) {
    $page_url = site_URL . "?action=gallery/photogallery&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", $text['image_gallery_view']) . "\",\"$page_url\"]";
}
if ($this_site_info['is_news_line_enabled']) {
    $page_url = site_URL . "?action=news/view&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", $text['View_news']) . "\",\"$page_url\"]";
}

if ($this_site_info['is_ec_enabled']) {
    $page_url = site_URL . "?action=ec/item/search&site_id={$this_site_info['id']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", text('EC_item_search')) . "\",\"$page_url\"]";

    $page_url = site_URL . "?action=ec/item/search_advanced&site_id={$this_site_info['id']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", text('EC_item_search_advanced')) . "\",\"$page_url\"]";

    $page_url = site_URL . "?action=ec/item/list_by_tag&site_id={$this_site_info['id']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", text('EC_item_list_by_tag')) . "\",\"$page_url\"]";

    $page_url = site_URL . "?action=ec/category/list&site_id={$this_site_info['id']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", text('EC_categories')) . "\",\"$page_url\"]";

    $page_url = site_URL . "?action=ec/item/browse&site_id={$this_site_info['id']}";
    $list[] = "[\"" . str_replace("\"", "\\\"", text('EC_item_browse')) . "\",\"$page_url\"]";
}

$page_url = site_root_URL . "/index.php?action=category/browse&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}";
$list[] = "[\"" . str_replace("\"", "\\\"", text('Browse_categories')) . "\",\"$page_url\"]";


// get list of pages
$pagelist = \e::db_getrows("SELECT id,lang,title,path FROM {$GLOBALS['table_prefix']}page WHERE site_id=$site_id ORDER BY id,lang,title");
// prn($pagelist);
$json = Array('files' => Array(), 'dirs' => Array(), 'parents' => Array());
$cnt = count($pagelist);
for ($i = 0; $i < $cnt; $i++) {
    $page_url = preg_replace("/\\/\$/", '', $site_root_url . '/' . $pagelist[$i]['path']) . "/{$pagelist[$i]['id']}.{$pagelist[$i]['lang']}.html";
    $list[] = "[\"" . str_replace("\"", "\\\"", "{$pagelist[$i]['id']}:{$pagelist[$i]['lang']}:{$pagelist[$i]['title']}") . "\",\"$page_url\"]";
}

echo "var tinyMCELinkList = new Array(\n";
$delim = '';
foreach ($list as $img) {
//	// Name, URL
    echo $delim;
    echo $img;
    if ($delim == '') {
        $delim = ",\n";
    }
}
echo ")";
?>
<?php

$link = $db;

$data = date("Y.m.d H:i");

if (isset($input_vars['interface_lang']))
    if (strlen($input_vars['interface_lang']) > 0)
        $input_vars['lang'] = $input_vars['interface_lang'];
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
run('site/menu');
//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id, $input_vars['lang']);

// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    die($txt['Forum_not_found']);
}
//------------------- site info - end ------------------------------------------
//--------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, "template_index.html", $verbose=false);
if (is_file($custom_page_template)) $this_site_info['template'] = $custom_page_template;
//--------------------------- get site template - end --------------------------





$query =
        "SELECT forum_list.*
       , 0 AS n_threads
       , count(DISTINCT if(forum_list.is_premoderated,if(forum_msg.is_visible,forum_msg.id,NULL),forum_msg.id)) AS n_messages
       , MAX(if(forum_list.is_premoderated,if(forum_msg.is_visible,forum_msg.data,NULL),forum_msg.data)) AS  last_message_data
  FROM
  (
   (`{$table_prefix}forum_list` forum_list
   )
   LEFT JOIN {$table_prefix}forum_msg forum_msg
   ON (forum_msg.forum_id=forum_list.id
       AND forum_msg.site_id=$site_id)
  )
  WHERE forum_list.site_id=$site_id
  GROUP BY forum_list.id";
//prn($query);
$list_of_forums = \e::db_getrows($query);




$forums = Array();
foreach ($list_of_forums as $_forum)
    $forums[] = $_forum['id'];
//prn($forums);
$n_visible_threads = Array();
if (count($forums) > 0) {
    $query = join(',', $forums);
    $query = "select forum_thread.forum_id, count(distinct forum_thread.id) as n_threads
            from {$table_prefix}forum_thread as forum_thread,
                 {$table_prefix}forum_msg as forum_msg,
                 {$table_prefix}forum_list as forum_list
            where  forum_thread.forum_id in($query)
               and forum_thread.id=forum_msg.thread_id
               and forum_list.id=forum_thread.forum_id
               and ( (forum_msg.is_visible and forum_list.is_premoderated) OR (NOT forum_list.is_premoderated))
            group by forum_thread.forum_id
            ;";
    //prn($query);
    $tmp = \e::db_getrows($query);
    foreach ($tmp as $_tm)
        $n_visible_threads[$_tm['forum_id']] = $_tm['n_threads'];
}

// ------------- adjust list - begin -------------------------------------------
$cnt = count($list_of_forums);
$prefix = site_root_URL . "/index.php?action=forum/thread&site_id={$site_id}&lang={$input_vars['lang']}&forum_id=";
for ($i = 0; $i < $cnt; $i++) {
    $list_of_forums[$i]['URL_open_forum'] = $prefix . $list_of_forums[$i]['id'];
    $list_of_forums[$i]['n_visible_threads'] = (isset($n_visible_threads[$list_of_forums[$i]['id']]) ? $n_visible_threads[$list_of_forums[$i]['id']] : 0);
}
// ------------- adjust list - end ---------------------------------------------
#prn($this_site_info);
run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $_SESSION['lang']);
//echo '<!-- ';
//prn("get_menu_items({$this_site_info['id']},0,{$_SESSION['lang']})",$menu_groups);
//echo ' -->';
//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = $lang_list[$i]['href'];

    $lang_list[$i]['url'] = str_replace('action=forum%2Fforum', '', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('index.php', 'forum.php', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace(site_root_URL, sites_root_URL, $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('?&', '?', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('&&', '&', $lang_list[$i]['url']);

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
// prn($lang_list);
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------
# search for template
$_template = site_get_template($this_site_info, 'template_forum_list');
$echo = process_template($_template
        , Array(
               'forums' => $list_of_forums,
               'site' => $this_site_info
        )
);
$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array(
        'title' => $this_site_info['title'] . ' - ' . $txt['forum_list']
        , 'content' => $echo
    )
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name;
$main_template_name = '';
?>
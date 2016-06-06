<?php

$link = $db;

$data = date("Y-m-d H:i:s");

$input_vars['lang'] = $lang = get_language('lang,interface_lang');
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
   (`<<tp>>forum_list` forum_list
   )
   LEFT JOIN <<tp>>forum_msg forum_msg
   ON (forum_msg.forum_id=forum_list.id
       AND forum_msg.site_id=$site_id)
  )
  WHERE forum_list.site_id=$site_id
  GROUP BY forum_list.id";
//prn($query);
$list_of_forums = \e::db_getrows($query);




$forums = Array();
foreach ($list_of_forums as $_forum) {
    $forums[] = $_forum['id'];
}
//prn($forums);
$n_visible_threads = Array();
if (count($forums) > 0) {
    $query = join(',', $forums);
    $query = "select forum_thread.forum_id, count(distinct forum_thread.id) as n_threads
            from <<tp>>forum_thread as forum_thread,
                 <<tp>>forum_msg as forum_msg,
                 <<tp>>forum_list as forum_list
            where  forum_thread.forum_id in($query)
               and forum_thread.id=forum_msg.thread_id
               and forum_list.id=forum_thread.forum_id
               and ( (forum_msg.is_visible and forum_list.is_premoderated) OR (NOT forum_list.is_premoderated))
            group by forum_thread.forum_id
            ;";
    //prn($query);
    $tmp = \e::db_getrows($query);
    foreach ($tmp as $_tm) {
        $n_visible_threads[$_tm['forum_id']] = $_tm['n_threads'];
    }
}

// ------------- adjust list - begin -------------------------------------------
$cnt = count($list_of_forums);
for ($i = 0; $i < $cnt; $i++) {
    $list_of_forums[$i]['URL_open_forum'] = \e::url_from_template(
            \e::config('url_template_thread_list'),
            [
                'site_id'=>$site_id,
                'lang'=>$lang,
                'forum_id'=>$list_of_forums[$i]['id'],
                'start'=>0
            ]);
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
    if(!isset($this_site_info['extra_setting']['lang'][$lang_list[$i]['lang']])){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url'] = \e::url_from_template(
            \e::config('url_template_forum_list'),
            [
                'site_id'=>$site_id,
                'lang'=>$lang_list[$i]['name'],
                'start'=>0
            ]);
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
usort ( $lang_list , function($k1, $k2){
    $defaultLang=\e::config('default_language');
    $s1 = ($k1['name'] == $defaultLang?'0':'1').$k1['name'];
    $s2 = ($k2['name'] == $defaultLang?'0':'1').$k2['name'];
    return -strcmp($s2, $s1);
} );
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

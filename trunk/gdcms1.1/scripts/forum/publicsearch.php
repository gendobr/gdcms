<?php

/*
  List of messages for a selected site, selected forum and selected thread
  Arguments are
  $site_id - site identifier, integer, mandatory
  $forum_id - forum identifier, integer, mandatory
  $thread_id - thread identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
run('forum/menu');
run('site/menu');

//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
if (!$this_site_info['is_forum_enabled']) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
//------------------- get site info - end --------------------------------------
//
//
//
//
//
//--------------------------- get list -- begin --------------------------------
$keywords = trim(isset($input_vars['keywords']) ? $input_vars['keywords'] : '');
if (strlen($keywords) > 0) {
    $start = isset($input_vars['start']) ? ( (int) $input_vars['start'] ) : 0;

    $query="SELECT SQL_CALC_FOUND_ROWS * FROM {$GLOBALS['table_prefix']}forum_msg AS forum_msg 
            WHERE site_id={$this_site_info['id']}
            AND ( 
                LOCATE('".DbStr($keywords)."', name)
              + LOCATE('".DbStr($keywords)."', email)
              + LOCATE('".DbStr($keywords)."', www)
              + LOCATE('".DbStr($keywords)."', subject)
              + LOCATE('".DbStr($keywords)."', msg)
            )
            order by data desc
            limit $start, ".rows_per_page;
    $rows=  db_getrows($query);
    
    $thread_ids=Array();
    $forum_ids=Array();
    foreach($rows as $row){
        $thread_ids[$row['thread_id']]=1;
        $forum_ids[$row['forum_id']]=1;
    }
    
    if(count($thread_ids)>0){
        $query="select * from {$GLOBALS['table_prefix']}forum_thread where site_id={$site_id} AND id in(".join(',', array_keys($thread_ids)).")";
        $tmp=  db_getrows($query);
        $threads=Array();
        foreach($tmp as $tm){
            $threads[$tm['id']]=$tm;
        }
    }
    if(count($forum_ids)>0){
        $query="select * from {$GLOBALS['table_prefix']}forum_list where site_id={$site_id} AND id in(".join(',', array_keys($forum_ids)).")";
        $tmp=  db_getrows($query);
        $forums=Array();
        foreach($tmp as $tm){
            $forums[$tm['id']]=$tm;
        }
    }
    
    $cnt=count($rows);
    for($i=0; $i<$cnt;$i++){
        $rows[$i]['thread']=$threads[$rows[$i]['thread_id']];
        $rows[$i]['forum']=$threads[$rows[$i]['forum_id']];
    }

    # --------------------- paging - begin -------------------------------------
    $num = db_getonerow("SELECT FOUND_ROWS() AS n_records;");
    $num = $num['n_records'];
    $pages = Array();
    if ($num > rows_per_page) {
        $pages = " {$txt['Pages']} :";
        for ($i = 0; $i < $num; $i = $i + rows_per_page) {
            if ($i == $start) {
                $url='';// sites_root_URL . "/thread.php?site_id={$site_id}&start={$i}&forum_id=$forum_id&lang={$input_vars['lang']}";
                $to = (1 + $i / rows_per_page);
            } else {
                $url=sites_root_URL . "/thread.php?site_id={$site_id}&start={$i}&forum_id=$forum_id&lang={$input_vars['lang']}";
                $to = (1 + $i / rows_per_page);
            }
            //$pages[]="<a href=\"" . sites_root_URL . "/thread.php?site_id={$site_id}&start={$i}&forum_id=$forum_id&lang={$input_vars['lang']}\">" . $to . "</a>\n";
            $pages[]=Array(
                'URL'=>$url,
                'innerHTML'=>$to
            );
        }
    }
    # --------------------- paging - end ---------------------------------------
} else {
    $result = Array(
        'rows' => Array(),
        'n_rows' => 0,
        'pages' => Array()
    );
}
//--------------------------- get list -- end ----------------------------------


run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $_SESSION['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = $lang_list[$i]['href'];

    // $lang_list[$i]['url']=str_replace('action=forum%2Fthread','',$lang_list[$i]['url']);
    // $lang_list[$i]['url']=str_replace('index.php','thread.php',$lang_list[$i]['url']);
    // $lang_list[$i]['url']=str_replace(site_root_URL,sites_root_URL,$lang_list[$i]['url']);
    // $lang_list[$i]['url']=str_replace('?&','?',$lang_list[$i]['url']);
    // $lang_list[$i]['url']=str_replace('&&','&',$lang_list[$i]['url']);

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
// prn($lang_list);
//------------------------ get list of languages - end -------------------------
# search for template
$_template = site_get_template($this_site_info, 'template_forum_search');



$echo = process_template($_template
        , Array(
    'forum' => $this_forum_info,
    'site' => $this_site_info,
    'result' => $result,
    'visitor' => $visitor,
    'form' => $form,
    'URL_view_forum_list' => site_root_URL . "/index.php?action=forum/forum&site_id=$site_id&lang={$input_vars['lang']}"
        )
);

$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array(
        'title' => $this_forum_info['name'] . ' - ' . $txt['forum_threads']
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

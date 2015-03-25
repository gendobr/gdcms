<?php

/*
 * List of messages for a selected site, selected forum and selected thread
 * Arguments are
 * $site_id - site identifier, integer, mandatory
 * $forum_id - forum identifier, integer, optional
 * $thread_id - thread identifier, integer, optional
 * $keywords - search string, optional  
 * (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
run('forum/menu');
run('forum/functions');
run('site/menu');


//------------------- get language - begin -------------------------------------
if (isset($input_vars['interface_lang'])) {
    if (strlen($input_vars['interface_lang']) > 0) {
        $input_vars['lang'] = $input_vars['interface_lang'];
    }
}
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
//------------------- get language - end ---------------------------------------
//
//
//
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
// ------------------- get forum info - begin ----------------------------------
$forum_id=isset($input_vars['forum_id'])?( (int)$input_vars['forum_id'] ):0;
if($forum_id > 0){
    $this_forum_info=get_forum_info($forum_id);
}else{
    $this_forum_info=false;
}
// ------------------- get forum info - end ------------------------------------
//
//
//--------------------------- get list -- begin --------------------------------
$keywords = trim(isset($input_vars['keywords']) ? $input_vars['keywords'] : '');
if (strlen($keywords) > 0) {
    $start = isset($input_vars['start']) ? ( (int) $input_vars['start'] ) : 0;

    $query="SELECT SQL_CALC_FOUND_ROWS * FROM {$GLOBALS['table_prefix']}forum_msg AS forum_msg 
            WHERE site_id={$this_site_info['id']}
                AND ( LOCATE('".DbStr($keywords)."', msg) OR LOCATE('".DbStr($keywords)."', name) )
              ".($forum_id>0?" AND forum_id=$forum_id":'')."
            order by data desc
            limit $start, ".rows_per_page;
    // AND MATCH (`name`,`email`,`www`,`subject`,`msg`) AGAINST ('".DbStr($keywords)."')
    // prn($query);
    $rows=  db_getrows($query);

    $num = db_getonerow("SELECT FOUND_ROWS() AS n_records;");
    $num = $num['n_records'];
    
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
            $tm['URL']=site_root_URL."/index.php?action=forum/msglist&thread_id={$tm['id']}&site_id={$site_id}&lang={$input_vars['lang']}&forum_id={$tm['forum_id']}";
            $threads[$tm['id']]=$tm;
        }
    }
    // prn($threads);
    if(count($forum_ids)>0){
        $query="select * from {$GLOBALS['table_prefix']}forum_list where site_id={$site_id} AND id in(".join(',', array_keys($forum_ids)).")";
        $tmp=  db_getrows($query);
        $forums=Array();
        foreach($tmp as $tm){
            $tm['URL']=site_root_URL."/index.php?action=forum/thread&site_id={$site_id}&lang={$input_vars['lang']}&forum_id=".$tm['id'];
            $forums[$tm['id']]=$tm;
        }
    }
    // prn($forums);
    
    $cnt=count($rows);
    for($i=0; $i<$cnt;$i++){
        $rows[$i]['thread']=$threads[$rows[$i]['thread_id']];
        $rows[$i]['forum']=$forums[$rows[$i]['forum_id']];
        $rows[$i]['html']=show_message($rows[$i]['msg']);
    }
    // prn($rows);
    # --------------------- paging - begin -------------------------------------

    $pages = Array();
    $url_prefix=site_root_URL.'/index.php?'.preg_query_string('/start/').'&start=';
    if ($num > rows_per_page) {
        //$pages = " {$txt['Pages']} :";
        for ($i = 0; $i < $num; $i = $i + rows_per_page) {
            if ($i == $start) {
                $url='';// sites_root_URL . "/thread.php?site_id={$site_id}&start={$i}&forum_id=$forum_id&lang={$input_vars['lang']}";
                $to = (1 + $i / rows_per_page);
            } else {
                $url=$url_prefix.$i;
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
    
    $result = Array(
        'rows' => $rows,
        'n_rows' => $num,
        'pages' => $pages
    );
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
//
//
//
//
//
//------------------- visitor info - begin -------------------------------------
if(get_level($site_id)>0) {
    $visitor=Array(
            'site_visitor_login'=>$_SESSION['user_info']['user_login'],
            'site_visitor_email'=>$_SESSION['user_info']['email'],
            'site_visitor_home_page_url'=>$this_site_info['url'],
            'URL_login'=>site_root_URL."/index.php?action=forum/login&lang={$input_vars['lang']}",
            'URL_signup'=>site_root_URL."/index.php?action=forum/signup&lang={$input_vars['lang']}",
            'URL_logout'=>site_root_URL."/index.php?action=forum/logout&lang={$input_vars['lang']}",
            'is_moderator'=>1
    );
}else {
    $visitor=$_SESSION['site_visitor_info'];
    $visitor['URL_login'] =site_root_URL."/index.php?action=forum/login&lang={$input_vars['lang']}";
    $visitor['URL_signup']=site_root_URL."/index.php?action=forum/signup&lang={$input_vars['lang']}";
    $visitor['URL_logout']=site_root_URL."/index.php?action=forum/logout&lang={$input_vars['lang']}";
    $visitor['is_moderator']=  in_array($visitor['site_visitor_login'], $this_forum_info['moderators']);
}
//prn($visitor);
//------------------- visitor info - end ---------------------------------------
//
//
//
//
//------------------- search form - begin --------------------------------------
$form=Array(
    'hidden_elements'=>"
        <input type=\"hidden\" name=\"action\" value=\"forum/publicsearch\">
        <input type=\"hidden\" name=\"site_id\" value=\"{$this_site_info['id']}\">
        <input type=\"hidden\" name=\"lang\" value=\"{$input_vars['lang']}\">
        ",
    'keywords'=>$keywords,
    'forum_id' => $forum_id,
    'forum_options'=> draw_options($forum_id,db_getrows("select id, name from {$GLOBALS['table_prefix']}forum_list where site_id={$this_site_info['id']}"))
);
// prn($form);
//------------------- search form - begin --------------------------------------
# search for template
$_template = site_get_template($this_site_info, 'template_forum_search');



$echo = process_template($_template
        , Array(
    'forum' => $this_forum_info,
    'site' => $this_site_info,
    'result' => $result,
    'visitor' => $visitor,
    'form' => $form,
    'URL_view_forum_list' => site_root_URL . "/index.php?action=forum/forum&site_id=$site_id&lang={$input_vars['lang']}" ,
            'text' => $txt
        )
);

$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array(
        'title' => $txt['forum_search']
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

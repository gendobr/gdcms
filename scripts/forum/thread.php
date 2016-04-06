<?php

$link = $db;
$data=date ("Y.m.d H:i");

if(isset($input_vars['interface_lang'])) if(strlen($input_vars['interface_lang'])>0) $input_vars['lang']=$input_vars['interface_lang'];
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
$echo='

';

run('site/menu');
run('forum/functions');
// prn($_SESSION);
// ------------------ site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id,$input_vars['lang']);

// prn($this_site_info);
if(checkInt($this_site_info['id'])<=0) {
    $input_vars['page_title']   = $txt['Forum_not_found'];
    $input_vars['page_header']  = $txt['Forum_not_found'];
    $input_vars['page_content'] = $txt['Forum_not_found'];
    global $main_template_name; $main_template_name='';
    return 0;
}
// ------------------ site info - end ------------------------------------------
// -------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, "template_index.html", $verbose=false);
if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
// -------------------------- get site template - end --------------------------

// ------------------ forum info - begin ---------------------------------------
$forum_id = checkInt($input_vars['forum_id']);
//$this_forum_info = \e::db_getonerow("SELECT * FROM {$table_prefix}forum_list WHERE id={$forum_id}");
$this_forum_info = get_forum_info($forum_id);

// prn($this_forum_info);
if(checkInt($this_forum_info['id'])<=0) {
    header("Location: ".site_root_URL."/index.php?action=forum/forum&site_id=$site_id");
    exit;
}
// ------------------ forum info - end -----------------------------------------

// site visitor session
if(!isset($_SESSION['site_visitor_info'])) $_SESSION['site_visitor_info']=$GLOBALS['default_site_visitor_info'];

//------------------- visitor info - begin -------------------------------------
if(get_level($site_id)>0) {
    //prn($_SESSION['user_info']);
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


// ----------------------- create new thread - begin ---------------------------
$errors='';
if(isset($input_vars['msg'])) {
    $errors=Array();

    $input_vars['msg']=trim(strip_tags($input_vars['msg']));
    if(strlen($input_vars['msg'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['Message']}\" {$txt['is_empty']}</font></b><br/>";

    $input_vars['subject']=trim(strip_tags($input_vars['subject']));
    if(strlen($input_vars['subject'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['forum_thread_subject']}\" {$txt['is_empty']}</font></b><br/>";

    if($_REQUEST['postedcode']!=$_SESSION['code'] OR strlen($_SESSION['code'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['Retype_the_number']}\" {$txt['is_empty']}</font></b><br/>";



    if(count($errors)==0) {
        run('notifier/functions');
        function ch($name) {return \e::db_escape(strip_tags(trim($name)));}
        $_SESSION['code']='';
        $name   = ch($_SESSION['site_visitor_info']['site_visitor_login']);
        $email  = ch($_SESSION['site_visitor_info']['site_visitor_email']);   if(!is_valid_email($email)) $email='';
        $www    = ch($_SESSION['site_visitor_info']['site_visitor_home_page_url']);     if(!is_valid_url($www))     $www='';
        $subject= ch($input_vars['subject']); if(strlen($subject)==0)     $subject='############';

        $is_visible=($this_forum_info['is_premoderated']==1)?0:1;

        $msg    = ch($input_vars['msg']);

        $query = "INSERT INTO {$table_prefix}forum_thread (subject, forum_id, site_id, data)
                  VALUES ('$subject', '$forum_id', '$site_id', '$data')";

        mysql_query($query, $link);
        $query="select LAST_INSERT_ID()";
        $result= mysql_query($query, $link);
        $num = mysql_fetch_array($result);
        $num=$num[0];

        $query = "INSERT INTO {$table_prefix}forum_msg (name, forum_id, site_id, thread_id, email, www, subject, msg, data, is_first_msg,is_visible)
                  VALUES ('$name', '$forum_id', '$site_id', '$num', '$email', '$www', '$subject', '$msg', '$data',1,$is_visible)";
        mysql_query($query, $link);


        if(!isset($_SESSION['msg'])) $_SESSION['msg']='';
        $_SESSION['msg'].='<div style="color:green;font-weight:bold;">'.text('New_thread_is_started').'</div>';
        if($is_visible==0) $_SESSION['msg'].='<div style="color:green;font-weight:bold;">'.text('Invisible_message_appears_after_moderator_review').'</div>';


        //---------------- notify site admin - begin ---------------------------
        //$site_admin=\e::db_getonerow("SELECT u.email FROM {$table_prefix}site_user AS su INNER JOIN {$table_prefix}user AS u ON u.id=su.user_id WHERE su.site_id={$this_site_info['id']} ORDER BY su.level ASC LIMIT 0,1");
        $site_admin_list=  \e::db_getrows("SELECT u.email FROM {$table_prefix}site_user AS su INNER JOIN {$table_prefix}user AS u ON u.id=su.user_id WHERE su.site_id={$this_site_info['id']}");
        foreach($site_admin_list as $site_admin){
            if(is_valid_email($site_admin['email'])) {

                $path=$this_site_info['title']."/".$this_forum_info['name'];
                notification_queue(
                    $site_admin['email'],
                    $path.' - '.$txt['New_thread_is_started'],
                    "{$txt['New_thread_is_started']}:\n\n".
                    $path."\n".
                    "===============================================================\n".
                    "{$txt['Name']} : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_login']) . "\n" .
                    "E-mail : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_email']) . "\n" .
                    "WWW : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_home_page_url']) . "\n" .
                    "{$txt['forum_thread_subject']}: ".strip_tags($input_vars['subject'])."\n\n".
                    strip_tags($input_vars['msg'])."\n".
                    "===============================================================\n".
                    site_root_URL."/index.php?action=forum%2Fsearch&site_id={$site_id}&filter_is_visible=0&submit=%C7%ED%E0%E9%F2%E8&orderby=data+desc \n\n",
                   'notify_action_email'  );
            }
        }
        //---------------- notify site admin - end -----------------------------
// --------------- notify forum moderators - begin ---------------------
        if($this_forum_info['moderators'] && is_array($this_forum_info['moderators']) && count($this_forum_info['moderators'])>0){
           $query=Array();
           foreach($this_forum_info['moderators'] as $moderator_login){
               $query[]=  \e::db_escape($moderator_login);
           }
           $query="SELECT * FROM {$table_prefix}site_visitor WHERE site_visitor_login in ('".join("','",$query)."')";
           $moderators=  \e::db_getrows($query);
           if($moderators){
                $path=$this_site_info['title']."/".$this_forum_info['name'].'/'.$input_vars['subject'];

                $subject = $path . ' - ' . $txt['New_thread_is_started'];

                $body = "\n\n{$txt['New_thread_is_started']}:\n\n" .
                         $path . "\n" .
                        "================================================================\n" .
                        "{$txt['Name']} : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_login']) . "\n" .
                        "E-mail : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_email']) . "\n" .
                        "WWW : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_home_page_url']) . "\n" .
                        "{$txt['Subject']}: " . strip_tags($input_vars['subject']) . "\n\n" .
                        strip_tags($input_vars['msg']) . "\n" .
                        "================================================================\n" .
                        site_root_URL . "/index.php?action=forum/thread&site_id={$site_id}&forum_id={$forum_id}&lang={$input_vars['lang']}"." \n\n";
                foreach($moderators as $moderator){
                   notification_queue($moderator['site_visitor_email'], $subject, $body, 'notify_action_email');
                }
           }
        }
        // --------------- notify forum moderators - end -----------------------
        header("Location: ".site_root_URL."/index.php?action=forum/thread&site_id=$site_id&forum_id=$forum_id&lang={$input_vars['lang']}");
        run("session_finish");         //finish session
        exit();
    }
    $errors=join(' ',$errors);
}
//------------------------ create new thread - end -----------------------------
// -------------------------- create confirmation code - begin -----------------
if(!isset($_SESSION['code'])) $_SESSION['code']='';
if(strlen($_SESSION['code'])==0) {
    srand((float)microtime() * 1000000);
    $chars = explode(',','1,2,3,4,5,6,7,8,9,0');
    shuffle($chars);
    $chars = join('',$chars);
    $chars = substr ($chars,0,3);
    $_SESSION['code']=$chars;
}

// -------------------------- create confirmation code - end -------------------


if(isset($_SESSION['msg'])) {
    $echo.=$_SESSION['msg'];
    unset($_SESSION['msg']);
}





$start=isset($input_vars['start'])?( (int)$input_vars['start'] ):0;





// if($this_forum_info['is_premoderated']==1) {
    if($visitor['is_moderator']){
        $some_messages_visible='';
        $n_messages="count(DISTINCT {$table_prefix}forum_msg.id)";
        $last_message_date="MAX({$table_prefix}forum_msg.data)";
    }else{
        $some_messages_visible="HAVING some_messages_visible>0";
        $n_messages="count(DISTINCT if({$table_prefix}forum_msg.is_visible,{$table_prefix}forum_msg.id,null))";
        $last_message_date="MAX( if({$table_prefix}forum_msg.is_visible, {$table_prefix}forum_msg.data,null) )";
    }
    $query=
   "SELECT SQL_CALC_FOUND_ROWS {$table_prefix}forum_thread.*
          ,{$n_messages} AS n_messages
          ,$last_message_date AS  last_message_data
          ,MAX({$table_prefix}forum_msg.is_visible) AS  some_messages_visible
    FROM
    (
        (`{$table_prefix}forum_thread` LEFT JOIN `{$table_prefix}forum_msg`
          ON (     {$table_prefix}forum_thread.id={$table_prefix}forum_msg.thread_id
               AND {$table_prefix}forum_thread.site_id=$site_id
               )
         )
     )
     WHERE     {$table_prefix}forum_thread.site_id=$site_id
           AND {$table_prefix}forum_thread.forum_id=$forum_id
     GROUP BY {$table_prefix}forum_thread.id
     {$some_messages_visible}
     ORDER BY last_message_data DESC
     LIMIT $start, 10";
//}
//else {
//    $query=
//   "SELECT SQL_CALC_FOUND_ROWS {$table_prefix}forum_thread.*
//          ,count(DISTINCT {$table_prefix}forum_msg.id) AS n_messages
//          ,MAX({$table_prefix}forum_msg.data) AS  last_message_data
//          ,1 AS  some_messages_visible
//    FROM
//    (
//        (`{$table_prefix}forum_thread` LEFT JOIN `{$table_prefix}forum_msg`
//          ON (     {$table_prefix}forum_thread.id={$table_prefix}forum_msg.thread_id
//               AND {$table_prefix}forum_thread.site_id=$site_id
//               )
//         )
//     )
//     WHERE     {$table_prefix}forum_thread.site_id=$site_id
//           AND {$table_prefix}forum_thread.forum_id=$forum_id
//     GROUP BY {$table_prefix}forum_thread.id ORDER BY last_message_data DESC
//     LIMIT $start, 10";
//}
//prn($query);
$result = \e::db_getrows($query);

$cnt=count($result);
for($i=0;$i<$cnt;$i++) {
    if($visitor['is_moderator'] || $result[$i]['some_messages_visible']) {
       $result[$i]['URL_view_thread']=site_root_URL."/index.php?action=forum/msglist&thread_id={$result[$i]['id']}&site_id=$site_id&forum_id=$forum_id&lang={$input_vars['lang']}";
    }
    else{
       $result[$i]['URL_view_thread']='';
    }

}


# --------------------- paging - begin ------------------------
$n_records = mysql_query($query="SELECT FOUND_ROWS() AS n_records;", $link)    or die("Querry failed");
$num = mysql_fetch_array($n_records);
$num=$num[0];
$pages='';
if($num>10) {
    $pages=" {$txt['Pages']} :";
    for($i=0;$i<$num; $i=$i+10) {
        if( $i==$start ) $to='<b>['.(1+$i/10).']</b>'; else $to=(1+$i/10);
        $pages.="<a href=\"".sites_root_URL."/thread.php?site_id={$site_id}&start={$i}&forum_id=$forum_id&lang={$input_vars['lang']}\">".$to."</a>\n";
    }
}
# --------------------- paging - end --------------------------







if(!isset($input_vars['msg']) )     $input_vars['msg']='';
if(!isset($input_vars['subject']) ) $input_vars['subject']='';








$form=Array('hiddent_fields'=>"<INPUT type='hidden' NAME='action' value='forum/thread'>
                               <INPUT type='hidden' NAME='site_id' value='$site_id'>
                               <INPUT type='hidden' NAME='forum_id' value='$forum_id'>
                               <INPUT type='hidden' NAME='lang' value='{$input_vars['lang']}'>",
            'action'=>site_root_URL.'/index.php',
            'errors'=>$errors,
            'fld_subject'=>Array('name'=>'subject','value'=>$input_vars['subject']),
            'fld_msg'=>Array('name'=>'msg','value'=>$input_vars['msg']),
            'fld_postedcode'=>Array('name'=>'postedcode','value'=>site_root_URL."/index.php?action=gb/bookcode")
     );



run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'],0,$_SESSION['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list=list_of_languages();
$cnt=count($lang_list);
for($i=0;$i<$cnt;$i++) {
    $lang_list[$i]['url']=$lang_list[$i]['href'];

    $lang_list[$i]['url']=str_replace('action=forum%2Fthread','',$lang_list[$i]['url']);
    $lang_list[$i]['url']=str_replace('index.php','thread.php',$lang_list[$i]['url']);
    $lang_list[$i]['url']=str_replace(site_root_URL,sites_root_URL,$lang_list[$i]['url']);
    $lang_list[$i]['url']=str_replace('?&','?',$lang_list[$i]['url']);
    $lang_list[$i]['url']=str_replace('&&','&',$lang_list[$i]['url']);

    $lang_list[$i]['lang']=$lang_list[$i]['name'];
}
// prn($lang_list);
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------

# search for template
$_template = site_get_template($this_site_info,'template_forum_thread');



$echo=process_template( $_template
    ,Array(
    'forum'=>$this_forum_info,
    'site'=>$this_site_info,
    'threads'=>$result,
    'pages'=>$pages,
    'visitor'=>$visitor,
    'form'=>$form,
    'URL_view_forum_list'=>site_root_URL."/index.php?action=forum/forum&site_id=$site_id&lang={$input_vars['lang']}"
    )
);

$file_content=process_template($this_site_info['template']
    ,Array(
    'page'=>Array(
    'title'=>$this_forum_info['name'].' - '.$txt['forum_threads']
    ,'content'=> $echo
    )
    ,'lang'=>$lang_list
    ,'site'=>$this_site_info
    ,'menu'=>$menu_groups
    ,'site_root_url'=>site_root_URL
    ,'text'=>$txt
));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name; $main_template_name='';
?>
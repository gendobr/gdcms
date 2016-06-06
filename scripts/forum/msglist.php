<?php

// $link = $db;
$data=date ("Y.m.d H:i");

$input_vars['lang'] = $lang = get_language('lang,interface_lang');
$txt = load_msg($input_vars['lang']);



run('forum/functions');
run('site/menu');
//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id,$input_vars['lang']);

// prn($this_site_info);
if(checkInt($this_site_info['id'])<=0) {
    die($txt['Site_not_found']);
}
//------------------- site info - end ------------------------------------------
//

//--------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, "template_index.html", $verbose=false);
if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
//--------------------------- get site template - end --------------------------

//------------------- forum info - begin ---------------------------------------
$forum_id = checkInt($input_vars['forum_id']);
$this_forum_info = get_forum_info($forum_id);

//prn($this_forum_info); exit();
if(checkInt($this_forum_info['id'])<=0) {
    header("Location: ".sites_root_URL."/forum.php?site_id=$site_id");
    exit;
}
//------------------- forum info - end -----------------------------------------
// site visitor session
if (!isset($_SESSION['site_visitor_info'])) {
    $_SESSION['site_visitor_info'] = $GLOBALS['default_site_visitor_info'];
}

//run('forum/functions');
//prn($input_vars);
//------------------- visitor info - begin -------------------------------------
if(get_level($site_id)>0) {
    //prn($_SESSION['user_info']);
    $visitor=Array(
            'site_visitor_login'=>$_SESSION['user_info']['user_login'],
            'site_visitor_email'=>$_SESSION['user_info']['email'],
            'site_visitor_home_page_url'=>$this_site_info['url'],
            'URL_login'=>\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/login&lang={$input_vars['lang']}",
            'URL_signup'=>\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/signup&lang={$input_vars['lang']}",
            'URL_logout'=>\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/logout&lang={$input_vars['lang']}",
            'is_moderator'=>1
    );
}else {
    $visitor=$_SESSION['site_visitor_info'];
    $visitor['URL_login'] =\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/login&lang={$input_vars['lang']}";
    $visitor['URL_signup']=\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/signup&lang={$input_vars['lang']}";
    $visitor['URL_logout']=\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/logout&lang={$input_vars['lang']}";
    $visitor['is_moderator']=  in_array($visitor['site_visitor_login'], $this_forum_info['moderators']);
}
//prn($visitor);
//------------------- visitor info - end ---------------------------------------

//

// ------------------ delete thread - begin ------------------------------------
if($visitor['is_moderator'] && isset($input_vars['delete_thread_id'])) {
    $delete_thread_id=(int)$input_vars['delete_thread_id'];
    $query="DELETE FROM <<tp>>forum_msg
            WHERE site_id=$site_id
              AND forum_id=$forum_id
              AND thread_id=$delete_thread_id";
    \e::db_execute($query);
    $query= "DELETE FROM <<tp>>forum_thread WHERE id={$delete_thread_id}";
    \e::db_execute($query);
}
// ------------------ delete thread - end --------------------------------------


//------------------- update message - begin -----------------------------------
if($visitor['is_moderator'] && isset($input_vars['msg_id'])) {
    $thread_id = checkInt($input_vars['thread_id']);
    $query="UPDATE <<tp>>forum_msg
             SET   msg='".\e::db_escape($input_vars['msg_text'])."'
             WHERE id=".((int)$input_vars['msg_id'])."
               AND site_id=$site_id
               AND forum_id=$forum_id
               AND thread_id=$thread_id
               AND (   `name`='".\e::db_escape($visitor['site_visitor_login'])."'
                    OR {$visitor['is_moderator']})";
    //prn($query);exit();
    \e::db_execute($query);
}
//------------------- update message - end -------------------------------------

// ------------------ delete message - begin -----------------------------------
if($visitor['is_moderator'] && isset($input_vars['delete_msg_id'])) {
    $thread_id = checkInt($input_vars['thread_id']);
    $query="DELETE FROM <<tp>>forum_msg
             WHERE id=".((int)$input_vars['delete_msg_id'])."
               AND site_id=$site_id
               AND forum_id=$forum_id
               AND thread_id=$thread_id
               AND (   `name`='".\e::db_escape($visitor['site_visitor_login'])."'
                    OR {$visitor['is_moderator']})";
    //prn($query);exit();
    \e::db_execute($query);
}
// ------------------ delete message - end -------------------------------------

//------------------- hide message - begin -------------------------------------
if($visitor['is_moderator'] && isset($input_vars['hide_msg_id'])) {
    $thread_id = checkInt($input_vars['thread_id']);
    $query="UPDATE <<tp>>forum_msg
             SET   is_visible=0
             WHERE id=".((int)$input_vars['hide_msg_id'])."
               AND site_id=$site_id
               AND forum_id=$forum_id
               AND thread_id=$thread_id
               AND (   `name`='".\e::db_escape($visitor['site_visitor_login'])."'
                    OR {$visitor['is_moderator']})";
    //prn($query);exit();
    \e::db_execute($query);
}
//------------------- hide message - end ---------------------------------------






//------------------- show message - begin -------------------------------------
if($visitor['is_moderator'] && isset($input_vars['show_msg_id'])) {
    $thread_id = checkInt($input_vars['thread_id']);
    $query="UPDATE <<tp>>forum_msg
             SET   is_visible=1
             WHERE id=".((int)$input_vars['show_msg_id'])."
               AND site_id=$site_id
               AND forum_id=$forum_id
               AND thread_id=$thread_id
               AND (   `name`='".\e::db_escape($visitor['site_visitor_login'])."'
                    OR {$visitor['is_moderator']})";
    //prn($query);exit();
    \e::db_execute($query);
}
//------------------- show message - end ---------------------------------------


//------------------- thread info - begin --------------------------------------
$thread_id = checkInt($input_vars['thread_id']);

$this_thread_info =\e::db_getonerow(
        "SELECT  th.*
           , ms.name  AS msg_sender_name
           , ms.email AS msg_sender_email
           , ms.www   AS msg_sender_www
           , ms.msg   AS msg_body
           , ms.data  AS msg_data
           , ms.id    AS msg_id
           , ms.is_visible    AS msg_is_visible
           ,MAX(ms_vis.is_visible) AS  some_messages_visible
    FROM (
          <<tp>>forum_thread AS th
          LEFT JOIN <<tp>>forum_msg AS ms
          ON (ms.thread_id=th.id AND ms.is_first_msg=1) )
         LEFT JOIN <<tp>>forum_msg AS ms_vis
         ON ms_vis.thread_id=th.id
    WHERE th.id={$thread_id}
    GROUP BY th.id
    ORDER BY ms.id ASC
    LIMIT 0,1");

// prn($this_thread_info);
// exit('4');
if(checkInt($this_thread_info['id'])<=0) {
    header("Location: ".\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/thread&site_id=$site_id&forum_id=$forum_id");
    exit;
}

if($visitor['is_moderator']){
   $this_thread_info['URL_delete']=\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/msglist&site_id={$site_id}&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&delete_thread_id={$this_thread_info['id']}";
   $this_thread_info['URL_hide']=\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/msglist&site_id={$site_id}&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&hide_msg_id={$this_thread_info['msg_id']}";
   $this_thread_info['URL_show']=\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/msglist&site_id={$site_id}&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&show_msg_id={$this_thread_info['msg_id']}";
}
//------------------- thread info - end ----------------------------------------


//------------------- add message - begin --------------------------------------
$errors='';
if(isset($input_vars['msg'])) {
    $errors=Array();

    $input_vars['msg']=trim(strip_tags($input_vars['msg']));
    if(strlen($input_vars['msg'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['Message']}\" {$txt['is_empty']}</font></b><br/>";

    #$input_vars['subject']=trim(strip_tags($input_vars['subject']));
    #if(strlen($input_vars['subject'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['forum_thread_subject']}\" {$txt['is_empty']}</font></b><br/>";

    if($_REQUEST['postedcode']!=$_SESSION['code'] OR strlen($_SESSION['code'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['Retype_the_number']}\" {$txt['is_empty']}</font></b><br/>";


    if(count($errors)==0) {
        function ch($name) {
            return \e::db_escape(strip_tags($name));
        }
        $_SESSION['code']='';

        $name   = \e::cast('plaintext',\e::request('name',$visitor['site_visitor_login']));
        
        $email  = \e::cast('plaintext',\e::request('email',$visitor['site_visitor_email']));
        if (!is_valid_email($email)) {
            $email = '';
        }
        
        $www    = \e::cast('plaintext',\e::request('www',$visitor['site_visitor_home_page_url']));
        if (!is_valid_url($www)) {
            $www = '';
        }

        $subject = '';
        // if(strlen($subject)==0)     $subject='-';
        
        $msg     = \e::cast('plaintext',\e::request('msg',''));

        $is_visible=($this_forum_info['is_premoderated']==1)?0:1;

        // $query = "INSERT INTO <<tp>>forum_msg (name, forum_id, site_id, thread_id, email, www, subject, msg, data, is_visible)
	//	          Values ('$name', '$forum_id', '$site_id', '$thread_id', '$email', '$www', '$subject', '$msg', '$data',$is_visible)";
        // mysql_query($query, $link);
        
        \e::db_execute(
                "INSERT INTO <<tp>>forum_msg (name, forum_id, site_id, thread_id, email, www, subject, msg, data, is_visible)
		          VALUES (
                          <<string name>>, 
                          <<integer forum_id>>,
                          <<integer site_id>>,
                          <<integer thread_id>>,
                          <<string email>>, 
                          <<string www>>,
                          <<string subject>>,
                          <<string msg>>, 
                          <<string data>>,
                          <<integer is_visible>>
                          )"
                
                ,[
                    'name'=>$name,
                    'forum_id'=> $forum_id,
                    'site_id'=> $site_id,
                    'thread_id'=> $thread_id,
                    'email'=> $email,
                    'www'=> $www,
                    'subject'=> $subject,
                    'msg'=> $msg,
                    'data'=> $data,
                    'is_visible'=> $is_visible
                ]);
        

        if(!isset($_SESSION['msg'])) $_SESSION['msg']='';
        $_SESSION['msg'].='<div style="color:green;font-weight:bold;">'.text('New_message_added').'</div>';
        if($is_visible==0) $_SESSION['msg'].='<div style="color:green;font-weight:bold;">'.text('Invisible_message_appears_after_moderator_review').'</div>';


        run('notifier/functions');

        //---------------- notify site admin - begin ---------------------------
        $site_admin_list=  \e::db_getrows("SELECT u.email FROM <<tp>>site_user AS su INNER JOIN <<tp>>user AS u ON u.id=su.user_id WHERE su.site_id={$this_site_info['id']}");
        foreach($site_admin_list as $site_admin){
            if(is_valid_email($site_admin['email'])) {
                $path=$this_site_info['title']."/".$this_forum_info['name']."/".$this_thread_info['subject'];
                notification_queue(
                        $site_admin['email'],
                        $path.' - '.$txt['New_message_added'],
                        "\n\n{$txt['New_message_added']}:\n\n".
                        $path."\n".
                        "================================================================\n".
                        "{$txt['Name']} : ".strip_tags($_SESSION['site_visitor_info']['site_visitor_login'])."\n".
                        "E-mail : ".strip_tags($_SESSION['site_visitor_info']['site_visitor_email'])."\n".
                        "WWW : ".strip_tags($_SESSION['site_visitor_info']['site_visitor_home_page_url'])."\n".
                        strip_tags($input_vars['msg'])."\n".
                        "================================================================\n".
                        site_root_URL."/index.php?action=forum%2Fsearch&site_id={$site_id}&filter_is_visible=0&submit=%C7%ED%E0%E9%F2%E8&orderby=data+desc \n\n",
                        'notify_action_email');
            }
        }
        //---------------- notify site admin - end -----------------------------

        // --------------- notify forum moderators - begin ---------------------
        // prn($this_forum_info['moderators']);exit();
        if($this_forum_info['moderators'] && is_array($this_forum_info['moderators']) && count($this_forum_info['moderators'])>0){
           $query=Array();
           foreach($this_forum_info['moderators'] as $moderator_login){
               $query[]=  \e::db_escape($moderator_login);
           }
           //prn($query); exit();
           $query="SELECT * FROM <<tp>>site_visitor WHERE site_visitor_login in ('".join("','",$query)."')";
           $moderators=  \e::db_getrows($query);
           if($moderators){
                $path=$this_site_info['title']."/".$this_forum_info['name']."/".$this_thread_info['subject'];

                $subject = $path . ' - ' . $txt['New_message_added'];

                $body = "\n\n{$txt['New_message_added']}:\n\n" .
                        $path . "\n" .
                        "================================================================\n" .
                        "{$txt['Name']} : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_login']) . "\n" .
                        "E-mail : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_email']) . "\n" .
                        "WWW : " . strip_tags($_SESSION['site_visitor_info']['site_visitor_home_page_url']) . "\n" .
                        strip_tags($input_vars['msg']) . "\n" .
                        "================================================================\n" .
                        site_root_URL . "/index.php?action=forum/msglist&thread_id={$thread_id}&site_id={$site_id}&forum_id={$forum_id}&lang={$input_vars['lang']}"." \n\n";


                foreach($moderators as $moderator){
                   notification_queue($moderator['site_visitor_email'], $subject, $body, 'notify_action_email');
                }
           }
        }
        // --------------- notify forum moderators - end -----------------------
        //prn("Location: ".sites_root_URL."/msglist.php?site_id=$site_id&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&start={$input_vars['start']}\n");

        //------------------------ get the page to redirect - begin ------------------
        if($this_forum_info['is_premoderated']==1) {
            $n_messages =\e::db_getonerow("SELECT count(id) as n_messages FROM <<tp>>forum_msg WHERE forum_id='$forum_id' AND site_id='$site_id' AND thread_id='$thread_id'  AND is_first_msg=0 and is_visible=1");
        }else {
            $n_messages =\e::db_getonerow("SELECT count(id) as n_messages FROM <<tp>>forum_msg WHERE forum_id='$forum_id' AND site_id='$site_id' AND thread_id='$thread_id'  AND is_first_msg=0");
        }
        $n_messages = $n_messages['n_messages'];
        //prn($n_messages);
        // (21-1)/10=2 =>20  (20-1)/10=1.9 =>10
        $pagestart  = 10*floor( ($n_messages-1) / 10);
        if($pagestart<0) $pagestart=0;
        //prn($pagestart);

        //------------------------ get the page to redirect - end --------------------
        //exit();
        header("Location: ".site_root_URL."/index.php?action=forum/msglist&site_id=$site_id&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&start={$pagestart}\n");
        run("session_finish");         //finish session
        //prn($_SESSION);
        exit();
    }
    $errors=join(' ',$errors);
}
//------------------- add message - end ----------------------------------------

// -------------------------- create confirmation code - begin -----------------
if(!isset($_SESSION['code']) || strlen($_SESSION['code'])==0) {
    srand((float)microtime() * 1000000);
    $chars = explode(',','1,2,3,4,5,6,7,8,9,0');
    shuffle($chars);
    $chars = join('',$chars);
    $chars = substr ($chars,0,3);
    $_SESSION['code']=$chars;
}

// -------------------------- create confirmation code - end -------------------




$start= isset($input_vars['start'])?abs((int)$input_vars['start']):0;



//------------------------ first message in thread - begin ---------------------
$from=$this_thread_info['msg_sender_name'];
if(is_valid_email($this_thread_info['msg_sender_email'])) {
    $tmp=explode('@',$this_thread_info['msg_sender_email']);
    $from.="
            <script>
              document.write('<br><a href=mailto:');
              document.write('{$tmp[0]}');
              document.write('@');
              document.write('{$tmp[1]}');
              document.write('>');
              document.write('{$tmp[0]}');
              document.write('@');
              document.write('{$tmp[1]}');
              document.write('</a>');
            </script>
            ";
    $this_thread_info['msg_sender_email']=$from;
}
$this_thread_info['msg_body']=show_message($this_thread_info['msg_body']);

//------------------------ first message in thread - end -----------------------

// ----------------------- get messages - begin --------------------------------
if($this_forum_info['is_premoderated']==1) {
    // if visitor is moderator then do not require only visible messages
    $is_visible=$visitor['is_moderator']?'':"and is_visible=1";
    $query="SELECT SQL_CALC_FOUND_ROWS *
            FROM <<tp>>forum_msg
            WHERE site_id=$site_id
              AND forum_id=$forum_id
              AND thread_id=$thread_id
              AND is_first_msg=0
              $is_visible
            ORDER BY `data` ASC LIMIT $start, 10 ";
} else {
    $query="SELECT SQL_CALC_FOUND_ROWS *
            FROM <<tp>>forum_msg
            WHERE site_id=$site_id
              AND forum_id=$forum_id
              AND thread_id=$thread_id
              AND is_first_msg=0
            ORDER BY `data` ASC LIMIT $start, 10 ";
}
$messages = \e::db_getrows($query);
$num = \e::db_getonerow("SELECT FOUND_ROWS() AS n_records");
$n_records=$num['n_records'];
// ----------------------- get messages - end ----------------------------------
//exit('7');
// ----------------------- adjust messages - begin -----------------------------
$cnt=count($messages);
for($i=0;$i<$cnt;$i++) {
    $row= & $messages[$i];
    if($row['is_visible']) {
        if(!is_valid_url($row['www'])) {
            $row['www']='';
        }
        if($row['name']=='Anonymous') {
            $row['name']='Anonymous.';
        }
        $row['msg']=show_message($row['msg']);
    }else {
        $row['subject']=text('Invisible_message');
        $row['msg']=text('Invisible_message_appears_after_moderator_review');
    }
    if($visitor['is_moderator']){
        $row['URL_delete']=\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/msglist&site_id={$site_id}&start={$start}&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&delete_msg_id={$row['id']}";
        if($row['is_visible']){
            $row['URL_hide']=\e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/msglist&site_id={$site_id}&start={$start}&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&hide_msg_id={$row['id']}";
            $row['URL_show']='';
        }else{
            $row['URL_hide'] = "";
            $row['URL_show'] = \e::config('APPLICATION_PUBLIC_URL')."/index.php?action=forum/msglist&site_id={$site_id}&start={$start}&forum_id=$forum_id&thread_id=$thread_id&lang={$input_vars['lang']}&show_msg_id={$row['id']}";
        }
    }
}
// ----------------------- adjust messages - end -------------------------------


# --------------------- paging - begin -----------------------------------------
$pages='';
if($n_records>10) {
    $pages=" {$txt['Pages']} : ";
    for($i=0;$i<$n_records; $i=$i+10) {
        if ($i == $start) {
            $to = '<b>[' . (1 + $i / 10) . ']</b>';
        } else {
            $to = (1 + $i / 10);
        }

        $page_url=\e::url_from_template(\e::config('url_template_message_list'),
            [
                'site_id'=>$site_id,
                'lang'=>$input_vars['lang'],
                'forum_id'=>$forum_id,
                'thread_id'=>$thread_id,
                'start'=>$i
            ]);
        $pages.="<a href=\"{$page_url}\">".$to."</a>\n";
    }
}
# --------------------- paging - end -------------------------------------------

if (!isset($input_vars['msg'])) {
    $input_vars['msg'] = '';
}
if (!isset($input_vars['subject'])) {
    $input_vars['subject'] = '';
}



$form=Array('hiddent_fields'=>"<INPUT type='hidden' NAME='action' value='forum/msglist'>
                               <INPUT type='hidden' NAME='site_id' value='$site_id'>
                               <INPUT type='hidden' NAME='forum_id' value='$forum_id'>
                               <INPUT type='hidden' NAME='thread_id' value='$thread_id'>
                               <INPUT type='hidden' NAME='lang' value='{$input_vars['lang']}'>
                               <INPUT type='hidden' NAME='start' value='{$start}'>",
        'action'=>site_root_URL.'/index.php',
        'errors'=>$errors,
        'fld_name'=>Array('name'=>'name','value'=>\e::request('name')),
        'fld_email'=>Array('name'=>'email','value'=>\e::request('email')),
        'fld_subject'=>Array('name'=>'subject','value'=>\e::request('subject')),
        'fld_msg'=>Array('name'=>'msg','value'=>\e::request('msg')),
        'fld_postedcode'=>Array('name'=>'postedcode','value'=>site_root_URL."/index.php?action=gb/bookcode")
);



run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list=list_of_languages();
$cnt=count($lang_list);
for($i=0;$i<$cnt;$i++) {
    if(!isset($this_site_info['extra_setting']['lang'][$lang_list[$i]['lang']])){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url']=\e::url_from_template(
            \e::config('url_template_message_list'),
            [
                'site_id'=>$site_id,
                'lang'=>$lang_list[$i]['name'],
                'forum_id'=>$forum_id,
                'thread_id'=>$thread_id,
                'start'=>0
            ]);
    $lang_list[$i]['lang']=$lang_list[$i]['name'];
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
$_template = site_get_template($this_site_info,'template_forum_msglist');

$echo=process_template( $_template
        ,Array(
        'forum'=>$this_forum_info,
        'site'=>$this_site_info,
        'thread'=>$this_thread_info,
        'messages'=>$messages,
        'pages'=>$pages,
        'visitor'=>$visitor,
        'form'=>$form,
        'cms_root_url'=>site_root_URL,
        'URL_view_forum_list' => site_root_URL."/index.php?action=forum/forum&site_id=$site_id&lang={$input_vars['lang']}",
        'URL_view_thread_list'=> site_root_URL."/index.php?action=forum/thread&site_id=$site_id&forum_id=$forum_id&lang={$input_vars['lang']}"
        )
);



$file_content=process_template($this_site_info['template']
        ,Array(
        'page'=>Array(
                'title'=>$this_thread_info['subject'].' - '.$txt['List_of_messages']
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

global $main_template_name;
$main_template_name='';
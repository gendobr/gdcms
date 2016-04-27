<?php

global $main_template_name;
$main_template_name = '';

if (isset($input_vars['interface_lang']))
    if (strlen($input_vars['interface_lang']) > 0)
        $input_vars['lang'] = $input_vars['interface_lang'];
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);

$msg = '';


// ------------- confirm new password - begin ----------------------------------
if(isset($input_vars['site_visitor_code'])){
    $site_visitor_code=\e::db_escape(trim($input_vars['site_visitor_code']));

    if(strlen($site_visitor_code)==strlen(md5(''))){
        $query = "SELECT site_visitor_id,
                         site_visitor_login,
                         site_visitor_email,
                         '' as site_visitor_home_page_url,
                         0 as is_cms_user,
                         site_visitor_password,
                         site_visitor_code
                  FROM {$table_prefix}site_visitor
                  WHERE site_visitor_code like '{$site_visitor_code}.%'";
        $info =\e::db_getonerow($query);
        if($info){
            $new_password=explode('.',$info['site_visitor_code']);
            $new_password=md5($new_password[1]);
            $query = "UPDATE {$table_prefix}site_visitor
                      SET site_visitor_code=NULL,
                          site_visitor_password='{$new_password}'
                      WHERE site_visitor_id={$info['site_visitor_id']}";
                      prn($query);
            \e::db_execute($query);
        }
    }
    if(isset($new_password)){
        $msg=text('New_password_is_set');
    }else{
        $msg=text('Invalid_confirmation_link');
    }
}
// ------------- confirm new password - end ------------------------------------


// ------------- create confirmation link - begin ------------------------------
if (isset($input_vars['name'])) {
    $user_login = \e::db_escape(trim($input_vars['name']));
    // ----------------- check if login name exists - begin ---------------------
    $query = "SELECT id as site_visitor_id,
                     user_login as site_visitor_login,
                     email as site_visitor_email,
                     '' as site_visitor_home_page_url,
                     1 as is_cms_user,
                     user_password as site_visitor_password
              FROM {$table_prefix}user
              WHERE user_login='{$user_login}'
              UNION
              SELECT site_visitor_id,
                     site_visitor_login,
                     site_visitor_email,
                     '' as site_visitor_home_page_url,
                     0 as is_cms_user,
                     site_visitor_password
              FROM {$table_prefix}site_visitor
              WHERE site_visitor_login='{$user_login}'";
    //prn($query);
    $info =\e::db_getonerow($query);
    //prn($info);
    // ----------------- check if login name exists - begin ---------------------
    if ($info && $info['is_cms_user']==0) {
        // create a temporary code
        $site_visitor_code = md5(session_id());

        // create a new random password
        $new_password=  substr(md5($site_visitor_code.$table_prefix),0,10);

        // save codes
        $query="UPDATE {$table_prefix}site_visitor
                SET site_visitor_code='{$site_visitor_code}.{$new_password}'
                WHERE site_visitor_login='{$user_login}'
                AND site_visitor_id={$info['site_visitor_id']}";
        \e::db_execute($query);

        // create email and confirmation link
        if(is_valid_email($info['site_visitor_email'])){
            run('notifier/functions');
            notification_queue(
		$info['site_visitor_email'],
                text('Password_reminder_email'),
                sprintf(str_replace("\\n","\n",text('Password_reminder_email_body')),
                        $info['site_visitor_login'],
                        site_root_URL."/index.php?action=forum%2Fresetpassword&site_visitor_code=".  rawurlencode($site_visitor_code),
                        $new_password)
                ,
		'notify_action_email'
            );
        }
        $msg = sprintf(text('Password_reminder_email_sent'),$info['site_visitor_email']);


    }
    else {
        $msg = '<img src=img/smiles/icon_sad.gif> ' . text('Wrong_login_name');
    }
} else {
    $input_vars['name'] = '';
}
// ------------- create confirmation link - end --------------------------------


// ------------- draw form - begin ---------------------------------------------
echo "
    <h1>".text('Password_reminder')."</h1>
  $msg
  <form action=" . site_root_URL . "/index.php style='width:300px;text-align:right;'>
   <input type=hidden name=action value=forum/resetpassword>
   <input type=hidden name=lang   value={$input_vars['lang']}>
   {$txt['User_Login']}:<input type=text name=name value=\"" . htmlspecialchars($input_vars['name']) . "\"><br>
   <input type=submit value=\"{$txt['Send_me_password']}\">
  </form>
";
// ------------- draw form - end -----------------------------------------------

// remove from history
nohistory($input_vars['action']);
?>
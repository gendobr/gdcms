<?php

global $main_template_name;
$main_template_name = '';

if (isset($input_vars['interface_lang']))
    if (strlen($input_vars['interface_lang']) > 0)
        $input_vars['lang'] = $input_vars['interface_lang'];
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);

$msg = '';

if (isset($input_vars['name'])) {
    $user_login = \e::db_escape(trim($input_vars['name']));
    $user_password = md5(trim($input_vars['pswd']));
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
    if ($info) {
        $_SESSION['site_visitor_info'] = $info;
        $_SESSION['site_visitor_info']['is_logged'] = ($user_password == $_SESSION['site_visitor_info']['site_visitor_password']);

        if ($info['is_cms_user'] == 1) {
            $_SESSION['user_info'] = do_login(trim($input_vars['name']), trim($input_vars['pswd']));
            if (is_admin())
                $_SESSION['site_visitor_info']['is_logged'] = true;
        }
        //prn($_SESSION);
        echo "
          <script>
            window.opener.location.reload();
            window.close();
          </script>
      ";
        run("session_finish");         //finish session
        exit();
    }
    else {
        $msg = '<img src=img/smiles/icon_sad.gif> ' . text('Wrong_login_name_or_password');
    }
} else {
    $input_vars['name'] = '';
}



echo "
  $msg
  <form action=" . site_root_URL . "/index.php style='width:300px;text-align:right;' method='POST'>
   <input type=hidden name=action value=forum/login>
   <input type=hidden name=lang   value={$input_vars['lang']}>
   {$txt['User_Login']}:<input type=text name=name value=\"" . checkStr($input_vars['name']) . "\"><br>
   {$txt['Password']}:<input type=password name=pswd><br>
   <a href=\"index.php?action=forum/resetpassword\">{$text['Password_reminder']}</a>
   <input type=submit value=\"{$txt['Enter']}\">
  </form>
";

// remove from history
nohistory($input_vars['action']);
?>
<?

/*
  Password recovery & form
 */

$error_msg = '';
$_SESSION['user_info']['is_logged'] = false;

if (strlen($input_vars['user_login']) > 0) {
//-------------------------- check info -- begin -------------------------------
    //------------------- get user info -- begin -------------------------------
    $tmp_user_info = db_getonerow(
            "SELECT * FROM {$table_prefix}user WHERE user_login='" . DbStr($input_vars['user_login']) . "'"
    );
    //------------------- get user info -- end ---------------------------------
    if ($tmp_user_info['id'] > 0) {

        // set new password
        $tmp_user_info['user_password'] = substr(md5(session_id() . time()), 0, 8);
        $query = "UPDATE {$table_prefix}user
                SET user_password='" . md5($tmp_user_info['user_password']) . "'
                WHERE user_login='" . DbStr($tmp_user_info['user_login']) . "'";
        //prn($query);
        db_execute($query);

        //----------------- send mail - begin ----------------------------------
        // prn("{$prefix}/mailer/class.phpmailer.php");
        require_once(script_root . "/lib/class.phpmailer.php");
        include(script_root . "/lib/mailing.php");
        // prn($tmp_user_info);
        my_mail(
                $tmp_user_info['email']  // receiver
                , 'Password reminder' // subject
                , "Dear {$tmp_user_info['full_name']} \n\n" .
                "Your login name is {$tmp_user_info['user_login']}\n" .
                "Your password is {$tmp_user_info['user_password']}\n" .
                " Regards " . mail_FromName
        );
        $input_vars['page_content'] = " {$text['Password_is_sent']} ";
        //----------------- send mail - end --------------------------------------
    } else {
        $input_vars['page_content'] = $text['ERROR'] . ' : ' . $text['Wrong_login_name'];
    }
//-------------------------- check info -- end ---------------------------------
} else {
//---------------------- form - begin ------------------------------------------

    $input_vars['page_content'] = "
  <font color=red><b>$error_msg</b></font>
  <form action=index.php method=post>
  <input type=hidden name=action value='forgot_password'>
  {$text['Login_name']} : <input type=text     name=user_login    value='" . checkStr($input_vars['user_login']) . "'>
  <input type=submit value='{$text['Send_me_password']}'>
  </form>
  ";
//---------------------- form - end --------------------------------------------
}



$input_vars['page_title'] = $text['Password_reminder'];
$input_vars['page_header'] = $text['Password_reminder'];
?>
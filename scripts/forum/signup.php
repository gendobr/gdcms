<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

global $main_template_name; $main_template_name='';

if(isset($input_vars['interface_lang'])) if(strlen($input_vars['interface_lang'])>0) $input_vars['lang']=$input_vars['interface_lang'];
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
$echo='';

run('site/menu');
run('forum/functions');


$errors=Array();
if(isset($input_vars['site_visitor_login']))
{

   $input_vars['site_visitor_login']=trim(strip_tags($input_vars['site_visitor_login']));
   if(strlen($input_vars['site_visitor_login'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['Name']}\" {$txt['is_empty']}</font></b><br/>";


   $input_vars['site_visitor_password']=trim(strip_tags($input_vars['site_visitor_password']));
   if(strlen($input_vars['site_visitor_password'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"{$txt['Password']}\" {$txt['is_empty']}</font></b><br/>";

   $input_vars['site_visitor_password_again']=trim(strip_tags($input_vars['site_visitor_password_again']));
   if($input_vars['site_visitor_password']!=$input_vars['site_visitor_password_again']) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Passwords_do_not_match']}</font></b><br/>";

   $input_vars['site_visitor_email']=trim(strip_tags($input_vars['site_visitor_email']));
   if(strlen($input_vars['site_visitor_email'])==0) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Value_of']} \"E-mail\" {$txt['is_empty']}</font></b><br/>";
   if(!is_valid_email($input_vars['site_visitor_email'])) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['invalid_email_address']}</font></b><br/>";

   $input_vars['site_visitor_home_page_url']=trim(strip_tags($input_vars['site_visitor_home_page_url']));
   if(strlen($input_vars['site_visitor_home_page_url'])>0 && !is_valid_url($input_vars['site_visitor_home_page_url'])) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Invalid_url_format']}</font></b><br/>";

     // ----------------- check if login name exists - begin --------------------
       $query="SELECT id as site_visitor_id,
                      user_login as site_visitor_login,
                      email as site_visitor_email,
                      '' as site_visitor_home_page_url
               FROM {$table_prefix}user
               WHERE user_login='".\e::db_escape($input_vars['site_visitor_login'])."'
               UNION
               SELECT  site_visitor_id,
                       site_visitor_login,
                       site_visitor_email,
                       '' as site_visitor_home_page_url
               FROM {$table_prefix}site_visitor
               WHERE site_visitor_login='".\e::db_escape($input_vars['site_visitor_login'])."'";
       $info=\e::db_getonerow($query);
       if($info) $errors[]="<b><font color=red>{$txt['ERROR']} : {$txt['Login_already_exists']}</font></b><br/>";
    // ----------------- check if login name exists - begin --------------------

   if(count($errors)==0)
   {
       $site_visitor_password=md5($input_vars['site_visitor_password']);
       $site_visitor_login=\e::db_escape($input_vars['site_visitor_login']);
       $site_visitor_email=\e::db_escape($input_vars['site_visitor_email']);
       $site_visitor_home_page_url=\e::db_escape($input_vars['site_visitor_home_page_url']);
       $query="insert into {$table_prefix}site_visitor (
                        site_visitor_password,
                        site_visitor_login,
                        site_visitor_email,
                        site_visitor_home_page_url
                        )
                        values
                        (
                        '$site_visitor_password',
                        '$site_visitor_login',
                        '$site_visitor_email',
                        '$site_visitor_home_page_url'
                        )
                ";
        \e::db_execute($query);

        $_SESSION['site_visitor_info']=\e::db_getonerow("SELECT * FROM {$table_prefix}site_visitor WHERE site_visitor_id=LAST_INSERT_ID()");
        echo "
           <h1>{$txt['Signup']}</h1>
           <b><font color=green>{$txt['Signup_finished_successfully']}</font>
           <a href='javascript:void(en())'>����������</a>
           <script>
              function en()
              {
                 window.opener.location.reload();
                 window.close();
              }
           </script>
        ";
        return '';
   }
}
else
{
   $input_vars['site_visitor_login']=
   $input_vars['site_visitor_email']=
   $input_vars['site_visitor_home_page_url']='';
}



   $visitor_info="
   <h1>{$txt['Signup']}</h1>
   <div>".join('</div><div>',$errors)."</div>
   <form method=post action=index.php>
    <input type=hidden name=action value=\"forum/signup\">
    <input type=hidden name=lang value=\"{$input_vars['lang']}\">
      <table>
      <tr>
        <td><b>{$txt['Name']} <font color=red>*</font></b></td>
        <td><input type=text name=site_visitor_login value=\"".htmlspecialchars($input_vars['site_visitor_login'])."\"></td>
      </tr>
      <tr>
        <td><b>{$txt['Password']} <font color=red>*</font></b></td>
        <td><input type=password name=site_visitor_password value=\"\"></td>
      </tr>
      <tr>
        <td><b>{$txt['Password_again']} <font color=red>*</font></b></td>
        <td><input type=password name=site_visitor_password_again value=\"\"></td>
      </tr>
      <tr>
        <td><b>{$txt['Email']} <font color=red>*</font></b></td>
        <td><input type=text name=site_visitor_email value=\"".htmlspecialchars($input_vars['site_visitor_email'])."\"></td>
      </tr>

      <tr>
        <td><b>WWW</b></td>
        <td><input type=text name=site_visitor_home_page_url value=\"".htmlspecialchars($input_vars['site_visitor_home_page_url'])."\"></td>
      </tr>
      <tr>
        <td></td>
        <td><input type=submit value=\"{$txt['Signup']}\"></td>
      </tr>
      </table>
   </form>
   ";


echo $visitor_info;


// remove from history
   nohistory($input_vars['action']);


?>

<?php
/**
  Login form for the site visitors
  arguments are 
    $nick - nickname
    $lp   - login_password 
*/

$error_msg='';

$_SESSION['site_visitor_info']=Array(
  'id'=>0
 ,'site_id'=>0
 ,'login_password'=>''
 ,'name_nick'=>'Site guest'
 ,'name_first'=>''
 ,'name_middle'=>''
 ,'name_last'=>''
 ,'birthdate'=>''
 ,'email'=>''
 ,'home_page_url'=>''
 ,'telephone'=>''
 ,'address'=>''
 ,'additional_info'=>''
 ,'is_logged'=>false
);

$GLOBALS['main_template_name']='';
#prn($input_vars);

//-------------------------- check info -- begin -------------------------------
if(!isset($input_vars['nick'])) $input_vars['nick']='';

if(strlen($input_vars['nick'])>0)
{
  if(strlen($input_vars['lp'])>0)
  {
   run('site_visitor/functions');
   // ------------------- get user info -- begin -------------------------------
      $tmp_user_info=site_visitor_check_login(map_nick($input_vars['nick']),$input_vars['lp']);
   // ------------------- get user info -- end ---------------------------------

     if($tmp_user_info)
     {
        $_SESSION['site_visitor_info']=$tmp_user_info;
        $_SESSION['site_visitor_info']['is_logged']=true;
     }
     else
     {
       $error_msg.=$text['ERROR'].' : '.$text['Wrong_login_name_or_password'];
     }
  }
  else
  {
     $error_msg.=$text['ERROR'].' : '.$text['Password_is_not_set'];
  }
}
//-------------------------- check info -- end ---------------------------------



if($_SESSION['site_visitor_info']['is_logged'])
{
  $input_vars['page_title'] = '';
  $input_vars['page_header'] = '';
  $input_vars['page_content']="
  <script type=\"text/javascript\">
  <!-- 
   if(window)
   {
     window.opener.location.reload();
     window.close();
   }  
  // -->
  </script>
  ";

}
else
{
//---------------------- login form - begin ------------------------------------
  $input_vars['page_title'] = $text['Enter_password'];
  $input_vars['page_header'] = $text['Enter_password'];
  $input_vars['page_content']="
  <font color=red><b>$error_msg</b></font>
  <form action=index.php method=post>
  <input type=hidden name=action value='site_visitor/login'>
  {$text['Login_name']} : <input type=text name=nick value='".checkStr($input_vars['nick'])."'><br>
  {$text['Password']} : <input type=password name=lp value=''><br>
  <input type=submit value='{$text['Enter']}'>
  </form>
  ";
//---------------------- login form - end --------------------------------------
}

echo "
<h1>{$input_vars['page_header']}</h1>
<p>{$input_vars['page_content']}</p>
";


// remove from history
   nohistory($input_vars['action']);



?>
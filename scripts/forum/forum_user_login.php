<?php
# public area
# function to check login info

prn($_SESSION);
function login_info()
{
  # do logout if logout=yes
    if($GLOBALS['input_vars']['logout']=='yes') unset($_SESSION['forum_user_info']);

  # ------------------ do login - begin ----------------------------------------
    if(isset($GLOBALS['input_vars']['un']))
    if(isset($GLOBALS['input_vars']['pw']))
    if(strlen($GLOBALS['input_vars']['un'])>0)
    if(strlen($GLOBALS['input_vars']['pw'])>0)
    {
      $_SESSION['forum_user_info']=\e::db_getonerow("SELECT * FROM <<tp>>forum_user WHERE user_login='".\e::db_escape($GLOBALS['input_vars']['un'])."' AND user_password='".md5($GLOBALS['input_vars']['pw'])."'");
      if(!$_SESSION['forum_user_info']) unset($_SESSION['forum_user_info']);
    }
  # ------------------ do login - end ------------------------------------------

  $tor='';
  if(isset($_SESSION['forum_user_info']))
  {
    # user is logged
    $tor="
         {$GLOBALS['text']['Welcome']}, <b>{$_SESSION['forum_user_info']['full_name']}</b>,
         <a href=\"/cms/index.php?action=forum/forum_user_profile&user_id={$_SESSION['forum_user_info']['id']}\">{$GLOBALS['text']['Edit_profile']}</a>
         <a href=\"/cms/index.php?".query_string('^(un|pw)$')."\">{$GLOBALS['text']['Logout']}</a>
         ";
  }
  else
  {
    # ------------- user is not logged - begin ---------------------------------
    $tor="<form action={$_SERVER['PHP_SELF']}>".
          hidden_fields('^(un|pw)$')
        ."&nbsp;&nbsp;&nbsp;{$GLOBALS['text']['Login_name']}:<input type=text name=un style='width:50px; border:1px solid black;'>
          {$GLOBALS['text']['Password']}:<input type=password name=pw style='width:50px; border:1px solid black;'>
          <input type=submit value=\"{$GLOBALS['text']['Login']}\" style='border:1px solid black;'>
          &nbsp;&nbsp;&nbsp;
          <a href=\"/cms/index.php?action=forum/forum_user_signup\">{$GLOBALS['text']['Signup']}</a>
          </form>";
    # ------------- user is not end - end --------------------------------------
  }
  return '<div align=left>'.$tor.'</div>';
}

?>
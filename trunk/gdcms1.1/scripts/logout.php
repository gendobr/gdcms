<?php
/**
 * Login & form
 */

$error_msg='';
$GLOBALS['main_template_name']='';

// do logout
if($_SESSION['user_info']['is_logged']) {
    ml('logout',$_SESSION);
    $_SESSION['user_info']['is_logged']=false;
    header("Location:scripts/login.html");
    echo "OK";
    return '';
}
// remove from history
   nohistory($input_vars['action']);


?>
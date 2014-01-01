<?php
/*
 *
*/

$GLOBALS['main_template_name']='';

header("Content-Type:text/html; charset=".site_charset);

run('site_visitor/functions');

$_SESSION['site_visitor_info']=site_visitor_check_login($input_vars['site_visitor_email'],$input_vars['site_visitor_password']);
//prn($_SESSION['site_visitor_info']);


if( $_SESSION['site_visitor_info'] ) {
    $_SESSION['site_visitor_info']['is_logged']=true;
    echo json_data($_SESSION['site_visitor_info']);
}
else {
    echo "<!-- No_saved_info -->".text('No_saved_info');
}


// remove from history
   nohistory($input_vars['action']);


?>
<?php
unset($_SESSION['site_visitor_info']);
$GLOBALS['main_template_name']='';
header("Content-Type:text/html; charset=".site_charset);

// remove from history
   nohistory($input_vars['action']);


?>
OK
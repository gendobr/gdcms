<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$GLOBALS['main_template_name']='';
$tmp=\e::db_getonerow("SELECT msg FROM <<tp>>forum_msg WHERE id=".( (int)$input_vars['msg_id'] ));
header("Content-Type: text/html; charset=".site_charset,true);
echo $tmp['msg'];
?>
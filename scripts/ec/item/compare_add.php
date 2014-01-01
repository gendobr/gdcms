<?php

global $main_template_name; $main_template_name='';

if(!isset($_SESSION['items_to_compare']) ) $_SESSION['items_to_compare']=Array();

$_SESSION['items_to_compare'][$input_vars['ec_item_id']]=$input_vars['ec_item_id'];

echo "OK";

// remove from history
   nohistory($input_vars['action']);


?>

<?php
/*
Smarty custom function
get list of active categories
*/

$site_id=(int)$site_id;
$deep=(int)$deep;

if($deep>0) $deep_restriction=" and pa.deep=$deep";
else $deep_restriction="";
$query="SELECT DISTINCT pa.*
        FROM {$table_prefix}ec_category as pa,
             {$table_prefix}ec_category as ch,
             {$table_prefix}ec_item as ec_item
        WHERE pa.start<=ch.start and ch.finish<=pa.finish
          and ch.ec_category_id=ec_item.ec_category_id
          and ch.site_id=$site_id
          and pa.site_id=$site_id
          $deep_restriction
          and (ec_item.ec_item_cense_level&".ec_item_show.")>0  
          and pa.is_visible  
        ORDER BY pa.start
        ";


$smarty->_tpl_vars['ec_categories']=\e::db_getrows($query);
//prn($smarty->_tpl_vars['ec_categories']);
?>
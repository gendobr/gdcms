<?php
/*
 last added image in the selecteg ec_category
*/
$ec_category_id=isset($input_vars['ec_category_id'])?( (int)$input_vars['ec_category_id'] ):0;

$query="select ec_item.ec_item_img1 as img, ec_item.site_id
from
{$GLOBALS['table_prefix']}ec_category as pa,
{$GLOBALS['table_prefix']}ec_category as ch,
{$GLOBALS['table_prefix']}ec_item as ec_item

where
    pa.start<=ch.start and ch.finish<=pa.finish
and pa.site_id=ch.site_id
and ec_item.ec_category_id=ch.ec_category_id
and ( ifnull(ec_item.ec_item_img1,'')<>'')
and pa.ec_category_id=$ec_category_id
and ".ec_item_show."&ec_item.ec_item_cense_level
order by ec_item.ec_item_last_change_date DESC
limit 0,1";
$img=\e::db_getonerow($query);

//------------------- site info - begin ----------------------------------------
  run('site/menu');
  $site_id = isset($img['site_id'])?((int)$img['site_id']):0;
  $this_site_info = get_site_info($site_id);
  //prn($this_site_info);
  if(!$this_site_info['id'])
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- site info - end ------------------------------------------


$site_root_dir=\e::config('SITES_ROOT').'/'.$this_site_info['dir'];
$path=$site_root_dir.'/'.$img["img"];

//prn($path);
readfile($path);
global $main_template_name;
$main_template_name='';

// remove from history
   nohistory($input_vars['action']);

?>
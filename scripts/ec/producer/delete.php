<?php
/**
  Deleting producer info
*/

  run('ec/producer/functions');

  $ec_producer_id = isset($input_vars['ec_producer_id'])?(int)$input_vars['ec_producer_id']:0;
  $this_producer_info = get_producer_info($ec_producer_id);
  //prn($this_producer_info);


//------------------- get site info - begin ------------------------------------
  run('site/menu');
  $this_site_info=false;
  if(isset($this_producer_info['site_id']))
  {
      $site_id = (int)$this_producer_info['site_id'];
      $this_site_info = get_site_info($site_id);
  }
  //prn($this_site_info);
  if(!$this_site_info)
  {
     $input_vars['page_title']   =
     $input_vars['page_header']  =
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- get site info - end --------------------------------------


//------------------- check permission - begin ---------------------------------
$this_site_info['admin_level']=get_level($site_id);
if($this_site_info['admin_level']==0 && !is_admin())
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------

# ---------------- delete image - begin ----------------------------------------
  $site_root_dir=\e::config('SITES_ROOT').'/'.$this_site_info['dir'];
  //prn($input_vars);
  $path=$site_root_dir.'/'.$this_producer_info["ec_producer_img"];
  if(is_file($path)) unlink($path);
# ---------------- delete image - end ------------------------------------------


$query="DELETE FROM {$table_prefix}ec_producer WHERE ec_producer_id=$ec_producer_id";
\e::db_execute($query);

$main_template_name='';

header('Location: index.php?action=ec/producer/list&site_id='.$site_id);

// remove from history
   nohistory($input_vars['action']);


?>
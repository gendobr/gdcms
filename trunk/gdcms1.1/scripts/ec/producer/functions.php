<?php
/* 
 * 
 */

function get_producer_info($ec_producer_id)
{
  global $table_prefix,$db,$input_vars;
  $tor=db_getonerow("SELECT * FROM {$table_prefix}ec_producer WHERE ec_producer_id=$ec_producer_id");
  if($tor)
  {
      $tor['ec_producer_url']=site_root_URL."/index.php?action=ec/producer/view&ec_producer_id=".$tor['ec_producer_id'];
  }
  return $tor;
}

function menu_ec_producer($info)
{
   $tor=Array();

   $tor['producer/edit']=Array(
                       'URL'=>"index.php?action=ec/producer/edit&ec_producer_id={$info['ec_producer_id']}&site_id=".$info['site_id']
                      ,'innerHTML'=>text('EC_edit_producer')
                      ,'attributes'=>''
                      );
   $tor['producer/view']=Array(
                       'URL'=>"index.php?action=ec/producer/view&ec_producer_id={$info['ec_producer_id']}&site_id=".$info['site_id']
                      ,'innerHTML'=>text('EC_item_producer_details').'<br><br>'
                      ,'attributes'=>' target=_blank '
                      );
   $tor['producer/delete']=Array(
                       'URL'=>"index.php?action=ec/producer/delete&ec_producer_id={$info['ec_producer_id']}&site_id=".$info['site_id']
                      ,'innerHTML'=>text('EC_delete_producer')
                      ,'attributes'=>''
                      );
   return $tor;
}
?>

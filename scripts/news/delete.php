<?php
//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  // prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------

//-------------------- delete news - begin -------------------------------------
  if(isset($input_vars['delete_news_id']))
  {
  $delete_news_id=checkInt($input_vars['delete_news_id']);
  if($delete_news_id>0)
  {
     $delete_news_lang=DbStr($input_vars['delete_news_lang']);
     $query="DELETE FROM {$table_prefix}news WHERE id={$delete_news_id} AND lang='$delete_news_lang' AND site_id={$site_id}";
     if($debug) prn(checkStr($query));
     db_execute($query);

     $query="DELETE FROM {$table_prefix}news_tags WHERE news_id={$delete_news_id} AND lang='$delete_news_lang'";
     if($debug) prn(checkStr($query));
     db_execute($query);

     // delete news comments
     if(!db_getonerow("SELECT id FROM {$table_prefix}news WHERE id={$delete_news_id} AND site_id={$site_id} LIMIT 0,1")){
       $query="DELETE FROM {$table_prefix}news_comment WHERE news_id={$delete_news_id} AND site_id={$site_id}";
       if($debug) prn(checkStr($query));
       db_execute($query);
     }

  }
  clear('delete_news_id','delete_news_lang');
  }
//-------------------- delete news - end ---------------------------------------

$main_template_name='';

echo '
<script type="text/javascript">
<!--
window.top.location.reload();
// -->
</script>

';

// remove from history
   nohistory($input_vars['action']);


return '';
?>
<?php

global $main_template_name; $main_template_name='';

$debug=false;
//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
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

//-------------------- delete poll - begin -------------------------------------
  if(isset($input_vars['delete_poll_id']))
  {
  $delete_poll_id=checkInt($input_vars['delete_poll_id']);
  if($delete_poll_id>0)
  {
     $query="DELETE FROM <<tp>>golos_pynannja WHERE id={$delete_poll_id} AND site_id={$site_id}";
     if($debug) prn(htmlspecialchars($query));
     #prn($query);
     \e::db_execute($query);

     $query="DELETE FROM <<tp>>golos_vidpovidi WHERE pynannja_id={$delete_poll_id} AND site_id={$site_id}";
     if($debug) prn(htmlspecialchars($query));
     #prn($query);
     \e::db_execute($query);
  }
  clear('delete_news_id','delete_news_lang');
  }
//-------------------- delete poll - end ---------------------------------------

$main_template_name='';

echo '
<script type="text/javascript">
<!--
';

if($input_vars['action']=='poll/list') echo  "\n window.top.location.reload();\n";
else echo "\n window.top.location.replace('index.php?action=poll/list&site_id={$site_id}');\n";

echo '
// -->
</script>
';


// remove from history
   nohistory($input_vars['action']);


return '';
?>
<?php
/*
  Script to delete site
  Argument is $site_id - site identifier, integer, mandatory 
  (c) Gennsdiy Dobrovolsky, gen_dobr@hotmail.com
*/

//------------------ check permision - begin -----------------------------------
if(!is_admin())
{
  $input_vars['page_title']   = 
  $input_vars['page_header']  = 
  $input_vars['page_content'] = $text['Access_denied'];
  return 0;
}
//------------------ check permision - end -------------------------------------



// remove from history
   nohistory($input_vars['action']);



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

if(isset($input_vars['confirmed']) && $input_vars['confirmed']=='yes') {
   ml('site/delete',$input_vars);
  //---------------------- deleting - begin ------------------------------------
    $input_vars['page_title']   = "{$text['Deleting_site']} &quot;{$this_site_info['title']}&quot;";
    $input_vars['page_header']  = "{$text['Deleting_site']} &quot;{$this_site_info['title']}&quot;";
    $input_vars['page_content']  = "{$text['Site']} &quot;{$this_site_info['title']}&quot; {$text['deleted_successfully']}";
    
    //-------------------- delete files - begin --------------------------------
      if(strlen($this_site_info['dir'])>0)
      {
         $dir_to_delete=sites_root.'/'.$this_site_info['dir'];
         if(is_dir($dir_to_delete))
         {
            run('lib/file_functions');
            rm_r($dir_to_delete);
         }
      }
    //-------------------- delete files - end ----------------------------------
    
    //-------------------- clear database - begin ------------------------------


      $sqls=Array(
        "DELETE FROM {$table_prefix}calendar WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}calendar_cache;",
        "DELETE FROM {$table_prefix}forum_list WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}forum_msg WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}forum_thread WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}fragment WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}gb WHERE site={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}golos_pynannja WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}golos_vidpovidi WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}golos_vidpovidi_details WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}listener WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}news_comment WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}news_subscriber WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}page_menu_group WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}photogalery WHERE site={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}photogalery_rozdil WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}rsssource WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}rsssourceitem WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}site_search WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}news_category WHERE news_id in(select id from cms_news where site_id={$this_site_info['id']});",
        "DELETE FROM {$table_prefix}news_tags WHERE news_id in(select id from cms_news where site_id={$this_site_info['id']});",
        "DELETE FROM {$table_prefix}news WHERE site_id={$this_site_info['id']};",
        "DELETE FROM {$table_prefix}page WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}category WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}ec_cart WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}ec_category_item_field_value WHERE ec_category_item_field_id in ( select ec_category_item_field_id from {$table_prefix}ec_category_item_field where site_id={$this_site_info['id']} )",
        "DELETE FROM {$table_prefix}ec_category_item_field WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}ec_category WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}ec_item_category WHERE ec_item_id in ( select ec_item_id from {$table_prefix}ec_item where site_id={$this_site_info['id']} )",
        "DELETE FROM {$table_prefix}ec_item_variant WHERE ec_item_id in ( select ec_item_id from {$table_prefix}ec_item where site_id={$this_site_info['id']} )",
        "DELETE FROM {$table_prefix}ec_item_comment WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}ec_item_tags WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}ec_item WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}site_user WHERE site_id={$this_site_info['id']}",
        "DELETE FROM {$table_prefix}site WHERE id={$this_site_info['id']}"
        );
      foreach($sqls as $q){
        db_execute($q);
      }


      //------------------ delete menu - begin ---------------------------------
         $query="SELECT id,id FROM {$table_prefix}menu_group WHERE site_id={$this_site_info['id']}";
         $menu_groups=join(',',GetAssociatedArray(db_execute($query)));
         //prn($menu_groups);
         
         if(strlen($menu_groups)>0)
         {
           $query="DELETE FROM {$table_prefix}menu_item WHERE menu_group_id IN($menu_groups);";
           //prn($query);
           db_execute($query);

           $query="DELETE FROM {$table_prefix}menu_group WHERE id IN($menu_groups);";
           //prn($query);
           db_execute($query);
         }
      //------------------ delete menu - end -----------------------------------

    //-------------------- clear database - end --------------------------------
  //---------------------- deleting - end --------------------------------------
}
else
{
  //---------------------- draw confirmation form - begin ----------------------
    $input_vars['page_title']   = $text['Are_You_sure'].'?';
    $input_vars['page_header']  = $text['Are_You_sure'].'?';
    $input_vars['page_content'] = "
    {$text['Are_you_sure_you_want_to_delete_site']} &quot;{$this_site_info['title']}&quot; ?
    <form action=index.php method=post>
    <input type=hidden name=action value=\"site/delete\">
    <input type=hidden name=site_id value=\"{$this_site_info['id']}\">
    <input type=hidden name=confirmed value=\"yes\">
    <input type=submit value=\"".text('positive_answer')."\">
    </form>
    ";
  //---------------------- draw confirmation form - end ------------------------
}

?>

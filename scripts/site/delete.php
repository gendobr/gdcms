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

if(isset($input_vars['confirmed']) && $input_vars['confirmed']=='yes') {
   ml('site/delete',$input_vars);
  //---------------------- deleting - begin ------------------------------------
    $input_vars['page_title']   = "{$text['Deleting_site']} &quot;{$this_site_info['title']}&quot;";
    $input_vars['page_header']  = "{$text['Deleting_site']} &quot;{$this_site_info['title']}&quot;";
    $input_vars['page_content']  = "{$text['Site']} &quot;{$this_site_info['title']}&quot; {$text['deleted_successfully']}";
    
    //-------------------- delete files - begin --------------------------------
      if(strlen($this_site_info['dir'])>0)
      {
         $dir_to_delete=\e::config('SITES_ROOT').'/'.$this_site_info['dir'];
         if(is_dir($dir_to_delete))
         {
            \core\fileutils::rm_r($dir_to_delete);
         }
      }
    //-------------------- delete files - end ----------------------------------
    
    //-------------------- clear database - begin ------------------------------


      $sqls=Array(
        "DELETE FROM <<tp>>calendar WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>calendar_cache;",
        "DELETE FROM <<tp>>forum_list WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>forum_msg WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>forum_thread WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>fragment WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>gb WHERE site={$this_site_info['id']};",
        "DELETE FROM <<tp>>golos_pynannja WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>golos_vidpovidi WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>golos_vidpovidi_details WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>listener WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>news_comment WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>news_subscriber WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>page_menu_group WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>photogalery WHERE site={$this_site_info['id']};",
        "DELETE FROM <<tp>>photogalery_rozdil WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>rsssource WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>rsssourceitem WHERE site_id={$this_site_info['id']};",
        // "DELETE FROM <<tp>>site_search WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>news_category WHERE news_id in(select id from <<tp>>news where site_id={$this_site_info['id']});",
        "DELETE FROM <<tp>>news_tags WHERE news_id in(select id from <<tp>>news where site_id={$this_site_info['id']});",
        "DELETE FROM <<tp>>news WHERE site_id={$this_site_info['id']};",
        "DELETE FROM <<tp>>page WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>category WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>ec_cart WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>ec_category_item_field_value WHERE ec_category_item_field_id in ( select ec_category_item_field_id from <<tp>>ec_category_item_field where site_id={$this_site_info['id']} )",
        "DELETE FROM <<tp>>ec_category_item_field WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>ec_category WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>ec_item_category WHERE ec_item_id in ( select ec_item_id from <<tp>>ec_item where site_id={$this_site_info['id']} )",
        "DELETE FROM <<tp>>ec_item_variant WHERE ec_item_id in ( select ec_item_id from <<tp>>ec_item where site_id={$this_site_info['id']} )",
        "DELETE FROM <<tp>>ec_item_comment WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>ec_item_tags WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>ec_item WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>site_user WHERE site_id={$this_site_info['id']}",
        "DELETE FROM <<tp>>site WHERE id={$this_site_info['id']}"
        );
      foreach($sqls as $q){
        \e::db_execute($q);
      }


      //------------------ delete menu - begin ---------------------------------
         $query="SELECT id,id FROM <<tp>>menu_group WHERE site_id={$this_site_info['id']}";
         $menu_groups=join(',',\e::db_get_associated_array($query));
         //prn($menu_groups);
         
         if(strlen($menu_groups)>0)
         {
           $query="DELETE FROM <<tp>>menu_item WHERE menu_group_id IN($menu_groups);";
           //prn($query);
           \e::db_execute($query);

           $query="DELETE FROM <<tp>>menu_group WHERE id IN($menu_groups);";
           //prn($query);
           \e::db_execute($query);
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

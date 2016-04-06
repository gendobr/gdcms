<?php
/**
  Page menu
  
  arguments are $page_id and $lang 
  
*/
  run('site/menu');

//------------------- check page id - begin ------------------------------------
  $page_id   = checkInt($input_vars['page_id']);

  // $lang      = DbStr($input_vars['lang']);
  $lang = get_language('lang');
  
  $query="SELECT * FROM {$table_prefix}page WHERE id={$page_id} AND lang='$lang'";
  $this_page_info=\e::db_getonerow($query);
  //prn($query,$this_page_info);
  if(checkInt($this_page_info['id'])<=0)
  {
     $input_vars['page_title']  =$text['Page_not_found'];
     $input_vars['page_header'] =$text['Page_not_found'];
     $input_vars['page_content']=$text['Page_not_found'];
     return 0;
  }
  //prn('$this_page_info',$this_page_info);
//------------------- check page id - end --------------------------------------

//------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($this_page_info['site_id']);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =$text['Access_denied'];
     $input_vars['page_header'] =$text['Access_denied'];
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
//------------------- get permission - end -------------------------------------

//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($this_page_info['site_id']);
  $this_site_info = get_site_info($site_id);

  // prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------
# ----------------------- list of site managers - begin ------------------------
  $tmp=\e::db_getrows(
       "select u.id, u.full_name, u.user_login, u.email, su.level
        from {$table_prefix}user AS u, {$table_prefix}site_user AS su
        where u.id = su.user_id AND su.site_id = {$this_site_info['id']}
        order by level desc");
  $this_site_info['managers']=Array();
  foreach($tmp as $tm) $this_site_info['managers'][$tm['id']] = $tm;
  unset($tm, $tmp);
# ----------------------- list of site managers - end --------------------------





# ----------------------- update page menus - begin ----------------------------
# prn($input_vars); 
  if(isset($input_vars['save']))
  {
    $checked=Array();  $checked[]=0;
    if(isset($_REQUEST['page_menu_group']))
    if(is_array($_REQUEST['page_menu_group']))
    {
      $checked=$_REQUEST['page_menu_group'];
    }

    $query="DELETE FROM {$table_prefix}page_menu_group 
            WHERE site_id={$this_site_info['id']}
              AND page_id={$this_page_info['id']}
              AND lang='{$this_page_info['lang']}'
              AND page_id NOT IN (".join(',',$checked).")";
    #prn($query);
    \e::db_execute($query);
    
    
    $query=Array();
    foreach($checked as $ch)
    {
      $ch=(int)$ch;
      if($ch<=0) continue;
      $query[]="({$this_page_info['site_id']}, {$this_page_info['id']}, $ch, '{$this_page_info['lang']}')";
    }
    if(count($query)>0)
    {
      $query="INSERT INTO {$table_prefix}page_menu_group (site_id, page_id, menu_group_id, lang)
              VALUES ".join(',',$query);
      #prn($query);
      \e::db_execute($query);
    }
  }
# ----------------------- update page menus - end ------------------------------ 


# ----------------------- list page menus - begin ------------------------------
  $query="SELECT mg.* , pmg.id AS pmg_id
          FROM {$table_prefix}menu_group AS mg
               LEFT JOIN
              {$table_prefix}page_menu_group AS pmg
              ON ( mg.id=pmg.menu_group_id
               AND pmg.page_id={$this_page_info['id']}
               AND pmg.lang = '{$this_page_info['lang']}'
               )
          WHERE mg.site_id = {$this_site_info['id']}
            AND mg.lang='{$this_page_info['lang']}'
            AND mg.page_id<>0
          ";
  #prn(checkStr($query));
  $menu_list=\e::db_getrows($query);
  #prn($menu_list);
# ----------------------- list page menus - end --------------------------------

# ----------------------- draw - begin -----------------------------------------
  $page_content='';
  foreach($menu_list as $mn)
  {
    $checked=(strlen($mn['pmg_id'])>0)?'checked':'';
    $page_content.="
     <div>
     <input type=checkbox name=\"page_menu_group[{$mn['id']}]\" value=\"{$mn['id']}\" $checked>
     {$mn['html']}
     </div>
    ";
  }
  $page_content="
  <form action=index.php method=post>
  <input type=hidden name=save value=yes>
  <input type=hidden name=action value=site/page/attach_menu>
  <input type=hidden name=page_id value='{$this_page_info['id']}'>
  <input type=hidden name=lang value='{$this_page_info['lang']}'>
  $page_content<br />
  <input type=submit>
  </form>
  ";


  $input_vars['page_header'] =
  $input_vars['page_title']  ="&quot;{$this_page_info['title']}&quot; - ".$text['Page_menu'];
  
  $input_vars['page_content']=$page_content;
# ----------------------- draw - end -------------------------------------------


//----------------------------- context menu - begin ---------------------------
  $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
  run('site/page/menu');
  $input_vars['page_menu']['page']['items'] = menu_page($this_page_info);

    $sti=$text['Site'].' "'. $this_site_info['title'].'"';
    $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);

//----------------------------- context menu - end -----------------------------

?>
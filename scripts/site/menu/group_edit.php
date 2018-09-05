<?php
/*
  Editing menu group properties
  Arguments are
    $menu_group_id  - menu group identifier, integer, mandatory
    $lang           - menu group language  , char(3), mandatory
*/

//-------------------------- check args - begin --------------------------------
  // $lang  = DbStr($input_vars['lang']);
  $lang = get_language('lang');
  if(strlen($lang)!=3)
  {
     $input_vars['page_title']  =$text['Invalid_language'];
     $input_vars['page_header'] =$text['Invalid_language'];
     $input_vars['page_content']=$text['Invalid_language'];
     return 0;
  }
  $menu_group_id = $input_vars['menu_group_id'];
  $menu_group_info=\e::db_getonerow("SELECT * FROM <<tp>>menu_group WHERE id={$menu_group_id} AND lang='{$lang}'");
  $menu_group_info['id'] = checkInt($menu_group_info['id']);
  if($menu_group_info['id']<=0)
  {
     $input_vars['page_title']  =$text['Invalid_menu_group_identifier'];
     $input_vars['page_header'] =$text['Invalid_menu_group_identifier'];
     $input_vars['page_content']=$text['Invalid_menu_group_identifier'];
     return 0;
  }
  //prn('$menu_group_info=',$menu_group_info);
//-------------------------- check args - end ----------------------------------


  run('site/menu');

//------------------- site info - begin ----------------------------------------

  $site_id = checkInt($menu_group_info['site_id']);
  $this_site_info = get_site_info($site_id);
#  $site_id = checkInt($menu_group_info['site_id']);

#  $this_site_info['id'] = checkInt($this_site_info['id']);
  if($this_site_info['id']<=0)
  {
     $input_vars['page_title']  =$text['Site_not_found'];
     $input_vars['page_header'] =$text['Site_not_found'];
     $input_vars['page_content']=$text['Site_not_found'];
     return 0;
  }
  //prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------




//------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($this_site_info['id']);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =$text['Access_denied'];
     $input_vars['page_header'] =$text['Access_denied'];
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
//------------------- get permission - end -------------------------------------


//------------------- save data - begin ----------------------------------------
  $messages='';
  if(isset($input_vars['save']) && $input_vars['save']=='yes')
  {
    $all_is_ok=true;
    $messages ='';
    if($input_vars['menu_group_lang']!=$menu_group_info['lang'])
    {
       //prn('Language changed !');
       // get all languages
          $langs = site_get_languages($this_site_info);
          $lang_list = [];
	  foreach($langs as $ln){
		$lang_list[$ln['id']]=$ln['id'];
	  }
          // prn($lang_list);

          // get existing group languages
          $existing_langs=\e::db_get_associated_array("SELECT lang,lang FROM <<tp>>menu_group WHERE id={$menu_group_info['id']}");
          //prn($existing_langs);

          // get allowed langs
          $allowed_langs=array_diff($lang_list,$existing_langs);
          //prn($allowed_langs);

       if(!in_array($input_vars['menu_group_lang'],$allowed_langs))
       {
          $input_vars['menu_group_lang']=$menu_group_info['lang'];
          $messages.="<font color=red>{$text['ERROR']} : {$text['this_language_is_not_allowed']}</font><br>\n";
          $all_is_ok=false;
       }
    }

    if(strlen($input_vars['menu_group_html'])==0)
    {
       $messages.="<font color=red>{$text['ERROR']} : {$text['Title_is_empty']}</font><br>\n";
       $all_is_ok=false;
    }

    #$input_vars['menu_group_url']=trim($input_vars['menu_group_url']);
    #if(strlen($input_vars['menu_group_url'])>0)
    #if(!is_valid_url($input_vars['menu_group_url']))
    #{
    #   $messages.="<font color=red>{$text['ERROR']} : {$text['Title_is_empty']}</font><br>\n";
    #   $all_is_ok=false;
    #}

    if($all_is_ok)
    {

       $menu_group_info['is_main'] = $input_vars['menu_group_is_main'];
       $menu_group_info['page_id'] = ($input_vars['menu_group_is_main']==0)?0:-1;
       $menu_group_info['html']    = $input_vars['menu_group_html'];
       $menu_group_info['url']     = $input_vars['menu_group_url'];
       $menu_group_info['icon']    = $input_vars['menu_group_icon'];
       $menu_group_info['code']    = $input_vars['menu_group_code'];



       /*
       if($menu_group_info['page_id']==0)
       {
         $query="UPDATE <<tp>>menu_group
                 SET  page_id=-1
                 WHERE     site_id={$this_site_info['id']}
                       AND lang = '{$menu_group_info['lang']}'";
         #prn($query);
         \e::db_execute($query);
       }
       */
       $messages.="<font color=green>{$text['Changes_saved_successfully']}</font><br>\n";
       $query="UPDATE <<tp>>menu_group
               SET  lang='{$input_vars['menu_group_lang']}'
                   ,html='".\e::db_escape($input_vars['menu_group_html'])."'
                   ,url='".\e::db_escape($input_vars['menu_group_url'])."'
                   ,icon='".\e::db_escape($input_vars['menu_group_icon'])."'
                   ,code='".\e::db_escape($input_vars['menu_group_code'])."'
                   ,page_id=".( (int)$menu_group_info['page_id'] )."
               WHERE     id={$menu_group_info['id']}
                     AND lang = '{$menu_group_info['lang']}'
       ";
       //prn($query);
       \e::db_execute($query);

       // update menu items
          $query="UPDATE <<tp>>menu_item
                  SET lang='{$input_vars['menu_group_lang']}'
                  WHERE     lang='{$menu_group_info['lang']}'
                        AND menu_group_id={$menu_group_info['id']}
                  ";
          // prn($query);
          \e::db_execute($query);
       $menu_group_info['lang']    = $input_vars['menu_group_lang'];
    }

  }
//------------------- save data - end ------------------------------------------

//------------------- draw form - begin ----------------------------------------
  $menu_group_info['is_main']=($menu_group_info['page_id']==0)?0:-1;
  #prn($menu_group_info);
  $input_vars['page_content']="
    <div><a href=\"index.php?action=site/menu/list&site_id={$site_id}\">&lt;&lt;&lt; {$text['Site_menu']}</a></div>
  <table>
    <tr><td colspan=2><b>{$messages}</b></td></tr>
    <form action=index.php method=post>
    <input type=hidden name=\"action\" value=\"site/menu/group_edit\">
    <input type=hidden name=\"save\" value=\"yes\">
    <input type=hidden name=\"menu_group_id\" value=\"{$menu_group_info['id']}\">
    <input type=hidden name=\"lang\" value=\"{$menu_group_info['lang']}\">
    <tr><td><b>{$text['Site']} : </b></td> <td>{$this_site_info['title']}</td></tr>
    <tr><td><b>{$text['Is_main_menu']} : </b></td> <td><select name=menu_group_is_main>".draw_options($menu_group_info['is_main'],Array('-1'=>$text['negative_answer'],'0'=>$text['positive_answer']))."</select></td></tr>
    <tr><td><b>{$text['Language']}<font color=red>*</font> : </b></td> <td><select name=menu_group_lang>".draw_options($menu_group_info['lang'],site_get_languages($this_site_info))."</select></td></tr>
    <tr><td><b>{$text['Title']}<font color=red>*</font></b> : </td> <td><input type=text name=menu_group_html value=\"".htmlspecialchars($menu_group_info['html'])."\"></td></tr>
    <tr><td><b>{$text['Icon']} : </b></td>   <td><input type=text name=menu_group_icon value=\"".htmlspecialchars($menu_group_info['icon'])."\"></td></tr>
    <tr><td><b>{$text['URL']} : </b></td>   <td><input type=text name=menu_group_url value=\"".htmlspecialchars($menu_group_info['url'])."\"></td></tr>
    <tr><td><b>{$text['UID']} : </b></td>   <td><input type=text name=menu_group_code value=\"".htmlspecialchars($menu_group_info['code'])."\"></td></tr>
    <tr><td></td><td><input type=submit value=\"{$text['Save']}\"></td></tr>
  </form>
  </table>
  ";
  $input_vars['page_title']  = $text['Editing_menu_group'];
  $input_vars['page_header'] = $text['Editing_menu_group'];
//------------------- draw form - end ------------------------------------------

//----------------------------- context menu - begin ---------------------------
/*
  if($this_page_info['id']>0)
  {
    $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
    run('site/page/menu');
    $input_vars['page_menu']['page']['items'] = menu_page($this_page_info);
  }
*/
  $input_vars['page_menu']['site']=Array('title'=>$text['Site_menu'],'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------
?>
<?php
/*
  Editing site/page menu

  arguments are
    $site_id - site identifier, integer, mandatory

    $page_id - page identifier, integer, optional
    $lang    - page language, char(3), optional

  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/
  run('site/menu');

//------------------- site info - begin ----------------------------------------

  $site_id = (int)$input_vars['site_id'];
  $this_site_info = get_site_info($site_id);

#  $site_id = checkInt($input_vars['site_id']);

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


//------------------- page info - begin ----------------------------------------
  if(!isset($input_vars['page_id'])) $input_vars['page_id']='';
  $page_id   = (int)($input_vars['page_id']);

  if(!isset($input_vars['lang'])) $input_vars['lang']='';
  // $lang      = DbStr($input_vars['lang']);
  $lang=$input_vars['lang'] = get_language('lang');

  $query="SELECT * FROM <<tp>>page WHERE id={$page_id} AND lang='$lang'";
  $this_page_info=\e::db_getonerow($query);
  $this_page_info['id']=checkInt($this_page_info['id']);
  //prn('$this_page_info',$this_page_info);
//------------------- page info - end ------------------------------------------

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


// --------------------- update ordering - begin -------------------------------
   if(isset($input_vars['ordering']) && is_array($input_vars['ordering'])){
       foreach($input_vars['ordering'] as $itid=>$itval){
           $query="UPDATE <<tp>>menu_item SET ordering=".( (int)$itval )." WHERE id=".( (int)$itid );
           \e::db_execute($query);
       }
   }
   if(isset($input_vars['mord']) && is_array($input_vars['mord'])){
       foreach($input_vars['mord'] as $gid=>$gval){
           $query="UPDATE <<tp>>menu_group SET ordering=".( (int)$gval )." WHERE id=".( (int)$gid );
           \e::db_execute($query);
       }
   }
// --------------------- update ordering - end ---------------------------------

// --------------------- move items - begin ------------------------------------
  if(isset($input_vars['moveup']))
  if((int)$input_vars['moveup']>0)
  {
    $query="SELECT mi.id
            FROM <<tp>>menu_item AS mi,
                 <<tp>>menu_group AS mg
            WHERE mg.site_id={$this_site_info['id']}
              AND mg.id=mi.menu_group_id
              AND mi.id=".( (int)$input_vars['moveup'] );
    $mi_id=\e::db_getonerow($query);
    #prn($query, $mi_id);
    if($mi_id)
    {
      $query="UPDATE <<tp>>menu_item
              SET ordering=IF(ordering-1>0,ordering-1,0)
              WHERE id={$mi_id['id']}";
      #prn($query);
      \e::db_execute($query);
    }
  }
  if(isset($input_vars['movedown']))
  if((int)$input_vars['movedown']>0)
  {
    $query="SELECT mi.id
            FROM <<tp>>menu_item AS mi,
                 <<tp>>menu_group AS mg
            WHERE mg.site_id={$this_site_info['id']}
              AND mg.id=mi.menu_group_id
              AND mi.id=".( (int)$input_vars['movedown'] );
    $mi_id=\e::db_getonerow($query);
    #prn($query, $mi_id);
    if($mi_id)
    {
      $query="UPDATE <<tp>>menu_item
              SET ordering=ordering+1
              WHERE id={$mi_id['id']}";
      #prn($query);
      \e::db_execute($query);
    }
  }
  unset($input_vars['movedown'],$input_vars['moveup']);
// --------------------- move items - end --------------------------------------


//------------------- add item to group - begin --------------------------------
  if(isset($input_vars['add_item_to']))
  if(strlen($input_vars['add_item_to'])>0)
  {
     // check menu group id
        $ttt=Explode(';',$input_vars['add_item_to']);
        $ttt[0]=checkInt($ttt[0]);
        $ttt[1]=\e::db_escape($ttt[1]);
        $menu_group_id=\e::db_getonerow("SELECT count(*) AS df FROM <<tp>>menu_group WHERE id={$ttt[0]} AND lang='{$ttt[1]}'");
        if($menu_group_id['df']>0)
        {


         // get new id
            $query="SELECT MAX(id) AS newid FROM  <<tp>>menu_item";
            $newid=\e::db_getonerow($query);
            $newid=$newid['newid']+1;

         // create new item
            $query="INSERT INTO <<tp>>menu_item(id,html,url,menu_group_id,lang)
                    VALUES ({$newid},'{$text['New_menu_item']}','#',{$ttt[0]},'{$ttt[1]}')";
            //prn($query);
            \e::db_execute($query);


        }
  }
  clear('add_item_to');
//------------------- add item to group - end ----------------------------------

//------------------- delete item - begin --------------------------------------
  if(isset($input_vars['delete_item_id']))
  if(strlen($input_vars['delete_item_id'])>0)
  {
     $delete_item_id=checkInt($input_vars['delete_item_id']);
     $query="SELECT *
             FROM <<tp>>menu_item AS mi,
                  <<tp>>menu_group AS mg
             WHERE     mg.site_id={$this_site_info['id']}
                   AND mg.id=mi.menu_group_id
                   AND mi.id=$delete_item_id
             ";
     if(count(\e::db_getrows($query))>0)
     {
        $query="DELETE FROM <<tp>>menu_item WHERE id=$delete_item_id";
        \e::db_execute($query);
     }
  }
  clear('delete_item_id');
//------------------- delete item - end ----------------------------------------

//------------------- delete group - begin -------------------------------------
  if(isset($input_vars['delete_group']))
  if(strlen($input_vars['delete_group'])>0)
  {
     $delete_group=explode(';',$input_vars['delete_group']);
     $delete_group[0] = checkInt($delete_group[0]);
     $delete_group[1] = \e::db_escape($delete_group[1])   ;
     $query="SELECT *
             FROM <<tp>>menu_group AS mg
             WHERE     mg.site_id={$this_site_info['id']}
                   AND mg.id={$delete_group[0]}
                   AND mg.lang='{$delete_group[1]}'
             ";
     if(count(\e::db_getrows($query))>0)
     {
        $query="DELETE FROM <<tp>>menu_item
                WHERE     menu_group_id={$delete_group[0]}
                      AND lang='{$delete_group[1]}'";
        \e::db_execute($query);
        $query="DELETE FROM <<tp>>menu_group
                WHERE     id={$delete_group[0]}
                      AND lang='{$delete_group[1]}'";
        \e::db_execute($query);
     }
  }
  clear('delete_group_id');
//------------------- delete group - end ---------------------------------------

//------------------- add group - begin ----------------------------------------
  if(isset($input_vars['add_group']))
  if(strlen($input_vars['add_group'])>0)
  {
     //prn('Adding group ...');

     // get new id
        $query="SELECT MAX(id) AS newid FROM  <<tp>>menu_group";
        $newid=\e::db_getonerow($query);
        $newid=$newid['newid']+1;

     // create new item
        $query="INSERT INTO <<tp>>menu_group(id,site_id,page_id,html)
                VALUES ({$newid},{$this_site_info['id']},-1,'{$text['New_menu_item']}')";
        //prn($query);
        \e::db_execute($query);
  }
  clear('add_group');
//------------------- add group - end ------------------------------------------

//------------------- add language - begin -------------------------------------
  if(isset($input_vars['add_lang']))
  if(strlen($input_vars['add_lang'])>0)
  {
     //prn('Adding language ...');
     $add_lang=checkInt($input_vars['add_lang']);
     // get new language
        $query="SELECT la.id AS lang
                FROM <<tp>>languages AS la LEFT JOIN <<tp>>menu_group AS mg ON(la.id=mg.lang  AND mg.id=$add_lang)
                WHERE la.is_visible=1 AND mg.lang is null
                LIMIT 0,1;";
        $newid=\e::db_getonerow($query);
        // prn($newid);

     // create new item
        $query="INSERT INTO <<tp>>menu_group(id,site_id,page_id,html, lang)
                VALUES ({$add_lang},{$this_site_info['id']},{$this_page_info['id']},'{$text['New_menu_item']}','{$newid['lang']}')";
        // prn($query);
        \e::db_execute($query);

  }
  clear('add_group');
//------------------- add language - end ---------------------------------------


//------------------- get existing menu - begin --------------------------------
  //----------------- menu groups - begin --------------------------------------
     $query = "SELECT *
               FROM <<tp>>menu_group
               WHERE     site_id={$this_site_info['id']}
               ORDER BY ordering, id, lang ";
     $tmp=\e::db_getrows($query);
     $menu_groups = Array();
     foreach($tmp as $tm)
     {
        $menu_groups[$tm['id'].':'.$tm['lang']]=$tm;
        $menu_groups[$tm['id'].':'.$tm['lang']]['items']=Array();
     }
     // prn($menu_groups);
  //----------------- menu groups - end ----------------------------------------

  //----------------- menu items - begin ---------------------------------------
     $mmm=Array();
     $mmm[]=0;
     foreach($menu_groups as $tm) $mmm[]=$tm['id'];
     $mmm=join(',',$mmm);
     $query = "SELECT *
               FROM <<tp>>menu_item
               WHERE  menu_group_id IN($mmm)
               ORDER BY menu_group_id, ordering ASC";
     $tmp=\e::db_getrows($query);
     foreach($tmp as $tm)
     {
        $menu_groups[$tm['menu_group_id'].':'.$tm['lang']]['items'][$tm['id']]=$tm;
     }
     //prn($menu_groups);
  //----------------- menu items - end -----------------------------------------

//------------------- get existing menu - end ----------------------------------

//------------------- draw - begin ---------------------------------------------
  if(!isset($this_page_info['id'])) $this_page_info['id']='';
  if(!isset($this_page_info['lang'])) $this_page_info['lang']='';

  $input_vars['page_content']="

  <style type=\"text/css\">
  <!--
   a.bt{background-color:white;border:1px solid black;padding:0 3 0 3;text-decoration:none;}
   a.bt:hover{background-color:yellow;border:1px solid black;padding:0 3 0 3;text-decoration:none;}
   .noborder{border:none;}
  -->
  </style>
  <form action=\"index.php\" method=post>
  <input type=hidden name=action value='site/menu/list'>
  <input type=hidden name=site_id value='$site_id'>
  <table border=0px cellpadding=3px cellspacing=0>
  <tr>
    <td width=1%>&nbsp;</td>
    <td><a href=\"index.php?action=site/menu/list&site_id={$this_site_info['id']}&page_id={$this_page_info['id']}&lang={$this_page_info['lang']}&add_group=yes\"><b>{$text['Add_menu_group']}</b></a></td>
  </tr>
  ";
  foreach($menu_groups as $grp)
  {
     $items="";
     if($grp['page_id']==0) $common_menu_style="font-size:140%;"; else $common_menu_style='';
     foreach($grp['items'] as $it)
     {
        $items.="
        <tr>
        <td class=noborder valign=top style='padding-top:6px;'>
          <a href=\"javascript:void(change_state('item_{$grp['id']}_{$it['id']}'))\"><img src=img/context_menu.gif border=0 align=middle></a>
          <div id=\"item_{$grp['id']}_{$it['id']}\" style=\"display:none; position:absolute; border:solid 1px blue; padding:4px; text-align:left; background-color:#e0e0e0;\">
            <a href=\"index.php?action=site/menu/item_edit&menu_item_id={$it['id']}\">{$text['Edit']}</a><br />
            <br><br>
            <a href=\"index.php?action=site/menu/list&site_id={$this_site_info['id']}&page_id={$this_page_info['id']}&lang={$it['lang']}&delete_item_id={$it['id']}\" onclick=\"return confirm('{$text['Are_you_sure']}?')\">{$text['Delete']}</a><br />
          </div>
        </td>
        <td class=noborder valign=top><nobr>
        <a href=\"index.php?action=site/menu/list&site_id={$this_site_info['id']}&moveup={$it['id']}\" class=bt>^</a>
        <a href=\"index.php?action=site/menu/list&site_id={$this_site_info['id']}&movedown={$it['id']}\" class=bt>v</a>
        <input type=text name='ordering[{$it['id']}]' value='{$it['ordering']}' style='width:50px;border:1px solid silver;'>
        </nobr></td>
        <td class=noborder valign=top>
        ".( (strlen($it['url'])>1)?"<a href=\"{$it['url']}\"> ".htmlspecialchars($it['html'])."</a>":"{$it['ordering']} ".htmlspecialchars($it['html'])."")."
        </td>
        </tr>
        ";
     }
     $input_vars['page_content'].="
     <tr>
     <td valign=top>
       <a href=\"javascript:void(change_state('menu_{$grp['id']}_{$grp['lang']}'))\"><img src=img/context_menu.gif border=0></a>
       <div id=\"menu_{$grp['id']}_{$grp['lang']}\" style=\"display:none; position:absolute; border:solid 1px blue; padding:4px; text-align:left; background-color:#e0e0e0;\">
       <nobr><a href=\"index.php?action=site/menu/group_edit&menu_group_id={$grp['id']}&lang={$grp['lang']}&site_id={$grp['site_id']}&page_id={$grp['page_id']}\">{$text['Edit']}</a></nobr><br/>
       <nobr><a href=\"index.php?action=site/menu/list&site_id={$this_site_info['id']}&page_id={$this_page_info['id']}&lang={$grp['lang']}&add_item_to={$grp['id']};{$grp['lang']}\">{$text['Add_item']}</a></nobr><br/>
       <nobr><a href=\"index.php?action=site/menu/list&site_id={$this_site_info['id']}&page_id={$this_page_info['id']}&lang={$this_page_info['lang']}&add_lang={$grp['id']}\">{$text['Add_translation']}</a></nobr><br/>
       <br><br>
       <nobr><a href=\"index.php?action=site/menu/list&site_id={$this_site_info['id']}&page_id={$this_page_info['id']}&lang={$grp['lang']}&delete_group={$grp['id']};{$grp['lang']}\" onclick=\"return confirm('{$text['Are_you_sure']}?')\">{$text['Delete']}</a></nobr><br/>
       </div>
     </td>
     <td valign=center>
     <b><a class=bt href=\"javascript:void(chast('grp_{$grp['id']}_{$grp['lang']}'))\" style=\"$common_menu_style\">&nbsp;+&nbsp;</a>
     <input type=text name='mord[{$grp['id']}]' value='{$grp['ordering']}' style='width:50px;border:1px solid silver;'>
     {$grp['id']} -- {$grp['lang']} -- {$grp['html']}</b>
     <div id=\"grp_{$grp['id']}_{$grp['lang']}\" style=\"display:block;\">
     <table>$items</table>
     </div>
     </td>
     </tr>

     ";
  }
  $input_vars['page_content'].="\n</table>\n<input type=submit></form>";

//------------------- draw - end -----------------------------------------------

$input_vars['page_title']  = $this_site_info['title'] .' - '. $text['Edit_navigation_menu'];
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['Edit_navigation_menu'];
//$input_vars['page_content']=$text['Access_denied'];


//----------------------------- context menu - begin ---------------------------
  if($this_page_info['id']>0)
  {
    $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
    run('site/page/menu');
    $input_vars['page_menu']['page']['items'] = menu_page($this_page_info,$this_site_info);
  }
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());


  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------

?>
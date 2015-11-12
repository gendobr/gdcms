<?php
/*
  Edit site map
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
  run('site/menu');
//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);

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

//------------------- moving - begin -------------------------------------------
  //---------------------- move up - begin -------------------------------------
    if(isset($input_vars['move_up']))
    {
    $input_vars['move_up']=checkInt($input_vars['move_up']);
    if($input_vars['move_up']>0)
    {
       $query="UPDATE {$table_prefix}page 
               SET map_position=map_position-1 
               WHERE     id={$input_vars['move_up']} 
                     AND site_id={$this_site_info['id']}
                     AND map_position>0";
       db_execute($query);
       clear('move_up');
    }
    }
  //---------------------- move up - end ---------------------------------------
  //---------------------- move down - begin -----------------------------------
    if(isset($input_vars['move_down']))
    {
    $input_vars['move_down']=checkInt($input_vars['move_down']);
    if($input_vars['move_down']>0)
    {
       $query="UPDATE {$table_prefix}page 
               SET map_position=map_position+1 
               WHERE     id={$input_vars['move_down']} 
                     AND site_id={$this_site_info['id']}";
       db_execute($query);
       clear('move_down');
    }
    }
  //---------------------- move down - end -------------------------------------
  //---------------------- move left - begin -----------------------------------
    if(isset($input_vars['move_left']))
    {
    $input_vars['move_left']=checkInt($input_vars['move_left']);
    if($input_vars['move_left']>0)
    {
       $query="UPDATE {$table_prefix}page 
               SET map_indent=map_indent-1 
               WHERE     id={$input_vars['move_left']} 
                     AND site_id={$this_site_info['id']}
                     AND map_indent>0";
       db_execute($query);
       clear('move_left');
    }
    }
  //---------------------- move left - end -------------------------------------
  //---------------------- move right - begin ----------------------------------
    if(isset($input_vars['move_right']))
    {
    $input_vars['move_right']=checkInt($input_vars['move_right']);
    if($input_vars['move_right']>0)
    {
       $query="UPDATE {$table_prefix}page 
               SET map_indent=map_indent+1 
               WHERE     id={$input_vars['move_right']} 
                     AND site_id={$this_site_info['id']}";
       //prn($query);
       db_execute($query);
       clear('move_right');
    }
    }
  //---------------------- move right - end ------------------------------------
  
  // map_position
  if(isset($input_vars['map_position']))
  if(is_array($input_vars['map_position']))
  {
    foreach($input_vars['map_position'] as $key=>$val)
    {
       $query="UPDATE {$table_prefix}page 
               SET map_position='$val' 
               WHERE     id='$key' 
                     AND site_id={$this_site_info['id']}";
       //prn($query);
       db_execute($query);
    }
  }
//------------------- moving - end ---------------------------------------------



//------------------- draw map - begin -----------------------------------------
  $input_vars['page_content']="
      <style type=\"text/css\">
      <!-- 
      .menu_block
      {
        position:absolute;
        border:solid 1px blue;
        background-color: #e0e0e0;
        padding:5px;
        text-align:left;
      }
       
      -->
      </style>
      <script type=\"text/javascript\">
      <!--
        var map_prev_menu;
        var map_href;
        function map_change_state(cid)
        {
            var lay=document.getElementById(cid);
            if (lay.style.display==\"none\")
            {
               if(map_prev_menu) map_prev_menu.style.display=\"none\";
               lay.style.display=\"block\";
               map_prev_menu=lay;
            }
            else
            {
               lay.style.display=\"none\";
               map_prev_menu=null;
            }
            map_href=true;
        }
        
        function map_hide_menu()
        {
          if(map_prev_menu && !map_href) map_prev_menu.style.display=\"none\";
          map_href=false;
        }
        document.onclick=map_hide_menu;
      // -->
      </script>
      <form action={$_SERVER['PHP_SELF']}>
      <input type=hidden name=action value=\"site/map/edit\">
      <input type=hidden name=site_id value=\"{$site_id}\">
      <table border=1px cellpadding=4px>
      <tr>
        <td align=center valign=top bgcolor=#e0e0ff></td>
        <td align=center valign=top bgcolor=#e0e0e0 colspan=2><b>{$text['Action']}</b></td>
        <td align=left valign=top bgcolor=#ffe0e0><b>#</b></td>
        <td align=left valign=top bgcolor=#ffe0e0><b>{$text['Language']}</b></td>
        <td align=left valign=top bgcolor=#ffe0e0><b>{$text['Page_Title']}</b></td>
      </tr>
  ";
  $query="SELECT *
          FROM {$table_prefix}page
          WHERE site_id={$this_site_info['id']}
          ORDER BY map_position, id, lang";
  $page_list=db_getrows($query);
  ///prn($page_list);
  
  run('site/page/menu');
  //menu_page($page_info)
  $prev_page_id=0;
  foreach($page_list as $row_id=>$page_info)
  {
     
     //if($page_info['id']!=$prev_page_id)
     //{
     //   $input_vars['page_content'].="<tr><td colspan=6></td></tr>";
     //   $prev_page_id=$page_info['id'];
     // }

     $input_vars['page_content'].="<tr>
     <td align=center valign=top bgcolor=#e0e0ff><input type=text name=\"map_position[{$page_info['id']}]\" value=\"{$page_info['map_position']}\" size=3 style='text-align:right;'></td>
     <td align=center valign=top bgcolor=#e0e0e0>\n";

     //--------------------------- context menu - begin ------------------------
       $context_menu=menu_page($page_info);
       $input_vars['page_content'].="<img src=\"img/context_menu.gif\" border=0px alt=\"\" onclick=\"map_change_state('cm{$row_id}')\">
       <div class=menu_block style='display:none;' id='cm{$row_id}'>";
       foreach($context_menu as $menu_item)
       {
         $input_vars['page_content'].="<nobr><a href=\"{$menu_item['URL']}\" {$menu_item['attributes']}>{$menu_item['innerHTML']}</a></nobr><br/>\n";
       }
       $input_vars['page_content'].="</div>";
     //--------------------------- context menu - end --------------------------

       $input_vars['page_content'].="
       </td>
       <td align=left valign=top bgcolor=#ffe0e0><!-- 
       --><a href='index.php?action=site/map/edit&site_id={$this_site_info['id']}&move_left={$page_info['id']}' title='{$text['Move_left']}'><img src='img/left_arrow.gif'></a><!-- 
       --><a href='index.php?action=site/map/edit&site_id={$this_site_info['id']}&move_right={$page_info['id']}' title='{$text['Move_right']}'><img src='img/right_arrow.gif'></a><!-- 
       --><a href='index.php?action=site/map/edit&site_id={$this_site_info['id']}&move_up={$page_info['id']}' title='{$text['Move_up']}'><img src='img/up_arrow.gif'></a><!-- 
       --><a href='index.php?action=site/map/edit&site_id={$this_site_info['id']}&move_down={$page_info['id']}' title='{$text['Move_down']}'><img src='img/down_arrow.gif'></a>
       </td>
       <td align=left valign=top bgcolor=#e0e0e0>{$page_info['id']}</td>
       <td align=left valign=top bgcolor=#e0ffe0>{$page_info['lang']}</td>
       <td align=left valign=top bgcolor=#e0e0ff style='padding-left:".checkInt(15*$page_info['map_indent'])."px;'>{$page_info['title']}</td>
       </tr>
       ";
  }
  $input_vars['page_content'].="\n\n
  <tr><td><input type=submit value='OK'></td><td colspan=5 style='border:none;'></td></tr>
  </table>
  </form>
  \n";
//------------------- draw map - end -------------------------------------------


$input_vars['page_title']  = $this_site_info['title'] .' - '. $text['Site_map'];
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['Site_map'];
//--------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
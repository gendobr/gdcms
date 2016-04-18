<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$debug=false;
run('site/menu');
//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);

  // prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = text('Site_not_found');
     $input_vars['page_header']  = text('Site_not_found');
     $input_vars['page_content'] = text('Site_not_found');
     return 0;
  }
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
$user_cense_level=get_level($site_id);
if($user_cense_level==0)
{
   $input_vars['page_title']  = text('Access_denied');
   $input_vars['page_header'] = text('Access_denied');
   $input_vars['page_content']= text('Access_denied');
   return 0;
}
//------------------- check permission - end -----------------------------------



// ------------------ delete - begin -------------------------------------------
   if(   isset($input_vars['ec_item_comment_delete'])
      && isset($input_vars['ec_item_comment'])
      && is_array($input_vars['ec_item_comment'])
   )
   {
      $query=Array();
      foreach( $input_vars['ec_item_comment'] as $ec_item_comment_id ) $query[]=(int)$ec_item_comment_id;
      if(count($query)>0)
      {
         $query="DELETE FROM {$table_prefix}ec_item_comment WHERE ec_item_comment_id IN(".join(',',$query).")";
         // prn($query);
         \e::db_execute($query);
      }
   }

// ------------------ delete - end ---------------------------------------------



//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;
  $re->exclude='/^ec_item_comment/';


  $re->from="
       {$table_prefix}ec_item_comment AS ec_item_comment
       INNER JOIN
       {$table_prefix}ec_item AS ec_item
       ON (     ec_item.ec_item_id=ec_item_comment.ec_item_id
            AND ec_item.ec_item_lang=ec_item_comment.ec_item_lang)
      ";
  $re->add_where(" ec_item_comment.site_id={$site_id} ");
  $re->add_where(" ec_item.site_id={$site_id} ");

  $re->add_field( $field='ec_item.ec_item_id'
                 ,$alias='ec_item_id'
                 ,$type ='id:hidden=no'
                 ,$label='#'
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item_comment.ec_item_comment_id'
                 ,$alias='ec_item_comment_id'
                 ,$type ='id:hidden=no'
                 ,$label='Comment #'
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='site_id'
                 ,$_group_operation=false);

  //---------------- list of languages - begin ---------------------------------
    $LL = join('&',\e::db_get_associated_array("SELECT ec_item_lang,CONCAT(ec_item_lang,'=',ec_item_lang) FROM {$table_prefix}ec_item WHERE site_id={$site_id}"));
    $re->add_field( $field='ec_item.ec_item_lang'
                   ,$alias='ec_item_lang'
                   ,$type ='enum:'.$LL
                   ,$label=text('Language')
                   ,$_group_operation=false);
  //---------------- list of languages - end -----------------------------------

  $re->add_field( $field='ec_item.ec_item_title'
                 ,$alias='ec_item_title'
                 ,$type ='string'
                 ,$label=text('ec_item_title')
                 ,$_group_operation=false);


  $re->add_field( $field='ec_item_comment.ec_item_comment_sender_name'
                 ,$alias='ec_item_comment_sender_name'
                 ,$type ='string'
                 ,$label=text('Comment_sender')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item_comment.ec_item_comment_body'
                 ,$alias='ec_item_comment_body'
                 ,$type ='string'
                 ,$label=text('Comment_content')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item_comment.ec_item_comment_datetime'
                 ,$alias='ec_item_comment_datetime'
                 ,$type ='datetime'
                 ,$label=text('Date')
                 ,$_group_operation=false);


  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------


# ---------------------- draw list - begin -------------------------------------
  $tor="
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
      a.row{display:block;padding:4px;text-decoration:none;}
      a.row:hover{background-color:yellow;}
      table.noborder td{border:none;}
      .b-t{border-top:1px dotted silver;}
      .b-l{border-left:1px dotted silver;}
      .b-r{border-right:1px dotted silver;}
      .b-b{border-bottom:1px dotted silver;}
      -->
      </style>
      <script type=\"text/javascript\">
      <!--
        var report_prev_menu;
        var report_href;
        function report_change_state(cid)
        {
            var lay=document.getElementById(cid);
            if (lay.style.display==\"none\")
            {
               if(report_prev_menu) report_prev_menu.style.display=\"none\";
               lay.style.display=\"block\";
               report_prev_menu=lay;
            }
            else
            {
               lay.style.display=\"none\";
               report_prev_menu=null;
            }
            report_href=true;
        }

        function report_hide_menu()
        {
          if(report_prev_menu && !report_href) report_prev_menu.style.display=\"none\";
          report_href=false;
        }
        document.onclick=report_hide_menu;
      // -->
      </script>
      <script type=\"text/javascript\" src=\"scripts/lib/popupcalend/calendar.js\"></script>
      ";
     // ------------------------- filter -- begin ------------------------------


        $tor.="
        <form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
           {$response['hidden_fields']}
           <table class=noborder>
        ";



        # $alias='ec_item_comment_id'
        $fld=$response['fields']['ec_item_comment_id'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td align=left>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
               <input type=text
                      name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                      value=\"{$value}\"
                      size=3>
               </td>
        ";
        # $alias='ec_item_id'
        $fld=$response['fields']['ec_item_id'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <td align=right>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
               <input type=text
                      name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                      value=\"{$value}\"
                      size=3>
               </td>
        ";
        # $alias='ec_item_lang'
        $fld=$response['fields']['ec_item_lang'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <td align=right>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                 <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\">
                     <option value=''> </option>
                      {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                 </select>
               </td>
        </tr>
        ";



        # $alias='ec_item_title'
        $fld=$response['fields']['ec_item_title'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top colspan=5>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>
               </tr>
        ";

        # $alias='ec_item_comment_sender_name'
        $fld=$response['fields']['ec_item_comment_sender_name'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top colspan=5>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>
               </tr>
        ";



        # $alias='ec_item_comment_body'
        $fld=$response['fields']['ec_item_comment_body'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top colspan=5>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>
               </tr>
        ";

         # $alias='ec_item_comment_datetime'
           $fld=$response['fields']['ec_item_comment_datetime'];
           //prn($fld);
           $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
           $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top colspan=2>
                        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                            value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                             size=17>
                        <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_min_name']})\"></nobr>
               </td><td align=center>
                        &minus;
               </td><td colspan=2>
                        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                            value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                             size=17>
                        <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_max_name']})\"></nobr>

               </td>
               </tr>
           ";



         $tor.="
              <tr><td colspan=8 valign=top align=right><input type=submit name=submit value=\"{$text['Search']}\"></td></tr>\n

         </table>
         </form>";
        //echo $tor;
     // ------------------------- filter -- end --------------------------------


     $tor.="
     <form action=index.php method=post>
     ".hidden_form_elements('^ec_item|^submit')."
     <table cellspacing=0px>";


     // ------------------------- header -- begin ------------------------------
     $tor.="
     <tr>
         <th align=center valign=top></th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_comment_id']['label']}</b><br>
               <nobr>
               <a href=\"{$response['fields']['ec_item_comment_id']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_comment_id']['url_order_desc']}\">&Lambda;</a>
               </nobr>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_lang']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_lang']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_lang']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_title']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_title']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_title']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_comment_datetime']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_comment_datetime']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_comment_datetime']['url_order_desc']}\">&Lambda;</a>
         </th>

         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_comment_sender_name']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_comment_sender_name']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_comment_sender_name']['url_order_desc']}\">&Lambda;</a>
         </th>
     </tr>\n";
     // ------------------------- header -- end --------------------------------
     // ------------------------- rows -- begin --------------------------------
     //prn($response['fields']['ec_item_currency']['options']);
     foreach($response['rows'] as $row_id=>$row)
     {
       $tor.="<tr class=row>
               <td align=center valign=top rowspan=2>
                <input type=checkbox name=ec_item_comment[{$row['ec_item_comment_id']}] value=\"{$row['ec_item_comment_id']}\">
               </td>
           <td align=left valign=top rowspan=2>&nbsp;{$row['ec_item_comment_id']}</td>
           <td align=left valign=top rowspan=2>&nbsp;{$row['ec_item_lang']}</td>
           <td align=left valign=top class='b-b b-r'><a href='index.php?action=ec/item/edit&site_id={$row['site_id']}&ec_item_id={$row['ec_item_id']}&ec_item_lang={$row['ec_item_lang']}'>{$row['ec_item_title']}</a></td>

           <td align=left valign=top class='b-b b-r b-l'>&nbsp;{$row['ec_item_comment_datetime']}</td>
           <td align=left valign=top class='b-b b-l'>&nbsp;{$row['ec_item_comment_sender_name']}</td>
       </tr>
       <tr>
          <td colspan=3 class='b-t' style='color:gray;'>{$row['ec_item_comment_body']}<td>
       </tr>
      ";


     }
     $tor.="<tr>
             <td colspan=\"6\" align=right style='border:none;'>
               <input type=submit name=ec_item_comment_delete value=\"".text('Delete')."\" style='width:30%;'>
            </td>
            </tr>";
     // ------------------------- rows -- end ----------------------------------
     // ------------------------- paging - begin -------------------------------
        $tor.="<tr>\n<td colspan=\"6\" align=left style='border:none;'>
             {$response['total_rows']} {$text['rows found']} :::::::
             {$text['Pages']} :
             ";

        foreach($response['pages'] as $pg)
          $tor.="<a href=\"{$pg['page_url']}\">{$pg['page_id']}</a>&nbsp;";

        $tor.='<br>';
        if(strlen($response['backward'])>0) $tor.="&nbsp;<a href=\"{$response['backward']}\">{$text['Previous_page']}</a>&nbsp;";
        if(strlen($response['forward'])>0)  $tor.="&nbsp;<a href=\"{$response['forward']}\">{$text['Next page']}</a>&nbsp;";


        $tor.="\n</td>\n</tr>\n\n";
     // ------------------------- paging - end ---------------------------------


     $tor.="
      </table>
      </form>
     ";
$input_vars['page_title']  = $this_site_info['title'] .' - '. text('EC_item_manage_comments');
$input_vars['page_header'] = $this_site_info['title'] .' - '. text('EC_item_manage_comments');
$input_vars['page_content']=$tor;



//--------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>

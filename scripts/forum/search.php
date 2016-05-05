<?php
/*
  List of messages for a selected site, selected forum and selected thread 
  Arguments are
  $site_id - site identifier, integer, mandatory
  $forum_id - forum identifier, integer, mandatory
  $thread_id - thread identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
  run('forum/menu');

//------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info =\e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id} AND is_forum_enabled=1");
  //prn('$this_site_info=',$this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- get site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------


//-------------------- delete message - begin ----------------------------------
  $delete_message_id=isset($input_vars['delete_message_id'])?checkInt($input_vars['delete_message_id']):0;
  if($delete_message_id>0)
  {
     $del_msg_info=\e::db_getonerow("SELECT  * FROM <<tp>>forum_msg WHERE  id={$delete_message_id} AND site_id={$site_id}");
     // prn($del_msg_info);
     if($del_msg_info['is_first_msg']==1)
     {
       $query="DELETE FROM <<tp>>forum_thread WHERE id={$del_msg_info['thread_id']}";
       \e::db_execute($query);
       $query="DELETE FROM <<tp>>forum_msg WHERE thread_id={$del_msg_info['thread_id']} AND site_id={$site_id}";
       \e::db_execute($query);
     }
     else
     {
       $query="DELETE FROM <<tp>>forum_msg WHERE id={$delete_message_id} AND site_id={$site_id}";
       //prn($query);
       \e::db_execute($query);
     }
  }
  clear('delete_message_id');
//-------------------- delete message - end ------------------------------------


//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");

  // -



  class report_generator extends Report
{
  function draw_default_list($response)
  {
      global $text;
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
      .report_field_label{color:silver;}
      table.report_rows td{border:none;}
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

       <form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
       {$response['hidden_fields']}\n";
     /*
     // ------------------------- header -- begin ------------------------------
     $tor.="<tr><th align=center valign=top>Action</th>\n";
     foreach($this->field as $fld)
     {
         if($fld['options']['hidden']=='yes') continue;
         $tor.="
              <th align=center valign=top>
               <b>{$response['fields'][$fld['alias']]['label']}</b><br>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_asc']}\"><img src=img/down_arrow.gif alt='{$text['asc']}' border=0></a>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_desc']}\"><img src=img/up_arrow.gif alt='{$text['desc']}' border=0></a>
               </th>
               \n";
     }
     $tor.="</tr>\n";
     // ------------------------- header -- end --------------------------------
     */
     // ------------------------- filter -- begin ------------------------------
     $tor.="<table border=1 width=100%>";
     foreach($this->field as $fld)
     {
         if(isset($fld['options']['hidden']) && $fld['options']['hidden']=='yes') continue;
         $tor.="
             <tr>
              <td align=right valign=top>
               <nobr>
               <b>{$response['fields'][$fld['alias']]['label']}</b>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_asc']}\">V</a>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_desc']}\">&Lambda;</a>
               </nobr>
               </td>
               \n";

         switch($fld['type'])
         {
            case 'id':
              $tor.="
                    <td align=left valign=top>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'')."\"
                                     size=3>
                    </td>
              ";
            break;

            case 'string':
              $tor.="
                    <td align=left valign=top>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\">
                    </td>
              ";
            break;

            case 'integer':
            case 'float':
              $tor.="
                    <td align=left valign=top>
                    <nobr>
                    &ge;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_value']}\"
                                         size=3>
                    &le;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_value']}\"
                                         size=3>
                    </nobr>
                    </td>
              ";
            break;
            case 'enum':
              $tor.="
                    <td align=left valign=top>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
                    </td>
              ";
            break;
            case 'unix_timestamp':
            case 'datetime':
              $tor.="
                    <td align=left>
                    <nobr>&ge;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                        value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                         size=17>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_min_name']})\">
                    &le;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                        value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                         size=17>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_max_name']})\"></nobr>
                    </td>
              ";
            break;
         }
     }
     $tor.="</tr>
            <tr><td></td><td valign=top align=left><input type=submit name=submit value=\"{$text['Search']}\"></td>
            </table>";
     // ------------------------- filter -- end --------------------------------

     // ------------------------- rows -- begin --------------------------------
     $tor.="<table border=1px width=100% class=report_rows>";
     $fld_cnt=count($this->field);
     foreach($response['rows'] as $row_id=>$row)
     {
       $tor.="<tr>\n";

       //--------------------------- context menu - begin ----------------------
       $tor.="<td align=center valign=top width=20px style='padding:6px;'>\n";
       if(is_array($row['context_menu']))
       {
         $tor.="<img src=\"img/context_menu.gif\" border=1px alt=\"\" onclick=\"report_change_state('cm{$row_id}')\">
         <div class=menu_block style='display:none;' id='cm{$row_id}'>";
         foreach($row['context_menu'] as $menu_item)
         {
           $tor.="<nobr><a href=\"{$menu_item['URL']}\" {$menu_item['attributes']}>{$menu_item['innerHTML']}</a></nobr><br/>\n";
         }
         $tor.="</div>";
       }
       $tor.="</td>\n";
       //--------------------------- context menu - end ------------------------

       $tor.="<td align=center valign=top><table border=0 width=100%>\n";
       foreach($this->field as $fld)
       {
           if(isset($fld['options']['hidden']) && $fld['options']['hidden']=='yes') continue;
           $tor.="<tr><td align=left valign=top width=150px class=report_field_label><nobr>{$response['fields'][$fld['alias']]['label']}</nobr></td><td align=left valign=top>{$row[$fld['alias']]}</td>\n</tr>\n";
       }
       $tor.="</table><br><br></td>\n";
       $tor.="\n</tr>\n\n";
     }

     // ------------------------- rows -- end ----------------------------------

     // ------------------------- paging - begin -------------------------------
        $tor.="<tr>\n<td colspan=\"2\" align=center>\n{$text['Pages']} :\n";

        foreach($response['pages'] as $pg)
          $tor.="<a href=\"{$pg['page_url']}\">{$pg['page_id']}</a>&nbsp;";

        $tor.='<br>';
        if(strlen($response['backward'])>0) $tor.="&nbsp;<a href=\"{$response['backward']}\">{$text['Previous_page']}</a>&nbsp;";
        if(strlen($response['forward'])>0)  $tor.="&nbsp;<a href=\"{$response['forward']}\">{$text['Next page']}</a>&nbsp;";


        $tor.="\n</td>\n</tr>\n\n";
     // ------------------------- paging - end ---------------------------------

     // ------------------------- list is empty - begin ------------------------
        $tor.="<tr><td colspan=\"{$fld_cnt}\" align=center>{$response['total_rows']} {$text['rows found']}</td></tr>\n\n";
     // ------------------------- list is empty - end --------------------------

     $tor.="</form>\n</table>\n";
     return $tor;
  }
}




  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  $re->from="<<tp>>forum_msg";
  $re->add_where(" site_id   = $site_id  ");
//  $re->add_where(" forum_id  = $forum_id ");
//  $re->add_where(" thread_id = $thread_id");

  $re->add_field( $field='is_visible'
                 ,$alias='is_visible'
                 ,$type ="enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 ,$label=text('is_visible')
                 ,$_group_operation=false);
  $re->add_field( $field='id'
                 ,$alias='id'
                 ,$type ='id'
                 ,$label=$text['Message_id']
                 ,$_group_operation=false);

  $re->add_field( $field='site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='site_id'
                 ,$_group_operation=false);

  $re->add_field( $field='forum_id'
                 ,$alias='forum_id'
                 ,$type ='id:hidden=yes'
                 ,$label='forum_id'
                 ,$_group_operation=false);

  function getenum($it){return $it['id'].'='.rawurlencode($it['name']);}
  $list_forum_names=join('&',array_map('getenum',\e::db_getrows("SELECT id,name FROM <<tp>>forum_list WHERE site_id={$this_site_info['id']}")));
  $re->add_field( $field='forum_id'
                 ,$alias='forum_name'
                 ,$type ="enum:1=".$list_forum_names
                 ,$label=$text['Forum']
                 ,$_group_operation=false);
                 
                 
  $re->add_field( $field='thread_id'
                 ,$alias='thread_id'
                 ,$type ='id:hidden=yes'
                 ,$label='thread_id'
                 ,$_group_operation=false);

  $list_thread_names=join('&',array_map('getenum',\e::db_getrows("SELECT id,subject as name FROM <<tp>>forum_thread WHERE site_id={$this_site_info['id']}")));
  $re->add_field( $field='thread_id'
                 ,$alias='thread_name'
                 ,$type ="enum:1=".$list_thread_names
                 ,$label=$text['Thread']
                 ,$_group_operation=false);

                 
  $re->add_field( $field='is_first_msg'
                 ,$alias='is_first_msg'
                 ,$type ="enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 ,$label=$text['is_first_msg']
                 ,$_group_operation=false);



  $re->add_field( $field='name'
                 ,$alias='name'
                 ,$type ='string'
                 ,$label=$text['Creator_name']
                 ,$_group_operation=false);


//  $re->add_field( $field='email'
//                 ,$alias='email'
//                 ,$type ='string'
//                 ,$label=$text['Creator_email']
//                 ,$_group_operation=false);
//
//  $re->add_field( $field='www'
//                 ,$alias='www'
//                 ,$type ='string'
//                 ,$label=$text['Creator_site_URL']
//                 ,$_group_operation=false);

  $re->add_field( $field='data'
                 ,$alias='data'
                 ,$type ='datetime'
                 ,$label=$text['Date_created']
                 ,$_group_operation=false);
//
//  $re->add_field( $field='subject'
//                 ,$alias='subject'
//                 ,$type ='string'
//                 ,$label=$text['Subject']
//                 ,$_group_operation=false);

  $re->add_field( $field='msg'
                 ,$alias='msg'
                 ,$type ='string'
                 ,$label=$text['Message_body']
                 ,$_group_operation=false);

  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------
$input_vars['page_title']  = $text['Search_messages'];
$input_vars['page_header'] = $text['Search_messages'];

  //--------------------------- context menu -- begin ----------------------------
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ----------------------
        $response['rows'][$i]['context_menu']=menu_msg($response['rows'][$i]);
        //$response['rows'][$i]['last_message_data']= "<nobr>{$response['rows'][$i]['last_message_data']}</nobr>";
      //--------------------------- context menu -- end ------------------------
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']="
 <p>
   <table>
   <tr>
     <td align=right><font size=+1><b>{$text['Site']} </b> :</font></td>
     <td><font size=+1>{$this_site_info['title']}</font></td>
   </tr>
   </table>
 </p>
"
.$re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------


  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  run('site/menu');
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
  
//--------------------------- context menu -- end ------------------------------

?>
<?php
/*
  List of news for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
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


/*
// ------------------ update - begin -------------------------------------------
   if(isset($input_vars['ec_item']) && is_array($input_vars['ec_item']))
   {
      //prn('updating...',$input_vars['ec_item']);
      foreach( $input_vars['ec_item'] as $ec_item_id=>$val )
      {
         $query=Array();
         foreach($val as $fl=>$va) $query[]="$fl='".DbStr($va)."'";
         $query="UPDATE {$table_prefix}ec_item SET ".join(',',$query)." WHERE ec_item_id=$ec_item_id LIMIT 1";
         //prn($query);
         \e::db_execute($query);
      }
   }
// ------------------ update - end ---------------------------------------------
*/
//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;
  $re->exclude='^ec_item';


  $re->from="{$table_prefix}ec_producer AS ec_producer";
  $re->add_where(" ec_producer.site_id={$site_id} ");

  $re->add_field( $field='ec_producer.ec_producer_id'
                 ,$alias='ec_producer_id'
                 ,$type ='id:hidden=no'
                 ,$label='#'
                 ,$_group_operation=false);

  $re->add_field( $field='ec_producer.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='site_id'
                 ,$_group_operation=false);

  $re->add_field( $field='ec_producer.ec_producer_title'
                 ,$alias='ec_producer_title'
                 ,$type ='string'
                 ,$label=text('ec_producer_title')
                 ,$_group_operation=false);


  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = $this_site_info['title'] .' - '. text('EC_producers');
$input_vars['page_header'] = $this_site_info['title'] .' - '. text('EC_producers');

  //--------------------------- context menu -- begin ----------------------------
    run('ec/producer/functions');
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_ec_producer($response['rows'][$i]);
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------

// prn($response);

























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

        # $alias='ec_item_id'
        $fld=$response['fields']['ec_producer_id'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
               <input type=text
                      name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                      value=\"{$value}\"
                      size=3>
               </td>
               </tr>
        ";

        # $alias='ec_item_title'
        $fld=$response['fields']['ec_producer_title'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top colspan=7>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
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
               <b>{$response['fields']['ec_producer_id']['label']}</b><br>
               <nobr>
               <a href=\"{$response['fields']['ec_producer_id']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_producer_id']['url_order_desc']}\">&Lambda;</a>
               </nobr>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_producer_title']['label']}</b><br>
               <a href=\"{$response['fields']['ec_producer_title']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_producer_title']['url_order_desc']}\">&Lambda;</a>
         </th>
     </tr>\n";
     // ------------------------- header -- end --------------------------------

     // ------------------------- rows -- begin --------------------------------
     //prn($response['fields']['ec_item_currency']['options']);
     foreach($response['rows'] as $row_id=>$row)
     {
       $tor.="<tr class=row>\n<td align=center valign=top>\n";

       //--------------------------- context menu - begin ----------------------
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
       //--------------------------- context menu - end ------------------------
       //prn($row);
       $tor.="
       </td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_producer_id']}</a></td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_producer_title']}</a></td>
       </tr>\n\n";
     }

     // ------------------------- rows -- end ----------------------------------


     // ------------------------- paging - begin -------------------------------
        $tor.="<tr>\n<td colspan=\"3\" align=left style='border:none;'>
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


     $tor.="\n</table>\n

     </form>";
     //return $tor;



$input_vars['page_content']= $tor;//$re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------

  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
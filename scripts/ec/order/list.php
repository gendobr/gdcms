<?php
/* 
 * list of the orders
 * Argument is $site_id - site identifier
 * (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
$debug=false;
run('site/menu');
run('ec/order/functions');
run('ec/item/functions');

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

# ------------------- delete order - begin -------------------------------------
  if(isset($input_vars['ec_order_delete']) && $input_vars['ec_order_delete']>0)
  {
     ec_order_delete((int)$input_vars['ec_order_delete']);
  }
# ------------------- delete order - end ---------------------------------------

// ------------------ update - begin -------------------------------------------
   if(isset($input_vars['ec_order']) && is_array($input_vars['ec_order']))
   {
      //prn('updating...',$input_vars['ec_item']);
      foreach( $input_vars['ec_order'] as $ec_order_id=>$val )
      {
         # --------------------- update record - begin -------------------------
         $tmp=Array();
         foreach($val as $fl=>$va) $tmp[]="$fl='".\e::db_escape($va)."'";
         $query="UPDATE <<tp>>ec_order SET ".join(',',$tmp)." WHERE ec_order_id=$ec_order_id LIMIT 1";
         // prn($query);
         \e::db_execute($query);
         $affected_rows=mysql_affected_rows();
         //prn($query,$affected_rows);

         if($affected_rows==0) continue;
         # --------------------- update record - end ---------------------------

         # update order hash
           ec_order_sha($ec_order_id);

         # --------------------- update order history - begin ------------------
           update_ec_order_history(
                   text('Ec_order_status_changed'),
                   stripslashes(join('<br/>',$tmp)),
                   'order_status_changed',
                   $ec_order_id,
                   0,
                   $site_id,
                   $_SESSION['user_info']['id']);
         # --------------------- update order history - end --------------------
      }
   }
// ------------------ update - end ---------------------------------------------






# --------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->distinct=false;
  $re->exclude='/^ec_order/';
  $re->from="<<tp>>ec_order AS ec_order,
             <<tp>>ec_user AS ec_user,
             <<tp>>site_visitor AS site_visitor ";
  $re->add_where(" ec_order.site_id={$site_id} ");
  $re->add_where(" ec_order.ec_user_id=ec_user.ec_user_id ");
  $re->add_where(" ec_user.site_visitor_id=site_visitor.site_visitor_id ");
//
  $re->add_field( $field='ec_order.ec_order_id'
                 ,$alias='ec_order_id'
                 ,$type ='id:hidden=no'
                 ,$label='#'
                 ,$_group_operation=false);

  $re->add_field( $field='ec_user.ec_user_name'
                 ,$alias='ec_user_name'
                 ,$type ='string'
                 ,$label=text('ec_user_name')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_user.ec_user_telephone'
                 ,$alias='ec_user_telephone'
                 ,$type ='string'
                 ,$label=text('ec_user_telephone')
                 ,$_group_operation=false);

  $re->add_field( $field='site_visitor.site_visitor_email'
                 ,$alias='site_visitor_email'
                 ,$type ='string'
                 ,$label=text('ec_user_email')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_user.ec_user_icq'
                 ,$alias='ec_user_icq'
                 ,$type ='string'
                 ,$label=text('ec_user_icq')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_order.ec_order_total'
                 ,$alias='ec_order_total'
                 ,$type ='string'
                 ,$label=text('ec_order_total')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_order.ec_order_paid'
                 ,$alias='ec_order_paid'
                 ,$type ='enum:1='.rawurlencode(text('Ec_order_is_paid_successfully')).'&0='.rawurlencode(text('Ec_order_is_not_paid'))
                 ,$label=text('ec_order_paid')
                 ,$_group_operation=false);

  $tmp=explode(',',ec_order_status);
  $cnt=count($tmp);
  for($i=0;$i<$cnt;$i++)
  {
      $tmp[$i].='='.rawurlencode(text('ec_order_status_'.$tmp[$i]));
  }
  $tmp=join('&',$tmp);
  $re->add_field( $field='ec_order.ec_order_status'
                 ,$alias='ec_order_status'
                 ,$type ='enum:'.$tmp
                 ,$label=text('ec_order_status')
                 ,$_group_operation=false);

# ec_date_created
  $re->add_field( $field='ec_order.ec_date_created'
                 ,$alias='ec_date_created'
                 ,$type ='datetime'
                 ,$label=text('Date')
                 ,$_group_operation=false);


  $re->add_field( $field='ec_order.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='ec_order'
                 ,$_group_operation=false);



  unset($field,$alias,$type,$label, $_group_operation);
  // prn($re->create_query());
  $response=$re->show();
  //prn($response);
# --------------------------- get list -- end ----------------------------------




# --------------------------- adjust list -- begin -----------------------------
    $cnt=count($response['rows']);

    $order_ids=Array();
    for($i=0;$i<$cnt;$i++) $order_ids[]=$response['rows'][$i]['ec_order_id'];
    if(count($order_ids)>0)
    {
        $query=join(',',$order_ids);
        $query="SELECT * FROM <<tp>>ec_cart WHERE ec_order_id IN($query) AND site_id=$site_id";
        $tmp=\e::db_getrows($query);
        //prn($tmp);
        $order_items=Array();
        foreach($order_ids as $oid) $order_items[$oid]=Array();
        foreach($tmp as $ec_item)
        {
            $order_items[$ec_item['ec_order_id']][$ec_item['ec_item_id']]=unserialize($ec_item['ec_cart_item']);
        }
    }


    for($i=0;$i<$cnt;$i++)
    {
      # context menu
        $response['rows'][$i]['context_menu']=menu_ec_order($response['rows'][$i]);

      # ec_items in the order
        $response['rows'][$i]['items']=$order_items[$response['rows'][$i]['ec_order_id']];
    }
    //prn($response);
# --------------------------- adjust list -- end -------------------------------



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
      table.sl td{color:silver;}
      .tb{width:35px;}

      .b-b{border-bottom:1px dotted silver;}
      .b-r{border-right:1px dotted silver;}
      .b-l{border-left:1px dotted silver;}
      .b-t{border-top:1px dotted silver;}
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


        # $alias='ec_order_id'
        $fld=$response['fields']['ec_order_id'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}:</td>
               <td align=left valign=top>
               <input type=text
                      name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                      value=\"{$value}\"
                      size=3>
               </td>

        ";

        #  ,$alias='is_under_construction'
        $fld=$response['fields']['ec_order_status'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <td>{$response['fields'][$fld['alias']]['label']}:</td>
               <td align=left valign=top><nobr>
                 <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\">
                     <option value=''> </option>
                      {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                 </select>
                 <select name =\"{$response['fields']['ec_order_paid']['filter']['form_element_name']}\">
                     <option value=''> </option>
                      {$response['fields']['ec_order_paid']['filter']['form_element_options']}
                 </select></nobr>
               </td>
               </tr>
        ";



        # $alias='ec_user_name'
        $fld=$response['fields']['ec_user_name'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}:</td>
               <td align=left valign=top>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>

        ";


        # $alias='ec_user_telephone'
        $fld=$response['fields']['ec_user_telephone'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="

               <td>{$response['fields'][$fld['alias']]['label']}:</td>
               <td align=left valign=top>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>
               </tr>
        ";

//
//


        # $alias='ec_user_email'
        $fld=$response['fields']['site_visitor_email'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}:</td>
               <td align=left valign=top>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>

        ";


        # $alias='ec_user_icq'
        $fld=$response['fields']['ec_user_icq'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="

               <td>{$response['fields'][$fld['alias']]['label']}:</td>
               <td align=left valign=top>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>
               </tr>
        ";

         # $alias='ec_date_created'
           $fld=$response['fields']['ec_date_created'];
           $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
           $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top colspan=3>
                        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                            value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                             size=17>
                        <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_min_name']})\"></nobr>
                        &minus;
                        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                            value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                             size=17>
                        <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_max_name']})\"></nobr>
               </td>
               </tr>
           ";

         $tor.="
              <tr><td colspan=4 valign=top align=right><input type=submit name=submit value=\"{$text['Search']}\"></td></tr>\n

         </table>
         </form>";
        //echo $tor;
     // ------------------------- filter -- end --------------------------------

     // ------------------------- paging - begin -------------------------------
        $paging="<tr>\n<td colspan=\"9\" align=left style='border:none;'>
             {$response['total_rows']} {$text['rows found']} :::::::
             {$text['Pages']} :
             ";
        foreach($response['pages'] as $pg)
          $paging.="<a href=\"{$pg['page_url']}\">{$pg['page_id']}</a>&nbsp;";

        $paging.='<br>';
        if(strlen($response['backward'])>0) $paging.="&nbsp;<a href=\"{$response['backward']}\">{$text['Previous_page']}</a>&nbsp;";
        if(strlen($response['forward'])>0)  $paging.="&nbsp;<a href=\"{$response['forward']}\">{$text['Next page']}</a>&nbsp;";

        $paging.="\n</td>\n</tr>\n\n";
     // ------------------------- paging - end ---------------------------------

     $tor.="
     <form action=index.php method=post>
     ".hidden_form_elements('^ec_order|^submit')."
     <table cellspacing=0px width=98%>
     $paging";

     // ------------------------- header -- begin ------------------------------
     $tor.="
     <tr>
         <th align=center valign=top></th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_order_id']['label']}</b><br>
               <nobr>
               <a href=\"{$response['fields']['ec_order_id']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_order_id']['url_order_desc']}\">&Lambda;</a>
               </nobr>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_user_name']['label']}</b><br>
               <a href=\"{$response['fields']['ec_user_name']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_user_name']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_date_created']['label']}</b><br>
               <a href=\"{$response['fields']['ec_date_created']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_date_created']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_order_total']['label']}</b><br>
               <a href=\"{$response['fields']['ec_order_total']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_order_total']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_order_status']['label']}</b><br>
               <a href=\"{$response['fields']['ec_order_status']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_order_status']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_order_paid']['label']}</b><br>
               <a href=\"{$response['fields']['ec_order_paid']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_order_paid']['url_order_desc']}\">&Lambda;</a>
         </th>
     </tr>\n";
     // ------------------------- header -- end --------------------------------


     // ------------------------- rows -- begin --------------------------------
     $yesno=Array(1=>text('positive_answer'),0=>text('negative_answer'));
     $uid=0;
     foreach($response['rows'] as $row_id=>$row)
     {
       $tor.="<tr class=row>
               <td align=center valign=top rowspan=2>
       ";

       //--------------------------- context menu - begin ----------------------
       if(is_array($row['context_menu']))
       {
         $tor.="<img src=\"img/context_menu.gif\" width=20px height=15px border=1px alt=\"\" onclick=\"report_change_state('cm{$row_id}')\">
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
           <td align=left valign=top rowspan=2><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_order_id']}</a></td>
           <td align=left valign=top class='b-b b-r'><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['site_visitor_email']}</a></td>
           <td align=left valign=top class='b-b b-r b-l'><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_date_created']}</a></td>
           <td align=left valign=top class='b-b b-r b-l'><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_order_total']}</a></td>
           <td align=left valign=top class='b-b b-r b-l'>
              <select style='width:100pt;' name=ec_order[{$row['ec_order_id']}][ec_order_status]>
              ".draw_options($row['ec_order_status_value'],$response['fields']['ec_order_status']['options'] )."
              </select>
           </td>
           <td align=center valign=top class='b-b     b-l'>
              <a class=row onclick=\"report_change_state('cm{$row_id}')\">{$yesno[$row['ec_order_paid_value']]}</a>
           </td>
       </tr>\n\n";

       $userdetails='';
       //{$row['ec_user_name']}
       if(strlen($row['ec_user_name'])>0)  $userdetails.="<tr><td width=30pt></td><td>{$row['ec_user_name']}</td></tr>";
       if(strlen($row['ec_user_telephone'])>0)  $userdetails.="<tr><td width=30pt>Tel.:</td><td>{$row['ec_user_telephone']}</td></tr>";
       if(strlen($row['site_visitor_email'])>0)      $userdetails.="<tr><td width=30pt><nobr>e-mail:</nobr></td><td>{$row['site_visitor_email']}</td></tr>";
       if(strlen($row['ec_user_icq'])>0)      $userdetails.="<tr><td width=30pt>ICQ:</td><td>{$row['ec_user_icq']}</td></tr>";


       $orderdetails='';
       //$orderdetails.="<tr><td><div class='b-t'>".text('ec_item_title')."</div></td><td width=30pt><div class='b-t'>".text('ec_item_amount')."</div></td></tr>";
       
       $uid++;
       foreach($row['items'] as $it)
       {
           //prn($it);
           $it_name='';
           //$it_name.="<div style='color:silver;'>{$it['info']['ec_item_title']}</div>";
           foreach($it['info']['ec_item_variant'] as $va)
           {
               $it_name.= "<br>".strip_tags($va['ec_item_variant_description'])." {$va['ec_item_variant_price_correction']['code']};";
           }
           $it_name = "<b>{$it['info']['ec_item_title']}</b> {$it_name}";
           $orderdetails.="<tr><td>$it_name</td><td width=30pt valign='top'>{$it['amount']} ".text('items')."</td></tr>";
       }
       $orderdetails="<table cellspacing=0 border=0 class='noborder sl' width=95%>$orderdetails</table>";
       $userdetails="<table cellspacing=0 border=0 class='noborder sl' width=95%>$userdetails</table>";
       $tor.="
         <tr>
            <td colspan=1 class='b-t b-r' valign=top>$userdetails</td>
            <td colspan=4 valign=top class='b-t b-l' valign=top>$orderdetails</td>
         </tr>
       ";

     }

     // ------------------------- rows -- end ----------------------------------

     $tor.=$paging;
     $tor.="<tr>
             <td colspan=\"5\" align=center style='border:none;'></td>
             <td colspan=\"3\" align=center style='border:none;'><input type=submit value=\"".text('Save_changes')."\" style='width:100%;'></td>
             <td colspan=\"1\" align=center style='border:none;'></td>
            </tr>";

//$tor.=

     $tor.="\n</table>\n

     </form>";
     //return $tor;
# ---------------------- draw list - end ---------------------------------------





//
$input_vars['page_title']  = 
$input_vars['page_header'] = $this_site_info['title'] .' - '. text('EC_orders');
$input_vars['page_content'] = $tor;
# --------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
# --------------------------- context menu -- end ------------------------------

?>

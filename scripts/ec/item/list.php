<?php
/*
  List of goods for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

$debug=false;
run('site/menu');
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



// ------------------ update - begin -------------------------------------------
   if(isset($input_vars['ec_item']) && is_array($input_vars['ec_item']))
   {
      //if(is_admin()) prn('updating...',$input_vars['ec_item']);
      foreach( $input_vars['ec_item'] as $val )
      {
         $ec_item_id=(int)$val['ec_item_id'];
         unset($val['ec_item_id']);
         $ec_item_lang=preg_replace("/\\W/",'',$val['ec_item_lang']);
         unset($val['ec_item_lang']);
         $query=Array();
         foreach($val as $fl=>$va) $query[]="$fl='".\e::db_escape($va)."'";
         $query="UPDATE <<tp>>ec_item SET ".join(',',$query).",cached_info=null,cache_datetime='2000-01-01 00:00:00' WHERE ec_item_id=$ec_item_id AND ec_item_lang='{$ec_item_lang}' LIMIT 1";
         //if(is_admin()) prn($query);
         \e::db_execute($query);
      }
   }
   if(isset($input_vars['ec_item_delete']) && isset($input_vars['ec_item_checked']))
   {
       //prn('Deleting ...');
       //prn($input_vars['ec_item_checked']);
       foreach($input_vars['ec_item_checked'] as $ke=>$va) ec_item_delete($ke,$va);
   }
// ------------------ update - end ---------------------------------------------

//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->distinct=false;
  $re->exclude='/^ec_item/';


  $re->from="<<tp>>ec_item AS ec_item";
  $re->add_where(" ec_item.site_id={$site_id} ");

  $category_id=isset($input_vars['filter_ec_category_id'])?((int)$input_vars['filter_ec_category_id']):0;
  if($category_id>0){
     $re->add_where("
             ( 
                ec_item.ec_item_id IN(select distinct ec_item_id from <<tp>>ec_item_category where ec_category_id={$category_id}) 
                OR ec_item.ec_category_id={$category_id}
             ) 
             ");
  }
  /*
   $re->add_field( $field="ec_item.ec_category_id"
                 ,$alias='ec_category_id'
                 ,$type ="enum:$list_of_categories"
                 ,$label=text('Category')
                 ,$_group_operation=false);
  */


  $re->add_field( $field='ec_item.ec_item_id'
                 ,$alias='ec_item_id'
                 ,$type ='id:hidden=no'
                 ,$label='#'
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='site_id'
                 ,$_group_operation=false);

  //---------------- list of languages - begin ---------------------------------
    $LL = join('&',\e::db_get_associated_array("SELECT ec_item_lang,CONCAT(ec_item_lang,'=',ec_item_lang) FROM <<tp>>ec_item WHERE site_id={$site_id}"));
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

  $re->add_field( $field='ec_item.ec_item_uid'
                 ,$alias='ec_item_uid'
                 ,$type ='string'
                 ,$label=wordwrap(text('ec_item_uid'), 4, "&shy;",1)
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item.ec_item_price'
                 ,$alias='ec_item_price'
                 ,$type ='float'
                 ,$label=text('ec_item_price')
                 ,$_group_operation=false);
  $re->add_field( $field='ec_item.ec_item_code'
                 ,$alias='ec_item_code'
                 ,$type ='string:hidden=yes'
                 ,$label='ec_item_code'
                 ,$_group_operation=false);
 # ------------------------ list of currency - begin ---------------------------
    $query="SELECT ec_currency_code, ec_curency_title FROM <<tp>>ec_currency ORDER BY ec_curency_title";
    $tmp=\e::db_getrows($query);
    $list_of_currency=Array();
    foreach($tmp as $tm) $list_of_currency[]=$tm['ec_currency_code'].'='.rawurlencode($tm['ec_curency_title']);
    unset($tmp,$tm);
    $list_of_currency=join('&',$list_of_currency);
    //prn($list_of_categories);
 # ------------------------ list of currency - end -----------------------------
   $re->add_field( $field="ec_item.ec_item_currency"
                 ,$alias='ec_item_currency'
                 ,$type ="enum:$list_of_currency"
                 ,$label=text('Currency')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item.ec_item_amount'
                 ,$alias='ec_item_amount'
                 ,$type ='integer'
                 ,$label=wordwrap(text('ec_item_amount'), 4, "&shy;",1)
                 ,$_group_operation=false);

 # ------------------- list of publication_states - begin ----------------------
   $tmp=Array();
   $cnt=array_keys($GLOBALS['ec_item_publication_states']);
   $publication_states=Array();
   foreach($cnt as $ke)
   {
      $tmp[]=$ke.'='.rawurlencode(text($GLOBALS['ec_item_publication_states'][$ke]));
      $publication_states[$ke]=text($GLOBALS['ec_item_publication_states'][$ke]);
   }
   $tmp=join('&',$tmp);
 # ------------------- list of publication_states - end ------------------------
  $re->add_field( $field="ec_item.ec_item_cense_level"
                 ,$alias='ec_item_cense_level'
                 ,$type ="enum:$tmp"
                 ,$label=text('EC_item_publication')
                 ,$_group_operation=false);

  $re->add_field( $field='ec_item.ec_item_last_change_date'
                 ,$alias='ec_item_last_change_date'
                 ,$type ='datetime'
                 ,$label=text('Date')
                 ,$_group_operation=false);




  $re->add_field( $field='ec_item.ec_item_tags'
                 ,$alias='ec_item_tags'
                 ,$type ='string'
                 ,$label=text('ec_item_tags')
                 ,$_group_operation=false);

 # ------------------------ list of producers - begin --------------------------
    $query="SELECT ec_producer_id, ec_producer_title FROM <<tp>>ec_producer WHERE site_id={$site_id} ORDER BY ec_producer_title ASC";
    $tmp=\e::db_getrows($query);
    $list_of_producers=Array();
    foreach($tmp as $tm) $list_of_producers[]=$tm['ec_producer_id'].'='.rawurlencode($tm['ec_producer_title']);
    unset($tmp,$tm);
    $list_of_producers=join('&',$list_of_producers);
    //prn($list_of_producers);
 # ------------------------ list of producers - end ----------------------------
    $re->add_field( $field="ec_item.ec_producer_id"
                 ,$alias='ec_producer_id'
                 ,$type ="enum:$list_of_producers"
                 ,$label=text('Producer')
                 ,$_group_operation=false);
#

# ec_item_purchases
  $re->add_field( $field='ec_item.ec_item_purchases'
                 ,$alias='ec_item_purchases'
                 ,$type ='integer'
                 ,$label=wordwrap(text('ec_item_purchases'), 4, "&shy;",1)
                 ,$_group_operation=false);

# ec_item_purchases
  $re->add_field( $field='ec_item.ec_item_in_cart'
                 ,$alias='ec_item_in_cart'
                 ,$type ='integer'
                 ,$label=wordwrap(text('ec_item_in_cart'), 4, "&shy;",1)
                 ,$_group_operation=false);

# ec_item_purchases
  $re->add_field( $field='ec_item.ec_item_views'
                 ,$alias='ec_item_views'
                 ,$type ='integer'
                 ,$label=wordwrap(text('ec_item_views'), 4, "&shy;",1)
                 ,$_group_operation=false);

unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = $this_site_info['title'] .' - '. text('EC_items');
$input_vars['page_header'] = $this_site_info['title'] .' - '. text('EC_items');

  //--------------------------- context menu -- begin ----------------------------
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_ec_item($response['rows'][$i]);
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------



























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
      <script type=\"text/javascript\" src=\"scripts/lib/calendar/calendar.js\"></script>
      ";

     // ------------------------- filter -- begin ------------------------------


        $tor.="
        <form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
           {$response['hidden_fields']}
           <table class=noborder>
        ";


        # $alias='ec_item_title'
        $fld=$response['fields']['ec_item_title'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>

        ";


        # $alias='ec_item_id'
        $fld=$response['fields']['ec_item_id'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="

               <td>&nbsp;&nbsp;&nbsp;{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
               <input type=text
                      name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                      value=\"{$value}\"
                      size=3>
               </td>
          </tr>
        ";


    # ------------------------ list of categories - begin -------------------------
    $query="SELECT ec_category_id, ec_category_title, deep FROM <<tp>>ec_category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
    $tmp=\e::db_getrows($query);
    $list_of_categories=Array();
    foreach($tmp as $tm) $list_of_categories[$tm['ec_category_id']]=str_repeat(' + ',$tm['deep']-1).get_langstring($tm['ec_category_title']);
    unset($tmp,$tm);
    // $list_of_categories=join('&',$list_of_categories);
    // prn($list_of_categories);
    # ------------------------ list of categories - end ---------------------------
        # $alias='ec_category_id'
        //$value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>".text('Category')."</td>
               <td align=left valign=top>
                 <select name =\"filter_ec_category_id\" style='width:100%;'>
                     <option value=''> </option>
                     ".draw_options($category_id,$list_of_categories)."
                 </select>
               </td>
        ";

        # $alias='ec_item_id'
        $fld=$response['fields']['ec_item_uid'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <td>&nbsp;&nbsp;&nbsp;{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
               <input type=text
                      name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                      value=\"{$value}\"
                      size=15>
               </td>
           </tr>
        ";

        # $alias='ec_producer_id'
        $fld=$response['fields']['ec_producer_id'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                 <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\" style='width:100%;'>
                     <option value=''> </option>
                      {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                 </select>
               </td>
        ";

        # $alias='ec_item_lang'
        $fld=$response['fields']['ec_item_lang'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <td>&nbsp;&nbsp;&nbsp;{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                 <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\">
                     <option value=''> </option>
                      {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                 </select>
               </td>
               </tr>
        ";

        # $alias='ec_item_tags'
        $fld=$response['fields']['ec_item_tags'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                        <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                         value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                         size=50 style='width:100%;'>
               </td>
        ";

        #  ,$alias='is_under_construction'
        $fld=$response['fields']['ec_item_cense_level'];
        //prn($fld);
        $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
        $tor.="
               <td>&nbsp;&nbsp;&nbsp;{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                 <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\">
                     <option value=''> </option>
                      {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                 </select>
               </td>
               </tr>
        ";


         # $alias='ec_item_price'
           $fld=$response['fields']['ec_item_price'];
           //prn($fld);
           $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                     size=17 style='width:120pt;'>

                        &minus;
                        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                     size=17 style='width:120pt;'>
                        </nobr>
               </td>

           ";

         # $alias='ec_item_purchases'
           $fld=$response['fields']['ec_item_purchases'];
           //prn($fld);
           $tor.="
               <td>&nbsp;&nbsp;&nbsp;{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                     size=5 style='width:40pt;'>
                        &minus;
                        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                     size=5 style='width:40pt;'>
               </td>
               </tr>
           ";

         # $alias='ec_item_last_change_date'
           $fld=$response['fields']['ec_item_last_change_date'];
           //prn($fld);
           $value = isset($response['fields'][$fld['alias']]['filter']['form_element_value'])?$response['fields'][$fld['alias']]['filter']['form_element_value']:'';
           $tor.="
               <tr>
               <td>{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                        <nobr><input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                     size=17>
                        <script type=\"text/javascript\">
                        <!--
                        var {$response['fields'][$fld['alias']]['filter']['form_element_min_name']}_calendar;
                        attach_calendar_to('{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}','{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}_calendar');
                        // -->
                        </script>
                        &minus;
                        <input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                     size=17>
                        <script type=\"text/javascript\">
                        <!--
                        var {$response['fields'][$fld['alias']]['filter']['form_element_max_name']}_calendar;
                        attach_calendar_to('{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}','{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}_calendar');
                        // -->
                        </script>
                       </nobr>
               </td>

           ";















         # $alias='ec_item_in_cart'
           $fld=$response['fields']['ec_item_in_cart'];
           //prn($fld);
           $tor.="

               <td>&nbsp;&nbsp;&nbsp;{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top><nobr>
                        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                     size=5 style='width:40pt;'>
                        &minus;
                        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                     size=5 style='width:40pt;'>
               </nobr></td>
               </tr>
           ";

         # $alias='ec_item_views'
           $fld=$response['fields']['ec_item_views'];
           //prn($fld);
           $tor.="
               <tr>
               <td colspan=2></td>
               <td>&nbsp;&nbsp;&nbsp;{$response['fields'][$fld['alias']]['label']}</td>
               <td align=left valign=top>
                        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])?$response['fields'][$fld['alias']]['filter']['form_element_min_value']:'')."\"
                                     size=5 style='width:40pt;'>

                        &minus;
                        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"".(isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])?$response['fields'][$fld['alias']]['filter']['form_element_max_value']:'')."\"
                                     size=5 style='width:40pt;'>
                        </nobr>
               </td>
               </tr>
           ";

$tor.="
              <tr><td colspan=4 valign=top align=right><input type=submit name=submit value=\"{$text['Search']}\"></td></tr>\n

         </table>
         </form>";
        //echo $tor;
     // ------------------------- filter -- end --------------------------------


     $tor.="
     <form action=index.php method=post>
     ".preg_hidden_form_elements('/^ec_item|^submit/')."
     <table cellspacing=0px>";


     // ------------------------- header -- begin ------------------------------
     $tor.="
     <tr>
         <th align=center valign=top></th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_id']['label']}</b><br>
               <nobr>
               <a href=\"{$response['fields']['ec_item_id']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_id']['url_order_desc']}\">&Lambda;</a>
               </nobr>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_lang']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_lang']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_lang']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_uid']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_uid']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_uid']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_title']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_title']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_title']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_cense_level']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_cense_level']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_cense_level']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_price']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_price']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_price']['url_order_desc']}\">&Lambda;</a>
         </th>

         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_amount']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_amount']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_amount']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_last_change_date']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_last_change_date']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_last_change_date']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_purchases']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_purchases']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_purchases']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_in_cart']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_in_cart']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_in_cart']['url_order_desc']}\">&Lambda;</a>
         </th>
         <th align=center valign=bottom>
               <b>{$response['fields']['ec_item_views']['label']}</b><br>
               <a href=\"{$response['fields']['ec_item_views']['url_order_asc']}\">V</a>
               <a href=\"{$response['fields']['ec_item_views']['url_order_desc']}\">&Lambda;</a>
         </th>


     </tr>\n";
     // ------------------------- header -- end --------------------------------

     // ------------------------- rows -- begin --------------------------------
     // prn($response['fields']['ec_item_currency']['options']);
     foreach($response['rows'] as $row_id=>$row)
     {
       $item_uid="{$row['ec_item_id']}{$row['ec_item_lang']}";
       $tor.="<tr class=row>
               <td align=center valign=top>
                <nobr>
                <input type=checkbox name=ec_item_checked[{$row['ec_item_id']}] value=\"{$row['ec_item_lang']}\">
       ";

       //--------------------------- context menu - begin ----------------------
       if(is_array($row['context_menu']))
       {
         $tor.="<img src=\"img/context_menu.gif\" border=1px alt=\"\" onclick=\"report_change_state('cm{$row_id}')\"></nobr>
         <div class=menu_block style='display:none;' id='cm{$row_id}'>";
         foreach($row['context_menu'] as $menu_item)
         {
           $tor.="<nobr><a href=\"{$menu_item['URL']}\" {$menu_item['attributes']}>{$menu_item['innerHTML']}</a></nobr><br/>\n";
         }
         $tor.="</div>";
       }
       else $tor.="</nobr>";
       //--------------------------- context menu - end ------------------------
       //prn($row);
       $tor.="
       </td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_id']}</a></td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_lang']}</a></td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_uid']}</a></td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_title']}</a></td>
           <td align=left valign=top>
              <input type=hidden name=ec_item[{$item_uid}][ec_item_id] value=\"{$row['ec_item_id']}\">
              <input type=hidden name=ec_item[{$item_uid}][ec_item_lang] value=\"{$row['ec_item_lang']}\">
              <select style='width:100pt;' name=ec_item[{$item_uid}][ec_item_cense_level]>
              ".draw_options($row['ec_item_cense_level_value'],$publication_states )."
              </select>
           </td>
           <td align=left valign=top><nobr>
              <input type=text style='width:40pt;'
                     name=ec_item[{$item_uid}][ec_item_price]
                     value=\"{$row['ec_item_price']}\"><!--
                         -->".(isset($response['fields']['ec_item_currency']['options'][$row['ec_item_currency_value']])?$response['fields']['ec_item_currency']['options'][$row['ec_item_currency_value']]:$row['ec_item_currency_value'])."<!--
           --></nobr>
           </td>
           <td align=left valign=top>
              <input type=text style='width:40pt;' name=ec_item[{$item_uid}][ec_item_amount] value=\"{$row['ec_item_amount']}\">
           </td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_last_change_date']}</a></td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_purchases']}</a></td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_in_cart']}</a></td>
           <td align=left valign=top><a class=row onclick=\"report_change_state('cm{$row_id}')\">{$row['ec_item_views']}</a></td>
       </tr>\n\n";
       // <select style='width:40pt;' name=ec_item[{$row['ec_item_id']}][ec_item_currency]>
       //       ".draw_options($row['ec_item_currency_value'],$response['fields']['ec_item_currency']['options'])."
       // </select>
     }

     // ------------------------- rows -- end ----------------------------------

     $tor.="<tr>
             <td colspan=\"5\" align=center style='border:none;'></td>
             <td colspan=\"3\" align=center style='border:none;'><input type=submit value=\"".text('Save_changes')."\" style='width:70%;'><input type=submit name=ec_item_delete value=\"".text('Delete')."\" style='width:30%;'></td>
             <td colspan=\"1\" align=center style='border:none;'></td>
            </tr>";

     // ------------------------- paging - begin -------------------------------
        $tor.="<tr>\n<td colspan=\"9\" align=left style='border:none;'>
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
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
<?php

/*
 * Manage rss aggregator sources
 *
 * http://127.0.0.1/cms/index.php?action=rss_aggregator/sources&site_id=1
 */

run('site/menu');
run('rss_aggregator/functions');

//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

//prn('$this_site_info=',$this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
//------------------- get site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0) {
   $input_vars['page_title']  =
   $input_vars['page_header'] =
   $input_vars['page_content']= text('Access_denied');
   return 0;
}
//------------------- check permission - end -----------------------------------


  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  $re->from="
   {$table_prefix}rsssource as rsssource
   LEFT JOIN {$table_prefix}rsssourceitem AS rsssourceitem
   ON ( rsssource.rsssource_id=rsssourceitem.rsssource_id AND rsssourceitem.site_id=$site_id )
  ";

   $re->add_where(" rsssource.site_id=$site_id ");

  $re->add_field( $field='rsssource.rsssource_id'
                 ,$alias='rsssource_id'
                 ,$type ='id'
                 ,$label=text('rsssource_id')
                 ,$_group_operation=false);

  $re->add_field( $field='rsssource.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label=text('site_id')
                 ,$_group_operation=false);

  $LL = join('&',db_get_associated_array("SELECT rsssource_lang,CONCAT(rsssource_lang,'=',rsssource_lang) FROM {$table_prefix}rsssource WHERE site_id={$site_id}"));
  $re->add_field( $field='rsssource.rsssource_lang'
                 ,$alias='rsssource_lang'
                 ,$type ='enum:'.$LL
                 ,$label=text('rsssource_lang')
                 ,$_group_operation=false);

  $re->add_field( $field='rsssource.rsssource_title'
                 ,$alias='rsssource_title'
                 ,$type ='string'
                 ,$label=text('rsssource_title')
                 ,$_group_operation=false);

  $re->add_field( $field='rsssource.rsssource_url'
                 ,$alias='rsssource_url'
                 ,$type ='string'
                 ,$label=text('rsssource_url')
                 ,$_group_operation=false);

  $re->add_field($field = 'rsssource.rsssource_last_updated'
                 , $alias = 'rsssource_last_updated'
                 , $type = 'datetime'
                 , $label = text('Date')
                 , $_group_operation = false);

  $re->add_field($field = "rsssource.rsssource_is_visible"
                 , $alias = 'rsssource_is_visible'
                 , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 , $label = text("rsssource_is_visible")
                 , $_group_operation = false);

  $re->add_field($field = "rsssource.rsssource_is_premoderated"
                 , $alias = 'rsssource_is_premoderated'
                 , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 , $label = text("rsssource_is_premoderated")
                 , $_group_operation = false);

  $re->add_field( $field='rsssource.rsssource_tag'
                 ,$alias='rsssource_tag'
                 ,$type ='string'
                 ,$label=text('rsssource_tag')
                 ,$_group_operation=false);

  $re->add_field( $field='count(DISTINCT rsssourceitem.rsssourceitem_id)'
                 ,$alias='n_items'
                 ,$type ='integer'
                 ,$label=text('rsssource_n_items')
                 ,$_group_operation=true);

  unset($field,$alias,$type,$label, $_group_operation);
  // prn($re->create_query());
  $response=$re->show();
  // prn($response);

//--------------------------- get list -- end ----------------------------------

//--------------------------- adjust list -- begin -----------------------------
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++) {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_rsssource($response['rows'][$i]);
        $response['rows'][$i]['rsssource_last_updated']= "<nobr>{$response['rows'][$i]['rsssource_last_updated']}</nobr>";
        $response['rows'][$i]['rsssource_url_value']= $response['rows'][$i]['rsssource_url'];
        $response['rows'][$i]['rsssource_url']= "<a href=\"{$response['rows'][$i]['rsssource_url']}\">".  shorten($response['rows'][$i]['rsssource_url'],30)."</a>";


      //--------------------------- context menu -- end --------------------------
    }
//--------------------------- adjust list -- end -------------------------------


$input_vars['page_title']  =
$input_vars['page_header'] = $this_site_info['title'] .' - '. text('rsssource_list');
$input_vars['page_content']= '
<p><a href="index.php?action=rss_aggregator/source_edit&site_id='.$site_id.'">'.text('Create_rsssource').'</a>
'.$re->draw_default_list($response);



//--------------------------- context menu -- begin ----------------------------
  $sti=text('Site').' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------



?>
<?php
/*
  List of sites
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

if(!is_logged()) return 0;


//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  $re->from="<<tp>>site AS site";

  if(!is_admin())
  {
     //------------------------ get own sites - begin --------------------------
       $query="SELECT site_id FROM <<tp>>site_user WHERE user_id={$_SESSION['user_info']['id']}";
       $site_list=\e::db_getrows($query);
       $sl=Array();
       $sl[]=0;
       foreach($site_list as $st)
       {
          $sl[]=$st['site_id'];
       }
       $sl=join(',', $sl);
       $re->add_where(" site.id IN($sl) ");
     //------------------------ get own sites - begin --------------------------
  }

  $re->add_field( $field='site.id'
                 ,$alias='id'
                 ,$type ='id:hidden=no'
                 ,$label="#"
                 ,$_group_operation=false);

  $re->add_field( $field='site.cense_level'
                 ,$alias='cense_level'
                 ,$type ='id:hidden=yes'
                 ,$label=$text['Site_id']
                 ,$_group_operation=false);

  $re->add_field( $field='site.dir'
                 ,$alias='dir'
                 ,$type ='string'
                 ,$label=$text['Site_directory']
                 ,$_group_operation=false);

  $re->add_field( $field='site.title'
                 ,$alias='title'
                 ,$type ='string'
                 ,$label=$text['Site_title']
                 ,$_group_operation=false);

  $re->add_field( $field='site.url'
                 ,$alias='url'
                 ,$type ='string:hidden=yes'
                 ,$label=$text['Site_title']
                 ,$_group_operation=false);


  $re->add_field( $field='site.template'
                 ,$alias='template'
                 ,$type ='string:hidden=yes'
                 ,$label='template'
                 ,$_group_operation=false);


  $re->add_field( $field='site.is_gb_enabled'
                 ,$alias='is_gb_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_gb_enabled'
                 ,$_group_operation=false);


  $re->add_field( $field='site.is_search_enabled'
                 ,$alias='is_search_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_search_enabled'
                 ,$_group_operation=false);

  $re->add_field( $field='site.is_ec_enabled'
                 ,$alias='is_ec_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_ec_enabled'
                 ,$_group_operation=false);

  $re->add_field( $field='site.is_gallery_enabled'
                 ,$alias='is_gallery_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_gallery_enabled'
                 ,$_group_operation=false);

  $re->add_field( $field='site.is_news_line_enabled'
                 ,$alias='is_news_line_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_news_line_enabled'
                 ,$_group_operation=false);


  $re->add_field( $field='site.is_site_map_enabled'
                 ,$alias='is_site_map_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_site_map_enabled'
                 ,$_group_operation=false);

  $re->add_field( $field='site.is_forum_enabled'
                 ,$alias='is_forum_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_forum_enabled'
                 ,$_group_operation=false);

  $re->add_field( $field='site.is_poll_enabled'
                 ,$alias='is_poll_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_poll_enabled'
                 ,$_group_operation=false);

  $re->add_field( $field='site.is_calendar_enabled'
                 ,$alias='is_calendar_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_calendar_enabled'
                 ,$_group_operation=false);
  
  $re->add_field( $field='site.is_rssaggegator_enabled'
                 ,$alias='is_rssaggegator_enabled'
                 ,$type ='id:hidden=yes'
                 ,$label='is_calendar_enabled'
                 ,$_group_operation=false);
  ////
  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = $text['List_of_sites'];
$input_vars['page_header'] = $text['List_of_sites'];

  //--------------------------- context menu -- begin ----------------------------
    run('site/menu');
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_site($response['rows'][$i]);
      //--------------------------- context menu -- end ------------------------
      $response['rows'][$i]['title']=get_langstring($response['rows'][$i]['title']);
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']= $re->draw_default_list($response);
?>
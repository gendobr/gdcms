<?php
/*
  List of forums for a selected site
  Argument is
  $site_id - site identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
  run('site/menu');

//------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
    $this_site_info = get_site_info($site_id);

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


//-------------------- delete forum - begin ------------------------------------
  $delete_forum_id= isset($input_vars['delete_forum_id'])?checkInt($input_vars['delete_forum_id']):0;
  if($delete_forum_id>0)
  {
     $query="DELETE FROM <<tp>>forum_list   WHERE site_id={$site_id} AND id={$delete_forum_id}";
     // prn($query);
     \e::db_execute($query);

     $query="DELETE FROM <<tp>>forum_thread WHERE site_id={$site_id} AND forum_id=$delete_forum_id";
     // prn($query);
     \e::db_execute($query);

     $query="DELETE FROM <<tp>>forum_msg    WHERE site_id={$site_id} AND forum_id=$delete_forum_id";
     // prn($query);
     \e::db_execute($query);

  }
  clear('delete_msg_id');
//-------------------- delete forum - end --------------------------------------

//-------------------- show message - begin ------------------------------------
  $show_msg_id=isset($input_vars['show_msg_id'])?checkInt($input_vars['show_msg_id']):0;
  if($show_msg_id>0)
  {
     $query="UPDATE <<tp>>gb SET is_visible=1 WHERE id={$show_msg_id} AND site={$site_id}";
     // prn($query);
     \e::db_execute($query);
  }
  clear('show_msg_id');
//-------------------- show message - end --------------------------------------

//-------------------- hide message - begin ------------------------------------
  $hide_msg_id=isset($input_vars['hide_msg_id'])?checkInt($input_vars['hide_msg_id']):0;
  if($hide_msg_id>0)
  {
     $query="UPDATE <<tp>>gb SET is_visible=0 WHERE id={$hide_msg_id} AND site={$site_id}";
     // prn($query);
     \e::db_execute($query);
  }
  clear('hide_msg_id');
//-------------------- hide message - end --------------------------------------

//--------------------------- get list -- begin --------------------------------
/*
SELECT <<tp>>forum_list.* 
       , count(DISTINCT <<tp>>forum_thread.id) AS n_threads
       , count(DISTINCT <<tp>>forum_msg.id) AS n_messages
       , MAX(<<tp>>forum_msg.data) AS  last_message_data
  FROM 
  (
   (<<tp>>forum_list LEFT JOIN <<tp>>forum_thread
     ON ( <<tp>>forum_list.id=<<tp>>forum_thread.forum_id 
          AND <<tp>>forum_thread.site_id=$site_id)
   )
   LEFT JOIN <<tp>>forum_msg 
   ON (<<tp>>forum_msg.forum_id=<<tp>>forum_list.id 
       AND <<tp>>forum_msg.site_id=$site_id)
  )
  WHERE <<tp>>forum_list.site_id=$site_id
  GROUP BY <<tp>>forum_list.id
*/
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  $re->from="
  (
   (<<tp>>forum_list as fl LEFT JOIN <<tp>>forum_thread AS ft
     ON ( fl.id=ft.forum_id AND ft.site_id=$site_id)
   )
   LEFT JOIN <<tp>>forum_msg  AS fm
   ON (fm.forum_id=fl.id AND fm.site_id=$site_id)
  )
  ";
  $re->add_where(" fl.site_id={$site_id} ");

  $re->add_field( $field='fl.id'
                 ,$alias='id'
                 ,$type ='id'
                 ,$label=$text['Forum_id']
                 ,$_group_operation=false);

  $re->add_field( $field='fl.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='site_id'
                 ,$_group_operation=false);

  $re->add_field( $field='fl.name'
                 ,$alias='name'
                 ,$type ='string'
                 ,$label=$text['forum_title']
                 ,$_group_operation=false);

  $re->add_field( $field='count(DISTINCT ft.id)'
                 ,$alias='n_threads'
                 ,$type ='integer'
                 ,$label=$text['forum_n_threads']
                 ,$_group_operation=true);

  $re->add_field( $field='count(DISTINCT fm.id)'
                 ,$alias='n_messages'
                 ,$type ='integer'
                 ,$label=$text['forum_n_messages']
                 ,$_group_operation=true);


  $re->add_field( $field='MAX(fm.data)'
                 ,$alias='last_message_data'
                 ,$type ='integer'
                 ,$label=$text['forum_last_message_date']
                 ,$_group_operation=true);

  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------
$input_vars['page_title']  = $this_site_info['title'] .' - '. $text['forum_list'];
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['forum_list'];

  //--------------------------- context menu -- begin ----------------------------
    run('forum/menu');
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_forum($response['rows'][$i]);
        $response['rows'][$i]['last_message_data']= "<nobr>{$response['rows'][$i]['last_message_data']}</nobr>";
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']= $re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
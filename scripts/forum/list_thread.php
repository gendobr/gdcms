<?php
/*
  List of threads for a selected site and selected forum
  Arguments are
  $site_id - site identifier, integer, mandatory
  $forum_id - forum identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
  run('site/menu');
  run('forum/menu');

//------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);

  //prn('$this_site_info=',$this_site_info);
  if(checkInt($this_site_info['id'])<=0) {
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

//------------------- get forum info - begin -----------------------------------
  $forum_id = checkInt($input_vars['forum_id']);
  $this_forum_info =\e::db_getonerow("SELECT * FROM <<tp>>forum_list WHERE id={$forum_id}");
  //prn('$this_site_info=',$this_site_info);
  if(checkInt($this_forum_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Forum_not_found'];
     $input_vars['page_header']  = $text['Forum_not_found'];
     $input_vars['page_content'] = $text['Forum_not_found'];
     return 0;
  }
//------------------- get forum info - end -------------------------------------


//-------------------- delete thread - begin -----------------------------------
  $delete_thread_id=checkInt(isset($input_vars['delete_thread_id'])?$input_vars['delete_thread_id']:0);
  if($delete_thread_id>0)
  {
     $query="DELETE FROM <<tp>>forum_thread WHERE id={$delete_thread_id} AND site_id={$site_id} AND forum_id=$forum_id";
     // prn($query);
     \e::db_execute($query);
     $query="DELETE FROM <<tp>>forum_msg    WHERE thread_id={$delete_thread_id} AND site_id={$site_id} AND forum_id=$forum_id";
     // prn($query);
     \e::db_execute($query);
  }
  clear('delete_thread_id');
//-------------------- delete thread - end -------------------------------------


//--------------------------- get list -- begin --------------------------------
/*
    SELECT id,site_id, forum_id,subject,data,
          ,count(DISTINCT <<tp>>forum_msg.id) AS n_messages
          ,MAX(<<tp>>forum_msg.data) AS  last_message_data
    FROM
        `<<tp>>forum_thread` AS ft LEFT JOIN `<<tp>>forum_msg` AS fm
          ON (     ft.id=fm.thread_id AND ft.site_id=$site_id )
     WHERE     ft.site_id=$site_id
           AND ft.forum_id=$forum_id
     GROUP BY ft.id ORDER BY `id` DESC
*/
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  $re->from="
        `<<tp>>forum_thread` AS ft LEFT JOIN `<<tp>>forum_msg` AS fm
          ON (     ft.id=fm.thread_id AND ft.site_id=$site_id )
  ";
  $re->add_where(" ft.site_id=$site_id   ");
  $re->add_where(" ft.forum_id=$forum_id ");

  $re->add_field( $field='ft.id'
                 ,$alias='id'
                 ,$type ='id'
                 ,$label=$text['Forum_id']
                 ,$_group_operation=false);

  $re->add_field( $field='ft.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='site_id'
                 ,$_group_operation=false);

  $re->add_field( $field='ft.forum_id'
                 ,$alias='forum_id'
                 ,$type ='id:hidden=yes'
                 ,$label='forum_id'
                 ,$_group_operation=false);

  $re->add_field( $field='ft.subject'
                 ,$alias='subject'
                 ,$type ='string'
                 ,$label=$text['forum_thread_subject']
                 ,$_group_operation=false);

  $re->add_field( $field='MAX(fm.is_visible)'
                 ,$alias='is_visible'
                 ,$type ="enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 ,$label=text('is_visible')
                 ,$_group_operation=true);

  $re->add_field( $field='count(DISTINCT fm.id)'
                 ,$alias='n_messages'
                 ,$type ='integer'
                 ,$label=$text['forum_n_messages']
                 ,$_group_operation=true);


  $re->add_field( $field='MAX(fm.data)'
                 ,$alias='last_message_data'
                 ,$type ='datetime'
                 ,$label=$text['forum_last_message_date']
                 ,$_group_operation=true);


  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------
$input_vars['page_title']  = $text['forum_threads'];
$input_vars['page_header'] = $text['forum_threads'];

  //--------------------------- context menu -- begin ----------------------------
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_thread($response['rows'][$i]);
        $response['rows'][$i]['last_message_data']= "<nobr>{$response['rows'][$i]['last_message_data']}</nobr>";
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']="
 <p>
   <font size=+1><b>{$text['Site']} </b> : {$this_site_info['title']}</font><br>
   <font size=+1><b>{$text['Forum']}</b> : {$this_forum_info['name']}</font><br>
 </p>
"
.$re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------


  $input_vars['page_menu']['forum']=Array('title'=>$text['Forum'],'items'=>Array());
  $input_vars['page_menu']['forum']['items'] = menu_forum($this_forum_info);

  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
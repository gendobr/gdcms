<?
/*
  List of messages in guestbook for moderator
  Argument is
  $site_id - site identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
  run('site/menu');

//------------------- old site info - begin ------------------------------------
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
//------------------- old site info - end --------------------------------------

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
  $delete_msg_id=checkInt($input_vars['delete_msg_id']);
  if($delete_msg_id>0)
  {
     //error_reporting(E_ALL);
     $query="DELETE FROM {$table_prefix}gb WHERE id={$delete_msg_id} AND site={$site_id}";
     // prn($query);
     db_execute($query);
     // echo mysql_error();
  }
  clear('delete_msg_id');
//-------------------- delete message - end ------------------------------------

//-------------------- show message - begin ------------------------------------
  $show_msg_id=checkInt($input_vars['show_msg_id']);
  if($show_msg_id>0)
  {
     $query="UPDATE {$table_prefix}gb SET is_visible=1 WHERE id={$show_msg_id} AND site={$site_id}";
     // prn($query);
     db_execute($query);
  }
  clear('show_msg_id');
//-------------------- show message - end --------------------------------------

//-------------------- hide message - begin ------------------------------------
  $hide_msg_id=checkInt($input_vars['hide_msg_id']);
  if($hide_msg_id>0)
  {
     $query="UPDATE {$table_prefix}gb SET is_visible=0 WHERE id={$hide_msg_id} AND site={$site_id}";
     // prn($query);
     db_execute($query);
  }
  clear('hide_msg_id');
//-------------------- hide message - end --------------------------------------

//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended_1");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  $re->from="{$table_prefix}gb AS gb";
  $re->add_where(" gb.site={$site_id} ");

  $re->add_field( $field='gb.site'
                 ,$alias='site'
                 ,$type ='id:hidden=yes'
                 ,$label=$text['site_id']
                 ,$_group_operation=false);

  $re->add_field( $field='gb.id'
                 ,$alias='id'
                 ,$type ='id'
                 ,$label=$text['Message_id']
                 ,$_group_operation=false);

    $re->add_field( $field='gb.is_visible'
                   ,$alias='is_visible'
                   ,$type ="id:hidden=yes"
                   ,$label=$text['Is_Visible']
                   ,$_group_operation=false);

    $re->add_field( $field='gb.is_visible'
                   ,$alias='is_visible_show'
                   ,$type ="enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                   ,$label=$text['Is_Visible']
                   ,$_group_operation=false);

  $re->add_field( $field='gb.data'
                 ,$alias='data'
                 ,$type ="datetime"
                 ,$label=$text['Date_created']
                 ,$_group_operation=false);

  $re->add_field( $field='gb.name'
                 ,$alias='name'
                 ,$type ='string'
                 ,$label=$text['Creator_name']
                 ,$_group_operation=false);


  $re->add_field( $field='gb.email'
                 ,$alias='email'
                 ,$type ='string'
                 ,$label=$text['Creator_email']
                 ,$_group_operation=false);

  $re->add_field( $field="gb.adress"
                 ,$alias='address'
                 ,$type ="string"
                 ,$label=$text['Creator_site_URL']
                 ,$_group_operation=false);

  $re->add_field( $field='gb.tema'
                 ,$alias='tema'
                 ,$type ='string'
                 ,$label=$text['Subject']
                 ,$_group_operation=false);

  $re->add_field( $field='gb.text'
                 ,$alias='text'
                 ,$type ='string'
                 ,$label=$text['Message_body']
                 ,$_group_operation=false);

  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------
$input_vars['page_title']  = $this_site_info['title'] .' - '. $text['guestbook'];
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['guestbook'];

  //--------------------------- context menu -- begin ----------------------------
    run('gb/menu');
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_gb_msg($response['rows'][$i]);
        $response['rows'][$i]['name']=wordwrap( $response['rows'][$i]['name'], 50, "<br>\n", 1);;
        $response['rows'][$i]['tema']=wordwrap( $response['rows'][$i]['tema'], 50, "<br>\n", 1);;
        $response['rows'][$i]['text']=wordwrap( $response['rows'][$i]['text'], 50, "<br>\n", 1);;
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']= $re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
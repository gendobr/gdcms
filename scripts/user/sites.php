<?php
/*
  List of sites operated by current user
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

if(!is_admin()) return 0;

//--------------------------- check user id - begin ----------------------------
  $user_id = checkInt($input_vars['user_id']);
  $this_user_info=\e::db_getonerow("SELECT * FROM {$table_prefix}user WHERE id={$user_id} ");
  if(checkInt($this_user_info['id'])<=0)
  {
     $input_vars['page_header']=$text['User_not_found'];
     $input_vars['page_title']=$text['User_not_found'];
     $input_vars['page_content']=$text['User_not_found'];
     return 0;
  }
//--------------------------- check user id - end ------------------------------

//--------------------------- save new levels - begin --------------------------
$ivk=array_keys($input_vars);
foreach($ivk as $key)
{
   if(substr($key, 0, 18)=='filter_user_level_')
   {
      //prn($key);
      $site_id = checkInt(str_replace('filter_user_level_','',$key));
      $level   = checkInt($input_vars[$key]);
      $query   = "DELETE FROM {$table_prefix}site_user WHERE site_id={$site_id} AND user_id={$user_id}";
      \e::db_execute($query);
      if($level>0)
      {
        $query="INSERT INTO {$table_prefix}site_user(user_id,site_id,level) 
                VALUES ({$user_id}, {$site_id}, {$level})";
        \e::db_execute($query);
      }
      clear($key);
   }
}
//--------------------------- save new levels - end ----------------------------


//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct = true;
  $re->from="{$table_prefix}site AS si LEFT JOIN  {$table_prefix}site_user AS su ON(si.id=su.site_id AND su.user_id={$this_user_info['id']})";
  
  $re->add_field( $field='su.level'
                 ,$alias='level'
                 ,$type ='integer'
                 ,$label=$text['Level']
                 ,$_group_operation=false);

  $re->add_field( $field='si.cense_level'
                 ,$alias='cense_level'
                 ,$type ='integer:hidden=yes'
                 ,$label=$text['Maximal_Level']
                 ,$_group_operation=false);

  $re->add_field( $field='si.id'
                 ,$alias='id'
                 ,$type ='id:hidden=yes'
                 ,$label=$text['User_id']
                 ,$_group_operation=false);

  $re->add_field( $field='si.dir'
                 ,$alias='dir'
                 ,$type ='string'
                 ,$label=$text['Site_directory']
                 ,$_group_operation=false);
                               
  $re->add_field( $field='si.title'
                 ,$alias='title'
                 ,$type ='string'
                 ,$label=$text['Site_title']
                 ,$_group_operation=false);

  $re->add_field( $field='si.url'
                 ,$alias='url'
                 ,$type ='string'
                 ,$label=$text['Site_URL']
                 ,$_group_operation=false);


  $re->add_field( $field='si.template'
                 ,$alias='template'
                 ,$type ='string'
                 ,$label=$text['Template']
                 ,$_group_operation=false);


  unset($field,$alias,$type,$label, $_group_operation);
  // prn($re->create_query());
  $response=$re->show();
  // prn($response);

//--------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = '"'.$this_user_info['user_login'].'" - '.$text['list_of_operated_sites'];
$input_vars['page_header'] = '"'.$this_user_info['user_login'].'" - '.$text['list_of_operated_sites'];



  //--------------------------- context menu -- begin ----------------------------
    run('site/menu');
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      $response['rows'][$i]['level']="<nobr><input name=filter_user_level_{$response['rows'][$i]['id']} value=\"{$response['rows'][$i]['level']}\" size=3><font size=+1>/{$response['rows'][$i]['cense_level']}</font></nobr>";
      $response['rows'][$i]['url']="<nobr><a href=\"{$response['rows'][$i]['url']}\">".shorten($response['rows'][$i]['url'],30)."</a></nobr>";
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_site(get_site_info($response['rows'][$i]['id']));
      //--------------------------- context menu -- end --------------------------
      $response['rows'][$i]['title']=get_langstring($response['rows'][$i]['title']);
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']= $re->draw_default_list($response);

//----------------------------- site context menu - begin ----------------------
  $input_vars['page_menu']['user']=Array('title'=>$text['User_menu'],'items'=>Array());
  run('user/menu');
  $input_vars['page_menu']['user']['items'] = menu_user($this_user_info);
//----------------------------- site context menu - end ------------------------
?>
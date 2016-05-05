<?php
/*
  List of sites
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

if(!is_admin()) return 0;



# --------------------------- check site id - begin ----------------------------
  run('site/menu');
  $this_site_info=get_site_info(isset($input_vars['site_id'])?((int)$input_vars['site_id']):0);

  if(!$this_site_info)
  {
     $input_vars['page_header']=
     $input_vars['page_title']=
     $input_vars['page_content']=$text['Site_not_found'];
     return '';
  }
  $site_id=$this_site_info['id'];
# --------------------------- check site id - end ------------------------------




# --------------------------- save new levels - begin --------------------------
$ivk=array_keys($input_vars);
$changed=false;
foreach($ivk as $key)
{
   if(substr($key, 0, 18)=='filter_user_level_')
   {
      //prn($key);
      $changed=true;
      $usrid=checkInt(str_replace('filter_user_level_','',$key));
      $level=checkInt($input_vars[$key]);
      $query="DELETE FROM <<tp>>site_user WHERE site_id={$site_id} AND user_id={$usrid}";
      \e::db_execute($query);
      if($level>0)
      {
        $query="INSERT INTO <<tp>>site_user(user_id,site_id,level) 
                VALUES ({$usrid}, {$site_id}, {$level})";
        \e::db_execute($query);
      }
      clear($key);
   }
}
if($changed) ml('site/admins',$input_vars);
# --------------------------- save new levels - end ----------------------------


# --------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->distinct = true;
  $re->from="<<tp>>user AS us LEFT JOIN  <<tp>>site_user AS su ON(us.id=su.user_id AND site_id={$site_id})";
  
  $re->add_field( $field='su.level'
                 ,$alias='level'
                 ,$type ='integer'
                 ,$label=$text['Level']
                 ,$_group_operation=false);

  $re->add_field( $field='us.id'
                 ,$alias='id'
                 ,$type ='id:hidden=yes'
                 ,$label=$text['User_id']
                 ,$_group_operation=false);

  $re->add_field( $field='us.user_login'
                 ,$alias='user_login'
                 ,$type ='string'
                 ,$label=$text['User_login']
                 ,$_group_operation=false);
                                                
  $re->add_field( $field='us.full_name'
                 ,$alias='full_name'
                 ,$type ='string'
                 ,$label=$text['Full_name']
                 ,$_group_operation=false);

  $re->add_field( $field='us.telephone'
                 ,$alias='telephone'
                 ,$type ='string'
                 ,$label=$text['Telephone']
                 ,$_group_operation=false);

  $re->add_field( $field='us.email'
                 ,$alias='email'
                 ,$type ='string'
                 ,$label=$text['Email']
                 ,$_group_operation=false);

  unset($field,$alias,$type,$label, $_group_operation);
  // prn($re->create_query());
  $response=$re->show();
  // prn($response);

# --------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = '"'.$this_site_info['title'].'" - '.$text['list_of_site_operators'];
$input_vars['page_header'] = '"'.$this_site_info['title'].'" - '.$text['list_of_site_operators'];

  //--------------------------- context menu -- begin --------------------------
    run('user/menu');
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      $response['rows'][$i]['level']="<nobr><input name=filter_user_level_{$response['rows'][$i]['id']} value=\"{$response['rows'][$i]['level']}\" size=3><font size=+1>/{$this_site_info['cense_level']}</font></nobr>";
      //--------------------------- context menu -- begin ----------------------
        $response['rows'][$i]['context_menu']=menu_user($response['rows'][$i]);
      //--------------------------- context menu -- end ------------------------
    }
  //--------------------------- context menu -- end ----------------------------

$input_vars['page_content']= $re->draw_default_list($response);

//----------------------------- site context menu - begin ----------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- site context menu - end ------------------------
?>
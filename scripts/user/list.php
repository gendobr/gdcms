<?php
/*
  List of users
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

if(!is_admin()) return 0;


//---------------------------- deleting user - begin ---------------------------
$input_vars['delete_user_id']=isset($input_vars['delete_user_id'])?((int)$input_vars['delete_user_id']):0;
if($input_vars['delete_user_id']>1 && $input_vars['delete_user_id']!=$_SESSION['user_info']['id'])
{
  $query="DELETE FROM {$table_prefix}site_user WHERE user_id={$input_vars['delete_user_id']}";
  \e::db_execute($query);

  $query="DELETE FROM {$table_prefix}user WHERE id={$input_vars['delete_user_id']}";
  \e::db_execute($query);
  
  $input_vars['delete_user_id']=0;
}
//---------------------------- deleting user - end -----------------------------

//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->exclude='^delete_';
  $re->distinct=false;

  $re->from="{$table_prefix}user AS user";


  $re->add_field( $field='user.id'
                 ,$alias='id'
                 ,$type ='id:hidden=yes'
                 ,$label=$text['User_id']
                 ,$_group_operation=false);

  $re->add_field( $field='user.user_login'
                 ,$alias='user_login'
                 ,$type ='string'
                 ,$label=$text['User_Login']
                 ,$_group_operation=false);
                                                
  $re->add_field( $field='user.full_name'
                 ,$alias='full_name'
                 ,$type ='string'
                 ,$label=$text['Full_Name']
                 ,$_group_operation=false);

  $re->add_field( $field='user.telephone'
                 ,$alias='telephone'
                 ,$type ='string'
                 ,$label=$text['Telephone']
                 ,$_group_operation=false);

  $re->add_field( $field='user.email'
                 ,$alias='email'
                 ,$type ='string'
                 ,$label=$text['Email']
                 ,$_group_operation=false);

  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = $text['List_of_users'];
$input_vars['page_header'] = $text['List_of_users'];

  //--------------------------- context menu -- begin ----------------------------
    run('user/menu');
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_user($response['rows'][$i]);
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']= $re->draw_default_list($response);
?>
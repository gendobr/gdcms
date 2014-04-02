<?php
/* 
 * Manage notifiers
 */
$debug=false;
run('site/menu');

if(!is_logged())
{
   $input_vars['page_title']  =
   $input_vars['page_header'] =
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}


// ------------------ delete row - begin ---------------------------------------
   if(isset($input_vars['delete']))
   {
       $query="DELETE FROM {$table_prefix}listener WHERE listener_id=".( (int)$input_vars['delete'] );
       db_execute($query);
   }
// ------------------ delete row - end -----------------------------------------

$message='';
//------------------- create new records - begin -------------------------------
  if(isset($input_vars['new_notifier_email']))
  {
     //prn($input_vars);
     $new_listener_event=$input_vars['new_notifier_event'];
     $new_site_id=$input_vars['new_notifier_site'];
     $new_user_id=$_SESSION['user_info']['id'];
     $new_listener_sendto=$input_vars['new_notifier_email'];
     $new_listener_action='email';
     $new_listener_template='template_email_'.$input_vars['new_notifier_event'].'.html';
     if(strlen($new_listener_event)==0) $message.='<div style="color:red;">'.text('ERROR_new_listener_event_is_empty').'</div>';
     if(strlen($new_site_id)==0) $message.='<div style="color:red;">'.text('ERROR_new_site_id_is_empty').'</div>';
     if(strlen($new_listener_sendto)==0) $message.='<div style="color:red;">'.text('ERROR_new_sendto_is_empty').'</div>';
     if(!is_valid_email($new_listener_sendto)) $message.='<div style="color:red;">'.text('invalid_email_address').'</div>';

     if(strlen($message)==0)
     {
         $query="INSERT INTO {$table_prefix}listener(
                   listener_event,
                   site_id,
                   user_id,
                   listener_sendto,
                   listener_action,
                   listener_template)
                 VALUES(
                   '".DbStr($new_listener_event)."',
                    ".( (int)$new_site_id ).",
                    ".( (int)$new_user_id ).",
                   '".DbStr($new_listener_sendto)."',
                   '".DbStr($new_listener_action)."',
                   '".DbStr($new_listener_template)."' )";
         //prn($query);
         db_execute($query);
     }

  }

  if(isset($input_vars['new_notifier_telephone']))
  {
     //prn($input_vars);
     $new_listener_event=$input_vars['new_notifier_event'];
     $new_site_id=$input_vars['new_notifier_site'];
     $new_user_id=$_SESSION['user_info']['id'];
     $new_listener_sendto=sprintf($input_vars['new_notifier_operator'],substr(ereg_replace('[^0-9]','',$input_vars['new_notifier_telephone']), -7));
     $new_listener_action='sms';
     $new_listener_template='template_sms_'.$input_vars['new_notifier_event'].'.html';
     if(strlen($new_listener_event)==0) $message.='<div style="color:red;">'.text('ERROR_new_listener_event_is_empty').'</div>';
     if(strlen($new_site_id)==0) $message.='<div style="color:red;">'.text('ERROR_new_site_id_is_empty').'</div>';
     if(strlen($input_vars['new_notifier_telephone'])==0) $message.='<div style="color:red;">'.text('ERROR_new_telephone_is_empty').'</div>';
     //if(!is_valid_email($new_listener_sendto)) $message.='<div style="color:red;">'.text('invalid_email_address').'</div>';

     if(strlen($message)==0)
     {
         $query="INSERT INTO {$table_prefix}listener(
                   listener_event,
                   site_id,
                   user_id,
                   listener_sendto,
                   listener_action,
                   listener_template)
                 VALUES(
                   '".DbStr($new_listener_event)."',
                    ".( (int)$new_site_id ).",
                    ".( (int)$new_user_id ).",
                   '".DbStr($new_listener_sendto)."',
                   '".DbStr($new_listener_action)."',
                   '".DbStr($new_listener_template)."' )";
         //prn($query);
         db_execute($query);
     }

  }

//------------------- create new records - end ---------------------------------


//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  /*
    select l.*, s.dir, s.url, s.title, u.user_login
    from {$GLOBALS['table_prefix']}listener as l, {$GLOBALS['table_prefix']}site as s, {$GLOBALS['table_prefix']}user as u
    where l.site_id=s.id and u.id=l.user_id
  */
  $re->from="{$table_prefix}listener as l, {$table_prefix}site as s, {$table_prefix}user as u";
  $re->add_where(" l.site_id=s.id ");
  $re->add_where(" u.id=l.user_id ");
  $re->exclude='^delete|^new_notifier';

  if(!is_admin())
  {
      $sitelist=array_keys($_SESSION['user_info']['sites']);
      $cnt=count($sitelist);
      for($i=0;$i<$cnt;$i++) $sitelist[$i]*=1;
      $sitelist=array_unique($sitelist);
      $re->add_where(" l.site_id IN(".join(',',$sitelist).") ");

      $re->add_where(" l.user_id={$_SESSION['user_info']['id']} ");
  }

  $re->add_field( $field='l.listener_id'
                 ,$alias='listener_id'
                 ,$type ='id:hidden=no'
                 ,$label='#'
                 ,$_group_operation=false);

  # listener_event
    $tmp=explode(',',events);
    $listener_event_options=Array(''=>'');
    foreach($tmp as $ev) $listener_event_options[$ev]=$ev.'='.rawurlencode(text('Notifier_event_'.$ev));
    $re->add_field( $field='l.listener_event'
                   ,$alias='listener_event'
                   ,$type ='enum:'.join('&',$listener_event_options)
                   ,$label=text('listener_event')
                   ,$_group_operation=false);
  # site_id
  $re->add_field( $field='l.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label='site_id'
                 ,$_group_operation=false);

  if(is_admin())
  {
     # user_id
     $re->add_field( $field='l.user_id'
                    ,$alias='user_id'
                    ,$type ='id:hidden=yes'
                    ,$label='user_id'
                    ,$_group_operation=false);

     # u.user_login
     $re->add_field( $field='u.user_login'
                    ,$alias='user_login'
                    ,$type ='string'
                    ,$label=text('Login_name')
                    ,$_group_operation=false);
  }

  # listener_sendto
  $re->add_field( $field='l.listener_sendto'
                 ,$alias='listener_sendto'
                 ,$type ='string'
                 ,$label=text('listener_sendto')
                 ,$_group_operation=false);

  # listener_action
  $re->add_field( $field='l.listener_action'
                 ,$alias='listener_action'
                 ,$type ='string'
                 ,$label=text('listener_action')
                 ,$_group_operation=false);

  # listener_template
  $re->add_field( $field='l.listener_template'
                 ,$alias='listener_template'
                 ,$type ='string:hidden=yes'
                 ,$label=text('listener_template')
                 ,$_group_operation=false);


  # s.dir
  $re->add_field( $field='s.dir'
                 ,$alias='dir'
                 ,$type ='string'
                 ,$label=text('Site_directory')
                 ,$_group_operation=false);

  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------

//--------------------------- adjust list - begin ------------------------------
    $cnt=count($response['rows']);
    $delete_prefix='index.php?'.query_string('^delete|^new_notifier').'&delete=';
    for($i=0;$i<$cnt;$i++)
    {
        $response['rows'][$i]['context_menu']="<a href=\"".$delete_prefix.$response['rows'][$i]['listener_id']."\">".text('Delete')."</a>";
    }
//--------------------------- adjust list - end --------------------------------



$input_vars['page_content'] = $message;
$input_vars['page_content'].= $re->draw_default_list($response);


// -------------------------- forms to add notifiers - begin -------------------

   // -------------------- list of available sites - begin ---------------------
      $cnt=count($_SESSION['user_info']['sites'])/2;
      $site_options=array_chunk(array_keys($_SESSION['user_info']['sites']),$cnt);
      $site_options=array_combine($site_options[0], $site_options[1]);
      asort($site_options);
      //prn($site_options);
   // -------------------- list of available sites - end -----------------------

   // -------------------- list of available events - begin --------------------
      $tmp=explode(',',events);
      $listener_event_options=Array(''=>'');
      foreach($tmp as $ev) $listener_event_options[$ev]=text('Notifier_event_'.$ev);
      //prn($listener_event_options);
   // -------------------- list of available events - end ----------------------

   // -------------------- form to add email notifier - begin ------------------
      $input_vars['page_content'] .="
      <style>
      .lab{display:inline-block;width:130px;}
      </style>
      <form action='index.php' method='post'>
      ".hidden_form_elements('^new_notifier')."
      <input type=hidden name=new_notifier_action value='email'>
      <h3 style='text-align:left;margin-bottom:0px;'>".text('New_email_notifier').":</h3>
      <span class=lab>Email: </span><input type=text name=new_notifier_email value=''><br/>
      <span class=lab>".text('listener_event').": </span><select name=new_notifier_event>".draw_options('',$listener_event_options)."</select><br/>
      <span class=lab>".text('Site').": </span><select name=new_notifier_site><option value=''></option>".draw_options('',$site_options)."</select><br/>
      <input type=submit value=\"".text('Create')."\">
      </form>
      ";
   // -------------------- form to add email notifier - end --------------------

//   // -------------------- form to add SMS notifier - begin --------------------
//      $options=unserialize(email2sms);
//      $input_vars['page_content'] .="
//      <form action='index.php' method='post'>
//      ".hidden_form_elements('^new_notifier')."
//      <input type=hidden name=new_notifier_action value='sms'>
//      <h3 style='text-align:left;margin-bottom:0px;'>".text('New_SMS_notifier').":</h3>
//      <span class=lab>".text('Mobile_operator').": </span><select name=new_notifier_operator>".draw_options('',$options)."</select><br/>
//      <span class=lab>".text('Telephone').": </span><input type=text name=new_notifier_telephone value=''><br/>
//      <span class=lab>".text('listener_event').": </span><select name=new_notifier_event>".draw_options('',$listener_event_options)."</select><br/>
//      <span class=lab>".text('Site').": </span><select name=new_notifier_site><option value=''></option>".draw_options('',$site_options)."</select><br/>
//      <input type=submit value=\"".text('Create')."\">
//      </form>
//      ";
//   // -------------------- form to add SMS notifier - end ----------------------

// -------------------------- forms to add notifiers - end ---------------------

$input_vars['page_title']   =
$input_vars['page_header']  = text('List_of_notifiers');

?>

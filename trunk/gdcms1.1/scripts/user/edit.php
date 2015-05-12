<?php
/*
  Edit user properties
*/
if(!is_admin()) return 0;


//------------------- this_user_info - begin -----------------------------------
  $query = "SELECT * FROM {$table_prefix}user WHERE id=".checkInt($input_vars['user_id']);
  $this_user_info=db_getonerow($query);
  //prn($this_user_info);
//------------------- this_user_info - end -------------------------------------

//------------------- edit properties -- begin ---------------------------------
  run('lib/class_db_record_editor');
  run('lib/class_db_record_editor_extended');

  class edbre extends extended_db_record_editor
  {
    function check_form_values()
    {
      global $text,$db,$table_prefix, $input_vars, $this_user_info;
      $all_is_ok = true;

      //-------------------- email - begin -------------------------------------
        if(strlen($this->field['email']['value'])>0)
        {
           if(!is_valid_email($this->field['email']['value']))
           {
             $this->messages.= " <b><font color=red> {$text['ERROR']} : {$text['invalid_email']} \"{$this->field['email']['value']}\"</font></b><br>\n";
             $all_is_ok = false;
           }
        }
      //-------------------- email - end ---------------------------------------
      //-------------------- login name uniqueness - begin ---------------------
        if(strlen($this->field['user_login']['value'])>0)
        {
           $query="SELECT count(*) AS ns FROM {$table_prefix}user WHERE user_login='".$this->field['user_login']['value']."' AND id<>'".checkInt($this->id)."'";
           // prn($query);
           $count_site=db_getonerow($query);
           $count_site=$count_site['ns'];
           if($count_site>0)
           {
             $this->messages.= " <b><font color=red> {$text['ERROR']} : User_login_already exists</font></b><br>\n";
             $all_is_ok = false;
           }
        }
      //-------------------- login name uniqueness - end -----------------------



      return $all_is_ok;
    }
  }
  $rep=new edbre;
  $rep->use_db($db);
  $rep->debug=false;
  $rep->set_table("{$table_prefix}user");

  $rep->add_field( 'id'
                  ,'id'
                  ,'integer:hidden=yes&default='.$input_vars['user_id']
                  ,'#');

  $rep->add_field( 'user_login'
                  ,'user_login'
                  ,'string:maxlength=255&required=yes'
                  ,$text['User_login']);

  /*
  $rep->add_field( 'user_password'
                  ,'user_password'
                  ,'string:maxlength=32'
                  ,$text['Password']);
  */

  $rep->add_field( 'full_name'
                  ,'full_name'
                  ,'string:maxlength=255&required=yes'
                  ,$text['Full_Name']);

  $rep->add_field( 'telephone'
                  ,'telephone'
                  ,'string:maxlength=128&required=yes'
                  ,$text['Telephone']);

  $rep->add_field( 'email'
                  ,'email'
                  ,'string:maxlength=128&required=yes'
                  ,$text['Email']);
  //prn($rep);
  $rep->set_primary_key('id',$input_vars['user_id']);
  $success=$rep->process();
//------------------- edit properties -- end -----------------------------------
//prn($rep);

// ------------------ post-process - begin -------------------------------------
   $password_messages='';
   if($success)
   {
      #prn('posprocess',$input_vars);
      //-------------------- password - begin ----------------------------------
      if(isset($input_vars['db_record_editor_user_password']) && strlen($input_vars['db_record_editor_user_password'])>0)
      {
         if($input_vars['db_record_editor_user_password_again']==$input_vars['db_record_editor_user_password'])
         {
             $query="UPDATE {$table_prefix}user SET user_password='".md5($input_vars['db_record_editor_user_password'])."' WHERE user_login='".DbStr($rep->field['user_login']['value'])."'";
             #prn($query);
             db_execute($query);
         }
         else
         {
            $password_messages = " <b><font color=red> {$text['ERROR']} : passwords_do_not_match </font></b><br>\n";
         }
      }
      //-------------------- password - end ------------------------------------


      $query = "SELECT * FROM {$table_prefix}user WHERE user_login='".DbStr($rep->field['user_login']['value'])."'";
      //prn($query);
      $this_user_info=db_getonerow($query);

   }
// ------------------ post-process - end ---------------------------------------

//----------------------------- draw -- begin ----------------------------------
  $form=$rep->draw_form();
  $form['elements']['user_password'] = Array(
                    'field' => 'user_password'
                   ,'alias' => 'user_password'
                   ,'type' => 'string'
                   ,'label' => 'Change_password_to'//$text['Type_password_again']
                   ,'form_element_name' => 'db_record_editor_user_password'
                   ,'form_element_value' => ''
                   ,'value' => ''
                   ,'options' => Array('maxlength' => 32, 'password'=>'yes')
                );
  $form['elements']['user_password_again'] = Array(
                    'field' => 'user_password_again'
                   ,'alias' => 'user_password_again'
                   ,'type' => 'string'
                   ,'label' => 'Retype_new_password_again'//$text['Type_password_again']
                   ,'form_element_name' => 'db_record_editor_user_password_again'
                   ,'form_element_value' => ''
                   ,'value' => ''
                   ,'options' => Array('maxlength' => 32, 'password'=>'yes')
                );
  #prn($form);
  #prn($password_messages);
  if($password_messages!='')
  {
     #prn($password_messages);
     $form['messages']=$password_messages;
  }
  $form['hidden_elements']=$rep->hidden_fields('^user_id$') .
  "<input type=hidden name=user_id value=\"{$this_user_info['id']}\">\n";

  #$form['elements']['user_login']['comments']       = $text['user_login_manual'];
  #$form['elements']['user_password']['comments']    = $text['user_password_manual'];
  #$form['elements']['full_name']['comments']        = $text['full_name_manual'];
  #$form['elements']['telephone']['comments']        = $text['telephone_manual'];
  #$form['elements']['email']['comments']            = $text['email_manual'];

  //prn($form);
  $input_vars['page_title']   = $text['User_profile'];
  $input_vars['page_header']  = $text['User_profile'];
  $input_vars['page_content'] = $rep->draw($form);
//----------------------------- draw -- end ------------------------------------

//----------------------------- site context menu - begin ----------------------
  if($this_user_info)
  {
    $input_vars['page_menu']['user']=Array('title'=>$text['User_menu'],'items'=>Array());
    run('user/menu');
    $input_vars['page_menu']['user']['items'] = menu_user($this_user_info);
  }
//----------------------------- site context menu - end ------------------------

?>
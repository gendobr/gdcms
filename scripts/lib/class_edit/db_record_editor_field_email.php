<?php

/*
   Class to edit database record > email address field

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/

class db_record_editor_field_email extends db_record_editor_field_string{

  function db_record_editor_field_email($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_')
  {
    parent::db_record_editor_field_string($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_');
  }

  function check_custom($email)
  {
    if(strlen($email)==0) return '';
    $to_return=filter_var($email, FILTER_VALIDATE_EMAIL);
    if($to_return) return '';
    $this->all_is_ok=false;
    return sprintf('<b><font color=red>'.self::$text['ERROR_invalid_format_of'].'</font></b> ',$this->label);
  }
}
?>
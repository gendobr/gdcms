<?php

/*
   Class to edit database record > url address field

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/

class db_record_editor_field_url extends db_record_editor_field_string{

  function db_record_editor_field_url($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_'){
    parent::db_record_editor_field_string($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_');
  }

  function check_custom($url)
  {
    if(strlen($url)==0) return '';
    if(is_valid_url($url)) return '';
    $this->all_is_ok=false;
    return sprintf('<b><font color=red>'.self::$text['ERROR_invalid_format_of'].'</font></b> ',$this->label);
  }
  function is_valid_url($url){
    return filter_var($url, FILTER_VALIDATE_URL);
  }
}
?>
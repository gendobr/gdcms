<?php
/*
   Class to edit database record > unix_timestamp

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/

# options : max=...&min=...&default=...&required=(yes|no)
class db_record_editor_field_unix_timestamp extends db_record_editor_field
{

  var $datetime_format='Y-m-d H:i:s';
  var $datetime;

  function db_record_editor_field_unix_timestamp($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_')
  {
     $this->field_init($_field,$_alias,$_ttype,$_label,$form_name_prefix);

   # $this->text['ERROR_value_of_is_greater_than']='ERROR: Value of %s is greater than %s';
   # $this->text['ERROR_value_of_is_less_than']='ERROR: Value of %s is less than %s';
   # $this->text['ERROR_invalid_format_of']='ERROR: Value of %s has invalid format';

     //$this->text['ERROR_value_of_is_greater_than']='ОШИБКА: Значение параметра %s больше чем %s';
     //$this->text['ERROR_value_of_is_less_than']='ОШИБКА: Значение параметра %s меньше чем %s';
     //$this->text['ERROR_invalid_format_of']='ОШИБКА: Неправильная форма параметра %s ';


   # ------------------- set default value - begin -----------------------------
     if(isset($this->options['default'])) $this->options['default']=$this->datetime_value($this->options['default']); else $this->options['default']=false;
     if($this->options['default']) $this->value=$this->form_element_value=$this->options['default'];
   # ------------------- set default value - begin -----------------------------

   # check format of maximal date
   # and convert it to unix timestamp
     if(isset($this->options['max'])) $this->options['max']=$this->timestamp($this->options['max']); else $this->options['max']=false;

   # check format of minimal date
   # and convert it to unix timestamp
     if(isset($this->options['min'])) $this->options['min']=$this->timestamp($this->options['min']); else $this->options['min']=false;
  }



  // ------------------- check posted data - begin -----------------------------
  function check_type($_data)
  {
    $messages='';
    $timestamp=$this->unix_timestamp($_data);
    # ------------ check format of the posted date - begin ---------------------
    # check format and convert it to unix timestamp
      if($timestamp===false)
      {
         $this->all_is_ok=false;
         $messages.="<font color=red><b>"
                   .sprintf(self::$text['ERROR_invalid_format_of'],$this->label)
                   ."</b></font><br/>\r\n";
         return $messages;
      }
    # ------------ check format of the posted date - end -----------------------

    # ----------------- minimal value - begin ----------------------------------
      if($this->options['max'] && $timestamp>$this->options['max'])
      {
         $this->all_is_ok=false;
         $messages.="<font color=red><b>"
                    .sprintf(self::$text['ERROR_value_of_is_greater_than']
                            ,$this->label
                            ,date($this->datetime_format,$this->options['max']))
                    ."</b></font><br/>\r\n";
      }
    # ---------------- minimal value - end -------------------------------------
    # ----------------- maximal value - begin ----------------------------------
      if($this->options['min'] && $timestamp<$this->options['min'])
      {
         $this->all_is_ok=false;
         $messages.="<font color=red><b>"
                   .sprintf(self::$text['ERROR_value_of_is_less_than']
                           ,$this->label
                           ,date($this->datetime_format,$this->options['min']))
                   ."</b></font><br/>\r\n";
      }
    # ---------------- maximal value - end -------------------------------------
    return $messages;
  }

  function datetime_value($val)
  {
     if (!(($timestamp = strtotime($val)) === -1) ) return date($this->datetime_format,$timestamp);
     else return false;
  }
  function unix_timestamp($val)
  {
     if (!(($timestamp = strtotime($val)) === -1) ) return $timestamp;
     else return false;
  }


# ------------------- these functions can be replaced - begin ------------------
  function set_value($new_value)
  {
    $this->value=$this->unix_timestamp($new_value);
    $this->datetime=date($this->datetime_format,$this->value);

    $this->form_element_value=$this->htmlencode($this->value);
    if(count($this->enum)>0) $this->form_element_options=$this->draw_options($this->value,$this->enum);
  }



  function sql_value()
  {
    if($this->timestamp===false) return 'NULL';
    else return $this->timestamp;
  }


 ////function check_custom($posted_data){return '';}

  function draw($t=''){

   if(!isset($this->options['hidden'])) $this->options['hidden']='no';
   if($this->options['hidden']=='yes') return "<input type=hidden name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">";

   if(count($this->enum)==0) $str="<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">";
   else $str="<select name=\"{$this->form_element_name}\"><option value=''></option>{$this->form_element_options}</select>";

   if(isset($this->options['required']) && $this->options['required']=='yes') $this->label.="<sup style='color:red;weight:bold;'>*</sup>";

   $str=sprintf($this->template
               ,$this->label
               ,"<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">"
               ,$this->message);
   return $str;
  }
}

?>
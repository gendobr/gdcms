<?php
/*
   Class to edit database record > string field

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/

# options : max=...&min=...&default=...&required=(yes|no)
class db_record_editor_field_string extends db_record_editor_field
{

  # -------------------------- constructor - begin -----------------------------
  function db_record_editor_field_string($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_')
  {
     $this->field_init($_field,$_alias,$_ttype,$_label,$form_name_prefix);

   # $this->text['ERROR_value_of_is_longer_than']='ERROR: Value of %s is longer than %s';
     $this->text['ERROR_value_of_is_longer_than']='ОШИБКА: Значение строки %s длинее чем %s';


   # ------------------- set default value - begin -----------------------------
     if(isset($this->options['default']))
     {
       $this->value=$this->options['default'];
       $this->form_element_value=$this->htmlencode($this->value);
     }
   # ------------------- set default value - begin -----------------------------
  }
  # -------------------------- constructor - end -------------------------------



  # ------------------- check data - begin -------------------------------------
  function check_type($posted_data)
  {
    $messages='';
    //------------------ check maximal length - begin --------------------------
      if(isset($this->options['maxlength']))
      {
        $this->options['maxlength'] = (int)($this->options['maxlength']);
        if(strlen($posted_data)>$this->options['maxlength'] && $this->options['maxlength']>0)
        {
          $this->all_is_ok=false;
          $messages.="<font color=red><b>"
                     .sprintf($this->text['ERROR_value_of_is_longer_than']
                             ,$_label
                             ,$this->options['maxlength'])
                     ."</b></font><br/>";
        }
      }
    //------------------ check maximal length - end ----------------------------
    return $messages;
  }
  # ------------------- check data - end ---------------------------------------



  function sql_value(){ return "'".mysql_real_escape_string($this->value)."'"; }



# ------------------- these functions can be replaced - begin ------------------
 function set_value($new_value)
 {
   $this->value=$new_value;

   // remove html tags
      if(   isset($this->options['html_denied'])
         && $this->options['html_denied']=='yes')
            $this->value=strip_tags($this->value);

   $this->form_element_value=$this->htmlencode($this->value);
   if(count($this->enum)>0) $this->form_element_options=$this->draw_options($this->value,$this->enum);
 }


# ------------------- these functions can be replaced - end --------------------


  function draw($template='')
  {
      if(!isset($this->options['hidden'])) $this->options['hidden']='no';
      if($this->options['hidden']=='yes') return "<input type=hidden name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">";

      if(count($this->enum)==0) $str="<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\" style='width:200pt;'>";
      else $str="<select name=\"{$this->form_element_name}\"><option value=''></option>{$this->form_element_options}</select>";

      if(isset($this->options['required']) && $this->options['required']=='yes') $this->label.="<sup style='color:red;weight:bold;'>*</sup>";

      $str=sprintf($this->template
                  ,$this->label
                  ,$str
                  ,$this->message);
      return $str;
  }
///// function draw_default_template()  {  }
///// function check_custom($posted_data){return '';}

}

?>
<?php
/*
   Class to edit database record > integer field

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/

# options : max=...&min=...&default=...&required=(yes|no)
class db_record_editor_field_integer extends db_record_editor_field
{

  function db_record_editor_field_integer($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_')
  {
     $this->field_init($_field,$_alias,$_ttype,$_label,$form_name_prefix);

   # $this->text['ERROR_value_of_is_greater_than']='ERROR: Value of %s is greater than %s';
   # $this->text['ERROR_value_of_is_less_than']='ERROR: Value of %s is less than %s';

     $this->text['ERROR_value_of_is_greater_than']='ОШИБКА: Значение параметра %s больше чем %s';
     $this->text['ERROR_value_of_is_less_than']='ОШИБКА: Значение параметра %s меньше чем %s';

   # ------------------- set default value - begin -----------------------------
     if(isset($this->options['default']))
     {
       $this->value=$this->form_element_value=check_float($this->options['default']);
     }
   # ------------------- set default value - begin -----------------------------
  }



  // ------------------- check posted data - begin -----------------------------
  function check_type($_data)
  {
    $posted_data=(int)($_data);
    $messages='';

    // ----------------- minimal value - begin ---------------------------------
      if(isset($this->options['max']))
      {
        $this->options['max']=(int)$this->options['max'];
        if($posted_data>$this->options['max'])
        {
           $this->all_is_ok=false;
           $messages.="<font color=red><b>"
                      .sprintf($this->text['ERROR_value_of_is_greater_than']
                              ,$this->label
                              ,$this->options['max'])
                      ."</b></font><br/>\r\n";
        }
      }
    // ---------------- minimal value - end ------------------------------------

    // ----------------- maximal value - begin ---------------------------------
       if(isset($this->options['min']))
       {
         $this->options['min']=(int)$this->options['min'];
         if($posted_data<$this->options['min'])
         {
            $this->all_is_ok=false;
            $messages.="<font color=red><b>"
                       .sprintf($this->text['ERROR_value_of_is_less_than']
                               ,$this->label
                               ,$this->options['min'])
                       ."</b></font><br/>\r\n";
         }
       }
    // ---------------- maximal value - end ------------------------------------
    return $messages;
  }


  function sql_value(){ return (int)($this->value); }

  function set_value($new_value)
  {
    $this->value=(int)$new_value;

    $this->form_element_value=$this->value;
    if(count($this->enum)>0) $this->form_element_options=$this->draw_options($this->value,$this->enum);
  }

  # template is like " {$label} : <input type=text name={$form_element_name} value={$form_element_value>}"
  function draw($template=''){

   if(!isset($this->options['hidden'])) $this->options['hidden']='no';
   if($this->options['hidden']=='yes') return "<input type=hidden name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">";
   #prn($this->alias,$this->enum);
   if(count($this->enum)==0) $str="<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">";
   else $str="<select name=\"{$this->form_element_name}\"><option value=''></option>{$this->form_element_options}</select>";

   if(isset($this->options['required']) && $this->options['required']=='yes') $this->label.="<sup style='color:red;weight:bold;'>*</sup>";

   $str=sprintf($this->template
               ,$this->label
               ,$str
               ,$this->message);


   return $str;
  }
 ////function draw_default_template()  {  }
 ////function check_custom($posted_data){return '';}

}

?>
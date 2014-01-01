<?php
/*
   Class to edit database record > string > textarea

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/


class db_record_editor_field_textarea extends db_record_editor_field_string{

  function db_record_editor_field_textarea($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_')
  {
    parent::db_record_editor_field_string($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_');
  }

 # template is like " {$label} : <input type=text name={$form_element_name} value={$form_element_value>}"
 function draw($t=''){
   if(!isset($this->options['hidden'])) $this->options['hidden']='no';
   if($this->options['hidden']=='yes') return "<input type=hidden name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">";

   if(!isset($this->options['rows'])) $this->options['rows']=5;
   $this->options['rows']=abs((int)$this->options['rows']);

   if(!isset($this->options['cols'])) $this->options['cols']=30;
   $this->options['cols']=abs((int)$this->options['cols']);

   if(isset($this->options['required']) && $this->options['required']=='yes') $this->label.="<sup style='color:red;weight:bold;'>*</sup>";

   $str=sprintf($this->template
               ,$this->label
               ,"<textarea name=\"{$this->form_element_name}\" rows={$this->options['rows']} cols={$this->options['cols']}>{$this->form_element_value}</textarea>"
               ,$this->message);

   return $str;
 }





  function draw_default_template()
  {
      if(!isset($this->options['hidden'])) $this->options['hidden']='no';
      if($this->options['hidden']=='yes') return "<input type=hidden name=\"{\$form.elements.{$this->alias}->form_element_name}\" value=\"{\$form.elements.{$this->alias}->form_element_value}\">";


      $str="<textarea name=\"{\$form.elements.{$this->alias}->form_element_name}\">{\$form.elements.{$this->alias}->form_element_value}</textarea>";
      $str=sprintf($this->template
                  ,"{\$form.elements.{$this->alias}->label}"
                  ,$str
                  ,"{\$form.elements.{$this->alias}->message}");
      return $str;
  }

}
?>
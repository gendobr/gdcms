<?php
/*
   Class to edit database record > common field properties

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/


class db_record_editor_field extends db_record_editor_common
{
 # field name
   var $field;

 # alias
   var $alias;
   
 # type
   var $type;
   
 # label
   var $label;

 # value
   var $value;

 # form element name
   var $form_element_name;

 # form element value
   var $form_element_value;

 # options for <select> tag ( if set of pre-defined values exists)
   var $form_element_options;

 # options
   var $options;

 # set of predefined values
   var $enum=Array();
 
 # form name prefix
   var $form_name_prefix='db_record_editor_';

 # error message
   var $message='';

 # if the posted data is correct
   var $all_is_ok=true;
   
 # posted data
   var $posted_data;

 # in primary key
   var $primary_key=false;

  public static $text=Array(
  # 'ERROR_value_of_is_empty'=>'ERROR: Value of %s is empty'
  #,'ERROR_forbidden_value_of'=>'ERROR: Forbidden value of  %s'
  #,'ERROR_data_not_posted'=>'ERROR: Parameter %s is not posted'
    'ERROR_value_of_is_empty'=>'ОШИБКА: Пустое поле %s '
   ,'ERROR_forbidden_value_of'=>'ОШИБКА: Недопустимое значение параметра %s'
   ,'ERROR_data_not_posted'=>'ОШИБКА: Параметр %s не задан'
   ,'ERROR_invalid_format_of'=>'ОШИБКА:  %s имеет неправильную форму'

  );
  var $template="
  <tr><td valign=top><b>%s</b></td><td valign=top>%s</td><td valign=top>%s</td></tr>
  ";

# -------------------------------- conctructor - begin -------------------------
 function field_init($_field,$_alias,$_ttype,$_label,$form_name_prefix='db_record_editor_')
 {
   // --------------- parse type descrpition options -- begin ------------------
      $_type=explode(':',$_ttype);
      if(count($_type)==1) $_type[1]='';
      $_options=$_type[1];
      $_type=$_type[0];
   // --------------- parse type descrpition options -- end --------------------
   // --------------- extract ENUM values - begin ------------------------------
      $_enum=Array();
      $_type=explode('(',preg_replace('/\)$/','',$_type));
      if(count($_type)>1) $_enum=explode('&',$_type[1]);
      $_type=$_type[0];
   // --------------- extract ENUM values - end --------------------------------

   $this->field=$_field; 
   $this->form_name_prefix=$form_name_prefix;
   $this->alias=$_alias; 
   $this->type=$_type;
   $this->label=$_label;
   $this->form_element_name=$this->form_name_prefix.$_alias;
   $this->form_element_value='';
   $this->value='';
   $this->messages='';

   // ----------------- split options -- begin ---------------------------------           
      $_options=explode('&',$_options);
      $this->options=Array();
      foreach($_options as $val)
      {
        $oops=explode('=',$val);
        if(count($oops)>1) $this->options[$oops[0]]=rawurldecode($oops[1]);
      }
          
      if(count($_enum)>0)
      {
         foreach($_enum as $val)
         {
            $oops=explode('=',$val);
            $this->enum[$oops[0]]=rawurldecode($oops[1]);
         }
         $this->form_element_options=$this->draw_options('',$this->enum);
      }

   // ------------------ split options -- end ----------------------------------

     

 }
# -------------------------------- conctructor - end ---------------------------




 function load_posted_data()
 {
   // ------------------ get posted data - begin -------------------------------
      global $_GET,$_POST,$_SERVER;
      $input_vars=array_merge($_GET,$_POST);
      # prn($this->form_element_name,$input_vars[$this->form_element_name]);
      if(isset($input_vars[$this->form_element_name]))
      {
        $this->posted_data=get_magic_quotes_gpc ()?stripslashes($input_vars[$this->form_element_name]):$input_vars[$this->form_element_name];
      # prn('$this->posted_data',$this->posted_data);
        if($this->check($this->posted_data)) $this->set_value($this->posted_data);
        else $this->form_element_value=$this->htmlencode($this->posted_data);
      }
      else
      {
         $this->all_is_ok=false;
         $this->messages.="<font color=red><b>"
                         .sprintf(self::$text['ERROR_data_not_posted'],$this->label)
                         ."</b></font><br/>";
      }
   // ------------------ get posted data - end ---------------------------------

 }
 
 function check($posted_data)
 {
   // ------------------- common checks - begin --------------------------------
      //------------------ check if value is defined - begin -------------------
        if(!isset($this->options['required'])) $this->options['required']='no';
        if($this->options['required']=='yes' && strlen($posted_data)==0)
        {
           $this->all_is_ok=false;
           $this->messages.="<font color=red><b>"
                           .sprintf(self::$text['ERROR_value_of_is_empty'],$this->label)
                           ."</b></font><br/>";
        }                  
      //------------------ check if value is defined - end ---------------------

      //------------- check if value is allowed - begin ------------------------
        if(count($this->enum)>0 && (strlen($posted_data)>0) && !isset($this->enum[$posted_data]))
        {
           #prn('$this->field=',$this,
           #    '$_REQUEST[]='.$_REQUEST[$this->form_element_name],
           #    '$posted_data='.$posted_data);
           $this->all_is_ok=false;
           $this->messages.="<font color=red><b>"
                           .sprintf(self::$text['ERROR_forbidden_value_of'],$this->label)
                           ."</b></font><br/>";
        }
      //------------- check if value is allowed - end --------------------------
   // ------------------- common checks - end ----------------------------------
   $this->messages.=$this->check_type($posted_data);
   $this->messages.=$this->check_custom($posted_data);
   #prn('check returns:'.$this->all_is_ok.';');
   return $this->all_is_ok;
 }


# ------------------- these functions can be replaced - begin ------------------ 
 function set_value($new_value)
 {
   $this->value=$new_value;
   $this->form_element_value=$this->htmlencode($new_value);
   if(count($this->enum)>0) $this->form_element_options=$this->draw_options($this->value,$this->enum);
 }
 
 function check_custom($posted_data){return '';}
 
 function check_type($posted_data){ return '';}
 
 function sql_value(){ return "'".mysql_escape_string($this->value)."'"; }
 
 # template is like " {$label} : <input type=text name={$form_element_name} value={$form_element_value>}"
 function draw($template=''){ return ''; }


 # run after record is saved
 function postprocess(){return '';}

  function draw_default_template()
  {
      if(!isset($this->options['hidden'])) $this->options['hidden']='no';
      if($this->options['hidden']=='yes') return "<input type=hidden name=\"{\$form.elements.{$this->alias}->form_element_name}\" value=\"{\$form.elements.{$this->alias}->form_element_value}\">";
   
      if(count($this->enum)==0) $str="<input type=text name=\"{\$form.elements.{$this->alias}->form_element_name}\" value=\"{\$form.elements.{$this->alias}->form_element_value}\">";
      else $str="<select name=\"{\$form.elements.{$this->alias}->form_element_name}\">{\$form.elements.{$this->alias}->form_element_options}</select>"; 
   
      $str=sprintf($this->template
                  ,"{\$form.elements.{$this->alias}->label}"
                  ,$str
                  ,"{\$form.elements.{$this->alias}->message}");
      return $str;
  }


# ------------------- these functions can be replaced - end -------------------- 

}

?>
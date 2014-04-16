<?php

/*
  Class to edit database record > email address field

  developed by webous agency
  http://webous.com/
  author: Gennadiy Dobrovolsky
  e-mail: val@webous.com
  date:   September 27, 2006

 */

class db_record_editor_field_password extends db_record_editor_field_string {

    function db_record_editor_field_password($_field, $_alias, $_ttype, $_label, $form_name_prefix = 'db_record_editor_') {
        parent::db_record_editor_field_string($_field, $_alias, $_ttype, $_label, $form_name_prefix = 'db_record_editor_');
    }

    function check_custom($password) {
        if (strlen($password) == 0)
            return '';

        if ($password == $_REQUEST["{$this->form_element_name}_again"])
            return '';

        return sprintf('<b><font color=red>' . self::$text['ERROR_invalid_format_of'] . '</font></b> ', $this->label);
    }

    function sql_value() {
        return "'" . md5($this->value) . "'";
    }

    function draw($t = '') {
        if (!isset($this->options['hidden']))
            $this->options['hidden'] = 'no';
        $str = "<input type=text name=\"{$this->form_element_name}\" value=\"\"><br/>"
                . "<input type=text name=\"{$this->form_element_name}_again\" value=\"\">";

        if (isset($this->options['required']) && $this->options['required'] == 'yes') {
            $this->label.="<sup style='color:red;weight:bold;'>*</sup>";
        }

        $str = sprintf($this->template
                , $this->label
                , $str
                , $this->message);
        return $str;
    }

}

?>
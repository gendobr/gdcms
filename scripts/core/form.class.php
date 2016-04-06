<?php

namespace core;

/**
 * Form utilities
 */
class form {

    static function hidden_form_elements($exclude_pattern) {
        $data = \e::query_array($exclude_pattern);
        $tor = Array();
        foreach ($data as $key => $val) {
            $tor[] = "<input type=\"hidden\" name=\"" . htmlspecialchars($key) . "\" value=\"" . htmlspecialchars($val) . "\">\n";
        }
        return join('', $tor);
    }

    // ------------------ draw options for <select> -- begin -------------------
    /*
     * $options=Array(0=>'Неділя',1=>'Понеділок',2=>'Вівторок',3=>'Середа');
     * echo "<select name=d>".draw_options(3,$options).'</select>';
     *
     */
    static function draw_options($value, $options) {
        $to_return = '';
        foreach ($options as $key => $val) {
            if (is_array($val)) {
                $val = array_values(array_unique($val));
                if (!isset($val[1]))
                    $val[1] = $val[0];
                if ($val[0] == $value && strlen($val[0]) == strlen($value))
                    $selected = ' selected ';
                else
                    $selected = '';
                $to_return.="<option value=\"" . htmlspecialchars(trim($val[0])) . "\" $selected>{$val[1]}</option>\n";
            }
            else {
                if ($key == $value && strlen($key) == strlen($value))
                    $selected = ' selected ';
                else
                    $selected = '';
                $to_return.="<option value=\"" . htmlspecialchars(trim($key)) . "\" $selected>$val</option>\n";
            }
        }
        return $to_return;
    }

    // ------------------ draw options for <select> -- end ---------------------

    static function draw_radio($value, $options, $name) {
        $to_return = '';
        // prn($options);
        foreach ($options as $key => $val) {
            if (is_array($val)) {
                $val = array_values($val);
                if (!isset($val[1]))
                    $val[1] = $val[0];
                if ($val[0] == $value && strlen($val[0]) == strlen($value))
                    $selected = ' checked ';
                else
                    $selected = '';
                $to_return.="<label><input type=radio name=\"{$name}\" value=\"" . htmlspecialchars(trim($val[0])) . "\" $selected> {$val[1]}</label>\n";
            }
            else {
                if ($key == $value && strlen($key) == strlen($value))
                    $selected = ' checked ';
                else
                    $selected = '';
                $to_return.="<label><input type=radio name=\"{$name}\" value=\"" . htmlspecialchars(trim($key)) . "\" $selected> $val</label>\n";
            }
        }
        return $to_return;
    }

    static function draw_checkbox_set($values, $options, $name) {
        $to_return = '';
        // \e::info($values);
        // \e::info($options);
        foreach ($options as $key => $val) {
            $uid='ch_'.time().  rand(0, 100000).'_'.$key;
            if (is_array($val)) {
                $val = array_values($val);
                if (!isset($val[1])) {
                    $val[1] = $val[0];
                }
                $selected = '';
                foreach($values as $value){
                    if ($val[0] == $value && strlen($val[0]) == strlen($value)) {
                        $selected = ' checked ';
                    }
                }
                $to_return.="<input type=\"checkbox\" name=\"{$name}\" id=\"{$uid}\" value=\"" . htmlspecialchars(trim($val[0])) . "\" $selected> <label for=\"{$uid}\"> {$val[1]}</label>\n";
            } else {
                $selected = '';
                foreach($values as $value){
                    if ($key == $value && strlen($key) == strlen($value)) {
                        $selected = ' checked ';
                    }
                }
                $to_return.="<input type=\"checkbox\" name=\"{$name}\" id=\"{$uid}\" value=\"" . htmlspecialchars(trim($key)) . "\" $selected> <label for=\"{$uid}\"> $val</label>\n";
            }
        }
        return $to_return;
    }

}


<?php

// ---------------------------- class Report -- begin --------------------------
/*

  Class Report generates report structure


 */
class db_record_editor {

    var $field = Array(); // filelds
    // $field[*]=Array('field'=>...,
    //                 'alias'=>...,
    //                 'type'=>'(integer|float|string|enum|datetime|unix_timestamp)(:[options])?',
    //                 'label'=>'',
    //                 'value'=>'',
    //                 'form_name'=>'',
    //                 'form_value'=>)
    // [options] are :
    //                 if type==enum
    //                 {
    //                    option_value=option_text(&option_value=option_text)*
    //                    this list will be used to form list of options in
    //                    <select> HTML tag
    //                 }
    //                 if type==string
    //                 {
    //                    maxlength=[integer]&default=[default_value]&required=yes&html_denied=yes
    //                 }
    //                 if type==integer or type=float
    //                 {
    //                    max=[number1]&min=[number2]&default=[default_value]
    //                 }
    //                 if type==datetime
    //                 {
    //                    max=[number1]&min=[number2]&default=[default_value]&required=yes
    //                 }
    var $table;              // table name
    var $id;                 // primary key value
    var $primary_key = false;  // primary key alias
    var $db;
    var $where_str;
    var $form_name_prefix = 'db_record_editor_';
    var $record_is_found = false;
    var $debug = false;
    var $datetime_format = 'Y-m-d H:i:s';
    var $exclude = '^$';
    var $messages = '';
    var $all_is_ok = true;

// ---------------------- user-defined functions -- begin ----------------------
    function check_form_values() {
        return true;
    }

    function process_db_values() {
        return true;
    }

    function create_primary_key() {
        //return $this->db->GenID("{$this->table}_seq");
    }

// ---------------------- user-defined functions -- end ------------------------
// ------------------ add field to list -- begin -------------------------------
    function add_field($_field, $_alias, $_ttype, $_label) {
        //prn($_ttype);
        global $_GET, $_POST, $_SERVER;
        $input_vars = array_merge($_GET, $_POST);
        //prn($input_vars);
        //--------------- extract options -- begin -----------------------------
        $_type = explode(':', $_ttype);
        if (!isset($_type[1]))
            $_type[1] = '';
        $_options = $_type[1];
        $_type = $_type[0];
        //if($_type=='enum')     die('==>'.$_type);
        //--------------- extract options -- end -------------------------------

        $form_element_name = $this->form_name_prefix . $_alias;

        $tor = Array('field' => $_field,
            'alias' => $_alias,
            'type' => $_type,
            'label' => $_label,
            'form_element_name' => $form_element_name,
            'form_element_value' => '',
            'value' => '');
        //------------------ split options -- begin ----------------------------
        $_options = explode('&', $_options);
        $tor['options'] = Array();
        foreach ($_options as $val) {
            $oops = explode('=', $val);
            if (isset($oops[0]) && isset($oops[1])) {
                $tor['options'][$oops[0]] = rawurldecode($oops[1]);
            }
        }
        //------------------ split options -- end ------------------------------

        unset($posted_data);
        if (isset($input_vars[$form_element_name]))
            $posted_data = get_magic_quotes_gpc() ? stripslashes($input_vars[$form_element_name]) : $input_vars[$form_element_name];

        switch ($_type) {
            case 'string':
                if (isset($posted_data)) {
                    //if(get_magic_quotes_gpc()) $posted_data=stripslashes($posted_data);

                    $tor['options']['maxlength'] = isset($tor['options']['maxlength']) ? $this->checkInt($tor['options']['maxlength']) : 0;
                    if (strlen($posted_data) > 2 * $tor['options']['maxlength'] && $tor['options']['maxlength'] > 0) {
                        $this->all_is_ok = false;
                        $this->messages.="<font color=red><b>ERROR : Length of {$_label} is greater than {$tor['options']['maxlength']}</b></font><br/>";
                    }

                    if (!isset($tor['options']['required']))
                        $tor['options']['required'] = 'no';
                    if ($tor['options']['required'] == 'yes' && strlen($posted_data) == 0) {
                        $this->all_is_ok = false;
                        $this->messages.="<font color=red><b>ERROR : value of {$_label} is empty</b></font><br/>";
                    }
                } else {
                    if (!isset($tor['options']['default']))
                        $tor['options']['default'] = '';
                    $posted_data = $tor['options']['default'];
                }
                $tor['form_element_value'] = $this->checkStr($posted_data);
                if (!isset($tor['options']['html_denied']))
                    $tor['options']['html_denied'] = 'no';
                if ($tor['options']['html_denied'] == 'yes')
                    $tor['value'] = $this->checkStr($posted_data);
                else
                    $tor['value'] = $posted_data;
                break;

            case 'enum':

                if (isset($posted_data)) {
                    //if(get_magic_quotes_gpc()) $posted_data=stripslashes($posted_data);
                    if (strlen($posted_data) > 0 && !in_array($posted_data, array_keys($tor['options']))) {
                        $this->all_is_ok = false;
                        $this->messages.="<font color=red><b>ERROR : forbidden value of {$_label}</b></font><br/>";
                    }
                }
                if (!isset($posted_data))
                    $posted_data = '';
                if (isset($tor['options'][$posted_data])) {
                    $tor['form_element_value'] = $this->checkStr($tor['options'][$posted_data]);
                } else {
                    $tor['form_element_value'] = '';
                }
                $tor['value'] = $posted_data;
                $tor['form_element_options'] = $this->draw_options($tor['value'], $tor['options']);
                break;

            case 'integer':
                if (isset($posted_data)) {
                    $posted_data = $this->checkInt($posted_data);
                    if (isset($tor['options']['max'])) {
                        $tor['options']['max'] = $this->checkInt($tor['options']['max']);
                        if ($posted_data > $tor['options']['max']) {
                            $this->all_is_ok = false;
                            $this->messages.="<font color=red><b>ERROR : value of {$_label} is greater than {$tor['options']['max']}</b></font><br/>";
                        }
                    }
                    if (isset($tor['options']['min'])) {
                        $tor['options']['min'] = $this->checkInt($tor['options']['min']);
                        if ($posted_data < $tor['options']['min']) {
                            $this->all_is_ok = false;
                            $this->messages.="<font color=red><b>ERROR : value of {$_label} is less than {$tor['options']['min']}</b></font><br/>";
                        }
                    }
                } else {
                    $posted_data = '';
                    if (isset($tor['options']['default']))
                        $posted_data = $this->checkInt($tor['options']['default']);
                }
                $tor['value'] = $tor['form_element_value'] = $posted_data;
                break;

            case 'float':
                if (isset($posted_data)) {
                    $posted_data = $this->checkFloat($posted_data);
                    if (isset($tor['options']['max'])) {
                        $tor['options']['max'] = $this->checkFloat($tor['options']['max']);
                        if ($posted_data > $tor['options']['max']) {
                            $this->all_is_ok = false;
                            $this->messages.="<font color=red><b>ERROR : value of {$_label} is greater than {$tor['options']['max']}</b></font><br/>";
                        }
                    }
                    if (isset($tor['options']['min'])) {
                        $tor['options']['min'] = $this->checkFloat($tor['options']['min']);
                        if ($posted_data < $tor['options']['min']) {
                            $this->all_is_ok = false;
                            $this->messages.="<font color=red><b>ERROR : value of {$_label} is less than {$tor['options']['min']}</b></font><br/>";
                        }
                    }
                } else {
                    if (isset($tor['options']['default']))
                        $posted_data = $this->checkFloat($tor['options']['default']);
                }
                $tor['value'] = $tor['form_element_value'] = $posted_data;
                break;

            case 'unix_timastamp':
                if (isset($posted_data)) {
                    if (strlen(trim($posted_data)) == 0 && $tor['options']['required'] == 'yes') {
                        $this->all_is_ok = false;
                        $this->messages.="<font color=red><b>ERROR : {$_label} is empty</b></font><br/>";
                    } else {
                        $posted_data = $this->checkDatetime($posted_data);
                        if (isset($tor['options']['max'])) {
                            $tor['options']['max'] = strtotime($this->checkDatetime($tor['options']['max']));
                            if ($posted_data > $tor['options']['max']) {
                                $this->all_is_ok = false;
                                $this->messages.="<font color=red><b>ERROR : value of {$_label} is greater than {$tor['options']['max']}</b></font><br/>";
                            }
                        }
                        if (isset($tor['options']['min'])) {
                            $tor['options']['min'] = strtotime($this->checkDatetime($tor['options']['min']));
                            if ($posted_data < $tor['options']['min']) {
                                $this->all_is_ok = false;
                                $this->messages.="<font color=red><b>ERROR : value of {$_label} is less than {$tor['options']['min']}</b></font><br/>";
                            }
                        }
                    }
                } else {
                    if (isset($tor['options']['default'])) {
                        $posted_data = $this->checkDatetime($tor['options']['default']);
                    }
                }
                $tor['value'] = $tor['form_element_value'] = strtotime($posted_data);
                //prn($tor);
                break;

            case 'datetime':
                if (isset($posted_data)) {
                    if (strlen(trim($posted_data)) == 0 && $tor['options']['required'] == 'yes') {
                        $this->all_is_ok = false;
                        $this->messages.="<font color=red><b>ERROR : {$_label} is empty</b></font><br/>";
                    } else {
                        $posted_data = $this->checkDatetime($posted_data);
                        if (isset($tor['options']['max'])) {
                            $tor['options']['max'] = $this->checkDatetime($tor['options']['max']);
                            if ($posted_data > $tor['options']['max']) {
                                $this->all_is_ok = false;
                                $this->messages.="<font color=red><b>ERROR : value of {$_label} is greater than {$tor['options']['max']}</b></font><br/>";
                            }
                        }
                        if (isset($tor['options']['min'])) {
                            $tor['options']['min'] = $this->checkDatetime($tor['options']['min']);
                            if ($posted_data < $tor['options']['min']) {
                                $this->all_is_ok = false;
                                $this->messages.="<font color=red><b>ERROR : value of {$_label} is less than {$tor['options']['min']}</b></font><br/>";
                            }
                        }
                    }
                } else {
                    if (isset($tor['options']['default'])) {
                        $posted_data = $this->checkDatetime($tor['options']['default']);
                    }
                }
                $tor['value'] = $tor['form_element_value'] = isset($posted_data) ? $posted_data : '';
                //prn($tor);
                break;
        }
        $this->field[$_alias] = $tor;
        unset($tor);
    }

// ------------------ add field to list -- end   -------------------------------
// --------------------- create GET query -- begin -----------------------------
    function create_get_query($to_exclude = '') {
        global $_GET, $_POST;

        $PARAM = array_merge($_GET, $_POST);
        $newquery = Array();
        foreach ($PARAM as $k0 => $v0)
            if (!eregi($to_exclude, $k0))
                $newquery[] = "{$k0}=" . rawurlencode($v0);
        $newquery = join('&', $newquery);
        return $newquery;
    }

// --------------------- create GET query -- end   -----------------------------
// --------------------- set primary key -- begin ------------------------------
    function set_primary_key($_keyname, $_keyvalue = 0) {
        //prn("$_keyname = $_keyvalue");
        if (isset($this->field[$_keyname])) {
            $this->primary_key = $_keyname;
            switch ($this->field[$_keyname]['type']) {
                case 'integer':
                    $this->id = $this->checkInt($_keyvalue);
                    $this->where_str = " WHERE {$this->field[$_keyname]['field']}={$this->id} ";
                    break;

                case 'float':
                    $this->id = $this->checkFloat($_keyvalue);
                    $this->where_str = " WHERE {$this->field[$_keyname]['field']}={$this->id} ";
                    break;

                case 'datetime':
                    $this->id = $this->checkDateTime($_keyvalue);
                    $this->where_str = " WHERE {$this->field[$_keyname]['field']}='{$this->id}' ";
                    break;

                case 'unix_timestamp':
                    $this->id = strtotime($this->checkDateTime($_keyvalue));
                    $this->where_str = " WHERE {$this->field[$_keyname]['field']}={$this->id} ";
                    break;

                case 'string':
                    $this->id = $this->checkStr($_keyvalue);
                    $this->where_str = " WHERE {$this->field[$_keyname]['field']}='{$this->id}' ";
                    break;
            }

            $query = "SELECT count({$this->field[$_keyname]['field']}) AS n_records FROM {$this->table} {$this->where_str};";
            ///prn($query);
            $resp = \e::db_getrows($query);
            if ($resp) {
                $this->field[$_keyname]['primary_key'] = true;
                $this->record_is_found = ($resp[0]['n_records'] == 1);
                return true;
            } else {
                $this->field[$_keyname]['primary_key'] = true;
                return false;
            }
        } else
            return false;
    }

// --------------------- set primary key -- end --------------------------------
// --------------------- hidden fields in form -- begin ------------------------
    function hidden_fields($rge = '^$') {
        global $_GET, $_POST;
        $input_vars = array_merge($_GET, $_POST);
        $hidden_fields = "\n<input type=hidden name=\"{$this->form_name_prefix}is_submitted\" value=\"yes\">\n";
        if (is_array($input_vars))
            foreach ($input_vars as $key => $val)
                if ((!preg_match('/^' . $this->form_name_prefix . '/', $key)) && (!@eregi($this->exclude, $key)) && (!@eregi($rge, $key)))
                    $hidden_fields.="<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\">\n";

        foreach ($this->field as $fld) {
            if (isset($fld['primary_key'])) {
                if ($fld['primary_key']) {
                    $hidden_fields.="<input type=\"hidden\" name=\"{$fld['form_element_name']}\" value=\"{$fld['form_element_value']}\">\n";
                }
            }
        }

        return $hidden_fields;
    }

// --------------------- hidden fields in form -- end --------------------------
// ------------------ get basic info from database -- begin ------------------
    function get_info_from_db() {
        if ($this->primary_key) {
            //prn('get_info_from_db()');
            // ------------------- create query -- begin ------------------------------
            $query = Array();
            foreach ($this->field as $fld) {
                $query[] = $fld['field'] . ' AS ' . $fld['alias'];
            }
            $query = 'SELECT ' . join(',', $query) . ' FROM ' . $this->table . ' ' . $this->where_str . ';';
            ///prn($query);
            if ($this->debug) {
                echo "<hr>Extract record:\n<br>" . $this->checkStr($query) . "\n<hr>\n\n";
            }
            // ------------------- create query -- end --------------------------------

            $result = \e::db_getrows($query);
            ///if($result===false) echo "<hr><font color=red><b>ERROR:</b></font><br><b>Query</b>:\n<br>".$this->checkStr($query)."\n\n<br><br>\n\n<b>Message : </b>".$this->db->ErrorMsg()."\n\n<hr>\n\n";
            //prn($result);
            //$num_records=$result->_numOfRows;
            $num_records = count($result);

            //$result = $result->fields;
            $result = isset($result[0]) ? $result[0] : false;

            $fld_nums = array_keys($this->field);
            ///prn($fld_nums);
            ///foreach($fld_nums as $key=>$val) $result[$val]=$result[$key];
            ///prn($result);

            $this->record_is_found = ($num_records == 1);
            if ($this->record_is_found) {

                // ------------------- update fields -- begin -----------------------------
                $ke = array_keys($this->field);
                //prn($ke);
                foreach ($ke as $key) {
                    switch ($this->field[$key]['type']) {
                        case 'datetime':
                            if (strlen($result[$key]) > 0) {
                                $this->field[$key]['value'] = $this->field[$key]['form_element_value'] = $this->checkDatetime($result[$key]);
                            }
                            break;

                        case 'unix_timestamp':
                            if (strlen($result[$key]) > 0) {
                                $this->field[$key]['value'] = $result[$key];
                                $this->field[$key]['form_element_value'] = date($this->datetime_format, $result[$key]);
                            }
                            break;

                        case 'string':
                            $this->field[$key]['form_element_value'] = htmlspecialchars($result[$key]);
                            $this->field[$key]['value'] = $result[$key];
                            break;

                        case 'enum':
                            $this->field[$key]['form_element_value'] = htmlspecialchars($result[$key]);
                            $this->field[$key]['value'] = $result[$key];
                            $this->field[$key]['form_element_options'] = $this->draw_options($this->field[$key]['value'], $this->field[$key]['options']);
                            break;

                        case 'integer':
                            if (strlen($result[$key]) > 0) {
                                $this->field[$key]['form_element_value'] = $this->field[$key]['value'] = $this->checkInt($result[$key]);
                            }
                            break;

                        case 'float':
                            if (strlen($result[$key]) > 0)
                                $this->field[$key]['form_element_value'] = $this->field[$key]['value'] = $this->checkFloat($result[$key]);
                            break;
                    }
                }
                // ------------------- update fields -- end -------------------------------
            }
            $this->process_db_values();
        }
    }

// ------------------ get basic info from database -- end ----------------------

    function set_table($_tname) {
        $this->table = $_tname;
    }

    function use_db($_tname) {
        //$this->db = $_tname;
    }

    function del_field($_field) {
        unset($this->field[$_field]);
    }

    function checkInt($ffff) {
        if (isset($ffff))
            return round($ffff * 1);
        else
            return 0;
    }

    function checkFloat($ffff) {
        if (isset($ffff))
            return $ffff * 1;
        else
            return 0;
    }

    function checkStr($ffff) {
        if (isset($ffff))
            return htmlspecialchars($ffff, ENT_QUOTES, 'cp1251');
        else
            return '';
    }

    function checkDateTime($ffff) {
        if (!(($timestamp = strtotime($ffff)) === -1))
            return date($this->datetime_format, $timestamp);
        else
            return date($this->datetime_format);
    }

    function draw_form() {
        $tor = Array();
        global $_SERVER;
        $tor['action'] = $_SERVER['PHP_SELF'];
        $tor['method'] = 'post';
        $tor['name'] = $this->form_name_prefix;
        $tor['hidden_elements'] = $this->hidden_fields();
        $tor['elements'] = $this->field;
        $tor['messages'] = $this->messages;
        return($tor);
    }

    function prn($tst) {
        echo '<hr><pre>';
        print_r($tst);
        echo '</pre><hr>';
    }

    function save() {
        global $_GET, $_POST;
        $input_vars = array_merge($_GET, $_POST);
        // ----------------------- check if the form is submitted -- begin --------
        if ($input_vars["{$this->form_name_prefix}is_submitted"] != 'yes')
            return false;
        // ----------------------- check if the form is submitted -- end ----------
        // -------------------- additional checks -- begin ------------------------
        if (!($this->check_form_values() && $this->all_is_ok))
            return false;
        // -------------------- additional checks -- end --------------------------

        $this->messages.='<font color=green><b>Changes saved successfully</b></font><br/>';

        if (!$this->record_is_found) {
            // ----------------- create record -- begin ----------------------------
            //prn('creating...'.$this->id);
            $fff = Array();
            $vvv = Array();
            foreach ($this->field as $fld) {
                ///prn($fld);
                ///if($fld['primary_key']) $this->field[$fld['alias']]['value']=$this->field[$fld['alias']]['form_element_value']=$val=$this->id=$this->create_primary_key();
                ///else $val=$fld['value'];
                if (isset($fld['primary_key']) && $fld['primary_key'])
                    $primary_key_alias = $fld['alias'];
                else
                    switch ($fld['type']) {
                        case 'string':
                        case 'enum':
                        case 'datetime':
                            $fff[] = $fld['field'];
                            $vvv[] = "'" . \e::db_escape($fld['value']) . "'";
                            break;

                        case 'integer':
                        case 'unix_timestamp':
                        case 'float':
                            $fff[] = $fld['field'];
                            $vvv[] = $fld['value'];
                            break;
                    }
            }

            if (count($fff) > 0) {
                $query = "INSERT INTO {$this->table}(" . join(',', $fff) . ") VALUES(" . join(',', $vvv) . ");";
                if ($this->debug)
                    $this->prn($query);
                else {
                    $retcode = \e::db_execute($query);
                    if ($retcode) {
                        ///prn(' NEW ID ='.$this->db->Insert_ID());
                        $new_ID =\e::db_getonerow("SELECT LAST_INSERT_ID() AS newid;");
                        $this->field[$primary_key_alias]['value'] = $this->field[$primary_key_alias]['form_element_value'] = $this->id = $new_ID['newid'];
                    } else {
                        ///echo "<hr><font color=red><b>ERROR:</b></font><br><b>Query</b>:\n<br>".$this->checkStr($query)."\n\n<br><br>\n\n<b>Message : </b>".$this->db->ErrorMsg()."\n\n<hr>\n\n";
                    }
                    return $retcode;
                }
            }
            // ----------------- create record -- end ------------------------------
        } else {
            // ----------------- update record -- begin ----------------------------
            ///prn('updating...'.$this->id);
            $fff = Array();
            $this->where_str = '';
            foreach ($this->field as $fld) {
                if (!isset($fld['primary_key']))
                    $fld['primary_key'] = false;
                if ($fld['primary_key']) {
                    if ($fld['type'] == 'string')
                        $this->where_str = " WHERE {$fld['field']}='{$this->id}' ";
                    else
                        $this->where_str = " WHERE {$fld['field']}={$this->id} ";
                }
                else {
                    switch ($fld['type']) {
                        case 'string':
                        case 'datetime':
                        case 'enum':
                            $fff[] = "{$fld['field']}='" . \e::db_escape($fld['value']) . "'";
                            break;

                        case 'integer':
                        case 'float':
                        case 'unix_timestamp':
                            $fff[] = "{$fld['field']}={$fld['value']}";
                            break;
                    }
                }
            }

            if (count($fff) > 0 && $this->where_str != '') {
                $query = "UPDATE {$this->table} SET " . join(',', $fff) . " {$this->where_str};";
                if ($this->debug)
                    $this->prn($query);
                else {
                    $retcode = \e::db_execute($query);
                    if ($retcode === false) {
                        ///echo "<hr><font color=red><b>ERROR:</b></font><br><b>Query</b>:\n<br>".$this->checkStr($query)."\n\n<br><br>\n\n<b>Message : </b>".$this->db->ErrorMsg()."\n\n<hr>\n\n";
                    }
                    return $retcode;
                }
            }
            // ----------------- update record -- end ------------------------------
        }
    }

    function process() {
        global $_GET, $_POST;
        $input_vars = array_merge($_GET, $_POST);
        if (!isset($input_vars["{$this->form_name_prefix}is_submitted"]))
            $input_vars["{$this->form_name_prefix}is_submitted"] = 'no';

        if ($input_vars["{$this->form_name_prefix}is_submitted"] == 'yes') {
            ///prn(' Save');
            return $this->save();
        } else {
            ///prn('get_info_from_db()');
            $this->get_info_from_db();
            return false;
        }
    }

// ------------------ draw options for <select> -- begin -----------------------
// sample usage
//
//    draw_options('value2',Array('value1'=>'Text1','value2'=>'Text2','value3'=>'Text3'))
//
//    returns
//        <option value='value1'>Text1</option>
//        <option value='value2' selected>Text2</option>
//        <option value='value3'>Text3</option>
//
    function eq($needle, $haystack) {
        if (is_array($haystack)) {
            $toret = false;
            foreach ($haystack as $val)
                if ($needle == $val && strlen($needle) == strlen($val)) {
                    $toret = true;
                    break;
                }
        } else {
            $toret = ($needle == $haystack && strlen($needle) == strlen($haystack));
        }
        return $toret;
    }

    function draw_options($value, $options) {
        $to_return = '';
        foreach ($options as $key => $val) {
            if (is_array($val)) {
                if ($this->eq($val[0], $value))
                    $selected = ' selected ';
                else
                    $selected = '';
                $to_return.="<option value=\"" . htmlspecialchars(trim($val[0])) . "\" $selected>{$val[1]}</option>\n";
            }
            else {
                if ($this->eq($key, $value))
                    $selected = ' selected ';
                else
                    $selected = '';
                $to_return.="<option value=\"" . trim($key) . "\" $selected>$val</option>\n";
            }
        }
        return $to_return;
    }

// ------------------ draw options for <select> -- end -------------------------
}

// ---------------------------- class Report -- end ----------------------------


/*

  include('./config.php');
  include('./fun.php');
  //include('./index_msg.php');
  include('./menu.php');
  $time_start = getmicrotime();

  $db = odbc_connect($odbc_db_name,$odbc_db_user,$odbc_db_pass);



  $rep=new Report;
  $rep->from    ="Progress";
  $rep->add_field('UserID','user_id' ,'id','user id');
  $rep->add_field('sum(result_approved)','result_approved_1' ,'integer','results approved',true );

  prn($rep->field);

  prn($rep->create_query());

  prn($rep->create_get_query(':filter_UserID:'));
  prn($rep->show_list());
  odbc_close($db);
 */

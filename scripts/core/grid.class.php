<?php

namespace core;

/**
 * Class Report generates report structure
 *

  run('lib/class_report3');
  $rpt=new Report3();
  $rpt->from='conf_participant as participant';

  //$rpt->add_field(new report3_field_int('id', 'id', 'Participant identifier' ));
  $rpt->add_field(new report3_field_id('id', 'id', 'ID' ));
  $rpt->add_field(new report3_field_string('last_name', 'last_name', 'Last Name' ));
  $rpt->add_field(new report3_field_string('first_name', 'first_name', 'First Name' ));
  $rpt->add_field(new report3_field_enum('conference_id', 'conference_id', 'Conference',Array('options'=>\e::db_getrows("SELECT id, title_ukr FROM {$table_prefix}conference")) ));
  $rpt->add_field(new report3_field_datetime('date_submission', 'date_submission', 'Submission Date' ));
  $rpt->field['date_submission']->dateformat='d.m.Y';
  // echo $rpt->create_sql();

  options:
  searchable=true|false
  sortable=true|false
  is_visible=true|false

  $data=$rpt->get_data();

  prn($data);
  var_dump($rpt);

  echo $rpt->draw($data);
 */
class grid {

    /**
     * List of columns
     */
    public $fields = Array();

    /**
     * FROM part of SQL query       -- string -- input
     */
    private $from;

    /**
     * rows per page
     */
    public $rows_per_page = 10;

    /**
     * Composed SQL query
     */
    private $query;

    /**
     * First row to show
     */
    private $start = 0;

    /**
     * regexp to exclude from URLs
     */
    private $exclude;
    private $exclude2='';
    
    /**
     * true|false
     */
    private $distinct = false;

    /**
     * all WHERE conditions -- Array() -- input
     * additional conditions can be entered here
     */
    private $where;
    public $group_by = Array();
    private $having;

    /**
     * объект для работы с базой mysql
     */
    private $db;

    /**
     * отправленные из формы данные
     */
    private $request;

    /**
     * текстовые сообщения
     */
    private $gettext;

    public function __construct($model, $posted_data, $db, $gettext) {

        $this->exclude = "/^core_grid_|".session_name()."/";
        $this->gettext = $gettext;
        $this->request = $posted_data;
        $this->db = $db;

        $this->fields = $model['fields'];

        $this->from = $model['from'];

        if (isset($model['rows_per_page'])) {
            $this->rows_per_page = (int) $model['rows_per_page'];
            if ($this->rows_per_page < 1) {
                $this->rows_per_page = 10;
            }
        }

        if (isset($posted_data['core_grid_start'])) {
            $this->start = (int) $posted_data['core_grid_start'];
            if ($this->start < 0) {
                $this->start = 0;
            }
        }
        if (isset($model['exclude'])) {
            $this->exclude2 = $model['exclude'];
        }

        if (isset($model['distinct']) && $model['distinct']) {
            $this->distinct = true;
        }

        if (isset($model['where'])) {
            $this->where = $model['where'];
        } else {
            $this->where = Array();
        }

        if (isset($model['having'])) {
            $this->having = $model['having'];
        } else {
            $this->having = Array();
        }
    }

    /**
     * Add extra field
     */
    function add_field($fld) {
        $this->fields[$fld->alias] = $fld;
    }

    /**
     * delete field
     */
    function remove_field($alias) {
        unset($this->fields[$alias]);
    }

    /**
     * Add extra WHERE condition
     */
    function add_where($str) {
        $this->where[] = $str;
    }

    /**
     * Add extra HAVING condition
     */
    function add_having($str) {
        $this->having[] = $str;
    }

    /**
     * create query to run
     */
    function create_sql() {

        // compose WHERE condition
        // take into account additional conditions
        $where = $this->where;

        // ask each field
        foreach ($this->fields as $fld) {
            if (!$fld->group_operation) {
                $tmp = $fld->get_filter_sql($this->db);
                if ($tmp) {
                    $where[] = "({$tmp})";
                }
            }
        }
        if (count($where) > 0) {
            $where = " WHERE " . join(' AND ', $where);
        } else {
            $where = '';
        }


        // compose HAVING condition
        // take into account additional conditions
        $having = $this->having;
        // ask each field
        foreach ($this->fields as $fld) {
            if ($fld->group_operation) {
                $tmp = $fld->get_filter_sql($this->db);
                if ($tmp) {
                    $having[] = "({$tmp})";
                }
            }
        }
        if (count($having) > 0) {
            $having = " HAVING " . join(' AND ', $this->having);
        } else {
            $having = '';
        }

        // if only distinct rows should be selected
        if ($this->distinct) {
            $distinct = ' DISTINCT ';
        } else {
            $distinct = '';
        }

        // GROUP BY part
        $group_by = Array();
        if(count($this->group_by)==0){
            foreach ($this->fields as $fld) {
                if (!$fld->group_operation) {
                    $group_by[] = $fld->alias;
                }
            }
        }else{
            $group_by=$this->group_by;
        }
        if (count($group_by) < count($this->fields) && count($group_by) > 0) {
            $group_by = " GROUP BY " . join(',', $group_by);
        } else {
            $group_by = '';
        }

        // -------------- field list -- begin ----------------------------------
        $field_list = Array();
        foreach ($this->fields as $fld) {
            $field_list[] = " {$fld->field} AS {$fld->alias} ";
        }
        $field_list = join(',', $field_list);
        // -------------- field list -- end   ----------------------------------
        // ----------- ordering -- begin ---------------------------------------
        $ORDERBY = Array();
        foreach ($this->fields as $fld) {
            if ($fld->ordering) {
                $ORDERBY[] = $fld->alias . ' ' . ($fld->ordering == 'asc' ? 'asc' : 'desc');
            }
        }
        if (count($ORDERBY) > 0) {
            $ORDERBY = ' ORDER BY ' . join(', ', $ORDERBY);
        } else {
            $ORDERBY = '';
        }
        // ----------- ordering -- end   ---------------------------------------
        // ----------- LIMIT row count - begin ---------------------------------
        $limit = " LIMIT $this->start, $this->rows_per_page";
        // ----------- LIMIT row count - end -----------------------------------

        $this->query = "SELECT SQL_CALC_FOUND_ROWS {$distinct} $field_list FROM {$this->from} {$where} {$group_by} {$having} {$ORDERBY} {$limit}";
        //echo htmlspecialchars($this->query);
        return $this->query;
    }

    // ---------------------- create query to run -- end -----------------------
    // --------------------- show response -- begin --------------------------------

    function get_data() {

        // create SQL expression
        $this->create_sql();


        // -------------- get rows from database - begin -----------------------
        $rows = \e::db_getrows($this->query);

        // create view of the row
        $cnt = count($rows);
        for ($i = 0; $i < $cnt; $i++) {
            $tmp = Array('data' => $rows[$i], 'view' => Array());
            foreach ($this->fields as $fld) {
                $tmp['view'][$fld->alias] = $fld->get_view($rows[$i]);
            }
            $rows[$i] = $tmp;
        }
        // -------------- get rows from database - end -------------------------
        // -------------- get total number of records - begin ------------------
        $query = "SELECT FOUND_ROWS() AS n_records;";
        $n_rows = \e::db_getonerow($query);
        $n_rows = $n_rows['n_records'];
        // prn($n_rows, $rows);
        // -------------- get total number of records - end --------------------
        // ----------------- paging links - begin ------------------------------
        $paging = self::get_paging_links("{$_SERVER['PHP_SELF']}?" . self::query_string('/core_grid_start|'.  session_name().'/', $this->request) . "&core_grid_start={start}", $n_rows, $this->start, $this->rows_per_page);
        //prn('$paging', $paging);
        // ----------------- paging links - end --------------------------------
        // ---------- filter form elements - begin -----------------------------
        $form = Array();
        $form['action'] = $_SERVER['PHP_SELF'];
        $form['name'] = 'grid_filter';
        
        $hidden_elements_array=$this->request;
        if(strlen($this->exclude2)>0) {
            $hidden_elements_array=self::query_array($this->exclude2, $hidden_elements_array);
        }
        $form['hidden_elements'] = self::hidden_form_elements($this->exclude, $hidden_elements_array);
        $form['elements'] = Array();
        foreach ($this->fields as $fld) {
            foreach ($fld->filter as $filter) {
                $form['elements'][$filter->form_element_name] = Array(
                    'form_element_name' => $filter->form_element_name,
                    'form_element_id' => $filter->form_element_name,
                    'form_element_value' => $filter->form_element_value,
                    'label' => $filter->label
                );
            }
        }
        // ---------- filter form elements - end -------------------------------

        return Array('fields' => $this->fields, 'rows' => $rows, 'paging' => $paging, 'filter_form' => $form, 'total_rows' => $n_rows);
    }

    // sample pattern is $pattern="la-la-la&start={start}"
    static function get_paging_links($pattern, $n_rows, $start, $rows_per_page) {
        $pages = Array();

        $startVariablePattern=Array('{start}',rawurlencode('{start}'));

        $imin = max(0, $start - 10 * $rows_per_page);
        $imax = min($n_rows, $start + 10 * $rows_per_page);

        // "first page" link
        if ($imin > 0) {
            //$pages[] = Array('URL' => $pattern . 0, 'innerHTML' => '[1]');
            $pages[] = Array('URL' => str_replace($startVariablePattern,'0',$pattern), 'innerHTML' => '[1]');
            $pages[] = Array('URL' => '', 'innerHTML' => '...');
        }

        for ($i = $imin; $i < $imax; $i = $i + $rows_per_page) {
            if ($i == $start) {
                $to = (1 + $i / $rows_per_page);
                $class='active';
            } else {
                $to = ( 1 + $i / $rows_per_page);
                $class='';
            }
            //$pages[] = Array('URL' => $pattern . $i, 'innerHTML' => $to, 'class'=>$class);
            $pages[] = Array('URL' => str_replace($startVariablePattern,$i,$pattern), 'innerHTML' => $to, 'class'=>$class);
        }

        // "last page" link
        if ($imax < $n_rows) {
            $last_page = floor(($n_rows - 1) / $rows_per_page);
            if ($last_page > 0) {
                $pages[] = Array('URL' => '', 'innerHTML' => "...");
                //$pages[] = Array('URL' => $pattern . ($last_page * $rows_per_page), 'innerHTML' => ($last_page + 1));
                $pages[] = Array('URL' => str_replace($startVariablePattern,($last_page * $rows_per_page),$pattern), 'innerHTML' => ($last_page + 1));
            }
        }
        return $pages;
    }

    // --------------------- show response -- end ------------------------------
    // ---------------------------- create query string -- begin ---------------
    // add all variables from POST and GET to query string
    // excluding variables having names that match $exclude_pattern
    // or too long values (>=1024 bytes)
    //
    static function query_string($exclude_pattern, $request) {
        $tor = Array();
        if (!$request) {
            return false;
        }
        $request = self::query_array($exclude_pattern, $request);
        # prn($request);
        $cnt = array_keys($request);
        foreach ($cnt as $key) {
            $tor[] = $key . '=' . rawurlencode($request[$key]);
        }
        return join('&', $tor);
    }

    static function query_array($exclude_pattern, $request) {
        $tor = Array();
        if (!$request) {
            return false;
        }
        # prn($request);
        # ---------------- create query string - begin -------------------------
        $tor = Array();
        while (count($cnt = array_keys($request)) > 0) {
            foreach ($cnt as $key) {
                if (is_array($request[$key])) {
                    foreach ($request[$key] as $k => $v) {
                        $request[$key . "[$k]"] = $v;
                    }
                } else {
                    $val = $request[$key];
                    if (sizeof($val) < 1024) {
                        $tor[$key] = $val;
                    }
                }
                unset($request[$key]);
                # prn($request);
            }
        }
        # ---------------- create query string - end ---------------------------
        # ---------------- remove elements matching exclude pattern - begin ----
        $cnt = array_keys($tor);
        foreach ($cnt as $key) {
            if (strlen($exclude_pattern) > 0)
                if (preg_match($exclude_pattern, $key)) {
                    unset($tor[$key]);
                }
        }
        # ---------------- remove elements matching exclude pattern - end ------
        return $tor;
    }

    static function hidden_form_elements($exclude_pattern, $request) {
        $tor = Array();
        if (!$request) {
            return false;
        }
        $request = self::query_array($exclude_pattern, $request);

        # prn($request);
        $cnt = array_keys($request);
        foreach ($cnt as $key) {
            $tor[] = "<input type=hidden name=\"" . htmlspecialchars($key) . "\" value=\"" . htmlspecialchars($request[$key]) . "\">\r\n";
        }
        return join(' ', $tor);
    }

    function draw($data) {
        $html = '';
        // draw filter form
        // \e::info('',$data['fields']);
        foreach ($data['fields'] as $alias => $fld) {
            if (!$fld->options['searchable']) {
                continue;
            }
            foreach ($fld->filter as $flt) {
                $html.="<span class=\"grid_filter_element\"><div class=\"grid_filter_label\">{$flt->label}</div><div>" . $flt->form_element_html() . "</div></span>";
            }
        }
        $html = "<form action=\"{$data['filter_form']['action']}\" method=\"POST\">
        {$data['filter_form']['hidden_elements']}
        $html
            <br/>
        <span class=\"grid_filter_element\"><div class=\"grid_filter_label\">&nbsp;</div><input type=submit value=\"" . $this->gettext->text("core/start_search") . "\"></span>
        </form><br/>";

        // draw paging
        //$paging = $this->gettext->text("core/rows_found", Array('rows' => $data['total_rows']))
        //        . ' &nbsp; ' .
        //        $this->gettext->text("core/pages") . ":&nbsp;";
        //foreach ($data['paging'] as $pg) {
        //    if ($pg['URL'] == '') {
        //        $paging.="{$pg['innerHTML']}&nbsp;";
        //    } else {
        //        $paging.="<a href=\"{$pg['URL']}\">{$pg['innerHTML']}</a>&nbsp;";
        //    }
        //}
        $paging = self::draw_paging($data['paging'],$data['total_rows'], $this->gettext);

        $html.="<div class=\"grid_paging\">$paging</div>";

        $html.="<table class=\"grid_table\" cellpadding=\"2px\" cellspacing=\"0px\" border=\"0px\"><thead><tr><td>&nbsp;</td>";
        foreach ($data['fields'] as $fld) {
            if ($fld->options['is_visible']) {
                $ord = $fld->get_ordering_url();
                $html.="
                 <th valign=\"top\">
                 ";

                if ($fld->options['sortable']) {
                    $html.="
                     <a class=\"grid_ordering_link\" href=\"{$ord['asc']}\">V</a><a class=\"grid_ordering_link\" href=\"{$ord['desc']}\">&Lambda;</a>
                     ";
                }
                $html.="
                    {$fld->label}
                 </th>";
            }
        }
        $html.="</tr></thead>";

        // draw rows
        $html.="<tbody>";
        foreach ($data['rows'] as $row) {
            $html.="<tr><td>" . (isset($row['menu']) ? $row['menu'] : '&nbsp;') . "</td>";
            foreach ($data['fields'] as $fld) {
                if ($fld->options['is_visible']) {
                    $html.="<td>" . $row['view'][$fld->alias] . "</td>";
                }
            }
            $html.="</tr>";
        }
        $html.="</tbody>";

        $html.="</table><div class=\"grid_paging\">$paging</div>";
        return $html;
    }

    public static function draw_paging($pagingArray,$total_rows, $gettext) {
        
        // draw paging
        $paging = $gettext->text("core/rows_found", Array('rows' => $total_rows));
        if(count($pagingArray)>1){
            $paging.= ' &nbsp; ' .  $gettext->text("core/pages") . ":&nbsp;";
            foreach ($pagingArray as $pg) {
                if ($pg['URL'] == '') {
                    $paging.="<span class=\"grid-list-paging-item ".(isset($pg['class'])?$pg['class']:'')."\">{$pg['innerHTML']}</span>";
                } else {
                    $paging.="<a href=\"{$pg['URL']}\" class=\"grid-list-paging-item ".(isset($pg['class'])?$pg['class']:'')."\">{$pg['innerHTML']}</a>";
                }
            }
        }
        return $paging;
    }

}

//==============================================================================
// report fields
abstract class grid_field {

    /**
     * Field expression
     */
    public $field;

    /**
     * Field alias
     */
    public $alias;

    /**
     * Type name
     */
    public $type;

    /**
     * Human readable column label
     */
    public $label;

    /**
     * additional options
     */
    public $options;

    /**
     * if current field is aggregate operation
     */
    public $group_operation = false;

    /**
     * Filter
     */
    public $filter;

    /**
     * Ordering
     */
    public $ordering;

    /**
     * Get filter SQL expression
     */
    function get_filter_sql($db) {
        $tmp = Array();
        if (isset($this->filter) && is_array($this->filter)) {
            foreach ($this->filter as $flt) {
                $sql = $flt->get_sql($db);
                if ($sql) {
                    $tmp[] = '(' . $sql . ')';
                }
            }
        }
        if (count($tmp) > 0) {
            return join(' AND ', $tmp);
        } else {
            return false;
        }
    }

    /**
     * get filter form fields
     */
    abstract function create_filter();

    /**
     * Get field value
     */
    function get_value($row) {
        return $row[$this->alias];
    }

    /**
     * Get field presentation
     */
    function get_view($row) {
        return $row[$this->alias];
    }

    function __construct($_field, $_alias, $_label, $_options = Array(), $_group_operation = false, $posted_data = Array()) {
        $this->field = $_field;
        $this->alias = $_alias;
        $this->type = 'int';
        $this->label = $_label;
        $this->group_operation = $_group_operation;
        $this->options = $_options;
        // var_dump($this);
        // load posted ordering parameters
        if (isset($posted_data['grid_orderby_' . $this->alias])) {
            $this->ordering = $posted_data['grid_orderby_' . $this->alias];
            if ($this->ordering == 'desc') {
                $this->ordering = 'desc';
            } else {
                $this->ordering = 'asc';
            }
        }


        // if field is visible
        $this->options['is_visible'] = isset($this->options['is_visible']) ? $this->options['is_visible'] : true;

        // if field is searchable
        $this->options['searchable'] = isset($this->options['searchable']) ? $this->options['searchable'] : true;

        // if field is sortable
        $this->options['sortable'] = isset($this->options['sortable']) ? $this->options['sortable'] : true;

        // 
        // create filters
        $this->filter = $this->create_filter();

        // load posted filter parameters
        $cnt = count($this->filter);
        for ($i = 0; $i < $cnt; $i++) {
            $this->filter[$i]->load_request($posted_data);
        }

        $prefix = $_SERVER['PHP_SELF'] . '?' . grid::query_string("/^grid_orderby_|".  session_name()."/", $posted_data) . "&grid_orderby_" . $this->alias . '=';
        $this->ordering_url = Array('asc' => $prefix . 'asc', 'desc' => $prefix . 'desc');
    }

    function get_ordering_url() {
        return $this->ordering_url;
    }

}

// =============================================================================
/**
 * Filter elements
 */
abstract class grid_filter {

    var $sql_template;
    var $value;
    var $form_element_name;
    var $form_element_value;
    var $label;

    function __construct($fld) {
        // var_dump($fld);
        $this->sql_template = $this->sql_template($fld->field);
        $this->form_element_name = preg_replace("/\W/", '_', get_class($this)) . "_{$fld->alias}";
        $this->label = $this->get_label($fld->label);
    }

    /**
     * Get filter sql expression
     */
    function get_sql($db) {
        if (isset($this->value) && strlen(trim($this->value))>0) {
            return sprintf($this->sql_template, $db->db_escape($this->value));
        } else {
            return false;
        }
    }

    /**
     * load posted data
     */
    function load_request($input_vars) {
        if (isset($input_vars[$this->form_element_name])) {
            $var = trim($input_vars[$this->form_element_name]);
            if ($this->is_valid($var)) {
                $this->value = $this->value_from_string($var);
                $this->form_element_value = $this->form_element_value($this->value);
            }
        } else {
            unset($this->value);
        }
    }

    /**
     * Form element HTML code
     */
    function form_element_html() {
        return "<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\">";
    }

    /**
     * Check if posted value is valid
     */
    abstract function is_valid($var);

    /**
     * Converts value from string to required type
     */
    abstract function value_from_string($var);

    /**
     * Returns human readable label
     */
    abstract function get_label($fieldlabel);

    /**
     * Create form element value presentation
     */
    function form_element_value($value) {
        return htmlspecialchars($value);
    }

    /**
     * Get sql template
     */
    abstract function sql_template($fld);
}

// =============================================================================
// integer report field
class grid_field_int extends grid_field {

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_int_from($this);
        $tor[] = new grid_filter_int_to($this);
        return $tor;
    }

}

// integer field filters
class grid_filter_int_from extends grid_filter {

    function is_valid($var) {
        return preg_match("/-?[0-9]+/", $var);
    }

    function sql_template($fld) {
        return " ( $fld >= %s ) ";
    }

    function value_from_string($var) {
        return (int) trim($var);
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&ge;';
    }

}

class grid_filter_int_to extends grid_filter {

    function is_valid($var) {
        return preg_match("/-?[0-9]+/", $var);
    }

    function sql_template($fld) {
        return " ( $fld <= %s ) ";
    }

    function value_from_string($var) {
        return (int) trim($var);
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&le;';
    }

}

// ID report field
class grid_field_id extends grid_field {

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_id($this);
        return $tor;
    }

}

// integer field filters
class grid_filter_id extends grid_filter {

    function is_valid($var) {
        return preg_match("/([0-9]+,)*([0-9]+)/", str_replace(' ', '', $var));
    }

    function sql_template($fld) {
        return " ( $fld IN ( %s ) ) ";
    }

    function value_from_string($var) {
        $tmp = explode(',', $var);
        $cnt = count($tmp);
        for ($i = 0; $i < $cnt; $i++) {
            $tmp[$i] = (int) trim($tmp[$i]);
        }
        return join(',', $tmp);
    }

    function get_label($fieldlabel) {
        return $fieldlabel . '=';
    }

}

// float report field
class grid_field_float extends grid_field {

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_float_from($this);
        $tor[] = new grid_filter_float_to($this);
        return $tor;
    }

}

// integer field filters
class grid_filter_float_from extends grid_filter {

    function is_valid($var) {
        return is_numeric(str_replace(',', '.', $var));
    }

    function sql_template($fld) {
        return " ( $fld >= %s ) ";
    }

    function value_from_string($var) {
        return (float) trim(str_replace(',', '.', $var));
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&ge;';
    }

}

class grid_filter_float_to extends grid_filter {

    function is_valid($var) {
        return is_numeric(str_replace(',', '.', $var));
    }

    function sql_template($fld) {
        return " ( $fld <= %s ) ";
    }

    function value_from_string($var) {
        return (float) trim(str_replace(',', '.', $var));
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&le;';
    }

}

// String report field
class grid_field_string extends grid_field {

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_string($this);
        return $tor;
    }

}

// integer field filters
class grid_filter_string extends grid_filter {

    function is_valid($var) {
        return true;
    }

    function sql_template($fld) {
        return " locate( '%s', $fld )>0 ";
    }

    function value_from_string($var) {
        return $var;
    }

    function get_label($fieldlabel) {
        return $fieldlabel . '&nbsp;';
    }

}

// enum report field
class grid_field_enum extends grid_field {

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_enum($this);
        return $tor;
    }

    function get_view($row) {
        $value = $row[$this->alias];
        foreach ($this->options['options'] as $key => $val) {
            if (is_array($val)) {
                $val = array_values(array_unique($val));
                if (!isset($val[1]))
                    $val[1] = $val[0];
                if ($val[0] == $value && strlen($val[0]) == strlen($value)) {
                    return $val[1];
                }
            } else {
                if ($key == $value && strlen($key) == strlen($value)) {
                    return $val;
                }
            }
        }
        return $value;
    }

}

// enum field filters
class grid_filter_enum extends grid_filter {

    private $options;

    function __construct($fld) {
        $this->sql_template = $this->sql_template($fld->field);
        $this->form_element_name = preg_replace("/\W/", '_', get_class($this)) . "_{$fld->alias}";
        $this->label = $this->get_label($fld->label);
        $this->options = $fld->options['options'];
        $this->form_element_value = $this->draw_options(null, $this->options);
    }

    function is_valid($var) {
        return true;
    }

    function sql_template($fld) {
        return " locate( '%s', $fld )>0 ";
    }

    function value_from_string($var) {
        return $var;
    }

    function get_label($fieldlabel) {
        return $fieldlabel . '&nbsp;';
    }

    function draw_options($value, $options) {
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
                $to_return.="<option value=\"" . trim($key) . "\" $selected>$val</option>\n";
            }
        }
        return $to_return;
    }

    /**
     * load posted data
     */
    function load_request($input_vars) {
        if (isset($input_vars[$this->form_element_name])) {
            $var = trim($input_vars[$this->form_element_name]);
            if ($this->is_valid($var)) {
                $this->value = $this->value_from_string($var);
                $this->form_element_value = $this->draw_options($this->value, $this->options);
            }
        } else {
            unset($this->value);
        }
    }

    function form_element_html() {
        return "<select name=\"{$this->form_element_name}\"><option value=\"\"></option>{$this->form_element_value}</select>";
    }

}

// datetime report field
class grid_field_datetime extends grid_field {

    var $dateformat = 'Y-m-d H:i:s';

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_datetime_from($this);
        $tor[] = new grid_filter_datetime_to($this);
        return $tor;
    }

    function get_view($row) {
        return date(isset($this->options['date_format'])?$this->options['date_format']:$this->dateformat, strtotime($row[$this->alias]));
    }

}

// integer field filters
class grid_filter_datetime_from extends grid_filter {

    function is_valid($var) {
        return (strtotime($var) !== false);
    }

    function sql_template($fld) {
        return " ( $fld >= '%s' ) ";
    }

    function value_from_string($var) {
        return date('Y-m-d H:i:s', strtotime($var));
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&ge;';
    }

    /**
     * Form element HTML code
     */
    function form_element_html() {
        return "<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\" class=\"datepicker\">";
    }

}

class grid_filter_datetime_to extends grid_filter {

    function is_valid($var) {
        return (strtotime($var) !== false);
    }

    function sql_template($fld) {
        return " ( $fld <= '%s' ) ";
    }

    function value_from_string($var) {
        return date('Y-m-d H:i:s', strtotime($var));
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&le;';
    }

    function form_element_html() {
        return "<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\" class=\"datepicker\">";
    }

}




// date report field
class grid_field_date extends grid_field {

    var $dateformat = 'Y-m-d';

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_date_from($this);
        $tor[] = new grid_filter_date_to($this);
        return $tor;
    }

    function get_view($row) {
        return date(isset($this->options['date_format'])?$this->options['date_format']:$this->dateformat, strtotime($row[$this->alias]));
    }

}

// integer field filters
class grid_filter_date_from extends grid_filter {

    function is_valid($var) {
        return (strtotime($var) !== false);
    }

    function sql_template($fld) {
        return " ( $fld >= '%s' ) ";
    }

    function value_from_string($var) {
        return date('Y-m-d', strtotime($var));
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&ge;';
    }

    /**
     * Form element HTML code
     */
    function form_element_html() {
        return "<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\" class=\"datepicker\">";
    }

}

class grid_filter_date_to extends grid_filter {

    function is_valid($var) {
        return (strtotime($var) !== false);
    }

    function sql_template($fld) {
        return " ( $fld <= '%s' ) ";
    }

    function value_from_string($var) {
        return date('Y-m-d', strtotime($var));
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&le;';
    }

    function form_element_html() {
        return "<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\" class=\"datepicker\">";
    }

}






class grid_filter_mask extends \core\grid_filter {

    function __construct($fld, $opts) {
        parent::__construct($fld);
        $this->opts = $opts;
    }

    function is_valid($var) {
        // echo "is_valid($var)";
        return (strlen($var) > 0);
    }

    function sql_template($fld) {
        // echo " ( ( $fld & %s )>0 ) ";
        return " ( ( $fld & %s )>0 ) ";
    }

    function value_from_string($var) {
        return (int) $var;
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>';
    }

    function form_element_html() {
        return "<select name=\"{$this->form_element_name}\">
                 <option value=''></option>"
                . \core\form::draw_options($this->form_element_value, $this->opts)
                . "</select>";
    }

}

// timestamp report field
class grid_field_timestamp extends grid_field {

    var $dateformat = 'Y-m-d H:i:s';

    function create_filter() {
        $tor = Array();
        $tor[] = new grid_filter_timestamp_from($this);
        $tor[] = new grid_filter_timestamp_to($this);
        return $tor;
    }

    function get_view($row) {
        return date($this->dateformat, $row[$this->alias]);
    }

}

// timestamp field filters
class grid_filter_timestamp_from extends grid_filter {

    function is_valid($var) {
        return (strtotime($var) !== false);
    }

    function sql_template($fld) {
        return " ( $fld >= '%s' ) ";
    }

    function value_from_string($var) {
        return strtotime($var);
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&ge;';
    }

    function form_element_html() {
        return "<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\" class=\"datepicker\">";
    }

}

class grid_filter_timestamp_to extends grid_filter {

    function is_valid($var) {
        return (strtotime($var) !== false);
    }

    function sql_template($fld) {
        return " ( $fld <= '%s' ) ";
    }

    function value_from_string($var) {
        return strtotime($var);
    }

    function get_label($fieldlabel) {
        return '<span class="filter-label">'.$fieldlabel . '</span>&le;';
    }

    function form_element_html() {
        return "<input type=text name=\"{$this->form_element_name}\" value=\"{$this->form_element_value}\" class=\"datepicker\">";
    }

}

?>
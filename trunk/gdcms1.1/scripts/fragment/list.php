<?php

/*
 * List of static page fragmetns
 */
run('site/menu');
run('fragment/functions');


# ------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
# ------------------- get site info - end --------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = text('Access_denied');
    return 0;
}
# ------------------- check permission - end -----------------------------------

// -------------------- delete fragment - begin --------------------------------
if(isset($input_vars['delete_fragment'])){
    $delete_fragment=explode('.',$input_vars['delete_fragment']);
    $query="DELETE FROM {$table_prefix}fragment
            WHERE site_id={$site_id}
              AND fragment_id=".( (int)$delete_fragment[0] )."
              AND fragment_lang='".DbStr($delete_fragment[1])."'
            LIMIT 1";
    db_execute($query);
    clear('delete_fragment');
}
// -------------------- delete fragment - end ----------------------------------
//--------------------------- get list -- begin --------------------------------
run("lib/class_report");
run("lib/class_report_extended");
$re = new report_generator;
$re->db = $db;
$re->distinct = false;
$re->rows_per_page=100;


// fragment_id          bigint(20)    (NULL)             NO      PRI     (NULL)   auto_increment  select,insert,update,references
// fragment_lang        varchar(3)    latin1_swedish_ci  YES             (NULL)                   select,insert,update,references
// site_id              bigint(20)    (NULL)             NO              (NULL)                   select,insert,update,references
// fragment_is_visible  tinyint(1)    (NULL)             NO              1                        select,insert,update,references
// fragment_name        varchar(128)  latin1_swedish_ci  YES             (NULL)                   select,insert,update,references
// fragment_html        text          latin1_swedish_ci  YES             (NULL)                   select,insert,update,references
// fragment_label       varchar(128)  latin1_swedish_ci  YES             (NULL)                   select,insert,update,references


$re->from = "{$table_prefix}fragment AS fragment";

$re->add_where(" fragment.site_id={$site_id} ");

$re->add_field($field = 'fragment.fragment_id'
        , $alias = 'fragment_id'
        , $type = 'id:hidden=no'
        , $label = '#'//'<span style="font-size:80%;">'.wordwrap($text['Page_id'], 5, "\n", 1).'</span>'
        , $_group_operation = false);

//---------------- list of languages - begin ---------------------------------
$LL = join('&', db_get_associated_array("SELECT fragment_lang,CONCAT(fragment_lang,'=',fragment_lang) FROM {$table_prefix}fragment WHERE site_id={$site_id}"));
$re->add_field($field = 'fragment.fragment_lang'
        , $alias = 'fragment_lang'
        , $type = 'enum:' . $LL
        , $label = text('Language')
        , $_group_operation = false);
//---------------- list of languages - end -----------------------------------

$re->add_field($field = 'fragment.site_id'
        , $alias = 'site_id'
        , $type = 'id:hidden=yes'
        , $label = text('Site_id')
        , $_group_operation = false);

$re->add_field($field = 'fragment.fragment_is_visible'
        , $alias = 'fragment_is_visible'
        , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        , $label = text('fragment_is_visible')
        , $_group_operation = false);

$re->add_field($field = 'fragment.fragment_place'
        , $alias = 'fragment_place'
        , $type = 'string'
        , $label = text('fragment_place')
        , $_group_operation = false);

$re->add_field($field = 'fragment.fragment_label'
        , $alias = 'title'
        , $type = 'string'
        , $label = text('fragment_label')
        , $_group_operation = false);

unset($field, $alias, $type, $label, $_group_operation);
//prn($re->create_query());
$response = $re->show();
//prn($response);
//--------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = $this_site_info['title'] .' - '. text('fragment_list');
$input_vars['page_header'] = $this_site_info['title'] .' - '. text('fragment_list');

  //--------------------------- context menu -- begin ----------------------------
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_fragment($response['rows'][$i]);
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']="<a href=\"index.php?action=fragment/edit&site_id=$site_id\">".text('fragment_create')."</a><br/><br/>";
$input_vars['page_content'] .= $re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
<?php

/*
  List of messages for a selected site, selected forum and selected thread
  Arguments are
  $site_id - site identifier, integer, mandatory
  $forum_id - forum identifier, integer, mandatory
  $thread_id - thread identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
run('site/menu');
run('forum/menu');

//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

//prn('$this_site_info=',$this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- get site info - end --------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
//------------------- get forum info - begin -----------------------------------
$forum_id = checkInt($input_vars['forum_id']);
$this_forum_info = \e::db_getonerow("SELECT * FROM {$table_prefix}forum_list WHERE id={$forum_id}");
//prn('$this_site_info=',$this_site_info);
if (checkInt($this_forum_info['id']) <= 0) {
    $input_vars['page_title'] = $text['Forum_not_found'];
    $input_vars['page_header'] = $text['Forum_not_found'];
    $input_vars['page_content'] = $text['Forum_not_found'];
    return 0;
}
//------------------- get forum info - end -------------------------------------
//------------------- get thread info - begin ----------------------------------
$thread_id = checkInt($input_vars['thread_id']);
$this_thread_info = \e::db_getonerow("SELECT * FROM {$table_prefix}forum_thread WHERE id={$thread_id}");
//prn('$this_thread_info=',$this_thread_info);
if (checkInt($this_thread_info['id']) <= 0) {
    $input_vars['page_title'] = $text['Thread_not_found'];
    $input_vars['page_header'] = $text['Thread_not_found'];
    $input_vars['page_content'] = $text['Thread_not_found'];
    return 0;
}
//------------------- get thread info - end ------------------------------------
//-------------------- delete message - begin ----------------------------------
$delete_message_id = checkInt(isset($input_vars['delete_message_id']) ? $input_vars['delete_message_id'] : 0);
if ($delete_message_id > 0) {
    $del_msg_info = \e::db_getonerow("SELECT  * FROM {$table_prefix}forum_msg WHERE  id={$delete_message_id} AND site_id={$site_id}");
    // prn($del_msg_info);
    if ($del_msg_info['is_first_msg'] == 1) {
        $query = "DELETE FROM {$table_prefix}forum_thread WHERE id={$del_msg_info['thread_id']}";
        \e::db_execute($query);
        $query = "DELETE FROM {$table_prefix}forum_msg WHERE thread_id={$del_msg_info['thread_id']} AND site_id={$site_id}";
        \e::db_execute($query);
    } else {
        $query = "DELETE FROM {$table_prefix}forum_msg WHERE id={$delete_message_id} AND site_id={$site_id}";
        // prn($query);
        \e::db_execute($query);
    }
}
clear('delete_message_id');
//-------------------- delete message - end ------------------------------------
//-------------------- make visible - begin ------------------------------------
$message_set_visible = checkInt(isset($input_vars['message_set_visible']) ? $input_vars['message_set_visible'] : 0);
if ($message_set_visible > 0) {
    $query = "UPDATE {$table_prefix}forum_msg SET is_visible=1 WHERE id={$message_set_visible} AND site_id={$site_id} LIMIT 1";
    //prn($query);
    \e::db_execute($query);
}
//-------------------- make visible - end --------------------------------------
//-------------------- make invisible - begin ------------------------------------
$message_set_invisible = checkInt(isset($input_vars['message_set_invisible']) ? $input_vars['message_set_invisible'] : 0);
if ($message_set_invisible > 0) {
    $query = "UPDATE {$table_prefix}forum_msg SET is_visible=0 WHERE id={$message_set_invisible} AND site_id={$site_id} LIMIT 1";
    //prn($query);
    \e::db_execute($query);
}
//-------------------- make ibvisible - end --------------------------------------
//--------------------------- get list -- begin --------------------------------
run("lib/class_report");
run("lib/class_report_extended_1");
$re = new report_generator;
$re->db = $db;
$re->distinct = false;

$re->from = "{$table_prefix}forum_msg";
$re->add_where(" site_id   = $site_id  ");
$re->add_where(" forum_id  = $forum_id ");
$re->add_where(" thread_id = $thread_id");

$re->add_field($field = 'is_visible'
        , $alias = 'is_visible'
        , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        , $label = text('is_visible')
        , $_group_operation = false);

$re->add_field($field = 'id'
        , $alias = 'id'
        , $type = 'id'
        , $label = $text['Message_id']
        , $_group_operation = false);

$re->add_field($field = 'site_id'
        , $alias = 'site_id'
        , $type = 'id:hidden=yes'
        , $label = 'site_id'
        , $_group_operation = false);

$re->add_field($field = 'forum_id'
        , $alias = 'forum_id'
        , $type = 'id:hidden=yes'
        , $label = 'forum_id'
        , $_group_operation = false);

$re->add_field($field = 'thread_id'
        , $alias = 'thread_id'
        , $type = 'id:hidden=yes'
        , $label = 'thread_id'
        , $_group_operation = false);

$re->add_field($field = 'is_first_msg'
        , $alias = 'is_first_msg'
        , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        , $label = $text['is_first_msg']
        , $_group_operation = false);


$re->add_field($field = 'name'
        , $alias = 'name'
        , $type = 'string'
        , $label = $text['Creator_name']
        , $_group_operation = false);


//  $re->add_field( $field='email'
//                 ,$alias='email'
//                 ,$type ='string'
//                 ,$label=$text['Creator_email']
//                 ,$_group_operation=false);
//
//  $re->add_field( $field='www'
//                 ,$alias='www'
//                 ,$type ='string'
//                 ,$label=$text['Creator_site_URL']
//                 ,$_group_operation=false);

$re->add_field($field = 'data'
        , $alias = 'data'
        , $type = 'datetime'
        , $label = $text['Date_created']
        , $_group_operation = false);
//
//  $re->add_field( $field='subject'
//                 ,$alias='subject'
//                 ,$type ='string'
//                 ,$label=$text['Subject']
//                 ,$_group_operation=false);

$re->add_field($field = 'msg'
        , $alias = 'msg'
        , $type = 'string'
        , $label = $text['Message_body']
        , $_group_operation = false);

unset($field, $alias, $type, $label, $_group_operation);
//prn($re->create_query());
$response = $re->show();
//prn($response);
//--------------------------- get list -- end ----------------------------------
$input_vars['page_title'] = $text['List_of_messages'];
$input_vars['page_header'] = $text['List_of_messages'];

//--------------------------- context menu -- begin ----------------------------
$cnt = count($response['rows']);
for ($i = 0; $i < $cnt; $i++) {
    //--------------------------- context menu -- begin ------------------------
    $response['rows'][$i]['context_menu'] = menu_msg($response['rows'][$i]);
    //--------------------------- context menu -- end --------------------------
}
//--------------------------- context menu -- end ------------------------------

$input_vars['page_content'] = "
 <p>
   <table>
   <tr>
     <td align=right><font size=+1><b>{$text['Site']} </b> :</font></td>
     <td><font size=+1>{$this_site_info['title']}</font></td>
   </tr>
   <tr>
     <td align=right><font size=+1><b>{$text['Forum']}</b> :</font></td>
     <td><font size=+1>{$this_forum_info['name']}</font></font></td>
   </tr>
   <tr>
     <td align=right><font size=+1><b>{$text['Thread']}</b> :</font></td>
     <td><font size=+1>{$this_thread_info['subject']}</font></td>
   </tr>
   <tr>
     <td align=right><font size=+1><b>{$text['Date_created']}</b> :</font></td>
     <td><font size=+1>{$this_thread_info['data']}</font></td>
   </tr>
   </table>
 </p>
"
        . $re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------


$input_vars['page_menu']['thread'] = Array('title' => $text['Thread'], 'items' => Array());
$input_vars['page_menu']['thread']['items'] = menu_thread($this_thread_info);

$input_vars['page_menu']['forum'] = Array('title' => $text['Forum'], 'items' => Array());
$input_vars['page_menu']['forum']['items'] = menu_forum($this_forum_info);


$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
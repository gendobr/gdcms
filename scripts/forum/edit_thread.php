<?php

/*
  Edit forum properties
  arguments are
  $site_id   - site identifier, integer, mandatory
  $forum_id  - forum identifier, integer, mandatory
  $thread_id - thread identifier, integer, optional
 */
run('site/menu');

//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn('$this_site_info=',$this_site_info);
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
//------------------- this_forum_info - begin ----------------------------------
$forum_id = checkInt($input_vars['forum_id']);
$query = "SELECT * FROM {$table_prefix}forum_list WHERE id=" . checkInt($input_vars['forum_id']);
//prn(checkStr($query));
$this_forum_info =\e::db_getonerow($query);
// prn('$this_forum_info=',$this_forum_info);
if (checkInt($this_forum_info['id']) <= 0) {
    $input_vars['page_title'] = $text['Forum_not_found'];
    $input_vars['page_header'] = $text['Forum_not_found'];
    $input_vars['page_content'] = $text['Forum_not_found'];
    return 0;
}
//------------------- this_forum_info - end ------------------------------------
//------------------- get thread info - begin ----------------------------------
$thread_id = checkInt($input_vars['thread_id']);
$this_thread_info =\e::db_getonerow("SELECT * FROM {$table_prefix}forum_thread WHERE id={$thread_id}");
//prn('$this_thread_info=',$this_thread_info);
//if(checkInt($this_thread_info['id'])<=0)
//{
//  $input_vars['page_title']   = $text['Thread_not_found'];
//   $input_vars['page_header']  = $text['Thread_not_found'];
//   $input_vars['page_content'] = $text['Thread_not_found'];
//   return 0;
//}
//------------------- get thread info - end ------------------------------------
//------------------- edit properties -- begin ---------------------------------
run('lib/class_db_record_editor');
run('lib/class_db_record_editor_extended');

class ThreadEditor extends extended_db_record_editor {

    function check_form_values() {

        global $input_vars;
        $this->check_form_values_message='';
        if (isset($input_vars['db_record_editor_forum_id']) && $input_vars['db_record_editor_forum_id'] <= 0) {
            $this->check_form_values_message = '<b style="color:red;">������: �������� �����</b>';
            return false;
        }
        return true;
    }

}

$rep = new ThreadEditor;
$rep->use_db($db);
$rep->debug = false;
$rep->set_table("{$table_prefix}forum_thread");

$rep->add_field('id'
        , 'id'
        , 'integer:hidden=yes&default=' . checkInt($this_thread_info['id'])
        , '#');

$rep->add_field('site_id'
        , 'site_id'
        , 'integer:hidden=yes&default=' . checkInt($this_site_info['id'])
        , '#');


$tmp = \e::db_getrows("SELECT * FROM {$table_prefix}forum_list WHERE site_id=$site_id ORDER BY `name`");
// prn($tmp);
$cmt = count($tmp);
for ($i = 0; $i < $cmt; $i++)
    $tmp[$i] = $tmp[$i]['id'] . '=' . rawurlencode($tmp[$i]['name']);
// prn(join('&',$tmp));
$rep->add_field('forum_id'
        , 'forum_id'
        , 'enum:' . join('&', $tmp)
        , text('Forum'));

$rep->add_field('subject'
        , 'subject'
        , 'string:maxlength=80&required=yes'
        , $text['forum_thread_subject']);

$rep->add_field('data'
        , 'data'
        , 'datetime:required=yes&default=' . rawurlencode(date('Y-m-d H:i:s'))
        , $text['Date_created']);





$rep->set_primary_key('id', $input_vars['thread_id']);



$success = $rep->process();

// prn($rep->field['forum_id']['value']); //->value
if ($success && $rep->id) {
    //prn($rep);
    $query = "UPDATE {$table_prefix}forum_msg
            SET forum_id={$rep->field['forum_id']['value']}
            WHERE thread_id={$rep->id} AND site_id={$site_id}";
    // prn($query);
    \e::db_execute($query);
}
//------------------- edit properties -- end -----------------------------------
//prn($rep);
//----------------------------- draw -- begin ----------------------------------
$form = $rep->draw_form();
//prn($form);
$form['hidden_elements'] = $rep->hidden_fields('^thread_id$') .
        "<input type=hidden name=thread_id value=\"{$rep->id}\">\n";

//prn($form);
$input_vars['page_title'] = $text['Edit_thread_properties'];
$input_vars['page_header'] = $text['Edit_thread_properties'];
$input_vars['page_content'] = "
   <p>
   <table>
   <tr>
     <td align=right><font size=+1><b>{$text['Site']} </b> :</font></td>
     <td><font size=+1>{$this_site_info['title']}</font></td>
   </tr>
   </table>
 </p>
 {$rep->check_form_values_message}
  " . $rep->draw($form);
//----------------------------- draw -- end ------------------------------------
//--------------------------- context menu -- begin ----------------------------
run('forum/menu');

if ($rep->id > 0) {
    $query = "SELECT * FROM {$table_prefix}forum_thread WHERE id={$rep->id}";
    $this_thread_info =\e::db_getonerow($query);
    $input_vars['page_menu']['thread'] = Array('title' => $text['Thread'], 'items' => Array());
    $input_vars['page_menu']['thread']['items'] = menu_thread($this_thread_info);
}

$input_vars['page_menu']['forum'] = Array('title' => $text['Forum'], 'items' => Array());
$input_vars['page_menu']['forum']['items'] = menu_forum($this_forum_info);

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
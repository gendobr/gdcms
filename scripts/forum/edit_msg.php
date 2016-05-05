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
$query = "SELECT * FROM <<tp>>forum_list WHERE id=" . checkInt($input_vars['forum_id']);
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
$this_thread_info =\e::db_getonerow("SELECT * FROM <<tp>>forum_thread WHERE id={$thread_id}");
//prn('$this_thread_info=',$this_thread_info);
if (checkInt($this_thread_info['id']) <= 0) {
    $input_vars['page_title'] = $text['Thread_not_found'];
    $input_vars['page_header'] = $text['Thread_not_found'];
    $input_vars['page_content'] = $text['Thread_not_found'];
    return 0;
}
//------------------- get thread info - end ------------------------------------
//


//------------------- edit properties -- begin ---------------------------------

run('lib/class_db_record_editor');
run('lib/class_db_record_editor_extended');

$rep = new extended_db_record_editor;

$rep->debug = false;
$rep->set_table("<<tp>>forum_msg");

$rep->add_field('id'
        , 'id'
        , 'integer:hidden=yes'
        , '#');

$rep->add_field('site_id'
        , 'site_id'
        , 'integer:hidden=yes&default=' . checkInt($this_site_info['id'])
        , '#');

$rep->add_field('forum_id'
        , 'forum_id'
        , 'integer:hidden=yes&default=' . checkInt($this_forum_info['id'])
        , '#');


$tmp = \e::db_getrows("SELECT * FROM <<tp>>forum_thread WHERE forum_id=$forum_id order by subject");
$cmt = count($tmp);
for ($i = 0; $i < $cmt; $i++)
    $tmp[$i] = $tmp[$i]['id'] . '=' . rawurlencode($tmp[$i]['subject']);
//prn($tmp);
if (!isset($_REQUEST['db_record_editor_thread_id']))
    $_POST['db_record_editor_thread_id'] = $thread_id;
$rep->add_field('thread_id'
        , 'thread_id'
        , 'enum:' . join('&', $tmp)
        , text('Thread'));

$rep->add_field('is_visible'
        , 'is_visible'
        , "enum:1=" . rawurlencode(text('positive_answer')) . "&0=" . rawurlencode(text('negative_answer'))
        , text('is_visible'));
// set default value
if (!isset($input_vars['msg_id']) || $input_vars['msg_id'] == 0) {
    $rep->field['is_visible']['value'] = 1;
    $rep->field['is_visible']['form_element_options'] = $rep->draw_options(1, $rep->field['is_visible']['options']);
}

$rep->add_field('name'
        , 'name'
        , 'string:maxlength=255&required=yes&default=' . $_SESSION['user_info']['user_login']
        , $text['Creator_name']);

$rep->add_field('email'
        , 'email'
        , 'string:maxlength=40&required=no&default=' . $_SESSION['user_info']['email']
        , $text['Creator_email']);

$rep->add_field('www'
        , 'www'
        , 'string:maxlength=255&required=no&default='
        , $text['Creator_site_URL']);

$rep->add_field('subject'
        , 'subject'
        , 'string:maxlength=255&required=no&default=----'
        , $text['Subject']);

$rep->add_field('data'
        , 'data'
        , 'datetime:required=yes&default=' . rawurlencode(date('Y-m-d H:i:s'))
        , $text['Date_created']);
$rep->add_field('msg'
        , 'msg'
        , 'string:required=yes&textarea=yes&default='
        , $text['Message_body']);



$msg_id = checkInt($input_vars['msg_id']);
$rep->set_primary_key('id', $msg_id);
//----------------------- additional checks - begin --------------------------
//--------------------- email - begin --------------------------------------
if (isset($input_vars['db_record_editor_is_submitted'])
        && strlen($rep->field['email']['value']) > 0
        && !is_valid_email($rep->field['email']['value'])) {
    $rep->all_is_ok = false;
    $rep->messages .="<font color=red><b>{$text['ERROR']} : {$text['invalid_email_address']}</b></font><br>";
}
//----------------------- additional checks - end ----------------------------

$success = $rep->process();
//prn($rep);
//------------------- edit properties -- end -----------------------------------
//prn($rep);
//----------------------------- draw -- begin ----------------------------------
$form = $rep->draw_form();
// prn($form);$form['elements']['msg']['value']



$reply_to_msg_text = '';
// if(isset($input_vars['debug'])) prn($input_vars);
if (isset($input_vars['reply_to_msg'])) {
    $reply_to_msg = (int) $input_vars['reply_to_msg'];
    // if(isset($input_vars['debug'])) prn($reply_to_msg);
    $reply_to_msg_text =\e::db_getonerow("SELECT msg FROM <<tp>>forum_msg WHERE id={$reply_to_msg}");
    if ($reply_to_msg_text) {
        $reply_to_msg_text = "[quote]{$reply_to_msg_text['msg']}[/quote]";
        // prn($reply_to_msg_text);
    }
}
if(!$form['elements']['msg']['value']){
    $form['elements']['msg']['form_element_value']=  htmlspecialchars($reply_to_msg_text);
}

$form['hidden_elements'] = $rep->hidden_fields('^msg_id$') .
        "<input type=hidden name=msg_id value=\"{$rep->id}\">\n";

//prn($form);
$input_vars['page_title'] = $text['Message'];
$input_vars['page_header'] = $text['Message'];
$input_vars['page_content'] = "
   <p>
   <style>
    table.nbd td{border:none;}
    textarea[name=db_record_editor_msg]{width:400px;height:300px;}
   </style>
   <table border=0px class=nbd>
   <tr>
     <td><font size=+1><b>{$text['Site']} </b> :</font></td>
     <td><font size=+1>{$this_site_info['title']}</font></td>
   </tr>
   <tr>
     <td><font size=+1><b>{$text['Forum']}</b> :</font></td>
     <td><font size=+1>{$this_forum_info['name']}</font></font></td>
   </tr>
   <!--
   <tr>
     <td><font size=+1><b>{$text['Thread']}</b> :</font></td>
     <td><font size=+1>{$this_thread_info['subject']}</font></font></td>
   </tr>
   -->
   </table>
 </p>
 <div class=fr>
  " . $rep->draw($form)
        . '</div>
 <script type="text/javascript" src="' . site_root_URL . '/scripts/forum/functions.js"></script>
 <script type="text/javascript">
  var site_root_url="' . site_root_URL . '";
  window.onload=function(){editor_init("db_record_editor_msg");}
 </script>

<style>
.smilesbtn{
 background-repeat: no-repeat;
 background-position:center center;
 width:24px;
 height:24px;
 border:none;
 background-color:transparent;
}
</style>

 ';
//----------------------------- draw -- end ------------------------------------
//--------------------------- context menu -- begin ----------------------------
run('forum/menu');

if ($rep->id > 0) {
    $query = "SELECT * FROM <<tp>>forum_msg WHERE id={$rep->id}";
    $this_msg_info =\e::db_getonerow($query);
    $input_vars['page_menu']['thread'] = Array('title' => $text['Message'], 'items' => Array());
    $input_vars['page_menu']['thread']['items'] = menu_msg($this_msg_info);
}

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
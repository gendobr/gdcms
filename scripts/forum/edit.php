<?php
/*
  Edit forum properties
  arguments are
  $site_id  - site identifier, integer, mandatory
  $forum_id - forum identifier, integer, optional
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
$forum_id = isset($input_vars['forum_id'])?checkInt($input_vars['forum_id']):0;
$query = "SELECT * FROM {$table_prefix}forum_list WHERE id=" . $forum_id;
$this_forum_info =\e::db_getonerow($query);
// prn('$this_forum_info=',$this_forum_info);
//------------------- this_forum_info - end -------------------------------------

//------------------- edit properties -- begin ---------------------------------
run('lib/class_db_record_editor');
run('lib/class_db_record_editor_extended');

$rep = new extended_db_record_editor;
$rep->use_db($db);
$rep->debug = false;
$rep->set_table("{$table_prefix}forum_list");

$rep->add_field('id'
        , 'id'
        , 'integer:hidden=yes&default=' . checkInt($this_forum_info['id'])
        , '#');

$rep->add_field('site_id'
        , 'site_id'
        , 'integer:hidden=yes&default=' . checkInt($this_site_info['id'])
        , '#');

$rep->add_field('name'
        , 'name'
        , 'string:maxlength=80&required=yes'
        , $text['forum_title']);

$rep->add_field('is_premoderated'
        , 'is_premoderated'
        , 'enum:1=' . rawurlencode($text['positive_answer']) . '&0=' . rawurlencode($text['negative_answer'])
        , text('Is_pre_moderated'));
//prn($rep);

$rep->add_field('about'
        , 'about'
        , 'string:textarea=yes'
        , text('forum_description'));

$rep->add_field('moderators'
        , 'moderators'
        , 'string:textarea=yes'
        , text('forum_moderators'));

$rep->set_primary_key('id', $forum_id);
$rep->process();
//------------------- edit properties -- end -----------------------------------
//prn($rep);

//----------------------------- draw -- begin ----------------------------------
$form = $rep->draw_form();
// prn($form);
$form['hidden_elements'] = $rep->hidden_fields('^forum_id$') .
        "<input type=hidden name=forum_id value=\"{$rep->id}\">\n";


// get list of possible moderators
$list_of_moderators=\e::db_getrows("select site_visitor_id, site_visitor_login, site_visitor_email from `{$table_prefix}site_visitor` order by site_visitor_login;");
$tmp="";
foreach($list_of_moderators as $moderator){
    $tmp.="<div class=\"site_visitor_login\" id=\"site_visitor_{$moderator['site_visitor_id']}\">{$moderator['site_visitor_login']}({$moderator['site_visitor_email']})</div>";
}
$form['elements']['moderators']['comments']='
    List of possible moderators:
    <div style="overflow:scroll; width:100%; height:300px;">'.$tmp.'</div>
    <script type="text/javascript">
    $(window).load(function(){
       // alert("OK");
       $(".site_visitor_login").each(function(ind,elem){
         $(elem).click(function(event){
           var text=event.target.innerHTML;
           var login=text.replace(/\\([^)]+\\)$/,\'\');
           // console.log(login);
           var oldValue=$(\'#db_record_editor_moderators\').val();
           // console.log(oldValue);
           $(\'#db_record_editor_moderators\').val(oldValue+login+"\n");
          })
       });

    });
    </script>';

//prn($form);
$input_vars['page_title'] = $text['Edit_forum_properties'];
$input_vars['page_header'] = $text['Edit_forum_properties'];
$input_vars['page_content'] = $rep->draw($form);
//----------------------------- draw -- end ------------------------------------
//----------------------------- site context menu - begin ----------------------
if ($rep->id > 0) {
    $this_forum_info =\e::db_getonerow("SELECT * FROM {$table_prefix}forum_list WHERE id={$rep->id}");
    run('forum/menu');
    $input_vars['page_menu']['forum'] = Array('title' => $text['Forum'], 'items' => Array());
    $input_vars['page_menu']['forum']['items'] = menu_forum($this_forum_info);
}
//----------------------------- site context menu - end ------------------------
//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
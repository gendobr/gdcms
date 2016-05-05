<?php
/*
  Editing menu group properties
  Arguments are
    $menu_item_id  - menu item identifier, integer, mandatory
*/


//-------------------------- check if item exists - begin ----------------------
$menu_item_id=checkInt($input_vars['menu_item_id']);
$menu_item_info=\e::db_getonerow("SELECT * FROM <<tp>>menu_item WHERE id={$menu_item_id}");
$menu_item_info['id'] = checkInt($menu_item_info['id']);
if($menu_item_info['id']<=0) {
    $input_vars['page_title']  =$text['Invalid_menu_item'];
    $input_vars['page_header'] =$text['Invalid_menu_item'];
    $input_vars['page_content']=$text['Invalid_menu_item'];
    return 0;
}
//prn('menu_item_info=',$menu_item_info);
//-------------------------- check if item exists - end ------------------------



//-------------------------- check args - begin --------------------------------
$menu_group_id   = $menu_item_info['menu_group_id'];
$menu_group_lang = $menu_item_info['lang'];
$menu_group_info=\e::db_getonerow("SELECT * FROM <<tp>>menu_group WHERE id={$menu_group_id} AND lang='{$menu_group_lang}'");
$menu_group_info['id'] = checkInt($menu_group_info['id']);
if($menu_group_info['id']<=0) {
    $input_vars['page_title']  =$text['Invalid_menu_group_identifier'];
    $input_vars['page_header'] =$text['Invalid_menu_group_identifier'];
    $input_vars['page_content']=$text['Invalid_menu_group_identifier'];
    return 0;
}
//prn('$menu_group_info=',$menu_group_info);
//-------------------------- check args - end ----------------------------------

run('site/menu');

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($menu_group_info['site_id']);
$this_site_info = get_site_info($site_id);

# $site_id = checkInt($menu_group_info['site_id']);
# $this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
# $this_site_info['id'] = checkInt($this_site_info['id']);
if($this_site_info['id']<=0) {
    $input_vars['page_title']  =$text['Site_not_found'];
    $input_vars['page_header'] =$text['Site_not_found'];
    $input_vars['page_content']=$text['Site_not_found'];
    return 0;
}
//prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------


//------------------- page info - begin ----------------------------------------
$page_id   = checkInt($menu_group_info['page_id']);
$lang      = \e::db_escape($menu_group_info['lang']);
$query="SELECT * FROM <<tp>>page WHERE id={$page_id} AND lang='$lang'";
$this_page_info=\e::db_getonerow($query);
$this_page_info['id']=checkInt($this_page_info['id']);
//prn('$this_page_info',$this_page_info);
//------------------- page info - end ------------------------------------------


//------------------- get permission - begin -----------------------------------
$user_cense_level=get_level($this_site_info['id']);
if($user_cense_level<=0) {
    $input_vars['page_title']  =$text['Access_denied'];
    $input_vars['page_header'] =$text['Access_denied'];
    $input_vars['page_content']=$text['Access_denied'];
    return 0;
}
//------------------- get permission - end -------------------------------------


// ------------------- update menu group - id - begin --------------------------
   if(isset($input_vars['db_record_editor_menu_group_id'])){
      $new_menu_group=explode('-',$input_vars['db_record_editor_menu_group_id']);
      // check if menu group exists
      if(\e::db_getonerow("select * from <<tp>>menu_group WHERE site_id='{$site_id}' AND id=".( (int)$new_menu_group[0] )." and lang='".  \e::db_escape($new_menu_group[1])."'" )){
         $query="UPDATE <<tp>>menu_item SET menu_group_id=".( (int)$new_menu_group[0] ).", lang='".  \e::db_escape($new_menu_group[1])."' WHERE id={$menu_item_id} ";
         \e::db_execute($query);
         $menu_item_info['menu_group_id']=(int)$new_menu_group[0];
         $menu_item_info['lang']=$new_menu_group[1];
      }
   }
// ------------------- update menu group - id - end ----------------------------

//------------------- editor -- begin ------------------------------------------
run('lib/class_db_record_editor');
run('lib/class_db_record_editor_extended');
$rep=new extended_db_record_editor;
$rep->use_db($db);
$rep->debug=false;
$rep->set_table("<<tp>>menu_item");

$rep->add_field( 'id'
        ,'id'
        ,'integer:hidden=yes&default='.$site_id
        ,'#');



$rep->add_field( 'html'
        ,'html'
        ,'string'
        ,$text['HTML code']);

$rep->add_field( 'url'
        ,'url'
        ,'string'
        ,'URL');

$rep->add_field( 'description'
        ,'description'
        ,'string'
        ,$text['Description']);

$rep->add_field( 'attributes'
        ,'attributes'
        ,'string'
        ,$text['Attributes']);
$rep->set_primary_key('id',$input_vars['menu_item_id']);
$rep->process();
//------------------- editor -- end --------------------------------------------

//------------------- draw - begin ---------------------------------------------
$form=$rep->draw_form();
// prn($form);
//------------------- draw - end -----------------------------------------------



//-------------------- list of menu groups - begin -----------------------------
$tmp=\e::db_getrows("SELECT * FROM <<tp>>menu_group WHERE site_id='{$site_id}' order by lang, id");
$cnt=count($tmp);
$db_record_editor_menu_group_id_options=Array();
for($i=0;$i<$cnt;$i++) {
    $db_record_editor_menu_group_id_options["{$tmp[$i]['id']}-{$tmp[$i]['lang']}"]=$tmp[$i]['lang'].'-'.$tmp[$i]['html'];
}
$db_record_editor_menu_group_id=$menu_item_info['menu_group_id'].'-'.$menu_item_info['lang'];
//-------------------- list of menu groups - end -------------------------------



//------------------- draw form - begin ----------------------------------------
if(!isset($this_page_info['title'])) $this_page_info['title']='';
$input_vars['page_content']="
  <div><a href=\"index.php?action=site/menu/list&site_id={$site_id}\">&lt;&lt;&lt; {$text['Site_menu']}</a></div>
  <table>
    <tr><td colspan=2><b>{$form['messages']}</b></td></tr>
    <form action=index.php method=post>
        {$form['hidden_elements']}
    <tr>
      <td><b>{$text['Site']} : </b></td>
      <td>{$this_site_info['title']}</td>
    </tr>
    <tr>
      <td><b>{$text['Page']} : </b></td>
      <td>{$this_page_info['title']}</td>
    </tr>
    <tr>
      <td><b>{$text['Language']} : </b></td>
      <td>{$menu_group_info['lang']}</td>
    </tr>
    <tr>
      <td><b>{$text['Menu_group']} : </b></td>
      <td><select name=\"db_record_editor_menu_group_id\">".draw_options($db_record_editor_menu_group_id, $db_record_editor_menu_group_id_options)."</select></td>
    </tr>
    <tr>
      <td><b>{$form['elements']['html']['label']} : </b></td>
      <td><input type=text name=\"{$form['elements']['html']['form_element_name']}\" value=\"{$form['elements']['html']['form_element_value']}\" id=\"menu_item_html\"></td>
    </tr>
    <tr>
      <td><b>{$form['elements']['url']['label']} : </b></td>
      <td>
      <input type=text name=\"{$form['elements']['url']['form_element_name']}\" value=\"{$form['elements']['url']['form_element_value']}\" id=\"menu_item_url\">
      <input type=button value=\"...\" onclick=\"popup('index.php?action=site/menu/select_page&site_id={$this_site_info['id']}&lang={$menu_group_info['lang']}')\">
      </td>
    </tr>
    <tr>
      <td><b>{$form['elements']['description']['label']} : </b></td>
      <td><textarea name=\"{$form['elements']['description']['form_element_name']}\">{$form['elements']['description']['form_element_value']}</textarea></td>
    </tr>
    <tr>
      <td><b>{$form['elements']['attributes']['label']} : </b></td>
      <td><textarea name=\"{$form['elements']['attributes']['form_element_name']}\">{$form['elements']['attributes']['form_element_value']}</textarea></td>
    </tr>
    <tr><td></td><td><input type=submit value=\"{$text['Save']}\"></td></tr>
  </form>
  </table>
        ";
$input_vars['page_title']  = $text['Editing_menu_item'];
$input_vars['page_header'] = $text['Editing_menu_item'];
//------------------- draw form - end ------------------------------------------

//----------------------------- context menu - begin ---------------------------
if($this_page_info['id']>0) {
    $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
    run('site/page/menu');
    $input_vars['page_menu']['page']['items'] = menu_page($this_page_info);
}

$input_vars['page_menu']['site']=Array('title'=>$text['Site_menu'],'items'=>Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------
?>
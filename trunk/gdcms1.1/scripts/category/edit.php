<?php

/*
  Изменение свойств категории
 */

run('category/functions');
run('lib/class_tree1');
run('lib/file_functions');


//------------------- site info - begin ----------------------------------------
run('site/menu');
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------

$input_vars['aed']=(isset($input_vars['aed']))?((int)$input_vars['aed']):0;

$this_category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$_SESSION['lang']}&category_id=";

#------------------------ get category info - begin ----------------------------
if (isset($input_vars['category_id'])) {
    $category_id = (int) $input_vars['category_id'];

    $this_category = new tree();
    $this_category->db = &$db;
    $this_category->name_id = 'category_id';
    $this_category->name_start = 'start';
    $this_category->name_finish = 'finish';
    $this_category->name_deep = 'deep';
    $this_category->name_table = $table_prefix . 'category';

    $this_category->where[] = " {$table_prefix}category.site_id={$site_id} ";

    $this_category->load_node($category_id);

    $this_category->info=adjust($this_category->info, $category_id);
    // prn('$this_category->info',$this_category->info);
    if (!$this_category->info)
        unset($input_vars['category_id']);

    $this_category_url = $this_category_url_prefix . $this_category->id;
}

if (!isset($input_vars['category_id']))
    redirect_to('index.php?action=category/list');
#------------------------ get category info - end ------------------------------



$list_of_languages = list_of_languages();

#------------------------------ create object - begin --------------------------
/*
  category_id           bigint(20)
  category_code         varchar(50)
  category_title        varchar(255)
  category_description  text
  is_deleted            tinyint(1)
  is_part_of            bigint(20)
  see_also              varchar(50)
 */
run('lib/class_edit/db_record_editor_common');
run('lib/class_edit/db_record_editor_field');
run('lib/class_edit/db_record_editor_field_integer');
run('lib/class_edit/db_record_editor_field_float');
run('lib/class_edit/db_record_editor_field_string');
run('lib/class_edit/db_record_editor_field_datetime');
run('lib/class_edit/db_record_editor_field_unix_timestamp');
run('lib/class_edit/db_record_editor_field_textarea');
run('lib/class_edit/db_record_editor_field_email');
run('lib/class_edit/db_record_editor_2');
db_record_editor_field::$text=db_record_editor_2::$text=$GLOBALS['text'];



if (isset($input_vars['db_record_editor_is_submitted']) && $input_vars['db_record_editor_is_submitted'] == 'yes') {
    $db_record_editor_category_description = '';
    //prn($list_of_languages);
    foreach ($list_of_languages as $lng) {
        $db_record_editor_category_description.="<{$lng['name']}>" . $_REQUEST['db_record_editor_category_description_' . $lng['name']] . "</{$lng['name']}>";
    }
    $_REQUEST['db_record_editor_category_description'] = & $db_record_editor_category_description;
    $_POST['db_record_editor_category_description'] = & $db_record_editor_category_description;
    $input_vars['db_record_editor_category_description'] = & $db_record_editor_category_description;
    //prn(checkStr($tmp));
}
//prn($input_vars);

$rep = new db_record_editor_2;
$rep->use_db($GLOBALS['db']);
$rep->debug = false;
#$rep->text=$text;
$rep->exclude = '^category_id$';
$rep->set_table("{$table_prefix}category");

# category_id           bigint(20)
$rep->field['category_id'] = new db_record_editor_field_integer(
                'category_id'
                , 'category_id'
                , 'integer:hidden=yes'
                , '#');

# category_code         varchar(50)
$rep->field['category_code'] = new db_record_editor_field_string(
                'category_code'
                , 'category_code'
                , 'string:maxlength=50&required=no&html_denied=yes'
                , text('Category_code'));

# category_title        varchar(255)
$rep->field['category_title'] = new db_record_editor_field_string(
                'category_title'
                , 'category_title'
                , 'string:maxlength=800&required=yes&html_denied=no'
                , text('Category_title'));

## is_deleted            tinyint(1)
#$rep->field['is_deleted']=new db_record_editor_field_integer(
#                 'is_deleted'
#                ,'is_deleted'
#                ,'integer(1='.rawurlencode('Да').'&0='.rawurlencode('Нет').')'
#                ,'Удалена');
# is_part_of            bigint(20)
if ($this_category->info['start'] > 0) {
    $list_of_categories = "SELECT * FROM {$table_prefix}category WHERE site_id={$site_id} ORDER BY start";
    $list_of_categories = db_getrows($list_of_categories);
    //prn($list_of_categories);
    $tmp = Array();
    foreach ($list_of_categories as $ct)
        $tmp[] = $ct['category_id'] . '=' . rawurlencode(str_repeat(' + ', $ct['deep']) . get_langstring($ct['category_title']));
    //prn($tmp);
    $list_of_categories = join('&', $tmp);

    $rep->field['is_part_of'] = new db_record_editor_field_integer(
                    'is_part_of'
                    , 'is_part_of'
                    , "integer($list_of_categories)"
                    , text('Category_move_into'));
}

# category_description  text
$rep->field['category_description'] = new db_record_editor_field_textarea(
                  'category_description'
                , 'category_description'
                , 'textarea'
                , text('category_description'));
//prn($rep->field['category_description']);


if ($this_category->info['start'] > 0)
    $rep->field['is_visible'] = new db_record_editor_field_integer(
                    'is_visible'
                    , 'is_visible'
                    , "integer(0=" . rawurlencode(text('negative_answer')) . "&1=" . rawurlencode(text('positive_answer')) . ")"
                    , text('category_is_visible'));


//prn($rep);
# prn(htmlencode($rep->draw_default_template()));

$rep->set_primary_key('category_id', $category_id);

#------------------------------ create object - end ----------------------------
#------------------------------ pre-process - begin ----------------------------
  if($rep->form_is_posted()){
      // check if category_code is unique
      $query="SELECT count(*) as n FROM {$table_prefix}category WHERE category_id<>{$category_id} AND category_code='".  DbStr($rep->field['category_code']->value)."'";
      $n_category_code=db_getonerow($query);
      if($n_category_code['n']>0){
          $rep->field['category_code']->posted_data=$rep->field['category_code']->value."-{$category_id}";
          $rep->field['category_code']->set_value($rep->field['category_code']->posted_data);
      }
  }
#------------------------------ pre-process - end ------------------------------
# process form data (update category properties)
$success = $rep->process();


# --------------------- post-process - begin -----------------------------------
if ($success) {
    function re_create_path($category_id, $site_id){
        global $table_prefix;
        $query = "select pa.*
                 from {$table_prefix}category pa, {$table_prefix}category ch
                 WHERE ch.category_id={$category_id} and ch.site_id={$site_id} and pa.site_id={$site_id}
                   and pa.start<=ch.start and ch.finish<=pa.finish
                 order by pa.start asc";
        $path = db_getrows($query);
        // prn($path);
        $tmp = Array();
        foreach ($path as $pa) {
            $tmp[] = ($pa['category_code'] ? $pa['category_code'] : $pa['category_id']);
        }
        $path = join('/', $tmp);
        $query = "UPDATE {$table_prefix}category
                  SET path='" . DbStr($path) . "'
                  WHERE category_id={$category_id}";
        //prn($query);
        db_execute($query);
    }

    #  ----------------------- move branch - begin ------------------------------
    if ($this_category->info['start'] > 0)
        if ($this_category->info['is_part_of'] != $rep->value_of('is_part_of')) {
            // вставить перемещение ветки №  $category_id
            // внутрь ветки $rep->value_of('is_part_of')
            if (!$this_category->move_to($rep->value_of('is_part_of'))) {
                // some errors occur
                // change to previous value
                $query = "UPDATE {$table_prefix}category
                    SET is_part_of=" . ((int) $this_category->info['is_part_of']) . "
                    WHERE category_id={$category_id}";
                db_execute($query);
            }
        }
    #  ----------------------- move branch - end -------------------------------

    // re_create_path($category_id, $site_id);
    // recreate paths for all the children
    //re_create_path($category_id, $site_id);
    $query = "select ch.*
             from {$table_prefix}category pa, {$table_prefix}category ch
             WHERE pa.category_id={$category_id} and ch.site_id={$site_id} and pa.site_id={$site_id}
               and pa.start<=ch.start and ch.finish<=pa.finish";
    $child_or_self = db_getrows($query);
    foreach($child_or_self as $ch){
        re_create_path($ch['category_id'], $site_id);
    }
    //prn($child_or_self);
    # ----------------- re-create modification time - begin ------------
    $date_lang_update="";
    //prn($this_category->info['date_lang_update_array']);
    foreach ($list_of_languages as $lng) {
        if(!isset($this_category->info['date_lang_update_array'][$lng['name']])){
            $this_category->info['date_lang_update_array'][$lng['name']]=date('Y-m-d H:i:s');
        }
        if(isset($input_vars['important_changes'][$lng['name']])){
            $this_category->info['date_lang_update_array'][$lng['name']]=date('Y-m-d H:i:s');
        }
        $date_lang_update.="<{$lng['name']}>{$this_category->info['date_lang_update_array'][$lng['name']]}</{$lng['name']}>";
    }


    $query = "UPDATE {$table_prefix}category
              SET date_last_changed=now(),
                  date_lang_update='" . DbStr($date_lang_update) . "'
              WHERE category_id={$category_id}";
    db_execute($query);

    // TODO re-create paths of the children
    # ----------------- re-create modification time - end --------------

    # ----------------- save date of important changes - begin -----------------
    // $this_category->info['date_lang_update_array'][$lng['name']]=$this_category->info['date_last_changed'];
    # ----------------- save date of important changes - end -------------------

    #  reload category info
    $this_category->load_node($category_id);
    $this_category->info=adjust($this_category->info, $category_id);
}
# --------------------- post-process - end -------------------------------------
#  ---------------------------- draw - begin -----------------------------------
# -------------------- get category parents - begin -------------------------
$this_category->get_parents();
$cnt = array_keys($this_category->parents);
foreach ($cnt as $i) {
    $this_category->parents[$i] = adjust($this_category->parents[$i], $this_category->id);

    if (isset($inherited_concept[$this_category->parents[$i]['category_id']])) {
        $this_category->parents[$i]['concepts'] = $inherited_concept[$this_category->parents[$i]['category_id']];
    } else {
        $this_category->parents[$i]['concepts'] = Array();
    }
}
#prn($this_category);
# -------------------- get category parents - end ---------------------------
# -------------------- get name of the neares parent - begin ----------------
$this_category->info['is_part_of_name'] = db_getonerow("SELECT category_title FROM {$table_prefix}category WHERE category_id=" . ( (int) $this_category->info['is_part_of'] ));
$this_category->info['is_part_of_name'] = $this_category->info['is_part_of_name']['category_title'];
#prn($this_category->info['is_part_of_name']);
# -------------------- get name of the neares parent - end ------------------


$form = $rep->draw_form();
$form['hidden_elements'].="<input type=hidden name=category_id value=\"{$rep->id}\">\n";

$input_vars['page_title'] =
        $input_vars['page_header'] = text('Category_edit');




// prn($list_of_languages);
$js_lang=Array();
foreach($list_of_languages as $l){
      $js_lang[]="\"{$l['name']}\":\"{$text[$l['name']]}\"";
}
$js_lang='{'.join(',',$js_lang).'}';
$input_vars['page_content'] =
        "
  <form action='index.php' method='post' name='db_record_editor_' style='width:700px;'>
  <input type=hidden name='action' value='category/edit'>
  <input type=hidden name='site_id' value='{$this_site_info['id']}'>
  <input type=hidden name='db_record_editor_is_submitted' value='yes'>
  <input type=hidden name='db_record_editor_category_id' value='{$rep->id}'>
  <input type=hidden name=category_id value='{$rep->id}'>
  <input type=hidden name=aed value='{$input_vars['aed']}'>


  <div class=label>{$form['elements']['category_title']->label}<sup style='color:red;weight:bold;'>*</sup></div>
  <div class=big>
    <input type=text
           name='{$form['elements']['category_title']->form_element_name}'
           id='{$form['elements']['category_title']->form_element_name}'
           value='{$form['elements']['category_title']->form_element_value}'>
    <script type=\"text/javascript\" src=\"scripts/lib/langstring.js\"></script>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langstring('{$form['elements']['category_title']->form_element_name}');
    </script>

  </div>";

if (isset($form['elements']['is_part_of'])) {
    $input_vars['page_content'].=
            "<div class=label>{$form['elements']['is_part_of']->label}</div>
  	<div class=big>
            <select name='{$form['elements']['is_part_of']->form_element_name}' id='{$form['elements']['is_part_of']->form_element_name}'>
                <option value=''></option>
                {$form['elements']['is_part_of']->form_element_options}
                </select>
        </div>";
}


//prn($list_of_languages);
foreach ($list_of_languages as $lng) {
    //prn($lng);
    //prn($form['elements']['category_description']->value);
    if(!isset($this_category->info['date_lang_update_array'][$lng['name']])){
        $this_category->info['date_lang_update_array'][$lng['name']]=$this_category->info['date_last_changed'];
    }
    $input_vars['page_content'].="
    <div class=label style='font-size:110%;'>
      {$form['elements']['category_description']->label} ({$lng['name']})
      ".text('Date').": {$this_category->info['date_lang_update_array'][$lng['name']]}
      <label><input type=\"checkbox\" name=\"important_changes[{$lng['name']}]\" value=\"".date('Y-m-d H:i:s')."\">".text('Important_changes')."</label>
    </div>
    <div class=big>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
     <textarea name='{$form['elements']['category_description']->form_element_name}_{$lng['name']}'
               id='{$form['elements']['category_description']->form_element_name}_{$lng['name']}'
               style='width:100%;height:500px;' class=\"wysiswyg\">"
            . checkStr(get_langstring($form['elements']['category_description']->value, $lng['name']))
            . "</textarea>
     <div align=right><input type=submit value='{$text['Save']}'></div>
    </div>
    ";
}

$input_vars['page_content'].= ( isset($form['elements']['is_visible']) ? "
   <div class=label>{$form['elements']['is_visible']->label}</div>
   <div class=big>
      " . draw_radio($form['elements']['is_visible']->value, $form['elements']['is_visible']->enum, $form['elements']['is_visible']->form_element_name) . "
   </div>" : '') . "

   <div class=label>{$form['elements']['category_code']->label}</div>
   <div class=big>
    <input type=text
           name='{$form['elements']['category_code']->form_element_name}'
           id='{$form['elements']['category_code']->form_element_name}'
           value='".($form['elements']['category_code']->form_element_value?$form['elements']['category_code']->form_element_value:encode_dir_name(get_langstring($form['elements']['category_title']->value, default_language)))."'>
   </div>
   <br/><input type=submit value='{$text['Save']}'>
   </form>
   ";

#  ---------------------------- adjust nodes - begin ---------------------------
$cnt = array_keys($this_category->parents);
foreach ($cnt as $i)
    $this_category->parents[$i] = adjust($this_category->parents[$i], $this_category->id);

$this_category->info = adjust($this_category->info, $this_category->id);
#  ---------------------------- adjust nodes - end -----------------------------


// enable TinyMCE or markitup
if($input_vars['aed']==1){

$tor= "
           <!-- Load TinyMCE -->
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/tiny_mce/jquery.tinymce.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/tiny_mce_start.js\"></script>

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  init_links();
                  tinymce_init('textarea.wysiswyg',
                     { external_link_list_url : \"index.php?action=site/filechooser/tiny_mce_link_list&site_id={$site_id}\",
                       external_image_list_url : \"index.php?action=site/filechooser/tiny_mce_image_list&site_id={$site_id}\",
                       language : \"".substr($_SESSION['lang'],0,2)."\"});
              });
           </script>
           <!-- /TinyMCE -->
    ";
}else{
$tor= "
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup.js\"></script>
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  init_links();
                  $('textarea.wysiswyg').markItUp(mySettings);
              });
           </script>
    ";
}

$tor.= "
      <style type=\"text/css\">
      <!--
      .menu_block
      {
        position:absolute;
        border:solid 1px blue;
        background-color: #e0e0e0;
        padding:5px;
        text-align:left;
      }

      -->
      </style>
      <script type=\"text/javascript\">
      <!--
        var report_prev_menu;
        var report_href;
        function report_change_state(cid)
        {
            var lay=document.getElementById(cid);
            if (lay.style.display==\"none\")
            {
               if(report_prev_menu) report_prev_menu.style.display=\"none\";
               lay.style.display=\"block\";
               report_prev_menu=lay;
            }
            else
            {
               lay.style.display=\"none\";
               report_prev_menu=null;
            }
            report_href=true;
        }

        function report_hide_menu()
        {
          if(report_prev_menu && !report_href) report_prev_menu.style.display=\"none\";
          report_href=false;
        }
        document.onclick=report_hide_menu;
      // -->
      </script>



";


$tor.="<div style='padding-left:25px;'>\n";
foreach ($this_category->parents as $row) {

    $parent_url = $this_category_url_prefix . $row['category_id'];
    $tor.="
      <div style=\"padding-left:{$row['padding']}px;\">
         <a href=# style='margin-left:-25px;' class=context_menu_link onclick=\"report_change_state('cm{$row['category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width=20 height=15></a>
         <a href=\"{$row['URL']}\" title=\"{$row['category_title']}\" "
            . ( ($row['is_visible'] == '0')?" style='color:silver;'":'' ).'>'
            . ($row['category_code'] ? $row['category_code'] : $row['category_id']) . " &nbsp;&nbsp;&nbsp; {$row['title_short']}</a><br/>
         <a href='{$parent_url}' style='color:silver;'>" . checkStr($parent_url) . "</a>
         <br>
          ";

          $tor.="<div id=\"cm{$row['category_id']}\" class=menu_block style='display:none;'>";
          foreach ($row['context_menu'] as $cm) {
               if ($cm['url'] != '')
                   $tor.="<nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr><br>";
               else
                   $tor.="<nobr><b>{$cm['html']}</b></nobr><br>";
          }
          $tor.="</div>";
    $tor.="</div>";
}



$category = &$this_category->info;
$tor.="
<div style=\"padding-left:{$category['padding']}px;\">
    <a href=\"#\" style='margin-left:-25px;' class=context_menu_link onclick=\"report_change_state('cm{$category['category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width=20 height=15></a>
    <span title=\"{$category['category_title']}\" style='" . (($category['is_visible'] == 0) ? " color:silver;" : '') . ";font-size:150%;'>
    " . ($category['category_code'] ? $category['category_code'] : $category['category_id']) . " &nbsp;&nbsp;&nbsp; {$category['title_short']}<br/>
    <a style='font-size:80%;color:silver;' href='{$this_category_url}'>" . checkStr($this_category_url) . "</a>
    </span>
    <br>
    <div id=\"cm{$category['category_id']}\" class=menu_block style='display:none;'>";
        foreach ($category['context_menu'] as $cm) {
            if ($cm['url'] != '')
                $tor.="<nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr><br>";
            else
                $tor.="<nobr><b>{$cm['html']}</b></nobr><br>";
        }
       $tor.="
    </div>
</div>
   ";
$input_vars['page_content'] = "  <div>{$rep->messages}</div>" . $tor . '  ' . $input_vars['page_content'];
#   $input_vars['page']['content'] = process_template('category/edit',Array(
#    'form'=>$form
#   ,'parents'=>$this_category->parents
#   ,'category'=>$this_category->info
#   ));
#  ---------------------------- draw - end -------------------------------------
# category context menu
$input_vars['page_menu']['category'] = Array('title' => text('Category'), 'items' => Array());
$input_vars['page_menu']['category']['items'] = menu_category($this_category->info);
//prn($input_vars['page_menu']['category']);
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
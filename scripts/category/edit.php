<?php

/*
  Изменение свойств категории
 */

run('category/functions');
run('lib/class_tree1');



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
// if wyswyg is enabled
$input_vars['aed'] = (isset($input_vars['aed'])) ? ((int) $input_vars['aed']) : 0;

// save preview URL
$this_category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$_SESSION['lang']}&category_id=";

#------------------------ get category info - begin ----------------------------
if (isset($input_vars['category_id'])) {
    $category_id = (int) $input_vars['category_id'];

    $this_category = new tree();

    $this_category->name_id = 'category_id';
    $this_category->name_start = 'start';
    $this_category->name_finish = 'finish';
    $this_category->name_deep = 'deep';
    $this_category->name_table = '<<tp>>category';

    $this_category->where[] = " <<tp>>category.site_id={$site_id} ";

    $this_category->load_node($category_id);

    $this_category->info = adjust($this_category->info, $category_id);
    // prn('$this_category->info',$this_category->info);
    if (!$this_category->info) {
        unset($input_vars['category_id']);
    }

    $this_category_url = $this_category_url_prefix . $this_category->id;
}

if (!isset($input_vars['category_id'])) {
    redirect_to('index.php?action=category/list');
}
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
db_record_editor_field::$text = db_record_editor_2::$text = $GLOBALS['text'];



if (isset($input_vars['db_record_editor_is_submitted']) && $input_vars['db_record_editor_is_submitted'] == 'yes') {
    $db_record_editor_category_description = '';
    $db_record_editor_category_meta = '';
    $db_record_editor_category_description_short='';
    //prn($list_of_languages);
    foreach ($list_of_languages as $lng) {
        $db_record_editor_category_description.="<{$lng['name']}>" . $_REQUEST['db_record_editor_category_description_' . $lng['name']] . "</{$lng['name']}>";
        $db_record_editor_category_meta.="<{$lng['name']}>" . $_REQUEST['db_record_editor_category_meta_' . $lng['name']] . "</{$lng['name']}>";
        
        
        $db_record_editor_category_description_short.="<{$lng['name']}>" . $_REQUEST['db_record_editor_category_description_short_' . $lng['name']] . "</{$lng['name']}>";
        
    }
    $_REQUEST['db_record_editor_category_description'] = & $db_record_editor_category_description;
    $_POST['db_record_editor_category_description'] = & $db_record_editor_category_description;
    $input_vars['db_record_editor_category_description'] = & $db_record_editor_category_description;
    
    $_REQUEST['db_record_editor_category_description_short'] = & $db_record_editor_category_description_short;
    $_POST['db_record_editor_category_description_short'] = & $db_record_editor_category_description_short;
    $input_vars['db_record_editor_category_description_short'] = & $db_record_editor_category_description_short;
    //prn(checkStr($tmp));
    $_REQUEST['db_record_editor_category_meta'] = & $db_record_editor_category_meta;
    $_POST['db_record_editor_category_meta'] = & $db_record_editor_category_meta;
    $input_vars['db_record_editor_category_meta'] = & $db_record_editor_category_meta;
}
//prn($input_vars);

$rep = new db_record_editor_2;

$rep->debug = false;
#$rep->text=$text;
$rep->exclude = '^category_id$';
$rep->set_table("<<tp>>category");

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
        , 'string:maxlength=500&required=no&html_denied=yes'
        , text('Category_code'));

# category_title        varchar(255)
$rep->field['category_title'] = new db_record_editor_field_string(
        'category_title'
        , 'category_title'
        , 'string:maxlength=800&required=yes&html_denied=no'
        , text('Category_title'));


$rep->field['category_title_short'] = new db_record_editor_field_string(
        'category_title_short'
        , 'category_title_short'
        , 'string:maxlength=800&required=no&html_denied=no'
        , text('Category_title_short'));




## is_deleted            tinyint(1)
#$rep->field['is_deleted']=new db_record_editor_field_integer(
#                 'is_deleted'
#                ,'is_deleted'
#                ,'integer(1='.rawurlencode('Да').'&0='.rawurlencode('Нет').')'
#                ,'Удалена');
# is_part_of            bigint(20)
if ($this_category->info['start'] > 0) {
    $list_of_categories = "SELECT * FROM <<tp>>category WHERE site_id={$site_id} ORDER BY start";
    $list_of_categories = \e::db_getrows($list_of_categories);
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

# category_description  text
$rep->field['category_description_short'] = new db_record_editor_field_textarea(
        'category_description_short'
        , 'category_description_short'
        , 'textarea'
        , text('category_description_short'));
//prn($rep->field['category_description']);

$rep->field['category_meta'] = new db_record_editor_field_textarea(
        'category_meta'
        , 'category_meta'
        , 'textarea'
        , text('category_meta'));


if ($this_category->info['start'] > 0) {
    $rep->field['is_visible'] = new db_record_editor_field_integer(
            'is_visible'
            , 'is_visible'
            , "integer(0=" . rawurlencode(text('negative_answer')) . "&1=" . rawurlencode(text('positive_answer')) . ")"
            , text('category_is_visible'));
}


//prn($rep);
# prn(htmlencode($rep->draw_default_template()));

$rep->set_primary_key('category_id', $category_id);

#------------------------------ create object - end ----------------------------
#------------------------------ pre-process - begin ----------------------------
if ($rep->form_is_posted()) {
    // check if category_code is unique
    $query = "SELECT count(*) as n FROM <<tp>>category WHERE category_id<>{$category_id} AND category_code='" . \e::db_escape($rep->field['category_code']->value) . "'";
    $n_category_code =\e::db_getonerow($query);
    if ($n_category_code['n'] > 0) {
        $rep->field['category_code']->posted_data = $rep->field['category_code']->value . "-{$category_id}";
        $rep->field['category_code']->set_value($rep->field['category_code']->posted_data);
    }
}
#------------------------------ pre-process - end ------------------------------
# process form data (update category properties)
$success = $rep->process();


# --------------------- post-process - begin -----------------------------------
if ($success) {

    function re_create_path($category_id, $site_id) {
        $query = "select pa.*
                 from <<tp>>category pa, <<tp>>category ch
                 WHERE ch.category_id={$category_id} and ch.site_id={$site_id} and pa.site_id={$site_id}
                   and pa.start<=ch.start and ch.finish<=pa.finish
                 order by pa.start asc";
        $path = \e::db_getrows($query);
        // prn($path);
        $tmp = Array();
        foreach ($path as $pa) {
            $tmp[] = ($pa['category_code'] ? $pa['category_code'] : $pa['category_id']);
        }
        $path = join('/', $tmp);
        $query = "UPDATE <<tp>>category
                  SET path='" . \e::db_escape($path) . "'
                  WHERE category_id={$category_id}";
        //prn($query);
        \e::db_execute($query);
    }

    #  ----------------------- move branch - begin ------------------------------
    if ($this_category->info['start'] > 0 && $this_category->info['is_part_of'] != $rep->value_of('is_part_of')) {
        // вставить перемещение ветки №  $category_id
        // внутрь ветки $rep->value_of('is_part_of')
        if (!$this_category->move_to($rep->value_of('is_part_of'))) {
            // some errors occur
            // change to previous value
            $query = "UPDATE <<tp>>category
                    SET is_part_of=" . ((int) $this_category->info['is_part_of']) . "
                    WHERE category_id={$category_id}";
            \e::db_execute($query);
        }
    }
    #  ----------------------- move branch - end -------------------------------
    // re_create_path($category_id, $site_id);
    // recreate paths for all the children
    //re_create_path($category_id, $site_id);
    $query = "select ch.*
             from <<tp>>category pa, <<tp>>category ch
             WHERE pa.category_id={$category_id} and ch.site_id={$site_id} and pa.site_id={$site_id}
               and pa.start<=ch.start and ch.finish<=pa.finish";
    $child_or_self = \e::db_getrows($query);
    foreach ($child_or_self as $ch) {
        re_create_path($ch['category_id'], $site_id);
    }
    //prn($child_or_self);
    # ----------------- re-create modification time - begin ------------
    $date_lang_update = "";
    //prn($this_category->info['date_lang_update_array']);
    foreach ($list_of_languages as $lng) {
        if (!isset($this_category->info['date_lang_update_array'][$lng['name']])) {
            $this_category->info['date_lang_update_array'][$lng['name']] = date('Y-m-d H:i:s');
        }
        if (isset($input_vars['important_changes'][$lng['name']])) {
            $this_category->info['date_lang_update_array'][$lng['name']] = date('Y-m-d H:i:s');
        }
        $date_lang_update.="<{$lng['name']}>{$this_category->info['date_lang_update_array'][$lng['name']]}</{$lng['name']}>";
    }


    $query = "UPDATE <<tp>>category
              SET date_last_changed=now(),
                  date_lang_update='" . \e::db_escape($date_lang_update) . "'
              WHERE category_id={$category_id}";
    \e::db_execute($query);

    // TODO re-create paths of the children
    # ----------------- re-create modification time - end --------------
    # ----------------- save date of important changes - begin -----------------
    // $this_category->info['date_lang_update_array'][$lng['name']]=$this_category->info['date_last_changed'];
    # ----------------- save date of important changes - end -------------------
    # 
    # ----------------- upload icon - begin ------------------------------------
    // prn($_FILES);
    if(isset($_FILES['category_icon']) && $_FILES['category_icon']['error']==0){
        run('lib/img');
        $relative_dir="gallery/".date('Y').'/'.date('m');
        $dir="{$this_site_info['site_root_dir']}/{$relative_dir}";
        \core\fileutils::path_create($this_site_info['site_root_dir'], "{$dir}/");

        $newFileName="category-{$category_id}-".\core\fileutils::encode_file_name($_FILES['category_icon']['name']);

        if(move_uploaded_file($_FILES['category_icon']['tmp_name'], "{$dir}/{$newFileName}") ){
            
            // ---------------- delete previous icons - begin ------------------
            if($this_category->info['category_icon'] && is_array($this_category->info['category_icon'])){
                foreach($this_category->info['category_icon'] as $pt){
                    $pt=trim($pt);
                    if(strlen($pt)>0){
                        $path=realpath("{$this_site_info['site_root_dir']}/{$pt}");
                        if($path && strncmp( $path , $this_site_info['site_root_dir'] , strlen($this_site_info['site_root_dir']) )==0){
                            unlink($path);
                        }
                    }
                }
            }
            // ---------------- delete previous icons - end --------------------
            
            // ---------------- upload new icons - begin -----------------------
            $smallFileName="category-{$category_id}-small-".\core\fileutils::encode_file_name($_FILES['category_icon']['name']);
            img_resize("{$dir}/{$newFileName}", "{$dir}/{$smallFileName}", \e::config('gallery_small_image_width'), \e::config('gallery_small_image_height'), $type = "circumscribe");
            $category_icon=['small'=>"{$relative_dir}/{$smallFileName}", "full"=>"{$relative_dir}/{$newFileName}"];
            $query = "UPDATE <<tp>>category
                      SET category_icon='" . \e::db_escape(json_encode($category_icon)) . "'
                      WHERE category_id={$category_id}";
            \e::db_execute($query);
            // ---------------- upload new icons - end -------------------------
        }
    }
    # ----------------- upload icon - end --------------------------------------
    # 
    # 
    #  reload category info
    $this_category->load_node($category_id);
    $this_category->info = adjust($this_category->info, $category_id);
    
    // log
    ml('category/edit', \e::post());
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
$this_category->info['is_part_of_name'] =\e::db_getonerow("SELECT category_title FROM <<tp>>category WHERE category_id=" . ( (int) $this_category->info['is_part_of'] ));
$this_category->info['is_part_of_name'] = $this_category->info['is_part_of_name']['category_title'];
#prn($this_category->info['is_part_of_name']);
# -------------------- get name of the neares parent - end ------------------


$form = $rep->draw_form();
$form['hidden_elements'].="<input type=hidden name=category_id value=\"{$rep->id}\">\n";

$input_vars['page_title'] = $input_vars['page_header'] = text('Category_edit');




// prn($list_of_languages);
$js_lang = Array();
foreach ($list_of_languages as $l) {
    $js_lang[] = "\"{$l['name']}\":\"{$text[$l['name']]}\"";
}
$js_lang = '{' . join(',', $js_lang) . '}';
$input_vars['page_content'] = "
  <form action='index.php' method='post' name='db_record_editor_' enctype=\"multipart/form-data\">
  <input type=hidden name='action' value='category/edit'>
  <input type=hidden name='site_id' value='{$this_site_info['id']}'>
  <input type=hidden name='db_record_editor_is_submitted' value='yes'>
  <input type=hidden name='db_record_editor_category_id' value='{$rep->id}'>
  <input type=hidden name=category_id value='{$rep->id}'>
  <input type=hidden name=aed value='{$input_vars['aed']}'>

  <div><!-- 
   --><span class=blk8>
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

  </div>   
</span><!-- 
   --><span class=blk4>
    <div class=label>" . text('Icon') . " :</div>
    <div class=category_icon>"
         .( $this_category->info['category_icon']
            ? "<a class=\"delete_link\" href=\"index.php?action=category/delete_icon&category_id=".
               $this_category->info['category_id']."\">&times;</a>"
            . "<a href=\"{$this_site_info['site_root_url']}/{$this_category->info['category_icon']['full']}\" target=_blank>"
            . "<img src=\"{$this_site_info['site_root_url']}/{$this_category->info['category_icon']['small']}\" style=\"max-width:100%;\">"
           :''  )."</a></div>
    <input type=\"file\" name=\"category_icon\">
   </span><!-- 
--></div>
";

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
    if (!isset($this_category->info['date_lang_update_array'][$lng['name']])) {
        $this_category->info['date_lang_update_array'][$lng['name']] = $this_category->info['date_last_changed'];
    }
    $input_vars['page_content'].="
    <div class=label style='font-size:110%;'>
      {$form['elements']['category_description']->label} ({$lng['name']})
      " . text('Date') . ": {$this_category->info['date_lang_update_array'][$lng['name']]}
      <label><input type=\"checkbox\" name=\"important_changes[{$lng['name']}]\" value=\"" . date('Y-m-d H:i:s') . "\">" . text('Important_changes') . "</label>
    </div>
    <div class=big>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=photo/json&lang={$lng['name']}&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
     <textarea name='{$form['elements']['category_description']->form_element_name}_{$lng['name']}'
               id='{$form['elements']['category_description']->form_element_name}_{$lng['name']}'
               style='width:100%;height:300px;' class=\"wysiswyg\">"
            . htmlspecialchars(get_langstring($form['elements']['category_description']->value, $lng['name']))
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
           value='" . ($form['elements']['category_code']->form_element_value ? $form['elements']['category_code']->form_element_value : ($rep->id)) . "'>
   </div>
   

  <div class=label><h3>{$form['elements']['category_title_short']->label}</h3></div>
  <div class=big>
    <input type=text
           name='{$form['elements']['category_title_short']->form_element_name}'
           id='{$form['elements']['category_title_short']->form_element_name}'
           value='{$form['elements']['category_title_short']->form_element_value}'>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langstring('{$form['elements']['category_title_short']->form_element_name}');
    </script>

  </div> 
";


            
$input_vars['page_content'].="<h3>{$form['elements']['category_description_short']->label}</h3>";

//prn($list_of_languages);
foreach ($list_of_languages as $lng) {
    $input_vars['page_content'].="
    <div class=label style='font-size:110%;'>
      {$form['elements']['category_description_short']->label} ({$lng['name']})
    </div>
    <div class=big>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=photo/json&lang={$lng['name']}&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
     <textarea name='{$form['elements']['category_description_short']->form_element_name}_{$lng['name']}'
               id='{$form['elements']['category_description_short']->form_element_name}_{$lng['name']}'
               style='width:100%;height:100px;' class=\"wysiswyg\">"
            . htmlspecialchars(get_langstring($form['elements']['category_description_short']->value, $lng['name']))
            . "</textarea>
    </div>
    ";
}
$input_vars['page_content'].="
<input type=submit value='{$text['Save']}'>
";
            
            
            
            
            
            
    $input_vars['page_content'].="
<script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/meta-tags-insert.js\"></script>

  ";
$input_vars['page_content'].="<h3>{$form['elements']['category_meta']->label}</h3>";
//prn($list_of_languages);
foreach ($list_of_languages as $lng) {

    $input_vars['page_content'].="
    <div class=label style='font-size:110%;'>
      {$form['elements']['category_meta']->label} ({$lng['name']})
    </div>
    <div class=big>
     <textarea name='{$form['elements']['category_meta']->form_element_name}_{$lng['name']}'
               id='{$form['elements']['category_meta']->form_element_name}_{$lng['name']}'
               style='width:100%;height:100px;'>"
            . htmlspecialchars(get_langstring($form['elements']['category_meta']->value, $lng['name']))
            . "</textarea>
  <script type=\"text/javascript\">
  $(document).ready(function(){
     metaTagsButtons('{$form['elements']['category_meta']->form_element_name}_{$lng['name']}');
  });
  </script>
    </div>
    ";
}








$input_vars['page_content'].="
   <br/><input type=submit value='{$text['Save']}'>
   </form>
   ";

#  ---------------------------- adjust nodes - begin ---------------------------
$cnt = array_keys($this_category->parents);
foreach ($cnt as $i) {
    $this_category->parents[$i] = adjust($this_category->parents[$i], $this_category->id);
    //prn($this_category->parents[$i]);
}
$this_category->info = adjust($this_category->info, $this_category->id);
#  ---------------------------- adjust nodes - end -----------------------------
// enable TinyMCE or markitup
if ($input_vars['aed'] == 1) {

    $tor = "
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
                       language : \"" . substr($_SESSION['lang'], 0, 2) . "\"});
              });
           </script>
           <!-- /TinyMCE -->
    ";
} else {
    $tor = "
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup.js\"></script>
           <!-- <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/jquery.ns-autogrow.min.js\"></script> -->
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />



           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  init_links();
                  $('textarea.wysiswyg').markItUp(mySettings);
                  //$('textarea.wysiswyg').autogrow({vertical: true, horizontal: false});
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
            . ( ($row['is_visible'] == '0') ? " style='color:silver;'" : '' ) . '>'
            . ($row['category_code'] ? $row['category_code'] : $row['category_id']) . " &nbsp;&nbsp;&nbsp; {$row['category_title_short']}</a><br/>
         <a href='{$parent_url}' style='color:silver;'>" . htmlspecialchars($parent_url) . "</a>
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
    " . ($category['category_code'] ? $category['category_code'] : $category['category_id']) . " &nbsp;&nbsp;&nbsp; {$category['category_title_short']}<br/>
    <a style='font-size:80%;color:silver;' href='{$this_category_url}'>" . htmlspecialchars($this_category_url) . "</a>
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


$input_vars['page_content'] .= 
" <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/select2/css/select2.min.css\" />
 <script type=\"text/javascript\" charset=\"UTF-8\" src=\"./scripts/lib/select2/js/select2.full.min.js\"></script>
 <script type=\"text/javascript\">
      $(function(){
          $('select').select2();
      });
  </script>";
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
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
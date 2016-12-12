<?php

/*

 */

run('ec/category/functions');
run('lib/class_tree1');
run('ec/item/functions');



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
#------------------------ get category info - begin ----------------------------
if (isset($input_vars['ec_category_id'])) {
    $ec_category_id = (int) $input_vars['ec_category_id'];

    $this_category = new tree();
    $this_category->name_id = 'ec_category_id';
    $this_category->name_start = 'start';
    $this_category->name_finish = 'finish';
    $this_category->name_deep = 'deep';
    $this_category->name_table = '<<tp>>ec_category';

    $this_category->where[] = " <<tp>>ec_category.site_id={$site_id} ";

    $this_category->load_node($ec_category_id);

    # prn('$this_category->info',$this_category->info);
    if (!$this_category->info)
        unset($input_vars['ec_category_id']);
}

if (!isset($input_vars['ec_category_id']))
    redirect_to('index.php?action=ec/category/list');
#------------------------ get category info - end ------------------------------
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

db_record_editor_field::$text=$GLOBALS['text'];
//db_record_editor_2::$text=$GLOBALS['text'];



$rep = new db_record_editor_2;

$rep->debug = false;
#$rep->text=$text;
$rep->exclude = '^ec_category_id$';
$rep->set_table("<<tp>>ec_category");

# category_id           bigint(20)
$rep->field['ec_category_id'] = new db_record_editor_field_integer(
        'ec_category_id'
        , 'ec_category_id'
        , 'integer:hidden=yes'
        , '#');

# category_code         varchar(50)
$rep->field['ec_category_code'] = new db_record_editor_field_string(
        'ec_category_code'
        , 'ec_category_code'
        , 'string:maxlength=50&required=no&html_denied=yes'
        , text('ec_category_code'));

# category_title        varchar(255)
$rep->field['ec_category_title'] = new db_record_editor_field_string(
        'ec_category_title'
        , 'ec_category_title'
        , 'string:maxlength=1024&required=yes&html_denied=no'
        , text('ec_category_title'));


# is_part_of            bigint(20)
if ($this_category->info['start'] > 0) {
    $list_of_categories = "SELECT * FROM <<tp>>ec_category WHERE site_id={$site_id} ORDER BY start";
    $list_of_categories = \e::db_getrows($list_of_categories);
    //prn($list_of_categories);
    $tmp = Array();
    foreach ($list_of_categories as $ct) {
        $tmp[] = $ct['ec_category_id'] . '=' . rawurlencode(str_repeat(' + ', $ct['deep']) . get_langstring($ct['ec_category_title']));
    }
    //prn($tmp);
    $list_of_categories = join('&', $tmp);

    $rep->field['is_part_of'] = new db_record_editor_field_integer(
            'is_part_of'
            , 'is_part_of'
            , "integer($list_of_categories)"
            , text('ec_category_move_into'));
}

# category_description  text
$rep->field['ec_category_description'] = new db_record_editor_field_textarea(
        'ec_category_description'
        , 'ec_category_description'
        , 'textarea'
        , text('ec_category_description'));



if ($this_category->info['start'] > 0)
    $rep->field['is_visible'] = new db_record_editor_field_integer(
            'is_visible'
            , 'is_visible'
            , "integer(0=" . rawurlencode(text('negative_answer')) . "&1=" . rawurlencode(text('positive_answer')) . ")"
            , text('ec_category_is_visible'));




# ------------------------ get list of onnullamount handlers - begin ----------
# these handlers are located in ec/item/functions.php
$tmp = get_defined_functions();
//prn($tmp['user']);
$cnt = count($tmp['user']);
$list_of_onnullamount_handlers = Array();
for ($i = 0; $i < $cnt; $i++) {
    if (!preg_match('/^onnullamount_/', $tmp['user'][$i])) {
        unset($tmp['user'][$i]);
        continue;
    }
    $list_of_onnullamount_handlers[$tmp['user'][$i]] = isset($text[$tmp['user'][$i]]) ? $text[$tmp['user'][$i]] : $tmp['user'][$i];
}
//prn($list_of_onnullamount_handlers);
//prn($rep->enum($list_of_onnullamount_handlers));
# ------------------------ get list of onnullamount handlers - end ------------
$rep->field['ec_item_onnullamount'] = new db_record_editor_field_string(
        'ec_item_onnullamount'
        , 'ec_item_onnullamount'
        , "string(" . $rep->enum($list_of_onnullamount_handlers) . ")"
        , text('OnNullAmount'));


# prn($rep);
# prn(htmlencode($rep->draw_default_template()));

$rep->set_primary_key('ec_category_id', $ec_category_id);

#------------------------------ create object - end ----------------------------
#------------------------------ pre-process - begin ----------------------------
/*
  if($rep->form_is_posted())
  {
  }
 */
#------------------------------ pre-process - end ------------------------------
# process form data (update category properties)
$success = $rep->process();

// echo ("Success=$success");
# --------------------- post-process - begin -----------------------------------
if ($success) {
    #  ----------------------- move branch - begin ------------------------------
    if ($this_category->info['start'] > 0)
        if ($this_category->info['is_part_of'] != $rep->value_of('is_part_of')) {

            if (!$this_category->move_to($rep->value_of('is_part_of'))) {
                // some errors occur
                // change to previous value
                $query = "UPDATE <<tp>>category
                    SET is_part_of=" . ((int) $this_category->info['is_part_of']) . "
                    WHERE category_id={$ec_category_id}";
                \e::db_execute($query);
            }
        }
    #  ----------------------- move branch - end --------------------------------
    #  ----------------------- save additional fields - begin -------------------
    //prn($input_vars['ec_category_item_field']);
    foreach ($input_vars['ec_category_item_field'] as $ec_category_item_field_id => $val) {
        //prn($val['ordering'],checkStr($val['title']),$val['options']);
        $val['title'] = trim($val['title']);
        if (strlen($val['title']) > 0) {
            $query = "replace <<tp>>ec_category_item_field(
                        ec_category_item_field_id,
                        site_id,
                        ec_category_id,
                        ec_category_item_field_title,
                        ec_category_item_field_options,
                        ec_category_item_field_ordering,
                        ec_category_item_field_type
                    ) values(
                       {$ec_category_item_field_id},
                       {$this_site_info['id']},
                       {$ec_category_id},
                       '" . \e::db_escape($val['title']) . "'  ,
                       '" . \e::db_escape($val['options']) . "',
                        " . ((int) $val['ordering']) . ",
                       '" . \e::db_escape($val['type']) . "'
                    )";
            //prn($query);
            \e::db_execute($query);
        } elseif ($ec_category_item_field_id > 0) {
            $query = "delete from <<tp>>ec_category_item_field
                     where ec_category_item_field_id={$ec_category_item_field_id}
                       and site_id = {$this_site_info['id']}
                       and ec_category_id = {$ec_category_id}
                     ";
            //prn($query);
            \e::db_execute($query);

            // delete field values
            $query = "delete from <<tp>>ec_category_item_field_value
                     where ec_category_item_field_id={$ec_category_item_field_id}";
            \e::db_execute($query);
        }
    }
    #  ----------------------- save additional fields - end ---------------------
    #  reload category info
    $this_category->load_node($ec_category_id);
    
}
# --------------------- post-process - end -------------------------------------
#  ---------------------------- draw - begin -----------------------------------
# -------------------- get category parents - begin -------------------------
$this_category->get_parents();
$cnt = array_keys($this_category->parents);
foreach ($cnt as $i) {
    $this_category->parents[$i] = ec_adjust($this_category->parents[$i], $this_category->id);

    if (isset($inherited_concept[$this_category->parents[$i]['ec_category_id']])) {
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
//prn($form);
# -------------------- draw additional fields - begin ------------------------
$types = Array('string' => text('String'), 'number' => text('Number'), 'enum' => text('Enumerator'));

$query = "select *
           from <<tp>>ec_category_item_field
           where site_id={$this_site_info['id']}
             and ec_category_id={$ec_category_id}
           order by ec_category_item_field_ordering ASC
           ";
$ec_category_item_field = \e::db_getrows($query);
//prn($ec_category_item_field);
$category_item_field_form = "";
foreach ($ec_category_item_field as $fld) {
    $category_item_field_form.="
        <div class=\"af\">
           <a class=delbtn href=\"javascript:void(delete_field('{$fld['ec_category_item_field_id']}'))\">&times;</a>

           <b>" . text('field_ordering') . ":</b><br/>
           <input type=text name=\"ec_category_item_field[{$fld['ec_category_item_field_id']}][ordering]\" value=\"{$fld['ec_category_item_field_ordering']}\"><br/>

           <br>
           <div>
             <b>" . text('field_title') . ":</b><br/>
             <input type=text name=\"ec_category_item_field[{$fld['ec_category_item_field_id']}][title]\" id=\"ec_category_item_field_title{$fld['ec_category_item_field_id']}\" value=\"{$fld['ec_category_item_field_title']}\">
             <script type=\"text/javascript\">
                draw_langstring('ec_category_item_field_title{$fld['ec_category_item_field_id']}');
             </script>
           </div>

           <br/><b>" . text('field_type') . ":</b><br/>
           <select style='width:100%;' name=\"ec_category_item_field[{$fld['ec_category_item_field_id']}][type]\">" . draw_options($fld['ec_category_item_field_type'], $types) . "</select><br/>

           <br/><b>" . text('field_options') . ":</b><br/>
           <textarea style='width:100%;height:150px;' name=\"ec_category_item_field[{$fld['ec_category_item_field_id']}][options]\">" . htmlspecialchars($fld['ec_category_item_field_options']) . "</textarea><br/>
        </div>
        ";
}

$category_item_field_form.="
        <h3 style='text-align:left'>" . text('Add_new_field') . "</h3>
        <div class=\"af\">
        <br><b>" . text('field_ordering') . ":</b><br>
        <input type=text name=\"ec_category_item_field[0][ordering]\" value=\"\" style=\"width:100%;\"><br>

        <div><br><b>" . text('field_title') . ":</b><br>
        <input type=text name=\"ec_category_item_field[0][title]\" id=\"ec_category_item_field_title0\" value=\"\" style=\"width:100%;\">
        <script type=\"text/javascript\">
             draw_langstring('ec_category_item_field_title0');
        </script>
        </div>

        <br/><b>" . text('field_type') . ":</b><br/>
        <select style='width:100%;' name=\"ec_category_item_field[0][type]\">" . draw_options('String', $types) . "</select><br/>

        <br><b>" . text('field_options') . ":</b><br>
        <textarea style='width:100%;height:150px;' name=\"ec_category_item_field[0][options]\"></textarea><br>
        </div>
    ";

$category_item_field_form = "
      <style>
        a.delbtn{
            color:red;
            text-decoration:none;
            border:1px solid red;
            padding:2px;
            font-weight:bold;
            font-size:10pt;
            float:right;
            display:block;
        }

        table.nobrd td{border:none;}
        table.nobrd {margin:0px;border:none;width:99%;}
      </style>
      <script type=\"text/javascript\">
          function delete_field(fid)
          {
             var f=document.getElementById('ec_category_item_field_title'+fid);
             f.value='';
             var editform=document.getElementById('db_record_editor_');
             editform.submit();
          }
      </script>
      $category_item_field_form
    ";
# -------------------- draw additional fields - end --------------------------



$input_vars['page_title'] = $input_vars['page_header'] = text('ec_category_edit');

$input_vars['page_content'] = "
  <style>
  form#db_record_editor_ input[type=\"text\"]{
      width:100%;
  }
  form#db_record_editor_ b{
      font-size:120%;
  }
  div.af{
      border:1px dotted gray;
      padding:3pt;
      margin-bottom:20px;
  }
  </style>
  <script type=\"text/javascript\" src=\"scripts/lib/langstring.js\"></script>
  {$rep->messages}
  <form action='index.php' method='post' name='db_record_editor_' id='db_record_editor_'>
  <input type=hidden name='action' value='ec/category/edit'>
  <input type=hidden name='site_id' value='{$this_site_info['id']}'>
  <input type=hidden name='db_record_editor_is_submitted' value='yes'>
  <input type=hidden name='db_record_editor_ec_category_id' value='{$rep->id}'>
  <input type=hidden name=ec_category_id value='{$rep->id}'>
  <br/><b>{$form['elements']['ec_category_title']->label}<sup style='color:red;'>*</sup></b><br/>
  <input type=text
         name='{$form['elements']['ec_category_title']->form_element_name}'
         id='{$form['elements']['ec_category_title']->form_element_name}'
         value='{$form['elements']['ec_category_title']->form_element_value}'>
  <script type=\"text/javascript\">
     draw_langstring('{$form['elements']['ec_category_title']->form_element_name}');
  </script>
  " .
        (isset($form['elements']['is_part_of']) ? "
  <br/><b>{$form['elements']['is_part_of']->label}</b><br/>
        <select name='{$form['elements']['is_part_of']->form_element_name}'
                id='{$form['elements']['is_part_of']->form_element_name}'
                style='width:100%;'>
           <option value=''></option>
           {$form['elements']['is_part_of']->form_element_options}
        </select><br/>
" : '')
        . "<br/><b>{$form['elements']['ec_category_description']->label}</b><br/>
    <textarea name='{$form['elements']['ec_category_description']->form_element_name}' rows=5 cols=30 style='width:100%;'>{$form['elements']['ec_category_description']->form_element_value}</textarea><br/>

    <br/><b>{$form['elements']['ec_category_code']->label}</b><br/>
    <input type=text
           name='{$form['elements']['ec_category_code']->form_element_name}'
           id='{$form['elements']['ec_category_code']->form_element_name}'
           value='{$form['elements']['ec_category_code']->form_element_value}'>
    <br/>
    " .
        (isset($form['elements']['is_visible']) ? "
    <br/><b>{$form['elements']['is_visible']->label}</b>
    <select name='{$form['elements']['is_visible']->form_element_name}'
            id='{$form['elements']['is_visible']->form_element_name}'>
      <option value=''></option>
      {$form['elements']['is_visible']->form_element_options}
    </select><br/>" : '')
        .
        (isset($form['elements']['ec_item_onnullamount']) ? "
    <br/><b>{$form['elements']['ec_item_onnullamount']->label}</b><br/>
    <select name='{$form['elements']['ec_item_onnullamount']->form_element_name}'
            id='{$form['elements']['ec_item_onnullamount']->form_element_name}'
            style='width:100%;'>
    <option value=''></option>
    {$form['elements']['ec_item_onnullamount']->form_element_options}
    </select><br/>" : '')
        . "
    <br/><h3 style='text-align:left;'>" . text('Additional_item_fields') . "</h3>
    <div>$category_item_field_form</div>
    <input type=submit style=\"font-size:300%;\" value='{$text['Save']}'>
    </form>
";
//	$rep->draw($form);
#  ---------------------------- adjust nodes - begin ---------------------------
$cnt = array_keys($this_category->parents);
foreach ($cnt as $i) {
    $this_category->parents[$i] = ec_adjust($this_category->parents[$i], $this_category->id);
}

$this_category->info = ec_adjust($this_category->info, $this_category->id);
#  ---------------------------- adjust nodes - end -----------------------------

$tor = "


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



<table border=0px width=100% cellpadding=0 cellspacing=3px>
";

foreach ($this_category->parents as $row) {
    $tor.="
  <tr>
  <td style=\"padding-left:{$row['padding']}px;border:none;\" valign=top>
      <a href=# class=context_menu_link onclick=\"report_change_state('cm{$row['ec_category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width-25 height=15></a>
      <a href=\"{$row['URL']}\" title=\"{$row['ec_category_title']}\" ";

    if ($row['is_visible'] == '0') {
        $tor.=" style='color:silver;'";
    }
    $tor.=">{$row['ec_category_code']} {$row['title_short']}</a><br>
      <div id=\"cm{$row['ec_category_id']}\" class=menu_block style='display:none;'>
    ";

    foreach ($row['context_menu'] as $cm) {
        if ($cm['url'] != '') {
            $tor.="<nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr><br>";
        } else {
            $tor.="<nobr><b>{$cm['html']}</b></nobr><br>";
        }
    }
    $tor.="
      </div>
  </td>
  </tr>
  ";
}



$category = &$this_category->info;
$tor.="
<tr>
<td style=\"padding-left:{$category['padding']}px;border:none;\" valign=top>
    <a href=\"#\" class=context_menu_link onclick=\"report_change_state('cm{$category['ec_category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width-25 height=15></a>
    <b><span title=\"{$category['ec_category_title']}\" ";

if ($category['is_visible'] == 0) {
    $tor.=" style='color:silver;'";
}

$tor.=">{$category['ec_category_code']} {$category['title_short']}</span></b><br>
    <div id=\"cm{$category['ec_category_id']}\" class=menu_block style='display:none;'>";

foreach ($category['context_menu'] as $cm) {
    if ($cm['url'] != '') {
        $tor.="<nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr><br>";
    } else {
        $tor.="<nobr><b>{$cm['html']}</b></nobr><br>";
    }
}

$tor.="
    </div>
</td>
</tr>
</table>
   ";
$input_vars['page_content'] = $tor . '  ' . $input_vars['page_content'];
#   $input_vars['page']['content'] = process_template('category/edit',Array(
#    'form'=>$form
#   ,'parents'=>$this_category->parents
#   ,'category'=>$this_category->info
#   ));
#  ---------------------------- draw - end -------------------------------------
# category context menu
$input_vars['page_menu']['category'] = Array('title' => text('ec_category'), 'items' => Array());
$input_vars['page_menu']['category']['items'] = menu_ec_category($this_category->info);
//prn($input_vars['page_menu']['category']);
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
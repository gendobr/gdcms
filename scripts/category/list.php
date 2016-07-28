<?php

/**
  Site categories
  can be used to browse pages and news
 */
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
//if(!is_librarian()) access_denied_page();

run('lib/class_tree1');
run('category/functions');


$this_category = new tree();

$this_category->name_id = 'category_id';
$this_category->name_start = 'start';
$this_category->name_finish = 'finish';
$this_category->name_deep = 'deep';
$this_category->name_table = '<<tp>>category';

$this_category->where[] = " <<tp>>category.site_id={$site_id} ";

$this_category->load_node(isset($input_vars['category_id']) ? ( (int) $input_vars['category_id'] ) : 0);

if ($this_category->id == 0) {
    $query = "INSERT INTO <<tp>>category(category_title,start,finish, deep, site_id) VALUES ('site root category',0,1,0,$site_id)";
    \e::db_execute($query);
    $this_category->load_node(0);
}

if (isset($_REQUEST['debug']))
    prn($this_category);

// -------------------------- move children - begin ----------------------------
if (isset($input_vars['move_down']))
    $this_category->move_down((int) $input_vars['move_down']);
if (isset($input_vars['move_up']))
    $this_category->move_up((int) $input_vars['move_up']);
// -------------------------- move children - end ------------------------------
# ------------------------- delete - begin -------------------------------------
if (isset($input_vars['category_delete']) && isset($input_vars['category']) && is_array($input_vars['category']) && count($input_vars['category']) > 0
) {
    $_ids = Array();
    foreach ($input_vars['category'] as $_id) {
        $_id = (int) $_id;
        $deleCat = \e::db_getonerow("SELECT category_id, category_icon FROM <<tp>>category WHERE site_id={$site_id} && category_id={$_id}");
        // ---------------- delete previous icons - begin ------------------
        if ($deleCat['category_icon']) {
            $deleCat['category_icon'] = json_decode($deleCat['category_icon']);
            if ($deleCat['category_icon'] && is_array($deleCat['category_icon'])) {
                foreach ($deleCat['category_icon'] as $pt) {
                    $pt = trim($pt);
                    if (strlen($pt) > 0) {
                        $path = realpath("{$this_site_info['site_root_dir']}/{$pt}");
                        if ($path && strncmp($path, $this_site_info['site_root_dir'], strlen($this_site_info['site_root_dir'])) == 0) {
                            unlink($path);
                        }
                    }
                }
            }
        }
        // ---------------- delete previous icons - end --------------------
        $this_category->delete_branch($_id);
    }

    //TODO: документы из удалённых категорий перемещать в родительскую категорию? 
}
# ------------------------- delete - end ---------------------------------------

if (isset($input_vars['add_child'])) {
    $new_child_category_id = $this_category->add_child();
    $query = "UPDATE <<tp>>category SET site_id={$site_id} WHERE category_id={$new_child_category_id}";
    \e::db_execute($query);
    $this_category->load_node($this_category->id);
}

$this_category->get_parents();
$this_category->get_children();

//$this_category->move_down(2);
//$this_category->move_up(2);
//$this_category->add_child(1);
//$this_category->delete_branch(5);
#  prn($this_category);die();
#  ---------------------------- adjust nodes - begin ---------------------------
$cnt = array_keys($this_category->parents);
foreach ($cnt as $i) {
    $this_category->parents[$i] = adjust($this_category->parents[$i], $this_category->id, $this_site_info);
}

$cnt = array_keys($this_category->children);
foreach ($cnt as $i) {
    $this_category->children[$i] = adjust($this_category->children[$i], $this_category->id, $this_site_info);
}

$this_category->info = adjust($this_category->info, $this_category->id, $this_site_info);
#  ---------------------------- adjust nodes - end -----------------------------
#  ---------------------------- draw - begin -----------------------------------
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
      <a href=# class=context_menu_link onclick=\"report_change_state('cm{$row['category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width-25 height=15></a>
      <a href=\"{$row['URL']}\" title=\"{$row['category_title']}\" ";

    if ($row['is_visible'] == '0')
        $tor.=" style='color:silver;'";
    $tor.=">{$row['title_short']} <span style='opacity:0.7;'> ( {$row['category_code']} )</span></a><br>
      <div id=\"cm{$row['category_id']}\" class=menu_block style='display:none;'>
  ";

    foreach ($row['context_menu'] as $cm) {
        if ($cm['url'] != '')
            $tor.="<div><nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr></div>";
        else
            $tor.="<div><nobr><b>{$cm['html']}</b></nobr></div>";
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
    <a href=\"#\" class=context_menu_link onclick=\"report_change_state('cm{$category['category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width-25 height=15></a>
    <b><span title=\"{$category['category_title']}\" ";

if ($category['is_visible'] == 0)
    $tor.=" style='color:silver;'";

$tor.="> {$category['title_short']} <span style='opacity:0.5;'>({$category['category_code']})</span></span></b><br>
    <div id=\"cm{$category['category_id']}\" class=menu_block style='display:none;'>";

foreach ($category['context_menu'] as $cm) {
    if ($cm['url'] != '')
        $tor.="<nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr><br>";
    else
        $tor.="<nobr><b>{$cm['html']}</b></nobr><br>";
}

$tor.="
    </div>
</td>
</tr>
";


$children = &$this_category->children;

foreach ($children as $row) {
    $tor.="
<tr>
<td style=\"padding-left:{$row['padding']}px;padding-bottom:3px;padding-top:2px;border:none;\" valign=top>
    <a href=\"#\" class=context_menu_link onclick=\"report_change_state('cm{$row['category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width-25 height=15></a>
    <a href=\"{$row['URL_move_down']}\" style=\"background-color:#e0e0e0;text-decoration:none;padding:1 3px 1 3px;text-align:center;width:15pt;border:1px solid blue;\">V</a>
    <a href=\"{$row['URL_move_up']}\"   style=\"background-color:#e0e0e0;text-decoration:none;padding:1 5px 1 5px;text-align:center;width:15pt;border:1px solid blue;\">&Lambda;</a>

    <a href=\"{$row['URL']}\" title=\"{$row['category_title']}\" ";

    if ($row['is_visible'] == '0')
        $tor.="style='color:silver;'";

    $tor.=">{$row['title_short']} <span style='opacity:0.7;'> ( {$row['category_code']} ) {$row['has_subcategories']}</a><br>
    <div id=\"cm{$row['category_id']}\" class=menu_block style='display:none;'>
";

    foreach ($row['context_menu'] as $cm) {
        if ($cm['url'] != '')
            $tor.="<nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr><br> ";
        else
            $tor.="<nobr><b>{$cm['html']}</b></nobr><br>";
    }

    $tor.="
    </div>
</td>
</tr>
";
}

$tor.="
</table>
   
   
   ";




$input_vars['page_title'] = $input_vars['page_header'] = text('Category') . " {$this_category->info['category_title']}";

$input_vars['page_content'] = $tor;

#  process_template('category/list',Array(
#    'parents' =>$this_category->parents
#   ,'children'=>$this_category->children
#   ,'category'=>$this_category->info
#  ));
#  ---------------------------- draw - end -------------------------------------
# category context menu
$input_vars['page_menu']['category'] = Array('title' => text('Category'), 'items' => Array());
$input_vars['page_menu']['category']['items'] = menu_category($this_category->info,$this_site_info);
//prn($input_vars['page_menu']['category']);
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
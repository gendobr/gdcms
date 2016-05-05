<?php
$main_template_name = '';
run('category/functions');
run('lib/class_tree1');

run('site/menu');
#------------------------ get category info - begin ----------------------------
if (isset($input_vars['category_id'])) {
    $category_id = (int) $input_vars['category_id'];

    $this_category = new tree();

    $this_category->name_id = 'category_id';
    $this_category->name_start = 'start';
    $this_category->name_finish = 'finish';
    $this_category->name_deep = 'deep';
    $this_category->name_table =  '<<tp>>category';

    //$this_category->where[] = " <<tp>>category.site_id={$site_id} ";

    $this_category->load_node($category_id);

    $this_category->info = adjust($this_category->info, $category_id);
    // prn('$this_category->info',$this_category->info);
    if (!$this_category->info) {
        unset($input_vars['category_id']);
    }

    //$this_category_url = $this_category_url_prefix . $this_category->id;
}

if (!isset($input_vars['category_id'])) {
    redirect_to('index.php');
}


//------------------- site info - begin ----------------------------------------

$site_id = (int) $this_category->info['site_id'];
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//
//
//
//
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
//
//
//
//
// ---------------- delete previous icons - begin ------------------------------
if ($this_category->info['category_icon'] && is_array($this_category->info['category_icon'])) {
    foreach ($this_category->info['category_icon'] as $pt) {
        $pt = trim($pt);
        if (strlen($pt) > 0) {
            $path = realpath("{$this_site_info['site_root_dir']}/{$pt}");
            if ($path && strncmp($path, $this_site_info['site_root_dir'], strlen($this_site_info['site_root_dir'])) == 0) {
                unlink($path);
            }
        }
    }
}
// ---------------- delete previous icons - end --------------------------------



$sql="UPDATE <<tp>>category SET category_icon=NULL WHERE category_id={$category_id}";
\e::db_execute($sql);

ml('category/delete_icon', ['category_id'=>$category_id]);

header("Location: index.php?action=category/edit&site_id={$site_id}&category_id={$category_id}");

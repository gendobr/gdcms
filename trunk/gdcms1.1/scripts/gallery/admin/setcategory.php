<?php

/*
 * Set image category
 */
$GLOBALS['main_template_name'] = '';

//prn($input_vars);
run('lib/file_functions');
$image_id = (int) str_replace('rozdil_', '', $input_vars['id']);
//$category_name = trim(iconv('UTF-8','cp1251',$input_vars['value']));
$category_name = trim(iconv('UTF-8',site_charset,$input_vars['value']));

// get image info
$img_info = db_getonerow("SELECT * FROM {$table_prefix}photogalery  WHERE id=$image_id");
if (!$img_info) {
    echo htmlspecialchars($category_name);
    exit();
}

$site_id = (int) $img_info['site'];
run('site/menu');
$this_site_info = get_site_info($site_id);
if($this_site_info['id']!=$site_id){
    echo htmlspecialchars($category_name);
    exit();
}

# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------

$rozdil  = DbStr($category_name);
$rozdil2 = encode_dir_name($category_name);
db_execute("UPDATE {$table_prefix}photogalery SET rozdil='$rozdil',rozdil2='$rozdil2' WHERE id=$image_id");
//echo htmlspecialchars(iconv('cp1251','UTF-8',$category_name));
//echo htmlspecialchars(iconv(site_charset,'UTF-8',$category_name));
echo htmlspecialchars($category_name);

?>
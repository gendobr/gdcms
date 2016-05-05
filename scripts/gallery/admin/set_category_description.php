<?php

/*
 * Set image category
 */
$GLOBALS['main_template_name'] = '';

//prn($input_vars);

$rozdil_id = (int) str_replace('rozdil_', '', $input_vars['id']);
//$category_description = trim(iconv('UTF-8','cp1251'    ,$input_vars['value']));
  $category_description = trim(iconv('UTF-8',site_charset,$input_vars['value']));


// get image info
$category_info =\e::db_getonerow("SELECT * FROM <<tp>>photogalery_rozdil  WHERE id=$rozdil_id");
if (!$category_info) {
    echo htmlspecialchars($category_description);
    exit();
}

$site_id = (int) $category_info['site_id'];
run('site/menu');
$this_site_info = get_site_info($site_id);
if($this_site_info['id']!=$site_id){
    echo htmlspecialchars($category_description);
    exit();
}

# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
   echo htmlspecialchars($text['Access_denied']);
   exit();
}
# ------------------- check permission - end -----------------------------------

$encoded_category_description  = \e::db_escape($category_description);
\e::db_execute("UPDATE <<tp>>photogalery_rozdil SET description='$encoded_category_description' WHERE id=$rozdil_id AND site_id=$site_id");
//echo htmlspecialchars(iconv('cp1251'    ,'UTF-8',$category_description));
//echo htmlspecialchars(iconv(site_charset,'UTF-8',$category_description));
echo htmlspecialchars($category_description);

?>
<?php

/*
 * Set image category
 */
$GLOBALS['main_template_name'] = '';
run('lib/file_functions');
//prn($input_vars);

$rozdil_id = (int) str_replace('rozdiltitle_', '', $input_vars['id']);
//$category_title = trim(iconv('UTF-8','cp1251',$input_vars['value']));
$category_title = trim(iconv('UTF-8',site_charset,$input_vars['value']));

// get image info
$category_info = db_getonerow("SELECT * FROM {$table_prefix}photogalery_rozdil  WHERE id=$rozdil_id");
if (!$category_info) {
  //echo htmlspecialchars(iconv('cp1251','UTF-8',$category_title));
  //echo htmlspecialchars(iconv(site_charset,'UTF-8',$category_title));
    echo htmlspecialchars($category_title);

    exit();
}

$site_id = (int) $category_info['site_id'];
run('site/menu');
$this_site_info = get_site_info($site_id);
if($this_site_info['id']!=$site_id){
    //echo htmlspecialchars(iconv('cp1251','UTF-8',$category_title));
    //echo htmlspecialchars(iconv(site_charset,'UTF-8',$category_title));
    echo htmlspecialchars($category_title);
    exit();
}

# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
   echo htmlspecialchars($text['Access_denied']);
   exit();
}
# ------------------- check permission - end -----------------------------------

$encoded_category_title  = DbStr($category_title);
$query="UPDATE {$table_prefix}photogalery_rozdil SET rozdil='$encoded_category_title', rozdil2='".encode_dir_name($category_title)."' WHERE id=$rozdil_id AND site_id=$site_id";
//prn($query);
db_execute($query);
// ------------- update image categories - begin -------------------------------
// get old subcategories from image list
$photogalery_rozdil_list = db_getrows(
        "SELECT DISTINCT p.rozdil
         FROM {$GLOBALS['table_prefix']}photogalery p
         WHERE p.site = {$this_site_info['id']}
          AND (p.rozdil='".DbStr($category_info['rozdil'])."' OR LOCATE('".DbStr($category_info['rozdil'])."/',p.rozdil))
         ORDER BY p.rozdil");
// prn($photogalery_rozdil_list);
// update
foreach($photogalery_rozdil_list as $rozdil){
   // replace from
   $from=DbStr($rozdil['rozdil']);

   // replace to
   $to=$category_title.substr($rozdil['rozdil'],strlen($category_info['rozdil']));

   // update list of images
   $query="UPDATE {$table_prefix}photogalery SET rozdil='".DbStr($to)."', rozdil2='".encode_dir_name($to)."' WHERE site={$site_id} AND rozdil='$from'";
   // prn($query);
   db_execute($query);
}
// ------------- update image categories - end ---------------------------------

// ------------- update image categories - begin -------------------------------
// get old subcategories from category list
$photogalery_rozdil_list = db_getrows(
        "SELECT DISTINCT pr.rozdil
         FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr
         WHERE pr.site_id = {$this_site_info['id']}
          AND (pr.rozdil='".DbStr($category_info['rozdil'])."' OR LOCATE('".DbStr($category_info['rozdil'])."/',pr.rozdil))
         ORDER BY pr.rozdil");
// prn($photogalery_rozdil_list);
// update
foreach($photogalery_rozdil_list as $rozdil){
   // replace from
   $from=DbStr($rozdil['rozdil']);

   // replace to
   $to=$category_title.substr($rozdil['rozdil'],strlen($category_info['rozdil']));

   // update list of subcategories
   $query="UPDATE {$table_prefix}photogalery_rozdil
           SET rozdil='".DbStr($to)."', rozdil2='".encode_dir_name($to)."'
           WHERE site_id={$site_id} AND rozdil='$from'";
   //echo '<!--';   prn($query);  //echo '-->';
   db_execute($query);
}
// ------------- update image categories - end ---------------------------------

//echo "
//<script language='text/javascript'>
//  window.location.reload();
//</script>
//";
// echo htmlspecialchars(iconv('cp1251','UTF-8',$category_title));
echo htmlspecialchars($category_title);
?>
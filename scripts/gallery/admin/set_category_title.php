<?php

/*
 * Set image category
 */
$GLOBALS['main_template_name'] = '';

//prn($input_vars);

$rozdil_id = (int) str_replace('rozdiltitle_', '', $input_vars['id']);
//$category_title = trim(iconv('UTF-8','cp1251',$input_vars['value']));
$category_title = trim(iconv('UTF-8',site_charset,$input_vars['value']));

// get image info
$category_info =\e::db_getonerow("SELECT * FROM <<tp>>photogalery_rozdil  WHERE id=$rozdil_id");
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

$encoded_category_title  = \e::db_escape($category_title);
$query="UPDATE <<tp>>photogalery_rozdil SET rozdil='$encoded_category_title', rozdil2='".\core\fileutils::encode_dir_name($category_title)."' WHERE id=$rozdil_id AND site_id=$site_id";
//prn($query);
\e::db_execute($query);
// ------------- update image categories - begin -------------------------------
// get old subcategories from image list
$photogalery_rozdil_list = \e::db_getrows(
        "SELECT DISTINCT p.rozdil
         FROM <<tp>>photogalery p
         WHERE p.site = {$this_site_info['id']}
          AND (p.rozdil='".\e::db_escape($category_info['rozdil'])."' OR LOCATE('".\e::db_escape($category_info['rozdil'])."/',p.rozdil))
         ORDER BY p.rozdil");
// prn($photogalery_rozdil_list);
// update
foreach($photogalery_rozdil_list as $rozdil){
   // replace from
   $from=\e::db_escape($rozdil['rozdil']);

   // replace to
   $to=$category_title.substr($rozdil['rozdil'],strlen($category_info['rozdil']));

   // update list of images
   $query="UPDATE <<tp>>photogalery SET rozdil='".\e::db_escape($to)."', rozdil2='".\core\fileutils::encode_dir_name($to)."' WHERE site={$site_id} AND rozdil='$from'";
   // prn($query);
   \e::db_execute($query);
}
// ------------- update image categories - end ---------------------------------

// ------------- update image categories - begin -------------------------------
// get old subcategories from category list
$photogalery_rozdil_list = \e::db_getrows(
        "SELECT DISTINCT pr.rozdil
         FROM <<tp>>photogalery_rozdil pr
         WHERE pr.site_id = {$this_site_info['id']}
          AND (pr.rozdil='".\e::db_escape($category_info['rozdil'])."' OR LOCATE('".\e::db_escape($category_info['rozdil'])."/',pr.rozdil))
         ORDER BY pr.rozdil");
// prn($photogalery_rozdil_list);
// update
foreach($photogalery_rozdil_list as $rozdil){
   // replace from
   $from=\e::db_escape($rozdil['rozdil']);

   // replace to
   $to=$category_title.substr($rozdil['rozdil'],strlen($category_info['rozdil']));

   // update list of subcategories
   $query="UPDATE <<tp>>photogalery_rozdil
           SET rozdil='".\e::db_escape($to)."', rozdil2='".\core\fileutils::encode_dir_name($to)."'
           WHERE site_id={$site_id} AND rozdil='$from'";
   //echo '<!--';   prn($query);  //echo '-->';
   \e::db_execute($query);
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
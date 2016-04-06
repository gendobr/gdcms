<?php
/*
  Rename file
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/


$GLOBALS['main_template_name']='';

run('site/menu');

//------------------- site info - begin ----------------------------------------
  $site_id = (int)$input_vars['site_id'];
  $this_site_info = get_site_info($site_id);
  #$this_site_info = \e::db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  #//prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0) {
    echo "ERROR: Site not found";
    exit();
  }
  $site_root_dir = str_replace("\\",'/',realpath($this_site_info['site_root_dir']));
// $site_root_url = $this_site_info['site_root_url'];
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0) {
    echo "ERROR: Access denied";
    exit();
}
//------------------- check permission - end -----------------------------------

// ----------------- check current directory - begin ---------------------------
   if(!isset($input_vars['current_dir'])) $input_vars['current_dir']='';
   // prn('$current_dir='.$input_vars['current_dir']);
   $current_dir=str_replace("\\",'/',realpath($site_root_dir.'/'.$input_vars['current_dir']));
   if(strlen($current_dir)<strlen($site_root_dir)){
       $current_dir=$site_root_dir;
   }
   // prn('$current_dir='.$current_dir);
// ----------------- check current directory - end -----------------------------


// echo "TEST"; exit();

run('lib/file_functions');



// check old file name
$oldfilepath = realpath($current_dir.'/'.$input_vars['oldname']);
if(!$oldfilepath){
    echo "ERROR: {$input_vars['oldname']} not found";
    exit();
}


if(is_file($oldfilepath)){
    // check new file name
    $newfilepath = encode_file_name($input_vars['newname']);
    if(!preg_match("/\\.(".allowed_file_extension.")$/i",$newfilepath)) {
        echo "ERROR: {$input_vars['newname']} is not allowed";
        exit();
    }
    $newfilepath = $current_dir.'/'.$newfilepath;
}

if(is_dir($oldfilepath)){
    // check new file name
    $newfilepath = encode_dir_name($input_vars['newname']);
    if($newfilepath=='gallery' || $newfilepath=='cache') {
        echo "ERROR: Renaming of the {$input_vars['oldname']} is not allowed";
        exit();
    }
    $newfilepath = $current_dir.'/'.$newfilepath;
}

if(rename($oldfilepath, $newfilepath)){
    echo "SUCCESS";
    exit();
}
echo "ERROR: File is not renamed";
exit();
?>
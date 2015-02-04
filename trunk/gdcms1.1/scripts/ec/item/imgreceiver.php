<?php

global $main_template_name;
$main_template_name = '';



run('lib/file_functions');
run('site/image/url_replacer');
run('ec/item/functions');
run('site/menu');

//header('dbg-1: xxx');

//
////exit('001');
//
//
//
# ------------------- check ec_item_id - begin ------------------------------------
$ec_item_id = 0;
$ec_item_lang = get_language('ec_item_lang');
if (isset($input_vars['ec_item_id'])) {
    $ec_item_id = (int) $input_vars['ec_item_id'];
    //$ec_item_lang = DbStr($input_vars['ec_item_lang']);
    $this_ec_item_info = get_ec_item_info($ec_item_id, $ec_item_lang, 0, false);
    if (!$this_ec_item_info) {
        $ec_item_id = 0;
    }
}
if ($ec_item_id == 0) {
    echo '{"status":"error","message":"Ec item not found"}';
    return;
}

//header('dbg-1: xx1');


# ------------------- get site info - begin ---------------------------------------
$site_id = $this_ec_item_info['site_id'];
$this_site_info = get_site_info($site_id);


# ------------------- get permission - begin --------------------------------------
$user_cense_level = get_level($site_id);
if ($user_cense_level <= 0) {
    echo '{"status":"error","message":"access denied"}';
    return;
}

// header('dbg-1: xx2');

// echo '{"status":"error","message":"Ec item not found"}';


// header('dbg-1: xx3');

// prn($_FILES['ec_item_img']);
//
//// ----------------- do upload - begin -----------------------------------------
$photos=$_FILES['ec_item_img'];
if ($photos['size'] > 0 && preg_match("/(jpg|gif|png|jpeg)$/i", $photos['name'], $regs)) {
    
    
    $data = date('Y-m-d-h-i-s');

    # get file extension
    $file_extension = ".{$regs[1]}";
    # create file name

    # create directory
    $relative_dir = date('Y') . '/' . date('m');
    $site_root_dir = sites_root . '/' . $this_site_info['dir'];
    path_create($site_root_dir, "/gallery/$relative_dir/");

    # copy uploaded file
    $big_image_file_name = "{$this_ec_item_info['site_id']}-{$data}-big-" . encode_file_name($photos['name']);
    $big_file_path="$site_root_dir/gallery/$relative_dir/$big_image_file_name";
    ec_img_resize($photos['tmp_name'], $big_file_path, gallery_big_image_width, gallery_big_image_height, "resample");

    $small_image_file_name = "{$this_ec_item_info['site_id']}-{$data}-small-" . encode_file_name($photos['name']);
    $small_file_path="$site_root_dir/gallery/$relative_dir/$small_image_file_name";
    ec_img_resize($photos['tmp_name'], $small_file_path, gallery_small_image_width, gallery_small_image_width, "circumscribe");
    $this_ec_item_info["ec_item_img"][] = Array("gallery/$relative_dir/$small_image_file_name","gallery/$relative_dir/$big_image_file_name",'');
    
    // update item info
    $ec_item_img=array_values($this_ec_item_info['ec_item_img']);
    //prn($ec_item_img);
    $cnt=count($ec_item_img);
    for($i=0;$i<$cnt;$i++){
        $ec_item_img[$i]=join("\t",$ec_item_img[$i]);
    }
    $ec_item_img=join("\n",$ec_item_img);

    $query = "UPDATE {$table_prefix}ec_item SET ec_item_img='" . DbStr($ec_item_img) . "'
              WHERE ec_item_id='{$this_ec_item_info['ec_item_id']}' AND ec_item_lang='{$this_ec_item_info['ec_item_lang']}'";
    db_execute($query);
    echo '{"status":"success","message":"OK"}';
    return;
}
//// ----------------- do upload - end -------------------------------------------

echo '{"status":"error","message":"image file not posted"}';
return;

<?php

$GLOBALS['main_template_name'] = '';
//exit('001');
header("Content-Type:application/json; charset=" . site_charset);


if (!isset($_FILES) && count($_FILES) > 0) {
    echo json_encode(['status' => "error", "data" => [], 'message' => 'Files not posted']);
    return '';
}

// prn($_FILES); return;



run('site/menu');

# ------------------- site info - begin ----------------------------------------
$site_id = \e::cast('integer', \e::request('site_id', 0));
if ($site_id > 0) {
    $this_site_info = get_site_info($site_id);
}
if (!$this_site_info) {
    echo json_encode(['status' => "error", "data" => [], 'message' => $text['Site_not_found']]);
    return '';
}
# ------------------- site info - end ------------------------------------------
# 
# 
# 
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    echo json_encode(['status' => "error", "data" => [], 'message' => $text['Access_denied']]);
    return 0;
}
# ------------------- check permission - end -----------------------------------


$page_id = \e::cast('integer', \e::request('page_id', 0));
$lang = get_language('lang');
$now = date('Y-m-d--H-i-s');

$img = new \core\img();
$relative_dir = "gallery/" . date('Y') . '/' . date('m');
$dir = "{$this_site_info['site_root_dir']}/{$relative_dir}";
\core\fileutils::path_create($this_site_info['site_root_dir'], "{$dir}/");


$data = [];

foreach ($_FILES as $imagefile) {

    // ignore non-image extensions
    if (!in_array(strtolower(\core\fileutils::file_extention($imagefile['name'])), \core\fileutils::$img_extensions)) {
        continue;
    }

    // ---------------- upload new icons - begin -------------------------------
    $bigFileName = "page-{$page_id}-{$lang}-{$now}-" . \core\fileutils::encode_file_name($imagefile['name']);
    if (move_uploaded_file($imagefile['tmp_name'], "{$dir}/{$bigFileName}")) {

        $smallFileName = "page-{$page_id}-{$lang}-{$now}-small-" . \core\fileutils::encode_file_name($imagefile['name']);
        $img->resize("{$dir}/{$bigFileName}", "{$dir}/{$smallFileName}", \e::config('gallery_small_image_width'), \e::config('gallery_small_image_height'), $rgb = 0xFFFFFF, $quality = 100, \core\img::$MODE_MAX_RATIO);


        $photo_imgfile = [];
        $photo_imgfile['small'] = "{$this_site_info['site_root_url']}/{$relative_dir}/{$smallFileName}";
        $photo_imgfile['full'] = $photo_imgfile['big'] = "{$this_site_info['site_root_url']}/{$relative_dir}/{$bigFileName}";

        $data[] = $photo_imgfile;
    }
    // ---------------- upload new icons - end ---------------------------------
}

if(count($data)>0){
    echo json_encode(['status' => "success", "data" => $data, 'message' => 'Uploaded successfully']);   
}else{
    echo json_encode(['status' => "error", "data" => [], 'message' => 'Images upload error']);
}


<?php

header("Content-Type:text/html; charset=" . site_charset);

global $main_template_name;
$main_template_name = '';

run('site/menu');

# ------------------- site info - begin ----------------------------------------
$site_id = 0;
if (isset($input_vars['site_id'])) {
    $site_id = checkInt($input_vars['site_id']);
    $this_site_info = get_site_info($site_id);
    $site_id = checkInt($this_site_info['id']);
}

if ($site_id <= 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
# ------------------- site info - end ------------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------
// prn($_FILES); return;

if (!isset($_FILES) && count($_FILES) > 0) {
    echo "File not posted. Exiting";
    return '';
}







$img = new \core\img();
$relative_dir = "gallery/" . date('Y') . '/' . date('m');
$dir = "{$this_site_info['site_root_dir']}/{$relative_dir}";
\core\fileutils::path_create($this_site_info['site_root_dir'], "{$dir}/");

foreach ($_FILES as $imagefile) {

    // ignore non-image extensions
    if (!in_array(strtolower(\core\fileutils::file_extention($imagefile['name'])), \core\fileutils::$img_extensions)) {
        echo "
            <span style='display:inline-block; width:95%;'>
            <div style='color:red'>ERROR: cannot upload image {$imagefile['name']}</div>
            </span>
        ";
        continue;
    }

    $photo_info = [];
    $photo_info['photo_category_id'] = \e::cast('integer', \e::request('photo_category_id', ''));
    $photo_info['site_id'] = \e::cast('integer', \e::request('site_id', ''));
    $photo_info['photo_visible'] = \e::cast('integer', \e::request('photo_visible', ''));
    $photo_info['photo_year'] = \e::cast('integer', \e::request('photo_year', ''));
    $photo_info['photo_title'] = \e::request('photo_title', '');
    $photo_info['photo_author'] = \e::request('photo_author', '');
    $photo_info['photo_description'] = \e::request('photo_description', '');
    \e::db_execute(
            "INSERT INTO <<tp>>photo (
                    photo_category_id,
                    site_id,
                    photo_visible,
                    photo_title,
                    photo_author,
                    photo_description,
                    photo_year
                    )
                 VALUES (
                    <<integer photo_category_id>>,
                    <<integer site_id>>,
                    <<integer photo_visible>>,
                    <<string photo_title>>,
                    <<string photo_author>>,
                    <<string photo_description>>,
                    <<integer photo_year>>
                 )", $photo_info);
    $photo_id = \e::db_getonerow("SELECT LAST_INSERT_ID() as newid");
    $photo_id = $photo_id['newid'];

    // ---------------- upload new icons - begin -------------------------------
    //photo_imgfile      text        utf8_bin   YES             (NULL)                   select,insert,update,references           
    $bigFileName = "photo-{$photo_id}-" . \core\fileutils::encode_file_name($imagefile['name']);
    if (move_uploaded_file($imagefile['tmp_name'], "{$dir}/{$bigFileName}")) {

        $smallFileName = "photo-{$photo_id}-small-" . \core\fileutils::encode_file_name($imagefile['name']);
        $img->resize("{$dir}/{$bigFileName}", "{$dir}/{$smallFileName}", 
                $this_site_info['extra_setting']['gallery_small_image_width'], 
                $this_site_info['extra_setting']['gallery_small_image_height'], 
                $rgb = 0xFFFFFF, $quality = 100, \core\img::$MODE_RESIZE);
        $photo_imgfile = ['small' => "{$relative_dir}/{$smallFileName}", "full" => "{$relative_dir}/{$bigFileName}"];
        \e::db_execute(
                "UPDATE <<tp>>photo
                          SET photo_imgfile=<<string photo_imgfile>>
                          WHERE photo_id=<<integer photo_id>>", [
            'photo_id' => $photo_id,
            'photo_imgfile' => json_encode($photo_imgfile)
        ]);

        echo "
            <span style='display:inline-block; width:95%;'>
            <img src={$this_site_info['site_root_url']}/{$photo_imgfile['small']} width=200px align=\"left\" style=\"margin-right:20px;\">
            <div style='color:green'>{$text['Gallery_image_uploaded_successfully']}</div>
            </span>
            ";
    } else {
        echo "
            <span style='display:inline-block; width:95%;'>
            <div style='color:red'>ERROR: cannot upload image {$imagefile['name']}</div>
            </span>
        ";
    }
    // ---------------- upload new icons - end ---------------------------------
}


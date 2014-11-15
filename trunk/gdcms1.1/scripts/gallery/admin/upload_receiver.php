<?php

header("Content-Type:text/html; charset=".site_charset);

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
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
# ------------------- site info - end ------------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------

// prn($_FILES); return;

if (!isset($_FILES) && count($_FILES) > 0) {
    echo "File not posted. Exiting";
    return '';
}
run('lib/file_functions');
run('gallery/category_model');




// ----------------- resizing image to reate small image - begin ---------------
function img_resize($photos, $imagefile, $width, $height, $rgb=0xFFFFFF, $quality=100) {
    if (!file_exists($photos)) {
        return false;
    }
    $size = getimagesize($photos);
    if ($size === false) {
        return false;
    }

    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    $icfunc = "imagecreatefrom" . $format;
    if (!function_exists($icfunc)) {
        return false;
    }

    $x_ratio = $width / $size[0];
    $y_ratio = $height / $size[1];
    //$ratio       = min($x_ratio, $y_ratio);
    $ratio = max($x_ratio, $y_ratio);
    $use_x_ratio = ($x_ratio == $ratio);
    $new_width = $use_x_ratio ? $width : floor($size[0] * $ratio);
    $new_height = !$use_x_ratio ? $height : floor($size[1] * $ratio);
    $new_left = $use_x_ratio ? 0 : floor(($width - $new_width) / 2);
    $new_top = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

    $bigimg = $icfunc($photos);
    $trumbalis = imagecreatetruecolor($width, $height);

    imagefill($trumbalis, 0, 0, $rgb);
    imagecopyresampled($trumbalis, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);

    imagejpeg($trumbalis, $imagefile, $quality);

    imagedestroy($bigimg);
    imagedestroy($trumbalis);
    return true;
}
/*
function cp1251_to_utf8($s) {
    if ((mb_detect_encoding($s, 'UTF-8,CP1251')) == "WINDOWS-1251") {
        $c209 = chr(209);
        $c208 = chr(208);
        $c129 = chr(129);
        for ($i = 0; $i < strlen($s); $i++) {
            $c = ord($s[$i]);
            if ($c >= 192 and $c <= 239)
                $t.=$c208 . chr($c - 48);
            elseif ($c > 239)
                $t.=$c209 . chr($c - 112);
            elseif ($c == 184)
                $t.=$c209 . $c209;
            elseif ($c == 168)
                $t.=$c208 . $c129;
            else
                $t.=$s[$i];
        }
        return $t;
    }
    else {
        return $s;
    }
}

function utf8_to_cp1251($s) {
    if ((mb_detect_encoding($s, 'UTF-8,CP1251')) == "UTF-8") {
        $byte2=false;
        $out='';
        for ($c = 0; $c < strlen($s); $c++) {
            $i = ord($s[$c]);
            if ($i <= 127)
                $out.=$s[$c];
            if ($byte2) {
                $new_c2 = ($c1 & 3) * 64 + ($i & 63);
                $new_c1 = ($c1 >> 2) & 5;
                $new_i = $new_c1 * 256 + $new_c2;
                if ($new_i == 1025) {
                    $out_i = 168;
                } else {
                    if ($new_i == 1105) {
                        $out_i = 184;
                    } else {
                        $out_i = $new_i - 848;
                    }
                }
                $out.=chr($out_i);
                $byte2 = false;
            }
            if (($i >> 5) == 6) {
                $c1 = $i;
                $byte2 = true;
            }
        }
        return $out;
    } else {
        return $s;
    }
}
*/
// ----------------- resizing image to reate small image - end -----------------
// prn($_FILES);
// prn($_REQUEST);

$gallery_small_image_width  = defined('gallery_small_image_width')?gallery_small_image_width:150;
$gallery_small_image_height = defined('gallery_small_image_height')?gallery_small_image_height:150;

foreach ($_FILES as $uploadedfile) {

    $rozdil = $input_vars["rozdil"];
    //echo "\$rozdil=$rozdil;<br>\n";

    $rozdil2 = encode_dir_name($rozdil);

    $pidpys = $input_vars["pidpys"];
    //echo "\$pidpys=$pidpys;<br>\n";

    $autor = $input_vars["autor"];
    //echo "\$autor=$autor;<br>\n";

    $rik = (int) $input_vars["rik"];

    $vis = ($input_vars["vis"] == 1) ? 1 : 0;

    $data = date('Y-m-d-h-i-s');

    if ($uploadedfile['size'] > 0 && preg_match("/(jpg|gif|png|jpeg)$/i", $uploadedfile['name'], $regs)) {
        //prn($regs[1]);
        # get file extension
        $file_extension = ".{$regs[1]}";

        # create file name
        $big_image_file_name = "{$site_id}-{$data}-" . encode_file_name($uploadedfile['name']);

        # create directory
        $relative_dir = date('Y/m');
        $site_root_dir = sites_root . '/' . $this_site_info['dir'];
        path_create($site_root_dir, "/gallery/$relative_dir/");

        # copy uploaded file
        copy($uploadedfile['tmp_name'], "$site_root_dir/gallery/$relative_dir/$big_image_file_name");

        # -------------- create small image - begin ----------------------------
        $small_image_file_name = "{$site_id}-{$data}-m-" . encode_file_name($uploadedfile['name']);
        img_resize(
                "$site_root_dir/gallery/$relative_dir/$big_image_file_name", // source image
                "$site_root_dir/gallery/$relative_dir/$small_image_file_name", // here thumbnail image will be saved
                $gallery_small_image_width, // new width
                $gallery_small_image_height, // new height
                $rgb      = 0xFFFFFF, $quality = 100);
        # -------------- create small image - end ------------------------------
        # save to database
        $icon_insert = db_execute(
                "INSERT INTO {$table_prefix}photogalery(id,photos,photos_m,rozdil,rozdil2,pidpys,autor,rik,site,vis)
                 VALUES
                 ( NULL
                  ,'" . DbStr("$relative_dir/$big_image_file_name") . "'
                  ,'" . DbStr("$relative_dir/$small_image_file_name") . "'
                  ,'" . DbStr($rozdil) . "'
                  ,'" . DbStr($rozdil2) . "'
                  ,'" . DbStr($pidpys) . "'
                  ,'" . DbStr($autor) . "'
                  ,'" . DbStr($rik) . "'
                  ,'" . DbStr($site_id) . "'
                  ,'" . DbStr($vis) . "'
                 )");

        # show report
        $url_prefix = preg_replace("/\\/+$/", '', $this_site_info['url']) . '/gallery';
        $row = db_getonerow("SELECT * FROM {$table_prefix}photogalery WHERE photos = '" . DbStr("$relative_dir/$big_image_file_name") . "'");
        if ($row) {
            echo "
            <span style='display:inline-block; width:95%;'>
            <img src={$url_prefix}/{$row['photos']} width=200px align=\"left\" style=\"margin-right:20px;\">
            <div style='color:green'>{$text['Gallery_image_uploaded_successfully']}</div>
            <div>{$row['pidpys']}</div>
            <div><small><b>{$text['Gallery_image_author']}:</b> {$row['autor']}</small></div>
            <div><small><b>{$text['Gallery_image_rik']}:</b> {$row['rik']}</small></div>
            <div><small><b>{$text['Gallery_category']}:</b> {$row['rozdil']}</small></div>
            </span>
            ";
        } else {
            echo "
            <span style='display:inline-block; width:95%;'>
            <div style='color:red'>ERROR: cannot upload image {$uploadedfile['name']}</div>
            </span>
            ";
        }
    }
}

gallery_synchronize_categories($this_site_info['id']);
?>
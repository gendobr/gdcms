<?php

function img_resize($photos, $imagefile, $width, $height, $rgb=0xFFFFFF, $quality=100) {
    if (!file_exists($photos)) return false;
    $size = getimagesize($photos);
    if ($size === false) return false;

    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
    $icfunc = "imagecreatefrom" . $format;
    if (!function_exists($icfunc)) return false;

    $x_ratio = $width / $size[0];
    $y_ratio = $height / $size[1];
    //$ratio       = min($x_ratio, $y_ratio);
    $ratio       = max($x_ratio, $y_ratio);
    $use_x_ratio = ($x_ratio == $ratio);
    $new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio);
    $new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio);
    $new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2);
    $new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

    $bigimg = $icfunc($photos);
    $trumbalis = imagecreatetruecolor($width, $height);

    imagefill($trumbalis, 0, 0, $rgb);
    imagecopyresampled($trumbalis, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);

    imagejpeg($trumbalis, $imagefile, $quality);

    imagedestroy($bigimg);
    imagedestroy($trumbalis);
    return true;
}
?>
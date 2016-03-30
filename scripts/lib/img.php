<?php


// ----------------- resizing image to create small image - begin ----------
/**
 * $type="resample"|"inscribe"|"circumscribe"
 */
function img_resize($inputfile, $outputfile, $width, $height, $type = "resample", $backgroundColor = 0xFFFFFF, $quality = 70) {
    if (!file_exists($inputfile)) {
        return false;
    }
    $size = getimagesize($inputfile);
    if ($size === false) {
        return false;
    }

    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    $icfunc = "imagecreatefrom" . $format;
    if (!function_exists($icfunc)) {
        return false;
    }

    switch ($type) {
        case "inscribe":
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height > $height) {
                $ratio = $height / $size[1];
                if($ratio>1){
                    $ratio=1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);
            }
            break;
        case "circumscribe":
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height < $height) {
                $ratio = $height / $size[1];
                if($ratio>1){
                    $ratio=1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);
            }
            break;
        default:
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height > $height) {
                $ratio = $height / $size[1];
                if($ratio>1){
                    $ratio=1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);                    
            }
            $width=$new_width;
            $height=$new_height;
            break;
    }
    $new_left = floor(($width - $new_width) / 2);
    $new_top = floor(($height - $new_height) / 2);

    $bigimg = $icfunc($inputfile);
    $trumbalis = imagecreatetruecolor($width, $height);

    imagefill($trumbalis, 0, 0, $backgroundColor);
    imagecopyresampled($trumbalis, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);
    imagejpeg($trumbalis, $outputfile, $quality);

    imagedestroy($bigimg);
    imagedestroy($trumbalis);
    return true;
}


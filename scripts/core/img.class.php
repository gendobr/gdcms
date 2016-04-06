<?php

namespace core;

/**
 * class to operate images
 * @author dobro
 */
class img {

    // modes:
    /** только уменьшать картинку, если надо */
    public static $MODE_DECREASE = 1;

    /** приводить к заданному размеру */
    public static $MODE_RESIZE = 2;

    /**
     * вписать исходную картинку в заданный прямоугольник
     * и, если надо, добавить поля заданного цвета
     */
    public static $MODE_MIN_RATIO = 4;

    /** уменьшить исходную картинку так,
     * чтобы в неё вписался прямоугольник заданного размера
     * и обрезать до заданного размера
     */
    public static $MODE_MAX_RATIO = 8;

    /**
     * корневая директория для пользовательских файлов
     */
    public $file_root_dir;

    /**
     * класс для работы с базой данных
     */
    public $db;

    /**
     * ширина большого варианта картинки
     */
    public $large_image_width;

    /**
     * высота большого варианта картинки
     */
    public $large_image_height;

    /**
     * ширина уменьшенного варианта картинки
     */
    public $small_image_width;

    /**
     * высота уменьшенного варианта картинки
     */
    public $small_image_height;

    /**
     * фоновый цвет
     */
    public $rgb;

    /**
     * качество jpg
     */
    public $quality;

    function __construct() {
        $this->rgb = 0xFFFFFF;
        $this->quality = 100;
    }

//    /**
//     * владелец изображения
//     */
//    public $user;
//
//    function __construct(
//    $file_root_dir, //
//            $large_image_width, //
//            $large_image_height, //
//            $small_image_width, //
//            $small_image_height, //
//            $tiny_image_width, //
//            $tiny_image_height,
//            $large_image_mode=0,
//            $small_image_mode=0,
//            $tiny_image_mode=0
//    ) {
//        
//        $this->large_image_mode=($large_image_mode>0)?$large_image_mode:self::$MODE_DECREASE;
//        $this->small_image_mode=($small_image_mode>0)?$small_image_mode:self::$MODE_MAX_RATIO;
//        $this->tiny_image_mode=($tiny_image_mode>0)?$tiny_image_mode:self::$MODE_MAX_RATIO;
//
//        $this->file_root_dir = $file_root_dir; //\e::config('FILES_ROOT');
//        $this->large_image_width = $large_image_width; //\e::config('large_image_width');
//        $this->large_image_height = $large_image_height; //\e::config('large_image_height');
//
//        $this->small_image_width = $small_image_width; //\e::config('small_image_width');
//        $this->small_image_height = $small_image_height; //\e::config('small_image_height');
//
//        $this->tiny_image_width = $tiny_image_width; //\e::config('tiny_image_width');
//        $this->tiny_image_height = $tiny_image_height; //\e::config('tiny_image_height');
//
//        $this->rgb = 0xFFFFFF;
//        $this->quality = 100;
//    }
//
//    /**
//     * загрузка новой картинки в систему
//     * @param $fileinfo string информация о загруженном файле
//     * @param $relative_dir string директория, в которую надо поместить рисунок и его иконку
//     * @param $newfilename новое имя файла
//     */
//    function upload($fileinfo, $_relative_dir, $newfilename = false) {
//
//        // get relative dir
//        $relative_dir = $_relative_dir;
//
//        // check if destination directory exists
//        $absolute_dir = $this->file_root_dir . '/' . $relative_dir;
//        $dir = realpath($absolute_dir);
//
//        if (!$dir) {
//            // create directory if it doesn't exists
//            \core\fileutils::path_create($this->file_root_dir, $absolute_dir . '/');
//            $dir = realpath($absolute_dir);
//            if (!$dir) {
//                // stop if directory still doesn't exists
//                return false;
//            }
//        }
//        // do upload
//        $filename = \core\fileutils::upload_file($fileinfo, $dir);
//
//        $result = '';
//        // do resize
//        if ($filename) {
//            if (!$newfilename) {
//                $newfilename = $filename;
//            }
//            // save new photo name
//            $result = "$relative_dir/$newfilename-small.jpg;$relative_dir/$newfilename-large.jpg;$relative_dir/$newfilename-tiny.jpg";
//            //\e::info('img.imgoperator.upload', $result);
//            // resize image
//            $rez1 = $this->resize("$dir/$filename", "$dir/$newfilename-large.jpg", $this->large_image_width, $this->large_image_height, $this->rgb, $this->quality, $this->large_image_mode);
//            $rez2 = $this->resize("$dir/$filename", "$dir/$newfilename-small.jpg", $this->small_image_width, $this->small_image_height, $this->rgb, $this->quality, $this->small_image_mode);
//            $rez3 = $this->resize("$dir/$filename", "$dir/$newfilename-tiny.jpg", $this->tiny_image_width, $this->tiny_image_height, $this->rgb, $this->quality, $this->tiny_image_mode);
//            unlink("$dir/$filename");
//        }
//        return $result;
//    }
//
//    /**
//     * remove image files and its approval marks
//     */
//    public function remove($files) {
//        // delete files
//        $path = explode(';', $files);
//        foreach ($path as $p) {
//            $absolute_path = realpath($this->file_root_dir . '/' . $p);
//            if ($absolute_path && strlen($absolute_path) > strlen($this->file_root_dir)) {
//                unlink($absolute_path);
//            }
//        }
//    }

    /**
     * Resize image
     * @param $big_image_file string original image file
     * @param $resized_image_file string transformed image file
     * @param $rgb hex code of the background color
     * @param $quality int JPEG quality of resized image
     * @param $width int maximal width
     * @param $height int maximal height
     * @param $mode bit_mask resize mode
     */
    public function resize($big_image_file, $resized_image_file, $width, $height, $rgb = 0xFFFFFF, $quality = 100, $mode = 1) {
        // echo "resize($big_image_file, $resized_image_file, $width, $height, $rgb=0xFFFFFF, $quality=100, $mode=0)<hr>";
        // check if file exists
        if (!file_exists($big_image_file)) {
            \e::error('img.imgoperator', "$big_image_file not found");
            return false;
        }

        // load file description
        $size = getimagesize($big_image_file);
        if ($size === false) {
            \e::error('img.imgoperator', "$big_image_file : cannot load image size");
            return false;
        }
        // get file format
        $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));

        // check if image can be loaded
        $icfunc = "imagecreatefrom" . $format;
        if (!function_exists($icfunc)) {
            \e::error('img.imgoperator', "$big_image_file : cannot find function $icfunc");
            return false;
        }

        // calculate new ratio and position
        $x_ratio = $width / $size[0];
        $y_ratio = $height / $size[1];



        switch ($mode) {
            case self::$MODE_MAX_RATIO:
                $bigimg = $icfunc($big_image_file);
                $tn_image = imagecreatetruecolor($width, $height);
                imagefill($tn_image, 0, 0, $rgb);

                $ratio = max($x_ratio, $y_ratio);
                $use_x_ratio = ($x_ratio == $ratio);
                $new_width = $use_x_ratio ? $width : floor($size[0] * $ratio);
                $new_height = !$use_x_ratio ? $height : floor($size[1] * $ratio);
                $new_left = $use_x_ratio ? 0 : floor(($width - $new_width) / 2);
                $new_top = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

                imagecopyresampled($tn_image, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);

                imagejpeg($tn_image, $resized_image_file, $quality);
                imagedestroy($bigimg);
                imagedestroy($tn_image);
                break;
            case self::$MODE_MIN_RATIO:
                $bigimg = $icfunc($big_image_file);
                $tn_image = imagecreatetruecolor($width, $height);
                imagefill($tn_image, 0, 0, $rgb);

                $ratio = min($x_ratio, $y_ratio);
                $use_x_ratio = ($x_ratio == $ratio);
                $new_width = $use_x_ratio ? $width : floor($size[0] * $ratio);
                $new_height = !$use_x_ratio ? $height : floor($size[1] * $ratio);
                $new_left = $use_x_ratio ? 0 : floor(($width - $new_width) / 2);
                $new_top = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);

                imagecopyresampled($tn_image, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);

                imagejpeg($tn_image, $resized_image_file, $quality);
                imagedestroy($bigimg);
                imagedestroy($tn_image);
                break;
            case self::$MODE_RESIZE:
                // resize 
                $bigimg = $icfunc($big_image_file);
                $tn_image = imagecreatetruecolor($width, $height);
                imagefill($tn_image, 0, 0, $rgb);
                imagecopyresampled($tn_image, $bigimg, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
                imagejpeg($tn_image, $resized_image_file, $quality);
                imagedestroy($bigimg);
                imagedestroy($tn_image);
                break;
            default:
                // default is self::$MODE_DECREASE
                $ratio = min($x_ratio, $y_ratio);
                if ($ratio >= 1) {
                    // real image size is smaller than required one
                    $bigimg = $icfunc($big_image_file);
                    $tn_image = imagecreatetruecolor($size[0], $size[1]);
                    imagefill($tn_image, 0, 0, $rgb);
                    imagecopy($tn_image, $bigimg, 0, 0, 0, 0, $size[0], $size[1]);
                    imagejpeg($tn_image, $resized_image_file, $quality);
                    imagedestroy($bigimg);
                    imagedestroy($tn_image);
                } else {
                    // real image size is greater than required one
                    $bigimg = $icfunc($big_image_file);
                    $new_width = floor($size[0] * $ratio);
                    $new_height = floor($size[1] * $ratio);
                    $tn_image = imagecreatetruecolor($new_width, $new_height);
                    imagefill($tn_image, 0, 0, $rgb);
                    imagecopyresampled($tn_image, $bigimg, 0, 0, 0, 0, $new_width, $new_height, $size[0], $size[1]);
                    imagejpeg($tn_image, $resized_image_file, $quality);
                    imagedestroy($bigimg);
                    imagedestroy($tn_image);
                }
                break;
        }
        return true;
    }

}

<?php

$GLOBALS['main_template_name'] = '';


$dirname=preg_replace("/^\//",'',dirname($_SERVER['PHP_SELF']));
$rel_file_path = explode('?',substr($_SERVER['REQUEST_URI'], strlen($dirname) + 1));
$rel_file_path = $rel_file_path[0];
// $rel_file_path = substr($_SERVER['REQUEST_URI'], strlen(dirname($_SERVER['PHP_SELF'])));

$filepath = realpath(\e::config('APP_ROOT') . '/' . $rel_file_path);
if ($filepath && strlen($filepath) > strlen(\e::config('APP_ROOT'))) {

    $arrayZips = array("text/css", "application/javascript", 'image/png', 'image/jpeg', 'image/gif');
    $arrayExtensions = array(".css", ".js", '.png', '.jpg', '.gif');
    $extension = strtolower((false === $pos = strrpos($filepath, '.')) ? '' : substr($filepath, $pos));

    $pos = array_search($extension, $arrayExtensions);
    if ($pos === false) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE); // возвращает mime-тип
        $mime_type = finfo_file($finfo, $filepath);
        finfo_close($finfo);
    } else {
        $mime_type = $arrayZips[$pos];
    }

    header("Content-Type:" . $mime_type);
    readfile($filepath);
} else {
    // echo "File ".\e::config('APP_ROOT') . '/' . $rel_file_path." not found";
    echo "File ".\e::config('APP_ROOT') . '/' . $rel_file_path." not found";
}

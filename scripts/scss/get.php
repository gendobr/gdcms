<?php

/**
 * Компилятор для scss
 * пример вызова
 * http://localhost/cms/index.php?action=scss%2Fget&site_id=62&f=znu12files/css/common.scss
 */ 

global $main_template_name;
$main_template_name = '';

$minify = false;


run('site/menu');


$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
$input_vars['site_id'] = $this_site_info['id'];
//------------------- site info - end ------------------------------------------
//$filename = preg_replace("/\\W/",'-',$_REQUEST['f']);

function scss_nameparse($str){
    
    $result = preg_replace_callback (
            "/\\{([^}]+)\\}/", 
            function($matches){
                $tmp=explode('||',$matches[1]);
                $param=\e::request(trim($tmp[0]),isset($tmp[1])?trim($tmp[1]):'');
                return preg_replace("/[^a-z0-9_-]/i",'-',$param);
            }, 
            $str);
    // echo $result;
    return $result;
}




$file = scss_nameparse(\e::request('f'));

$basename = \core\fileutils::encode_file_name(basename($file));

//prn(explode('/', dirname($file)));
//prn(array_map(function($d) { return encode_dir_name($d); }, explode('/', dirname($file))));
//prn(array_filter( array_map(function($d) { return encode_dir_name($d); }, explode('/', dirname($file))),function($f) {  return strlen(trim($f)) > 0;}));
//prn(join('/',  array_filter( array_map(function($d) { return encode_dir_name($d); }, explode('/', dirname($file))),function($f) {  return strlen(trim($f)) > 0;})));

$dirname=dirname($file);
if($dirname!='.'){
    $dirname = join('/',  array_filter( array_map(function($d) { return \core\fileutils::encode_dir_name($d); }, explode('/', $dirname)),function($f) {  return strlen(trim($f)) > 0;}));
}else{
    $dirname='';
}


if ($dirname) {
    $scssRelativePath = "{$dirname}/{$basename}";
} else {
    $scssRelativePath = $basename;
}

$scssFilepath = realpath("{$this_site_info['site_root_dir']}/{$scssRelativePath}");

if (!$scssFilepath) {
    $scssFilepath = realpath(\e::config('TEMPLATE_ROOT') . "/{$scssRelativePath}");
}

if (!$scssFilepath) {
    echo "/* file  $scssRelativePath not found */";
    return;
}


//$cachePath = "{$this_site_info['site_root_dir']}/cache/" . md5($scssFilepath) . '.css';
$cachePath = \e::config('CACHE_ROOT')."/{$this_site_info['dir']}/" . md5($scssFilepath) . '.css';
\core\fileutils::path_create(\e::config('CACHE_ROOT'), \e::config('CACHE_ROOT')."/{$this_site_info['dir']}/");

echo "/* cache $cachePath */";
if (!file_exists($cachePath) || filemtime($scssFilepath) > filemtime($cachePath)) {

    $cssRootDir = dirname($scssFilepath);

    require \e::config('SCRIPT_ROOT') . "/scss/scss.inc.php";
    $scss = new scssc();
    $scss->setImportPaths("$cssRootDir/");

    $css = $scss->compile(file_get_contents($scssFilepath));

    // minification
    if ($minify) {
        $css = str_replace(Array("\n", "\r"), '', $css);
        $css = preg_replace("/ +/", ' ', $css);
        $css = preg_replace("/ \\{ /", '{', $css);
        $css = preg_replace("/ \\} /", '}', $css);
        $css = str_replace(Array("; ", ": ", ", "), Array(';', ':', ','), $css);
        $css = preg_replace("/\\/\\*[^*]*\\*\\//", '', $css);
    }
    file_put_contents($cachePath, $css);
}else{
    $css=file_get_contents($cachePath);
}

header("Content-Type: text/css");
echo $css;

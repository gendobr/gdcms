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
run('lib/file_functions');

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

$file = $input_vars['f'];
$basename = encode_file_name(basename($file));

//prn(explode('/', dirname($file)));
//prn(array_map(function($d) { return encode_dir_name($d); }, explode('/', dirname($file))));
//prn(array_filter( array_map(function($d) { return encode_dir_name($d); }, explode('/', dirname($file))),function($f) {  return strlen(trim($f)) > 0;}));
//prn(join('/',  array_filter( array_map(function($d) { return encode_dir_name($d); }, explode('/', dirname($file))),function($f) {  return strlen(trim($f)) > 0;})));

        
$dirname = join('/',  array_filter( array_map(function($d) { return encode_dir_name($d); }, explode('/', dirname($file))),function($f) {  return strlen(trim($f)) > 0;}));

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


$cachePath = "{$this_site_info['site_root_dir']}/cache/" . md5($scssFilepath) . '.css';
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

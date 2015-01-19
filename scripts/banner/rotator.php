<?php

/*
 * sample usage:
 * insert into HTML code
 *
 * <script type="text/javascript" src=".../index.php?action=banner%2Frotator&site_id=73&lang=ukr"></script>
 *
 * then all *ukr.html files located in the "banners" directory will be shown in random order
 *
 */
// banner rotation script
//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
//prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0){
    die();
}
//------------------- get site info - end --------------------------------------
if (isset($input_vars['interface_lang'])) {
    if ($input_vars['interface_lang']) {
        $input_vars['lang'] = $input_vars['interface_lang'];
    }
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
$input_vars['lang'] = get_language('lang');
//-------------------------- load messages - begin -----------------------------
$txt = load_msg($input_vars['lang']);
//-------------------------- load messages - end -----------------------------


$banners_at_once = isset($input_vars['n']) ? (int) $input_vars['n'] : 1;
if ($banners_at_once < 1) {
    $banners_at_once = 1;
}

global $main_template_name;
$main_template_name = '';

run("lib/file_functions");

$banner_dir = ereg_replace('/$', '', sites_root . '/' . $this_site_info['dir']) . '/banners';
$files = ls($banner_dir);
$files = $files['files'];

$cnt = count($files);
$filenameending = "{$input_vars['lang']}.html";
$filenameending_len = strlen($filenameending);
for ($i = 0; $i < $cnt; $i++) {
    // echo "// {$files[$i]}\n";
    // echo "// ".substr($files[$i],-$filenameending_len)."\n";
    if (substr($files[$i], -$filenameending_len) != $filenameending) {
        unset($files[$i]);
    }
}
$files = array_values($files);
//prn($files);

srand((float) microtime() * 10000000);
$rand_keys = array_rand($files, $banners_at_once);
if (!is_array($rand_keys)) {
    $rand_keys = Array($rand_keys);
}
//prn($rand_keys);


$cnt = 0;
foreach ($rand_keys as $key) {
    //prn($banner_dir . '/' . $files[$key]);exit();
    $fl = file_get_contents($banner_dir . '/' . $files[$key]);
    //prn($banner_dir . '/' . $files[$key].'<hr>'.checkStr($fl));exit();

    $fl = str_replace(Array("\n", "\r", "\""), Array('', '', "\\\""), $fl);
    echo "// {$files[$key]}\n";
    echo "document.writeln(\"&nbsp;{$fl}\");\r\n";
}

// remove from history
nohistory($input_vars['action']);
?>
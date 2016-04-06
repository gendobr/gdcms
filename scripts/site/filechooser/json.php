<?php

run('site/menu');
$GLOBALS['main_template_name'] = '';
//------------------- site info - begin ----------------------------------------
$site_id = (int) $input_vars['site_id'];
$this_site_info = get_site_info($site_id);
#$this_site_info = \e::db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
#//prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
$site_root_dir = preg_replace("/\\/\$/",'',$this_site_info['site_root_dir']);
$site_root_url = preg_replace("/\\/\$/",'',$this_site_info['site_root_url']);
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------


run('lib/file_functions');


// current directory
if (!isset($input_vars['current_dir'])) {
    $input_vars['current_dir'] = '';
}
$input_vars['current_dir']=preg_replace('/\/$/','',$input_vars['current_dir']);
$current_dir = realpath($site_root_dir . '/' . $input_vars['current_dir']);
if(!$current_dir || strlen(realpath($site_root_dir))>strlen($current_dir)){
    $input_vars['current_dir']='';
    $current_dir = realpath($site_root_dir);
}

$current_url = preg_replace("/\\/\$/",'',$site_root_url . '/' . $input_vars['current_dir']);
// prn('$current_url='.$current_url);
$current_dir_content = scandir($current_dir);
//prn($current_dir_content);

$cnt = count($current_dir_content);
$json = Array('files' => Array(), 'dirs' => Array(), 'parents' => Array());
for ($i = 0; $i < $cnt; $i++) {
    if ($current_dir_content[$i] == '.' || $current_dir_content[$i] == '..') {
        continue;
    }
    $filename = $current_dir . '/' . $current_dir_content[$i];
    if (is_dir($filename)) {
        $json['dirs'][] = Array(
            'name' => $current_dir_content[$i],
            'url' => "index.php?action=site/filechooser/json&site_id=$site_id&current_dir=" . $input_vars['current_dir'] . '/' . $current_dir_content[$i]
        );
    }
    if (is_file($filename)) {
        $json['files'][] = Array(
            'name' => $current_dir_content[$i],
            'url' => $current_url . '/' . $current_dir_content[$i]
        );
    }
}
//prn($current_dir);
$_path = $current_dir;
$len = strlen($site_root_dir);
$i = 0;
while (strlen($_path) > $len && $i++ < 1000) {
    $relative_path = str_replace($site_root_dir, '', $_path);
    $json['parents'][] = Array(
        'name' => ($relative_path != '' ? basename($relative_path) : 'home'),
        'url' => "index.php?action=site/filechooser/json&site_id=$site_id&current_dir=" . $relative_path
    );
    $_path = dirname($_path);
}
$json['parents'][] = Array(
    'name' => 'home',
    'url' => "index.php?action=site/filechooser/json&site_id=$site_id&current_dir="
);
$json['parents'] = array_reverse($json['parents']);
$json['datasource']="index.php?action=site/filechooser/json&site_id=$site_id&current_dir=" . $input_vars['current_dir'];
echo json_encode($json);
?>
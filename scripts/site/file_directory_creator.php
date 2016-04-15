<?php

/*
 * AJAX upload
 * file receiver
 */
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
//prn($this_site_info);exit();
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

if (!isset($input_vars['newdir']) || strlen($input_vars['newdir']) == 0) {
    echo "Dirname not posted. Exiting";
    return '';
}


$log_file_path=\e::config('CACHE_ROOT')."/directory_creator.log.txt";


// save log
ml('site/file_directory_creator', Array($this_site_info, $input_vars));



// load dirname
if (!isset($input_vars['current_dir'])) {
    $input_vars['current_dir'] = '';
}
$input_vars['current_dir'] = preg_replace("/^\\/|\\/\$/", '', $input_vars['current_dir']);

# ----------------------- get destination dir - begin --------------------------
$destination_dir = preg_replace("/\\/+/", '/', str_replace("\\", '/', "{$this_site_info['site_root_dir']}/" . $input_vars['current_dir']));
$destination_dir = realpath($destination_dir);
$destination_dir = str_replace("\\", '/', $destination_dir);
if (stristr($destination_dir, $this_site_info['site_root_dir']) === false) {
    // prn("Invalid destination dir  {$this_site_info['site_root_dir']}+{$input_vars['dirname']} = {$destination_dir}");
    file_put_contents($log_file_path, "Invalid destination dir  {$this_site_info['site_root_dir']}+{$input_vars['dirname']} = {$destination_dir}");
    die();
}
// for logging
$destination_dir_relative=preg_replace("/^\\/+|\\/+\$/",'',substr($destination_dir, strlen($this_site_info['site_root_dir'])));
if(strlen($destination_dir_relative)==0){
   $destination_dir_url="{$this_site_info['site_root_url']}";
}else{
   $destination_dir_url="{$this_site_info['site_root_url']}/{$destination_dir_relative}";
}
////$destination_dir_url="{$this_site_info['site_root_url']}{$destination_dir_relative}";
//site_root_url
# ----------------------- get destination dir - end ----------------------------











function utf8_to_cp1251($s) {
    $out='';
    $byte2=false;
    if ((mb_detect_encoding($s, 'UTF-8,CP1251')) == "UTF-8") {
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

// $log = 'Uploading files: into ' . $destination_dir_relative .';</br>';
$log = '';
$newdir=  \core\fileutils::encode_dir_name(utf8_to_cp1251($input_vars['newdir']));
$path=$destination_dir."/".$newdir;
$log.=" Creating directory $newdir<br>\n";
if(mkdir($path)){
    $log.="SUCCESS<br>\n";
}else{
    $log.="ERROR<br>\n";
}

file_put_contents($log_file_path, strip_tags($log)."\n");
echo $log;
?>
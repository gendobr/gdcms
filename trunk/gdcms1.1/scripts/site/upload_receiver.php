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

if (!isset($_FILES) || count($_FILES) == 0) {
    echo "File not posted. Exiting";
    return '';
}

if(defined('template_cache_root')){
    $log_file_path=template_cache_root."/multiple_upload_receiver.log.txt";
}else{
    $log_file_path=local_root . '/template_cache/multiple_upload_receiver.log.txt';
}


// save log
ml('site/upload_receiver', Array($this_site_info, $_FILES));

// load file functions
run('lib/file_functions');

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
    write_to_file($log_file_path, "Invalid destination dir  {$this_site_info['site_root_dir']}+{$input_vars['dirname']} = {$destination_dir}");
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










//function utf8_to_cp1251($s) {
//    $out='';
//    $byte2=false;
//    if ((mb_detect_encoding($s, 'UTF-8,CP1251')) == "UTF-8") {
//        for ($c = 0; $c < strlen($s); $c++) {
//            $i = ord($s[$c]);
//            if ($i <= 127)
//                $out.=$s[$c];
//            if ($byte2) {
//                $new_c2 = ($c1 & 3) * 64 + ($i & 63);
//                $new_c1 = ($c1 >> 2) & 5;
//                $new_i = $new_c1 * 256 + $new_c2;
//                if ($new_i == 1025) {
//                    $out_i = 168;
//                } else {
//                    if ($new_i == 1105) {
//                        $out_i = 184;
//                    } else {
//                        $out_i = $new_i - 848;
//                    }
//                }
//                $out.=chr($out_i);
//                $byte2 = false;
//            }
//            if (($i >> 5) == 6) {
//                $c1 = $i;
//                $byte2 = true;
//            }
//        }
//        return $out;
//    } else {
//        return $s;
//    }
//}

// $log = 'Uploading files: into ' . $destination_dir_relative .';</br>';
$log = '';
//$log.="\r\n form element names: " . join(',', array_keys($_FILES)) . ';';
foreach ($_FILES as $fld => $vals) {
    if (!preg_match("/\\.(" . allowed_file_extension . ")\$/i", $vals['name'])) {
        $log.=" <b><font color=red>File {$vals['name']} has forbidden extension</font></b><br>";
        continue;
    }

    //$vals['name'] = $_FILES[$fld]['name'] = encode_file_name(iconv('utf8',site_charset,$vals['name']));
    //$vals['name'] = $_FILES[$fld]['name'] = encode_file_name($vals['name']);
    //$vals['name'] = $_FILES[$fld]['name'] = encode_file_name(utf8_to_cp1251($vals['name']));
    $vals['name'] = $_FILES[$fld]['name'] = encode_file_name($vals['name']);

    $fname = upload_file($fld, $destination_dir);
    $log.="Uploading file {$_FILES[$fld]['name']} => <a href='{$destination_dir_url}/{$fname}' target=_blank>$destination_dir_relative/$fname</a>;";
    if (strlen($fname) > 0) {
        $log.="OK\n";
    } else {
        $log.="ERROR:\n";
        switch ($vals['error']) {
            case UPLOAD_ERR_INI_SIZE : $log.= "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
                break;
            case UPLOAD_ERR_FORM_SIZE: $log.= "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
                break;
            case UPLOAD_ERR_PARTIAL : $log.= "The uploaded file was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE : $log.= "No file was uploaded.";
                break;
        }
    }
    write_to_file($log_file_path, strip_tags($log)."\n");
}

echo $log;
?>
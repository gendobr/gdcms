<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

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




$list=\core\fileutils::ls_r($site_root_dir);
$cnt=count($list);
$len=strlen($site_root_dir);
for($i=0;$i<$cnt;$i++){
    if(preg_match("/png|jpeg|jpg|gif/i",$list[$i])){
        $list[$i]=substr($list[$i],$len);
    }else{
        unset($list[$i]);
    }
}
sort($list);
$list=array_values($list);
// prn($list);

echo "var tinyMCEImageList = new Array(\n";
$delim='';
foreach($list as $img){
//	// Name, URL
    echo $delim;
    echo "[\"{$img}\", \"{$site_root_url}{$img}\"]";
    if($delim==''){
       $delim=",\n";
    }
}
echo ")";
?>
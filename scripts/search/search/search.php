<?php

run('site/page/page_view_functions');
run('site/menu');


// ---------------- language - begin -------------------------------------------
if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
$input_vars['lang']=$_SESSION['lang'];
$lang = get_language('lang');
$txt=load_msg($input_vars['lang']);
// ---------------- language - end ---------------------------------------------






//------------------- main site info - begin ------------------------------------
  $list_of_sites=array_map('checkInt',explode(',',$input_vars['site_id']));
  $site_id = abs((int)$list_of_sites[0]);
  $this_site_info = get_site_info($site_id,$input_vars['lang']);
 # prn($this_site_info);
  if($this_site_info['id']<=0) die($txt['Site_not_found']);
  $list_of_sites=array_unique($list_of_sites);
//------------------- main site info - end --------------------------------------
//--------------------------- get site template - begin ------------------------
  $custom_page_template = sites_root.'/'.$this_site_info['dir'].'/template_index.html';
  #prn('$news_template',$news_template);
  if (is_file($custom_page_template)) {
    $this_site_info['template'] = $custom_page_template;
}
//--------------------------- get site template - end --------------------------
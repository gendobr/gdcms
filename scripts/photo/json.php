<?php

header('Content-Type:text/html; charset='.site_charset);
run('site/menu');
$GLOBALS['main_template_name'] = '';
//------------------- site info - begin ----------------------------------------
$site_id = (int) $input_vars['site_id'];
$this_site_info = get_site_info($site_id);
#$this_site_info =\e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
#//prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
$site_root_dir = preg_replace("/\\/\$/", '', $this_site_info['site_root_dir']);
$site_root_url = preg_replace("/\\/\$/", '', $this_site_info['site_root_url']);
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = 
    $input_vars['page_header'] = 
    $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------


$input_vars['lang'] = $lang = get_language('lang,interface_lang');

//
// get list of pages
$pagelist = \e::db_getrows("select * from <<tp>>photo_category WHERE site_id=$site_id AND photo_category_visible ORDER BY photo_category_path ASC");
                    
// prn($pagelist);
$json = ['files' => [], 'dirs' => [], 'parents' => []];

for ($i = 0, $cnt = count($pagelist); $i < $cnt; $i++) {
    
    $pagelist[$i]['photo_category_title']=get_langstring($pagelist[$i]['photo_category_title'],$lang);
    
    $photo_category_view_url=str_replace([
                '{photo_category_code}','{photo_category_path}','{photo_category_id}','{lang}', '{site_id}'
            ],[
                $pagelist[$i]['photo_category_code'],$pagelist[$i]['photo_category_path'],$pagelist[$i]['photo_category_id'],$lang, $this_site_info['id']
            ],\e::config('url_pattern_photo_category'));
    
    $element_id='gallery'.md5($i.rand(0, 1000));
    $json['files'][] = Array(
        'name' => $pagelist[$i]['photo_category_code'],
        'url' => $photo_category_view_url,
        'prefix' => str_repeat('&nbsp+&nbsp', substr_count($pagelist[$i]['photo_category_path'], '/')),
        'htmlblock' =>    "<script type=\"text/javascript\" src=\"".\e::config('APPLICATION_PUBLIC_URL')."/scripts/lib/ajax_loadblock.js\"></script>"
        . "<div id=\"{$element_id}\"> </div>"
        . "<script type=\"text/javascript\">"
        . "ajax_loadblock('$element_id',"
        . "'".\e::config('APPLICATION_PUBLIC_URL')."/index.php?"
        . "action=photo/photo_category_block&"
        . "site_id={$site_id}&lang={$lang}"
        . "&photo_category_id={$pagelist[$i]['photo_category_id']}"
        . "&template=',null);"
        . "</script>"
        ."<div id={$element_id}><a href='".$photo_category_view_url."'>". htmlspecialchars($pagelist[$i]['photo_category_title'])."</a></div>"

    );
}


echo json_data($json);
?>
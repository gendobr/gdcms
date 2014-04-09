<?php

header('Content-Type:text/html; charset='.site_charset);
run('site/menu');
$GLOBALS['main_template_name'] = '';
//------------------- site info - begin ----------------------------------------
$site_id = (int) $input_vars['site_id'];
$this_site_info = get_site_info($site_id);
#$this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
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
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
//
//
// get list of pages

// get list of pages
//$pagelist = db_getrows("select distinct rozdil
//                        from {$GLOBALS['table_prefix']}photogalery
//                        WHERE site=$site_id ORDER BY rozdil ASC");

$pagelist = db_getrows("select distinct rozdil
                        from {$GLOBALS['table_prefix']}photogalery_rozdil
                        WHERE site_id=$site_id ORDER BY rozdil ASC");
                    
// prn($pagelist);
$json = Array('files' => Array(), 'dirs' => Array(), 'parents' => Array());
$cnt = count($pagelist);
$url_prefix = site_root_URL . "/index.php?action=gallery/photogallery&site_id={$site_id}&lang={$_SESSION['lang']}&rozdilizformy=";
for ($i = 0; $i < $cnt; $i++) {
    $pos = strrpos($pagelist[$i]['rozdil'], '/');
    if ($pos === false) {
        $name = $pagelist[$i]['rozdil'];
    } else {
        $name = substr($pagelist[$i]['rozdil'], $pos);
    }

    $element_id='gallery'.md5($i.rand(0, 1000));
    $json['files'][] = Array(
        'name' => $name,
        'url' => $url_prefix.rawurlencode($pagelist[$i]['rozdil']),
        'prefix' => str_repeat('&nbsp+&nbsp', substr_count($pagelist[$i]['rozdil'], '/')),
        'htmlblock' =>    "<iframe style='width:1px;height:1px;border:none;opacity:0;' src='" . site_root_URL . "/index.php?action=gallery/html&site_id={$site_id}&lang={$_SESSION['lang']}&cat=" . rawurlencode($pagelist[$i]['rozdil']) . "&element={$element_id}'></iframe>"
        ."<div id={$element_id}><a href='".$url_prefix.rawurlencode($pagelist[$i]['rozdil'])."'>".  checkStr($name)."</a></div>"

    );
}


echo json_data($json);
?>
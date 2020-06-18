<?php
/*
 * ����, ������� ���������� ������ ������ ��������� ��� ��������� �����
 * ���������:
 * site_id - ������������� �����
 * lang - ��� �����
 * template - ��� �����  � ��������
 * element - ������������� �������� HTML, � ������� ���� �������� ������
 */

global $main_template_name;
$main_template_name = '';


//------------------- site info - begin ----------------------------------------
run('site/menu');
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    echo 'Site_not_found';
    return 0;
}
//------------------- site info - end ------------------------------------------
// get language
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = \e::config('default_language');
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = \e::config('default_language');
}
$txt = load_msg($input_vars['lang']);
// $lang = $input_vars['lang'];
$lang = get_language('lang');


// get list of categories
run('category/functions');

$cache_path=\e::config('CACHE_ROOT')."/{$this_site_info['dir']}/category_public_list_{$lang}.cache";
\core\fileutils::path_create(\e::config('CACHE_ROOT'), $cache_path);    
$caterory_list = \core\fileutils::get_cached_info($cache_path, 12*3600);
if (! $caterory_list) {
  $caterory_list=category_public_list($site_id, $lang);
  \core\fileutils::set_cached_info($cache_path, $caterory_list);
}




run('site/page/page_view_functions');

// ------------------ draw tree - begin ----------------------------------------
$_template = false;
if(isset($input_vars['template']) && strlen(trim($input_vars['template']))>0){
    $_template = site_get_template($this_site_info, $input_vars['template']);
}else{
    $_template = site_get_template($this_site_info, 'template_category_tree_block');
}
if(!$_template) $_template = 'cms/template_category_tree_block';

$vyvid = process_template($_template, Array(
            'caterory_list' => $caterory_list
            , 'text' => $txt
            , 'site' => $this_site_info
            , 'lang' => $lang
                )
);
// ------------------ draw tree - end ------------------------------------------

if(strlen($vyvid)==0) {echo '';return '';}
echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset='.site_charset.'">
  </head>
  <body>
';
if(isset($input_vars['element']))
{
  echo "
    <div id=toinsert>$vyvid</div>
    <script type=\"text/javascript\">
    <!--
    var from = document.getElementById('toinsert');
    //alert(from.innerHTML);
    var to;
    if(window.top)
    {
      //alert('window.top - OK');
      if(window.top.document)
      {
        //alert('window.top.document - OK');
        to = window.top.document.getElementById('{$input_vars['element']}');
        //alert(to);
        if(to)
        {
           //alert('element - OK');
           to.innerHTML = from.innerHTML;
        }
      }
    }
    // -->
    </script>
    "
    ;
}
else echo $vyvid;

echo '
    </body>
</html>
';
// remove from history
   nohistory($input_vars['action']);

?>
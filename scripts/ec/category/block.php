<?php

/*
 * блок, который показывает полный список категорий для заданного сайта
 * аргументы:
 * site_id - идентификатор сайта
 * lang - код языка
 * template - имя файла  с шаблоном
 * element - идентификатор элемента HTML, в который надо вставить список
 * deep - мексимальная глубина выводимого дерева
 * ec_category_id - ветка, которую надо вывести
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
$deep_restriction = '';
if (isset($input_vars['deep'])) {
    $deep = (int) $input_vars['deep'];
    if ($deep >= 0) {
        $deep_restriction = " and ch.deep<=" . $deep;
    }
}

$category_info = false;
if (isset($input_vars['ec_category_id'])) {
    $ec_category_id = (int) $input_vars['ec_category_id'];
    if ($ec_category_id > 0) {
        $category_info =\e::db_getonerow("SELECT ec_category_id, start, finish FROM <<tp>>ec_category WHERE ec_category_id={$ec_category_id} and site_id=" . $site_id . "");
    }
}


if ($category_info) {
    $start = (int) $category_info['start'];
    $finish = (int) $category_info['finish'];
    // ------------------ get list of categories - begin -----------------------
    $query = "select ch.*, bit_and(pa.is_visible) as visible
              from <<tp>>ec_category pa,
                   <<tp>>ec_category ch
              where pa.start<=ch.start and ch.finish<=pa.finish
                and pa.site_id=" . ((int) $site_id) . "
                and ch.site_id=" . ((int) $site_id) . "
                and {$start}<=ch.start and ch.finish<={$finish}
                and {$start}<=pa.start and pa.finish<={$finish}
                    {$deep_restriction}
              group by ch.ec_category_id
              having visible>0
              order by  ch.start";
    // prn($query);
    $caterory_list = \e::db_getrows($query);
    // ------------------ get list of categories - end -------------------------
} else {
    // ------------------ get list of categories - begin -----------------------
    $query = "select ch.*, bit_and(pa.is_visible) as visible
              from <<tp>>ec_category pa,
                   <<tp>>ec_category ch
              where pa.start<=ch.start and ch.finish<=pa.finish
                and pa.site_id=" . ((int) $site_id) . "
                and ch.site_id=" . ((int) $site_id) . "
               {$deep_restriction}
              group by ch.ec_category_id
              having visible>0
              order by  ch.start";
    // prn($query);
    $caterory_list = \e::db_getrows($query);
    // ------------------ get list of categories - end -------------------------
}



// ------------------ adjust list of categories - begin ------------------------
$category_url_pattern = str_replace(Array('{site_id}', '{lang}'), Array((int) $site_id, $lang), \e::config('url_pattern_ec_category'));
$cnt = count($caterory_list);
for ($i = 0; $i < $cnt; $i++) {
    $caterory_list[$i]['ec_category_title'] = get_langstring($caterory_list[$i]['ec_category_title'], $lang);
    $caterory_list[$i]['ec_category_description'] = get_langstring($caterory_list[$i]['ec_category_description'], $lang);
    $caterory_list[$i]['URL'] = str_replace('{ec_category_id}', $caterory_list[$i]['ec_category_id'], $category_url_pattern);
}
// prn($caterory_list);
// ------------------ adjust list of categories - end --------------------------





run('site/page/page_view_functions');

// ------------------ draw tree - begin ----------------------------------------
$_template = false;
if (isset($input_vars['template']) && strlen(trim($input_vars['template'])) > 0) {
    $_template = site_get_template($this_site_info, 'template_ec_category_block');
}
if (!$_template)
    $_template = 'cms/template_ec_category_block';

$vyvid = process_template($_template, Array(
    'caterory_list' => $caterory_list
    , 'text' => $txt
    , 'site' => $this_site_info
    , 'lang' => $lang
        )
);
// ------------------ draw tree - end ------------------------------------------


header('Content-Type:text/html; charset='.site_charset);
header('Access-Control-Allow-Origin: *');
echo $vyvid;


/*
if (strlen($vyvid) == 0) {
    echo '';
    return '';
}
echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset='.site_charset.'">
  </head>
  <body>
';
if (isset($input_vars['element'])) {
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
else
    echo $vyvid;

echo '
    </body>
</html>
';
*/
// remove from history
nohistory($input_vars['action']);
?>
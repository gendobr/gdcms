<?php

# prn($_REQUEST);

run('site/menu');

# ------------------- site info - begin ----------------------------------------
if (isset($input_vars['site_id'])) {
    $site = $site_id = checkInt($input_vars['site_id']);
    $this_site_info = get_site_info($site);

    if (checkInt($this_site_info['id']) <= 0) {
        $input_vars['page_title'] = $text['Site_not_found'];
        $input_vars['page_header'] = $text['Site_not_found'];
        $input_vars['page_content'] = $text['Site_not_found'];
        return 0;
    }
} else {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
# ------------------- site info - end ------------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------
// $GLOBALS['main_template_name']='popup';
// site_id - идентификатор сайта
// lang - код языка
// template - имя файла  с шаблоном
// element - идентификатор элемента HTML, в который надо вставить список
// deep - мексимальная глубина выводимого дерева
// ec_category_id - ветка, которую надо вывести

$block_id = 'block' . md5(time() . session_id());
$vyvid = '
    <div id="' . $block_id . '"></div>
    <script type="text/javascript" src="' . site_public_URL . '/scripts/lib/ajax_loadblock.js"></script>
    <script type="text/javascript">
      ajax_loadblock("' . $block_id . '","'
        . site_public_URL . '/index.php?action=ec/category/block&site_id=' . $site_id
        . '&lang=' . $_SESSION['lang']
        . '&deep=-1'
        . '&ec_category_id=-1'
        . '&template=someTemplateFile.html",null);
    </script>
';
//--------------------------- draw - begin -------------------------------------
$input_vars['page_title'] =
        $input_vars['page_header'] = $this_site_info['title'] . ' - ' . text('HTML_code_to_insert_list_of_categories');
$input_vars['page_content'] = "
    <pre style='width:100%; overflow:scroll;height:200px;'>
    " . checkStr($vyvid) . "
    </pre>
    <b>site_id</b> - ".text('ec_category_html_site_id')."<br>
    <b>lang</b> - ".text('ec_category_html_lang')."<br>
    <b>template</b> - ".text('ec_category_html_template')."<br>
    <!-- b>element</b> - идентификатор элемента HTML, в который надо вставить список<br -->
    <b>deep</b> - ".text('ec_category_html_deep')."<br>
    <b>ec_category_id</b> - ".text('ec_category_html_category_id')."<br>
";

// ------------------ get list of categories - begin -----------------------
$query = "select ch.*, bit_and(pa.is_visible) as visible
              from {$GLOBALS['table_prefix']}ec_category pa,
                   {$GLOBALS['table_prefix']}ec_category ch
              where pa.start<=ch.start and ch.finish<=pa.finish
                and pa.site_id=" . ((int) $site_id) . "
                and ch.site_id=" . ((int) $site_id) . "
              group by ch.ec_category_id
              having visible>0
              order by  ch.start";
// prn($query);
$caterory_list = db_getrows($query);
// ------------------ get list of categories - end -------------------------
$input_vars['page_content'].= '<h4>'.text('ec_category_html_existing_category_ids').'</h4>';
foreach($caterory_list as $cat){
    $input_vars['page_content'].= "<div style='padding-left:".(20*$cat['deep'])."px'>{$cat['ec_category_id']} - ".get_langstring($cat['ec_category_title'])."</div>";
}


//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
//--------------------------- draw - begin -------------------------------------
?>
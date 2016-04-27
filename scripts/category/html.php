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







$block_id='block'.md5(time().  session_id());
$vyvid = '
    <div id="'.$block_id.'"></div>
    <script type="text/javascript" src="' . site_public_URL . '/scripts/lib/ajax_loadblock.js"></script>
    <script type="text/javascript">
      ajax_loadblock("'.$block_id.'","'.site_public_URL
        .'/index.php?action=category/tree_block&site_id='
        .$site_id.'&lang='.$_SESSION['lang'].'&template=someTemplateFile.html",null);
    </script>
';
//--------------------------- draw - begin -------------------------------------
$input_vars['page_title'] =
$input_vars['page_header'] = $this_site_info['title'] . ' - ' . text('Page_and_news_category_html_code');
$input_vars['page_content'] = "
    <pre style='width:100%; overflow:scroll;height:200px;'>
    ".  htmlspecialchars($vyvid)."
    </pre>
";

//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
//--------------------------- draw - begin -------------------------------------
?>
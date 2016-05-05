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











$vyvid = '';
$this_action = $input_vars['action'];

# --------------------------- list of categories - begin -----------------------
$categories = "<b>{$text['Gallery_categories']}:</b><br/>";
$rozdilizformy = (isset($input_vars['rozdilizformy'])) ? $input_vars['rozdilizformy'] : '';
if ($rozdilizformy != '') {
    $n = count(explode('/', $rozdilizformy)) + 1;
    $path = explode('/', $rozdilizformy);
    $r = 0;
    $re1 = '';
    $vyvid.= " <a href=" . site_root_URL . "/index.php?action={$this_action}&rozdilizformy=$re1&site_id=$site_id>{$text['Image_gallery']}</a>";
    while ($r < $n - 1) {
        if ($r > 0) {
            $re1.="/" . $path[$r];
        } else {
            $re1.=$path[$r];
        }
        if ($r == $n - 2) {
            $vyvid.= " /{$path[$r]}";
        } else {
            $vyvid.= " /<a href=" . site_root_URL . "/index.php?action={$this_action}&rozdilizformy=" . rawurlencode($re1) . "&site_id=$site_id>{$path[$r]}</a>";
        }
        $r = $r + 1;
    }

    # ----------------------------- draw HTML code - begin --------------------
    $element_id = "d" . time();
    //    $vyvid.="<p>{$text['Gallery_html_code']} : <div style='border:1px solid blue;padding:20px;'>" .
    //            checkStr("
    //       <div id={$element_id}>&nbsp;</div>
    //       <iframe style='width:1px;height:1px;border:none;' src='" . site_root_URL . "/index.php?action=gallery/html&site_id={$site_id}&lang={$_SESSION['lang']}&cat=" . rawurlencode($rozdilizformy) . "&element={$element_id}&orderBy=date_desc'></iframe>"
    //            ) . '</div></p>';
    $vyvid.="<p>{$text['Gallery_html_code']} : <div style='border:1px solid blue;padding:20px;'>" .
            htmlspecialchars("
             <script type=\"text/javascript\" src=\"" . site_public_URL . "/scripts/lib/ajax_loadblock.js\"></script>
             <div id={$element_id}> </div>
             <script type=\"text/javascript\">
             ajax_loadblock('{$element_id}','" . site_public_URL 
                     . "/index.php?action=gallery/html"
                     ."&site_id={$site_id}"
                     ."&lang={$_SESSION['lang']}"
                     ."&rows=10&cat=" . rawurlencode($rozdilizformy)
                     ."&template=&orderBy=date_desc',null);
             </script>"
            ) . '</div></p><p>
                
                <b>orderBy</b> in (
                date_asc, date_desc, category_asc, category_desc
                title_asc, title_desc, author_asc, author_desc
                year_asc, year_desc, random_asc, random_desc)
            </p>';
    # ----------------------------- draw HTML code - end ----------------------

    $vyvid.="<p>Subcategories:<div style='margin-top:5px;margin-bottom:5px; padding-left:5px;'>";
    $result01 = \e::db_getrows(
                "select distinct SUBSTRING_INDEX( rozdil, '/', $n )  rozdil
                 FROM <<tp>>photogalery
                 WHERE rozdil LIKE '$rozdilizformy/%'
                   AND vis = 1
                   AND site = {$site_id}
                 ", $db);
    foreach($result01 as $row01){
        $vyvid .= "<a href=" . site_root_URL . "/index.php?action={$this_action}&rozdilizformy=" . rawurlencode($row01['rozdil']) . "&site_id=$site_id>{$row01['rozdil']}</a><br>";
    }
    $vyvid.="</div></p>";
} else {
    # ----------------------------- draw HTML code - begin --------------------
    $element_id = "d" . time();
    $vyvid.="<p>{$text['Gallery_html_code']} : <div style='border:1px solid blue;padding:20px;'>" .
            htmlspecialchars("
             <script type=\"text/javascript\" src=\"" . site_public_URL . "/scripts/lib/ajax_loadblock.js\"></script>
             <div id={$element_id}> </div>
             <script type=\"text/javascript\">
             ajax_loadblock('{$element_id}','" . site_public_URL 
                     . "/index.php?action=gallery/html"
                     ."&site_id={$site_id}"
                     ."&lang={$_SESSION['lang']}"
                     ."&rows=10"
                     ."&template=&orderBy=date_desc',null);
             </script>"
            ) . '</div></p>';

    # ----------------------------- draw HTML code - end ----------------------
//127.0.0.1/cms/index.php?action=gallery/html&site_id=1&lang=ukr&cat=2008%2F%ED%EE%E2%E8%ED%E0%202&element=d1364883505&rows=15
    $result00 = \e::db_getrows("SELECT DISTINCT SUBSTRING_INDEX( rozdil, '/', 1 )  rozdil
                                	FROM <<tp>>photogalery
                                	WHERE vis = 1 AND site = {$site_id}
                                	GROUP BY rozdil2", $db);
    foreach($result00 as $row00){
        $vyvid .= "<a href=" . site_root_URL . "/index.php?action={$this_action}&rozdilizformy=" . rawurlencode($row00['rozdil']) . "&site_id=$site_id>{$row00['rozdil']}</a><br>";
    }
}
# --------------------------- list of categories - end -------------------------
//









$result = \e::db_getrows("SELECT * FROM <<tp>>photogalery WHERE rozdil = '" . \e::db_escape($rozdilizformy) . "' AND vis = 1 AND site = '$site_id' ORDER BY rik DESC");
if ($result) {




    $vyvid .= "<table cellpadding=5 cellspacing=4 border=0>";
    $n = 1;
    $prefix = preg_replace("/\\/$/", '', $this_site_info['url']);
    foreach ($result as $row3) {
        $m = $n % 2;
        if ($m == 1) {
            $vyvid .= "<tr>";
        }
        $vyvid .= "<td valign=top><a href=" . site_root_URL . "/index.php?action=gallery/photo&site_id={$site_id}&lang={$_SESSION['lang']}&item="
                . $row3['id']
                . "><img style='border:5px silver outset;' src="
                . $prefix . '/gallery/'
                . $row3['photos_m']
                . "></a><br>"
                . $row3['pidpys']
                . "</td>";
        if ($m == 0) {
            $vyvid .= "</tr>";
        }
        $n = $n + 1;
    }
    $vyvid .= "</table><hr>";
}

































//--------------------------- draw - begin -------------------------------------
$input_vars['page_title'] =
        $input_vars['page_header'] = $this_site_info['title'] . ' - ' . $text['Gallery_get_html_code'];
$input_vars['page_content'] = $vyvid;

//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
//--------------------------- draw - begin -------------------------------------
?>
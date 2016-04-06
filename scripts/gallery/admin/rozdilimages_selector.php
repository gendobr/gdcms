<?php

// prn($_REQUEST);
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


if (isset($input_vars['setimage'])) {
    //prn($input_vars);
    $setimage = (int) $input_vars['setimage'];
    $image_info = \e::db_getonerow("SELECT * FROM {$table_prefix}photogalery where id={$setimage} and site={$this_site_info['id']}");

    if ($image_info) {
        $rozdil_info = \e::db_getonerow(
                        "SELECT *
                 FROM {$table_prefix}photogalery_rozdil
                 WHERE site_id={$this_site_info['id']}
                 AND rozdil='" . \e::db_escape($input_vars['rozdil']) . "'");
        // prn($rozdil_info);
        if ($rozdil_info) {
            // update record
            $query = "UPDATE {$table_prefix}photogalery_rozdil
                   SET photos='" . \e::db_escape($image_info['photos']) . "',
                       photos_m='" . \e::db_escape($image_info['photos_m']) . "',
                       image_id=" . ((int) $image_info['id']) . "
                   WHERE site_id={$this_site_info['id']}
                       AND rozdil='" . \e::db_escape($input_vars['rozdil']) . "'
                       AND id=" . ((int) $rozdil_info['id']) . "";
            // prn($query);
            \e::db_execute($query);
        } else {
            // create record
            $query = "insert into {$table_prefix}photogalery_rozdil (photos,photos_m,rozdil,rozdil2,site_id,image_id)
                SELECT photos,photos_m,'" . \e::db_escape($input_vars['rozdil']) . "','" . md5($input_vars['rozdil']) . "',site,id
                FROM {$table_prefix}photogalery where id={$setimage} and site={$this_site_info['id']}";
            // prn($query);
            \e::db_execute($query);
        }
        echo "<html><body>
            <script type=\"text/javascript\">
              window.top.location.reload();
            </script>
            </body></html>";
        exit();
    }
}


$vyvid = "

";


$n = count(explode('/', $input_vars['rozdil']));


run('gallery/gallery_images');
//prn($input_vars['rozdil']);
//$images=new GalleryImages($_SESSION['lang'], $this_site_info, 0, $input_vars['rozdil'], '');
//prn($images->list);
//prn($n);
$image_list = \e::db_getrows(
                "SELECT DISTINCT *
         FROM {$GLOBALS['table_prefix']}photogalery
         WHERE site = {$this_site_info['id']}
             AND SUBSTRING_INDEX( rozdil, '/', $n )='" . \e::db_escape($input_vars['rozdil']) . "'
         ORDER BY rozdil");
//prn($image_list);

$url_prefix = preg_replace("/\\/+$/", '', $this_site_info['url']) . '/gallery';
$prefix = "index.php?action=gallery/admin/rozdilimages_selector&site_id={$this_site_info['id']}&rozdil=" . rawurlencode($input_vars['rozdil']) . "&setimage=";
// prn('$prefix=',$prefix);
foreach ($image_list as $image) {
    $vyvid.="<a href=\"{$prefix}{$image['id']}\"><img src=\"{$url_prefix}/{$image['photos_m']}\" class=\"imgRozdil2\" id=\"image_{$image['id']}\"></a> ";
}

// prn($GLOBALS['main_template_name']);
$GLOBALS['main_template_name'] = 'design/popup';
$input_vars['page_title'] = $input_vars['page_header'] = $this_site_info['title'] . ' - ' . text('image_rozdilimages');
$input_vars['page_content'] = $vyvid;
// echo $vyvid;
?>
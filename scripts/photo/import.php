<?php

run('site/menu');

# ------------------- site info - begin ----------------------------------------
$site_id = \e::cast('integer', \e::request('site_id', 0));
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
$site_id = $this_site_info['id'];
# ------------------- site info - end ------------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------


$list_of_languages = list_of_languages();
$js_lang = Array();
foreach ($list_of_languages as $l) {
    $js_lang[$l['name']] = $text[$l['name']];
}
$js_lang = json_encode($js_lang);


// get new categories
$tmp = \e::db_getrows("SELECT * FROM <<tp>>photo_category photo_category WHERE site_id=<<integer site_id>>", ['site_id' => $site_id]);
$new_categories = [];
foreach ($tmp as $tm) {
    $new_categories[$tm['photo_category_path']] = $tm;
}
// \e::info($new_categories);
// get old categories
$tmp = \e::db_getrows("SELECT * FROM <<tp>>photogalery_rozdil WHERE site_id=<<integer site_id>>", ['site_id' => $site_id]);
$old_categories = [];
foreach ($tmp as $tm) {
    if (!$tm['rozdil2']) {
        $tm['rozdil2'] = \core\fileutils::encode_dir_name($tm['rozdil']);
    }
    $old_categories[$tm['rozdil2']] = $tm;
}




$copyImageFile = function($old_image) use($this_site_info) {
    $newname = 'gallery/' . dirname($old_image) . '/prf-' . basename($old_image);
    if (file_exists("{$this_site_info['site_root_dir']}/gallery/{$old_image}")) {
        copy("{$this_site_info['site_root_dir']}/gallery/{$old_image}", "{$this_site_info['site_root_dir']}/{$newname}");
    }
    return $newname;
};

$html = "";
// copy old categories to new
foreach ($old_categories as $path => $cat) {
    if (!isset($new_categories[$path])) {


        $html .= "<div>Creating category $path</div>";

        // copy files
        $old_tn_image = $cat['photos_m'];
        $old_fl_image = $cat['photos'];
        $photo_category_icon = [];
        if ($old_tn_image || $old_fl_image) {
            $photo_category_icon = [
                'small' => $copyImageFile($old_tn_image),
                'full' => $copyImageFile($old_fl_image)
            ];
        }
        // copy DB record
        \e::db_execute(
                " INSERT INTO <<tp>>photo_category(
                        site_id,
                        photo_category_path,
                        photo_category_ordering,
                        photo_category_title,
                        photo_category_description,
                        photo_category_icon,
                        photo_category_code
                    )
                    VALUES(
                        <<integer site_id>>,
                        <<string photo_category_path>>,
                        <<integer photo_category_ordering>>,
                        <<string photo_category_title>>,
                        <<string photo_category_description>>,
                        <<string photo_category_icon>>,
                        <<string photo_category_code>>
                    )
                ", [
            'site_id' => $site_id,
            'photo_category_path' => $cat['rozdil2'],
            'photo_category_ordering' => $cat['weight'],
            'photo_category_title' => preg_replace("/.*\\//", "", $cat['rozdil']),
            'photo_category_description' => $cat['description'],
            'photo_category_icon' => json_encode($photo_category_icon),
            'photo_category_code' => preg_replace("/.*\\//", "", $cat['rozdil2']),
        ]);
    }
}

$html .= "<hr>";

// get images from new gallery
$tmp = \e::db_getrows("SELECT * FROM <<tp>>photo WHERE site_id=<<integer site_id>>", ['site_id' => $site_id]);
$newimages = [];
foreach ($tmp as $tm) {
    $tm['photo_imgfile'] = json_decode($tm['photo_imgfile'], true);
    $newimages[$tm['photo_imgfile']['full']] = $tm;
}
// \e::info($newimages);
// get images from old gallery
$oldimages = \e::db_getrows("SELECT * FROM <<tp>>photogalery WHERE site=<<integer site_id>>", ['site_id' => $site_id]);
// \e::info($tmp);;
foreach ($oldimages as $tm) {
    $imgfile = 'gallery/' . dirname($tm['photos']) . '/prf-' . basename($tm['photos']);
    $oldimages[$imgfile] = $tm;
    if (!isset($newimages[$imgfile])) {

        $html .= "<div>Creating image $imgfile</div>";

        $path = $tm['rozdil2'];
        if (!$path) {
            $path = \core\fileutils::encode_dir_name($tm['rozdil']);
        }

        // image in new gallery does not exists
        $photo_imgfile = [
            'small' => $copyImageFile($tm['photos_m']),
            'full' => $copyImageFile($tm['photos'])
        ];
        // copy DB record
        $sql = "INSERT INTO <<tp>>photo(
                 photo_category_id,
                 site_id,
                 photo_visible,
                 photo_title,
                 photo_author,
                 photo_description,
                 photo_year,
                 photo_imgfile
              ) VALUES(
                <<integer photo_category_id>>,
                <<integer site_id>>,
                <<integer photo_visible>>,
                <<string photo_title>>,
                <<string photo_author>>,
                <<string photo_description>>,
                <<integer photo_year>>,
                <<string photo_imgfile>>
              )";
        \e::db_execute($sql, [
            'photo_category_id' => ( isset( $new_categories[$path] ) ? $new_categories[$path]['photo_category_id'] : 0),
            'site_id' => $site_id,
            'photo_visible' => ( $tm['vis'] ? 1 : 0),
            'photo_title' => $tm['pidpys'],
            'photo_author' => $tm['autor'],
            'photo_description' => $tm['description'],
            'photo_year' => $tm['rik'],
            'photo_imgfile' => json_encode($photo_imgfile)
        ]);
    }
}






$input_vars['page_header'] = $input_vars['page_title'] = text('photo_import');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = text('Site') . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

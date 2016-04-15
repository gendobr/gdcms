<?php

// get list ordering by path
run('photo/functions');


$photo_category_info = photo_category_info(\e::cast('integer', \e::request('photo_category_id', 0)));
if (!$photo_category_info) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('photo_category_not_found');
    return 0;
}
$photo_category_id = $photo_category_info['photo_category_id'];
// \e::info($photo_category_info);
// -------------- get site info - begin ----------------------------------------
run('site/menu');
$site_id = $photo_category_info['site_id'];
$this_site_info = get_site_info($site_id);
// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
// -------------- get site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------

$list_of_languages = list_of_languages();



// ---------------- delete icons - begin ---------------------------------------
if ($photo_category_info['photo_category_icon'] && is_array($photo_category_info['photo_category_icon'])) {
    foreach ($photo_category_info['photo_category_icon'] as $pt) {
        $pt = trim($pt);
        if (strlen($pt) > 0) {
            $path = realpath("{$this_site_info['site_root_dir']}/{$pt}");
            if ($path && strncmp($path, $this_site_info['site_root_dir'], strlen($this_site_info['site_root_dir'])) == 0) {
                unlink($path);
            }
        }
    }
}
// ---------------- delete icons - end -----------------------------------------


$children = \e::db_getrows(
                "SELECT * FROM <<tp>>photo_category 
    WHERE photo_category_path LIKE '" . \e::db_escape($photo_category_info['photo_category_path']) . "/%'  
    AND site_id=<<integer site_id>>", [ 'site_id' => $site_id]);

if (\e::request('delete_children')) {
    // ---------------- children - begin ---------------------------------------

    foreach ($children as $child) {
        // ---------------- delete icons - begin -------------------------------
        if ($child['photo_category_icon'] && $child['photo_category_icon'] = json_decode($child['photo_category_icon'], true)) {
            foreach ($child['photo_category_icon'] as $pt) {
                $pt = trim($pt);
                if (strlen($pt) > 0) {
                    $path = realpath("{$this_site_info['site_root_dir']}/{$pt}");
                    if ($path && strncmp($path, $this_site_info['site_root_dir'], strlen($this_site_info['site_root_dir'])) == 0) {
                        unlink($path);
                    }
                }
            }
        }
        // ---------------- delete icons - end ---------------------------------
        \e::db_execute(
                "DELETE FROM <<tp>>photo_category
         WHERE photo_category_id=<<integer photo_category_id>>", [ 'photo_category_id' => $child['photo_category_id']]);
        // TODO process photo category images
    }
} else {
    // attach children to parent category
    $old_path_length = strlen($photo_category_info['photo_category_path']);
    foreach ($children as $child) {
        $cat_new_path = $photo_category_info['category_parent'] . substr($child['photo_category_path'], $old_path_length);

        \e::db_execute(
        "UPDATE <<tp>>photo_category
         SET photo_category_path=<<string photo_category_path>>
         WHERE photo_category_id=<<integer photo_category_id>>", 
         [ 'photo_category_id' => $child['photo_category_id'],
           'photo_category_path' => $cat_new_path ]
        );
    }
}


\e::db_execute(
        "DELETE FROM <<tp>>photo_category
     WHERE photo_category_id=<<integer photo_category_id>>", [ 'photo_category_id' => $photo_category_id]);
// TODO process photo category images
// ---------------- delete previous - end --------------------------------------
// redirect to editor page
\e::redirect(\e::url(['action' => 'photo/photo_category_list', 'site_id' => $photo_category_info['site_id']]));


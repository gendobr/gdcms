<?php

class GalleryCategory {

    // input data
            protected $lang, $this_site_info, $rozdilizformy, $start, $category_details_url_template, $keywords;
    // parameters
    private $rowsPerPage = 10;
    private $orderBy = 'rozdil ASC, id ASC';
    // mapping of the sortable_columns
    private $sortable_columns = Array(
        'date' => 'id',
        'id' => 'id',
        'category' => 'rozdil',
        'rozdil' => 'rozdil',
        'tag' => 'rozdil',
        'title' => 'rozdil',
        'default' => 'weight',
        'weight' => 'weight'
    );
    // output data
            protected $_list, $_pages, $_items_found, $_this_category_info, $breadcrumbs;

    function __construct($lang, $this_site_info, $start, $rozdilizformy, $keywords, $category_details_url_template = url_pattern_gallery_category) {
        $this->lang = $lang;
        $this->this_site_info = $this_site_info;
        $this->start = (int) $start;
        $this->rozdilizformy = $rozdilizformy;
        $this->category_details_url_template = $category_details_url_template;
        $this->keywords = $keywords;

        $this->image_url_prefix = preg_replace("/\\/+$/", '', $this->this_site_info['url']) . '/gallery';
        
        $this->category_info=$this->get_category_info();
    }

    function __get($attr) {
        // hack to set rowsPerPage parameter
        if (substr($attr, 0, 12) == 'rowsPerPage_') {
            $opt = explode('_', $attr);
            $this->rowsPerPage = (int) $opt[1];
            if ($this->rowsPerPage <= 0) {
                $this->rowsPerPage = 10;
            }
            return null;
        }
        // hack to set orderBy parameter
        if (substr($attr, 0, 8) == 'orderBy_') {
            $opt = explode('_', $attr);
            $cnt = count($opt);
            $ordering = Array();
            for ($i = 1; $i < $cnt; $i+=2) {
                if (isset($this->sortable_columns[$opt[$i]])) {
                    $ordering[] = $this->sortable_columns[$opt[$i]] . ' ' . (strtolower($opt[$i + 1] == 'desc' ? 'desc' : 'asc'));
                }
            }
            if (count($ordering) > 0) {
                $this->orderBy = join(', ', $ordering);
            }
            return null;
        }


        switch ($attr) {

            case 'breadcrumbs':
                if (!isset($this->breadcrumbs)) {
                    $this->breadcrumbs = $this->gallery_breadcrumbs();
                }
                return $this->breadcrumbs;
                break;
            case 'category':
            case 'info':
                if (!isset($this->_list)) {
                    $this->_this_category_info = $this->get_category_info();
                }
                // prn($this->_this_category_info);
                return $this->_this_category_info;
                break;
            case 'children':
                if (!isset($this->_list)) {
                    $this->_list = $this->get_list();
                }
                return $this->_list;
                break;
            case 'items_found':
                if (!isset($this->_list)) {
                    $this->_list = $this->get_list();
                }
                return $this->_items_found;
                break;
            case 'paging_links':
                if (!isset($this->_list)) {
                    $this->_list = $this->get_list();
                }
                return $this->paging_links;
                break;
            default: return Array();
        }
    }

    private function get_category_info() {
        $url_details_pattern = str_replace(Array('{site_id}', '{lang}', '{start}', '{keywords}'), Array($this->this_site_info['id'], $this->lang, 0, ''), $this->category_details_url_template);

        //$query = "SELECT * FROM {$GLOBALS['table_prefix']}photogalery_rozdil WHERE rozdil='" . DbStr($this->rozdilizformy) . "'";
        $query = "SELECT * FROM {$GLOBALS['table_prefix']}photogalery_rozdil 
                  WHERE rozdil='" . DbStr($this->rozdilizformy) . "' 
                     OR rozdil2='" . DbStr($this->rozdilizformy) . "'";
        $this_category_info = db_getonerow($query);
        if ($this_category_info) {
            $this_category_info['url_details'] = str_replace(
                    Array('{rozdilizformy}','{rozdil2}'), 
                    Array(rawurlencode($this_category_info['rozdil']),rawurlencode($this_category_info['rozdil2'])),
                    $url_details_pattern);
            $this_category_info['url_thumbnail'] = $this->image_url_prefix . '/' . $this_category_info['photos_m'];
            $this_category_info['url_image'] = $this->image_url_prefix . '/' . $this_category_info['photos'];
            $this_category_info['name'] = preg_replace("/^.*\\//", '', $this_category_info['rozdil']);
            //prn($this_category_info);
        }
        return $this_category_info;
    }

    private function gallery_breadcrumbs() {

        //$rozdilizformy = $this->rozdilizformy;
        $rozdilizformy = $this->category_info['rozdil'];
        $site_id = $this->this_site_info['id'];
        $lang = $this->lang;
        $keywords = $this->keywords;
        // link to root category
        $breadcrumbs[] = Array(
            'innerHTML' => text('Image_gallery'),
            'url' => str_replace(
                    Array('{rozdil2}','{rozdilizformy}', '{site_id}', '{lang}', '{start}', '{keywords}'), 
                    Array(''         ,''               , $site_id   , $lang   , 0        , ''),
                    $this->category_details_url_template)
        );

        if ($keywords) {
            $breadcrumbs[] = Array(
                'innerHTML' => $keywords,
                'url' => ''
            );
            return $breadcrumbs;
        }

        $n = count(explode('/', $rozdilizformy)) + 1;
        $path = explode('/', $rozdilizformy);
        $r = 0;
        $re1 = '';

        while ($r < $n - 1) {
            if ($r > 0){
                $re1.="/" . $path[$r]; 
            } else{
                $re1.=$path[$r];
            }
            if ($r == $n - 2) {
                // $vyvid.= " /{$path[$r]}";
                $breadcrumbs[] = Array(
                    'innerHTML' => $path[$r],
                    'url' => ''
                );
            } else {
                $breadcrumbs[] = Array(
                    'innerHTML' => $path[$r],
                    'url' => str_replace(
                            Array('{rozdil2}','{rozdilizformy}', '{site_id}', '{lang}', '{start}', '{keywords}'),
                            Array(encode_dir_name($re1),rawurlencode($re1), $site_id, $lang, 0, $keywords), $this->category_details_url_template)
                );
            }
            $r = $r + 1;
        }

        return $breadcrumbs;
    }

    private function get_list() {
        $this_site_info = $this->this_site_info;
        $lang = $this->lang;
        //$rozdilizformy = $this->rozdilizformy;
        $rozdilizformy = $this->category_info['rozdil'];
        //prn($rozdilizformy);
        if ($rozdilizformy) {
            $n = count(explode('/', $rozdilizformy)) + 1;
            $query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
                pr.rozdil `dirname`,
                p.photos_m as icon,
                p.photos as image,
                p.pidpys as main_image_title,
                pr.weight,
                count(p.photos_m) as n_images
            FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr,
                 {$GLOBALS['table_prefix']}photogalery p
            WHERE pr.site_id = {$this_site_info['id']}
              AND p.vis
              AND p.site = {$this_site_info['id']}
              AND SUBSTRING_INDEX( pr.rozdil, '/', ".($n-1)." ) ='" . DbStr($rozdilizformy) . "'
              AND SUBSTRING_INDEX( pr.rozdil, '/', $n )=pr.rozdil
              AND (pr.rozdil=p.rozdil OR pr.rozdil=SUBSTRING_INDEX( p.rozdil, '/', $n ))
            GROUP BY `dirname`
            ORDER BY pr.weight, pr.rozdil
            LIMIT {$this->start},{$this->rowsPerPage}";
            // prn($query);
            $categories = db_getrows($query);
            // prn($categories);
            for($i=0, $cnt=count($categories); $i<$cnt; $i++){
                if($categories[$i]['dirname']==$rozdilizformy){
                    unset($categories[$i]);
                }
            }
            $categories=array_values($categories);
        } else {
            $query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
                pr.rozdil `dirname`,
                p.photos_m as icon,
                p.photos as image,
                p.pidpys as main_image_title,
                pr.weight,
                count(p.photos_m) as n_images
            FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr,
                 {$GLOBALS['table_prefix']}photogalery p
            WHERE pr.site_id = {$this_site_info['id']}
              AND LOCATE('/',pr.rozdil) = 0
              AND p.vis
              AND p.site = {$this_site_info['id']}
              AND (pr.rozdil=p.rozdil OR pr.rozdil=SUBSTRING_INDEX( p.rozdil, '/', 1 ))
            GROUP BY `dirname`
            ORDER BY pr.weight, pr.rozdil
            LIMIT {$this->start},{$this->rowsPerPage};";
            // prn($query);
            $categories = db_getrows($query);
        }
        $n_records = db_getonerow("SELECT FOUND_ROWS() AS n_records;");
        $this->_items_found = (int) $n_records['n_records'];
        // prn($n_records);
        // -------------------- category icons - begin -------------------------
        $rozdil_images_list = db_getrows(
                "SELECT pr.id,p.photos,p.photos_m,pr.rozdil, pr.site_id, pr.image_id, pr.rozdil2
         FROM {$GLOBALS['table_prefix']}photogalery_rozdil as pr
             ,{$GLOBALS['table_prefix']}photogalery as p
         WHERE pr.site_id = {$this_site_info['id']}
           AND p.site = {$this_site_info['id']}
           AND p.id=pr.image_id
         ");
        // prn($rozdil_images_list);
        $rozdil_images = Array();
        foreach ($rozdil_images_list as $rozdil_image) {
            $rozdil_images[$rozdil_image['rozdil']] = $rozdil_image;
        }
        unset($rozdil_images_list);
        // -------------------- category icons - end ---------------------------
        $url_details_prefix = str_replace(Array('{site_id}', '{lang}', '{start}', '{keywords}'), Array($this_site_info['id'], $lang, 0, ''), $this->category_details_url_template);

        $url_thumbnail_prefix = preg_replace("/\\/+$/", '', $this_site_info['url']) . '/gallery';
        $cnt = count($categories);
        for ($i = 0; $i < $cnt; $i++) {
            //$categories[$i]['url_details'] = $url_details_prefix . rawurlencode($categories[$i]['dirname']);
            $categories[$i]['url_details'] = str_replace(
                    Array('{rozdilizformy}','{rozdil2}'), 
                    Array(rawurlencode($categories[$i]['dirname']),  encode_dir_name($categories[$i]['dirname'])), 
                    $url_details_prefix);

            $categories[$i]['url_thumbnail'] = $url_thumbnail_prefix . '/' . $categories[$i]['icon'];
            $categories[$i]['url_image'] = $url_thumbnail_prefix . '/' . $categories[$i]['image'];
            $categories[$i]['name'] = preg_replace("/^.*\\//", '', $categories[$i]['dirname']);

            if (isset($rozdil_images[$categories[$i]['dirname']])) {
                $categories[$i]['url_thumbnail'] = $url_thumbnail_prefix . '/' . $rozdil_images[$categories[$i]['dirname']]['photos_m'];
            }
            // prn($categories[$i]['dirname']);
        }


        $url_template = str_replace(
                Array('{site_id}'          , '{lang}', '{keywords}','{rozdilizformy}','{rozdil2}'),
                Array($this_site_info['id'], $lang   , ''          ,rawurlencode($this->category_info['rozdil']), encode_dir_name($this->category_info['rozdil'])), $this->category_details_url_template);
        $this->paging_links = get_paging_links($this->start, $this->_items_found, $this->rowsPerPage, $url_template);

        return $categories;
    }

}

// -----------------------------------------------------------------------------
function gallery_get_children_of($this_site_info, $lang, $rozdilizformy = false) {



    if ($rozdilizformy) {
        $n = count(explode('/', $rozdilizformy)) + 1;
        //        $query = "SELECT DISTINCT
        //                pr.rozdil `dirname`,
        //                p.photos_m as icon,
        //                p.photos as image,
        //                p.pidpys as main_image_title,
        //                pr.weight,
        //                count(p.photos_m) as n_images
        //            FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr,
        //                 {$GLOBALS['table_prefix']}photogalery p
        //            WHERE pr.site_id = {$this_site_info['id']}
        //              AND p.vis
        //              AND p.site = {$this_site_info['id']}
        //              AND pr.rozdil LIKE '" . DbStr($rozdilizformy) . "/%'
        //              AND (pr.rozdil=p.rozdil OR pr.rozdil=SUBSTRING_INDEX( p.rozdil, '/', $n ))
        //            GROUP BY `dirname`
        //            ORDER BY pr.weight, pr.rozdil";
        $query = "SELECT DISTINCT
                pr.rozdil `dirname`,
                p.photos_m as icon,
                p.photos as image,
                p.pidpys as main_image_title,
                pr.weight,
                count(p.photos_m) as n_images
            FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr,
                 {$GLOBALS['table_prefix']}photogalery p
            WHERE pr.site_id = {$this_site_info['id']}
              AND p.vis
              AND p.site = {$this_site_info['id']}
              AND SUBSTRING_INDEX( pr.rozdil, '/', ".($n-1)." ) ='" . DbStr($rozdilizformy) . "'
              AND SUBSTRING_INDEX( pr.rozdil, '/', $n )=pr.rozdil
              AND (pr.rozdil=p.rozdil OR pr.rozdil=SUBSTRING_INDEX( p.rozdil, '/', $n ))
            GROUP BY `dirname`
            ORDER BY pr.weight, pr.rozdil";
        $categories = db_getrows($query);
    } else {
        $query = "SELECT DISTINCT
            pr.rozdil `dirname`,
            p.photos_m as icon,
            p.photos as image,
            p.pidpys as main_image_title,
            pr.weight,
            count(p.photos_m) as n_images
        FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr,
             {$GLOBALS['table_prefix']}photogalery p
        WHERE pr.site_id = {$this_site_info['id']}
          AND LOCATE('/',pr.rozdil) = 0
          AND p.vis
          AND p.site = {$this_site_info['id']}
          AND (pr.rozdil=p.rozdil OR pr.rozdil=SUBSTRING_INDEX( p.rozdil, '/', 1 ))
        GROUP BY `dirname`
        ORDER BY pr.weight, pr.rozdil;";
        // prn($query);
        $categories = db_getrows($query);
    }
    // -------------------- category icons - begin ---------------------------------
    $rozdil_images_list = db_getrows(
            "SELECT pr.id,p.photos,p.photos_m,pr.rozdil, pr.site_id, pr.image_id, pr.rozdil2
         FROM {$GLOBALS['table_prefix']}photogalery_rozdil as pr
             ,{$GLOBALS['table_prefix']}photogalery as p
         WHERE pr.site_id = {$this_site_info['id']}
           AND p.site = {$this_site_info['id']}
           AND p.id=pr.image_id
         ");
    // prn($rozdil_images_list);
    $rozdil_images = Array();
    foreach ($rozdil_images_list as $rozdil_image) {
        $rozdil_images[$rozdil_image['rozdil']] = $rozdil_image;
    }
    unset($rozdil_images_list);
    // -------------------- category icons - end -----------------------------------
    $url_details_prefix = str_replace(
            Array('{site_id}', '{lang}', '{start}', '{keywords}'), 
            Array($this_site_info['id'], $lang, 0, ''), 
            url_pattern_gallery_category);

    $url_thumbnail_prefix = preg_replace("/\\/+$/", '', $this_site_info['url']) . '/gallery';
    $cnt = count($categories);
    for ($i = 0; $i < $cnt; $i++) {
        //$categories[$i]['url_details'] = $url_details_prefix . rawurlencode($categories[$i]['dirname']);
        $categories[$i]['url_details'] = str_replace(
                Array('{rozdilizformy}','{rozdil2}'), 
                Array(rawurlencode($categories[$i]['dirname']),  encode_dir_name($categories[$i]['dirname'])), 
                $url_details_prefix);

        $categories[$i]['url_thumbnail'] = $url_thumbnail_prefix . '/' . $categories[$i]['icon'];
        $categories[$i]['url_image'] = $url_thumbnail_prefix . '/' . $categories[$i]['image'];
        $categories[$i]['name'] = preg_replace("/^.*\\//", '', $categories[$i]['dirname']);

        if (isset($rozdil_images[$categories[$i]['dirname']])) {
            $categories[$i]['url_thumbnail'] = $url_thumbnail_prefix . '/' . $rozdil_images[$categories[$i]['dirname']]['photos_m'];
        }
        // prn($categories[$i]['dirname']);
    }
    return $categories;
}

function gallery_breadcrumbs($rozdilizformy, $site_id, $lang, $keywords) {

    // link to root category
    $breadcrumbs[] = Array(
        'innerHTML' => text('Image_gallery'),
        'url' => str_replace(
                Array('{rozdil2}','{rozdilizformy}', '{site_id}', '{lang}', '{start}', '{keywords}'), 
                Array('','', $site_id, $lang, 0, ''), 
                url_pattern_gallery_category)
    );

    if ($keywords) {
        $breadcrumbs[] = Array(
            'innerHTML' => $keywords,
            'url' => ''
        );
        return $breadcrumbs;
    }

    $n = count(explode('/', $rozdilizformy)) + 1;
    $path = explode('/', $rozdilizformy);
    $r = 0;
    $re1 = '';

    while ($r < $n - 1) {
        if ($r > 0)
            $re1.="/" . $path[$r]; else
            $re1.=$path[$r];
        if ($r == $n - 2) {
            // $vyvid.= " /{$path[$r]}";
            $breadcrumbs[] = Array(
                'innerHTML' => $path[$r],
                'url' => ''
            );
        } else {
            $breadcrumbs[] = Array(
                'innerHTML' => $path[$r],
                'url' => str_replace(
                        Array('{rozdil2}','{rozdilizformy}', '{site_id}', '{lang}', '{start}', '{keywords}'), 
                        Array(encode_dir_name($re1),rawurlencode($re1), $site_id, $lang, 0, $keywords), 
                        url_pattern_gallery_category)
            );
        }
        $r = $r + 1;
    }

    return $breadcrumbs;
}

function gallery_get_all_categories($site_id, $parent = false) {
    $rozdil_list_tmp = db_getrows(
            "SELECT DISTINCT rozdil, rozdil2
             FROM {$GLOBALS['table_prefix']}photogalery
             WHERE site = {$site_id}
             ORDER BY rozdil");
    //prn($rozdil_list_tmp);
    $rozdil_list = Array();
    foreach ($rozdil_list_tmp as $rozdil) {
        $r = $rozdil['rozdil'];
        $i = 0;
        while (strlen($r) > 0 && $r != '.' && ($i++) < 100) {
            $rozdil_list[$r] = false;
            $r = dirname($r);
        }
    }
    //prn($rozdil_list);
    ksort($rozdil_list, SORT_STRING);
    $rozdil_list = array_keys($rozdil_list);
    //prn($rozdil_list);
    if ($parent === false) {
        return $rozdil_list;
    }
    $cnt = count($rozdil_list);
    // prn($cnt,$rozdil_list);
    $prefix = $parent . '/';
    $prefix_length = strlen($prefix);
    for ($i = 0; $i < $cnt; $i++) {
        $rozdil_list[$i] = '/' . $rozdil_list[$i];
        //prn($rozdil_list[$i], substr($rozdil_list[$i], 0, $prefix_length),$prefix );
        if (substr($rozdil_list[$i], 0, $prefix_length) != $prefix) {
            unset($rozdil_list[$i]);
            continue;
        }
        //prn(1);
        if (substr_count(substr($rozdil_list[$i], $prefix_length), '/') > 0) {
            unset($rozdil_list[$i]);
            continue;
        }
        //prn();
    }
    return array_values($rozdil_list);
}

function gallery_synchronize_categories($site_id) {
    // get all existing categories
    $existing_categories = gallery_get_all_categories($site_id);
    // prn('$existing_categories=',$existing_categories);//###
    // get categories from photogalery_rozdil
    $photogalery_rozdil_list = db_getrows(
            "SELECT *
             FROM {$GLOBALS['table_prefix']}photogalery_rozdil
             WHERE site_id = {$site_id}
             ORDER BY rozdil");
    // prn($photogalery_rozdil_list);//###
    // check if some rows should be deleted or added
    $cnt = count($photogalery_rozdil_list);
    // prn('$cnt='.$cnt);
    for ($i = 0; $i < $cnt; $i++) {
        $key = array_search($photogalery_rozdil_list[$i]['rozdil'], $existing_categories);
        // prn('$key='.$key);
        if ($key === false) {
            continue;
        }
        unset($photogalery_rozdil_list[$i]);
        unset($existing_categories[$key]);
    }

    $photogalery_rozdil_list = array_values($photogalery_rozdil_list);
    $existing_categories = array_values($existing_categories);
    // prn($photogalery_rozdil_list,$existing_categories); exit();
    //    // delete categories
    //    $deletable = Array();
    //    $cnt = count($photogalery_rozdil_list);
    //    for ($i = 0; $i < $cnt; $i++) {
    //        $deletable[] = $photogalery_rozdil_list[$i]['id'];
    //    }
    //    if (count($deletable) > 0) {
    //        $query = "DELETE FROM {$GLOBALS['table_prefix']}photogalery_rozdil WHERE id IN(" . join(',', $deletable) . ")";
    //        db_execute($query);
    //    }
    // add categories
    $cnt = count($existing_categories);
    $new = Array();
    for ($i = 0; $i < $cnt; $i++) {
        $new[] = "({$site_id},'" . DbStr($existing_categories[$i]) . "','" . DbStr(encode_dir_name($existing_categories[$i])) . "')";
    }
    if (count($new) > 0) {
        $query = "INSERT INTO {$GLOBALS['table_prefix']}photogalery_rozdil(site_id,rozdil,rozdil2) VALUES " . join(',', $new) . "";
        // prn($query);
        db_execute($query);
    }
}

?>
<?php

$data = date("Y-m-d H:i");


#prn($_SESSION);
//---------------------- load language - begin ---------------------------------
if (isset($input_vars['interface_lang']) && strlen($input_vars['interface_lang']) > 0) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = $_SESSION['lang'];
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = $_SESSION['lang'];
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = \e::config('default_language');
}
$lang = $input_vars['lang'] = get_language('lang');

$txt = load_msg($input_vars['lang']);
//---------------------- load language - end -----------------------------------

run('site/menu');
//------------------- site info - begin ----------------------------------------
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id, isset($lang) ? $lang : '');
if (checkInt($this_site_info['id']) <= 0) {
    die($txt['Gallery_not_found']);
}
//------------------- site info - end ------------------------------------------
//--------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, 'template_index.html', $verbose = false);
if ($custom_page_template) {
    $this_site_info['template'] = $custom_page_template;
}
//--------------------------- get site template - end --------------------------

run('gallery/category_model');

$vyvid = '';


$breadcrumbs = Array();

# --------------------------- list of gallery categories - begin ---------------
// current category
$rozdilizformy = (isset($input_vars['rozdilizformy'])) ? $input_vars['rozdilizformy'] : '';

$rozdil2=(isset($input_vars['rozdil2'])) ? $input_vars['rozdil2'] : '';
if($rozdil2){
    //prn($rozdil2);
    // header("Debug1: $rozdil2");
    $query="SELECT * FROM <<tp>>photogalery_rozdil WHERE rozdil2='".\e::db_escape($rozdil2)."'";
    // header("Debug2: {$query}");
    $rozdilizformy= \e::db_getonerow($query);
    $rozdilizformy=$rozdilizformy['rozdil'];
    // header("Debug2: {$rozdilizformy['rozdil']}");
    //prn($rozdilizformy);
}


if ($rozdilizformy != '') {
    // if current category is set
    //
    $breadcrumbs = gallery_breadcrumbs($rozdilizformy, $site_id, $lang,'');
    ////$categories = gallery_get_children_of($this_site_info, $lang, $rozdilizformy);
    //prn($categories);

    $url_details_prefix = str_replace(Array('{site_id}', '{lang}', '{start}','{keywords}'), Array($this_site_info['id'], $lang, 0,''), \e::config('url_pattern_gallery_category'));
    $url_thumbnail_prefix = preg_replace("/\\/+$/", '', $this_site_info['url']) . '/gallery';
    $query="SELECT * FROM <<tp>>photogalery_rozdil WHERE rozdil='".  \e::db_escape($rozdilizformy)."' OR rozdil='" . \e::db_escape(rawurldecode($rozdilizformy)) . "'";
    $this_category_info=\e::db_getonerow($query);
    $this_category_info['url_details'] = str_replace(
            Array('{rozdilizformy}','{rozdil2}'),
            Array(rawurlencode($this_category_info['rozdil']),  \core\fileutils::encode_dir_name($this_category_info['rozdil'])),
            $url_details_prefix);
    $this_category_info['url_thumbnail'] = $url_thumbnail_prefix . '/' . $this_category_info['photos_m'];
    $this_category_info['url_image']     = $url_thumbnail_prefix . '/' . $this_category_info['photos'];
    $this_category_info['name'] = preg_replace("/^.*\\//", '', $this_category_info['rozdil']);
    //prn($this_category_info);
} else {
    ////$categories = gallery_get_children_of($this_site_info, $lang);
    $this_category_info=false;
    //prn($categories);
}


# --------------------------- list of categories - end -------------------------
// ---------------------- get list of images - begin ---------------------------
$keywords = isset($input_vars['keywords']) ? trim($input_vars['keywords']) : '';

// -------------------- get gallery images - lazy list - begin -----------------
run('gallery/gallery_images');
// -------------------- get gallery images - lazy list - end -------------------

if (strlen($keywords) > 0) {
    $breadcrumbs = $breadcrumbs = gallery_breadcrumbs('', $site_id, $lang,$keywords);
    ////$categories = Array();
}
// ---------------------- get list of images - end -----------------------------


run('site/page/page_view_functions');

$images=new GalleryImages($lang, $this_site_info, isset($input_vars['start']) ? ( (int) $input_vars['start'] ) : 0, $rozdilizformy, $keywords);
$category=new GalleryCategory($lang, $this_site_info, isset($input_vars['start']) ? ( (int) $input_vars['start'] ) : 0, $rozdilizformy, $keywords, \e::config('url_pattern_gallery_category'));
// ---------------------- draw gallery - begin ---------------------------------
$_template = site_get_template($this_site_info, 'template_photogallery');
$vyvid.= process_template($_template, Array(
      'images' => $images
    , 'category'=>$category
    //    , 'categories' => $categories
    //    , 'breadcrumbs' => $breadcrumbs
    //    , 'current_category'=>$this_category_info
    , 'text' => $txt
    , 'site' => $this_site_info
    , 'lang' => $lang
    , 'cms_root_url' => site_root_URL
    , 'keywords' => htmlspecialchars($keywords)
        )
);
//prn($category);


$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);
//prn($menu_groups);
// ---------------- mark active menu items - begin -----------------------------
$rozdilizformy_pattern = str_replace('/', "\\/", preg_quote(str_replace(Array('{site_id}', '{lang}','{keywords}','{start}'), Array($site_id, $lang,'','0'), \e::config('url_pattern_gallery_category'))));
$rozdilizformy_pattern = '/' . str_replace("\\{rozdilizformy\\}", '([^&]*)', $rozdilizformy_pattern) . '/';
//prn('$rozdilizformy_pattern',$rozdilizformy_pattern);
foreach ($menu_groups as $kmg => $mg) {
    foreach ($mg['items'] as $kmi => $mi) {
        $matches = Array();
        //prn($mi['url']);
        if (preg_match($rozdilizformy_pattern, $mi['url'], $matches)) {
            //prn($matches);
            $rozdilizformy_etalon = rawurldecode($matches[1]);
            //prn($rozdilizformy_etalon);
            $len_etalon = strlen($rozdilizformy_etalon);
            //prn("$len_etalon = strlen($rozdilizformy_etalon);");

            $len = strlen($rozdilizformy);
            //prn("$len = strlen($rozdilizformy);");
            if ($len >= $len_etalon
                    && $rozdilizformy_etalon == substr($rozdilizformy, 0, $len_etalon)) {
                $menu_groups[$kmg]['items'][$kmi]['disabled'] = 1;
                //prn($mi['url'],'disabled');
            }
        }
    }
}
// ---------------- mark active menu items - end -------------------------------
//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
   $lang_list[$i]['href'] =
   $lang_list[$i]['url'] = str_replace(
       Array('{site_id}', '{lang}', '{start}', '{keywords}','{rozdilizformy}'), 
       Array($this_site_info['id'], $lang_list[$i]['name'], 0, '',$category->category_info['rozdil']), 
       \e::config('url_pattern_gallery_category'));

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
// prn($lang_list);
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------

$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array(
          'title' => ((count($breadcrumbs)>0)?$breadcrumbs[count($breadcrumbs)-1]['innerHTML']:$txt['image_gallery_view'])
        , 'content' => $vyvid
        , 'abstract' => ''
        , 'site_id' => $site_id
        , 'lang' => $input_vars['lang']
        ,'editURL'=>site_URL."?action=gallery/admin/photogalery&site_id={$site_id}"
        )
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name;
$main_template_name = '';


?>

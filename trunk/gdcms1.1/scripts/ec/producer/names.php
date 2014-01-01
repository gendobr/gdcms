<?php

/**
 * Browse ec_item categories
 *   argments are
 *    $category_id - identifier of category
 *
 *
 *
 */
run('ec/item/functions');
run('site/menu');
# -------------------- set interface language - begin ---------------------------
$debug = false;
if (isset($input_vars['interface_lang']))
    if ($input_vars['interface_lang'])
        $input_vars['lang'] = $input_vars['interface_lang'];
if (!isset($input_vars['lang']))
    $input_vars['lang'] = default_language;
if (strlen($input_vars['lang']) == 0)
    $input_vars['lang'] = default_language;
// $lang=$input_vars['lang'];
$lang = get_language('lang');
# -------------------- set interface language - end -----------------------------
# -------------------------- load messages - begin -----------------------------
global $txt;
$txt = load_msg($lang);
# -------------------------- load messages - end -------------------------------
# ------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info)
    die($txt['Site_not_found']);
$this_site_info['title'] = get_langstring($this_site_info['title'], $lang);
//prn($this_site_info);
//prn($input_vars);
# ------------------- get site info - end --------------------------------------
# --------------------------- get site template - begin ------------------------
$custom_page_template = sites_root . '/' . $this_site_info['dir'] . '/template_index.html';
if (is_file($custom_page_template))
    $this_site_info['template'] = $custom_page_template;
# --------------------------- get site template - end --------------------------
# --------------------------- get list of producers - begin --------------------
//$query = "SELECT ec_producer.ec_producer_id,
//                 ec_producer.ec_producer_title,
//                 ec_producer.ec_producer_img,
//                 count(ec_item.ec_item_id) as n_items
//          FROM {$table_prefix}ec_producer as ec_producer,
//               {$table_prefix}ec_item as ec_item
//          WHERE ec_producer.ec_producer_id=ec_item.ec_producer_id
//               AND ec_item.ec_item_lang='{$lang}'
//			   AND ec_item.site_id={$site_id}
//			   AND ec_producer.site_id={$site_id}
//          GROUP BY ec_producer.ec_producer_id
//          ORDER BY ec_producer.ec_producer_title ASC";
$query = "SELECT ec_producer.ec_producer_id,
                 ec_producer.ec_producer_title,
                 ec_producer.ec_producer_img,
                 count(ec_item.ec_item_id) as n_items
          FROM {$table_prefix}ec_producer as ec_producer
               left join {$table_prefix}ec_item as ec_item
               on (
                   ec_producer.ec_producer_id=ec_item.ec_producer_id
                   and ec_item.ec_item_lang='{$lang}'
		   AND ec_item.site_id={$site_id}

               )
          WHERE ec_producer.site_id={$site_id}
          GROUP BY ec_producer.ec_producer_id
          ORDER BY ec_producer.ec_producer_title ASC
  ";
$list_of_producers = db_getrows($query);
//prn($list_of_producers);
# --------------------------- get list of producers - end ----------------------





include(script_root . '/ec/item/get_public_list.php');
include(script_root . '/ec/item/adjust_public_list.php');

//prn($list_of_ec_items);
//prn($pages);
# -------------------- get list of page languages - begin ----------------------
$tmp = db_getrows("SELECT DISTINCT ec_item_lang as lang
                     FROM {$table_prefix}ec_item  AS ec_item
                     WHERE ec_item.site_id={$site_id}
                       AND ec_item.ec_item_cense_level&" . ec_item_show . "");
$existing_languages = Array();
foreach ($tmp as $tm)
    $existing_languages[$tm['lang']] = $tm['lang'];
// prn($existing_languages);


$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    if (!isset($existing_languages[$lang_list[$i]['name']])) {
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url'] = $lang_list[$i]['href'];
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
$lang_list = array_values($lang_list);
//prn($lang_list);
# -------------------- get list of page languages - end ------------------------
//------------------------ draw using SMARTY template - begin ------------------
run('site/page/page_view_functions');

# get site menu
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

# -------------------- search for template - begin ---------------------------
$template_ec_producer_list = site_get_template($this_site_info, 'template_ec_producer_list');
#$template_ec_producer_list = sites_root.'/'.$this_site_info['dir'].'/template_ec_producer_list.html';
#if(!is_file($template_ec_producer_list)) $template_ec_producer_list = 'cms/template_ec_producer_list';
# -------------------- search for template - end -----------------------------


$vyvid =
        process_template($template_ec_producer_list
        , Array(
            'text' => $txt,
            'site' => $this_site_info,
            'producers' => $list_of_producers,
            'url_prefix_producer_details' => "index.php?action=ec/producer/view&lang={$lang}&ec_producer_id="
        )
        )
;

$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array('title' => $txt['EC_item_producers']
        , 'content' => $vyvid
        , 'abstract' => ''
        , 'site_id' => $site_id
        , 'lang' => $input_vars['lang']
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
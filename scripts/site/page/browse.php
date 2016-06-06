<?php

/*
  View pages for site
  arguments are
  $site_id - site identifier, integer, mandatory
  $lang    - interface language, char(3)

  if there is a file news_view_list.html in the site roto directory
  it will be treated as Smarty template and used instead of default one.
 */

$debug = false;


run('site/menu');


# ---------------------- get site info - begin ---------------------------------
$this_site_info = get_site_info(isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0);
if (!$this_site_info) {
    die('Site not found');
}
$site_id = $this_site_info['id'];
# ---------------------- get site info - end -----------------------------------
# 
# load messages
$input_vars['lang'] = $lang = get_language('lang,interface_lang');
$txt = load_msg($input_vars['lang']);



// ====================== get category selector = begin ========================
run('lib/class_tree1');
run('lib/class_tree2');

class page_browse_tree extends browse_tree {

    var $site_id;

    function page_browse_tree($category_id, $site_id) {
        $this->site_id = $site_id;
        parent::browse_tree($category_id, $site_id);
    }

    // --------------------- restrict children - begin --------------------------
    function restrict_children() {
        global $db, $input_vars;
        $child_ids = Array();
        foreach ($this->children as $ke => $ch)
            $child_ids[$ke] = (int) $ch['category_id'];
        if (count($child_ids) > 0) {
            $query = "SELECT pa.category_id
                   FROM <<tp>>category as ch
                       ,<<tp>>category as pa
                       ,<<tp>>page as page
                   WHERE pa.category_id in(" . join(',', $child_ids) . ")
                     AND pa.start<=ch.start AND ch.finish<=pa.finish
                     AND page.category_id = ch.category_id
                     AND page.lang = '" . \e::db_escape($input_vars['lang']) . "'
                     and page.site_id={$this->site_id}";
            $visible_children = \e::db_getrows($query);
            //prn($visible_children);
            $cnt = count($visible_children);
            for ($i = 0; $i < $cnt; $i++)
                $visible_children[$i] = $visible_children[$i]['category_id'];
        }else
            $visible_children=Array();

        $cnt = count($this->children);
        for ($i = 0; $i < $cnt; $i++)
            if (!in_array($this->children[$i]['category_id'], $visible_children))
                unset($this->children[$i]);
        $this->children = array_values($this->children);
    }

    // --------------------- restrict children - end ----------------------------
//    function adjust($_info, $category_id, $url_prefix) {
//        $tor = $_info;
//        $tor['category_title'] = get_langstring($tor['category_title']);
//        $tor['category_description'] = get_langstring($tor['category_description']);
//        $tor['title_short'] = shorten($tor['category_title']);
//        $tor['padding'] = 20 * $tor['deep'];
//        $tor['URL'] = "{$url_prefix}&category_id={$tor['category_id']}";
//        $tor['has_subcategories'] = ($tor['finish'] - $tor['start'] > 1) ? '>>>' : '';
//        return $tor;
//    }

}

$page_browse_tree = new page_browse_tree(isset($input_vars['category_id']) ? ( (int) $input_vars['category_id'] ) : 0, $site_id);
$category_selector = $page_browse_tree->draw();
#  prn($page_browse_tree,$page_browse_tree->draw());
#  echo $category_selector;
// ====================== get category selector = end ==========================
# ----------------------- get page menu - begin --------------------------------
run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);
# ----------------------- get page menu - end ----------------------------------
# -------------------- get list of page languages - begin ----------------------
$tmp = \e::db_getrows(
                "SELECT DISTINCT lang
                  FROM <<tp>>page  AS pg
                  WHERE pg.site_id={$site_id}
                    AND pg.cense_level>={$this_site_info['cense_level']}");
$existing_languages = Array();
foreach ($tmp as $tm) {
    if(!isset($this_site_info['extra_setting']['lang'][$tm['lang']])){
        unset($lang_list[$i]);
        continue;
    }
    $existing_languages[$tm['lang']] = $tm['lang'];
}
#prn($existing_languages);
$lang_list = Array();
foreach ($existing_languages as $lng) {
    $tmp_url = site_root_URL . "/index.php?interface_lang=$lng&" . query_string('lang$');
    $lang_list[] = Array(
        'name' => $lng
        , 'lang' => $lng
        , 'href' => $tmp_url
        , 'url' => $tmp_url
    );
}
$lang_list = array_values($lang_list);
usort ( $lang_list , function($k1, $k2){
    $defaultLang=\e::config('default_language');
    $s1 = ($k1['name'] == $defaultLang?'0':'1').$k1['name'];
    $s2 = ($k2['name'] == $defaultLang?'0':'1').$k2['name'];
    return -strcmp($s2, $s1);
} );
// prn($lang_list);
# -------------------- get list of page languages - end ------------------------
# --------------------------- get list of pages - begin ------------------------
if ($page_browse_tree->info && $page_browse_tree->info['start'] > 0) {
    $category_restriction = "AND pg.category_id={$page_browse_tree->info['category_id']}";
}
else
    $category_restriction='';

if (!isset($input_vars['start']))
    $input_vars['start'] = 0;
$start = abs(round(1 * $input_vars['start']));
$query = "SELECT SQL_CALC_FOUND_ROWS
                   pg.id
                  ,pg.lang
                  ,pg.title
                  ,pg.path
                  ,IF(LENGTH(TRIM(pg.abstract))>0,pg.abstract,pg.content)  AS abstract
                  ,pg.last_change_date
                  ,IF(LENGTH(TRIM(pg.abstract))>0,1,0) as abstract_present
                  ,IF(LENGTH(TRIM(pg.content))>0,1,0) as content_present
                  ,LENGTH(pg.content)/1024 as size
            FROM <<tp>>page AS pg
            WHERE pg.site_id={$site_id}
              AND pg.cense_level>={$this_site_info['cense_level']}
              AND pg.lang='{$_SESSION['lang']}'
              $category_restriction
            ORDER BY pg.last_change_date DESC
            LIMIT $start," . \e::config('rows_per_page');
$list_of_pages = \e::db_getrows($query);
//if($debug)
# prn($query,$list_of_pages);
# -------------------- adjust list - begin ---------------------------------
$cnt = count($list_of_pages);
# prn($this_site_info['site_root_url']);
for ($i = 0; $i < $cnt; $i++) {
    //$list_of_pages[$i]['content_present']
    if ($list_of_pages[$i]['abstract_present'] == 0)
        $list_of_pages[$i]['abstract'] = shorten(strip_tags($list_of_pages[$i]['abstract']), 255);

    $list_of_pages[$i]['url'] = preg_replace("/^\\/+/", '', "{$list_of_pages[$i]['path']}/{$list_of_pages[$i]['id']}.{$list_of_pages[$i]['lang']}.html");
    $list_of_pages[$i]['url'] = $this_site_info['site_root_url'] . '/' . $list_of_pages[$i]['url'];

    $list_of_pages[$i]['size'] = htmlspecialchars(round($list_of_pages[$i]['size'], 2));
}
# prn($query,$list_of_pages);
# -------------------- adjust list - end -----------------------------------
# --------------------------- paging links - begin -------------------------
$query = "SELECT FOUND_ROWS() AS n_records";
$num_rows = \e::db_getonerow($query);
$num_rows = $num_rows['n_records'];
#prn('$num_rows='.$num_rows);
# --------------------------- paging links - end ---------------------------
// --------------------- number of pages - begin ---------------------
$pages = Array();
$i_min = max(0, $start - \e::config('rows_per_page') * \e::config('rows_per_page'));
$i_max = min($num_rows, $start + \e::config('rows_per_page') * \e::config('rows_per_page'));
for ($i = $i_min; $i < $i_max; $i = $i + \e::config('rows_per_page')) {
    if ($i == $start) {
        $pages[] = Array(
            'URL' => ''
            , 'innerHTML' => '<b style="font-size:120%">[' . (1 + $i / \e::config('rows_per_page')) . ']</b>'
        );
    } else {
        $pages[] = Array(
            'URL' => site_root_URL . "/index.php?start={$i}&" . query_string('^start$|^' . session_name() . '$')
            , 'innerHTML' => '[ ' . (1 + $i / \e::config('rows_per_page')) . ' ]'
        );
    }
}
// --------------------- number of pages - end -----------------------
//------------------------ draw using SMARTY template - begin ----------------

$news_template = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'] . '/template_page_browse.html';
#prn('$news_template',$news_template);
if (!is_file($news_template)) $news_template = 'cms/template_page_browse';

#prn('$news_template',$news_template);
$vyvid = $category_selector . ' '
        . process_template($news_template
                , Array(
              'paging_links' => $pages
            , 'text' => $txt
            , 'search_result' => $list_of_pages
            , 'pages_found' => $num_rows
            , 'row_shown_first' => ($start + 1)
            , 'row_shown_last' => ($start + count($list_of_pages))
        ));

$file_content = process_template($this_site_info['template']
                , Array(
            'page' => Array(
                'title' => $txt['Browse_pages']
                , 'content' => $vyvid
                , 'abstract' => ( isset($txt['news_manual']) ? $txt['news_manual'] : '')
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
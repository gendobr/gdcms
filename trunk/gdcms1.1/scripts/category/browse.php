<?php
run('category/functions');
//------------------- site info - begin ----------------------------------------
run('site/menu');

$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
$input_vars['site_id'] = $this_site_info['id'];
//------------------- site info - end ------------------------------------------
// ------------------ get language - begin -------------------------------------
if (isset($input_vars['interface_lang'])) {
    if ($input_vars['interface_lang']) {
        $input_vars['lang'] = $input_vars['interface_lang'];
    }
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
global $txt;
$txt = load_msg($input_vars['lang']);
$input_vars['lang'] = $lang = get_language('lang');
// ------------------ get language - end ---------------------------------------


global $main_template_name;
$main_template_name = '';

// ------------ get category info - begin --------------------------------------
//$where = Array();
//if (isset($input_vars['category_id'])) {
//    $where[0] = 'category_id=' . ( (int) $input_vars['category_id'] );
//}
//if (isset($input_vars['path'])) {
//    $input_vars['path'] = preg_replace("/\\/+$|^\\/+/", '', $input_vars['path']);
//    $where[0] = "path='" . DbStr($input_vars['path']) . "'";
//}
//if (isset($input_vars['category_code'])) {
//    $where[0] = "category_code='" . DbStr($input_vars['category_code']) . "'";
//}
//
//if (count($where) == 0) {
//    $where[0] = 'start=0';
//}
//$where[1] = 'site_id=' . $site_id;
//$where[2] = 'is_visible =1';
//$query = "SELECT * FROM {$table_prefix}category WHERE " . join(' AND ', $where);
////if (isset($_REQUEST['debug'])) {
////    prn($query);
////}
//$this_category_info = db_getonerow($query);
//if (!$this_category_info) {
//    die('Category not found');
//}
//
//$category_id = $this_category_info['category_id'];
//$this_category_info['category_title'] = get_langstring($this_category_info['category_title'], $lang);
//$this_category_info['category_description'] = get_langstring($this_category_info['category_description'], $lang);
//$this_category_info['URL'] = str_replace(
//                Array('{path}'   ,'{lang}','{site_id}','{category_id}','{category_code}'),
//                Array($this_category_info['path'],$lang   ,$site_id   ,$this_category_info['category_id'],$this_category_info['category_code']),
//                url_pattern_category);
//$this_category_info['date_lang_update']=get_langstring($this_category_info['date_lang_update'], $lang);
////prn($this_category_info);
//// ------------ get category info - end ----------------------------------------
//

$this_category_info=category_info($input_vars);
$category_id = $this_category_info['category_id'];
// -------------------- do redirect if needed - begin --------------------------
if (is_valid_url($url = trim($this_category_info['category_description']))) {
    header("Location: {$url}");
    return;
}
// -------------------- do redirect if needed - end ----------------------------



run('lib/file_functions');
// cache info as file in the site dir
$tmp = get_cached_info(sites_root . '/' . $this_site_info['dir'] . "/cache/category_{$category_id}_{$lang}.cache", 0);

if ($tmp) {
    // prn($tmp);
    $this_category_info['children'] = $tmp['children'];
    $this_category_info['parents'] = $tmp['parents'];
} else {
    // ---------------------------- get parents - begin ------------------------
    $query = "select pa.category_id, pa.site_id, pa.category_code, pa.category_title,
                      pa.start, pa.finish, pa.is_deleted, pa.deep, pa.is_part_of,
                      pa.see_also, pa.is_visible, pa.path
               from {$table_prefix}category pa, {$table_prefix}category ch
               WHERE ch.category_id={$category_id} and ch.site_id={$site_id} and pa.site_id={$site_id}
                 and pa.start<ch.start and ch.finish<pa.finish
               order by pa.start asc";
    $this_category_info['parents'] = db_getrows($query);

    // all parents should be visible
    $parents_are_visible = true;
    $cnt = count($this_category_info['parents']);
    for ($i = 0; $i < $cnt; $i++) {
        $pa = &$this_category_info['parents'][$i];
        if ($pa['is_visible'] != 1) {
            $parents_are_visible = false;
            break;
        }
        $pa['category_title'] = get_langstring($pa['category_title'], $lang);
        //$pa['category_description'] = get_langstring($pa['category_description'], $lang);
        $pa['URL'] = str_replace(
                Array('{path}'   ,'{lang}','{site_id}','{category_id}','{category_code}'),
                Array($pa['path'],$lang   ,$site_id   ,$pa['category_id'],$pa['category_code']),
                url_pattern_category);
    }
    if (!$parents_are_visible) {
        die('Category is hidden');
    }
    // echo '<!-- '; prn($this_category_info['parents']); echo ' -->';
    // ---------------------------- get parents - end --------------------------
    // ------------------- get children - begin --------------------------------
    $query = "select ch.category_id, ch.site_id, ch.category_code, ch.category_title,
                      ch.start, ch.finish, ch.is_deleted, ch.deep, ch.is_part_of,
                      ch.see_also, ch.is_visible, ch.path, ch.category_description
               from {$table_prefix}category pa, {$table_prefix}category ch
               WHERE pa.category_id={$category_id} and ch.site_id={$site_id} and ch.is_visible
                 and pa.site_id={$site_id} and ch.deep=" . ($this_category_info['deep'] + 1 ) . "
                 and pa.start<ch.start and ch.finish<pa.finish
               order by ch.start asc";
    //prn(checkStr($query));
    $this_category_info['children'] = db_getrows($query);

    $cnt = count($this_category_info['children']);
    for ($i = 0; $i < $cnt; $i++) {
        $ch = &$this_category_info['children'][$i];
        $ch['category_title'] = get_langstring($ch['category_title'], $lang);
        $ch['category_description'] = get_langstring($ch['category_description'], $lang);
        //prn($ch['category_description']);
        //if(is_valid_url($url=trim($ch['category_description']))){
        // $ch['URL'] = $url;
        //}else{
        $ch['URL'] = str_replace(
                Array('{path}'   ,'{lang}','{site_id}','{category_id}','{category_code}'),
                Array($ch['path'],$lang   ,$site_id   ,$ch['category_id'],$ch['category_code']),
                url_pattern_category);
        //}
    }
    //prn($this_category_info['children']);
    // ------------------- get children - end ----------------------------------
    $tmp = Array('parents' => $this_category_info['parents'], 'children' => $this_category_info['children']);
    set_cached_info(sites_root . '/' . $this_site_info['dir'] . "/cache/category_{$category_id}_{$lang}.cache", $tmp);
}

// get attached news - delayed feature
run('news/menu');
class CategoryNews {

    protected $lang, $this_site_info, $category_info, $start;
    protected $_list, $_pages, $items_found;
    protected $rows_per_page = 10;
    protected $ordering = 'last_change_date DESC';
    protected $startname = 'news_start';

    function __construct($_lang, $_this_site_info, $_category_info, $start) {
        $this->lang = $_lang;
        $this->this_site_info = $_this_site_info;
        $this->category_info = $_category_info;
        $this->start = $start;
    }

    function __get($attr) {
        if (!isset($this->_list)) {
            $this->init();
        }
        switch ($attr) {
            case 'list':
                return $this->_list;
                break;
            case 'pages':
                return $this->_pages;
                break;
            case 'items_found':
                return $this->items_found;
                break;
            case 'start':
                return $this->start + 1;
                break;
            case 'finish':
                return min($this->start + $this->rows_per_page, $this->items_found);
                break;
            default: return Array();
        }
    }

    private function init() {
        $site_id = $this->this_site_info['id'];
        $category_id = $this->category_info['category_id'];

        // get all the visible children
        $query = "SELECT ch.category_id, BIT_AND(pa.is_visible) as visible
            FROM {$GLOBALS['table_prefix']}category ch, {$GLOBALS['table_prefix']}category pa
            WHERE pa.start<=ch.start AND ch.finish<=pa.finish
              AND {$this->category_info['start']}<=ch.start AND ch.finish<={$this->category_info['finish']}
              AND pa.site_id=$site_id and ch.site_id=$site_id
            GROUP BY ch.category_id
            HAVING visible
        ";
        // prn($query);
        $children = db_getrows($query);
        $cnt = count($children);
        for ($i = 0; $i < $cnt; $i++) {
            $children[$i] = $children[$i][category_id];
        }
        // prn(join(',',$children));
        // get all the visible news attached to visible children
        $query = "SELECT SQL_CALC_FOUND_ROWS
                   news.id
                  ,news.lang
                  ,news.title
                  ,news.news_code
                  ,news.site_id
                  ,news.abstract AS abstract
                  ,news.last_change_date
                  ,news.expiration_date
                  ,news.tags
                  ,IF(LENGTH(TRIM(news.content))>0,1,0) as content_present
            FROM {$GLOBALS['table_prefix']}news news
            WHERE site_id=$site_id
              AND lang='" . DbStr($this->lang) . "'
              AND cense_level>={$this->this_site_info['cense_level']}
              AND last_change_date<=now()
              AND ( expiration_date is null OR now()<=expiration_date )
              AND news.id in(SELECT news_id FROM {$GLOBALS['table_prefix']}news_category WHERE category_id in(" . join(',', $children) . ") )
            ORDER BY {$this->ordering}
            LIMIT {$this->start},{$this->rows_per_page}";
        //prn($query);
        $this->_list = db_getrows($query);




        $this->items_found = db_getonerow("SELECT FOUND_ROWS() AS n_records");
        $this->items_found = $this->items_found['n_records'];
        //prn('$this->items_found=' . $this->items_found);
        # --------------------------- list of pages - begin --------------------------
        $this->_pages = $this->get_paging_links($this->items_found, $this->start, $this->rows_per_page);
        //prn('$this->_pages=',$this->_pages);
        # --------------------------- list of pages - end ----------------------------

        $this->_list = news_get_view($this->_list, $this->lang);

        return '';
    }

    function get_paging_links($records_found, $start, $rows_per_page) {

        $url_prefix = site_URL . '?' . preg_query_string("/" . $this->startname . "|" . session_name() . "/") . "&{$this->startname}=";

        $pages = Array();
        $imin = max(0, $start - 10 * $rows_per_page);
        $imax = min($records_found, $start + 10 * $rows_per_page);
        if ($imin > 0) {
            $pages[] = Array(
                'URL' => $url_prefix . '0',
                'innerHTML' => '[1]'
            );
            $pages[] = Array('URL' => '', 'innerHTML' => '...');
        }

        for ($i = $imin; $i < $imax; $i = $i + $rows_per_page) {
            if ($i == $start) {
                $pages[] = Array('URL' => '', 'innerHTML' => '<b>[' . (1 + $i / $rows_per_page) . ']</b>');
            } else {
                $pages[] = Array('URL' => $url_prefix . $i, 'innerHTML' => ( 1 + $i / $rows_per_page));
            }
        }

        if ($imax < $records_found) {
            $last_page = floor(($records_found - 1) / $rows_per_page);
            if ($last_page > 0) {
                $pages[] = Array('URL' => '', 'innerHTML' => "...");
                $pages[] = Array(
                    'URL' => $url_prefix . ($last_page * $rows_per_page)
                    , 'innerHTML' => "[" . ($last_page + 1) . "]"
                );
            }
        }
        return $pages;
    }

}

// get attached calendar events - delayed feature
run('calendar/functions');
// get attached pages - delayed feature
// ---------------------- draw - begin -----------------------------------------
run('site/page/page_view_functions');
$menu_groups = get_menu_items($site_id, 0, $lang);

// ------------- mark menu items - begin ---------------------------------------
// $this_category_info['parents']
$urls = Array();
foreach ($this_category_info['parents'] as $tmp) {
    $UP = parse_url($tmp['URL']);
    if (isset($UP['query'])) {
        parse_str($UP['query'], $out);
        $UP['query'] = $out;
    } else {
        $UP['query'] = Array();
    }
    $urls[] = $UP;
}
$UP = parse_url($this_category_info['URL']);
if (isset($UP['query'])) {
    parse_str($UP['query'], $out);
    $UP['query'] = $out;
} else {
    $UP['query'] = Array();
}
$urls[] = $UP;

function category_url_is_similar($url_pattern, $url) {

    $U = parse_url($url);
    if (!isset($U['path'])){
        $U['path']='';
    }
    if (isset($U['query'])) {
        parse_str($U['query'], $out);
        $U['query'] = $out;
    } else {
        $U['query'] = Array();
    }

    // prn($U, $url_pattern);

    $weight = 0.0;
    $total = 0;

    // compare host
    if (isset($url_pattern['host']) && isset($U['host'])) {
        $total++;
        $weight+=($url_pattern['host'] == $U['host']) ? 1 : 0;
    }

    // compare query
    if (isset($url_pattern['query']) && isset($U['query'])) {
        foreach ($url_pattern['query'] as $key => $val) {
            $total+=1;
            if (isset($U['query'][$key]) && $U['query'][$key] == $val) {
                $weight+=($url_pattern['path'] == $U['path']) ? 1 : 0;
            }
        }
    }
    if ($total == 0) {
        return false;
    }
    // compare path
    if (($url_pattern['path'] == $U['path']) && ($weight / $total) > 0.9) {
        return true;
    }
    return false;
}

foreach ($menu_groups as $kmg => $mg) {
    foreach ($mg['items'] as $kmi => $mi) {
        $is_similar = false;
        foreach ($urls as $pt) {
            if (category_url_is_similar($pt, $mi['url'])) {
                $is_similar = true;
                break;
            }
        }
        // prn($mi['url'], "=>" . $is_similar);
        $menu_groups[$kmg]['items'][$kmi]['disabled'] = $is_similar;
    }
}

// ------------- mark menu items - end -----------------------------------------

$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
    $lang_list[$i]['url'] = $lang_list[$i]['href'];
}
//prn($lang_list);
//clearstatcache();
// if(isset($_REQUEST['v'])) phpinfo();,isset($_REQUEST['v'])

$template_name='template_category_browse';
$_template = sites_root.'/'.$this_site_info['dir']."/{$template_name}_{$category_id}.html";
if(!is_file($_template)){
    $_template = site_get_template($this_site_info, $template_name);
}


//echo $_template;

$category_events = new CategoryEvents($lang, $this_site_info, $this_category_info, isset($input_vars['event_start']) ? ( (int) $input_vars['event_start']) : 0);
//$category_events->init();
$vyvid = process_template($_template
        , Array(
    'category' => $this_category_info
    , 'news' => new CategoryNews($lang, $this_site_info, $this_category_info, isset($input_vars['news_start']) ? ( (int) $input_vars['news_start']) : 0)
    , 'events' => $category_events
    , 'text' => $txt
    , 'site' => $this_site_info
    , 'lang' => $lang
        ), Array('category_news')
);
$this_site_info['title'] = get_langstring($this_site_info['title'], $input_vars['lang']);
$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array('title' => $this_category_info['category_title']
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
echo $file_content;
// ---------------------- draw - end -------------------------------------------
?>
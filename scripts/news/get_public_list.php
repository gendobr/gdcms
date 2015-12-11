<?php

# -------------------- number of news in the block - begin ---------------------
$rows = rows_per_page;
if (isset($input_vars['rows'])) {
    $rows = (int) $input_vars['rows'];
}
if ($rows <= 0 or $rows > 10000) {
    $rows = rows_per_page;
}
# -------------------- number of news in the block - end -----------------------
# -------------------- if abstracts should be shown - begin --------------------
$show_abstracts = true;
if (isset($input_vars['abstracts'])) {
    if ($input_vars['abstracts'] == 'no') {
        $show_abstracts = false;
    }
}
# -------------------- if abstracts should be shown - end ----------------------


run('news/menu');


$debug = false;

if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
$input_vars['lang'] = get_language('lang');

# -------------------------- load messages - begin -----------------------------
global $txt;
$txt = load_msg($input_vars['lang']);
# -------------------------- load messages - end -------------------------------
#
# ------------------- get site info - begin ------------------------------------
if (!function_exists('get_site_info')) {
    run('site/menu');
}
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}
# ------------------- get site info - end --------------------------------------
#
#
#
#
#
# --------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, 'template_index.html', false);
# --------------------------- get site template - end --------------------------
#
#
#
#
#
#
# ====================== get category selector = begin =========================
if (!class_exists('news_browse_tree')) {
    run('lib/class_tree1');
    run('lib/class_tree2');

    class news_browse_tree extends browse_tree {

        var $site_id;

        function news_browse_tree($category_id, $site_id) {
            $this->site_id = $site_id;
            $this->exclude = '^start$|^' . session_name() . '$|^news_date_|^news_keywords$|^tags$';
            parent::browse_tree($category_id, $GLOBALS['db'], $GLOBALS['table_prefix'], $site_id);
        }

        // --------------------- restrict children - begin --------------------------
        function restrict_children() {
            global $table_prefix, $db, $input_vars;
            $child_ids = Array();
            foreach ($this->children as $ke => $ch) {
                $child_ids[$ke] = (int) $ch['category_id'];
            }
            if (count($child_ids) > 0) {
                $query = "select pa.category_id
                from {$table_prefix}category as pa
                     STRAIGHT_JOIN {$table_prefix}category as ch
                     STRAIGHT_JOIN {$table_prefix}news_category as nc
                     STRAIGHT_JOIN {$table_prefix}news as news
                where pa.category_id in(" . join(',', $child_ids) . ")
                  and news.lang='" . DbStr($input_vars['lang']) . "'
                  and news.id=nc.news_id
                  and nc.category_id=ch.category_id
                  and pa.start<=ch.start AND ch.finish<=pa.finish";
                $visible_children = db_getrows($query);
                //prn($visible_children);
                $cnt = count($visible_children);
                for ($i = 0; $i < $cnt; $i++) {
                    $visible_children[$i] = $visible_children[$i]['category_id'];
                }
            } else {
                $visible_children = Array();
            }

            $cnt = count($this->children);
            for ($i = 0; $i < $cnt; $i++) {
                if (!in_array($this->children[$i]['category_id'], $visible_children)) {
                    unset($this->children[$i]);
                }
            }
            $this->children = array_values($this->children);
        }

        // --------------------- restrict children - end ----------------------------
        function adjust($_info, $category_id, $url_prefix) {
            $tor = $_info;
            $tor['category_title'] = get_langstring($tor['category_title'], $GLOBALS['input_vars']['lang']);
            $tor['category_description'] = get_langstring($tor['category_description'], $GLOBALS['input_vars']['lang']);
            $tor['title_short'] = shorten($tor['category_title']);
            $tor['padding'] = 20 * $tor['deep'];
            $tor['URL'] = "{$url_prefix}&category_id={$tor['category_id']}";
            $tor['has_subcategories'] = ($tor['finish'] - $tor['start'] > 1) ? '>>>' : '';
            return $tor;
        }

    }

}
$news_browse_tree = new news_browse_tree(isset($input_vars['category_id']) ? ( (int) $input_vars['category_id'] ) : 0, $site_id);
$category_selector = $news_browse_tree->draw();
$categories = $news_browse_tree;
#   prn($news_browse_tree);
#  echo $category_selector;
# ====================== get category selector = end ==========================
# 
# 
# 
# 
# 
# 
# 
# ---------------------- tag selector - begin ----------------------------------
$lang = DbStr($input_vars['lang']);

run('lib/file_functions');

// -------------------- get cached list of tags - begin ------------------------
// cache info as file in the site dir
$tmp = get_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_tags_site{$site_id}_lang{$lang}.cache", cachetime);
if ($tmp) {
    $tags = $tmp;
} else {
    $query = "SELECT DISTINCT news_tags.tag
               FROM {$table_prefix}news_tags AS news_tags
                  , {$table_prefix}news AS news
               WHERE news_tags.news_id=news.id
                 AND news.lang=news_tags.lang
                 AND news.cense_level>={$this_site_info['cense_level']}
                 AND news.site_id={$site_id}
                 AND news.lang='{$lang}'";
    $tags = db_getrows($query);
    set_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_tags_site{$site_id}_lang{$lang}.cache", $tags);
}
// -------------------- get cached list of tags - end --------------------------

//prn($tags);
$tag_selector = '';
$selected_tags = Array();
if (count($tags) > 0) {
    $cnt = count($tags);
    for ($i = 0; $i < $cnt; $i++) {
        $tags[$i] = $tags[$i]['tag'];
    }

    if (isset($input_vars['tags']) && strlen($input_vars['tags']) > 0) {
        $selected_tags = array_intersect($tags, explode(',', $input_vars['tags']));
    }

    $url_prefix = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_|^news_keywords$|^tags$|^category_id$|^action$') . '&tags=';

    $tag_selector = '';
    foreach ($tags as $tg) {
        if (strlen($tg) == 0)
            continue;
        if (in_array($tg, $selected_tags)) {
            $tag_list = join(',', array_diff($selected_tags, Array($tg)));
            $tag_selector.=" <span class='tag_selected'><a href=\"{$url_prefix}" . rawurlencode($tag_list) . "\"><img src=\"" . site_root_URL . "/img/checked.gif\" width=11px height=11px border=0 class=img_tag_selected><b>{$tg}</b></a></span> ";
        } else {
            $tag_list = join(',', array_merge($selected_tags, Array($tg)));
            $tag_selector.=" <span class='tag'><a href=\"{$url_prefix}" . rawurlencode($tag_list) . "\"><img src=\"" . site_root_URL . "/img/unchecked.gif\" width=11px height=11px border=0 class=img_tag><b>{$tg}</b></a></span> ";
        }
    }
    //prn(checkStr($tag_selector));
    if (strlen($tag_selector) > 0) {
        $tag_selector = "<div><b>{$txt['News_tags']}</b><br>$tag_selector</div>";
    }
    //prn($tag_selector);
}
# ---------------------- tag selector - end ------------------------------------
# 
# 
# 
# 
# 
# --------------------- draw date selector - begin -----------------------------
# site_id=1&lang=ukr&news_date_year=2007&news_date_month=1&news_date_day=29
$lang = DbStr($input_vars['lang']);

# year
if (isset($input_vars['news_date_year']) && strlen($input_vars['news_date_year']) > 0) {
    $href = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_|^news_keywords$|^action$');
    $all_dates = "<a href='{$href}'>{$txt['All_dates']}</a>";

    $news_date_year = (int) $input_vars['news_date_year'];
    $year_options = "<b>{$news_date_year}</b>";


    $month_names = Array('', $txt['month_January'], $txt['month_February'], $txt['month_March'], $txt['month_April'], $txt['month_May'], $txt['month_June'], $txt['month_July'], $txt['month_August'], $txt['month_September'], $txt['month_October'], $txt['month_November'], $txt['month_December']);
    if (isset($input_vars['news_date_month']) && strlen($input_vars['news_date_month']) > 0) {
        $news_date_month = (int) $input_vars['news_date_month'];
        $month_options = "<b>{$month_names[$news_date_month]}</b>";

        $href = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_|^news_keywords$|^action$') . '&news_date_year=';
        $year_options = "<a href='{$href}{$news_date_year}'>{$news_date_year}</a>";

        if (isset($input_vars['news_date_day']) && strlen($input_vars['news_date_day']) > 0) {
            $news_date_day = (int) $input_vars['news_date_day'];
            $href = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_day$|^news_date_month$|^news_keywords$|^action$') . '&news_date_month=';
            $month_options = "<a href='{$href}{$news_date_month}'>{$month_names[$news_date_month]}</a>";
            $day_options = "<b>$news_date_day</b>";
        } else {
            $tmp = db_getrows("SELECT DISTINCT DAYOFMONTH(last_change_date) AS day
                           FROM {$table_prefix}news as news
                           WHERE news.site_id={$site_id}
                             AND news.cense_level>={$this_site_info['cense_level']}
                             AND news.lang='{$lang}'
                             AND year(last_change_date)=$news_date_year
                             AND month(last_change_date)=$news_date_month
                           ORDER BY day ASC");
            $days = Array();
            $href = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_day$|^news_keywords$|^action$') . '&news_date_day=';
            foreach ($tmp as $tm)
                $days[] = "<a href='{$href}{$tm['day']}'>{$tm['day']}</a>";
            $day_options = join(' ', $days);
        }
    } else {
        $tmp = get_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_months_site{$site_id}_lang{$lang}_year{$news_date_year}.cache", cachetime);
        if (!$tmp) {
            $tmp = db_getrows("SELECT DISTINCT month(last_change_date) AS month
                                      FROM {$table_prefix}news as news
                                      WHERE news.site_id={$site_id}
                                       AND  news.cense_level>={$this_site_info['cense_level']}
                                       AND news.lang='{$lang}'
                                       AND year(last_change_date)=$news_date_year
                                      ORDER BY month ASC");
            set_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_months_site{$site_id}_lang{$lang}_year{$news_date_year}.cache", $tmp);
        }
        $months = Array();
        $href = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_day$|^news_keywords$|^action$') . "&news_date_month=";
        foreach ($tmp as $tm) {
            $months[] = "<a href='{$href}{$tm['month']}'>" . $month_names[$tm['month']] . "</a>";
        }
        $month_options = join(' ', $months);
    }
} else {
    $all_dates = "<b>{$txt['All_dates']}</b>";
    $years = Array();
    $href = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_|^news_keywords$|^action$') . '&news_date_year=';



    $tmp = get_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_years_site{$site_id}_lang{$lang}.cache", cachetime);
    if (!$tmp) {
        $tmp = db_getrows("SELECT DISTINCT YEAR(last_change_date) AS year FROM {$table_prefix}news as news WHERE news.site_id={$site_id} AND  news.cense_level>={$this_site_info['cense_level']} AND news.lang='{$lang}' ORDER BY year ASC");
        set_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_years_site{$site_id}_lang{$lang}.cache", $tmp);
    }


    foreach ($tmp as $tm) {
        $years[] = "<a href='{$href}{$tm['year']}'>{$tm['year']}</a>";
    }
    $year_options = join(' ', $years);
}


# news search form
$news_date_selector = "<div>$all_dates</div>";
$news_date_selector.="<div>{$txt['Year']} : {$year_options} </div>";
if (isset($month_options) && strlen($month_options) > 0) {
    $news_date_selector.="<div>{$txt['Month']} : {$month_options} </div>";
}
if (isset($day_options) && strlen($day_options) > 0) {
    $news_date_selector.="<div>{$txt['Day']} : {$day_options} </div>";
}

# --------------------- draw date selector - end -------------------------------
# 
# 
# 
# 
# 
# 
# 
# 
# --------------------- draw keyword search form - begin -----------------------
$news_keywords = trim(isset($input_vars['news_keywords']) ? $input_vars['news_keywords'] : '');
$news_keywords_selector = "
    <form action=\"" . url_prefix_news_list . "site_id=$site_id\" method=\"post\">
    <input type=text name=news_keywords value=\"{$news_keywords}\">
    <input type=submit value=\"{$txt['Search']}\">
    </form>
    ";
# --------------------- draw keyword search form - end -------------------------
# 
# 
# 
# 
# 
# 
# 
# 
# 
# ------------------- get list of news - begin ---------------------------------

$lang = DbStr($input_vars['lang']);


# ------------------------- category restriction - begin -----------------------
if (isset($input_vars['category_id'])) {
    $category_ids = preg_split("/(;|:|,| )+/", $input_vars['category_id']);
    for ($i = 0, $cnt = count($category_ids); $i < $cnt; $i++) {
        $category_ids[$i]*=1;
        if ($category_ids[$i] <= 0) {
            unset($category_ids[$i]);
        }
    }
    $category_ids = array_values($category_ids);

    if (count($category_ids) > 0) {
        $category_ids = join(',', $category_ids);
        if (isset($input_vars['category_filter_mode'])) {
            $category_restriction = "
               inner join {$GLOBALS['table_prefix']}news_category as nc on (nc.news_id=ne.id)
               inner join {$GLOBALS['table_prefix']}category as ch on (nc.category_id=ch.category_id)
               inner join {$GLOBALS['table_prefix']}category as pa on (pa.start<=ch.start and ch.finish<=pa.finish and pa.category_id in({$category_ids}))
              ";
        } else {
            $category_restriction = "
               inner join {$GLOBALS['table_prefix']}news_category as nc on (nc.news_id=ne.id)
               inner join {$GLOBALS['table_prefix']}category as ch on (nc.category_id=ch.category_id and ch.category_id  in({$category_ids}))
              ";
        }
    }
    //$category_restriction="AND ne.category_id={$news_browse_tree->info['category_id']}";
} else {
    $category_restriction = '';
}
# ------------------------- category restriction - end -------------------------
# 
# 
# 
# 
# 
# 
# ------------------------- date restriction - begin ---------------------------
$news_date_restriction = '';
if (isset($input_vars['news_date_year']) && strlen($input_vars['news_date_year']) > 0) {
    $news_date_restriction.="  AND YEAR(ne.last_change_date)=" . ((int) $input_vars['news_date_year']);
}
if (isset($input_vars['news_date_month']) && strlen($input_vars['news_date_month']) > 0) {
    $news_date_restriction.="  AND MONTH(ne.last_change_date)=" . ((int) $input_vars['news_date_month']);
}
if (isset($input_vars['news_date_day']) && strlen($input_vars['news_date_day']) > 0) {
    $news_date_restriction.="  AND DAYOFMONTH(ne.last_change_date)=" . ((int) $input_vars['news_date_day']);
}
#prn($news_date_restriction);
# ------------------------- date restriction - end -----------------------------
# 
# 
# 
# 
# 
# ------------------------- keyword restriction - begin ------------------------
$news_keywords_restriction = '';
if (strlen($news_keywords) > 0) {
    # $news_keywords
    $news_keywords_restriction = explode(' ', trim($news_keywords));
    $cnt = count($news_keywords_restriction);
    $tmp = "LOCATE('%s',concat(ifnull(ne.title,''),' ',ifnull(ne.content,''),' ',ifnull(ne.abstract,'')))";
    for ($i = 0; $i < $cnt; $i++) {
        if (strlen($news_keywords_restriction[$i]) > 0) {
            $news_keywords_restriction[$i] = sprintf($tmp, DbStr($news_keywords_restriction[$i]));
        } else {
            unset($news_keywords_restriction[$i]);
        }
    }
    if (count($news_keywords_restriction) > 0) {
        $news_keywords_restriction = ' AND ' . join(' AND ', $news_keywords_restriction);
    } else
        $news_keywords_restriction = '';
}
# prn('$news_keywords_restriction='.$news_keywords_restriction);
# ------------------------- keyword restriction - end --------------------------
# 
# 
# 
# 
# ------------------------- tag restriction - begin ----------------------------
if (count($selected_tags) > 0) {
    $news_tags_restriction = '';
    foreach ($selected_tags as $tg) {
        //$news_tags_restriction.=" AND FIND_IN_SET('" . DbStr($tg) . "',tags)>0 ";
        $news_tags_restriction.=" AND LOCATE('" . DbStr(trim($tg)) . "',tags)>0 ";
    }
} else {
    $news_tags_restriction = '';
}
# ------------------------- tag restriction - end ------------------------------
# 
# 
# 
# ------------------------- page start - begin ---------------------------------
if (!isset($input_vars['start'])) {
    $input_vars['start'] = 0;
}
$start = abs(round(1 * $input_vars['start']));
# ------------------------- page start - end -----------------------------------
# 
# 
# 
# 
# ------------------  set custom ordering - begin ------------------------------
$orderby = Array();
if (isset($_REQUEST['orderby']) && strlen($_REQUEST['orderby']) > 0) {
    $sortablecolumns = array_flip(array('id', 'title', 'last_change_date', 'category_id', 'expiration_date', 'weight'));
    $tmp = explode(',', $_REQUEST['orderby']);
    // echo '<!-- tmp'; prn($tmp); echo ' -->';
    foreach ($tmp as $tm) {
        // echo '<!-- tm'; prn($tm); echo ' -->';
        $ord = preg_split('/ +/', trim($tm));
        // echo '<!-- ord '; prn($ord); echo ' -->';
        if (isset($sortablecolumns[$ord[0]])) {
            if (isset($ord[1]) && strtolower($ord[1]) == 'desc') {
                $ord[1] = 'desc';
            } else {
                $ord[1] = 'asc';
            }
            $orderby[$ord[0]] = "ne.{$ord[0]} {$ord[1]}";
        }
    }
}
//echo '<!-- orderby'; prn($orderby); echo ' -->';
if (!isset($orderby['last_change_date'])) {
    if (isset($input_vars['date']) && $input_vars['date'] == 'asc') {
        $date_order = 'ASC';
    } else {
        $date_order = 'DESC';
    }
    $orderby[] = "ne.last_change_date {$date_order}";
}
$orderby = join(',', $orderby);
// ------------------  set custom ordering - begin -----------------------------
// ------------------  set ordering - begin ------------------------------------


$now = date('Y-m-d H:i:s', time());
$query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
                   ne.id
                  ,ne.lang
                  ,ne.site_id
                  ,ne.title
                  ,ne.tags
                  ,ne.news_code
                  ,ne.news_extra_1
                  ,ne.news_extra_2
                  ,ne.abstract AS abstract
                  ,ne.last_change_date
                  ,ne.expiration_date
                  ,ne.content
                  ,IF(LENGTH(TRIM(ne.content))>0,1,0) as content_present
            FROM {$table_prefix}news AS ne
                 $category_restriction
            WHERE ne.site_id={$site_id}
              AND ne.cense_level>={$this_site_info['cense_level']}
              AND ne.lang='{$lang}'
              AND ne.last_change_date < '$now' AND ( ne.expiration_date is null OR  '$now'< ne.expiration_date)
              $news_date_restriction
              $news_keywords_restriction
              $news_tags_restriction
            ORDER BY {$orderby}
            LIMIT $start,$rows";

            
$startTime=  microtime(true);
$list_of_news = db_getrows($query);
header('Cms-Timing: '. (microtime(true)-$startTime));
// if(isset($input_vars['debug'])) prn($query,$list_of_news);
// echo '<!-- '; prn($query); echo ' -->';
# --------------------------- get list of news - end -------------------------
# --------------------------- list of pages - begin --------------------------
$query = "SELECT FOUND_ROWS() AS n_records;";
$num = db_getonerow($query);
// prn($query,$num);
$news_found = $num = (int) $num['n_records'];
$pages = Array();
$imin = max(0, $start - 10 * rows_per_page);
$imax = min($num, $start + 10 * rows_per_page);
if ($imin > 0) {
    $pages[] = Array(
        'URL' => sites_root_URL . "/news.php?start=0&" . query_string('^start$|^' . session_name() . '$|^action$'),
        'innerHTML' => '[1]'
    );
    $pages[] = Array('URL' => '', 'innerHTML' => '...');
}

for ($i = $imin; $i < $imax; $i = $i + rows_per_page) {
    if ($i == $start)
        $to = '<b>[' . (1 + $i / rows_per_page) . ']</b>';
    else
        $to = ( 1 + $i / rows_per_page);
    $pages[] = Array(
        'URL' => url_prefix_news_list . "start={$i}&" . query_string('^start$|^' . session_name() . '$|^action$')
        , 'innerHTML' => $to
    );
}

if ($imax < $num) {
    $last_page = floor(($num - 1) / rows_per_page);
    if ($last_page > 0) {
        $pages[] = Array('URL' => '', 'innerHTML' => "...");
        $pages[] = Array(
            'URL' => sites_root_URL . "/news.php?start=" . ($last_page * rows_per_page) . "&" . query_string('^start$|^' . session_name() . '$|^action$')
            , 'innerHTML' => "[" . ($last_page + 1) . "]"
        );
    }
}
# --------------------------- list of pages - end ----------------------------
// adjust list of news
$list_of_news = news_get_view($list_of_news, $lang);


if (!function_exists('db_get_template'))
    run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);
// prn('$menu_groups',$menu_groups);
// mark current page URL
$prefix_length = strlen(url_prefix_news_list);

foreach ($menu_groups as $kmg => $mg) {
    foreach ($mg['items'] as $kmi => $mi) {
        if (url_prefix_news_list == substr($mi['url'], $prefix_length)) {
            continue;
        }
        if (!preg_match("/action=news(\\/|%2F)view/i", $mi['url'])) {
            continue;
        }
        if (!preg_match("/site_id={$site_id}(\$|&)/i", $mi['url'])) {
            continue;
        }
        $menu_groups[$kmg]['items'][$kmi]['disabled'] = 1;
    }
}
//------------------------ get list of languages - begin -----------------------
# -------------------- get list of page languages - begin --------------------

$tmp = get_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_lang_{$site_id}.cache", cachetime);
if (!$tmp) {
    $tmp = db_getrows("SELECT DISTINCT lang
                     FROM {$table_prefix}news  AS ne
                     WHERE ne.site_id={$site_id}
                       AND ne.cense_level>={$this_site_info['cense_level']}");
    set_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/news_lang_{$site_id}.cache", $tmp);
}

$existing_languages = Array();
foreach ($tmp as $tm) {
    $existing_languages[$tm['lang']] = $tm['lang'];
}
# prn($existing_languages);
# -------------------- get list of page languages - end ----------------------

$lang_list = list_of_languages();
#prn($lang_list);
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    //prn($lang_list[$i]['name']);
    if (!isset($existing_languages[$lang_list[$i]['name']])) {
        unset($lang_list[$i]);
        continue;
    }
    //prn('OK');
    $lang_list[$i]['url'] = $lang_list[$i]['href'];

    $lang_list[$i]['url'] = str_replace('action=news%2Fview', '', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('index.php', 'news.php', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace(site_root_URL, sites_root_URL, $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('?&', '?', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('&&', '&', $lang_list[$i]['url']);

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
$lang_list = array_values($lang_list);
//prn($lang_list);
//------------------------ get list of languages - end -------------------------





$all_news_url = sites_root_URL . "/news.php?start=0&site_id=$site_id&lang={$input_vars['lang']}";
if (isset($input_vars['category_id']))
    $all_news_url.="&category_id=" . ((int) $input_vars['category_id']);


$rss_url = site_root_URL . "/index.php?action=news/rss&start=0&" . query_string('action|start');
//prn($rss_url);
/*
  $vyvid=process_template( $news_template
  ,Array(
  'site'=>$this_site_info
  ,'paging_links'=>$pages
  ,'text'=>$txt
  ,'news'=>$list_of_news
  ,'news_found' => $news_found
  ,'news_date_selector'=>$news_date_selector
  ,'news_keywords_selector'=>$news_keywords_selector
  ,'news_category_selector'=>$category_selector
  ,'news_tags'=>$tag_selector
  ));
 */
?>
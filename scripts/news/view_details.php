<?php

/*
  View news for site
  arguments are
  $news_id - news identifier, integer, mandatory
  $lang    - interface language, char(3)
 */

$GLOBALS['main_template_name'] = '';

run('site/page/page_view_functions');
run('site/menu');
run('news/menu');

$debug = false;


//-------------------------- load messages - begin -----------------------------
$input_vars['lang'] = get_language('interface_lang,lang');
$txt = load_msg($input_vars['lang']);
//-------------------------- load messages - end -------------------------------
//
# ------------------- get full news info - begin -------------------------------
$news_id = isset($input_vars['news_id']) ? ((int) $input_vars['news_id']) : 0;
$news_code=isset($input_vars['news_code']) ? $input_vars['news_code'] : '';
$this_news_info = false;
if($news_id > 0){
  $this_news_info = \e::db_getonerow("SELECT * FROM <<tp>>news WHERE id={$news_id} AND lang='" . \e::db_escape($input_vars['lang']) . "'");    
}
if(!$this_news_info && strlen($news_code)>0){
  $this_news_info = \e::db_getonerow("SELECT * FROM <<tp>>news WHERE news_code='" . \e::db_escape($news_code) . "' AND lang='" . \e::db_escape($input_vars['lang']) . "'");
}

if (!$this_news_info) {
    header("HTTP/1.0 404 Not Found");
    header("Location: /---".md5(rand(1, 100000)).'.html');
    exit();
}



// get news categories
$query = "SELECT DISTINCT pa.category_id, pa.category_code,pa.category_title, pa.deep, pa.start
          FROM <<tp>>category as pa
              ,<<tp>>news_category as nc
          WHERE nc.category_id=pa.category_id
            AND nc.news_id={$this_news_info['id']}
            AND pa.site_id={$this_news_info['site_id']}
          ORDER BY pa.start
          ";
$this_news_info['categories'] = \e::db_getrows($query);
$tmp = Array();
foreach ($this_news_info['categories'] as $cat) {
    $tmp[] = Array(
          'category_id' => $cat['category_id']
        , 'category_code' => $cat['category_code']
        , 'category_title' => "<div style=\"padding-left:" . (10 * $cat['deep']) . "pt\">" . get_langstring($cat['category_title']) . "</div>"
        , 'category_url' => \e::config('url_prefix_news_list') . "site_id={$this_news_info['site_id']}&category_id={$cat['category_id']}"
        , 'deep' => $cat['deep']
    );
}
$this_news_info['categories'] = $tmp;
# prn('$this_news_info',$this_news_info);
# ------------------- get full news info - end ---------------------------------
//
//
//
//
# ------------------- get site info - begin ------------------------------------
$site_id = checkInt($this_news_info['site_id']);
$this_site_info = get_site_info($site_id, $input_vars['lang']);
# prn($this_site_info);
# prn($input_vars);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}

$this_site_info['URL_to_view_news'] = str_replace(
    ['{site_id}','{lang}','{other_parameters}'],
    [$this_site_info['id'],$input_vars['lang'],''],
    \e::config('url_template_news_list'));

# ------------------- get site info - end --------------------------------------
//
//
//
//
//--------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, 'template_index');
//--------------------------- get site template - end --------------------------
//
//
# --------------------- check level of censor - begin --------------------------
//------------------- visitor info - begin -------------------------------------
if (get_level($site_id) > 0) {
    //prn($_SESSION['user_info']);
    $visitor = Array(
        'site_visitor_login' => $_SESSION['user_info']['user_login'],
        'site_visitor_email' => $_SESSION['user_info']['email'],
        'site_visitor_home_page_url' => $this_site_info['url'],
        'URL_login' => site_root_URL . "/index.php?action=forum/login&lang={$input_vars['lang']}",
        'URL_signup' => site_root_URL . "/index.php?action=forum/signup&lang={$input_vars['lang']}",
        'URL_logout' => site_root_URL . "/index.php?action=forum/logout&lang={$input_vars['lang']}",
        'is_moderator' => 1
    );
} else {
    // site visitor session
    if (!isset($_SESSION['site_visitor_info'])) {
        $_SESSION['site_visitor_info'] = $GLOBALS['default_site_visitor_info'];
    }

    $visitor = $_SESSION['site_visitor_info'];
    $visitor['URL_login'] = site_root_URL . "/index.php?action=forum/login&lang={$input_vars['lang']}";
    $visitor['URL_signup'] = site_root_URL . "/index.php?action=forum/signup&lang={$input_vars['lang']}";
    $visitor['URL_logout'] = site_root_URL . "/index.php?action=forum/logout&lang={$input_vars['lang']}";
    $visitor['is_moderator'] = 0;
}
// prn($visitor);
//------------------- visitor info - end ---------------------------------------




if( $this_news_info['cense_level'] < $this_site_info['cense_level'] ) {
    $until=\e::cast('integer',\e::request('until',0));
    $code_posted=\e::cast('plaintext',trim(\e::request('code',0)));
    $code_calculated=md5("{$until}-{$this_news_info['id']}-{$this_news_info['lang']}");
    if(strlen($code_posted)==0 || $until<time() || $code_posted!=$code_calculated){
        die('News not found');
    }
}
# --------------------- check level of censor - end ----------------------------
// ------------------ show related news using tags - begin ---------------------
// sample usage (paste in Smarty template)
//   {capture name=npt assign="related_news"}
//     {show_related_news news_id=$news.id lang=$news.lang site_id=$news.site_id}
//   {/capture}
//   {if $related_news}
//       {txt lang=$text.language_name variants="eng=Similar news::rus=������� �������::ukr=���� ������"}
//       {$related_news}
//   {/if}

function show_related_news($params) {
    // echo '<!-- '; prn($params); echo ' -->';
    extract($params);
    $news_id*=1;
    $site_id*=1;
    $lang= \e::db_escape($lang);
    # required parameters are news_id, lang, site_id, count
    // get cached data
    $cach_timestamp = time() - 3600 * 24;
    $query = "select n.cached_info from <<tp>>news as n where n.lang='{$lang}' and n.id={$news_id} and cach_timestamp>{$cach_timestamp}";
    $cached_info = \e::db_getonerow($query);
    if($cached_info && $cached_info['cached_info']) {
        $tmp = json_decode($cached_info['cached_info'], true);
    }

    if($tmp){
        $tmp = isset($tmp['related']) ? $tmp['related'] : false;
    }

    if(!$tmp){

        if (!isset($count) || ($count <= 0)){
            $count = 5;
        }



        # ---------------- get all tags of the current news - begin ------------
        $query = "select nt.tag from <<tp>>news_tags as nt where nt.lang='{$lang}' and nt.news_id={$news_id} ";
        $tags = \e::db_getrows($query);
        $cnt = count($tags);
        if ($cnt == 0)
            return '';
        for ($i = 0; $i < $cnt; $i++)
            $tags[$i] = "'" . \e::db_escape($tags[$i]['tag']) . "'";
        # prn($tags);
        # ---------------- get all tags of the current news - end --------------
        # ---------------- get news using tags - begin -------------------------
        $query = "select distinct nws.id,nws.lang,nws.site_id,nws.title,nws.news_code
                  from  <<tp>>news as nws,
                          <<tp>>news_tags as nt
                  where nws.id=nt.news_id
                      and nws.id<>{$news_id}
                      and nws.lang=nt.lang
                      and nt.tag IN (" . join(',', $tags) . ")
                      and nt.lang='{$lang}'
                      and nws.site_id={$site_id}
                  order by nws.last_change_date DESC
                  LIMIT 0,$count
             ";
        // prn($query);
        $tmp = \e::db_getrows($query);

        \e::db_execute("UPDATE <<tp>>news 
                        SET cached_info=<<string cached_info>>,
                            cach_timestamp=<<integer cach_timestamp>>
                        WHERE id = <<integer news_id>>
                              AND lang = <<string lang>> ", 
                        [
                            'cached_info'=>json_encode(['related'=>$tmp]),
                            'cach_timestamp' => time(),
                            'news_id' => $news_id,
                            'lang'=>$lang
                        ]
                    );
    }

    $tor = '';
    foreach ($tmp as $row) {
        $url_news=str_replace(
                Array('{news_id}','{lang}','{news_code}'),
                Array($row['id'],$row['lang'],$row['news_code']),
                \e::config('url_template_news_details'));
        $tor.="<div class=\"see_also\"><a href=\"{$url_news}\">{$row['title']}</a></div>";
    }
    # ---------------- get news using tags - end ---------------------------
    return $tor;
}

// ------------------ show related news using tags - end -----------------------
# ------------------- get news categories - begin ------------------------------
# sample usage (paste in Smarty template)
#     {show_news_categories news_id=$news.id site_id=$news.site_id}
#
function show_news_categories($params) {

    // echo '<!-- '; prn($params); echo ' -->';
    extract($params);
    # required parameters are news_id, site_id
    $lang=get_language('interface_lang,lang');

    $query = "SELECT DISTINCT pa.category_id, pa.category_title, pa.deep, pa.start
              FROM <<tp>>category as c
                  ,<<tp>>category as pa
                  ,<<tp>>news_category as nc
              WHERE nc.category_id=c.category_id
                AND nc.news_id={$news_id}
                AND pa.start<=c.start
                AND c.finish<=pa.finish
                AND c.site_id={$site_id}
                AND pa.site_id={$site_id}
              ORDER BY pa.start
              ";
    $this_news_info['categories'] = \e::db_getrows($query);
    $tmp = '';
    foreach ($this_news_info['categories'] as $cat) {
        $category_url = str_replace(
            ['{site_id}','{lang}','{other_parameters}'],
            [$site_id,$lang,"category_id={$cat['category_id']}"],
            \e::config('url_template_news_list'));
        $tmp.="<span class=\"level{$cat['deep']}\"><a href=\"{$category_url}\">" . get_langstring($cat['category_title'], $lang) . "</a></span>\n";
    }
    return $tmp;
}

# ------------------- get news categories - end --------------------------------
//
//
//
//
# ------------------- news comments - begin ------------------------------------
// hide comment
if (isset($input_vars['hide_comment']) && $visitor['is_moderator']) {
    $news_comment_id = (int) $input_vars['hide_comment'];
    \e::db_execute("UPDATE <<tp>>news_comment SET news_comment_is_visible=0 WHERE news_id={$news_id} AND site_id={$site_id} AND news_comment_id={$news_comment_id}");
}
// show comment
if (isset($input_vars['show_comment']) && $visitor['is_moderator']) {
    $news_comment_id = (int) $input_vars['show_comment'];
    \e::db_execute("UPDATE <<tp>>news_comment SET news_comment_is_visible=1 WHERE news_id={$news_id} AND site_id={$site_id} AND news_comment_id={$news_comment_id}");
}

// add comment
if (isset($input_vars['news_comment_content'])) {
    if ($_REQUEST['postedcode'] != $_SESSION['captcha']
            || strlen($_SESSION['captcha']) == 0)
        $errors = "<b><font color=red>{$txt['Retype_the_number']}</font></b><br/>";

    $news_comment_content = trim(strip_tags($input_vars['news_comment_content']));
    $news_comment_parent_id = isset($input_vars['news_comment_parent_id']) ? (int) $input_vars['news_comment_parent_id'] : 0;
    $news_comment_sender = $visitor['site_visitor_login'];
    $news_comment_is_visible = isset($input_vars['news_comment_is_visible']) && $input_vars['news_comment_is_visible'] ? 1 : 0;
    if (strlen($news_comment_content) > 0 && !isset($errors)) {
        \e::db_execute("INSERT INTO <<tp>>news_comment
                          (  news_id  , news_lang                           ,  site_id  ,  news_comment_datetime, news_comment_content              , news_comment_is_visible   , news_comment_sender              ,  news_comment_parent_id    )
                    VALUES( {$news_id}, '" . \e::db_escape($this_news_info['lang']) . "', {$site_id},  now()                , '" . \e::db_escape($news_comment_content) . "', {$news_comment_is_visible}, '" . \e::db_escape($news_comment_sender) . "',  {$news_comment_parent_id} )
                   ");
        // prn('$news_comment_parent_id='.$news_comment_parent_id);
        $_SESSION['captcha'] = '';
        header("Location: index.php?action=news/view_details&news_id={$news_id}&lang={$this_news_info['lang']}");
        exit();
    }
}
// -------------------------- create confirmation code - begin -----------------
if (!isset($_SESSION['captcha']) || strlen($_SESSION['captcha']) == 0) {
    srand((float) microtime() * 1000000);
    $chars = explode(',', '1,2,3,4,5,6,7,8,9,0');
    shuffle($chars);
    $chars = join('', $chars);
    $chars = substr($chars, 0, 3);
    $_SESSION['captcha'] = $chars;
}

// -------------------------- create confirmation code - end -------------------
// get attached comments - delayed feature
// run('news/menu');
class NewsComments {

    protected $lang, $this_site_info, $news_info, $start;
    protected $_list, $_pages, $items_found;
    protected $rows_per_page = 10;

    // protected $ordering = 'last_change_date DESC';
    // protected $startname = 'news_comments_start';

    function __construct($_lang, $_this_site_info, $_news_info, $start) {
        $this->lang = $_lang;
        $this->this_site_info = $_this_site_info;
        $this->news_info = $_news_info;
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
            case 'items_found':
                return $this->items_found;
                break;
            default: return Array();
        }
    }

    private function init() {
        $site_id = (int) $this->this_site_info['id'];
        $news_id = (int) $this->news_info['id'];
        $news_lang = $this->news_info['lang'];

        // get all visible comments
        //$query = "SELECT *
        //          FROM <<tp>>news_comment
        //          WHERE news_id={$news_id}
        //            and news_lang='".  DbStr($news_lang)."'
        //            AND site_id={$site_id}
        //          ORDER BY news_comment_datetime ASC
        //          ";
        $query = "SELECT *
                  FROM <<tp>>news_comment
                  WHERE news_id={$news_id}
                    AND site_id={$site_id}
                  ORDER BY news_comment_datetime ASC
                  ";
        // prn($query);
        $list = \e::db_getrows($query);
        if (count($list) == 0) {
            $this->_list = Array();
            $this->items_found = 0;
            return;
        }


        // map links
        $cnt = count($list);
        $map = Array();
        for ($i = 0; $i < $cnt; $i++) {
            $map[$list[$i]['news_comment_id']] = $i;
        }
        // prn($map);
        // return;

        $prev = -1;
        for ($i = 0; $i < $cnt; $i++) {
            $list[$i]['indent'] = 0;
            $list[$i]['next'] = -1;
            if ($list[$i]['news_comment_parent_id'] == 0) {
                // prn("i=$i prev=$prev");
                if (isset($list[$prev])) {
                    $list[$prev]['next'] = $i;
                }
                $prev = $i;
            }
            $list[$i]['url_hide_comment'] = "index.php?action=news/view_details&news_id=$news_id&lang=$news_lang&hide_comment=" . $list[$i]['news_comment_id'];
            $list[$i]['url_show_comment'] = "index.php?action=news/view_details&news_id=$news_id&lang=$news_lang&show_comment=" . $list[$i]['news_comment_id'];
        }

        // create linked list
        for ($i = $cnt - 1; $i >= 0; $i--) {
            // place element after its parent
            if ($list[$i]['news_comment_parent_id'] > 0) {
                // search parent
                $parent_position = isset($map[$list[$i]['news_comment_parent_id']]) ? $map[$list[$i]['news_comment_parent_id']] : -1;
                if ($parent_position >= 0) {
                    $old_next = $list[$parent_position]['next'];
                    $list[$parent_position]['next'] = $i;
                    // ����� ����� ������, ������� ���������� � �������� $i
                    $j = $i;
                    while ($list[$j]['next'] >= 0) {
                        $j = $list[$j]['next'];
                    }
                    $list[$j]['next'] = $old_next;
                }
            }
        }
        // prn($list);
        // set indents
        for ($i = 0; $i < $cnt; $i++) {
            $parent_position = isset($map[$list[$i]['news_comment_parent_id']]) ? $map[$list[$i]['news_comment_parent_id']] : -1;
            if ($parent_position >= 0) {
                $list[$i]['indent'] = 1 + $list[$parent_position]['indent'];
            }
        }
        // prn($list);



        $this->_list = Array();
        $next = 0;
        while ($next >= 0) {
            $this->_list[] = $list[$next];
            $next = $list[$next]['next'];
        }
        // prn($this->_list);

        $this->items_found = count($this->_list);

        return '';
    }

}

# ------------------- news comments - end --------------------------------------
//
//
//
//
//
# --------------------- draw news details - begin ------------------------------
$news_view = news_get_view(Array($this_news_info), $input_vars['lang'], $this_site_info);
$news_view = $news_view[0];
# prn($news_view);
# check if site news template name exists
$news_template = false;
// check if  template name is posted
if(isset($input_vars['template'])){
    $news_template_name=basename($input_vars['template']);
    $news_template = site_get_template($this_site_info, $news_template_name);
}
// if template name was not posted, look for default template
if(!$news_template){
    $news_template = site_get_template($this_site_info, 'template_news_details');
}
if (isset($input_vars['debug']) && $input_vars['debug'] == $input_vars['action']) {
    prn('$news_template=' . $news_template);
}
// prn($news_view);
if ($news_template) {
    $vyvid = '';
    $vyvid .= process_template($news_template
            , Array(
                    'news' => $news_view
                  , 'site_root_url' => site_root_URL
                  , 'text' => $txt
                  , 'site' => $this_site_info
                  , 'comments' => (new NewsComments($news_view['lang'], $this_site_info, $news_view, 0) )
                  , 'visitor' => $visitor
                  , 'postedcode_src' => (site_root_URL . "/index.php?action=news/captcha&t=" . time())
            )
            , Array('show_related_news', 'show_news_categories'));
}
# --------------------- draw news details - end --------------------------------

$lang = \e::db_escape($this_news_info['lang']);


// update number of views
\e::db_execute("UPDATE <<tp>>news SET news_views=news_views+1 WHERE id=<<integer id>> AND lang=<<string lang>>",$this_news_info);




//run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);


//------------------------ get list of languages - begin -----------------------
$tmp = \e::db_getrows("SELECT DISTINCT lang FROM <<tp>>news WHERE id={$news_id}");
$this_news_languages = Array();
foreach ($tmp as $tm) {
    $this_news_languages[$tm['lang']] = $tm['lang'];
}
//prn($this_news_languages);

$lang_list = list_of_languages();
//prn($lang_list);
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    if (!isset($this_news_languages[$lang_list[$i]['name']])) {
        unset($lang_list[$i]);
        continue;
    }
    if(!isset($this_site_info['extra_setting']['lang'][$lang_list[$i]['name']])){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url'] = $lang_list[$i]['href']= \e::url_from_template(
        \e::config('url_template_news_details'),
        [
            'news_id'=>$this_news_info['id'],
            'lang'=>$lang_list[$i]['name'],
            'news_code'=>$this_news_info['news_code']
        ]
    );


    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
usort ( $lang_list , function($k1, $k2){
    $defaultLang=\e::config('default_language');
    $s1 = ($k1['name'] == $defaultLang?'0':'1').$k1['name'];
    $s2 = ($k2['name'] == $defaultLang?'0':'1').$k2['name'];
    return -strcmp($s2, $s1);
} );
// prn($lang_list); exit();
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------
//prn("{$input_vars['debug']}=={$input_vars['action']}");
if (isset($input_vars['debug']) && $input_vars['debug'] == $input_vars['action']) {
    prn('$custom_page_template=' . $custom_page_template);
}

$file_content = process_template($custom_page_template //$this_site_info['template']
        , Array(
    'page' => Array(
          'title' => $this_news_info['title']
        , 'content' => $vyvid
        , 'abstract' => ''
        , 'site_id' => $site_id
        , 'lang' => $input_vars['lang']
        , 'page_meta_tags'=>$this_news_info['news_meta_info']
        , 'category_id' => (isset($this_news_info['categories'][0]) ? $this_news_info['categories'][0]['category_id'] : 0)
        , 'editURL'=>site_URL."?action=news/edit&site_id={$site_id}&aed=1&news_id={$this_news_info['id']}&lang={$this_news_info['lang']}"
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

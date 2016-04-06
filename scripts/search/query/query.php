<?php

$timestart = microtime(true);

run('site/page/page_view_functions');
run('site/menu');



// -------------------------- get language - begin -----------------------------
if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
$input_vars['lang'] = $_SESSION['lang'];
$input_vars['lang'] = $lang = get_language('lang');
$txt = load_msg($input_vars['lang']);
// -------------------------- get language - end -------------------------------
// 
// 
// 
// 
//------------------- main site info - begin -----------------------------------
$siteIds = array_map('checkInt', explode(',', $input_vars['site_id']));
$site_id = abs((int) $siteIds[0]);
$this_site_info = get_site_info($site_id, $input_vars['lang']);

# prn($this_site_info);
if ($this_site_info['id'] <= 0) {
    die($txt['Site_not_found']);
}
$siteIds = array_unique($siteIds);
//------------------- main site info - end -------------------------------------
//
//
//
//
//
// ------------------ do search - begin ----------------------------------------


$keywords = trim(isset($input_vars['keywords']) ? strip_tags($input_vars['keywords']) : '');
if (strlen($keywords) > 0) {
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer.php");
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer_ukr.php");
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer_rus.php");
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer_eng.php");
    include (\e::config('SCRIPT_ROOT') . "/search/getlanguage/getlanguage.php");
    include (\e::config('SCRIPT_ROOT') . "/search/commonwords/commonwords.php");

    include (\e::config('SCRIPT_ROOT') . "/search/stemming/stemmer.class.php");
    include (\e::config('SCRIPT_ROOT') . "/search/stemming/porter_eng.class.php");
    include (\e::config('SCRIPT_ROOT') . "/search/stemming/porter_rus.class.php");
    include (\e::config('SCRIPT_ROOT') . "/search/stemming/porter_ukr.class.php");

    $commonwords = new commonwords(\e::config('SCRIPT_ROOT') . "/search/commonwords/commonwords.txt");

    $langSelector = new getlanguage(Array(
        'files' => Array(
            'eng' => \e::config('SCRIPT_ROOT') . "/search/getlanguage/stats_eng.txt",
            'rus' => \e::config('SCRIPT_ROOT') . "/search/getlanguage/stats_rus.txt",
            'ukr' => \e::config('SCRIPT_ROOT') . "/search/getlanguage/stats_ukr.txt",
        // 'slov' => '../getlanguage/stats_slov.txt',
        // 'češ' => '../getlanguage/stats_ces.txt',
        )
    ));

    $tokenizers = Array(
        'eng' => new tokenizer_eng(),
        'ukr' => new tokenizer_ukr(),
        'rus' => new tokenizer_rus()
    );

    $stemmers = Array(
        'eng' => new porter_eng(),
        'ukr' => new porter_ukr(),
        'rus' => new porter_rus()
    );
    $stemmersLangs = array_keys($stemmers);


    $tokens = Array();
    $remainder = $keywords;
    $lang = $langSelector->getTextLang($remainder);
    $lang = $lang['lang'];
    $checkedLangs = Array();
    // echo '<!--'; prn($remainder); echo '-->';
    // echo '<!--'; prn($lang); echo '-->';
    while (true) {
        if (isset($checkedLangs[$lang])) {
            break;
        }
        $checkedLangs[$lang] = 1;
        $reply = $tokenizers[$lang]->getTokens($remainder);
        // echo '<!--'; prn($reply); echo '-->';
        // print_r($reply); exit("222");
        if (count($reply['tokens']) > 0) {
            $tokens[$lang] = $commonwords->removeCommonWords($reply['tokens']);
            $cnt = count($tokens[$lang]);
            for ($i = 0; $i < $cnt; $i++) {
                $token = $tokens[$lang][$i];
                $stemLen = mb_strlen($token, site_charset);
                $stem = $token;
                foreach ($stemmersLangs as $l) {

                    $tmp = $stemmers[$l]->stem($token);
                    $tmpLen = mb_strlen($tmp, site_charset);
                    if ($tmpLen > 0 && $tmpLen < $stemLen) {
                        $stemLen = $tmpLen;
                        $stem = $tmp;
                    }
                }
                $tokens[$lang][$i] = $stem;
            }
        }
        $dl = mb_strlen($remainder, site_charset) - mb_strlen($reply['remainder'], site_charset);
        if ($dl == 0) {
            break;
        }
        $remainder = $reply['remainder'];
        if (strlen($remainder) == 0) {
            break;
        }
        $lang = $langSelector->getTextLang($remainder);
        $lang = $lang['lang'];
    }
    // echo '<!-- tokens'; prn($tokens); echo '-->';
    $keywords = join(' ', array_map(function($x) {
                return join(' ', $x);
            }, $tokens));


    $query = "SELECT  MATCH (words) AGAINST ('" . \e::db_escape($keywords) . "') AS rel, ss.* 
            FROM {$GLOBALS['table_prefix']}search_index_cache AS ss
            WHERE MATCH (words) AGAINST ('" . \e::db_escape($keywords) . "')
                AND site_id IN(" . join(',', $siteIds) . ")
                AND lang='".  \e::db_escape($input_vars['lang'])."'
            LIMIT 0,101;";
    $search_result = \e::db_getrows($query);
    // echo '<!-- '; prn($query); echo ' -->';

    // extract site info
    $tmp = \e::db_getrows("SELECT * FROM {$GLOBALS['table_prefix']}site WHERE id IN(" . join(',', $siteIds) . ")");
    $sites = Array();
    foreach ($tmp as $tm) {
        $tm['title'] = get_langstring($tm['title'], $lang);
        $sites[$tm['id']] = $tm;
    }
    // prn($sites);

    $cnt = count($search_result);
    for ($i = 0; $i < $cnt; $i++) {
        $search_result[$i]['site_title'] = $sites[$search_result[$i]['site_id']]['title'];
        $search_result[$i]['site_url'] = $sites[$search_result[$i]['site_id']]['url'];
    }

    if ($cnt > 100) {
        $num_rows = '>100';
        unset($search_result[101]);
    } else {
        $num_rows = $cnt;
    }

    $pages = Array();
} else {
    $pages = Array();
    $search_result = Array();
    $num_rows = 0;
}
// ------------------ do search - end ------------------------------------------
//
//
// -------------------------- draw - begin -------------------------------------
$search_template = site_get_template($this_site_info, 'template_search_results');
$vyvid = process_template($search_template
        , Array(
    'paging_links' => $pages
    , 'text' => $txt
    , 'search_result' => $search_result
    , 'urls_found' => $num_rows
    , 'form_keywords' => checkStr(isset($input_vars['keywords']) ? $input_vars['keywords'] : '')
    , 'form_action' => url_prefix_search
    , 'form_site_id' => join(',', $siteIds)
    , 'form_lang' => checkStr($input_vars['lang'])
        ));
$vyvid .= "<div style='opacity:0.4;font-size:10px;'>" . (microtime(true) - $timestart) . 's</div>';
// -------------------------- draw - end ---------------------------------------
//
//
//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = url_prefix_search . "interface_lang={$lang_list[$i]['name']}&lang={$lang_list[$i]['name']}&site_id=" . join(',', $siteIds) . "&keywords=" . rawurlencode(isset($input_vars['keywords']) ? $input_vars['keywords'] : '');
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
//------------------------ get list of languages - end -------------------------
// get site menu
$menu_groups = get_menu_items($this_site_info['id'], 0, $lang);
// ----------------------- draw page - begin -----------------------------------
global $main_template_name;
$main_template_name = '';
$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array(
        'title' => $txt['Site_search']
        , 'content' => $vyvid
        , 'abstract' => ''//$txt['search_manual']
        , 'site_id' => $site_id
        , 'lang' => $lang
    )
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
echo $file_content;
// ----------------------- draw page - end -------------------------------------


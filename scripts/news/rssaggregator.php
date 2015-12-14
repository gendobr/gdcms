<?php

/*
 * RSS aggregator
 * Input:
 *   site_id
 *   feed = array of feeeds
 *   template = template file name
 *   rows = number of news in the block
 *   timeout = number, minutes, cache expiration time
 *   lang = current language
 * sample URL is
 * /cms/index.php?action=news/rssaggregator&site_id=1&template=&rows=10&timeout=30&feed=http://habrahabr.ru/rss/best/::http://habrahabr.ru/rss/best/::http://habrahabr.ru/rss/best/
 */
// do not use templates
global $main_template_name;
$main_template_name = '';

$out_charset = site_charset;
$in_charset = 'UTF-8';
// iconv(site_charset, 'UTF-8',$from)
//header('content-type: text/html; charset=utf-8');
header('content-type: text/html; charset=' . $out_charset);

run('site/menu');
run('lib/file_functions');


run('lib/hyphenation/hyphenation');

// ------------------ get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}
// ------------------ get site info - end --------------------------------------
// -------------------- number of news in the block - begin --------------------
if (isset($input_vars['rows'])) {
    $rows = (int) $input_vars['rows'];
} else {
    $rows = rows_per_page;
}
if ($rows <= 0) {
    $rows = rows_per_page;
}
// ------------------- number of news in the block - end -----------------------
// ------------------- check if template name is posted - begin ----------------
if (isset($input_vars['template'])) {
    $template_name = str_replace(Array('/', "\\"), '_', $input_vars['template']);  // to prevent template names like /etc/passwd
    $template_path = site_get_template($this_site_info, $template_name);
}
if (!$template_path) {
    $template_path = site_get_template($this_site_info, 'template_news_rssaggregator');
}
// prn('$template_path=',$template_path);
# -------------------- check if template name is posted - end ------------------
# --------------------  cache expiration time (minutes) - begin ----------------
$timeout = isset($input_vars['timeout']) ? ( (int) $input_vars['timeout'] ) : 30;
if ($timeout <= 0) {
    $timeout = 30;
}
# --------------------  cache expiration time (minutes) - end ------------------
# -------------------------- load messages - begin -----------------------------
if (isset($input_vars['interface_lang']))
    if ($input_vars['interface_lang'])
        $input_vars['lang'] = $input_vars['interface_lang'];
if (!isset($input_vars['lang']))
    $input_vars['lang'] = default_language;
if (strlen($input_vars['lang']) == 0)
    $input_vars['lang'] = default_language;
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
# -------------------------- load messages - end -------------------------------


$feed = array_values($input_vars['feed']);
//$feed = explode('::',$input_vars['feed']);
$cnt = count($feed);
for ($i = 0; $i < $cnt; $i++) {
    if (!is_valid_url($feed[$i])) {
        unset($feed[$i]);
    }
}
$feed = array_values($feed);
$cnt = count($feed);
if ($cnt == 0) {
    die('Valid feed URLs not found');
}

// cache file path
$cache_file_path = template_cache_root . '/' . $this_site_info['dir'] . "/rss_aggregator_" . md5(join('::', $feed));


// try read data from cache
$content = get_cached_info($cache_file_path, 0);
if ($content) {
    echo $content;
    exit();
}

// if cache is expired or cache not found ...

$news = Array();
foreach ($feed as $url) {
    $xmlDoc = new DOMDocument();
    $xmlDoc->load($url);

    //    //get elements from "<channel>"
    //    $channels = $xmlDoc->getElementsByTagName('channel');
    //    foreach($channels as $channel){
    //        // $channel = $xmlDoc->getElementsByTagName('channel')->item(0);
    //        // $channel_title = $channel->getElementsByTagName('title')
    //        //                ->item(0)->childNodes->item(0)->nodeValue;
    //        // $channel_link = $channel->getElementsByTagName('link')
    //        //                ->item(0)->childNodes->item(0)->nodeValue;
    //        // $channel_desc = $channel->getElementsByTagName('description')
    //        //                ->item(0)->childNodes->item(0)->nodeValue;
    //    }
    //get and output "<item>" elements
    $x = $xmlDoc->getElementsByTagName('item');
    foreach ($x as $item) {
        try {
            $item_title = $item->getElementsByTagName('title');
            if ($item_title)
                $item_title = $item_title->item(0);
            if ($item_title)
                $item_title = $item_title->childNodes;
            if ($item_title)
                $item_title = $item_title->item(0);
            if ($item_title)
                $item_title = $item_title->nodeValue;
            if ($item_title)
                $item_title = @iconv($in_charset, $out_charset, $item_title);
        } catch (Exception $e) {
            $item_title = '';
        }

        try {
            $item_link = $item->getElementsByTagName('link')
                            ->item(0)->childNodes->item(0)->nodeValue;
        } catch (Exception $e) {
            $item_link = '';
        }
        try {
            $item_desc = $item->getElementsByTagName('description')
                            ->item(0)->childNodes->item(0)->nodeValue;
            $item_desc = @iconv($in_charset, $out_charset, $item_desc);
        } catch (Exception $e) {
            $item_desc = '';
        }
        try {
            $item_date = $item->getElementsByTagName('pubDate')
                            ->item(0)->childNodes->item(0)->nodeValue;
        } catch (Exception $e) {
            $item_date = '';
        }
        $news[] = Array(
            'content_present' => 1,
            'URL_view_details' => $item_link,
            'title' => $item_title,
            'abstract' => $item_desc,
            'last_change_date' => strtotime($item_date)
        );
    }

    // ATOM feed
    $x = $xmlDoc->getElementsByTagName('entry');
    foreach ($x as $item) {
        try {
            $item_title = $item->getElementsByTagName('title');
            if ($item_title)
                $item_title = $item_title->item(0);
            if ($item_title)
                $item_title = $item_title->childNodes;
            if ($item_title)
                $item_title = $item_title->item(0);
            if ($item_title)
                $item_title = $item_title->nodeValue;
            if ($item_title)
                $item_title = @iconv($in_charset, $out_charset, $item_title);
            //prn($item_title);
        } catch (Exception $e) {
            $item_title = '';
        }

        try {
            $item_link = $item->getElementsByTagName('link')->item(0)->getAttribute('href');
            //prn($item_link);
        } catch (Exception $e) {
            $item_link = '';
        }
        try {
            $item_date = $item->getElementsByTagName('updated')
                            ->item(0)->childNodes->item(0)->nodeValue;
        } catch (Exception $e) {
            $item_date = '';
        }
        try {
            $item_desc = $item->getElementsByTagName('content')
                            ->item(0)->childNodes->item(0)->nodeValue;
            $item_desc = @iconv($in_charset, $out_charset, $item_desc);
        } catch (Exception $e) {
            $item_desc = '';
        }
        $news[] = Array(
            'content_present' => 1,
            'URL_view_details' => $item_link,
            'title' => $item_title,
            'abstract' => $item_desc,
            'last_change_date' => strtotime($item_date)
        );
    }
}

// order news by date (last news on top)
function rssaggregator_compare($b, $a) {
    if ($a['last_change_date'] == $b['last_change_date']) {
        return 0;
    }
    return ($a['last_change_date'] < $b['last_change_date']) ? -1 : 1;
}

usort($news, "rssaggregator_compare");

// remove last news
$cnt = count($news);
for ($i = 0; $i < $cnt; $i++) {
    if ($i >= $rows) {
        unset($news[$i]);
    }
}
// prn($news);
// php hyphenator

run('site/page/page_view_functions');
#prn('$news_template',$news_template);
$content = process_template($template_path
        , Array(
    'text' => $txt
    , 'news' => $news
        ));
echo $content;

// save content to cache
set_cached_info($cache_file_path, $content);
?>
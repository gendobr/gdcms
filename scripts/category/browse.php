<?php
run('category/functions');
run('site/menu');

//------------------- site info - begin ----------------------------------------
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = 
    $input_vars['page_header'] = 
    $input_vars['page_content'] = text('Site_not_found');
    header("HTTP/1.0 404 Not Found");
    header("Location: /".md5(rand(1, 100000).'.html'));
    exit();
}
$input_vars['site_id'] = $this_site_info['id'];
//------------------- site info - end ------------------------------------------
//
//
//
// ------------------ get language - begin -------------------------------------
$input_vars['lang'] = $lang = get_language('lang,interface_lang');
global $txt;
$txt = load_msg($input_vars['lang']);
// prn($lang);
// ------------------ get language - end ---------------------------------------


global $main_template_name;
$main_template_name = '';


$this_category_info=category_info($input_vars);
if(!$this_category_info){
    header("HTTP/1.0 404 Not Found");
    header("Location: /".md5(rand(1, 100000).'.html'));
    exit('Page not found');
}
$category_id = $this_category_info['category_id'];
// -------------------- do redirect if needed - begin --------------------------
$url = trim(strip_tags($this_category_info['category_description']));
if (is_valid_url($url)) {
    header("Location: {$url}");
    return;
}
if (preg_match("/^(url|link|goto|redirect):/i",$url)) {
    $url=preg_replace("/^(url|link|goto|redirect):/i","",$url);
    header("Location: {$url}");
    return;
}
// -------------------- do redirect if needed - end ----------------------------



run('news/menu');
run('calendar/functions');
run('site/page/page_view_functions');


$categoryViewModel=new CategoryViewModel(
        $this_site_info,
        $this_category_info,
        $lang);


// ---------------------- draw - begin -----------------------------------------

$menu_groups = get_menu_items($site_id, 0, $lang);

// ------------- mark menu items - begin ---------------------------------------
$urls = Array();
foreach ($categoryViewModel->parents as $tmp) {
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
    if(!get_langstring($this_category_info['category_title_orig'], $lang_list[$i]['name'], true)){
        unset($lang_list[$i]);
        continue;
    }
    if(!isset($this_site_info['extra_setting']['lang'][$lang_list[$i]['name']])){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
    $lang_list[$i]['url'] = $lang_list[$i]['href']=
        str_replace(
            Array('{path}', '{lang}', '{site_id}', '{category_id}', '{category_code}'), 
            Array($this_category_info['path'], $lang_list[$i]['lang'], $this_category_info['site_id'], $this_category_info['category_id'], $this_category_info['category_code']), 
            \e::config('url_pattern_category'));
}
$lang_list=array_values($lang_list);
usort ( $lang_list , function($k1, $k2){
    $defaultLang=\e::config('default_language');
    $s1 = ($k1['name'] == $defaultLang?'0':'1').$k1['name'];
    $s2 = ($k2['name'] == $defaultLang?'0':'1').$k2['name'];
    return -strcmp($s2, $s1);
} );
// prn($lang_list);
//clearstatcache();
// if(isset($_REQUEST['v'])) phpinfo();,isset($_REQUEST['v'])

$template_name='template_category_browse';


$_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir']."/{$template_name}_{$category_id}.html";
if(!is_file($_template)){
    $_template='';
}
if(!$_template){
    $prnts=array_reverse($categoryViewModel->parents);
    foreach ($prnts as $tmp) {
        $_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir']."/{$template_name}_{$tmp['category_id']}.html";
        if(is_file($_template)){
            break;
        }else{
            $_template='';
        }
    }
    if(!$_template){
       $_template = site_get_template($this_site_info, $template_name);
    }
}



//echo $_template;

$category_events = new CategoryEvents2(
        $lang,
        $this_site_info,
        $this_category_info,
        isset($input_vars['event_start']) ? ( (int) $input_vars['event_start']) : 0,
        $input_vars);
// $category_events->init();
// prn($category_events->list);

$category_news=new CategoryNews(
        $lang,
        $this_site_info,
        $this_category_info,
        isset($input_vars['news_start']) ? ( (int) $input_vars['news_start']) : 0,
        $input_vars);



$vyvid = process_template($_template
    , Array(
      'category' => $categoryViewModel
    , 'news' => $category_news
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


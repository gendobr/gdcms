<?php
run('category/functions');
//------------------- site info - begin ----------------------------------------
run('site/menu');

$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = 
    $input_vars['page_header'] = 
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
$input_vars['site_id'] = $this_site_info['id'];
//------------------- site info - end ------------------------------------------
// ------------------ get language - begin -------------------------------------
$input_vars['lang'] = $lang = get_language('lang,interface_lang');
global $txt;
$txt = load_msg($input_vars['lang']);

// ------------------ get language - end ---------------------------------------


global $main_template_name;
$main_template_name = '';


$this_category_info=category_info($input_vars);
if(!$this_category_info){
    header("HTTP/1.0 404 Not Found");
    exit('Page not found');
}
$category_id = $this_category_info['category_id'];

//// -------------------- do redirect if needed - begin --------------------------
//if (is_valid_url($url = trim($this_category_info['category_description']))) {
//    header("Location: {$url}");
//    return;
//}
//// -------------------- do redirect if needed - end ----------------------------

run('news/menu');
run('calendar/functions');
run('site/page/page_view_functions');


$categoryViewModel=new CategoryViewModel(
        $this_site_info,
        $this_category_info,
        $lang);




// do search 
$search_results=Array();
$too_many=false;
if(isset($input_vars['keywords'])){
    $words=preg_split("/[; ,.!\t-]+/",$input_vars['keywords']);
    $where=Array();
    foreach($words as $word){
        if(strlen(trim($word))>0){
            $where[]=  " ( LOCATE('".\e::db_escape($word)."',category_description) OR LOCATE('".\e::db_escape($word)."',category_title) )";
        }
    }
    if( count($where) > 0 ){
        $sql="SELECT *
              FROM <<tp>>category 
              WHERE site_id={$this_site_info['id']}  AND is_visible
                AND ".join(' AND ',$where)."
                AND {$this_category_info['start']} <= `start` AND `finish` <= {$this_category_info['finish']} 
              LIMIT 0,101";
        
        
        
        $search_results=\e::db_getrows($sql);
        $cnt = count($search_results);
        for ($i = 0; $i < $cnt; $i++) {
            $ch_category_title = get_langstring($search_results[$i]['category_title'], $lang, true);
            if(!$ch_category_title){
                unset($search_results[$i]);
                continue;
            }

            $ch = &$search_results[$i];
            $ch['category_title'] = get_langstring($ch['category_title'], $lang, true);
            $ch['category_title_short'] = get_langstring($ch['category_title_short'], $lang, true);
            $ch['category_description'] = '';//strip_tags(get_langstring($ch['category_description'], $lang));
            $ch['category_description_short'] = strip_tags(get_langstring($ch['category_description_short'], $lang));
            
            if(is_valid_url($ch['category_description'])){
                $ch['category_description_short']="<a href=\"{$ch['category_description']}\">{$ch['category_description']}</a>";
            }
            $ch['URL'] = str_replace(
                    Array('{path}'   ,'{lang}','{site_id}','{category_id}','{category_code}'),
                    Array($ch['path'],$lang   ,$site_id   ,$ch['category_id'],$ch['category_code']),
                    \e::config('url_pattern_category'));
        }
        $search_results = array_values($search_results);
        
        
        // get parents for each child
        $cnt=count($search_results);
        $parentPaths=[];
        $pathCompare=function($a, $b){
            $la=strlen($a);
            $lb=strlen($b);
            if($la==$lb){
                return 0;
            }
            if($la<$lb){
                return -1;
            }
            return 1;
        };
        for ($i = 0; $i < $cnt; $i++) {
            $path=$search_results[$i]['path'];
            $paths=[];
            do{
                $paths[$path]='';
                $parentPaths[$path]='';
                $path=preg_replace("/[^\\/]+\$/",'',$path);
                $path=preg_replace("/\\/\$/",'',$path);
            }while(strlen($path)>0);
            uksort($paths, $pathCompare);
            $search_results[$i]['parents']=$paths;
        }
        // prn($search_results);
        // prn($parentPaths);
        $sql="SELECT * FROM <<tp>>category WHERE site_id=<<integer site_id>> AND path in(<<string[] path>>)";
        $result=\e::db_getrows($sql,['path'=>array_keys($parentPaths),'site_id'=>$site_id],false);
        $parents=[];
        foreach($result as $pa){
            $pa['category_title'] = get_langstring($pa['category_title'], $lang, true);
            $pa['category_title_short'] = get_langstring($pa['category_title_short'], $lang, true);
            $pa['category_description'] = '';// strip_tags(get_langstring($pa['category_description'], $lang));
            $pa['category_description_short'] = strip_tags(get_langstring($pa['category_description_short'], $lang));
            $pa['URL'] = str_replace(
                    Array('{path}'   ,'{lang}','{site_id}','{category_id}','{category_code}'),
                    Array($pa['path'],$lang   ,$site_id   ,$pa['category_id'],$pa['category_code']),
                    \e::config('url_pattern_category'));
            $parents[$pa['path']]=$pa;
        }
        // prn($parents);
        for ($i = 0; $i < $cnt; $i++) {
            if(!isset($search_results[$i]['parents'])){
                $search_results[$i]['parents']=[];
            }
            $keys=array_keys($search_results[$i]['parents']);
            foreach($keys as $key){
                $search_results[$i]['parents'][$key]=$parents[$key];
            }
        }
        // prn($search_results);
        
        if($cnt>=101){
            unset($search_results[100]);
            $too_many=true;
        }

    }
}




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
//prn($lang_list);
//clearstatcache();
// if(isset($_REQUEST['v'])) phpinfo();,isset($_REQUEST['v'])

$template_name='template_category_search';


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

$vyvid = process_template($_template
    , Array(
      'category' => $categoryViewModel
    , 'text' => $txt
    , 'site' => $this_site_info
    , 'lang' => $lang
    , 'search_results'=>$search_results
        
    , 'too_many' => $too_many
    , 'n_results' => count($search_results)
    , 'keywords' => (isset($input_vars['keywords'])?$input_vars['keywords']:'')
    , 'action' => \e::config('APPLICATION_URL')

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





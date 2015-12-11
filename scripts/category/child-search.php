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


$this_category_info=category_info($input_vars);
if(!$this_category_info){
    header("HTTP/1.0 404 Not Found");
    exit('Page not found');
}
$category_id = $this_category_info['category_id'];
// -------------------- do redirect if needed - begin --------------------------
if (is_valid_url($url = trim($this_category_info['category_description']))) {
    header("Location: {$url}");
    return;
}
// -------------------- do redirect if needed - end ----------------------------



run('lib/file_functions');
// cache info as file in the site dir
$tmp = get_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/category_{$category_id}_{$lang}.cache", 600);

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
        $ch_category_title = get_langstring($this_category_info['children'][$i]['category_title'], $lang, true);
        if(!$ch_category_title){
            unset($this_category_info['children'][$i]);
            continue;
        }
        
        $ch = &$this_category_info['children'][$i];
        $ch['category_title'] = get_langstring($ch['category_title'], $lang, true);
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
    $this_category_info['children']=array_values($this_category_info['children']);
    //prn($this_category_info['children']);
    // ------------------- get children - end ----------------------------------
    $tmp = Array('parents' => $this_category_info['parents'], 'children' => $this_category_info['children']);
    set_cached_info(template_cache_root . '/' . $this_site_info['dir'] . "/cache/category_{$category_id}_{$lang}.cache", $tmp);
}

// do search 
$search_results=Array();
$too_many=false;
if(isset($input_vars['keywords'])){
    $words=preg_split("/[; ,.!\t-]+/",$input_vars['keywords']);
    $where=Array();
    foreach($words as $word){
        $where[]=  " ( LOCATE('".DbStr($word)."',category_description) OR LOCATE('".DbStr($word)."',category_title) )";
    }
    if( count($where) > 0 ){
        $sql="SELECT *
        FROM {$table_prefix}category 
        WHERE site_id={$this_site_info['id']}  AND is_visible
        AND ".join(' AND ',$where)."
        AND {$this_category_info['start']} <= `start` AND `finish` <= {$this_category_info['finish']} 
        LIMIT 0,101";
        
        
        
        $search_results=db_getrows($sql);
        $cnt = count($search_results);
        
        for ($i = 0; $i < $cnt; $i++) {
            $ch_category_title = get_langstring($search_results[$i]['category_title'], $lang, true);
            if(!$ch_category_title){
                unset($search_results[$i]);
                continue;
            }

            $ch = &$search_results[$i];
            $ch['category_title'] = get_langstring($ch['category_title'], $lang, true);
            $ch['category_description'] = strip_tags(get_langstring($ch['category_description'], $lang));
            if(is_valid_url($ch['category_description'])){
                $ch['category_min_description']="<a href=\"{$ch['category_description']}\">{$ch['category_description']}</a>";
            }else{
                $ch['category_min_description']=shorten($ch['category_description'],128);
            }
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
        $search_results = array_values($search_results);
        if($cnt>101){
            unset($search_results[100]);
            $too_many=true;
        }

    }
}


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
    if(!get_langstring($this_category_info['category_title_orig'], $lang_list[$i]['name'], true)){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
    $lang_list[$i]['url'] = $lang_list[$i]['href'];
}
$lang_list=array_values($lang_list);
//prn($lang_list);
//clearstatcache();
// if(isset($_REQUEST['v'])) phpinfo();,isset($_REQUEST['v'])

$template_name='template_category_search';


$_template = sites_root.'/'.$this_site_info['dir']."/{$template_name}_{$category_id}.html";
if(!is_file($_template)){
    $_template='';
}
if(!$_template){
    $prnts=array_reverse($this_category_info['parents']);
    foreach ($prnts as $tmp) {
        $_template = sites_root.'/'.$this_site_info['dir']."/{$template_name}_{$tmp['category_id']}.html";
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
      'category' => $this_category_info
    , 'search_results' => $search_results
    , 'too_many' => $too_many
    , 'n_results' => count($search_results)
    , 'keywords' => (isset($input_vars['keywords'])?$input_vars['keywords']:'')
    , 'action' => site_public_URL.'/index.php'
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

<?php
/*
  View news for site
  arguments are
    $site_id     - site identifier, integer, mandatory
    $lang        - interface language, char(3)
    $category_id - category identifier
    $category_id - category identifier

    if there is a file news_view_list.html in the site roto directory
    it will be treated as Smarty template and used instead of default one.
*/

include(\e::config('SCRIPT_ROOT').'/news/get_public_list2.php');


$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}
//prn($this_site_info);

$news=new CmsNewsViewer($input_vars);
// exit('1');


/*
   'paging_links'=>$pages
  ,'text'=>$txt
  ,'news'=>$list_of_news
  ,'news_found' => $news_found
  ,'news_date_selector'=>$news_date_selector
  ,'news_keywords_selector'=>$news_keywords_selector
  ,'news_category_selector'=>$category_selector
  ,'news_tags'=>$tag_selector
*/



//------------------------ draw using SMARTY template - begin ----------------
  $news_template = site_get_template($this_site_info,'template_news_view_list2');

  $rss_url = site_root_URL . "/index.php?action=news/rss&start=0&" . query_string('action|start');

  global $txt;
  $txt = load_msg($news->getLang());
  //prn($txt);

  
# -------------------- get list of page languages - begin --------------------

$tmp = \core\fileutils::get_cached_info(\e::config('CACHE_ROOT') . '/' . $this_site_info['dir'] . "/cache/news_lang_{$site_id}.cache", cachetime);
if (!$tmp) {
    $tmp = \e::db_getrows("SELECT DISTINCT lang
                     FROM {$table_prefix}news  AS ne
                     WHERE ne.site_id={$site_id}
                       AND ne.cense_level>={$this_site_info['cense_level']}");
    \core\fileutils::set_cached_info(\e::config('CACHE_ROOT') . '/' . $this_site_info['dir'] . "/cache/news_lang_{$site_id}.cache", $tmp);
}

$existing_languages = Array();
foreach ($tmp as $tm) {
    $existing_languages[$tm['lang']] = $tm['lang'];
}
# prn($existing_languages);
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
    $lang_list[$i]['url'] = $news->url(Array('lang'=>$lang_list[$i]['name']));
    $lang_list[$i]['href']=$lang_list[$i]['url'];

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
$lang_list = array_values($lang_list);
//prn($lang_list);
//------------------------ get list of languages - end -------------------------

$menu_groups = get_menu_items($this_site_info['id'], 0, $news->getLang());
// prn('$menu_groups',$menu_groups);
// mark current page URL
$prefix_length = strlen(\e::config('url_prefix_news_list'));

foreach ($menu_groups as $kmg => $mg) {
    foreach ($mg['items'] as $kmi => $mi) {
        if (\e::config('url_prefix_news_list') == substr($mi['url'], $prefix_length)) {
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
//
  #prn('$news_template',$news_template);

  $startTime=  microtime(true);
  $vyvid=process_template( $news_template
                    ,Array(
                           'news'=>$news
                          ,'rss_url'=>$rss_url
                          ,'txt'=>$txt
                     )
         );
  $vyvid.= '<div style="opacity:0.2; font-size:80%;">'.(microtime(true) - $startTime)."</div>";
  $this_site_info['title']=get_langstring($this_site_info['title'],$news->getLang());
  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$txt['News']
                                               ,'content'=>$vyvid
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$news->getLang()
                                               ,'editURL'=>site_URL.'?action=news/list&site_id='.$site_id
                                          )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

$GLOBALS['main_template_name']='';

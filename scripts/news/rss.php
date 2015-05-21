<?php

/*
  Generate "Latest news" block
  arguments are
  $site_id - site identifier, integer, mandatory
  $lang    - interface language, char(3), mandatory (rus|ukr|eng)
  $rows    - number of rows< integer, optional
  $abstracts =yes|no (default is "yes")
  $template=<template file name>, file name (extension is ".html"),
  template placed in site root directory.
  $date=asc if the oldest messages must appear at top of the list
  $date=desc if the newest messages must appear at top of the list

  $category=<category_id> restrict category
 */

include(script_root . '/news/get_public_list2.php');


$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}


$news = new CmsNewsViewer($input_vars);
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

global $txt;
$txt = load_msg($news->getLang());



//run('site/page/page_view_functions');
# ---------------------- choose template - begin -------------------------------


# check if template name is posted
if (isset($input_vars['template'])) {
    $input_vars['template']=preg_replace("/[^a-z0-9_-]/i",'_',$input_vars['template']);
    $news_template = sites_root . '/' . $this_site_info['dir'] . '/' . $input_vars['template'] . '.html';
    if (!is_file($news_template)) {
        $news_template = false;
    }
    if (!$news_template) {
        $news_template = sites_root . '/' . $this_site_info['dir'] . '/' . $input_vars['template'];
    }
    if (!is_file($news_template)) {
        $news_template = false;
    }
} else {
    $news_template = false;
}


# check if site news template name exists
if (!$news_template) {
    $news_template = site_get_template($this_site_info, 'template_rss');
}
# ---------------------- choose template - end ---------------------------------

//prn(iconv ( site_charset, 'UTF-8', $this_site_info['title'] ));
//prn('$news_template',$news_template);
//prn($list_of_news);
$vyvid = process_template($news_template,
    Array(
        'text' => $txt
      , 'news' => $news
      , 'site' => $this_site_info
      , 'new_list_url' => $news->url(Array())
    )
);

if (strlen($vyvid) == 0) {
    echo '';
    return '';
}


$GLOBALS['main_template_name'] = '';
// remove from history
nohistory($input_vars['action']);
// echo '<pre>'.checkStr($vyvid).'</pre>';die();
header("Content-Type: application/rss+xml; charset=UTF-8");
echo $vyvid;




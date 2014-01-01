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

include(script_root.'/news/get_public_list.php');
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



global $main_template_name; $main_template_name='';

//run('site/page/page_view_functions');

# ---------------------- choose template - begin -------------------------------

# check if template name is posted
  if(isset($_REQUEST['template']))
  {
    $news_template = sites_root.'/'.$this_site_info['dir'].'/'.$_REQUEST['template'].'.html';
    if(!is_file($news_template)) $news_template=false;
    if(!$news_template) $news_template = sites_root.'/'.$this_site_info['dir'].'/'.$_REQUEST['template'];
    if(!is_file($news_template)) $news_template=false;
  }
  else $news_template=false;


# check if site news template name exists
  if(!$news_template) $news_template = sites_root.'/'.$this_site_info['dir'].'/template_rss.html';
  if(!is_file($news_template)) $news_template=false;

# use default system template
  #prn('$news_template',$news_template);
  if(!$news_template) $news_template = 'cms/template_rss';
# ---------------------- choose template - end ---------------------------------



# ---------------- convert string from cp1251 to utf8 - begin ------------------
# sample usage (in Smarty template)
#  {utf8 from=$site.title}
#
function ch_encoding($params)
{
  extract($params);
  return iconv(site_charset, 'UTF-8',$from);
}
# ---------------- convert string from cp1251 to utf8 - end --------------------
  //prn(iconv ( site_charset, 'UTF-8', $this_site_info['title'] ));
  //prn('$news_template',$news_template);
  //prn($list_of_news);
  $vyvid=process_template( $news_template
                                ,Array(
                                  'paging_links'=>$pages
                                 ,'text'=>$txt
                                 ,'news'=>$list_of_news
                                 ,'news_found' => $news_found
                                 ,'site'=>$this_site_info
                                 ,'new_list_url'=>url_prefix_news_list."site_id={$this_site_info['id']}&lang={$lang}"
                                )
                                ,Array('ch_encoding'));

if(strlen($vyvid)==0) {echo '';return '';}

//echo '<pre>'.checkStr($vyvid).'</pre>';die();

//header("Content-type:application/rss+xml");
header("Content-Type: application/rss+xml; charset=UTF-8");
echo $vyvid;


// remove from history
   nohistory($input_vars['action']);

?>
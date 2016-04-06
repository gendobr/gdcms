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

include(\e::config('SCRIPT_ROOT').'/news/get_public_list.php');

//prn($this_site_info);
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
  $news_template = site_get_template($this_site_info,'template_news_view_list');



  //prn($txt);

  #prn('$news_template',$news_template);
  $vyvid=process_template( $news_template
                    ,Array(
                           'paging_links'=>$pages
                          ,'text'=>$txt
                          ,'news'=>$list_of_news
                          ,'news_found' => $news_found
                          ,'news_date_selector'=>$news_date_selector
                          ,'news_keywords_selector'=>$news_keywords_selector
                          ,'news_category_selector'=>$category_selector
                          ,'news_category'=>$categories
                          ,'news_tags'=>$tag_selector
                          ,'start'=>$start+1
                          ,'finish'=>$start+count($list_of_news)
                          ,'rss_url'=>$rss_url
                     )
         );
  $this_site_info['title']=get_langstring($this_site_info['title'],$input_vars['lang']);
  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$txt['News']
                                               ,'content'=>$vyvid
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$input_vars['lang']
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

global $main_template_name; $main_template_name='';
?>
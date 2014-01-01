<?php
/*
  Generate "Latest news" Javascript block
  arguments are
    $site_id - site identifier, integer, mandatory
    $lang    - interface language, char(3), mandatory
    $rows    - number of rows< integer, optional
    $abstracts =yes|no
    
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


   $vyvid = '';
   $vyvid .= "document.writeln('<style type=\"text/css\">');\n";
   $vyvid .= "document.writeln('<!-- ');\n";
   $vyvid .= "document.writeln('a.nln div{padding-top:3px;padding-bottom:3px;} ');\n";
   $vyvid .= "document.writeln('-->');\n";
   $vyvid .= "document.writeln('</style>');\n";

  //------------------ draw latest news - begin --------------------------------
    function repl($str)
    {
      $tor=$str;
      $tor=str_replace("\"","\\\"",$tor);

      $tor=str_replace("'","&#039;",$tor);
      $tor=str_replace(Array("\n","\r"),' ',$tor);
      return $tor;
    }
    if(isset($list_of_news[0]))
    {
      $row=$list_of_news[0];
      $news_page_url=sites_root_URL."/news_details.php?news_id={$row['id']}&lang={$lang}";
      $title_full  = repl($row['title']);

      //$vyvid .= "document.writeln('<a class=nln href=$news_page_url title=\"{$title_full}\"><div><b>".date('d.m.Y',strtotime($row['last_change_date'])).' - '.repl($row['title'])."</b></div></a>');\n";
        $vyvid .= "document.writeln('<a class=nln href=$news_page_url title=\"{$title_full}\"><div><b>".repl($row['title'])."</b></div></a>');\n";
      if($show_abstracts)
      {
        $vyvid .= "document.writeln('<div style=\"padding-left:5px;\">".repl($row['content'])."</div>');\n";
      }
    }
    unset($list_of_news[0]);
  //------------------ draw latest news - end ----------------------------------
    foreach($list_of_news as $row)
    {
      $news_page_url=sites_root_URL."/news_details.php?news_id={$row['id']}&lang={$lang}";
      $title_short = date('d.m.Y',strtotime($row['last_change_date']))." - ".repl(shorten($row['title'],40));
      $row['last_change_date'] = date('d.m.Y',strtotime($row['last_change_date']));
      //$title_full  = repl($row['last_change_date'].' - '.$row['title']);
      $title_full  = repl($row['title']);
      $towrite     = "<a class=nln href=\"{$news_page_url}\" title=\"{$title_full}\"><div>{$title_full}</div></a>";
      $vyvid .= "document.writeln('". str_replace("'","\\'",$towrite)."');\n";
    }
  //--------------------------- list of news - end -----------------------------
//------------------- get list of news - end -----------------------------------

echo $vyvid;

global $main_template_name; $main_template_name='';

// remove from history
   nohistory($input_vars['action']);


?>
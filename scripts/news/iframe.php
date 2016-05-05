<?php
/*
  Generate "Latest news" IFRAME block
  arguments are
    $site_id - site identifier, integer, mandatory
    $lang    - interface language, char(3), mandatory
    $rows    - number of rows< integer, optional
    $abstracts =yes|no
    
*/

$debug=false;
# -------------------- number of news in the block - begin ---------------------
  $rows=10;
  if(isset($input_vars['rows'])) $rows=(int)$input_vars['rows'];
  if($rows<=0 or $rows>100) $rows=10;
# -------------------- number of news in the block - end -----------------------

# -------------------- if abstracts should be shown - begin --------------------
  $show_abstracts = true;
  if(isset($input_vars['abstracts'])) if($input_vars['abstracts']=='no') $show_abstracts = false;
# -------------------- if abstracts should be shown - end ----------------------


if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])   ) $input_vars['lang']=\e::config('default_language');
if(strlen($input_vars['lang'])==0) $input_vars['lang']=\e::config('default_language');
$input_vars['lang']      = get_language('lang');

# load messages
  $txt=load_msg($input_vars['lang']);

//------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
  //prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     die();
     $input_vars['page_title']   = $txt['Site_not_found'];
     $input_vars['page_header']  = $txt['Site_not_found'];
     $input_vars['page_content'] = $txt['Site_not_found'];
     return 0;
  }
//------------------- get site info - end --------------------------------------


//------------------- get list of news - begin ---------------------------------

  $lang  = \e::db_escape($input_vars['lang']);
  $start = isset($_REQUEST['start'])?(int)$_REQUEST['start']:0;
  //--------------------------- list of news - begin ---------------------------
  /*
  $query="SELECT MIN(ABS(STRCMP(ns.lang,'{$lang}'))) AS e1
                ,ns.id AS id1
                ,ne.id AS id2
                ,ne.lang AS lang2
                ,ABS(STRCMP(ne.lang,'{$lang}')) AS e2
                ,ne.id
                ,ne.lang
                ,ne.title
                ,ne.abstract AS content
                ,ne.last_change_date
          FROM <<tp>>news AS ns,
               <<tp>>news AS ne
          WHERE ns.site_id={$site_id}
            AND ne.site_id={$site_id}
            AND ns.cense_level>={$this_site_info['cense_level']}
            AND ne.cense_level>={$this_site_info['cense_level']}
          GROUP BY id1, id2,lang2
          HAVING id1=id2 AND e1=e2
          ORDER BY ne.last_change_date DESC
          LIMIT $start,".$rows;
  */

  $date_sort='DESC';
  if(isset($_REQUEST['date'])) if(strtolower($_REQUEST['date'])=='asc') $date_sort='ASC';

# ----------------------- get list of news - begin -----------------------------
  $query="SELECT SQL_CALC_FOUND_ROWS 
                 ne.id
                ,ne.lang
                ,ne.title
                ,ne.abstract
                ,ne.last_change_date
          FROM <<tp>>news AS ne
          WHERE ne.site_id={$site_id}
            AND ne.cense_level>={$this_site_info['cense_level']}
            AND ne.lang='{$lang}'
          ORDER BY ne.last_change_date $date_sort
          LIMIT $start,".$rows;
    $list_of_news = \e::db_getrows($query);
    if($debug) prn($query,$list_of_news);
    # -------------------- adjust list - begin ---------------------------------
      $cnt=count($list_of_news);
      for($i=0;$i<$cnt;$i++)
      {
        if($list_of_news[$i]['content_present']==1)
        {
          $list_of_news[$i]['URL_view_details']=sites_root_URL."/news_details.php?news_id={$list_of_news[$i]['id']}&lang={$lang}";
        }
        else
        {
          $list_of_news[$i]['URL_view_details']='';
        }
      }
    # -------------------- adjust list - end -----------------------------------

# ----------------------- get list of news - end -------------------------------


# --------------------------- list of pages - begin ----------------------------
    $query="SELECT FOUND_ROWS() AS n_records;";
    $num = \e::db_getonerow($query);
    // prn($query,$num);
    $news_found = $num = (int)$num['n_records'];
    $pages = Array();
    for($i=0;$i<$num; $i=$i+\e::config('rows_per_page'))
    {
        if( $i==$start ) $to='<b>['.(1+$i/\e::config('rows_per_page')).']</b>'; else $to=(1+$i/\e::config('rows_per_page'));
        $pages[]=Array(
                    'URL'=>$_SERVER['PHP_SELF']."?start={$i}&".query_string('^start$|^'.session_name().'$|^action$')
                   ,'innerHTML' => $to
                 );
    }

# --------------------------- list of pages - end ------------------------------







run('site/page/page_view_functions');
  $news_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/template_news_view_list.html';
  #prn('$news_template',$news_template);
  if(!is_file($news_template)) $news_template = 'cms/template_news_view_list';

  #prn('$news_template',$news_template);
  $vyvid=process_template( $news_template
                                ,Array(
                                  'paging_links'=>$pages
                                 ,'text'=>$txt
                                 ,'news'=>$list_of_news
                                 ,'news_found' => $news_found
                                 ,'all_news_url'=>$all_news_url
                                ));
#prn(Array(
#                                  'paging_links'=>$pages
#                                 ,'text'=>$txt
#                                 ,'news'=>$list_of_news
#                                 ,'news_found' => $news_found
#                                ));

echo $vyvid;
global $main_template_name; $main_template_name='';

// remove from history
   nohistory($input_vars['action']);


?>
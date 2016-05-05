<?php
/*
   link selector for FreeRTE
*/
$GLOBALS['main_template_name']='';
run('site/menu');

// remove from history
   nohistory($input_vars['action']);


# site info
  $this_site_info = get_site_info($input_vars['site_id']);
  //prn('$this_site_info=',$this_site_info);

//------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($this_site_info['id']);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =$text['Access_denied'];
     $input_vars['page_header'] =$text['Access_denied'];
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
//------------------- get permission - end -------------------------------------

//prn($input_vars);
//prn($text);

if(!isset($input_vars['view'])) $input_vars['view']='';


$tor='';
$url_prefix="index.php?".query_string('^view$');
switch($input_vars['view'])
{
    case 'news':


        $tor.="
        <a href=\"index.php?action=site/page/link_selector&site_id={$this_site_info['id']}\">���� URL</a>/ {$text['News']}<br><br>
        <form action=index.php>
        ".hidden_form_elements('^filter_')."
        <input type=text name=filter_kw value=\"".htmlspecialchars(isset($input_vars['filter_kw'])?$input_vars['filter_kw']:'')."\">
        <input type=submit value=\"{$text['Search']}\">
        </form>
        ";

        # ------------------------- keyword restriction - begin --------------------
        $news_keywords_restriction='';
        if(isset($input_vars['filter_kw']) && (strlen($news_keywords=trim($input_vars['filter_kw']))>0))
        {
             $news_keywords_restriction='';
             $news_keywords_restriction=explode(' ',trim($news_keywords));
             $cnt=count($news_keywords_restriction);
             $tmp="LOCATE('%s',concat(ifnull(ne.title,''),' ',ifnull(ne.content,''),' ',ifnull(ne.abstract,'')))";
             for($i=0;$i<$cnt;$i++)
             {
                  if(strlen($news_keywords_restriction[$i])>0)
                  {
                     $news_keywords_restriction[$i]=sprintf($tmp,\e::db_escape($news_keywords_restriction[$i]));
                  }
                  else unset($news_keywords_restriction[$i]);
             }
             if(count($news_keywords_restriction)>0)
             {
                  $news_keywords_restriction=' AND '.join(' AND ',$news_keywords_restriction);
             }
             else $news_keywords_restriction='';
        }
        # ------------------------- keyword restriction - begin --------------------
        $category_restriction='';
        $date_order='';
        if(!isset($input_vars['start'])) $input_vars['start']=0;
        $start=abs(round(1*$input_vars['start']));

        $rows=50;

        $query="SELECT SQL_CALC_FOUND_ROWS
                   ne.id
                  ,ne.lang
                  ,ne.title
                  ,ne.abstract AS abstract
                  ,ne.last_change_date
                  ,IF(LENGTH(TRIM(ne.content))>0,1,0) as content_present
            FROM {$table_prefix}news AS ne
                 $category_restriction
            WHERE ne.site_id={$this_site_info['id']}
              AND ne.cense_level>={$this_site_info['cense_level']}
              AND ne.lang='{$_SESSION['lang']}'
              $news_keywords_restriction
            ORDER BY ne.last_change_date $date_order
            LIMIT $start,$rows";
        $list_of_news = \e::db_getrows($query);
        //prn($query,$list_of_news);
        //prn($query);

        # -------------------- adjust list - begin ---------------------------------
          $cnt=count($list_of_news);
          for($i=0;$i<$cnt;$i++)
          {
              $list_of_news[$i]['URL_view_details']=url_prefix_news_details."news_id={$list_of_news[$i]['id']}&lang={$list_of_news[$i]['lang']}";
          }
        # -------------------- adjust list - end -----------------------------------

        # --------------------------- list of pages - begin --------------------------
          $url_prefix_list=site_root_URL.'/index.php?'.query_string('^start$|^'.session_name().'$').'&start=';
          $query="SELECT FOUND_ROWS() AS n_records;";
          $num = \e::db_getonerow($query);
          // prn($query,$num);
          $news_found = $num = (int)$num['n_records'];
          $pages = Array();
          $imin=max(0,$start-10*\e::config('rows_per_page'));
          $imax=min($num,$start+10*\e::config('rows_per_page'));
          if($imin>0)  $pages[]=Array( 'URL'=>$url_prefix_list."0" ,'innerHTML' => '[1] ...' );

          for($i=$imin;$i<$imax; $i=$i+\e::config('rows_per_page'))
          {
                if( $i==$start ) $to='<b>['.(1+$i/\e::config('rows_per_page')).']</b>'; else $to=(1+$i/\e::config('rows_per_page'));
                $pages[]=Array(
                            'URL'=>$url_prefix_list."{$i}"
                           ,'innerHTML' => $to
                         );
          }

          if($imin<$num)
          {
            $last_page=floor($num/\e::config('rows_per_page'));
            if($last_page>0)
            $pages[]=Array(
              'URL'=>$url_prefix_list.($last_page*\e::config('rows_per_page'))
             ,'innerHTML' => "... [".($last_page+1)."]"
            );
          }
        # --------------------------- list of pages - end ----------------------------

        # -------------------- draw - begin ------------------------------------------
          foreach($list_of_news as $nw)
          {
              $tor.="<div><a href=\"javascript:void(insert_link('{$nw['URL_view_details']}'));\">{$nw['lang']} : {$nw['title']}</a></div>";
          }

          if(count($pages)>1)
          {
              $tor.="{$text['Pages']}:";
              foreach($pages as $pg)
              {
                  $tor.=" <a href=\"{$pg['URL']}\">{$pg['innerHTML']}</a> ";
              }
          }
        # -------------------- draw - end --------------------------------------------
    break;

    case 'pages':


        $tor.="
        <a href=\"index.php?action=site/page/link_selector&site_id={$this_site_info['id']}\">���� URL</a>/ {$text['Pages']}<br><br>

        <form action=index.php>
        ".hidden_form_elements('^filter_')."
        <input type=text name=filter_kw value=\"".htmlspecialchars(isset($input_vars['filter_kw'])?$input_vars['filter_kw']:'')."\">
        <input type=submit value=\"{$text['Search']}\">
        </form>
        ";

        # ------------------------- keyword restriction - begin --------------------
        $page_keywords_restriction='';
        if(isset($input_vars['filter_kw']) && (strlen($page_keywords=trim($input_vars['filter_kw']))>0))
        {
             $page_keywords_restriction='';
             $page_keywords_restriction=explode(' ',trim($page_keywords));
             $cnt=count($page_keywords_restriction);
             $tmp="LOCATE('%s',concat(ifnull(pg.title,''),' ',ifnull(pg.content,''),' ',ifnull(pg.abstract,'')))";
             for($i=0;$i<$cnt;$i++)
             {
                  if(strlen($page_keywords_restriction[$i])>0)
                  {
                     $page_keywords_restriction[$i]=sprintf($tmp,\e::db_escape($page_keywords_restriction[$i]));
                  }
                  else unset($page_keywords_restriction[$i]);
             }
             if(count($page_keywords_restriction)>0)
             {
                  $page_keywords_restriction=' AND '.join(' AND ',$page_keywords_restriction);
             }
             else $page_keywords_restriction='';
        }
        # ------------------------- keyword restriction - begin --------------------
        $category_restriction='';
        $date_order='';
        if(!isset($input_vars['start'])) $input_vars['start']=0;
        $start=abs(round(1*$input_vars['start']));

        $rows=50;

        $query="SELECT SQL_CALC_FOUND_ROWS
                   pg.id
                  ,pg.lang
                  ,pg.title
                  ,pg.abstract AS abstract
                  ,pg.last_change_date
                  ,pg.path
                  ,IF(LENGTH(TRIM(pg.content))>0,1,0) as content_present
            FROM {$table_prefix}page AS pg
                 $category_restriction
            WHERE pg.site_id={$this_site_info['id']}
              $page_keywords_restriction
            ORDER BY pg.last_change_date $date_order
            LIMIT $start,$rows";
         //     AND pg.cense_level>={$this_site_info['cense_level']}
         //     AND pg.lang='{$_SESSION['lang']}'

        $list_of_pages = \e::db_getrows($query);
        //prn($query,$list_of_news);
        //prn($query);

        # -------------------- adjust list - begin ---------------------------------
          $cnt=count($list_of_pages);
          for($i=0;$i<$cnt;$i++)
          {
              $pref=ereg_replace('/+$','',"{$this_site_info['url']}/{$list_of_pages[$i]['path']}/");
              $list_of_pages[$i]['URL_view_details']="$pref/{$list_of_pages[$i]['id']}.{$list_of_pages[$i]['lang']}.html";
          }
        # -------------------- adjust list - end -----------------------------------

        # --------------------------- list of pages - begin --------------------------
          $url_prefix_list=site_root_URL.'/index.php?'.query_string('^start$|^'.session_name().'$').'&start=';
          $query="SELECT FOUND_ROWS() AS n_records;";
          $num = \e::db_getonerow($query);
          // prn($query,$num);
          $news_found = $num = (int)$num['n_records'];
          $pages = Array();
          $imin=max(0,$start-10*\e::config('rows_per_page'));
          $imax=min($num,$start+10*\e::config('rows_per_page'));
          if($imin>0)  $pages[]=Array( 'URL'=>$url_prefix_list."0" ,'innerHTML' => '[1] ...' );

          for($i=$imin;$i<$imax; $i=$i+\e::config('rows_per_page'))
          {
                if( $i==$start ) $to='<b>['.(1+$i/\e::config('rows_per_page')).']</b>'; else $to=(1+$i/\e::config('rows_per_page'));
                $pages[]=Array(
                            'URL'=>$url_prefix_list."{$i}"
                           ,'innerHTML' => $to
                         );
          }

          if($imin<$num)
          {
            $last_page=floor($num/\e::config('rows_per_page'));
            if($last_page>0)
            $pages[]=Array(
              'URL'=>$url_prefix_list.($last_page*\e::config('rows_per_page'))
             ,'innerHTML' => "... [".($last_page+1)."]"
            );
          }
        # --------------------------- list of pages - end ----------------------------

        # -------------------- draw - begin ------------------------------------------
          foreach($list_of_pages as $pg)
          {
              $tor.="<div><a href=\"javascript:void(insert_link('{$pg['URL_view_details']}'));\">{$pg['lang']} : {$pg['title']}</a></div>";
          }

          if(count($pages)>1)
          {
              $tor.="{$text['Pages']}:";
              foreach($pages as $pg)
              {
                  $tor.=" <a href=\"{$pg['URL']}\">{$pg['innerHTML']}</a> ";
              }
          }
        # -------------------- draw - end --------------------------------------------
    break;

    default:
        $tor.="
        <b>���� URL</b><br><br>
        ";
        if($this_site_info['is_poll_enabled'])
        {
           $tor.="<a href=\"#\" onclick=\"insert_link('".site_URL."?action=poll/ask&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}'); return false;\">{$text['Polls_manage']}</a><br>";
        }
        if($this_site_info['is_forum_enabled'])
        {
           $tor.="<a href=\"#\" onclick=\"insert_link('".site_URL."?action=forum/forum&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}'); return false;\">{$text['View_forums']}</a><br>";
        }
        if($this_site_info['is_gb_enabled'])
        {
           $tor.="<a href=\"#\" onclick=\"insert_link('".site_URL."?action=gb/guestbook&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}'); return false;\">{$text['View_Guestbook']}</a><br>";
        }
        if($this_site_info['is_gallery_enabled'])
        {
           $tor.="<a href=\"#\" onclick=\"insert_link('".site_URL."?action=gallery/photogallery&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}'); return false;\">{$text['image_gallery_view']}</a><br>";
        }
        if($this_site_info['is_news_line_enabled'])
        {
           $tor.="<a href=\"#\" onclick=\"insert_link('".site_URL."?action=news/view&site_id=&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}'); return false;\">{$text['View_news']}</a><br>";
        }

        $tor.="<a href=\"index.php?".query_string('view')."&view=news\">{$text['News']}</a> ... <br>";
        $tor.="<a href=\"index.php?".query_string('view')."&view=pages\">{$text['Pages']}</a> ... <br>";

    break;
}
echo $tor;
?>
<script>
function _(id){return window.opener.document.getElementById(id);}
function insert_link(url){    _('url').value=url;window.close();}
</script>
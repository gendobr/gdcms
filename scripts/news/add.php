<?php
/*
  Adding news to selected site
  Argument is $site_id  - site identifier, integer, mandatory
              $news_id  - news identifier, integer, optional
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/

// remove from history
   nohistory($input_vars['action']);



run('site/menu');


$debug=false;
//------------------- site info - begin ----------------------------------------
  $site_id = (int) $input_vars['site_id'];
  $this_site_info = get_site_info($site_id);
  if($debug) prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- get site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
$user_level = get_level($site_id);
if($user_level==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------

//------------------- news info (optional) - begin -----------------------------
  $news_id   = (int)$input_vars['news_id'];
  $news_lang = isset($input_vars['news_lang'])?get_language('news_lang'):'';

  $query = "SELECT * FROM <<tp>>news WHERE id={$news_id} AND site_id={$site_id}";
  if(strlen($news_lang)>0) $query .= " AND lang='".\e::db_escape($news_lang)."'";

  $this_news_info=\e::db_getonerow($query);
  $this_news_info['id'] = checkInt($this_news_info['id']);
  if($debug) prn('$this_news_info',$this_news_info);
//------------------- news info (optional) - end -------------------------------

//-------------------- add page - begin ----------------------------------------
  if($this_news_info['id']>0)
  {
    // add the same news in another language

    //-------------------- get existing page languages - begin -----------------
      $query="SELECT lang FROM <<tp>>news WHERE id={$news_id}";
      $tmp=\e::db_getrows($query);
      // prn($tmp);
      $existins_langs=Array(0=>'');
      foreach($tmp as $lng) $existins_langs[]=$lng['lang'];
    //-------------------- get existing page languages - end -------------------

    //-------------------- get available languages - begin ---------------------
      // $query="SELECT id FROM <<tp>>languages WHERE is_visible=1 AND id NOT IN('".join("','",$existins_langs)."') LIMIT 0,1";
      // prn($query);
      // $tmp=\e::db_getonerow($query);
      $langs = site_get_languages($this_site_info);
	    unset($tmp);
      foreach($langs as $ln){
	    if(!in_array($ln['id'],$existins_langs)){
    		$tmp=$ln;
	    }
	}
      // prn($tmp);
    //-------------------- get available languages - end -----------------------
    if(isset($tmp) && strlen($tmp['id'])>0)
    {
      $query = "INSERT INTO <<tp>>news(
                    id
                   ,lang
                   ,site_id
                   ,title
                   ,cense_level
                   ,last_change_date
                   ,abstract
                   ,content
                   ,tags
                   ,creation_date
                   ,news_icon
                   , weight)
                values(
                    $news_id
                   ,'{$tmp['id']}'
                   ,$site_id
                   ,'".\e::db_escape($text['Add_translation'].' : '.$this_news_info['title'])."'
                   , 0
                   , '".\e::db_escape($this_news_info['last_change_date'])."'
                   ,'".\e::db_escape($this_news_info['abstract'])."'
                   ,'".\e::db_escape($this_news_info['content'])."'
                   ,'".\e::db_escape($this_news_info['tags'])."'
                   ,NOW()
                   ,'".\e::db_escape($this_news_info['news_icon'])."'
                   , 0)";
      // prn($query);
      \e::db_execute($query);

      // get news lang
      $news_lang=$tmp['id'];
    }
  }
  else
  {
    // create new news


    // calculate news id
    $query = "SELECT max(id) AS newid FROM <<tp>>news";
    $newid=\e::db_getonerow($query);
    $news_id=$newid=1+(int)$newid['newid'];

    // insert new record
    $query = "INSERT INTO <<tp>>news(id, lang, site_id, title, cense_level, last_change_date,creation_date, weight)
              values($newid, '".\e::config('default_language')."', $site_id, '{$text['New_page']}',0, NOW(),NOW(),0)";
    \e::db_execute($query);

    // get news lang
    $news_lang=\e::config('default_language');
  }
//-------------------- add page - end ------------------------------------------

if(isset($input_vars['return']) && strlen($input_vars['return'])>0)
{
    header("Location: ".base64_decode($input_vars['return']));
}
else
{
      header("Location: index.php?action=news/edit&news_id={$news_id}&lang={$news_lang}&site_id={$site_id}&interface_lang={$_SESSION['lang']}&aed=".\e::config('defaultToVisualEditor'));
}
exit;

?>
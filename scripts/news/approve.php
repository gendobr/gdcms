<?php
/*
  Approve news publication
  argument is $news_id    - news identifier, integer, mandatory
              $lang       - news language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/


$debug=false;
$GLOBALS['main_template_name']='';

//------------------- check news id - begin ------------------------------------
  $news_id   = checkInt($input_vars['news_id']);
  //$lang      = DbStr($input_vars['lang']);
  $lang      = get_language('lang');


  $query="SELECT * FROM {$table_prefix}news WHERE id={$news_id} AND lang='$lang'";
  $this_news_info=db_getonerow($query);
  if($debug) prn($query,$this_news_info);
  if(checkInt($this_news_info['id'])<=0)
  {
     $input_vars['page_title']  =$text['Page_not_found'];
     $input_vars['page_header'] =$text['Page_not_found'];
     $input_vars['page_content']=$text['Page_not_found'];
     return 0;
  }
//------------------- check news id - end --------------------------------------

//------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($this_news_info['site_id']);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =$text['Access_denied'];
     $input_vars['page_header'] =$text['Access_denied'];
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
//------------------- get permission - end -------------------------------------

//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$this_news_info['site_id']}");
  if($debug) prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------

//------------------- change page status - begin -------------------------------

  //----------------- get possible cense levels - begin ------------------------
    $query  = "SELECT level
               FROM {$table_prefix}site_user
               WHERE     site_id={$this_site_info['id']}
                     AND level<={$user_cense_level}
               ORDER BY level DESC LIMIT 0,3";
    $tmp    = db_getrows($query);
    $levels = Array();
    $levels[] = $user_cense_level;
    foreach($tmp as $_tm) $levels[] = $_tm['level'];
    $levels[] = 0;
    $levels = array_unique($levels);
    // prn($levels);
    unset($tmp,$_tm);
  //----------------- get possible levels - end --------------------------------

  switch($input_vars['transition'])
  {
    case 'approve':
      $new_cense_level=checkInt($levels[0]);
      $input_vars['page_title']   = $this_news_info['title'].' - '.$text['Approve'];
      $input_vars['page_header']  = $this_news_info['title'].' - '.$text['Approve'];
    break;
    case 'seize':
      $new_cense_level=checkInt($levels[1]);
      $input_vars['page_title']   = $this_news_info['title'].' - '.$text['Seize_to_revize'];
      $input_vars['page_header']  = $this_news_info['title'].' - '.$text['Seize_to_revize'];
    break;
    case 'return':
      $new_cense_level=checkInt($levels[2]);
      $input_vars['page_title']   = $this_news_info['title'].' - '.$text['Return_to_previous_operator'];
      $input_vars['page_header']  = $this_news_info['title'].' - '.$text['Return_to_previous_operator'];
    break;
    default:
      $new_cense_level=checkInt($this_news_info['cense_level']);
    break;
  }
  $query="UPDATE {$table_prefix}news
          SET cense_level={$new_cense_level}
          WHERE     id={$this_news_info['id']}
                AND lang='{$this_news_info['lang']}';";
  if($debug) prn($query);
  db_execute($query);
//------------------- change page status - end ---------------------------------

echo $text['Changes_saved_successfully'];
exit();

//----------------------------- draw page - begin ------------------------------
  $input_vars['page_content'] = $text['Changes_saved_successfully'];
//----------------------------- draw page - end --------------------------------

//----------------------------- context menu - begin ---------------------------
  $input_vars['page_menu']['page']=Array('title'=>$text['Manage_news'],'items'=>Array());
  run('news/menu');
  $input_vars['page_menu']['page']['items'] = menu_news($this_news_info);

  $input_vars['page_menu']['site']=Array('title'=>$text['Site_menu'],'items'=>Array());
  run('site/menu');
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------

// remove from history
   nohistory($input_vars['action']);


?>
<?
/*
  Set news weight
  argument is $news_id    - news identifier, integer, mandatory
              $lang       - news language  , char(3), mandatory
              $weight     - news weight
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/

global $main_template_name;
$main_template_name='';
$debug=false;

//------------------- check news id - begin ------------------------------------
  $news_id   = checkInt($input_vars['news_id']);
  // $lang      = DbStr($input_vars['lang']);
  $lang = get_language('lang');

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

////------------------- site info - begin ----------------------------------------
//  $site_id = $this_news_info['site_id'];
//  $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$this_news_info['site_id']}");
//  if($debug) prn('$this_site_info=',$this_site_info);
////------------------- site info - end ------------------------------------------

//------------------- change page status - begin -------------------------------

$weight=(int)$input_vars['weight'];


$query="UPDATE {$table_prefix}news SET weight=weight+{$weight} WHERE site_id={$this_news_info['site_id']} and id=$news_id and lang='".DbStr($lang)."'";
db_execute($query);

//$query="UPDATE {$table_prefix}news SET weight=weight+1 WHERE site_id={$this_news_info['site_id']} and weight>=$weight";
//if($debug) prn($query);
//db_execute($query);
//
//$query="UPDATE {$table_prefix}news SET weight=$weight WHERE site_id={$this_news_info['site_id']} and id=$news_id and lang='$lang'";
//if($debug) prn($query);
//db_execute($query);
//------------------- change page status - end ---------------------------------

echo "OK";
// remove from history
   nohistory($input_vars['action']);


?>
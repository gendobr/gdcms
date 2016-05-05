<?php
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

  $query="SELECT * FROM <<tp>>news WHERE id={$news_id} AND lang='$lang'";
  $this_news_info=\e::db_getonerow($query);
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



//------------------- change page status - begin -------------------------------

$weight=(int)$input_vars['weight'];


$query="UPDATE <<tp>>news SET weight=weight+{$weight} WHERE site_id={$this_news_info['site_id']} and id=$news_id and lang='".\e::db_escape($lang)."'";
\e::db_execute($query);


//------------------- change page status - end ---------------------------------

echo "OK";
// remove from history
   nohistory($input_vars['action']);


?>
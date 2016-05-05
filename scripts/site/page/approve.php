<?php
/*
  Approve page publication
  argument is $page_id    - page identifier, integer, mandatory
              $lang       - page_language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/


run('site/page/menu');


//------------------- check page id - begin ------------------------------------
  $page_id   = (int)$input_vars['page_id'];

  //$lang      = DbStr($input_vars['lang']);
  $lang = get_language('lang');

  $this_page_info=get_page_info($page_id,$lang);
  //prn($query,$this_page_info);
  if(!$this_page_info['id'])
  {
     $input_vars['page_title']  =$text['Page_not_found'];
     $input_vars['page_header'] =$text['Page_not_found'];
     $input_vars['page_content']=$text['Page_not_found'];
     return 0;
  }
  // prn('$this_page_info',$this_page_info);
//------------------- check page id - end --------------------------------------

//------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($this_page_info['site_id']);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =$text['Access_denied'];
     $input_vars['page_header'] =$text['Access_denied'];
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
//------------------- get permission - end -------------------------------------

//------------------- site info - begin ----------------------------------------
  $site_id = $this_page_info['site_id'];
  $this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$this_page_info['site_id']}");
  $this_site_info['url']=ereg_replace('/+$','',$this_site_info['url']);
# prn('$this_site_info=',$this_site_info);
  
  $this_site_info['absolute_path']=\e::config('SITES_ROOT').$this_site_info['dir'];
  
//------------------- site info - end ------------------------------------------

//------------------- change page status - begin -------------------------------
  //----------------- get possible cense levels - begin ------------------------
    $query  = "SELECT level FROM <<tp>>site_user  WHERE site_id={$this_site_info['id']} AND level<={$user_cense_level} ORDER BY level DESC LIMIT 0,3";
    $tmp    = \e::db_getrows($query);
    $levels = Array();
    $levels[] = $user_cense_level;
    foreach($tmp as $_tm) $levels[] = $_tm['level'];
    $levels[] = 0;
    $levels = array_values(array_unique($levels));
    //prn($levels);
    unset($tmp,$_tm);
  //----------------- get possible levels - end --------------------------------
  


  // site root dir
     $root=\e::config('SITES_ROOT').'/'.ereg_replace('^/+|/+$','',$this_site_info['dir']);

  // exported page path
     $dir=$root.'/'.ereg_replace('^/+|/+$','',$this_page_info['path']).'/'.$this_page_info['id'].'.'.$this_page_info['lang'].'.html';
     if(isset($input_vars['verbose'])) prn('$dir='.$dir);

  switch($input_vars['transition'])
  {
    case 'approve':
      $new_cense_level=(int)$levels[0];
      $input_vars['page_title']   = $this_page_info['title'].' - '.$text['Approve'];
      $input_vars['page_header']  = $this_page_info['title'].' - '.$text['Approve'];

    # path_delete($this_site_info['absolute_path'],$this_site_info['absolute_path'].'/'.ereg_replace('^/+|/+$','',$this_page_info['file']) );

    break;
    case 'seize':
      $new_cense_level=(int)$levels[1];
      $input_vars['page_title']   = $this_page_info['title'].' - '.$text['Seize_to_revize'];
      $input_vars['page_header']  = $this_page_info['title'].' - '.$text['Seize_to_revize'];
      
      // delete page file if page permission
      \core\fileutils::path_delete($root,$dir,isset($input_vars['verbose']));
      if(isset($input_vars['verbose'])) prn("path_delete($root,$dir);");
    # path_delete($this_site_info['absolute_path'],$this_site_info['absolute_path'].'/'.ereg_replace('^/+|/+$','',$this_page_info['file']) );
    break;
    case 'return':
      $new_cense_level=(int)$levels[2];
      $input_vars['page_title']   = $this_page_info['title'].' - '.$text['Return_to_previous_operator'];
      $input_vars['page_header']  = $this_page_info['title'].' - '.$text['Return_to_previous_operator'];
      // delete page file if page permission
      \core\fileutils::path_delete($root,$dir,isset($input_vars['verbose']));
      if(isset($input_vars['verbose'])) prn("path_delete($root,$dir);");
    break;
    default:
      $new_cense_level=(int)$this_page_info['cense_level'];
    break;
  }
  $query="UPDATE <<tp>>page 
          SET cense_level={$new_cense_level} 
          WHERE id={$this_page_info['id']} AND lang='{$this_page_info['lang']}'";
  //prn($query);
  \e::db_execute($query);
//------------------- change page status - end ---------------------------------


//----------------------------- draw page - begin ------------------------------
  $GLOBALS['main_template_name']='';
  $input_vars['page_content'] = '<b><font color=green>'.$text['Changes_saved_successfully'].'</font></b>'.
  '
   <script language="Javascript1.2">
   <!-- 
     window.opener.location.reload();
     window.close();
   // -->
   </script>
  '
  ;
  echo $input_vars['page_content'];
//----------------------------- draw page - end --------------------------------

//----------------------------- context menu - begin ---------------------------
/*
  $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
  run('site/page/menu');
  $input_vars['page_menu']['page']['items'] = menu_page($this_page_info);

  $input_vars['page_menu']['site']=Array('title'=>$text['Site_menu'],'items'=>Array());
  run('site/menu');
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
*/
//----------------------------- context menu - end -----------------------------

?>
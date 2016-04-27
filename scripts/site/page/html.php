<?php
/*
  Show html code to insert into another pages
  argument is $page_id    - page identifier, integer, mandatory
              $lang       - page_language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/

run('site/page/menu');
run('site/menu');
# ------------------- check page id - begin ------------------------------------
  $page_id   = (int)$input_vars['page_id'];
  // $lang      = DbStr($input_vars['lang']);
  $lang = get_language('lang');
  
  $this_page_info=get_page_info($page_id,$lang);
  if(!$this_page_info)
  {
     $input_vars['page_title']   =
     $input_vars['page_header']  =
     $input_vars['page_content'] = $text['Page_not_found'];
     return 0;
  }
  //prn('$this_page_info',$this_page_info);
# ------------------- check page id - end --------------------------------------



# ------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($this_page_info['site_id']);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =
     $input_vars['page_header'] =
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
# ------------------- get permission - end -------------------------------------

# site info
  $this_site_info = get_site_info($this_page_info['site_id']);
  # prn('$this_site_info=',$this_site_info);



//----------------------------- draw page - begin ------------------------------
  $GLOBALS['main_template_name']='';
  
  if(strlen($this_page_info['path'])>0) 
  $page_path="{$this_site_info['url']}/{$this_page_info['path']}/{$this_page_info['id']}.{$this_page_info['lang']}.html";
  else 
  $page_path="{$this_site_info['url']}{$this_page_info['id']}.{$this_page_info['lang']}.html";

  $input_vars['page_content'] = "
  {$text['Get_html_link_man']}
  <div style='padding:10px;border:1px solid green;'>
  <code style='color:green;font-size:110%; font-weight:bold;'>".
  htmlspecialchars("<a href=\"{$page_path}\">{$this_page_info['title']}</a>")
  ."</code>
  </div>
  <br>
  <div style='padding:10px;border:1px solid blue;'>
   <br>
   <a href=\"{$page_path}\">{$this_page_info['title']}</a>
<br><br>
  </div>";
  echo $input_vars['page_content'];
//----------------------------- draw page - end --------------------------------

//----------------------------- context menu - begin ---------------------------
/*
  $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
  
  $input_vars['page_menu']['page']['items'] = menu_page($this_page_info);

  $input_vars['page_menu']['site']=Array('title'=>$text['Site_menu'],'items'=>Array());
  
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
*/
//----------------------------- context menu - end -----------------------------

// remove from history
   nohistory($input_vars['action']);

?>
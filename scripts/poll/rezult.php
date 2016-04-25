<?php
global $main_template_name; $main_template_name='';

//------------------- get site info - begin ------------------------------------
  run('site/menu');
  $site_id = (int)($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);
  //prn($this_site_info);die();
  //prn($input_vars);
  if(!$this_site_info) die($txt['Site_not_found']);
//------------------- get site info - end --------------------------------------

//--------------------------- get site template - begin ------------------------
  $custom_page_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/template_index.html';
  #prn('$news_template',$news_template);
  if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
//--------------------------- get site template - end --------------------------



// ------------------ get poll - begin -----------------------------------------
   $polls=\e::db_getrows("SELECT * FROM {$table_prefix}golos_pynannja WHERE site_id={$site_id} AND is_active=1");
   if(!$polls) return '';
   //prn('$polls',$polls);

   $poll_ids=Array();
   foreach($polls as $key=>$val) $poll_ids[$key]=(int)$val['id'];

   $vidpovidi=\e::db_getrows("SELECT * FROM {$table_prefix}golos_vidpovidi WHERE pynannja_id IN (".join(',',$poll_ids).") ORDER BY pynannja_id, id ");

   $poll_ids=array_flip($poll_ids);
   foreach($vidpovidi as $val)
   {
       $i=$poll_ids[$val['pynannja_id']];
       if(!isset($polls[$i]['vidpovidi']))  $polls[$i]['vidpovidi']=Array();
       $polls[$i]['vidpovidi'][$val['id']]=$val['html'];
   }
// prn($polls);
//------------------- get poll - end -------------------------------------------



// ------------------- draw form - begin ---------------------------------------
   $page_content='';
   foreach($polls as $poll)
   {
       //prn($poll);
       $page_content.="<h3>{$poll['title']}</h3>";
       
       if($poll['poll_type']=='checkbox')
       {
           foreach($poll['vidpovidi'] as $key=>$val)
               $page_content.="<div><label><input type=checkbox name=poll[{$poll['id']}][$key] value=$key>{$val}</label></div>";
       }
       else
       {
           foreach($poll['vidpovidi'] as $key=>$val)
               $page_content.="<div><label><input type=radio name=poll[{$poll['id']}][0] value=$key>{$val}</label></div>";
       }
   }
   
   $page_content="
     <form action=index.php>
     <input type=hidden name=action value=poll/ask>
     <input type=hidden name=site_id value=$site_id>
     $page_content
     <br>
     <input type=submit>
     </form>
   ";
// ------------------- draw form - end -----------------------------------------



if(isset($input_vars['interface_lang']))
   if(strlen($input_vars['interface_lang'])>0)
      $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])) $input_vars['lang']=\e::config('default_language');
$input_vars['lang']      = get_language('lang');
$txt = load_msg($input_vars['lang']);


run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
  $lang_list=list_of_languages();
  // prn($lang_list);
//------------------------ get list of languages - end -------------------------


  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array(
                                     'title'=>$txt['Poll']
                                    ,'content'=> $page_content
                                    ,'abstract'=> ''
                                    ,'site_id'=>$site_id
                                    ,'lang'=>$input_vars['lang']
                                    )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
                                
echo $file_content;                   

?>
<?php
$link = $db;
$data=date ("Y-m-d H:i");



if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
$input_vars['lang']      = get_language('lang');
$txt=load_msg($input_vars['lang']);

  run('site/menu');

//------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id,$input_vars['lang']);

  ///prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     die($txt['Site_not_found']);
  }
//------------------- get site info - end --------------------------------------

//--------------------------- get site template - begin ------------------------
  $custom_page_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/template_index.html';
  #prn('$news_template',$news_template);
  if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
//--------------------------- get site template - end --------------------------


//------------------- draw map - begin -----------------------------------------
  $query="SELECT pa.id, pa.lang, pa.title, pa.map_position, pa.map_indent
          FROM {$table_prefix}page AS  pa INNER JOIN {$table_prefix}site AS si ON(pa.site_id=si.id)
          WHERE     pa.site_id={$this_site_info['id']}
                AND pa.lang='".\e::db_escape($input_vars['lang'])."'
								AND pa.cense_level>=si.cense_level 
          ORDER BY pa.map_position, pa.id";
  $page_list=\e::db_getrows($query);
  ///prn($query,$page_list);
  foreach($page_list as $page_info)
  {
    $page_url="{$this_site_info['url']}/{$page_info['id']}.{$page_info['lang']}.html";
    $page_url = str_replace("//{$page_info['id']}.{$page_info['lang']}.html","/{$page_info['id']}.{$page_info['lang']}.html",$page_url);
    $vyvid.="
    <tr>
      <td style='padding-left:".checkInt(15*$page_info['map_indent'])."px;'>
      <a href='{$page_url}'>{$page_info['title']}</a>
      </td>
    </tr>
    ";
  }
  $vyvid="
  <table>
  {$vyvid}
  </table>
  ";

//------------------- draw map - end -------------------------------------------



run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
  $lang_list=list_of_languages();
  $cnt=count($lang_list);
  for($i=0;$i<$cnt;$i++)
  {
     $lang_list[$i]['url']=str_replace('action=site%2Fmap%2Fview','',$lang_list[$i]['href']);
     $lang_list[$i]['url']=str_replace('index.php','map.php',$lang_list[$i]['url']);
     $lang_list[$i]['url']=str_replace(site_root_URL,sites_root_URL,$lang_list[$i]['url']);
     $lang_list[$i]['url']=str_replace('?&','?',$lang_list[$i]['url']);
     $lang_list[$i]['url']=str_replace('&&','&',$lang_list[$i]['url']);

     $lang_list[$i]['lang']=$lang_list[$i]['name'];
  }
  //prn($lang_list);
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------

  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$this_site_info['title'].' - '.$txt['Site_map'],'content'=> $vyvid)
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name; $main_template_name='';
?>
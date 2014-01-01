<?php
/**
 * Browse ec_item categories
 *   argments are
 *    $category_id - identifier of category
 *
 *
 *
 */
run('site/menu');
run('ec/item/functions');
run('site/page/page_view_functions');

# -------------------- set interface language - begin ---------------------------
  $debug=false;
  if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
  if(!isset($input_vars['lang'])   ) $input_vars['lang']=default_language;
  if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
  // $lang=$input_vars['lang'];
  $lang=get_language('lang');
# -------------------- set interface language - end -----------------------------

# -------------------------- load messages - begin -----------------------------
  global $txt;
  $txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------



# ------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
  //prn($this_site_info);
  //prn($input_vars);
# ------------------- get site info - end --------------------------------------

# --------------------------- get site template - begin ------------------------
  $custom_page_template = site_get_template($this_site_info,'template_index');
  //$custom_page_template = sites_root.'/'.$this_site_info['dir'].'/template_index.html';
  //if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
# --------------------------- get site template - end --------------------------




# --------------------------- get list of tags - begin -------------------------
  if(isset($input_vars['ec_item_tags']) && strlen($input_vars['ec_item_tags'])>0)
  {
     $ec_item_tags=trim($input_vars['ec_item_tags']);
     $list_of_tags=Array();
  }
  else
  {
      $ec_item_tags='';
      $query="select distinct ec_item_tag 
             from {$table_prefix}ec_item_tags as ec_item_tags
                  inner join {$table_prefix}ec_item as ec_item
                  on(ec_item_tags.ec_item_id=ec_item.ec_item_id)
             where  ec_item.site_id=$site_id
                and (ec_item.ec_item_cense_level&".ec_item_show.")
             order by ec_item_tag asc";
      $list_of_tags=db_getrows($query);
      $cnt=count($list_of_tags);
      for($i=0;$i<$cnt;$i++) $list_of_tags[$i]=$list_of_tags[$i]['ec_item_tag'];
      //prn($list_of_tags);
  }
# --------------------------- get list of tags - end ---------------------------



# --------------------------- get list of items - begin ------------------------
  include(script_root.'/ec/item/get_public_list.php');
  include(script_root.'/ec/item/adjust_public_list.php');
# --------------------------- get list of items - end --------------------------


//prn($list_of_ec_items);

//prn($pages);

# -------------------- get list of page languages - begin ----------------------
    $tmp=db_getrows("SELECT DISTINCT ec_item_lang as lang
                     FROM {$table_prefix}ec_item  AS ec_item
                     WHERE ec_item.site_id={$site_id}
                       AND ec_item.ec_item_cense_level&".ec_item_show."");
    $existing_languages=Array();
    foreach($tmp as $tm) $existing_languages[$tm['lang']]=$tm['lang'];
    // prn($existing_languages);


    $lang_list=list_of_languages();
    $cnt=count($lang_list);
    for($i=0;$i<$cnt;$i++)
    {
        if(!isset($existing_languages[$lang_list[$i]['name']]))
        {
          unset($lang_list[$i]);
          continue;
        }
        $lang_list[$i]['url']=$lang_list[$i]['href'];
        $lang_list[$i]['lang']=$lang_list[$i]['name'];
    }
    $lang_list=array_values($lang_list);
    //prn($lang_list);
# -------------------- get list of page languages - end ------------------------


//------------------------ draw using SMARTY template - begin ------------------

  # get site menu
    $menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

  # search for template
    $ec_item_template_list_by_tag = site_get_template($this_site_info,'template_ec_item_list_by_tag');

  # search for template
    $ec_item_template_list = site_get_template($this_site_info,'template_ec_item_list');

  $vyvid=
  process_template( $ec_item_template_list_by_tag
                    ,Array(
                           'text'=>$txt,
                           'list_of_tags'=>$list_of_tags,
                           'current_tag'=>$ec_item_tags,
                           'tag_view_url_prefix'=>"index.php?action=ec/item/list_by_tag&lang=$lang&site_id=$site_id&ec_item_tags=",
                           'site'=>$this_site_info
                     )
  )
  .process_template( $ec_item_template_list
                    ,Array(
                           'pages'=>$pages,
                           'text'=>$txt,
                           'ec_items'=>$list_of_ec_items,
                           'ec_items_search_summary'=>sprintf(text('EC_items_search_summary'),$start+1,$start+count($list_of_ec_items),$rows_found),
                           'ec_items_found' => $rows_found,
                           'start'=>$start+1,
                           'finish'=>$start+count($list_of_ec_items),
                           'site'=>$this_site_info,
                           'orderby'=>$orderby 
                     )
  )
  ;

  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>(strlen($ec_item_tags)>0?$ec_item_tags:$txt['EC_items'])
                                               ,'content'=>$vyvid
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
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name; $main_template_name='';

?>
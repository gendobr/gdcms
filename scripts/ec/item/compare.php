<?php
/*
 * Compare the selected ec_items
 */
global $main_template_name; $main_template_name='';
run('site/page/page_view_functions');
run('site/menu');
run('ec/item/functions');


# -------------------- set interface language - begin --------------------------
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
  $site_id = (int)$input_vars['site_id'];
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
# ------------------- get site info - end --------------------------------------



# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
  $lang_list=array_values($lang_list);
# -------------------- get list of page languages - end ------------------------

# -------------------- remove items - begin ------------------------------------
  if(isset($input_vars['remove_ec_item_id']))
  {
     unset($_SESSION['items_to_compare'][$input_vars['remove_ec_item_id']]);
  }
# -------------------- remove items - end --------------------------------------

# -------------------- get items to compare - begin ----------------------------
  //unset($_SESSION['items_to_compare']);
  if( isset($_SESSION['items_to_compare']) )
  {
      $items=Array();
      $prefix=site_root_URL.'/index.php?'.query_string('^ec_item_id$').'&remove_ec_item_id=';
      foreach($_SESSION['items_to_compare'] as $ec_item_id)
      {
          $items[$ec_item_id]=get_ec_item_info($ec_item_id,$lang,$site_id);
          $items[$ec_item_id]['url_remove_item']=$prefix.$ec_item_id;
      }
      //prn($items);
      if(!is_logged()){
	     $items_keys=array_keys($items);
		 $items_keys[]=0;
         \e::db_execute("UPDATE {$table_prefix}ec_item SET ec_item_views=ifnull(ec_item_views,0)+1 WHERE ec_item_id IN(".join(',',$items_keys).")");
      }
  }
# -------------------- get items to compare - end ------------------------------




//-------------------- draw using SMARTY template - begin ----------------------
  # get site menu
    $menu_groups = get_menu_items($this_site_info['id'],0,$lang);
  # search for template
    $ec_item_template = site_get_template($this_site_info,'template_ec_item_compare');

  # get site template
    $custom_page_template = site_get_template($this_site_info,'template_index');

  $vyvid='';

  $vyvid.=
  process_template( $ec_item_template
                    ,Array(
                           'items'=>$items,
                           'text'=>$txt,
                           'site'=>$this_site_info
                     )
  );
  //prn($items);
  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$txt['EC_items_compare']
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
echo $file_content;

//-------------------- draw using SMARTY template - end ------------------------



?>

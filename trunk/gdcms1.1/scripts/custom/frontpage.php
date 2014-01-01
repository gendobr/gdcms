<?php
/**
 * Browse ec_item categories
 *   argments are
 *    $category_id - identifier of category
 *    $site_id     - site identifier
 *
 *
 */


  $frontpage_templates_dir='golovna';
  run('site/page/page_view_functions');
  run('site/menu');

# -------------------- set interface language - begin ---------------------------
  $debug=false;
  if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
  if(!isset($input_vars['lang'])   ) $input_vars['lang']=default_language;
  if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
  // $lang=$input_vars['lang'];
  $lang = get_language('lang');
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


# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
# -------------------- get list of page languages - end ------------------------

# -------------------- search for template - begin -----------------------------
  $ec_item_template_list = sites_root.'/'.$this_site_info['dir']."/$frontpage_templates_dir/template_ec_item_list.html";
  if(!is_file($ec_item_template_list)) $ec_item_template_list = 'cms/template_ec_item_list';
# -------------------- search for template - end -------------------------------


# -------------------- vystavka - begin ----------------------------------------
  $input_vars['ec_item_tags']=rawurldecode('%E2%E8%F1%F2%E0%E2%EA%E0');
  $input_vars['ec_item_state']='show';
  $input_vars['orderby']='rand() asc';
  $input_vars['rows']=5;
  include(script_root.'/ec/item/get_public_list.php');
  include(script_root.'/ec/item/adjust_public_list.php');
  $vystavka=process_template( $ec_item_template_list
                    ,Array(
                           'pages'=>$pages,
                           'text'=>$txt,
                           'ec_items'=>$list_of_ec_items,
                           'ec_items_search_summary'=>'',//sprintf(text('EC_items_search_summary'),$start+1,$start+count($list_of_ec_items),$rows_found),
                           'ec_items_found' => $rows_found,
                           'start'=>$start+1,
                           'finish'=>$start+count($list_of_ec_items),
                           'category_view_url_prefix'=>"index.php?action=ec/item/browse&lang=$lang&site_id=$site_id&ec_category_id=",
                           'site'=>$this_site_info
                     )
  )
  ;
  unset(  $input_vars['ec_item_tags'],$input_vars['ec_item_state'],$input_vars['orderby'],$input_vars['rows']);
# -------------------- vystavka - end ------------------------------------------

# -------------------- last added - begin --------------------------------------
  $input_vars['ec_item_state']='show sell';
  $input_vars['ec_category_id']=-10;
  $input_vars['orderby']='ec_item_last_change_date desc';
  $input_vars['rows']=6;
  include(script_root.'/ec/item/get_public_list.php');
  include(script_root.'/ec/item/adjust_public_list.php');
  $last_added=process_template( $ec_item_template_list
                    ,Array(
                           'pages'=>Array(),
                           'text'=>$txt,
                           'ec_items'=>$list_of_ec_items,
                           'ec_items_search_summary'=>'',//sprintf(text('EC_items_search_summary'),$start+1,$start+count($list_of_ec_items),$rows_found),
                           'ec_items_found' => $rows_found,
                           'start'=>$start+1,
                           'finish'=>$start+count($list_of_ec_items),
                           'category_view_url_prefix'=>"index.php?action=ec/item/browse&lang=$lang&site_id=$site_id&ec_category_id=",
                           'site'=>$this_site_info
                     )
  )
  ;
  unset($input_vars['ec_item_state'],$input_vars['orderby'],$input_vars['rows'],$input_vars['ec_category_id']);
# -------------------- last added - end ----------------------------------------


# -------------------- last news - begin ---------------------------------------
  $input_vars['category_filter_mode']='yes';
  $input_vars['category_id']=2;
  $input_vars['lang']='ukr';
  include(script_root.'/news/get_public_list.php');

  $news_template = sites_root.'/'.$this_site_info['dir']."/$frontpage_templates_dir/template_news_view_list.html";
  #prn('$news_template',$news_template);
  if(!is_file($news_template)) $news_template = 'cms/template_news_view_list';

  //prn($txt);

  #prn('$news_template',$news_template);
  $novyny=process_template( $news_template
                    ,Array(
                           'paging_links'=>$pages
                          ,'text'=>$txt
                          ,'news'=>$list_of_news
                          ,'news_found' => $news_found
                          ,'news_date_selector'=>$news_date_selector
                          ,'news_keywords_selector'=>$news_keywords_selector
                          ,'news_category_selector'=>$category_selector
                          ,'news_tags'=>$tag_selector
                          ,'start'=>$start+1
                          ,'finish'=>$start+count($list_of_news)
                     )
         );

  unset($input_vars['category_filter_mode'],
        $input_vars['category_id'],
        $input_vars['lang']);
# -------------------- last news - end -----------------------------------------



# -------------------- stan rechej - begin -------------------------------------
  $input_vars['category_filter_mode']='yes';
  $input_vars['category_id']=3;
  $input_vars['lang']='ukr';
  include(script_root.'/news/get_public_list.php');

  $news_template = sites_root.'/'.$this_site_info['dir']."/$frontpage_templates_dir/template_news_stan_rechej.html";
  #prn('$news_template',$news_template);
  if(!is_file($news_template)) $news_template = 'cms/template_news_view_list';

  //prn($txt);

  //prn('$news_template',$news_template);
  //prn($list_of_news);
  $stan_rechej=process_template( $news_template
                    ,Array(
                           'paging_links'=>$pages
                          ,'text'=>$txt
                          ,'news'=>$list_of_news
                          ,'news_found' => $news_found
                          ,'news_date_selector'=>$news_date_selector
                          ,'news_keywords_selector'=>$news_keywords_selector
                          ,'news_category_selector'=>$category_selector
                          ,'news_tags'=>$tag_selector
                          ,'start'=>$start+1
                          ,'finish'=>$start+count($list_of_news)
                     )
         );
  //prn($stan_rechej);
  unset($input_vars['category_filter_mode'],
        $input_vars['category_id'],
        $input_vars['lang']);
# -------------------- stan rechej - end ---------------------------------------





# ------------------------ draw using SMARTY template - begin ------------------

  # get site menu
    $menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);



//$vyvid="
//    $novyny
//<h2>Виставка</h2>
//$vystavka
//
//  ";
# --------------------------- get site template - begin ------------------------
  $custom_page_template = sites_root.'/'.$this_site_info['dir']."/$frontpage_templates_dir/template_index.html";
  if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
  //prn($custom_page_template);
  //prn($this_site_info['template']);
# --------------------------- get site template - end --------------------------

  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>get_langstring($this_site_info['title'])
                                               ,'content'=>$novyny
                                               ,'abstract'=> ''
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$input_vars['lang']
                                          )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                 ,'stan_rechej'=>$stan_rechej
                                 ,'vystavka'=>$vystavka
                                 ,'last_added'=>$last_added
                                ));
# ------------------------ draw using SMARTY template - end --------------------
echo $file_content;

global $main_template_name; $main_template_name='';

?>
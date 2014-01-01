<?php
/*
 * public subscription page
 */

if (isset($input_vars['interface_lang']))
    if ($input_vars['interface_lang'])
        $input_vars['lang'] = $input_vars['interface_lang'];
if (!isset($input_vars['lang']))
    $input_vars['lang'] = default_language;
if (strlen($input_vars['lang']) == 0)
    $input_vars['lang'] = default_language;
$input_vars['lang'] = get_language('lang');

//-------------------------- load messages - begin -----------------------------
global $txt;
$txt = load_msg($input_vars['lang']);
//-------------------------- load messages - end -------------------------------
//
//------------------- get site info - begin ------------------------------------
if (!function_exists('get_site_info'))
    run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
$this_site_info['title']=get_langstring($this_site_info['title'],$input_vars['lang']);
//prn($this_site_info);die();
//prn($input_vars);
if (!$this_site_info) die($txt['Site_not_found']);
//------------------- get site info - end --------------------------------------

//--------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, 'template_index.html', false);
if (is_file($custom_page_template)){
    $this_site_info['template'] = $custom_page_template;
}
//--------------------------- get site template - end --------------------------

$message='';
// -------------------------- do subscription - begin --------------------------
if(isset($input_vars['code'])){

    // get subscriber by code
    $code=DbStr(trim($input_vars['code']));
    $subscriber_info=  db_getonerow("select * from {$table_prefix}news_subscriber WHERE news_subscriber_code='$code'");
    if($subscriber_info){
        // if subscriber is found change the is_valid value and clear code
        $query="UPDATE {$table_prefix}news_subscriber SET news_subscriber_code=null, news_subscriber_is_valid=1 WHERE news_subscriber_code='$code'";
        db_execute($query);
        // show message
        $message=str_replace(Array('{name}'), Array($subscriber_info['news_subscriber_name']),text('Your_subscription_is_successfully_confirmed'));
    }else{
        $message=text('Your_confirmation_link_is_obsolete');
    }
}
// -------------------------- do subscription - end ----------------------------


// -------------------------- draw page content - begin ------------------------
   $page_content=$message;
// -------------------------- draw page content - end --------------------------

//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
// prn($lang_list);
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = $lang_list[$i]['href'];
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
$lang_list = array_values($lang_list);
// prn($lang_list);
//------------------------ get list of languages - end -------------------------

if (!function_exists('db_get_template')){
    run('site/page/page_view_functions');
}
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

//------------------------ draw using SMARTY template - begin ------------------
$file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$this_site_info['title'].' - '.$txt['Subscribe_mailing_list']
                                               ,'content'=>$page_content
                                               ,'abstract'=> ( isset($txt['news_manual'])?$txt['news_manual']:'')
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$input_vars['lang']
                                          )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
//------------------------ draw using SMARTY template - end --------------------
echo $file_content;

global $main_template_name; $main_template_name='';
?>
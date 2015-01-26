<?php
/*
 * public unsubscription page
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
// -------------------------- confirm unsubscription - begin -------------------
if(isset($input_vars['code'])){

    // get subscriber by code
    $code=DbStr(trim($input_vars['code']));
    $subscriber_info=  db_getonerow("select * from {$table_prefix}news_subscriber WHERE news_subscriber_code='$code'");
    if($subscriber_info){
        // if subscriber is found change the is_valid value and clear code
        $query="DELETE FROM {$table_prefix}news_subscriber WHERE news_subscriber_code='$code'";
        db_execute($query);
        // show message
        $message=str_replace(Array('{name}'), Array($subscriber_info['news_subscriber_name']),text('Your_subscription_is_successfully_cancelled'));
    }else{
        $message=text('Your_unsubscription_link_is_obsolete');
    }
}
// -------------------------- confirm unsubscription - end ---------------------

$data_is_correct=false;
// -------------------------- do subscription - begin --------------------------
if(isset($input_vars['news_subscriber_email']) && strlen($input_vars['news_subscriber_email'])>0 && is_valid_email($input_vars['news_subscriber_email'])){

    // generate code
    $code=  md5(time().session_id().  rand(0, 10000));

    // check if code is unique
    while(db_getonerow("select * from {$table_prefix}news_subscriber WHERE news_subscriber_code='$code'")){
        $code=  md5(time().session_id().  rand(0, 10000));
    }

    $confirmation_url=site_root_URL."/index.php?action=news_subscription/unsubscribe&site_id={$site_id}&code=".$code;
    $subscriber_info=db_getonerow("select * from {$table_prefix}news_subscriber WHERE news_subscriber_email='".DbStr($input_vars['news_subscriber_email'])."'");
    if($subscriber_info){
        // re-send confirmation link
        // update the subscriber
        $query="UPDATE {$table_prefix}news_subscriber
                SET news_subscriber_code='{$code}'
                WHERE site_id=$site_id
                    AND news_subscriber_email='".  DbStr($input_vars['news_subscriber_email'])."'
                ";
        db_execute($query);
        $message.='<div>'.$txt['Please_read_the_unsubscription_confirmation_email'].'</div>';

        // send email to confirm
        run('notifier/functions');
        $unsubscription_confirmation_mail_subject=str_replace(
                Array('{name}','{site_title}','{site_url}','{confirmation_url}'),
                Array($subscriber_info['news_subscriber_name'],$this_site_info['title'],$this_site_info['url'],$confirmation_url),
                $txt['Unsubscription_confirmation_mail_subject']);

        $unsubscription_confirmation_mail_body=str_replace(
                Array('{name}','{site_title}','{site_url}','{confirmation_url}',"\\n"),
                Array($subscriber_info['news_subscriber_name'],$this_site_info['title'],$this_site_info['url'],$confirmation_url,"\n"),
                $txt['Unsubscription_confirmation_mail_body']);
        notification_queue($subscriber_info['news_subscriber_email'], $unsubscription_confirmation_mail_subject, $unsubscription_confirmation_mail_body, 'notify_action_email');

    }
}
// -------------------------- do subscription - end ----------------------------

if (!function_exists('db_get_template')){
    run('site/page/page_view_functions');
}
// -------------------------- draw page content - begin ------------------------
//$page_content=$message;
//if(!isset($input_vars['code']) && !isset($input_vars['news_subscriber_email'])){
//    $page_content.="
//    <form action=index.php method=POST>
//    <input type=hidden name=action value=\"news_subscription/unsubscribe\">
//    <input type=hidden name=site_id value=\"{$site_id}\">
//    <div>{$txt['News_subscriber_email']}</div>
//    <input type=\"text\" name=\"news_subscriber_email\" value=\"".(isset($input_vars['news_subscriber_email'])?checkStr($input_vars['news_subscriber_email']):'')."\"><br><br>
//    <input type=submit value=\"{$txt['Unsubscribe_mailing_list']}\">
//    </form>
//    ";
//}
$_template = site_get_template($this_site_info, 'template_news_unsubscribe');

$page_content=process_template($_template
        , Array(
                'site' => $this_site_info
              , 'site_root_url' => site_root_URL
              , 'data_is_correct' => $data_is_correct
              , 'text' => $txt
              , 'message' => $message
              , 'news_subscriber_email' => (isset($input_vars['news_subscriber_email'])?checkStr($input_vars['news_subscriber_email']):'')
        )
        // , Array('show_related_news', 'show_news_categories')
);
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


$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

//------------------------ draw using SMARTY template - begin ------------------
$file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$this_site_info['title'].' - '.$txt['Unsubscribe_mailing_list']
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

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
$data_is_correct=false;
// -------------------------- do subscription - begin --------------------------
if(isset($input_vars['news_subscriber_name']) && isset($input_vars['news_subscriber_email'])){
    $data_is_correct=true;

    $input_vars['news_subscriber_email']=trim($input_vars['news_subscriber_email']);
    if(strlen($input_vars['news_subscriber_email'])==0){
        $data_is_correct=false;
        $message.='<div>'.$txt['ERROR_email_is_empty'].'</div>';
    }elseif(!is_valid_email($input_vars['news_subscriber_email'])){
        $data_is_correct=false;
        $message.='<div>'.$txt['ERROR_email_has_invalid_format'].'</div>';
    }elseif(db_getonerow("select news_subscriber_id from {$table_prefix}news_subscriber WHERE news_subscriber_is_valid AND news_subscriber_email='".DbStr($input_vars['news_subscriber_email'])."'")){
        $data_is_correct=false;
        $message.='<div>'.$txt['You_have_already_subscribed'].'</div>';
    }

    $input_vars['news_subscriber_name']=trim($input_vars['news_subscriber_name']);
    if(strlen($input_vars['news_subscriber_name'])==0){
        $input_vars['news_subscriber_name']=$input_vars['news_subscriber_email'];
    }




    if($data_is_correct){

        // generate code
        $code=  md5(time().session_id().  rand(0, 10000));

        // check if code is unique
        while(db_getonerow("select * from {$table_prefix}news_subscriber WHERE news_subscriber_code='$code'")){
            $code=  md5(time().session_id().  rand(0, 10000));
        }

        $confirmation_url=site_root_URL."/index.php?action=news_subscription/confirmation&site_id={$site_id}&code=".$code;

        if(db_getonerow("select * from {$table_prefix}news_subscriber WHERE news_subscriber_email='".DbStr($input_vars['news_subscriber_email'])."'")){
            // re-send confirmation link
            // update the subscriber
            $query="UPDATE {$table_prefix}news_subscriber
                    SET news_subscriber_code='{$code}'
                    WHERE site_id=$site_id
                        AND news_subscriber_email='".  DbStr($input_vars['news_subscriber_email'])."'
                    ";
            db_execute($query);
            $message.='<div>'.$txt['Resending_confirmation_code'].'</div>';
        }else{
            // insert new subscriber
            $query="INSERT INTO {$table_prefix}news_subscriber(
                    news_subscriber_name,
                    news_subscriber_email,
                    news_subscriber_code,
                    news_subscriber_is_valid,
                    news_subscriber_date,
                    site_id
                    ) VALUES (
                    '".  DbStr($input_vars['news_subscriber_name'])."',
                    '".  DbStr($input_vars['news_subscriber_email'])."',
                    '{$code}',
                    0,
                    NOW(),
                    $site_id
                )";
            db_execute($query);
        }

        // send email to confirm
        run('notifier/functions');
        $subscription_confirmation_mail_subject=str_replace(
                Array('{name}','{site_title}','{site_url}','{confirmation_url}'),
                Array($input_vars['news_subscriber_name'],$this_site_info['title'],$this_site_info['url'],$confirmation_url),
                $txt['Subscription_confirmation_mail_subject']);

        $subscription_confirmation_mail_body=str_replace(
                Array('{name}','{site_title}','{site_url}','{confirmation_url}',"\\n"),
                Array($input_vars['news_subscriber_name'],$this_site_info['title'],$this_site_info['url'],$confirmation_url,"\n"),
                $txt['Subscription_confirmation_mail_body']);
        notification_queue($input_vars['news_subscriber_email'], $subscription_confirmation_mail_subject, $subscription_confirmation_mail_body, 'notify_action_email');


        header("Location: index.php?action=news_subscription/subscribe&site_id={$site_id}&success=1");
        exit();

    }
}
// -------------------------- do subscription - end ----------------------------

// -------------------------- show success message - begin ---------------------
if(isset($input_vars['success'])){
   $message.='<div>'.$txt['Please_read_the_confirmation_email'].'</div>';
}
// -------------------------- show success message - end -----------------------

// -------------------------- draw page content - begin ------------------------
$page_content=$message;
if(!$data_is_correct){
    $page_content.="
    <form action=index.php method=POST>
    <input type=hidden name=action value=\"news_subscription/subscribe\">
    <input type=hidden name=site_id value=\"{$site_id}\">

    <div>{$txt['News_subscriber_name']}</div>
    <input type=\"text\" name=\"news_subscriber_name\" value=\"".(isset($input_vars['news_subscriber_name'])?checkStr($input_vars['news_subscriber_name']):'')."\"><br>

    <div>{$txt['News_subscriber_email']}</div>
    <input type=\"text\" name=\"news_subscriber_email\" value=\"".(isset($input_vars['news_subscriber_email'])?checkStr($input_vars['news_subscriber_email']):'')."\"><br><br>
    <input type=submit value=\"{$txt['Subscribe_mailing_list']}\">
    </form>
    ";
}
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
<?php
/*
 * send news to all subscribers
 */
$debug = false;
$GLOBALS['main_template_name'] = '';

// number of emails per iteration
$N = 100;

// news_id
# ------------------- check news id - begin ------------------------------------
$news_id = checkInt((isset($input_vars['news_id']) ? $input_vars['news_id'] : 0));
$lang = get_language('lang');

$query = "SELECT * FROM {$table_prefix}news WHERE id={$news_id} AND lang='$lang'";
$this_news_info = db_getonerow($query);
if ($debug) {
    prn(checkStr($query), $this_news_info);
}
if (checkInt($this_news_info['id']) <= 0) {
    if (!isset($input_vars['site_id'])) {
        $input_vars['site_id'] = 0;
    }
    echo "News {$news_id}.{$lang} not found";
    return 0;
}
# prn($this_news_info);
# ------------------- check news id - end --------------------------------------

run('site/menu');

# ------------------- get site info - begin ------------------------------------
$site_id = (int) $this_news_info['site_id'];
$this_site_info = get_site_info($site_id);
if (!$this_site_info) die($txt['Site_not_found']);
# ------------------- get site info - end --------------------------------------

# ------------------- get permission - begin -----------------------------------
$user_cense_level = get_level($site_id);
if ($user_cense_level <= 0) {
    echo "You cannot send news {$news_id}.{$lang}";
    return 0;
}
#if($debug) prn('$user_cense_level='.$user_cense_level);
# ------------------- get permission - end -------------------------------------


//-------------------------- load messages - begin -----------------------------
if (isset($input_vars['interface_lang']))
    if ($input_vars['interface_lang'])
        $input_vars['lang'] = $input_vars['interface_lang'];
if (!isset($input_vars['lang']))
    $input_vars['lang'] = default_language;
if (strlen($input_vars['lang']) == 0)
    $input_vars['lang'] = default_language;
$input_vars['lang']      = get_language('lang');
$txt = load_msg($input_vars['lang']);
//-------------------------- load messages - end -------------------------------


// get first $N subscribers starting from $start
$start = isset($input_vars['start']) ? (int) $input_vars['start'] : 0;
$query = "SELECT * FROM {$table_prefix}news_subscriber WHERE site_id={$site_id} AND news_subscriber_is_valid ORDER BY news_subscriber_id ASC LIMIT $start, $N";
$subscribers = db_getrows($query);
$report='Emailing is finished';

if($subscribers){

    run('notifier/functions');
    run('site/page/page_view_functions');

    // search for template
    $template_subscription = site_get_template($this_site_info, 'template_subscription');
    if(isset($input_vars['debug']) && $input_vars['debug']==$input_vars['action']) prn('$template_subscription='.$template_subscription);


    $message_subj=$this_site_info['title']."-".$this_news_info['title'];

    $report='Sending email <br>';

    $i=1;

    // put each message into notificator queue and show each subscriber
    foreach ($subscribers as $subscriber_info) {
        echo "Sending to {$subscriber_info['news_subscriber_name']}";


        // generate unique code
        $code=  md5(time().session_id().  rand(0, 10000));
        // check if code is unique
        while(db_getonerow("select * from {$table_prefix}news_subscriber WHERE news_subscriber_code='$code'")){
            $code=  md5(time().session_id().  rand(0, 10000));
        }

        // save code to database
        db_execute("UPDATE {$table_prefix}news_subscriber SET news_subscriber_code='$code' WHERE news_subscriber_id={$subscriber_info['news_subscriber_id']}");

        // compose "unsubscribe" link
        $unsubscribe_link=site_root_URL."/index.php?action=news_subscription/unsubscribe&site_id={$site_id}&code=".$code;

        // compose "unsubscribe" link
        $view_news_details_link=site_root_URL."/index.php?action=news/view_details&news_id={$news_id}&lang={$lang}";

        // draw message (HTML code)
        $message_body= process_template($template_subscription, Array(
                            'news'=>$this_news_info
                          , 'subscriber'=>$subscriber_info
                          , 'site' => $this_site_info
                          , 'site_root_url' => site_root_URL
                          , 'text' => $txt
                          , 'lang'=>$lang
                          , 'view_news_details_link'=>$view_news_details_link
                          , 'unsubscribe_link'=>$unsubscribe_link
                       ));

        notification_queue($subscriber_info['news_subscriber_email'], $message_subj, $message_body, 'notify_action_email_html');

        $report.="<div>{$i} {$subscriber_info['news_subscriber_name']} {$subscriber_info['news_subscriber_email']} - OK</div>";
        $i++;

    }

    // redirect to this page
    $next_page_location=site_root_URL."/index.php?action=news_subscription/send_news&news_id={$news_id}&lang={$lang}&start=".($start+$N);

     echo "<html>
        <head>
         <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".site_charset."\">
         <meta http-equiv=\"Refresh\" content=\"5;URL={$next_page_location}\">
        </head>
        <body>
         {$report}

        <div><a href=\"{$next_page_location}\">Send next portion</a></div>
        </body></html>";
}else{
     echo "<html>
        <head>
         <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".site_charset."\">
        </head>
        <body>
        All the messages are sent.
        </body></html>";
}
?>
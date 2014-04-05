<?php
/*
 * Send notifiers
*/

if(!function_exists('my_mail')) {
    run('lib/mailing');
    run('lib/class.phpmailer');
    run('lib/class.smtp');
}
if(!function_exists('site_get_template')) {
    run('site/menu');
}

function notify($event,$site_info,$data) {
    $site_templates=Array();
    $listeners=db_getrows("SELECT * FROM {$GLOBALS['table_prefix']}listener WHERE site_id={$site_info['id']} AND listener_event='{$event}'");
    //prn("SELECT * FROM {$GLOBALS['table_prefix']}listener WHERE site_id={$site_info['id']} AND listener_event='{$event}'",$listeners);

    foreach($listeners as $ls) {
        // get site template path
        if(!isset($site_templates[$ls['listener_template']])) {
            $site_templates[$ls['listener_template']]=site_get_template($site_info,$ls['listener_template']);
        }
        $function_name='notify_action_'.$ls['listener_action'];
        if(!function_exists($function_name)) continue;


        $notification_queue_body=process_template($site_templates[$ls['listener_template']],array_merge($data,Array('site'=>$site_info)));
        //prn('$notification_queue_body',$notification_queue_body);

        $notification_queue_subject=get_langstring($site_info['title']).': '.text('Notifier_event_'.$event);

        ///$notification_queue_subject=notification_transliterate($notification_queue_subject);
        ///$notification_queue_body=notification_transliterate($notification_queue_body);
        $query="INSERT INTO {$GLOBALS['table_prefix']}notification_queue(
                    notification_queue_to,
                    notification_queue_subject,
                    notification_queue_body,
                    notification_queue_attempts,
                    notification_queue_function)
                VALUES(
                    '".DbStr($ls['listener_sendto'])."',
                    '".DbStr($notification_queue_subject)."',
                    '".DbStr($notification_queue_body)."',
                    0,
                    '".DbStr($function_name)."')
        ";
        //prn($query);
        db_execute($query);
    }
}

function notification_queue($sendto,$subj,$body,$handler,$site_id=0) {
    $query="INSERT INTO {$GLOBALS['table_prefix']}notification_queue(
                    notification_queue_to,
                    notification_queue_subject,
                    notification_queue_body,
                    notification_queue_attempts,
                    notification_queue_function,
                    site_id)
                VALUES(
                    '".DbStr($sendto)."',
                    '".DbStr($subj)."',
                    '".DbStr($body)."',
                    0,
                    '".DbStr($handler)."',
                    '".((int)$site_id)."')
        ";
    //prn($query);
    db_execute($query);

}

function notification_transliterate($str) {
    //return iconv(site_charset, "cp1252//TRANSLIT", $str);
    $tor=str_replace(
            Array('ё' ,'ц' ,'ч' ,'ш' ,'щ'  ,'ю' ,'я' ,'ы','а','б','в','г','д','е','ж' ,'з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х' ,'э','ї' ,'і','І','ь',
            'Ё' ,'Ц' ,'Ч' ,'Ш' ,'Щ'  ,'Ю' ,'Я' ,'Ы','А','Б','В','Г','Д','Е','Ж' ,'З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х' ,'Э','?')
            ,Array('yo','ts','ch','sh','sch','yu','ya','y','a','b','v','g','d','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','e','yi','i','I','`',
            'yo','ts','ch','sh','sch','yu','ya','y','a','b','v','g','d','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','e','yi')
            ,$str);
    $tor=eregi_replace('[^a-z0-9_#:;.@<>"/&=%-]+',' ',$tor);
    return $tor;
}

function notification_queue_next($n_messages=1) {
    $query="SELECT *
            FROM {$GLOBALS['table_prefix']}notification_queue
            WHERE notification_queue_attempts<10
            ORDER BY notification_queue_id ASC LIMIT 0,$n_messages";
    $rows=db_getrows($query);
    foreach($rows as $row) {
        $query="UPDATE {$GLOBALS['table_prefix']}notification_queue
                SET notification_queue_attempts=notification_queue_attempts+1
                WHERE notification_queue_id={$row['notification_queue_id']}";
        db_execute($query);

        if( function_exists($row['notification_queue_function']) ) {
            $success=call_user_func(
                    $row['notification_queue_function'],
                    $row['notification_queue_to'],
                    $row['notification_queue_subject'],
                    $row['notification_queue_body']
            );
        }
        else $success=false;

        if($success) {
            $query="DELETE FROM {$GLOBALS['table_prefix']}notification_queue
                    WHERE notification_queue_id={$row['notification_queue_id']}";
            db_execute($query);
        }
        //echo $query;
        prn($row['notification_queue_id'],$row['notification_queue_subject'],$row['notification_queue_attempts'],$row['notification_queue_function'],'sucess='.$success);
    }
}

function notify_action_email($to,$subject,$body) {
    return my_mail($to,$subject,$body);
}

function notify_action_email_html($to,$subject,$body) {
    return my_mail($to,$subject,$body,true);
}

function notify_action_sms($to,$subject,$body) {
        ///$notification_queue_subject=notification_transliterate($notification_queue_subject);
        ///$notification_queue_body=notification_transliterate($notification_queue_body);
    return my_mail($to,notification_transliterate($subject),notification_transliterate($body));
}



?>

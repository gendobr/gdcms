<?php
/* 
 * CRON task to send notification
 * sample call is
 * wget <cms_root_url>/index.php?action=notifier/cron
 */
run('notifier/functions');

 echo "<html>
        <head>
         <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".site_charset."\">
         <meta http-equiv=\"Refresh\" content=\"60;URL=index.php?action=notifier/cron\">
        </head>
        <body>";

echo notification_queue_next(emails_at_once);

echo "</body></html>";

$GLOBALS['main_template_name']='';
return false;
?>

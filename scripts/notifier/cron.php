<?php
/* 
 * CRON task to send notification
 * sample call is
 * wget <cms_root_url>/index.php?action=notifier/cron
 */
run('notifier/functions');
echo notification_queue_next(emails_at_once);

$GLOBALS['main_template_name']='';
return false;
?>

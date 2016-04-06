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

$fname=\e::config('CACHE_ROOT').'/notifier_cron_log.txt';

if(emails_at_once>0 && time()-filemtime($fname)>=58){
	$log=notification_queue_next(emails_at_once);
	file_put_contents($fname, $log);
	echo $log;
}else{
	echo "------";
}
echo "</body></html>";

$GLOBALS['main_template_name']='';
return false;
?>

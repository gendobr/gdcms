<?php
/*
  Approve news publication
  argument is $news_id    - news identifier, integer, mandatory
  $lang       - news language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
 */


$debug = false;

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
if ($debug) {
    prn('$this_site_info=', $this_site_info);
}
//------------------- site info - end ------------------------------------------
//----------------------------- draw page - begin ------------------------------
$GLOBALS['main_template_name'] = '';


#    $site_id - site identifier, integer, mandatory
#    $lang    - interface language, char(3), mandatory (rus|ukr|eng)
#    $rows    - number of rows< integer, optional
#    $abstracts =yes|no (default is "yes")
#    $template=<template file name>, file name (extension is ".html"),
#              template placed in site root directory.
#    $date=asc if the oldest messages must appear at top of the list
#    $date=desc if the newest messages must appear at top of the list
#
#    $category=<category_id> restrict category
# ------------------------ list of categories - begin -------------------------
$query = "SELECT category_id, category_title, deep FROM <<tp>>category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
$tmp = \e::db_getrows($query);
$list_of_categories = Array();
foreach ($tmp as $tm) {
    $list_of_categories[$tm['category_id']] = str_repeat(' + ', $tm['deep']) . get_langstring($tm['category_title']);
}
unset($tmp, $tm);
//prn($list_of_categories);
# ------------------------ list of categories - end ---------------------------


$uid = time();
$iframe = '';


$iframe.='
    <h4>'.text('rss_imported_items_page').'</h4>
    <div style="color:blue;padding:10px; width:90%; overflow:scroll;height:80pt;border:1px solid red;"><pre>' .
        htmlspecialchars("
        <a href=\"" . site_root_URL . "/index.php?action=rss_aggregator/view&site_id={$site_id}&lang={$_SESSION['lang']}\">'.text('rss_imported_items_page').'</a>
    ") . '</pre></div>';

        
$iframe.='
    <h4>'.text('rss_imported_items_rss').'</h4>
    <div style="color:blue;padding:10px; width:90%; overflow:scroll;height:80pt;border:1px solid red;"><pre>' .
        htmlspecialchars("
        <a href=\"" . site_root_URL . "/index.php?action=rss_aggregator/rss&site_id={$site_id}&lang={$_SESSION['lang']}\">".text('rss_imported_items_rss')."</a>
    ") . '</pre></div>';

        
$iframe.='
    <h4>'.text('rss_imported_items_block').'</h4>
    <div style="color:blue;padding:10px; width:90%; overflow:scroll;height:80pt;border:1px solid red;"><pre>' .
        htmlspecialchars("
<div id=rssitems$uid></div>
<iframe style='width:1px;height:1px;border:none;opacity:0;' src='"
                . site_root_URL
                . "/index.php?action=rss_aggregator/block"
                . "&site_id={$site_id}"
                . "&lang={$_SESSION['lang']}"
                . "&rows_per_page=10"
                . "&template=template_rss_aggregator_block"
                . "&element=rssitems$uid"
                . "'></iframe>") . '</pre></div>';
?><html>
    <head>
        <META content="text/html; charset=<?= site_charset ?>" http-equiv="Content-Type">
        <link rel="stylesheet" href="img/styles.css" type="text/css">
        <title><?= get_langstring($this_site_info['title']) ?> - <?=text('rss_imported_records')?></title>

        <script language="javascript" type="text/javascript" src="scripts/lib/jquery.min.js"></script>
        <script language="javascript" type="text/javascript" src="scripts/lib/jquery-ui.min.js"></script>
        <link href="scripts/lib/jquery-ui/1.8/themes/base/jquery.ui.all.css" rel="stylesheet" type="text/css"/>
        <script language="javascript" type="text/javascript" src="scripts/lib/startup.js"></script>
        <script language="javascript" type="text/javascript">
            $(window).load(function () {
                setInterval(function () {
                    $('#result').load('index.php?action=ping');
                }, 5 * 60 * 1000);
            });
        </script>
    </head>
    <body leftmargin="5" topmargin="5" style='background-color:white;'><?php

        echo $iframe;

        ?></body></html>

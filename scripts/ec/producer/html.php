<?php
/*
  Approve news publication
  argument is $news_id    - news identifier, integer, mandatory
              $lang       - news language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/


$debug=false;

//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  if($debug) prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------

//----------------------------- draw page - begin ------------------------------
  $GLOBALS['main_template_name']='';


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
    $query="SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
    $tmp=db_getrows($query);
    $list_of_categories=Array();
    foreach($tmp as $tm) $list_of_categories[$tm['category_id']]=str_repeat(' + ',$tm['deep']).get_langstring($tm['category_title']);
    unset($tmp,$tm);
    //prn($list_of_categories);
 # ------------------------ list of categories - end ---------------------------


$uid=time();
$iframe ='';


    $iframe.='
    <h4>'.text('ec_producer_list_page').'</h4>
    <div style="color:blue;padding:10px; width:90%; overflow:scroll;height:80pt;border:1px solid red;"><pre>'.
    checkStr("
        <a href=\"".site_root_URL."/index.php?action=ec/producer/names&site_id={$site_id}&lang={$_SESSION['lang']}\">".text('EC_producer_list')."</a>
    ").'</pre></div>';

    $iframe.='
    <h4>'.text('EC_producer_html_code').'</h4>
    <div style="color:blue;padding:10px; width:90%; overflow:scroll;height:80pt;border:1px solid red;"><pre>'.
    checkStr("<div id=brands$uid></div>
<script type=\"text/javascript\" src=\"" . site_public_URL . "/scripts/lib/ajax_loadblock.js\"></script>
<script type=\"text/javascript\">
ajax_loadblock('brands{$uid}','".site_public_URL."/index.php?action=ec/producer/block&site_id={$site_id}&lang={$_SESSION['lang']}&rows_per_page=10&template=template_ec_producer_block',null);
</script>").'</pre></div>';

?><html>
<head>
<META content="text/html; charset=<?=site_charset?>" http-equiv=Content-Type>
<link rel="stylesheet" href="img/styles.css" type="text/css">
<title><?=  get_langstring($this_site_info['title'])?> - <?=text('EC_producers')?></title>

<script language="javascript" type="text/javascript" src="scripts/lib/jquery.min.js"></script>
<script language="javascript" type="text/javascript" src="scripts/lib/jquery-ui.min.js"></script>
<link href="scripts/lib/jquery-ui/1.8/themes/base/jquery.ui.all.css" rel="stylesheet" type="text/css"/>
<script language="javascript" type="text/javascript" src="scripts/lib/startup.js"></script>
<script language="javascript" type="text/javascript">
$(window).load(function () {
  setInterval(function(){
      $('#result').load('index.php?action=ping');
  },5*60*1000);
});
</script>
</head>
<body leftmargin="5" topmargin="5" style='background-color:white;'><?php

 echo $iframe;

 ?></body></html>

<?php

//$event_list=event_get_by_date($input_vars['site_id'],date('Y'),date('m'),date('d'), date('H'), date('i'));
// /index.php?action=calendar/get_by_date&site_id=1&y=2011&m=3&d=23&h=23&i=48  => 15
// /index.php?action=calendar/get_by_date&site_id=1&y=2011&m=05&d=1&h=23&i=10  => 18, 19
// /index.php?action=calendar/get_by_date&site_id=1&y=2011&m=05&d=9&h=23&i=10  => 18
// /index.php?action=calendar/get_by_date&site_id=1&y=2011&m=12&d=31&h=23&i=10 => 17, 19
// /index.php?action=calendar/get_by_date&site_id=1&y=2012&m=01&d=01&h=00&i=10 => 17, 19
// /index.php?action=calendar/get_by_date&site_id=1&y=2011&m=03&d=26&h=00&i=10 => 19
// /index.php?action=calendar/get_by_date&site_id=12&y=2011&m=03&d=28

header('Access-Control-Allow-Origin: *');

global $main_template_name;
$main_template_name = '';

// ------------------------- load messages - begin -----------------------------
if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
$input_vars['lang'] = get_language('lang');
global $txt;
$txt = load_msg($input_vars['lang']);
// ------------------------- load messages - end -------------------------------
//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

if (!$this_site_info){
    die($txt['Site_not_found']);
}
//------------------- get site info - end --------------------------------------

run('site/page/page_view_functions');
run('calendar/functions');

$y = isset($input_vars['y']) ? (int) $input_vars['y'] : date('Y'); // year
$m = isset($input_vars['m']) ? (int) $input_vars['m'] : date('m'); // month
$d = isset($input_vars['d']) ? (int) $input_vars['d'] : date('d'); // day of month
$h = isset($input_vars['h']) ? (int) $input_vars['h'] : date('H');// -1; // hours
$i = isset($input_vars['i']) ? (int) $input_vars['i'] : date('i');// -1; // minutes

//echo "event_get_by_date($site_id, $y, $m, $d, $h, $i)";
$event_list = event_get_by_date($site_id, $y, $m, $d, $h, $i,$verbose=isset($input_vars['verbose']));


//prn($event_list);
// restrict by category
if(count($event_list)>0 &&  isset($input_vars['category_id'])){
    // get categories for events
    $ids=Array();
    foreach($event_list as $event){
        $ids[]=$event['id'];
    }
    //prn($ids);
    $query="SELECT event_id
            FROM {$GLOBALS['table_prefix']}calendar_category AS cc
            WHERE category_id IN(
                    SELECT ch.category_id
                    FROM  {$GLOBALS['table_prefix']}category pa, {$GLOBALS['table_prefix']}category ch
                    WHERE pa.site_id={$site_id}
                      AND ch.site_id={$site_id}
                      AND pa.category_id=".( (int)$input_vars['category_id'] )."
                      AND pa.start <= ch.start AND ch.finish <= pa.finish
            )
            AND event_id IN(".join(',',$ids).");";
    $tmp = db_getrows($query);
    $checked_id=Array();
    foreach($tmp as $tm){
        $checked_id[$tm['event_id']]=$tm['event_id'];
    }
    $cnt=count($event_list);
    for($i=0; $i<$cnt; $i++){
        if(!isset($checked_id[$event_list[$i]['id']])){
            unset($event_list[$i]);
        }
    }
    $event_list=array_values($event_list);
}

//prn('event_list=', $event_list);
# check if template name is posted
$subtemplate=false;
if (isset($input_vars['template'])) {
    $subtemplate=site_get_template($this_site_info,$_REQUEST['template']);
}
if(!$subtemplate){
    $subtemplate=site_get_template($this_site_info,'template_calendar_view_block');
}
// prn('$subtemplate',$subtemplate);
# ---------------------- choose template - end ---------------------------------

$vyvid = process_template($subtemplate
                , Array(
                    'site'=>$this_site_info,
                    'event_list'=>$event_list
                  ));
//echo $vyvid;



echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset='.site_charset.'">
  </head>
  <body>
';
if (isset($input_vars['element'])) {
    echo "
    <div id=toinsert>$vyvid</div>
    <script type=\"text/javascript\">
    <!--
    var from = document.getElementById('toinsert');
    //alert(from.innerHTML);
    var to;
    if(window.top)
    {
      //alert('window.top - OK');
      if(window.top.document)
      {
        //alert('window.top.document - OK');
        to = window.top.document.getElementById('{$input_vars['element']}');
        //alert(to);
        if(to)
        {
           //alert('element - OK');
           to.innerHTML = from.innerHTML;
        }
      }
    }
    // -->
    </script>
    "
    ;
} else {
    echo $vyvid;
}

echo '
    </body>
</html>
';
// remove from history
nohistory($input_vars['action']);

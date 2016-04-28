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
$input_vars['lang'] = $lang = get_language('lang,interface_lang');
global $txt;
$txt = load_msg($input_vars['lang']);
// ------------------------- load messages - end -------------------------------
// 
//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

if (!$this_site_info) {
    die($txt['Site_not_found']);
}
//------------------- get site info - end --------------------------------------

run('site/page/page_view_functions');
run('calendar/functions');

$y = \e::cast('integer',\e::request('y', date('Y')));
$m = \e::cast('integer',\e::request('m', date('m'))); 
if($m<10) $m="0$m";

$d = \e::cast('integer',\e::request('d', date('d')));
if($d<10){
    $d="0$d";
}

$interval=\e::request('interval', '1W');
if(!preg_match("/^\\d+[YMDW]\$/",$interval)){
    $interval='1W';
}

// get posted start date
$startDate=new \DateTime("$y-$m-$d");

$finishDate=new \DateTime("$y-$m-$d");
$finishDate->add(new DateInterval("P{$interval}"));
$step = new DateInterval('P1D');
$daterange = new DatePeriod($startDate, $step ,$finishDate);


$query=[];
foreach($daterange as $date){
    $dt=explode('-',$date->format("Y-m-d"));
    $query[]=" (Y={$dt[0]} AND m={$dt[1]} AND d={$dt[2]}) ";
}
$query = "SELECT *
          FROM <<tp>>calendar_days_cache 
          WHERE site_id={$this_site_info['id']} 
            AND ( ".join(' OR ', $query)." )  
          ORDER BY Y, m, d;";
// prn(htmlspecialchars($query));
$event_days = \e::db_getrows($query,[],false);
// prn($event_days);
// exit();

//prn($event_days);
// extract event ids
$event_ids = array_map(function($in) { return $in['calendar_id'];}, $event_days);
// prn($event_ids);
// restrict by category
if (count($event_ids) > 0 && isset($input_vars['category_id']) && $input_vars['category_id'] > 0) {
    // get categories for events
    $ids = Array();
    foreach ($event_ids as $event) {
        $ids[] = $event;
    }
    //prn($ids);
    $query = "SELECT event_id
            FROM <<tp>>calendar_category AS cc
            WHERE category_id IN(
                    SELECT ch.category_id
                    FROM  <<tp>>category pa, <<tp>>category ch
                    WHERE pa.site_id={$site_id}
                      AND ch.site_id={$site_id}
                      AND pa.category_id=" . ( (int) $input_vars['category_id'] ) . "
                      AND pa.start <= ch.start AND ch.finish <= pa.finish
            )
            AND event_id IN(" . join(',', $ids) . ");";
    $tmp = \e::db_getrows($query);
    $checked_id = Array();
    foreach ($tmp as $tm) {
        $checked_id[$tm['event_id']] = $tm['event_id'];
    }
    $cnt = count($event_ids);
    for ($i = 0; $i < $cnt; $i++) {
        if (!isset($checked_id[$event_ids[$i]])) {
            unset($event_ids[$i]);
        }
    }
    $event_ids = array_values($event_ids);
}
// prn($event_ids);

if (count($event_ids) > 0) {
    $event_list = \e::db_getrows("select * from <<tp>>calendar where vis and id in(" . join(',', $event_ids) . ")");
} else {
    $event_list = Array();
}
$events = get_view($event_list, $input_vars['lang']);
$map = Array();
foreach ($events as $ev) {
    $map[$ev['id']] = $ev;
}
unset($events);
// prn($map);

$cnt = count($event_days);
for ($i = $cnt - 1; $i >= 0; $i--) {
    if (isset($map[$event_days[$i]['calendar_id']])) {
        $event_days[$i]['event'] = $map[$event_days[$i]['calendar_id']];
        $event_days[$i]['startDate'] = "{$event_days[$i]['y']}-" .
                ( $event_days[$i]['m'] < 10 ? "0{$event_days[$i]['m']}" : $event_days[$i]['m'])
                . '-' . ( $event_days[$i]['d'] < 10 ? "0{$event_days[$i]['d']}" : $event_days[$i]['d'] ) . " "
                . ($event_days[$i]['h'] >= 0 ? $event_days[$i]['h'] : 0) . ":" . ($event_days[$i]['i'] >= 0 ? $event_days[$i]['i'] : 0);
    } else {
        unset($event_days[$i]);
    }
}
$event_days = array_values($event_days);


//prn('event_days=', $event_days);
# check if template name is posted
$subtemplate = false;
if (isset($input_vars['template'])) {
    $subtemplate = site_get_template($this_site_info, $input_vars['template']);
}
if (!$subtemplate) {
    $subtemplate = site_get_template($this_site_info, 'template_calendar_view_block');
}
//prn('$subtemplate',$subtemplate);
# ---------------------- choose template - end ---------------------------------

$vyvid = process_template($subtemplate, [ 'site' => $this_site_info, 'event_list' => $event_days  ]);
//echo $vyvid;



echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=' . site_charset . '">
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

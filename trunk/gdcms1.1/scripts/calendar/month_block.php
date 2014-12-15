<?php

/*
 * View month calendar
 */
//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);


//var_dump($this_site_info);
if (!$this_site_info) {
    die(text('Site_not_found'));
}
//------------------- get site info - end --------------------------------------

global $main_template_name;
$main_template_name = '';
$month = isset($input_vars['month']) ? ( (int) $input_vars['month'] ) : ( (int) date('m') );
$year = isset($input_vars['year']) ? ( (int) $input_vars['year'] ) : ( (int) date('Y') );


run('calendar/functions');

run('site/page/page_view_functions');

//-------------------------- load messages - begin -----------------------------
if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
        $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
$lang=$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
//-------------------------- load messages - end -------------------------------



$timestamps = Array();
$first_timestamp = mktime($hour = 12, $minute = 00, $second = 00, $month, $day = 1, $year);
$dt = 24 * 3600; // 1 day
for ($i = 0; $i < 32; ++$i) {
    $timestamps[$i] = $first_timestamp + $i * $dt;
}
//prn($timestamps);
$month = (int) date('m', $first_timestamp);
$year = (int) date('Y', $first_timestamp);




$shift = date('w', $timestamps[0]);
//prn($shift);
$days = Array();
for ($i = 0; $i < $shift; $i++) {
    $days[] = '';
}
foreach ($timestamps as $tms) {
    if (date('m', $tms) == $month) {
        $days[] = date('d', $tms);
    }
}
//prn($days);
/*
 */
$month_names = Array(1 => $txt['month_January'], 2 => $txt['month_February'],
    3 => $txt['month_March'], 4 => $txt['month_April'],
    5 => $txt['month_May'], 6 => $txt['month_June'],
    7 => $txt['month_July'], 8 => $txt['month_August'],
    9 => $txt['month_September'], 10 => $txt['month_October'],
    11 => $txt['month_November'], 12 => $txt['month_December']);

$weekday_names = Array(-1 => '--',
    0 => $txt['weekday_short_sunday'],
    1 => $txt['weekday_short_monday'],
    2 => $txt['weekday_short_tuesday'],
    3 => $txt['weekday_short_wednesday'],
    4 => $txt['weekday_short_thursday'],
    5 => $txt['weekday_short_friday'],
    6 => $txt['weekday_short_saturday']);

$calendar = array_chunk($days, 7);
// prn($calendar);




$month_table=Array();

$month_table['view_day_events_url_template']=site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month&year={year}&month={month}&day={day}";
//$month_table['other_month_url_template']=site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month_block&month={month}&year={year}";
$month_table['other_month_url_template']=site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month&month={month}&year={year}";

// draw navigator
$month_table['next_month_link'] = str_replace(Array('{year}','{month}'),Array($year,$month+1),$month_table['other_month_url_template']);
$month_table['prev_month_link'] = str_replace(Array('{year}','{month}'),Array($year,$month-1),$month_table['other_month_url_template']);

$month_table['next_year_link'] = str_replace(Array('{year}','{month}'),Array(($year + 1),$month),$month_table['other_month_url_template']); 
$month_table['prev_year_link'] = str_replace(Array('{year}','{month}'),Array(($year - 1),$month),$month_table['other_month_url_template']); 



$month_table['month_name']=$month_names[$month];
$month_table['month']=$month;
$month_table['year']=$year;
$month_table['weekdays']=$weekday_names;
unset($month_table['weekdays'][-1]);



$month_table['days']=Array();



foreach ($calendar as $row) {
    $tr = Array();
    foreach ($row as $day) {
        if (events_exist($year, $month, $day, $this_site_info)) {
            $view_day_events_url = str_replace(Array('{year}','{month}','{day}'),Array($year,$month,$day),$month_table['view_day_events_url_template']);
            $tr[]=Array('innerHTML'=>$day,'href'=>$view_day_events_url, 'year'=>$year, 'month'=>$month, 'day'=>$day);
        } else {
            $tr[]=Array('innerHTML'=>$day,'href'=>'', 'year'=>$year, 'month'=>$month, 'day'=>$day);
        }
    }
    for ($i = count($row); $i < 7; $i++) {
        $tr[]=Array('innerHTML'=>'','href'=>'');
    }
    $month_table['days'][]=$tr;
}




////------------------------ draw using SMARTY template - begin ----------------
if(isset($input_vars['verbose'])){
    prn($month_table);
}
// draw main
$_template = site_get_template($this_site_info, 'template_calendar_block');
$vyvid = process_template($_template
                , Array(
                      'month_table' => $month_table
                    , 'text' => $txt
                )
);
if (isset($input_vars['element'])) {
    echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\">
<html>
  <head>
    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . site_charset . "\">
  </head>
  <body>
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
    </body>
</html>
";
} else {
    echo $vyvid;
}
?>
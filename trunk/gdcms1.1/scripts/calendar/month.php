<?php

/*
 * View month calendar
 */


// на сколько лет вперёд показывать календарь
define('max_forward_years', 2);

//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);



//var_dump($this_site_info);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}
//------------------- get site info - end --------------------------------------

global $main_template_name;
$main_template_name = '';
$month = isset($input_vars['month']) ? ( (int) $input_vars['month'] ) : ( (int) date('m') );
$year = isset($input_vars['year']) ? ( (int) $input_vars['year'] ) : ( (int) date('Y') );

$current_year=date('Y');
if($year-$current_year>max_forward_years){
   $year=$current_year+max_forward_years;
}
if($current_year-$year>max_forward_years){
    $year=$current_year-max_forward_years;
}


run('calendar/functions');

run('site/page/page_view_functions');

//-------------------------- load messages - begin -----------------------------
if (isset($input_vars['interface_lang'])) {
    if ($input_vars['interface_lang']) {
        $input_vars['lang'] = $input_vars['interface_lang'];
    }
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
// create table


$month_table=Array();

// draw navigator
$month_prefix = site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month&year={$year}&month=";
$month_next = $month_prefix . ($month + 1);
$month_prev = $month_prefix . ($month - 1);
$year_prefix = site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month&month={$month}&year=";
$year_next = $year_prefix . ($year + 1);
$year_prev = $year_prefix . ($year - 1);


$month_table['prev_month_link']=$month_prev;
$month_table['month_name']=$month_names[$month];
$month_table['next_month_link']=$month_next;
$month_table['prev_year_link']=$year_prev;
$month_table['next_year_link']=$year_next;
$month_table['year']=$year;
$month_table['weekdays']=$weekday_names;
//prn($month_table['weekdays']);
unset($month_table['weekdays'][-1]);

$month_table['days']=Array();

$view_day_events_url_prefix = site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month&year={$year}&month={$month}&day=";
foreach ($calendar as $row) {
    $tr = Array();
    foreach ($row as $day) {
        if (events_exist($year, $month, $day, $this_site_info)) {
            $view_day_events_url = $view_day_events_url_prefix . $day;
            $tr[]=Array('innerHTML'=>$day,'href'=>$view_day_events_url);
        } else {
            $tr[]=Array('innerHTML'=>$day,'href'=>'');
        }
    }
    for ($i = count($row); $i < 7; $i++) {
        $tr[]=Array('innerHTML'=>'','href'=>'');
    }
    $month_table['days'][]=$tr;
}
// prn($month_table);


// if day is set ...
$day = isset($input_vars['day']) ? ( (int) $input_vars['day'] ) : 0;

if ($day > 0) {
    $events = get_view(event_get_by_date($this_site_info['id'], $year, $month, $day,-1, -1, false),$lang);
    //prn($events);
    //exit();
}else{
    $events=false;
}

///exit('124');
////------------------------ draw using SMARTY template - begin ----------------

// draw main
$_template = site_get_template($this_site_info, 'template_calendar_list');
$vyvid = process_template($_template
                , Array(
                      'events' => $events
                    , 'text' => $txt
                    , 'year'=>$year
                    , 'month'=>$month
                    , 'day'=>$day
                    , 'month_table'=>$month_table
                )
);


// load site menu
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);


//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = $lang_list[$i]['href'];
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}

//------------------------ get list of languages - end -------------------------

  $this_site_info['title']=get_langstring($this_site_info['title'],$input_vars['lang']);
  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$txt['Calendar_event_list']
                                               ,'content'=>$vyvid
                                               ,'abstract'=> ''
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$input_vars['lang']
                                               ,'editURL'=>site_URL."?action=calendar/list&site_id={$site_id}"
                                          )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;
?>
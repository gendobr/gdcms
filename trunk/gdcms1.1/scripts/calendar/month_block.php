<?php

/*
 * View month calendar
 */

header('Access-Control-Allow-Origin: *');

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


$month = (int) date('m', $first_timestamp);
$year = (int) date('Y', $first_timestamp);



$month_table=getMonthTable($year, $month,$this_site_info);



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
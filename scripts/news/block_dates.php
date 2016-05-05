<?php
/**
 * ���� � ������, ����� ���� �������
 */
global $main_template_name; $main_template_name='';

//------------------- site info - begin ----------------------------------------
run('site/menu');
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    echo 'Site_not_found';
    return 0;
}
//------------------- site info - end ------------------------------------------
// get language
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = \e::config('default_language');
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = \e::config('default_language');
}
$txt = load_msg($input_vars['lang']);
$lang = get_language('lang');




# ---------------------- choose template - begin -------------------------------
$_template = false;
if(isset($input_vars['template']) && strlen(trim($input_vars['template']))>0){
    $_template = site_get_template($this_site_info, $input_vars['template']);
}else{
    $_template = site_get_template($this_site_info, 'template_news_dates_block');
}
if(!$_template){
    $_template = 'cms/template_news_dates_block';
}
# ---------------------- choose template - end ---------------------------------

# ---------------------- get news dates - begin --------------------------------
$query="SELECT YEAR(last_change_date) as news_year,
               MONTH(last_change_date) as news_month,
               count(id) as n_news
        FROM <<tp>>news
        WHERE site_id={$this_site_info['id']}
          AND lang='".\e::db_escape($lang)."'
          AND cense_level>={$this_site_info['cense_level']}
        GROUP BY news_year, news_month
        ORDER BY news_year DESC, news_month ASC
        ";
//prn($query);
$dates =  \e::db_getrows($query);

$month_names = Array('', $txt['month_January'], $txt['month_February'], $txt['month_March'], $txt['month_April'], $txt['month_May'], $txt['month_June'], $txt['month_July'], $txt['month_August'], $txt['month_September'], $txt['month_October'], $txt['month_November'], $txt['month_December']);
for($cnt=count($dates), $i=0; $i<$cnt; $i++){
    $dates[$i]['news_month_name']=$month_names[$dates[$i]['news_month']];
    $dates[$i]['URL']=site_URL."?action=news/view&site_id={$this_site_info['id']}&lang={$lang}&news_date_year={$dates[$i]['news_year']}&news_date_month={$dates[$i]['news_month']}";
}
// prn($dates);
# ---------------------- get news dates - end ----------------------------------



run('site/page/page_view_functions');
  #prn('$news_template',$news_template);
  $vyvid=process_template( $_template
                                ,Array(
                                  'dates'=>$dates
                                 ,'text'=>$txt
                                ));

if(strlen($vyvid)==0) {echo '';return '';}
echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset='.site_charset.'">
  </head>
  <body>
';
if(isset($input_vars['element']))
{
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
}
else echo $vyvid;

echo '
    </body>
</html>
';
// remove from history
   nohistory($input_vars['action']);



?>
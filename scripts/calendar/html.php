<?php
/*
 * 
 */
//------------------- site info - begin ----------------------------------------
run('site/menu');
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------


$uid=time();


$lang_list = list_of_languages();
$lang_options=[];
foreach($lang_list as $it){
    $lang_options[$it['name']]=$it['name'];
}
// \e::info($lang_list);
$input_lang=  \core\form::draw_options(\e::config('default_language'), $lang_options);



$category_list =  array_map(
    function($row){
        return [
            $row['category_id'],
            str_repeat('&nbsp;|&nbsp;&nbsp;&nbsp;',  $row['deep'] ).get_langstring($row['category_title'])
        ];
    },
    \e::db_getrows("SELECT * FROM <<tp>>category WHERE site_id=<<integer site_id>> ORDER BY start ASC",['site_id'=>$site_id])
);
$category_options=\core\form::draw_options('', $category_list);


$input_vars['page_title'] =
$input_vars['page_header'] =text('Calendar_widget_html_code');
$input_vars['page_content'] = "

  
<div>Lang: <select id=input_lang>{$input_lang}</select></div>
<div>".text('Calendar_widget_template_file').":<input type=text id=input_template><input type=button value=\"OK\"></div>
<div>".text('Category').":<select id=input_category>{$category_options}</select></div>
    
<br/>



  <h3>".text('Calendar_widget_current_events')."</h3>
  <pre style='padding:10px;width:100%; height:150pt;overflow:scroll;'>
&lt;script type=\"text/javascript\" src=\"".\e::config('APPLICATION_PUBLIC_URL')."/cms/scripts/lib/ajax_loadblock.js\"&gt;&lt;/script&gt;
&lt;div id=calendar$uid&gt;&nbsp;&lt;/div&gt;
&lt;script type=\"text/javascript\"&gt;
ajax_loadblock('calendar$uid','"
 .\e::config('APPLICATION_PUBLIC_URL')
 ."/index.php?action=calendar/get_by_date"
 ."&amp;site_id={$site_id}"
 ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
 ."&amp;rows=<span id=s_rows>10</span>"
 ."&amp;template=<span class=s_template></span>"
 ."&amp;category_id=<span class=s_category></span>"
 ."',null);
&lt;/script&gt;
  </pre>
  



<h3>".text('Calendar_widget_today_events')."</h3>
<pre style='padding:10px;width:100%; height:150pt;overflow:scroll;'>
&lt;script type=\"text/javascript\" src=\"".\e::config('APPLICATION_PUBLIC_URL')."/scripts/lib/ajax_loadblock.js\"&gt;&lt;/script&gt;
&lt;div id=calendar$uid&gt;&nbsp;&lt;/div&gt;
&lt;script type=\"text/javascript\"&gt;
ajax_loadblock('calendar$uid','"
     .\e::config('APPLICATION_PUBLIC_URL')
     ."/index.php?action=calendar/get_by_date"
     ."&amp;site_id={$site_id}"
     ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
     ."&amp;rows=<span class=s_rows>10</span>"
     ."&amp;template=<span class=s_template></span>"
     ."&amp;category_id=<span class=s_category></span>"
     ."&amp;h=-1"
     ."&amp;i=-1"
     ."',null);
&lt;/script&gt;
  </pre>


<h3>".text('Calendar_widget_events_interval')."</h3>
<div>".text('Date_interval').": 
<input type=text id=input_interval_num>
<select id=input_interval_unit><option value=''>-</option><option value=Y>Year</option><option value=M>Month</option><option value=W>Week</option><option value=D>Days</option></select>
</div>
<pre style='padding:10px;width:100%; height:150pt;overflow:scroll;'>
&lt;script type=\"text/javascript\" src=\"".\e::config('APPLICATION_PUBLIC_URL')."/scripts/lib/ajax_loadblock.js\"&gt;&lt;/script&gt;
&lt;div id=calendar$uid&gt;&nbsp;&lt;/div&gt;
&lt;script type=\"text/javascript\"&gt;
ajax_loadblock('calendar$uid','"
     .\e::config('APPLICATION_PUBLIC_URL')
     ."/index.php?action=calendar/get_by_date_interval"
     ."&amp;site_id={$site_id}"
     ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
     ."&amp;rows=<span class=s_rows>10</span>"
     ."&amp;template=<span class=s_template></span>"
     ."&amp;category_id=<span class=s_category></span>"
     ."&amp;interval=<span class=s_interval_num></span><span class=s_interval_unit></span>"
     ."',null);
&lt;/script&gt;
  </pre>




  <h3>".text('Calendar_widget_month')."</h3>
  <pre style='padding:10px;width:100%; height:150pt;overflow:scroll;'>
&lt;script type=\"text/javascript\" src=\"".\e::config('APPLICATION_PUBLIC_URL')."/scripts/lib/ajax_loadblock.js\"&gt;&lt;/script&gt;
&lt;div id=calendar$uid&gt;&nbsp;&lt;/div&gt;
&lt;script type=\"text/javascript\"&gt;
ajax_loadblock('calendar$uid','"
     .\e::config('APPLICATION_PUBLIC_URL')
     ."/index.php?action=calendar/month_block"
     ."&amp;site_id={$site_id}"
     ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
     ."&amp;template=<span class=s_template></span>"
     ."',null);
&lt;/script&gt;
  </pre>




<script type=\"text/javascript\">

$(document).ready(function(){
    \$(\"#input_lang\").change(function(){\$(\".s_lang\").html(\$(\"#input_lang\").val());});
    \$(\"#input_template\").keyup(function(){\$(\".s_template\").html(\$(\"#input_template\").val());});
    \$(\"#input_category\").change(function(){\$(\".s_category\").html(\$(\"#input_category\").val());});    
    
    \$(\"#input_interval_num\").keyup(function(){\$(\".s_interval_num\").html(\$(\"#input_interval_num\").val());});
    \$(\"#input_interval_unit\").change(function(){\$(\".s_interval_unit\").html(\$(\"#input_interval_unit\").val());});    
});
</script>
";
# ------------------------------------------------------------------------------
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);

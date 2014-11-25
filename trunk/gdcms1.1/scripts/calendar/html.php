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


$input_vars['page_title'] =
$input_vars['page_header'] =text('Calendar_widget_html_code');
$input_vars['page_content'] = "
<script type=\"text/javascript\">
<!--
function set_span_value(span_id,val)
{
  var sp
  sp=document.getElementById(span_id+'0');
  if(sp) sp.innerHTML=val;

  sp=document.getElementById(span_id+'1');
  if(sp) sp.innerHTML=val;
//
//  sp=document.getElementById(span_id+'2');
//  if(sp) sp.innerHTML=val;
//
//  sp=document.getElementById(span_id+'3');
//  if(sp) sp.innerHTML=val;
//
//  sp=document.getElementById(span_id+'4');
//  if(sp) sp.innerHTML=val;
}
// -->
</script>
  ".text('Calendar_widget_template_file').":
  <input type=text id=s_template_fld onchange=\"set_span_value('s_template',this.value)\"><input type=button value=\"OK\"><br />





  <h3>".text('Calendar_widget_current_events')."</h3>
  <pre style='padding:10px;width:100%; height:150pt;overflow:scroll;'>
&lt;script type=\"text/javascript\" src=\"".site_root_URL."/cms/scripts/lib/ajax_loadblock.js\"&gt;&lt;/script&gt;
&lt;div id=calendar$uid&gt;&nbsp;&lt;/div&gt;
&lt;script type=\"text/javascript\"&gt;
ajax_loadblock('calendar$uid','"
 .site_root_URL
 ."/index.php?action=calendar/get_by_date"
 ."&amp;site_id={$site_id}"
 ."&amp;lang=<span id=s_lang>{$_SESSION['lang']}</span>"
 ."&amp;rows=<span id=s_rows>10</span>"
 ."&amp;template=<span id=s_template0></span>"
 ."&amp;element=calendar$uid"
 ."',null);
&lt;/script&gt;
  </pre>
  <h3>".text('Calendar_widget_today_events')."</h3>
  <pre style='padding:10px;width:500pt; height:150pt;overflow:scroll;'>
&lt;script type=\"text/javascript\" src=\"".site_root_URL."/cms/scripts/lib/ajax_loadblock.js\"&gt;&lt;/script&gt;
&lt;div id=calendar$uid&gt;&nbsp;&lt;/div&gt;
&lt;script type=\"text/javascript\"&gt;
ajax_loadblock('calendar$uid','"
     .site_root_URL
     ."/index.php?action=calendar/get_by_date"
     ."&amp;site_id={$site_id}"
     ."&amp;lang=<span id=s_lang>{$_SESSION['lang']}</span>"
     ."&amp;rows=<span id=s_rows>10</span>"
     ."&amp;template=<span id=s_template1></span>"
     ."&amp;element=calendar$uid"
     ."&amp;h=-1"
     ."&amp;i=-1"
     ."',null);
&lt;/script&gt;
  </pre>

  <h3>".text('Calendar_widget_month')."</h3>
  <div>".site_root_URL."/index.php?action=calendar/month_block&site_id={$site_id}&interface_lang=rus</div>

";
# ------------------------------------------------------------------------------
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
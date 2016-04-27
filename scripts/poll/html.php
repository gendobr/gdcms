<?php
/*
  List of news for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

$debug=false;
  run('site/menu');

//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);

  // prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------


run('poll/functions');
























$input_vars['page_title']  = 
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['Poll_html_code'];

$polls=\e::db_getrows("SELECT * FROM {$table_prefix}golos_pynannja WHERE site_id={$site_id} AND is_active=1");
$pl=Array();
foreach($polls as $poll) $pl[$poll['id']]=shorten(strip_tags($poll['title']),40);
$pl=draw_options(isset($input_vars['poll_id'])?$input_vars['poll_id']:'',$pl);

$uid=md5(session_id());
$url=site_URL."?action=poll/block&amp;site_id=$site_id";
$input_vars['page_content']= "
<script type=\"text/javascript\">
<!--
function set_span_value(span_id,val)
{
  var sp
  sp=document.getElementById(span_id);
  if(sp) sp.innerHTML=val;
}
// -->
</script>

����:<select onchange='set_span_value(\"span_lang\",this.value)'><option value=ukr>ukr</option><option value=rus>rus</option><option value=eng>eng</option></select><br>
�������:<select onchange='set_span_value(\"span_poll_id\",this.value)'><option value=''>�� �������</option>{$pl}</select>
<br><br>

<div style='background-color:#e0e0e0;padding:10px;'>
  &lt;div id=poll{$uid}&gt; &lt;/div&gt;<br>
  &lt;iframe style='width:1px;height:1px;border:none;' src='{$url}&amp;lang=<span id=span_lang>ukr</span>&amp;poll_id=<span id=span_poll_id>0</span>&amp;element=poll{$uid}'&gt;&lt;/iframe&gt;
</div>
";

//--------------------------- context menu -- begin ----------------------------

  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

// remove from history
   nohistory($input_vars['action']);


?>
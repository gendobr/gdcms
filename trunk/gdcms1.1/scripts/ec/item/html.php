<?php
/**
 * Блок "Товары" для вставки в страницы
 */

$debug=false;
//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  if($debug) prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------
$GLOBALS['main_template_name']='design/popup';







# ------------------------ list of categories - begin --------------------------
  $query="SELECT ec_category_id, ec_category_title, deep FROM {$table_prefix}ec_category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
  $tmp=db_getrows($query);
  $list_of_categories=Array();
  foreach($tmp as $tm) $list_of_categories[$tm['ec_category_id']]=str_repeat(' + ',$tm['deep']-1).get_langstring($tm['ec_category_title']);
  unset($tmp,$tm);
# ------------------------ list of categories - end ----------------------------


# ------------------------ ordering variants - begin ---------------------------
$tmp=Array('ec_item_id', 'ec_item_lang', 'ec_item_title',
           'ec_item_last_change_date','ec_item_price',
           'ec_item_currency','ec_item_amount',
           'ec_producer_id', 'ec_category_id',
           'ec_item_uid','ec_item_purchases','rand()','ec_item_ordering_');
$orderby=Array();
foreach($tmp as $tm)
{
    $field=preg_replace('/_$/','',$tm);
    $orderby["{$field}+asc"]=text($tm).' '.text('ordering_asc');
    $orderby["{$field}+desc"]=text($tm).' '.text('ordering_desc');
}
//prn($orderby);
# ------------------------ ordering variants - end -----------------------------


# ------------------------ list of producers - begin --------------------------
  $query="SELECT ec_producer_id, ec_producer_title
          FROM {$table_prefix}ec_producer
          WHERE site_id={$site_id}
          ORDER BY ec_producer_title ASC";
  $tmp=db_getrows($query);
  $list_of_producers=Array();
  foreach($tmp as $tm) $list_of_producers[$tm['ec_producer_id']]=get_langstring($tm['ec_producer_title']);
  unset($tmp,$tm);
# ------------------------ list of producers - end ----------------------------

$uid=time();
$element_id='block'.$uid;
$tor="
<script type=\"text/javascript\" src=\"" . site_public_URL . "/scripts/lib/ajax_loadblock.js\"></script>
<script type=\"text/javascript\">
<!--
var spans=['1'];
var fld={
 'action':'ec/item/block',
 'site_id':'{$site_id}',
 'lang':'{$_SESSION['lang']}',
 'rows':'10',
 'orderby':'',
 'ec_category_id':'',
 'ec_producer_id':'',
 'template':'',
 'ec_item_keywords':'',
 'ec_item_tags':''
 //, 'element':'ecblock$uid',
};
var url;
function set_value(varname,varvalue)
{
  fld[varname]=varvalue;
  var sp,i;

  url='".site_root_URL."/index.php?';
  for(i in fld) url+= ( fld[i]?('&'+i+'='+fld[i]):'' );
  // alert(url);
  var code=url.replace(/\\&/g,'&amp;');
  // alert(code);
  for(i in spans)
  {
      sp=document.getElementById('s_url'+spans[i]);
      if(sp) sp.innerHTML=code;
  }
}

function preview()
{
  document.getElementById('{$element_id}').innerHTML='&nbsp;';
  ajax_loadblock('{$element_id}',url,null);
  //var previewframe = document.getElementById('previewframe');
  //previewframe.src = url;
}
// -->
</script>

<div style='background-color:#e0e0e0;padding:10pt;'>
<h4>".text('ec_item_html_change_parameters')."</h4>
".text('ec_item_html_change_tip')."
<br />

 ".text('ec_item_html_lang').":
 <select name=s_lang_selector onchange=\"set_value('lang',this.value);\">
   <option value='ukr'>ukr</option>
   <option value='rus'>rus</option>
   <option value='eng'>eng</option>
 </select><br />

 ".text('ec_item_html_rows').":
 <input type=text onchange=\"set_value('rows',this.value)\">
 <input type=button value=\"OK\">
<br />

 ".text('ec_item_html_orderby').":
 <select onchange=\"set_value('orderby',this.value)\">
   <option value=''></option>
   ".draw_options('',$orderby)."
 </select><br />

 ".text('ec_item_html_category').":
 <select onchange=\"set_value('ec_category_id',this.value)\">
 <option value=''> </option>
 ".draw_options('0',$list_of_categories)."
 </select><br />


 ".text('ec_item_html_producer')."
 <select onchange=\"set_value('ec_producer_id',this.value)\">
 <option value=''> </option>
 ".draw_options('0',$list_of_producers)."
 </select><br />

 ".text('ec_item_html_template').":
 <input type=text
        id=template_fld
        onchange=\"set_value('template',this.value)\">
 <input type=button value=\"OK\"><br />

 ".text('ec_item_html_keywords').":
 <input type=text
        id=ec_item_keywords_fld
        onchange=\"set_value('ec_item_keywords',this.value)\">
 <input type=button value=\"OK\"><br />


 ".text('ec_item_html_tags').":
 <input type=text
        id=ec_item_tags_fld
        onchange=\"set_value('ec_item_tags',this.value)\">
 <input type=button value=\"OK\"><br />


</div>



<br />




{$text['Get_html_link_man']}
 <font color=blue><div style='padding:10px; width:90%; overflow:scroll;height:80pt;border:1px solid blue;padding:10px;'><pre>
".
        checkStr("
<script type=\"text/javascript\" src=\"" . site_public_URL . "/scripts/lib/ajax_loadblock.js\"></script>
<div id={$element_id}> </div>
<script type=\"text/javascript\">
").
"\najax_loadblock('{$element_id}','<span id=s_url1></span>',null);\n".
checkStr("</script>"
            )
."

 </pre></div></font><br />
<br />

<script type=\"text/javascript\">
set_value('','');
</script>
<input type=button onclick='preview()' value='Preview'>
<div id={$element_id}> </div>
";

$input_vars['page_content']=$tor;
//echo $input_vars['page_content'];
// remove from history
   nohistory($input_vars['action']);


?>

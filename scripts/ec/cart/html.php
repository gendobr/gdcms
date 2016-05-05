<?php
/**
 * 
 */

$debug=false;
//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info =\e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
  if($debug) prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------
  $GLOBALS['main_template_name']='design/popup';







# ------------------------ list of categories - begin --------------------------
  $query="SELECT ec_category_id, ec_category_title, deep FROM <<tp>>ec_category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
  $tmp=\e::db_getrows($query);
  $list_of_categories=Array();
  foreach($tmp as $tm) $list_of_categories[$tm['ec_category_id']]=str_repeat(' + ',$tm['deep']-1).get_langstring($tm['ec_category_title']);
  unset($tmp,$tm);
# ------------------------ list of categories - end ----------------------------


# ------------------------ ordering variants - begin ---------------------------
$tmp=Array('ec_item_id', 'ec_item_lang', 'ec_item_title',
           'ec_item_last_change_date','ec_item_price',
           'ec_item_currency','ec_item_amount',
           'ec_producer_id', 'ec_category_id',
           'ec_item_uid');
$orderby=Array();
foreach($tmp as $tm)
{
    $orderby["{$tm}+asc"]=text($tm).' '.text('ordering_asc');
    $orderby["{$tm}+desc"]=text($tm).' '.text('ordering_desc');
}
//prn($orderby);
# ------------------------ ordering variants - end -----------------------------


# ------------------------ list of producers - begin --------------------------
  $query="SELECT ec_producer_id, ec_producer_title
          FROM <<tp>>ec_producer
          WHERE site_id={$site_id}
          ORDER BY ec_producer_title ASC";
  $tmp=\e::db_getrows($query);
  $list_of_producers=Array();
  foreach($tmp as $tm) $list_of_producers[$tm['ec_producer_id']]=get_langstring($tm['ec_producer_title']);
  unset($tmp,$tm);
# ------------------------ list of producers - end ----------------------------

$uid=time();

$tor="
<script type=\"text/javascript\">
<!--
var spans=['1'];
var fld={
 'action':'ec/cart/block',
 'site_id':'{$site_id}',
 'lang':'{$_SESSION['lang']}',
 'rows':'5',
 'orderby':'',
 'ec_category_id':'',
 'ec_producer_id':'',
 'template':'',
 'ec_item_keywords':'',
 'ec_item_tags':''
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
  document.getElementById('ecblock$uid').innerHTML='&nbsp;';
  var previewframe = document.getElementById('previewframe');
  previewframe.src = url;
}
// -->
</script>



<h1>".text('ec_item_html_onempty_cart').":</h1>

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
 <select onchange=\"set_value('rows',this.value)\">
   <option value='1'>1</option>
   <option value='2'>2</option>
   <option value='3'>3</option>
   <option value='4'>4</option>
   <option value='5' selected>5</option>
   <option value='6'>6</option>
   <option value='7'>7</option>
   <option value='8'>8</option>
   <option value='9'>9</option>
   <option value='10'>10</option>
 </select><br />

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






<br />




{$text['Get_html_link_man']}

 <font color=blue><div style='padding:10px; width:90%; overflow:scroll;height:80pt;border:1px solid blue;padding:10px;'><pre>
  &lt;div id=ecblock$uid&gt;&lt;a href=\"".site_public_URL."/index.php?action=ec/cart/view&lang={$_SESSION['lang']}&site_id={$site_id}\"&gt;".text('Shopping_Cart')."&lt;/a&gt;&lt;/div&gt;
  &lt;script type=\"text/javascript\" src=\"" . site_public_URL . "/scripts/lib/ajax_loadblock.js\">&lt;/script>
  &lt;script type=\"text/javascript\">
      ajax_loadblock(\"ecblock$uid\",\"<span id=s_url1></span>\",null);
  &lt;/script>
 </pre></div></font><br />
<br />

<script type=\"text/javascript\">
set_value('','');
</script>

";
$input_vars['page_title']=text('EC_cart_block_html_code');
$input_vars['page_content'] =  $tor;

// remove from history
   nohistory($input_vars['action']);

?>
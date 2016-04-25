<?php
/* 
 * cart block
 * Arguments are: site_id, lang, element
*/

global $main_template_name;
$main_template_name='';
run('site/menu');
run('ec/cart/functions');
run('site/page/page_view_functions');
run('ec/item/functions');
# -------------------- set interface language - begin --------------------------
$debug=false;
if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])   ) $input_vars['lang']=\e::config('default_language');
if(strlen($input_vars['lang'])==0) $input_vars['lang']=\e::config('default_language');
// $lang=$input_vars['lang'];
$lang = get_language('lang');
# -------------------- set interface language - end ----------------------------


# -------------------------- load messages - begin -----------------------------
global $txt;
$txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------


# ------------------- get site info - begin ------------------------------------

$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if(!$this_site_info) die($txt['Site_not_found']);
$this_site_info['title']=get_langstring($this_site_info['title'],$lang);
# ------------------- get site info - end --------------------------------------

# get block template
$_template = site_get_template($this_site_info,'template_ec_cart_block');

# get total amount
if(!isset($_SESSION['ec_cart'])) $_SESSION['ec_cart']=Array('items'=>Array());
if(!isset($_SESSION['ec_cart']['items'])) $_SESSION['ec_cart']['items']=Array();
$total=ec_cart_get_total($_SESSION['ec_cart']);

$n_cart_items=0;
foreach($_SESSION['ec_cart']['items'] as $it){
  $n_cart_items+=$it['amount'];
  //prn($it['amount']);
}
//$n_cart_items=count($_SESSION['ec_cart']['items']);

$url_cart_details=site_public_URL."/index.php?action=ec/cart/view&lang={$lang}&site_id={$site_id}";

if($n_cart_items==0) {
    if(isset($input_vars['rows'])) {$input_vars['rows']=abs(1*$input_vars['rows']);} else{$input_vars['rows']=3;}
    include(\e::config('SCRIPT_ROOT').'/ec/item/get_public_list.php');
    include(\e::config('SCRIPT_ROOT').'/ec/item/adjust_public_list.php');
    //prn($list_of_ec_items);
}
else {
    $list_of_ec_items=false;
}


$vyvid = process_template( $_template
        ,Array('cart'    => $_SESSION['ec_cart'],
        'total'   => $_SESSION['ec_cart']['total'],
        'n_cart_items' => $n_cart_items,
        'text'    => $txt,
        'site'    => $this_site_info,
        'form_action'=>site_public_URL.'/index.php',
        'ec_items'=>$list_of_ec_items,
        'hidden_order_form_elements'=>"<input type='hidden' name='action' value='ec/order/new'><input type='hidden' name='site_id' value='{$site_id}'><input type='hidden' name='lang' value='{$lang}'>",
        'url_cart_details' => $url_cart_details )
);
/*
if(isset($input_vars['element'])) {
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
*/

header('Content-Type:text/html; charset='.site_charset);
header('Access-Control-Allow-Origin: *');
  echo $vyvid;
// remove from history
nohistory($input_vars['action']);


?>
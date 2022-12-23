<?php
/*
 * View cart
*/

//error_reporting(E_ALL);
# -------------------- set interface language - begin --------------------------
$debug=false;
if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])   ) $input_vars['lang']=\e::config('default_language');
if(strlen($input_vars['lang'])==0) $input_vars['lang']=\e::config('default_language');
// $lang=$input_vars['lang'];
$lang=get_language('lang');
# -------------------- set interface language - end ----------------------------

# -------------------------- load messages - begin -----------------------------
global $txt;
$txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------



# ------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if(!$this_site_info) die($txt['Site_not_found']);
$this_site_info['title']=get_langstring($this_site_info['title'],$lang);
//prn($this_site_info);
//prn($input_vars);
# ------------------- get site info - end --------------------------------------

# --------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info,'template_index');
# --------------------------- get site template - end --------------------------









if(!isset($_SESSION['ec_cart'])) $_SESSION['ec_cart']=Array( 'total'=>0, 'items'=>Array());
if(!isset($_SESSION['ec_cart']['delivery_cost']))  $_SESSION['ec_cart']['delivery_cost']=0;
if(!isset($_SESSION['ec_cart']['delivery'])) $_SESSION['ec_cart']['delivery']=Array();

run('ec/cart/functions');
run('ec/delivery/functions');


// ----------------------- delete item from cart - begin -----------------------
if(isset($input_vars['cart_delete_key']))
    unset($_SESSION['ec_cart']['items'][$input_vars['cart_delete_key']]);
// ----------------------- delete item from cart - end -------------------------

// ----------------------- update cart - begin ---------------------------------
$message='';
// prn($input_vars['cart']);
if(isset($input_vars['cart'])) {
    foreach($input_vars['cart'] as $ke=>$va) {
        //prn($ke,$va,$_SESSION['ec_cart']['items'][$ke]);
        if($va<=0) unset($_SESSION['ec_cart']['items'][$ke]);
        else $_SESSION['ec_cart']['items'][$ke]['amount']=(int)$va;

        if(ec_cart_check_product_amount && $_SESSION['ec_cart']['items'][$ke]['amount']>$_SESSION['ec_cart']['items'][$ke]['info']['ec_item_amount']) {
            $message.="<div style='color:red;'>".text('EC_you_cannot_order_such_number_of items')."</div>";
            $_SESSION['ec_cart']['items'][$ke]['amount']=$_SESSION['ec_cart']['items'][$ke]['info']['ec_item_amount'];
        }

    }
}

if(isset($input_vars['ec_delivery'])) {
    //prn($input_vars);
    $_SESSION['ec_cart']['delivery']=Array(
            'ec_delivery'=>$input_vars['ec_delivery'],
            'ec_delivery_parameter'=>$input_vars['ec_delivery_parameter'] );
}
// ----------------------- update cart - end -----------------------------------

$_SESSION['ec_cart']['delivery_cost']=delivery_cost(isset($_SESSION['ec_cart']['total'])?$_SESSION['ec_cart']['total']:0,$this_site_info,$_SESSION['ec_cart']['delivery']);
$_SESSION['ec_cart']['total']=$_SESSION['ec_cart']['total']=ec_cart_get_total($_SESSION['ec_cart']);
//prn($_SESSION['ec_cart']['total']);

if(isset($input_vars['ec/order/new'])) {
    // redirect to new order page
    $url_return=site_root_URL.'/index.php?action=ec/order/new&site_id='.$site_id;
    header("Location: $url_return");
    exit();
}

// -------------------- return to shopping - begin -----------------------------
if(isset($input_vars['ec/return'])) {
    # -------------------- url "return to shopping" - begin --------------------
    $url_return=site_root_URL.'/index.php?action=ec/item/search_advanced&site_id='.$site_id;

    $cnt=count($_SESSION['history']);
    for($i=$cnt-1;$i>=0;$i--) {
        //prn($i,$_SESSION['history'][$i]);
        $pos=strpos($_SESSION['history'][$i],'action=ec%2Fcart%2F');
        if($pos!==false) continue;

        $pos=strpos($_SESSION['history'][$i],'action=ec%2Fitem%2Fview');
        if($pos!==false) continue;

        $url_return=$_SESSION['history'][$i];
        break;
    }
    # -------------------- url "return to shopping" - end ----------------------
    header("Location: $url_return");
    exit();
}
// -------------------- return to shopping - end -------------------------------


//prn($_SESSION['ec_cart']);











//------------------------ draw using SMARTY template - begin ------------------
run('site/page/page_view_functions');

# get site menu
$menu_groups = get_menu_items($this_site_info['id'],0,$lang);

# search for template
$ec_item_template=site_get_template($this_site_info,'template_ec_cart');

# -------------------- get list of page languages - begin ----------------------
$tmp=\e::db_getrows("SELECT DISTINCT ec_item_lang as lang
                     FROM <<tp>>ec_item  AS ec_item
                     WHERE ec_item.site_id={$site_id}
                       AND ec_item.ec_item_cense_level&".ec_item_show."");
$existing_languages=Array();
foreach($tmp as $tm) $existing_languages[$tm['lang']]=$tm['lang'];
// prn($existing_languages);


$lang_list=list_of_languages();
$cnt=count($lang_list);
for($i=0;$i<$cnt;$i++) {
    if(!isset($existing_languages[$lang_list[$i]['name']])) {
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url']=$lang_list[$i]['href'];
    $lang_list[$i]['lang']=$lang_list[$i]['name'];
}
$lang_list=array_values($lang_list);
//prn($lang_list);
# -------------------- get list of page languages - end ------------------------




//prn($_SESSION['ec_cart']);
//prn($ec_item_template);
$vyvid= process_template( $ec_item_template
        ,Array(
        'ec_cart'=>$_SESSION['ec_cart'],
        'total'=>$_SESSION['ec_cart']['total'],
        'message'=>$message,
        'text'=>$txt,
        'remove_item_url_prefix'=>"index.php?action=ec/cart/add&site_id=$site_id&ec_category_id=",
        'site'=>$this_site_info,
        'hidden_form_elements'=>preg_hidden_form_elements('/^cart$|delivery/'),
        'url_prefix_delete'=>"index.php?action=ec/cart/view&site_id=$site_id&lang=$lang&cart_delete_key=",
       //'hidden_order_form_elements'=>hidden_form_elements('^action|delivery').'<input type="hidden" name="action" value="ec/order/new">',
        'delivery_form'=>delivery_form($_SESSION['ec_cart']['total'],$this_site_info,$_SESSION['ec_cart']['delivery']),
        'sum_to_pay'=>($_SESSION['ec_cart']['total']+$_SESSION['ec_cart']['delivery_cost'])
        )
);
//prn($ec_item_template,$vyvid);

$file_content=process_template($this_site_info['template']
        ,Array(
        'page'=>Array('title'=>$txt['EC_items']
                ,'content'=>$vyvid
                ,'abstract'=> ''
                ,'site_id'=>$site_id
                ,'lang'=>$input_vars['lang']
        )
        ,'lang'=>$lang_list
        ,'site'=>$this_site_info
        ,'menu'=>$menu_groups
        ,'site_root_url'=>site_root_URL
        ,'text'=>$txt
));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name;
$main_template_name='';


?>
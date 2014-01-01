<?php
/*
  View order  details using ec_order_id
  input data:
     $ec_order_id - order identifier
     $lang        - interface language
*/


global $main_template_name;
$main_template_name='';
run('site/page/page_view_functions');
run('ec/order/functions');
run('ec/cart/functions');
run('ec/delivery/functions');
run('site_visitor/functions');
run('site/menu');
run('notifier/functions');


# --------------------- get order info - begin ---------------------------------
$ec_order_id=isset($input_vars['ec_order_id'])?( (int)$input_vars['ec_order_id'] ):0;
$this_ec_order_info=get_order_info($ec_order_id);
if(!$this_ec_order_info)  die('Order not found');
$this_ec_order_info['ec_order_status_text']=text('ec_order_status_'.$this_ec_order_info['ec_order_status']);
//prn($this_ec_order_info);
# --------------------- get order info - end -----------------------------------


# -------------------- set interface language - begin --------------------------
$debug=false;
if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])   ) $input_vars['lang']=default_language;
if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
// $lang=$input_vars['lang'];
$lang = get_language('lang');
# -------------------- set interface language - end -----------------------------



# -------------------------- load messages - begin -----------------------------
global $txt;
$txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------



# ------------------- get site info - begin ------------------------------------
$site_id = checkInt($this_ec_order_info['site_id']);
$this_site_info = get_site_info($site_id);
if(!$this_site_info) die($txt['Site_not_found']);
$this_site_info['title']=get_langstring($this_site_info['title'],$lang);
# ------------------- get site info - end --------------------------------------
//prn($_SESSION['ec_order_ids']);



# --------------------- check permissions - begin ------------------------------
# ec_order_id is saved in the session as order just created
$access_allowed=( isset($_SESSION['ec_order_ids']) && isset($_SESSION['ec_order_ids'][$this_ec_order_info['ec_order_id']]));

# site visitor is logged in and owns order
$access_allowed=( $access_allowed || isset($_SESSION['site_visitor_info']) && $_SESSION['site_visitor_info']['is_logged'] && $_SESSION['site_visitor_info']['site_visitor_id']==$this_ec_order_info['site_visitor_id']);

# user is site admin
$access_allowed=( $access_allowed || ( get_level($site_id)>0 ) );

# user is superuser
$access_allowed=( $access_allowed ||  is_admin());

if(!$access_allowed) {
    $input_vars['page_title']  =
            $input_vars['page_header'] =
            $input_vars['page_content']= text('Access_denied');
    return 0;
}
# --------------------- check permissions - end --------------------------------



# get site template
$custom_page_template = site_get_template($this_site_info,'template_index');


# -------------------- get list of page languages - begin ----------------------
$tmp=db_getrows("SELECT DISTINCT ec_item_lang as lang
                     FROM {$table_prefix}ec_item  AS ec_item
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
# -------------------- get list of page languages - end ------------------------









//prn($total);




# ---------------------- get payment form if needed - begin --------------------
if(!$this_ec_order_info['ec_order_paid']) {

    run('ec/pay/interfaces');
    run('ec/pay/liqpay/class_request');
    $paynowform=new payment_request(Array(
                    'result_url'=>'<![CDATA['.site_root_URL."/index.php?action=ec/order/postpay&ec_order_id={$ec_order_id}&lang={$lang}".']]>',
                    'server_url'=>'<![CDATA['.site_root_URL."/index.php?action=ec/order/pay".']]>',
                    'merchant_id'=>liqpay_merchant_id, // identifier of the merchant
                    'order_id'=>"$ec_order_id-at-".date('Y-m-d-H-i-s'),
                    'amount'=>$this_ec_order_info['total'],
                    'currency'=>$this_site_info['ec_currency'],
                    'description'=>preg_replace('/_+/',' ',transliterate($this_site_info['title']))." order {$ec_order_id}".( liqpay_test_mode?' TEST PAYMENT':''),
                    'default_phone'=>'',
                    'pay_way'=>'card',
                    'merchant_sign'=>liqpay_merchant_sign
    ));
    if(liqpay_test_mode) {
        $paynowform->merchant_id=sha1($paynowform->merchant_id);
        $paynowform->merchant_sign=sha1($paynowform->merchant_sign);
        $paynowform->liqpay_script=site_root_URL."/scripts/ec/pay/liqpay/sample_reply.php?order_id={$ec_order_id}&amount={$this_site_info['ec_currency']}&merchant_sign=".liqpay_merchant_sign;
    }

    //$paynowbutton=$paynowform->get_pay_now_form(text('Pay_now'));
    //prn('Not Paid!',checkStr($paynowbutton));
}
else {
    //prn('Paid!');
    $paynowbutton='';
    $paynowform=false;
}
# ---------------------- get payment form if needed - end ----------------------


# ---------------------- update order history - begin --------------------------
//prn($_SESSION);
if(isset($input_vars['ec_order_history_details'])) {
    $ec_order_history_details=trim($input_vars['ec_order_history_details']);
    if(strlen($ec_order_history_details)) {

        $event_title=($_SESSION['user_info']['is_logged']?(text('ec_order_admin_comment').' '.$_SESSION['user_info']['full_name']):(text('ec_order_customer_comment').' '.$this_ec_order_info['shipping']['ec_user_name'].'('.$_SESSION['site_visitor_info']['site_visitor_email'].')'));

        # ------------------------ update order history - begin ----------------
        update_ec_order_history(
                $event_title,
                $ec_order_history_details,
                ($_SESSION['user_info']['is_logged']?'ec_order_admin_comment':'ec_order_customer_comment'),
                $ec_order_id,
                (int)$_SESSION['site_visitor_info']['site_visitor_id'],
                $site_id,
                (int)$_SESSION['user_info']['id'] );
        # ------------------------ update order history - end ------------------

        # ----------------------- send other notification - begin --------------
        notify('order_updated',
                $this_site_info,
                Array(
                'order'=>$this_ec_order_info,
                'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
                'user'=>$this_ec_order_info['shipping'],
                'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                'ec_order_id'=>$ec_order_id,
                'new_comment'=>$ec_order_history_details,
                'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
                )
        );
        # ----------------------- send other notification - end ----------------

        # ---------- email notification to customer - begin --------------------
        # search for template
        $ec_item_template=site_get_template($this_site_info,'template_email_to_customer_order_updated');

        $email_body=process_template( $ec_item_template
                ,Array(
                'site'=>$this_site_info,
                'order'=>$this_ec_order_info,
                'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
                'user'=>$this_ec_order_info['shipping'],
                'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                'ec_order_id'=>$ec_order_id,
                'new_comment'=>$ec_order_history_details,
                'url_order_details'=>site_root_URL.'/index.php?action=ec/order/view&ec_order_id='.$this_ec_order_info['ec_order_id']
        ));
        $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id;
        if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
        my_mail($this_ec_order_info['shipping']['site_visitor_email'], $email_subject, $email_body);
        # ---------- email notification to customer - end ----------------------

        # ---------- email notification to seller - begin ----------------------
        # search for template
        $ec_item_template=site_get_template($this_site_info,'template_email_to_seller_order_updated');

        $email_body=process_template( $ec_item_template
                ,Array(
                'site'=>$this_site_info,
                'order'=>$this_ec_order_info,
                'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
                'user'=>$this_ec_order_info['shipping'],
                'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                'ec_order_id'=>$ec_order_id,
                'new_comment'=>$ec_order_history_details,
                'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
        ));
        $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id;
        if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
        foreach($this_site_info['managers'] as $mng) {
            my_mail($mng['email'], $email_subject, $email_body);
        }
        # ---------- email notification to seller - end ------------------------


        header("Location: index.php?action=ec/order/view&ec_order_id={$ec_order_id}");
        exit();
    }
}
# ---------------------- update order history - end ----------------------------

# get order history
$order_history=new order_history($ec_order_id);
//prn($order_history->list);







//------------------------ draw using SMARTY template - begin ------------------

# get site menu
$menu_groups = get_menu_items($this_site_info['id'],0,$lang);

# -------------------- search for template - begin ---------------------------
$ec_item_template = site_get_template($this_site_info,'template_ec_order_view');
# -------------------- search for template - end -----------------------------


$message='';
if(isset($input_vars['message'])) $message.=text($input_vars['message']);

//prn($this_ec_order_info);

if(isset($_SESSION['site_visitor_info']) && $_SESSION['site_visitor_info']['is_logged']) {
    $vyvid=" {$_SESSION['site_visitor_info']['site_visitor_login']}! ".site_visitor_draw_menu($_SESSION['site_visitor_info'],$this_site_info).'<br/><br/>';
}else $vyvid='';

$vyvid.=
        process_template( $ec_item_template
        ,Array(
        //'ec_cart'=>$this_ec_order_info['ec_cart'],
        'order'=>$this_ec_order_info,
        'total'=>$this_ec_order_info['total'],
        'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'], $this_site_info, $this_ec_order_info['ec_cart']['delivery']),
        'message'=>$message,
        'text'=>$txt,
        'edit_cart_url'=>"index.php?action=ec/cart/view&site_id=$site_id&lang=$lang",
        'remove_item_url_prefix'=>"index.php?action=ec/cart/add&site_id=$site_id&ec_category_id=",
        'site'=>$this_site_info,
        'user'=>$this_ec_order_info['shipping'],
        'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
        'order_history'=>$order_history,
        'paynowform'=>$paynowform )
);

$file_content=process_template($this_site_info['template']
        ,Array(
        'page'=>Array('title'=>$txt['EC_order'].' '.$this_ec_order_info['ec_order_id']
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





?>
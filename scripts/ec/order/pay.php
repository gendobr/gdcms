<?php
/**
 * Receive LiqPay notification
 */
global $main_template_name;
$main_template_name='';

// remove from history
nohistory($input_vars['action']);


run('ec/pay/interfaces');
run('ec/pay/liqpay/class_reply');
run('ec/order/functions');
run('ec/delivery/functions');
run('ec/cart/functions');
run('site/menu');
run('notifier/functions');
run('site/page/page_view_functions');

$payment_reply = new payment_reply($input_vars);
if($payment_reply->is_valid()) {
    $ec_order_id=1*$payment_reply->get_order_id();

    # --------------------- get order info - begin ------------------------------
    $this_ec_order_info=get_order_info($ec_order_id);
    if(!$this_ec_order_info)  die('Order not found');
    $this_ec_order_info['ec_order_status_text']=text('ec_order_status_'.$this_ec_order_info['ec_order_status']);
    //prn($this_ec_order_info);
    # --------------------- get order info - end --------------------------------

    # -------------------- set interface language - begin --------------------------
    if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
    if(!isset($input_vars['lang'])   ) $input_vars['lang']=\e::config('default_language');
    if(strlen($input_vars['lang'])==0) $input_vars['lang']=\e::config('default_language');
    //$lang=$input_vars['lang'];
    $lang = get_language('lang');
    # -------------------- set interface language - end -----------------------------

    # ------------------- get site info - begin ---------------------------------
    $site_id = checkInt($this_ec_order_info['site_id']);
    $this_site_info = get_site_info($site_id);
    if(!$this_site_info) die($txt['Site_not_found']);
    $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
    # ------------------- get site info - end -----------------------------------

    // update order status
    switch($payment_reply->get_status()) {
        case 'success':
        # set order as paid
            $query="UPDATE <<tp>>ec_order
                    SET ec_order_paid=1 , ec_order_paid_amount=".$payment_reply->get_amount()."
                    WHERE ec_order_id=$ec_order_id";
            //prn($query);
            \e::db_execute($query);

            # update order hash
            ec_order_sha($ec_order_id);

            # update order history
            $order_history_query="SELECT
                        '".\e::db_escape(text('Ec_order_is_paid_successfully'))."' as ec_order_history_title,
                        '".\e::db_escape($payment_reply->get_human_readable_info())."' as ec_order_history_details,
                           NOW() as ec_order_history_date,
                          'order_paid_successfully' as ec_order_history_action,
                           ec_order.ec_order_id,
                           ec_user.site_visitor_id,
                           ec_order.site_id,
                           0 as user_id
                    FROM <<tp>>ec_order as ec_order
                         INNER JOIN <<tp>>ec_user as ec_user
                         ON ec_order.ec_user_id=ec_user.ec_user_id
                    WHERE ec_order_id=$ec_order_id
                    ";

            # ----------------------- send other notification - begin ----------
            notify('order_updated',
                    $this_site_info,
                    Array(
                    'order'=>$this_ec_order_info,
                    'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
                    'user'=>$this_ec_order_info['shipping'],
                    'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                    'ec_order_id'=>$ec_order_id,
                    'new_comment'=>text('Ec_order_is_paid_successfully').'  '.$payment_reply->get_amount().$payment_reply->get_currency().' <br>'.strip_tags($payment_reply->get_human_readable_info()),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
                    )
            );
            # ----------------------- send other notification - end ------------

            # ---------- email notification to customer - begin ----------------
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
                    'new_comment'=>text('Ec_order_is_paid_successfully').'  '.$payment_reply->get_amount().$payment_reply->get_currency().' <br>'.$payment_reply->get_human_readable_info(),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
            ));
            $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_is_paid_successfully').'...';
            if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
            my_mail($this_ec_order_info['shipping']['site_visitor_email'], $email_subject, $email_body);
            # ---------- email notification to customer - end ------------------

            # ---------- email notification to seller - begin ------------------
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
                    'new_comment'=>text('Ec_order_is_paid_successfully').'  '.$payment_reply->get_amount().$payment_reply->get_currency().' <br>'.$payment_reply->get_human_readable_info(),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
            ));
            $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_is_paid_successfully').'...';
            if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
            foreach($this_site_info['managers'] as $mng) {
                my_mail($mng['email'], $email_subject, $email_body);
            }
            # ---------- email notification to seller - end --------------------
            break;

        case 'failure':
        # update order history
            $order_history_query="SELECT
                        '".\e::db_escape(text('Ec_order_payment_failure'))."' as ec_order_history_title,
                        '".\e::db_escape($payment_reply->get_human_readable_info())."' as ec_order_history_details,
                           NOW() as ec_order_history_date,
                          'order_payment_failure' as ec_order_history_action,
                           ec_order.ec_order_id,
                           ec_user.site_visitor_id,
                           ec_order.site_id,
                           0 as user_id
                    FROM <<tp>>ec_order as ec_order
                         INNER JOIN <<tp>>ec_user as ec_user
                         ON ec_order.ec_user_id=ec_user.ec_user_id
                    WHERE ec_order_id=$ec_order_id
                    ";
            # ----------------------- send other notification - begin ----------
            notify('order_updated',
                    $this_site_info,
                    Array(
                    'order'=>$this_ec_order_info,
                    'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
                    'user'=>$this_ec_order_info['shipping'],
                    'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                    'ec_order_id'=>$ec_order_id,
                    'new_comment'=>text('Ec_order_payment_failure').'<br>'.strip_tags($payment_reply->get_human_readable_info()),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
                    )
            );
            # ----------------------- send other notification - end ------------

            # ---------- email notification to customer - begin ----------------
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
                    'new_comment'=>text('Ec_order_payment_failure').'<br>'.$payment_reply->get_human_readable_info(),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
            ));
            $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_payment_failure');
            if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
            my_mail($this_ec_order_info['shipping']['site_visitor_email'], $email_subject, $email_body);
            # ---------- email notification to customer - end ------------------

            # ---------- email notification to seller - begin ------------------
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
                    'new_comment'=>text('Ec_order_payment_failure').'<br>'.$payment_reply->get_human_readable_info(),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
            ));
            $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_payment_failure');
            if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
            foreach($this_site_info['managers'] as $mng) {
                my_mail($mng['email'], $email_subject, $email_body);
            }
            # ---------- email notification to seller - end --------------------

            break;

        case 'waiting':
        # update order history
            $order_history_query="SELECT
                        '".\e::db_escape(text('Ec_order_payment_waiting'))."' as ec_order_history_title,
                        '".\e::db_escape($payment_reply->get_human_readable_info())."' as ec_order_history_details,
                           NOW() as ec_order_history_date,
                          'order_payment_failure' as ec_order_history_action,
                           ec_order.ec_order_id,
                           ec_user.site_visitor_id,
                           ec_order.site_id,
                           0 as user_id
                    FROM <<tp>>ec_order as ec_order
                         INNER JOIN <<tp>>ec_user as ec_user
                         ON ec_order.ec_user_id=ec_user.ec_user_id
                    WHERE ec_order_id=$ec_order_id
                    ";
            # ----------------------- send other notification - begin ------------
            notify('order_updated',
                    $this_site_info,
                    Array(
                    'order'=>$this_ec_order_info,
                    'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
                    'user'=>$this_ec_order_info['shipping'],
                    'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                    'ec_order_id'=>$ec_order_id,
                    'new_comment'=>text('Ec_order_payment_waiting').'<br>'.strip_tags($payment_reply->get_human_readable_info()),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
                    )
            );
            # ----------------------- send other notification - end ------------

            # ---------- email notification to customer - begin ----------------
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
                    'new_comment'=>text('Ec_order_payment_waiting').'<br>'.$payment_reply->get_human_readable_info(),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
            ));
            $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_payment_waiting');
            if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
            my_mail($this_ec_order_info['shipping']['site_visitor_email'], $email_subject, $email_body);
            # ---------- email notification to customer - end ------------------

            # ---------- email notification to seller - begin ------------------
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
                    'new_comment'=>text('Ec_order_payment_waiting').'<br>'.$payment_reply->get_human_readable_info(),
                    'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
            ));
            $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_payment_waiting');
            if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
            foreach($this_site_info['managers'] as $mng) {
                my_mail($mng['email'], $email_subject, $email_body);
            }
            # ---------- email notification to seller - end --------------------

            break;
    }


}
else {
    $ec_order_id=1*$payment_reply->get_order_id();
    # --------------------- get order info - begin ------------------------------
    $this_ec_order_info=get_order_info($ec_order_id);
    if(!isset($this_ec_order_info['ec_order_id']))  die('Order not found');
    $this_ec_order_info['ec_order_status_text']=text('ec_order_status_'.$this_ec_order_info['ec_order_status']);
    //prn($this_ec_order_info);
    # --------------------- get order info - end --------------------------------

    # update order history
    $order_history_query="SELECT
                '".\e::db_escape(text('Ec_order_payment_invalid'))."' as ec_order_history_title,
                '".\e::db_escape($payment_reply->get_human_readable_info())."' as ec_order_history_details,
                   NOW() as ec_order_history_date,
                  'order_payment_invalid' as ec_order_history_action,
                   ec_order.ec_order_id,
                   ec_user.site_visitor_id,
                   ec_order.site_id,
                   0 as user_id
            FROM <<tp>>ec_order as ec_order
                 INNER JOIN <<tp>>ec_user as ec_user
                 ON ec_order.ec_user_id=ec_user.ec_user_id
            WHERE ec_order_id=$ec_order_id
            ";
    # ----------------------- send other notification - begin ------------------
    notify('order_updated',
            $this_site_info,
            Array(
            'order'=>$this_ec_order_info,
            'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
            'user'=>$this_ec_order_info['shipping'],
            'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
            'ec_order_id'=>$ec_order_id,
            'new_comment'=>text('Ec_order_payment_invalid').'<br>'.strip_tags($payment_reply->get_human_readable_info()),
            'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
            )
    );
    # ----------------------- send other notification - end --------------------
    # ---------- email notification to customer - begin ------------------------
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
            'new_comment'=>text('Ec_order_payment_invalid').'<br>'.$payment_reply->get_human_readable_info(),
            'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
    ));
    $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_payment_invalid');
    if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
    my_mail($this_ec_order_info['shipping']['site_visitor_email'], $email_subject, $email_body);
    # ---------- email notification to customer - end --------------------------

    # ---------- email notification to seller - begin --------------------------
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
            'new_comment'=>text('Ec_order_payment_invalid').'<br>'.$payment_reply->get_human_readable_info(),
            'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
    ));
    $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id.' - '.text('Ec_order_payment_invalid');
    if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
    foreach($this_site_info['managers'] as $mng) {
        my_mail($mng['email'], $email_subject, $email_body);
    }
    # ---------- email notification to seller - end ----------------------------
}

ml('ec/order/pay#success',$order_history_query);
$order_history_query="INSERT INTO <<tp>>ec_order_history(
                    ec_order_history_title,
                    ec_order_history_details,
                    ec_order_history_date,
                    ec_order_history_action,
                    ec_order_id,
                    site_visitor_id,
                    site_id,
                    user_id
                ) ".$order_history_query ;
//prn($order_history_query);
\e::db_execute($order_history_query);
?><html>
    <head>
        <meta http-equiv="Refresh" content="30;URL=index.php?action=ec/order/postpay&<?php echo query_string('^action$');?>">
    </head>
    <body>
        Payment is sucessfully processed
    </body>
</html>

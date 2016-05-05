<?php
/* 
 * Order admin details
 * argument is $ec_order_id - integer, mandatory
 *
*/
$debug=false;
run('site/menu');
run('ec/order/functions');
run('ec/item/functions');
run('ec/cart/functions');
run('ec/delivery/functions');
run('notifier/functions');
run('site/page/page_view_functions');

# --------------------- get order info - begin ---------------------------------
$ec_order_id=isset($input_vars['ec_order_id'])?( (int)$input_vars['ec_order_id'] ):0;
$this_ec_order_info=get_order_info($ec_order_id);
//prn($this_ec_order_info);
if(!$this_ec_order_info)  die('Order not found');
$this_ec_order_info['ec_order_status_text']=text('ec_order_status_'.$this_ec_order_info['ec_order_status']);
//prn($this_ec_order_info);
if(!$this_ec_order_info) {
    $input_vars['page_title']   =
            $input_vars['page_header']  =
            $input_vars['page_content'] = text('Ec_order_not_found');
    return 0;
}
# --------------------- get order info - end -----------------------------------


//------------------- site info - begin ----------------------------------------
$site_id = $this_ec_order_info['site_id'];
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if(checkInt($this_site_info['id'])<=0) {
    $input_vars['page_title']   = text('Site_not_found');
    $input_vars['page_header']  = text('Site_not_found');
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
$user_cense_level=get_level($site_id);
if($user_cense_level==0) {
    $input_vars['page_title']  = text('Access_denied');
    $input_vars['page_header'] = text('Access_denied');
    $input_vars['page_content']= text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------


$order_history=new order_history($ec_order_id);

// ------------------ update order status - begin ------------------------------
if( isset($input_vars['ec_order_history_status']) && $input_vars['ec_order_history_status']!='' ) {
    $tmp=explode(',',ec_order_status);
    $cnt=count($tmp);
    $ec_order_status_options=Array(''=>'');
    for($i=0;$i<$cnt;$i++) $ec_order_status_options[$tmp[$i]]=text('ec_order_status_'.$tmp[$i]);
    if($input_vars['ec_order_history_status']=='Ec_order_is_paid_successfully') {
        $history_message=text('Changed_ec_order_status_to').' <b>'.text('Ec_order_is_paid_successfully').'</b><br/>';
        $query="UPDATE <<tp>>ec_order SET ec_order_paid=1 where ec_order_id=$ec_order_id";
        \e::db_execute($query);
    }
    elseif($input_vars['ec_order_history_status']=='Ec_order_is_not_paid') {
        $history_message=text('Changed_ec_order_status_to').' <b>'.text('Ec_order_is_not_paid').'</b><br/>';
        $query="UPDATE <<tp>>ec_order SET ec_order_paid=0 where ec_order_id=$ec_order_id";
        \e::db_execute($query);
    }
    elseif(isset($ec_order_status_options[$input_vars['ec_order_history_status']])) {
        $history_message=text('Changed_ec_order_status_to').' <b>'.$ec_order_status_options[$input_vars['ec_order_history_status']].'</b><br/>';
        $query="UPDATE <<tp>>ec_order SET ec_order_status='".\e::db_escape($input_vars['ec_order_history_status'])."' where ec_order_id=$ec_order_id";
        \e::db_execute($query);
    }
    //prn($query);
    if(mysql_affected_rows()>0) {
        # update order hash
        ec_order_sha($ec_order_id);

        # reload order info
        $this_ec_order_info=get_order_info($ec_order_id);
        $this_ec_order_info['ec_order_status_text']=$ec_order_status_options[$this_ec_order_info['ec_order_status']];



        $order_comment=(isset($input_vars[$order_history->form['textarea_name']])?$input_vars[$order_history->form['textarea_name']]:'');
        # ---------------------- update order history - begin ------------------
        update_ec_order_history(
                text('Ec_order_status_changed'),
                $history_message.'<br/><br/>'.$order_comment,
                'order_status_changed',
                $ec_order_id,
                0,
                $site_id,
                $_SESSION['user_info']['id']);
        # ---------------------- update order history - end --------------------

        # ----------------------- send other notification - begin --------------
        # prn($this_ec_order_info);
        notify('order_updated',
                $this_site_info,
                Array(
                'order'=>$this_ec_order_info,
                'user'=>$this_ec_order_info['shipping'],
                'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                'ec_order_id'=>$ec_order_id,
                'new_comment'=>$history_message.'<br/><br/>'.$order_comment,
                'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
                )
        );
        //die();
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
                'new_comment'=>$history_message.'<br/><br/>'.$order_comment,
                'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
        ));
        $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id;
        if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
        my_mail($this_ec_order_info['shipping']['site_visitor_email'], $email_subject, $email_body);
        # ---------- email notification to customer - end ----------------------
    }
}
elseif(
isset($input_vars[$order_history->form['textarea_name']])
        && strlen(trim($input_vars[$order_history->form['textarea_name']]))>0
) {
    $order_comment=(isset($input_vars[$order_history->form['textarea_name']])?$input_vars[$order_history->form['textarea_name']]:'');
    # ---------------------- update order history - begin ----------------------
    update_ec_order_history(
            text('ec_order_admin_comment'),
            trim($input_vars[$order_history->form['textarea_name']]),
            'ec_order_admin_comment',
            $ec_order_id,
            0,
            $site_id,
            $_SESSION['user_info']['id']);
    # ---------------------- update order history - end ------------------------
    # ----------------------- send other notification - begin ------------------
    notify('order_updated',
            $this_site_info,
            Array(
            'order'=>$this_ec_order_info,
            'delivery_info'=>delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery']),
            'user'=>$this_ec_order_info['shipping'],
            'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
            'ec_order_id'=>$ec_order_id,
            'new_comment'=>$order_comment,
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
            'new_comment'=>$order_comment,
            'url_order_details'=>site_root_URL.'/index.php?action=ec/order/admindetails&ec_order_id='.$this_ec_order_info['ec_order_id']
    ));
    $email_subject=get_langstring($this_site_info['title'],$lang).':'.text('EC_order').' '.$ec_order_id;
    if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
    my_mail($this_ec_order_info['shipping']['site_visitor_email'], $email_subject, $email_body);
    # ---------- email notification to customer - end --------------------------
}
// ------------------ update order status - end --------------------------------

// ------------------
$tor='';

// show warning if validation is not passed
if(!ec_order_validate($this_ec_order_info))
    $tor.='<div style="background-color:red; color:white; font-weight:bold;padding:4px;">'.text('ec_order_invalid').'</div>';

// ----------------------- show order status - begin ---------------------------
$tor.="<h3 style='text-align:left;margin-bottom:0px;'>".text("ec_order_status")."</h3>
          <div>{$this_ec_order_info['ec_order_status_text']}</div>";
if(  $this_ec_order_info['ec_order_paid'] ) {
    $tor.='<div>'.text('Ec_order_is_paid_successfully')
            ." {$this_ec_order_info['ec_order_paid_amount']} "
            .text($this_site_info['ec_currency'])
            .' / '
            .text('EC_payment_total')." {$this_ec_order_info['total']} "
            .text($this_site_info['ec_currency'])."</div>";
}
else {
    $tor.='<div>'.text('Ec_order_is_not_paid').' / '
            .text('EC_payment_total')." {$this_ec_order_info['total']} "
            .text($this_site_info['ec_currency']).'</div>';
}
// ----------------------- show order status - end -----------------------------

// ----------------------- user details - begin --------------------------------
$user=$this_ec_order_info['shipping'];

$tor.="<h3 style='text-align:left;margin-bottom:0px;'>".text("EC_order_creator")."</h3>
   <table class=\"nobd\" style=\"border:none;width:80%;\">";

if($user['ec_user_name'])
    $tor.="<tr><td>".text('ec_user_name').":</td><td>{$user['ec_user_name']}</td></tr>";

$tor.="<tr><td>".text('site_visitor_email').":</td><td>{$user['site_visitor_email']}</td></tr>";

if($user['ec_user_telephone'])
    $tor.="<tr><td>".text('ec_user_telephone').":</td><td>{$user['ec_user_telephone']}</td></tr>";

if($user['ec_user_icq'])
    $tor.="<tr><td>".text('ec_user_icq').":</td><td>{$user['ec_user_icq']}</td></tr>";

if($user['ec_user_delivery_street_address'])
    $tor.="<tr><td>".text('ec_user_delivery_street_address').":</td><td>{$user['ec_user_delivery_street_address']}</td></tr>";

if($user['ec_user_delivery_city'])
    $tor.="<tr><td>".text('ec_user_delivery_city').":</td><td>{$user['ec_user_delivery_city']}</td></tr>";

if($user['ec_user_delivery_suburb'])
    $tor.="<tr><td>".text('ec_user_delivery_suburb').":</td><td>{$user['ec_user_delivery_suburb']}</td></tr>";

if($user['ec_user_delivery_region'])
    $tor.="<tr><td>".text('ec_user_delivery_region').":</td><td>{$user['ec_user_delivery_region']}</td></tr>";

// �������� �������������� ����� �� ����� ������
$tor.="<tr><td>&nbsp;</td><td>&nbsp;</td></tr>";
foreach($this_ec_order_info['custom'] as $key=>$val){
    if(!isset($this_ec_order_info['shipping'][$key])){
        $tor.="<tr><td>{$key}:</td><td>{$val}</td></tr>";
    }
}
//prn($this_ec_order_info);
//$this_ec_order_info['custom']shipping

$tor.="</table>";

// ----------------------- user details - end ----------------------------------




// ----------------------- shopping cart - begin -------------------------------
$tor.="

    <style type=\"text/css\">
        .nobd{border:none;}
        .nobd td{border:none;}
        .nobd input{width:100%;}
        .rw{background-color:yellow;}
        .em{}
    </style>
    <script type=\"text/javascript\">
        function mover(trid)
        {
            ta=document.getElementById(trid);
            kids = ta.childNodes;
            for (var i = 0; i < kids.length; i++)
            {
                kids[i].className=\"rw\";
            }
        }
        function mout(trid)
        {
            ta=document.getElementById(trid);
            kids = ta.childNodes;
            for (var i = 0; i < kids.length; i++)
            {
                kids[i].className=\"em\";
            }
        }
    </script>


   <table border=\"1px\" cellspacing=\"0\" cellpadding=\"3pt\" style=\"border:none;width:80%;\">
   <tr><td style=\"border:none;\" colspan=7><br/><h3 style='text-align:left;margin-bottom:0px;'>".text("EC_items")."</h3></td></tr>
   <tr>
        <td style=\"border:none;\"><b>".text("Ec_item_UID")."</b></td>
        <td style=\"border:none;\"><b>".text("ec_item_title")."</b></td>
        <td style=\"border:none;\"><b>".text("ec_item_price")."</b></td>
        <td style=\"border:none;\">&nbsp;</td>
        <td style=\"border:none;\"><b>".text("Number_of_items")."</b></td>
        <td colspan=\"2\" style=\"border:none;\">&nbsp;</td>
   </tr>
   <tr><td colspan=\"6\"></td></tr>
   ";

$url_prefix=site_root_URL.'/index.php?action=ec/item/view&ec_item_id=';
foreach($this_ec_order_info['ec_cart']['items'] as $ke=>$it) {
    $tor.="
        <tr id=\"tr0_{$ke}\">
            <td class=\"em\" valign=top onmouseover=\"mover('tr0_{$ke}')\" onmouseout=\"mout('tr0_{$ke}')\">{$it['info']['ec_item_uid']}</td>
            <td class=\"em\" valign=top onmouseover=\"mover('tr0_{$ke}')\" onmouseout=\"mout('tr0_{$ke}')\">
            <a href='{$url_prefix}{$it['info']['ec_item_id']}' target='_blank'>{$it['info']['ec_item_title']}</a>
            ";
    if(isset($it['info']['ec_item_variant'][0])) {
        $tor.="<div style=\"color:gray;font-size:80%;\">";
        foreach($it['info']['ec_item_variant'] as $k_variant=>$variant) {
            $tor.=strip_tags($variant['ec_item_variant_description'])."<br/>\n";
        }
        $tor.="
            </div>
          ";
    }

    $tor.="
          </td>
          <td class=\"em\" valign=top onmouseover=\"mover('tr0_{$ke}')\" onmouseout=\"mout('tr0_{$ke}')\" style=\"border-right:none;\">{$it['info']['ec_item_price_corrected']} {$it['info']['ec_item_currency_title']}</td>
          <td class=\"em\" valign=top onmouseover=\"mover('tr0_{$ke}')\" onmouseout=\"mout('tr0_{$ke}')\" style=\"border-right:none;border-left:none;\">&times;</td>
          <td class=\"em\" valign=top onmouseover=\"mover('tr0_{$ke}')\" onmouseout=\"mout('tr0_{$ke}')\" style=\"border-right:none;border-left:none;\">{$it['amount']}</td>
          <td class=\"em\" valign=top onmouseover=\"mover('tr0_{$ke}')\" onmouseout=\"mout('tr0_{$ke}')\" style=\"border-right:none;border-left:none;\">=</td>
          <td class=\"em\" valign=top onmouseover=\"mover('tr0_{$ke}')\" onmouseout=\"mout('tr0_{$ke}')\" style=\"border-left:none;\" class=\"b-l-n\">
            "
            .($it['amount']*$it['info']['ec_item_price_corrected'])
            ."
            {$it['info']['ec_item_currency_title']}
          </td>
         </tr>
         <tr><td colspan=\"7\"></td></tr>
            ";
}
$tor.="
    <tr>
        <td colspan=\"6\" align=\"left\" valign=\"top\" style=\"border:none;background-color:#e0e0e0;\">".text('EC_cart_total').":</td>
        <td style=\"border:none;background-color:#eaeaea;\">

        {$this_ec_order_info['ec_cart']['total']} ".text($this_site_info['ec_currency'])."
        </td>
    </tr>



   <tr><td style=\"border:none;\" colspan=7>&nbsp;<h3 style='text-align:left;margin:0px;'>".text('EC_delivery')."</h3></td></tr>
   <tr><td style=\"border:none;\" colspan=7>"
        .delivery_info($this_ec_order_info['ec_cart']['total'],$this_site_info,$this_ec_order_info['ec_cart']['delivery'])
        ."</td></tr>
    <tr>
        <td colspan=\"6\" align=\"left\" valign=\"top\" style=\"border:none;background-color:#e0e0e0;\">"
        .text('EC_cart_total').":
        </td>
        <td style=\"border:none;background-color:#eaeaea;\">
        {$this_ec_order_info['ec_cart']['delivery_cost']} ".text($this_site_info['ec_currency'])
        ."</td>
    </tr>
    <tr><td colspan=\"7\" style=\"border:none;\">&nbsp;</td></tr>
    <tr>
        <td colspan=\"6\" align=\"left\" valign=\"top\" style=\"border:none;background-color:#e0e0e0;\"><h3>"
        .text('EC_payment_total').":</h3>
        </td>
        <td style=\"border:none;background-color:#eaeaea;\"><h3>
        {$this_ec_order_info['total']} ".text($this_site_info['ec_currency'])
        ."</h3></td>
    </tr>
   </table>";
// ----------------------- shopping cart - end ---------------------------------

/*
   ";
   // foreach($this_ec_order_info['total'] as $tt) $tor.="{$tt['sum']} {$tt['ec_item_currency_title']}<br/>\n";
   $tor.="
//'total'=>$this_ec_order_info['total'],
*/
// ----------------------- order history - begin -------------------------------



$tor.="<h3 style='text-align:left;'>".text("EC_Order_History")."</h3>
          <style type=\"text/css\">
            div.history_item{padding:5pt;border:1px dotted yellow;margin-bottom:10px;}
            div.history_item h3{margin:0px;text-align:left;}
            div.history_item div.details{color:gray;}
          </style>
          ";
foreach($order_history->list as $hs) {
    $tor.="
             <div class=\"history_item\">
                <h3>{$hs['ec_order_history_title']}</h3>
                <a href=\"mailto:{$hs['site_visitor_email']}{$hs['email']}\">{$hs['site_visitor_email']}{$hs['email']}</a> {$hs['ec_order_history_date']}
                <div class=\"details\">
            {$hs['ec_order_history_details']}
                </div>
            </div>
            ";
}

$tmp=explode(',',ec_order_status);
$cnt=count($tmp);
$ec_order_status_options=Array(''=>'');
for($i=0;$i<$cnt;$i++)
    $ec_order_status_options[$tmp[$i]]=text('ec_order_status_'.$tmp[$i]);

$ec_order_status_options['Ec_order_is_paid_successfully'] = text('Ec_order_is_paid_successfully');
$ec_order_status_options['Ec_order_is_not_paid'] = text('Ec_order_is_not_paid');

$tor.="
<div class=\"history_item\">
<form action=\"{$order_history->form['action']}\" method=\"post\">
        {$order_history->form['hidden_elements']}
<h3 style='display:inline;'>".text("Change_ec_order_status_to").":</h3><select style='width:100pt;' name=ec_order_history_status>
".draw_options('',$ec_order_status_options)."
</select><input type=\"submit\" value=\"".text("Send")."\"><br/>
<br><br>
<h3>".text("Ec_order_new_comment")."</h3>
<textarea name=\"{$order_history->form['textarea_name']}\" style=\"width:100%;height:150pt;\"></textarea><br/>
<input type=\"submit\" value=\"".text("Send")."\">
</form>
</div>";


// ----------------------- order history - end ---------------------------------











// =============================================================================
$input_vars['page_title']  =
        $input_vars['page_header'] =  $this_ec_order_info['ec_order_id'].' - '. text('EC_order_details').' - '.$this_site_info['title'];
$input_vars['page_content'] = $tor;

# --------------------------- context menu -- begin ----------------------------

$sti=text('EC_order_details').' #'.$this_ec_order_info['ec_order_id'];
$_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
$input_vars['page_menu']['page']=Array('title'=>$_menu,'items'=>Array());
$input_vars['page_menu']['page']['items'] = menu_ec_order($this_ec_order_info);


$sti=$text['Site'].' "'. $this_site_info['title'].'"';
$Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
$input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);


# --------------------------- context menu -- end ------------------------------

?>

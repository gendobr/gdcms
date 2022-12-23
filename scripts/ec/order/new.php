<?php
/* 
 * create new order
*/

global $main_template_name;
$main_template_name='';
run('site/page/page_view_functions');
run('ec/order/functions');
run('site/menu');
run('notifier/functions');
run('ec/cart/functions');
run('ec/item/functions');
run('ec/delivery/functions');

// remove from history
nohistory($input_vars['action']);

//prn($input_vars);die('!!!');

# -------------------- set interface language - begin --------------------------
$debug=false;
if(isset($input_vars['interface_lang']) && $input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
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
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if(!$this_site_info) die($txt['Site_not_found']);
$this_site_info['title']=get_langstring($this_site_info['title'],$lang);
//prn($this_site_info);
//prn($input_vars);
# ------------------- get site info - end --------------------------------------








# --------------------------- handle one-click order - begin -------------------
if(isset($input_vars['ec_item_id'])) {
    # -------------------- get ec item info - begin ----------------------------
    $ec_item_id   = (int)$input_vars['ec_item_id'];
    $ec_item_lang = isset($input_vars['ec_item_lang'])?( $input_vars['ec_item_lang'] ):\e::config('default_language');
    $this_ec_item_info=get_ec_item_info($ec_item_id,$ec_item_lang);
    if(!$this_ec_item_info) die('Item not found');
    //prn($this_ec_item_info);
    # -------------------- get ec item info - end ------------------------------
    ec_cart_additem($this_ec_item_info,$input_vars);
}
# --------------------------- handle one-click order - end ---------------------




# get list of page languages
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


# error/success message
$order_form_msg='<!-- -->';





// -------------------- update delivery options - begin ------------------------
//prn($_SESSION['ec_cart']);
if(!isset($_SESSION['ec_cart']['delivery_cost']))  $_SESSION['ec_cart']['delivery_cost']=0;
if(!isset($_SESSION['ec_cart']['delivery'])) $_SESSION['ec_cart']['delivery']=Array();

if(isset($input_vars['ec_delivery'])) {
    //prn($input_vars);
    $_SESSION['ec_cart']['delivery']=Array(
            'ec_delivery'=>$input_vars['ec_delivery'],
            'ec_delivery_parameter'=>$input_vars['ec_delivery_parameter'] );
    $_SESSION['ec_cart']['delivery_cost']=delivery_cost($_SESSION['ec_cart']['total'],$this_site_info,$_SESSION['ec_cart']['delivery']);
}
$_SESSION['ec_cart']['total']=ec_cart_get_total($_SESSION['ec_cart']);
// -------------------- update delivery options - end --------------------------














# pre-defined form field names
if(!isset($_SESSION['user_data_fields'])) $_SESSION['user_data_fields']=Array();
$user_data_fields=Array(
        'ec_user_email'=>'','ec_user_name'=>'',
        'ec_user_telephone'=>'','ec_user_icq'=>'',
        'ec_user_delivery_city'=>'','ec_user_delivery_region'=>'',
        'ec_user_delivery_street_address'=>'','ec_user_delivery_suburb'=>''
);
if(isset($input_vars['confirmed'])) {
# -------------------- check posted data - begin -------------------------------
    //prn('Confirmed');
    $order_form_msg='';
    # ---------------- get posted data - begin ---------------------------------
    $cnt=array_keys($user_data_fields);
    foreach($cnt as $fld)
        if(isset($input_vars[$fld]))
            $user_data_fields[$fld]=$input_vars[$fld];
    # ---------------- get posted data - end -----------------------------------

    # ---------------- parse test rule - begin ---------------------------------
    if( isset($input_vars['ec_test_rule']) ) {
        //prn($input_vars['ec_test_rule']);
        if(!apply_test_rule($input_vars['ec_test_rule'],$input_vars)) {
            $order_form_msg.="<div style='color:red;'>".text('ec_user_Fill_in_the_form_correctly')."</div>";
        }
    }
    # ---------------- parse test rule - begin ---------------------------------

    # ---------------- save data to session - begin ----------------------------
    $cnt=array_keys($user_data_fields);
    foreach($cnt as $fld) $_SESSION['user_data_fields'][$fld]=$user_data_fields[$fld];
    # ---------------- save data to session - end ------------------------------

    # ---------------- get custom user fields - begin --------------------------
      $req=array_merge($_GET,$_POST);
      unset($req['site_id'],$req['action'],$req['confirmed'],$req['ec_test_rule']);
      foreach($req as $key=>$val){
          if(!isset($user_data_fields[$key])){
              $_SESSION['user_data_fields'][$key]=
              $user_data_fields[$key]=$input_vars[$key];
          }
      }
    # ---------------- get custom user fields - end ----------------------------


    # ------------------- check email format - begin ---------------------------
    # email is mandatory field
    $ec_user_email=isset($input_vars['ec_user_email'])?trim($input_vars['ec_user_email']):'';
    if(strlen($ec_user_email)>0) {
        if(!is_valid_email($ec_user_email)) {
            $order_form_msg.="<div style='color:red;'>".text('ec_user_email_is_invalid')."</div>";
        }
    }
    else {
        $order_form_msg.="<div style='color:red;'>".text('ec_user_email_is_empty')."</div>";
    }
    # ------------------ check email format - end ------------------------------

    # ------------------- check ICQ format - begin -----------------------------
    $ec_user_icq=isset($input_vars['ec_user_icq'])?trim($input_vars['ec_user_icq']):'';
    if(strlen($ec_user_icq)>0) {
        if(!preg_match('/^[0-9]+$/',$ec_user_icq)) {
            $order_form_msg.="<div style='color:red;'>".text('ec_user_ICQ_is_invalid')."</div>";
        }
    }
    # ------------------- check ICQ format - end -------------------------------

    # -------------------- check delivery options - begin ----------------------
    if(   count(delivery_config($this_site_info))>0) {
        if(!isset($_SESSION['ec_cart']['delivery']['ec_delivery'])
                || count($_SESSION['ec_cart']['delivery']['ec_delivery'])==0) {
            $order_form_msg.="<div style='color:red;'>".text('ec_delivery_missing')."</div>";
        }
    }
    # -------------------- check delivery options - end ------------------------
# -------------------- check posted data - end ---------------------------------
}else {
# -------------------- set saved data - begin ----------------------------------
    $cnt=array_keys($user_data_fields);
    foreach($cnt as $fld) {
        $user_data_fields[$fld]=(isset($_SESSION['user_data_fields'][$fld])?$_SESSION['user_data_fields'][$fld]:'');
    }
# -------------------- set saved data - end ------------------------------------
}























//prn($total);
//prn($order_form_msg);
//die();
//prn($_SESSION['ec_cart']);die();
// =============================================================================
// ------------------- creating order - begin ----------------------------------
if(strlen($order_form_msg)==0) {

    // ----------- check if site visitor already registered - begin ------------
    $query="SELECT *
              FROM <<tp>>site_visitor
              WHERE site_visitor_login='".\e::db_escape($user_data_fields['ec_user_email'])."'
                 OR site_visitor_email='".\e::db_escape($user_data_fields['ec_user_email'])."'";
    $site_visitor_info=\e::db_getonerow($query);
    // ----------- check if site visitor already registered - end --------------



    // ----------- register site visitor - begin -------------------------------
    if(!$site_visitor_info) {
        $user_data_fields['site_visitor_password']=
                $site_visitor_password=substr(md5(time().session_id()),0,10);
        $query="insert into <<tp>>site_visitor
                    ( site_visitor_password,
                      site_visitor_login,
                      site_visitor_email,
                      site_visitor_home_page_url)
                  values(
                     '".md5($site_visitor_password)."',
                     '".\e::db_escape($user_data_fields['ec_user_email'])."',
                     '".\e::db_escape($user_data_fields['ec_user_email'])."',
                     ''
                  )";
        \e::db_execute($query);
        $query="SELECT * FROM <<tp>>site_visitor WHERE site_visitor_id=LAST_INSERT_ID()";
        $site_visitor_info=\e::db_getonerow($query);
    }
    // ----------- register site visitor - end ---------------------------------




    // ----------- check if ec user data exists - begin ------------------------
    function compare_strings($str1,$str2) {
        $tmp1=preg_replace('/ +/',' ',$str1);
        $tmp2=preg_replace('/ +/',' ',$str2);
        if($tmp1==$tmp2) return true;

        $pattern='/\W/';
        $arr1=array_unique(preg_split($pattern,$str1));
        $arr2=array_unique(preg_split($pattern,$str2));
        $intersect=array_intersect($arr1,$arr2);
        if($arr1==$intersect && $arr2=$intersect) return true;
        return false;
    }

    $query="SELECT * FROM <<tp>>ec_user WHERE site_visitor_id={$site_visitor_info['site_visitor_id']}";
    $ec_user_records=\e::db_getrows($query);

    $ec_user_record_exists=false;
    foreach($ec_user_records as $ur) {
        //ec_user_id
        $equal=true;
        $equal=( $equal && compare_strings($ur['ec_user_name'],$user_data_fields['ec_user_name']));
        $equal=( $equal && compare_strings($ur['ec_user_telephone'],$user_data_fields['ec_user_telephone']));
        $equal=( $equal && compare_strings($ur['ec_user_icq'],$user_data_fields['ec_user_icq']));
        $equal=( $equal && compare_strings($ur['ec_user_delivery_city'],$user_data_fields['ec_user_delivery_city']));
        $equal=( $equal && compare_strings($ur['ec_user_delivery_region'],$user_data_fields['ec_user_delivery_region']));
        $equal=( $equal && compare_strings($ur['ec_user_delivery_street_address'],$user_data_fields['ec_user_delivery_street_address']));
        $equal=( $equal && compare_strings($ur['ec_user_delivery_suburb'],$user_data_fields['ec_user_delivery_suburb']));
        $ec_user_record_exists=$ec_user_record_exists||$equal;
        if($ec_user_record_exists) {
            $ec_user_id=$ur['ec_user_id'];
            break;
        }
    }
    // ----------- check if ec user data exists - end --------------------------

    // ----------------- create new user delivery data - begin -----------------
    if(!$ec_user_record_exists) {
        $query="insert into <<tp>>ec_user(
                    ec_user_name,
                    ec_user_telephone,
                    ec_user_icq,
                    ec_user_delivery_city,
                    ec_user_delivery_region,
                    ec_user_delivery_street_address,
                    ec_user_delivery_suburb,
                    ec_user_uid,
                    site_visitor_id
              )values(
                    '".\e::db_escape($user_data_fields['ec_user_name'])."',
                    '".\e::db_escape($user_data_fields['ec_user_telephone'])."',
                    '".\e::db_escape($user_data_fields['ec_user_icq'])."',
                    '".\e::db_escape($user_data_fields['ec_user_delivery_city'])."',
                    '".\e::db_escape($user_data_fields['ec_user_delivery_region'])."',
                    '".\e::db_escape($user_data_fields['ec_user_delivery_street_address'])."',
                    '".\e::db_escape($user_data_fields['ec_user_delivery_suburb'])."',
                    '".md5(time().session_id())."',
                {$site_visitor_info['site_visitor_id']}
              )";
        //prn('Saving new user data:'.$query);
        \e::db_execute($query);
        $ec_user_id=\e::db_getonerow("SELECT last_insert_id() as new_ec_user_id");
        $ec_user_id=$ec_user_id['new_ec_user_id'];
    }
    // ----------------- create new user delivery data - begin -----------------








    // ----------------- save new order - begin --------------------------------
    //prn('Creating new order');

    // multi-currency trial variant
    // ! don't work
    //   $ec_order_total=Array();
    //   foreach($total as $ct=>$va) $ec_order_total[]="{$va['sum']} {$ct}";
    //   $ec_order_total=join(',',$ec_order_total);



    // one-currency variant
    // it is preferred because the LiqPay payment system
    // acceps only ONE currency per payment
    $ec_order_total=$_SESSION['ec_cart']['total']+$_SESSION['ec_cart']['delivery_cost'];
    //prn($ec_order_total);
    $ec_order_statuses = explode(',',ec_order_status);
    $ec_order_status_default=array_shift($ec_order_statuses);

    $query="insert into <<tp>>ec_order(
                   ec_date_created, site_id, ec_order_status,
                   ec_order_total, ec_user_id, ec_order_paid,
                   ec_order_details,ec_order_custom_data)
	       values(
                   NOW(), $site_id,
                 '{$ec_order_status_default}',
                 '{$ec_order_total}', {$ec_user_id}, 0,
                 '".\e::db_escape(serialize($_SESSION['ec_cart']))."',
                 '".\e::db_escape(serialize($_SESSION['user_data_fields']))."')";
    //prn($query);alter table `<<tp>>ec_order` add column `ec_order_custom_data` text NULL after `ec_order_paid_amount`;
    \e::db_execute($query);

    $query="SELECT LAST_INSERT_ID() AS ec_order_id";
    //prn($query);
    $ec_order_id=\e::db_getonerow($query);
    $ec_order_id=$ec_order_id['ec_order_id'];

    // update order hash
    ec_order_sha($ec_order_id);
    // ----------------- save new order - end ----------------------------------



    // --------------------- save order items - begin --------------------------
    $query=Array();
    if (isset($_SESSION['ec_cart']['items']))
    foreach($_SESSION['ec_cart']['items'] as $it) {
        $query[]="(
            '".\e::db_escape($it['info']['ec_item_uid'])."',
            '".((int)$it['info']['ec_item_id'])."',
            '".\e::db_escape($it['info']['ec_item_lang'])."',
            '".\e::db_escape($it['info']['ec_item_title'])."',
            '".((float)$it['info']['ec_item_price'])."',
            '".\e::db_escape($it['info']['ec_item_currency'])."',
            '".\e::db_escape("{$it['info']['ec_item_size'][0]}x{$it['info']['ec_item_size'][1]}x{$it['info']['ec_item_size'][2]} {$it['info']['ec_item_size'][3]}")."',
            '".\e::db_escape("{$it['info']['ec_item_weight'][0]} {$it['info']['ec_item_weight'][1]}")."',
            '".\e::db_escape($it['amount'])."',
            '".\e::db_escape(serialize($it))."',
            '$ec_order_id',
                $site_id
	     )";
    }
    if(count($query)>0) {
        $query="insert into <<tp>>ec_cart(
                 ec_item_uid, ec_item_id, ec_item_lang, ec_item_title,
                 ec_item_price, ec_item_currency, ec_item_size,
                 ec_item_weight, ec_cart_amount, ec_cart_item,
                 ec_order_id, site_id )
               values ".join(',',$query);
        //prn($query);
        \e::db_execute($query);
    }
    // --------------------- save order items - end ----------------------------





    // ----------------------- update ec_item state - begin --------------------
    if (isset($_SESSION['ec_cart']['items']))
    foreach($_SESSION['ec_cart']['items'] as $it) {
        $query="UPDATE <<tp>>ec_item
                 SET ec_item_amount=ec_item_amount-1,
                     ec_item_purchases=ifnull(ec_item_purchases,0)+1
                 WHERE ec_item_id=".$it['info']['ec_item_id']." LIMIT 1";
        \e::db_execute($query);
        if(    (($it['info']['ec_item_amount']-1)<=0)
                && function_exists($it['info']['ec_item_onnullamount'])
                && eregi('^onnullamount_',$it['info']['ec_item_onnullamount'])
        ) {
            call_user_func($it['info']['ec_item_onnullamount'], $it['info']['ec_item_id']);
        }
    }
    // ----------------------- update ec_item state - end ----------------------












    // ----------------------- send email - begin ------------------------------

    $delivery_info=delivery_info($_SESSION['ec_cart']['total'],$this_site_info,$_SESSION['ec_cart']['delivery']);






    # ---------- email confirmation to customer - begin ------------------------
    if(is_valid_email($ec_user_email)) {
        # search for template
        $ec_item_template=site_get_template($this_site_info,'template_email_to_customer_order_created');

        $email_body=process_template( $ec_item_template
                ,Array(
                'ec_cart'          =>$_SESSION['ec_cart'],
                'delivery_info'    =>$delivery_info,
                'total'            =>$ec_order_total,
                'message'          =>$order_form_msg,
                'site'             =>$this_site_info,
                'user'             =>array_merge($_SESSION['user_data_fields'],$site_visitor_info),
                'ec_order_id'      =>$ec_order_id,
                'custom'           =>$_SESSION['user_data_fields'],
                'site_visitor_password'=>(isset($site_visitor_password)?$site_visitor_password:'')  )
        );
        $email_subject=get_langstring($this_site_info['title'],$lang).':'.$txt['EC_new_order_No'].' '.$ec_order_id;
        if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
        //prn($ec_item_template,$ec_user_email, $email_subject, $email_body);
        my_mail($ec_user_email, $email_subject, $email_body);
    }
    # ---------- email confirmation to customer - end --------------------------



    # ---------- email confirmation to site manager - begin --------------------
    # search for template
    $ec_item_template=site_get_template($this_site_info,'template_email_to_seller_order_created');

    $email_body=process_template( $ec_item_template
            ,Array(
            'ec_cart'          =>$_SESSION['ec_cart'],
            'delivery_info'    =>$delivery_info,
            'total'            =>$ec_order_total,
            'message'          =>$order_form_msg,
            'site'             =>$this_site_info,
            'user'             =>array_merge($user_data_fields,$site_visitor_info),
            'ec_order_id'      =>$ec_order_id,
            'custom'           =>$_SESSION['user_data_fields'],
            'ec_user_delivery_address'
            )
    );
    $email_subject=get_langstring($this_site_info['title'],$lang).':'.$txt['EC_new_order_No'].' '.$ec_order_id;

    if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
    //prn($this_site_info);
    foreach($this_site_info['managers'] as $mng) {
        my_mail($mng['email'], $email_subject, $email_body);
    }
    # ---------- email confirmation to site manager - end ----------------------
    # ----------------------- send email - end ---------------------------------







    // --------------------- update order history - begin ----------------------
    update_ec_order_history(
            text('Ec_order_created'),
            str_replace('ec/order/admindetails','ec/order/view',$email_body),
            'order_created',
            $ec_order_id,
            $site_visitor_info['site_visitor_id'],///
            $site_id,
            0);
    // --------------------- update order history - end ------------------------

    // ----------------------- send other notifications - begin ----------------
    notify('order_created',
            $this_site_info,
            Array(
            'ec_cart'          =>$_SESSION['ec_cart'],
            'delivery_info'    =>$delivery_info,
            'total'            =>$ec_order_total,
            'message'          =>$order_form_msg,
            'site'             =>$this_site_info,
            'user'             =>array_merge($user_data_fields,$site_visitor_info),
            'ec_order_id'      =>$ec_order_id ) );
    // ----------------------- send other notifications - end ------------------

    // prn($total);
    //echo("Location: index.php?action=ec/order/view&ec_order_id={$ec_order_id}&lang={$lang}&message=EC_Order_successfully_sent");
    //die();
    unset($_SESSION['ec_cart']);//<big>{$message}</big><br><br>
    if(!isset($_SESSION['ec_order_ids'])) $_SESSION['ec_order_ids']=Array();
    $_SESSION['ec_order_ids'][$ec_order_id]=$ec_order_id;

    header("Location: index.php?action=ec/order/view&ec_order_id={$ec_order_id}&lang={$lang}&message=EC_Order_successfully_sent");
    return '';
}
// ---------------------- creating new order - end -----------------------------
// =============================================================================




















//prn($_SESSION['ec_cart']);


//------------------------ draw using SMARTY template - begin ------------------


# get site menu
$menu_groups = get_menu_items($this_site_info['id'],0,$lang);

# search for template
$ec_neworder_template=site_get_template($this_site_info,'template_ec_order_new');

//prn($_SESSION['ec_cart']);
//prn($_SESSION);
$ec_order_total=$_SESSION['ec_cart']['total']+$_SESSION['ec_cart']['delivery_cost'];
//prn('$ec_order_total='.$ec_order_total);
//prn($ec_item_template);

$user_data_fields=isset($_SESSION['user_data_fields'])?$_SESSION['user_data_fields']:[];
if(!is_array($user_data_fields)) $user_data_fields=[];
if(!isset($user_data_fields['ec_user_name'])) $user_data_fields['ec_user_name']='';
if(!isset($user_data_fields['ec_user_email'])) $user_data_fields['ec_user_email']='';
if(!isset($user_data_fields['ec_user_telephone'])) $user_data_fields['ec_user_telephone']='';
if(!isset($user_data_fields['ec_user_icq'])) $user_data_fields['ec_user_icq']='';
if(!isset($user_data_fields['ec_user_delivery_street_address'])) $user_data_fields['ec_user_delivery_street_address']='';
if(!isset($user_data_fields['ec_user_delivery_city'])) $user_data_fields['ec_user_delivery_city']='';
if(!isset($user_data_fields['ec_user_delivery_suburb'])) $user_data_fields['ec_user_delivery_suburb']='';
if(!isset($user_data_fields['ec_user_delivery_region'])) $user_data_fields['ec_user_delivery_region']='';


$vyvid=
        process_template( $ec_neworder_template
        ,array_merge(Array(
        'ec_cart'=>$_SESSION['ec_cart'],
        'total'=>$_SESSION['ec_cart']['total'],
        'message'=>$order_form_msg,
        'text'=>$txt,
        'edit_cart_url'=>"index.php?action=ec/cart/view&site_id=$site_id&lang=$lang",
        'remove_item_url_prefix'=>"index.php?action=ec/cart/add&site_id=$site_id&ec_category_id=",
        'site'=>$this_site_info,
        'url_prefix_delete'=>"index.php?action=ec/cart/view&site_id=$site_id&lang=$lang&cart_delete_key=",
        'hidden_order_form_elements'=>preg_hidden_form_elements('^ec_user_|^confirmed$|^ec_test_rule$|^ec_delivery').'<input type=hidden name=confirmed value=yes>',
        'site_visitor_info'=>( isset($_SESSION['site_visitor_info'])?$_SESSION['site_visitor_info']:''),
        'delivery_form'=>delivery_form($_SESSION['ec_cart']['total'],$this_site_info,$_SESSION['ec_cart']['delivery']),
	'ec_order_total'=>$ec_order_total,
        'custom'=>$user_data_fields
        ),$user_data_fields)
);



# search for template
$custom_page_template=site_get_template($this_site_info,'template_index');
# --------------------------- get site template - end --------------------------
$file_content=process_template($custom_page_template//$this_site_info['template']
        ,Array(
        'page'=>Array('title'=>$txt['EC_send_order']
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

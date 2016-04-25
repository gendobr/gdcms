<?php
/**
 * This page is shown to customer
 * after the LiqPay payment is finished
 * &ec_order_id={$ec_order_id}&lang={$lang}
 */
// &message=EC_Order_payment_result
$GLOBALS['main_template_name']='';
run('ec/order/functions');
run('ec/pay/interfaces');
run('ec/pay/liqpay/class_reply');
run('ec/cart/functions');

run('site/page/page_view_functions');
run('site_visitor/functions');
run('site/menu');
run('notifier/functions');




# -------------------- process payment system reply - begin --------------------
$payment_reply = new payment_reply($input_vars);
$payment_reply->is_valid=$payment_reply->is_valid();
// prn($payment_reply);
# -------------------- process payment system reply - end ----------------------


# --------------------- get order info - begin ---------------------------------
  $ec_order_id=1*$payment_reply->get_order_id();
  $this_ec_order_info=get_order_info($ec_order_id);
  if(!$this_ec_order_info)  die('Order not found');
  $this_ec_order_info['ec_order_status_text']=text('ec_order_status_'.$this_ec_order_info['ec_order_status']);
  // prn($this_ec_order_info);
# --------------------- get order info - end -----------------------------------

# -------------------- set interface language - begin --------------------------
  $debug=false;
  if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
  if(!isset($input_vars['lang'])   ) $input_vars['lang']=\e::config('default_language');
  if(strlen($input_vars['lang'])==0) $input_vars['lang']=\e::config('default_language');
  // $lang=$input_vars['lang'];
  $lang = get_language('lang');
# -------------------- set interface language - end ----------------------------

# ------------------- get site info - begin ------------------------------------
  $site_id = checkInt($this_ec_order_info['site_id']);
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
  //prn($this_site_info);
  //prn($input_vars);
# ------------------- get site info - end --------------------------------------


# --------------------- check permissions - begin ------------------------------
# ec_order_id is saved in the session as order just created
  $access_allowed=( isset($_SESSION['ec_order_ids']) && isset($_SESSION['ec_order_ids'][$this_ec_order_info['ec_order_id']]));

# site visitor is logged in and owns order
  $access_allowed=( $access_allowed || isset($_SESSION['site_visitor_info']) && $_SESSION['site_visitor_info']['is_logged'] && $_SESSION['site_visitor_info']['site_visitor_id']==$this_ec_order_info['site_visitor_id']);

# user is site admin
  $access_allowed=( $access_allowed || ( get_level($site_id)>0 ) );

# user is superuser
  $access_allowed=( $access_allowed ||  is_admin());

  if(!$access_allowed)
  {
      $input_vars['page_title']  =
      $input_vars['page_header'] =
      $input_vars['page_content']= text('Access_denied');
      return 0;
  }
# --------------------- check permissions - end --------------------------------


# get site template
  $custom_page_template = site_get_template($this_site_info,'template_index');


# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
  $lang_list=array_values($lang_list);
# -------------------- get list of page languages - end ------------------------















//------------------------ draw using SMARTY template - begin ------------------

  # get site menu
    $menu_groups = get_menu_items($this_site_info['id'],0,$lang);

  # search for template
    $ec_item_template = site_get_template($this_site_info,'template_ec_order_postpay');


  if(isset($_SESSION['site_visitor_info']) && $_SESSION['site_visitor_info']['is_logged'])
  {
     $vyvid=" {$_SESSION['site_visitor_info']['site_visitor_login']}! ".site_visitor_draw_menu($_SESSION['site_visitor_info'],$this_site_info).'<br/><br/>';
  }else $vyvid='';

  $vyvid.=
  process_template( $ec_item_template
                   ,Array( 'order'=>$this_ec_order_info,
                           'total'=>$this_ec_order_info['total'],
                           'site'=>$this_site_info,
                           'user'=>$this_ec_order_info['shipping'],
                           'validation_result'=>( ec_order_validate($this_ec_order_info)?1:0 ),
                           'payment_reply'=>$payment_reply,
                           'url_order_details'=>site_root_URL."/index.php?action=ec/order/view&lang={$lang}&ec_order_id=".$this_ec_order_info['ec_order_id'],
                           'lang'=>$input_vars['lang'] ) );

  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>text('EC_order').' '.$this_ec_order_info['ec_order_id'].' - '.text('ec_payment_reply')
                                               ,'content'=>$vyvid
                                               ,'abstract'=> ''
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$input_vars['lang']
                                          )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

?>
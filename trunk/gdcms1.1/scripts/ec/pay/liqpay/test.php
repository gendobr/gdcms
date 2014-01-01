<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include '../interfaces.php';
include 'class_liqpay_request.php';

// echo '<pre>'; print_r($_SERVER);echo '</pre>';
$merchant_id='i0390394910';
$merchant_sign='mDI53yabbkfIXRaz6oWHOrq4ABUDwEcVOnW';

$paynowform=new liqpay_request(Array(
      'result_url'=>"http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}",
      'server_url'=>"http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}",
      'merchant_id'=>$merchant_id, // identifier of the merchant
      'order_id'=>'yoursite_order_#'.time(),
      'amount'=>'0.01',
      'currency'=>'USD',
      'description'=>'Some short descripition',
      'default_phone'=>'',
      'pay_way'=>'card',
      'merchant_sign'=>$merchant_sign
));

//$paynowform->liqpay_script="http://{$_SERVER['HTTP_HOST']}".dirname($_SERVER['PHP_SELF']).'/sample_reply.php';

echo $paynowform->get_pay_now_form('Buy Now');


if(isset($_REQUEST['operation_xml']))
{
  include 'class_liqpay_reply.php';
  $payment_reply = new liqpay_reply($_REQUEST['operation_xml'],$_REQUEST['signature'],$merchant_sign);
  echo $payment_reply->get_human_readable_info();
}
/*
Action:result_url;
Merchant ID:i0390394910;
Order ID:yoursite_order_#1252264891;
Amount:0.01;
Currency:USD;
Status:wait_secure;
Error code:;
Transaction ID:1814928;
Payment Way:card;
Sender Phone:+380962854775;
Description:Some short descripition;
*/
?>

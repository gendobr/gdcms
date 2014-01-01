<?php

//echo checkStr(base64_decode($_REQUEST['operation_xml']));

$posted_xml=simplexml_load_string(base64_decode($_REQUEST['operation_xml']));

//echo  '<hr>'.$posted_xml->order_id;


$xml="<reply>
  <version>1.2</version>
  <action>result_uri</action>
  <merchant_id>{$posted_xml->merchant_id}</merchant_id>
  <order_id>{$posted_xml->order_id}</order_id>
  <amount>{$posted_xml->amount}</amount>
  <currency>{$posted_xml->currency}</currency>
  <description>{$posted_xml->description}</description>
  <status>success</status>
  <code></code>
  <transaction_id>".time()."</transaction_id>
  <pay_way>card</pay_way>
  <sender_phone>card</sender_phone>
</reply>";

//echo '<hr>'.checkStr($xml);

$merchant_sign=$_REQUEST['merchant_sign'];
$xml_string_encoded=base64_encode($xml);
$signature=base64_encode(sha1($merchant_sign.$xml.$merchant_sign,1));

// echo $posted_xml->result_url;
// echo $posted_xml->server_url;
header("Location: {$posted_xml->server_url}".(eregi("\\?",$posted_xml->server_url)?'&':'?')."operation_xml=".rawurlencode($xml_string_encoded)."&signature=".rawurlencode($signature));
?>
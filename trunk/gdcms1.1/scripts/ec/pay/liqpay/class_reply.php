<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class payment_reply implements pay_reply{

    var $action;
    var $merchant_id;
    var $order_id;
    var $amount_id;
    var $currency;
    var $description;
    var $status; // success | failure | wait_secure
    var $code;   // error code
    var $transaction_id; //
    var $pay_way;
    var $sender_phone;

    var $xml_string;
    var $signature;
    var $merchant_sign;
    function __construct($REQUEST,$merchant_sign=liqpay_merchant_sign)
    {
        // $_REQUEST['operation_xml'],$_REQUEST['signature'],liqpay_merchant_sign
        $xml_string_encoded=$REQUEST['operation_xml'];
        $signature=$REQUEST['signature'];

        $this->merchant_sign=$merchant_sign;
        $this->signature=$signature;

        $this->xml_string=
        $xml_string=base64_decode($xml_string_encoded);
        //echo '<pre>';echo(checkStr($xml_string));echo '</pre>';

        $xml = simplexml_load_string($xml_string);
        //echo '<pre>';print_r($xml);echo '</pre>';
        $this->action=$xml->action;
        $this->merchant_id=$xml->merchant_id;
        $this->order_id=$xml->order_id;
        $this->amount=str_replace(',','.',$xml->amount);
        $this->currency=$xml->currency;
        $this->description=$xml->description;
        $this->status=$xml->status; // success | failure | wait_secure
        $this->code=$xml->code;   // error code
        $this->transaction_id=$xml->transaction_id; //
        $this->pay_way=$xml->pay_way;
        $this->sender_phone=$xml->sender_phone;
    }


    // order id
       public function get_order_id()       { return $this->order_id;      }

    // transaction id
       public function get_transaction_id() { return $this->transaction_id;}

    // amount to pay
       public function get_amount(){ return $this->amount;}

    // currency
       public function get_currency(){ return $this->currency;}

    // status of the payment (success|failure|waiting|unknown)
       public function get_status() {
            switch($this->status)
            {
                case 'success'    : return 'success'; break;
                case 'failure'    : return 'failure'; break;
                case 'wait_secure': return 'waiting'; break;
                default: return '';
            }
       }

    // full descripition of payment
       public function get_description(){return $this->xml_string;}


       public function get_human_readable_info()
       {
           //<b>Merchant ID:</b>{$this->merchant_id};<br>
           $info="
           <b>Action:</b>{$this->action};<br>
           <b>Order ID:</b>{$this->order_id};<br>
           <b>Amount:</b>{$this->amount};<br>
           <b>Currency:</b>{$this->currency};<br>
           <b>Status:</b>{$this->status};<br>
           <b>Error code:</b>{$this->code};<br>
           <b>Transaction ID:</b>{$this->transaction_id};<br>
           <b>Payment Way:</b>{$this->pay_way};<br>
           <b>Sender Phone:</b>{$this->sender_phone};<br>
           <b>Description:</b>{$this->description};<br>
           ";
           return $info;
       }

       public function is_valid()
       {
          $correct_signature=base64_encode(sha1($this->merchant_sign.$this->xml_string.$this->merchant_sign,1));
          return $correct_signature==$this->signature;
       }
}

/*
  Sample usage
  $payment_reply = new liqpay_reply($_REQUEST['operation_xml'],$_REQUEST['signature'],'2312378h');
  echo $payment_reply->get_human_readable_info();

 */
?>

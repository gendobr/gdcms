<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/
class payment_request {

    var $version;
    var $result_url;    // show this page to customer
    var $server_url;    // post result to this address
    var $merchant_id;   // registration data from liqpay
    var $order_id;      // some string, your own order UID
    var $amount;        // amount to pay
    var $currency;      // UAH or USD or EUR or RUR
    var $description;   // some your comments
    var $default_phone; // ?????
    var $pay_way;       // card or liqpay (default way to pay)
    var $merchant_sign; // merchant signature (generated at LiqPay site)

    var $liqpay_script;

    function __construct($data) {
        // real payment processing site
        $this->liqpay_script='https://liqpay.com/?do=clickNbuy';

        // show this page to customer
        $this->result_url=isset($data['result_url'])?$data['result_url']:'';

        // post result to this address
        $this->server_url=isset($data['server_url'])?$data['server_url']:'';

        // registration data from liqpay
        if(!isset($data['merchant_id'])) return $this->show_error('ERROR: merchant_id is not set');
        $this->merchant_id=$data['merchant_id'];

        // some string, your own order UID
        if(!isset($data['order_id'])) return $this->show_error('ERROR: order_id is not set');
        $this->order_id=$data['order_id'];

        // amount to pay
        if(!isset($data['amount'])) return $this->show_error('ERROR: amount is not set');
        $this->amount=$data['amount'];

        // currency UAH or USD or EUR or RUR
        $this->currency=isset($data['currency'])?$data['currency']:'UAH';

        // some your comments
        $this->description=isset($data['description'])?$data['description']:'';

        // ?????
        $this->default_phone=isset($data['default_phone'])?$data['default_phone']:'';

        // card or liqpay (default way to pay)
        $this->pay_way=isset($data['pay_way'])?$data['pay_way']:'card';

        // merchant signature (generated at LiqPay site)
        if(!isset($data['merchant_sign'])) return $this->show_error('ERROR: merchant_sign is not set');
        $this->merchant_sign=$data['merchant_sign'];
    }

    function get_pay_now_form($part_to_pay=1) {
        $xml="<request>"
                ."<version>1.2</version>"
                ."<result_url>{$this->result_url}</result_url>"
                ."<server_url>{$this->server_url}</server_url>"
                ."<merchant_id>{$this->merchant_id}</merchant_id>"
                ."<order_id>{$this->order_id}</order_id>"
                ."<amount>".($this->amount*$part_to_pay)."</amount>"
                ."<currency>{$this->currency}</currency>"
                ."<description>{$this->description} (".($part_to_pay*100)."%)</description>"
                ."<default_phone>{$this->default_phone}</default_phone>"
                ."<pay_way>{$this->pay_way}</pay_way>"
                ."</request>";
        $sign=base64_encode(sha1($this->merchant_sign.$xml.$this->merchant_sign,1));
        $xml_encoded=base64_encode($xml);

        $theform="
            <form action=\"{$this->liqpay_script}\" method=\"POST\" class=\"payment_form\">
              <input type=\"hidden\" name=\"operation_xml\" value=\"$xml_encoded\">
              <input type=\"hidden\" name=\"signature\" value=\"$sign\">
              <input type=\"image\" src=\"scripts/ec/pay/liqpay/PrivatbankLiqPAY.png\">
            </form>
                ";
        //prn(checkStr($theform));
        return $theform;
    }

    function show_error($str) {
        echo "\n\n<hr>$str<hr>\n\n";
        return '';
    }
}

/*
 * Sample usage is
    $paynowform=new liqpay_request(Array(
      'result_url'=>'http://www.yoursite.com/customer_payment_result_page.php',
      'server_url'=>'http://www.yoursite.com/accounting_page.php',
      'merchant_id'=>'fsdf34fe', // identifier of the merchant
      'order_id'=>'yoursite_order_#123279',
      'amount'=>'0.01',
      'currency'=>'USD',
      'description'=>'Some short descripition',
      'default_phone'=>'1234567890',
      'pay_way'=>'cart',
      'merchant_sign'=>'fsd234v'
    ));
    echo $paynowform->get_pay_now_form('Buy Now');
*/
?>

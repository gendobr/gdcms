<?php

interface pay_reply
{
    public function get_order_id();            // order id
    public function get_transaction_id();      // transaction id
    public function get_amount();              // amount to pay
    public function get_currency();            // currency
    public function get_status();              // status of the payment (success|failure|waiting)
    public function get_description();         // full descripition of payment
    public function get_human_readable_info(); // get human-readable information to show on the result page
    public function is_valid();                // if payment data is valid (i.e. fraud detector)
}
?>

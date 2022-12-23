<?php
/*
 Order functions
*/


/**
 * extract order details from database
 */
function get_order_info($order_id) {

    $query="SELECT ec_order.*, ec_user.site_visitor_id
            FROM <<tp>>ec_order ec_order
                 INNER JOIN <<tp>>ec_user ec_user
                 ON ec_user.ec_user_id=ec_order.ec_user_id
            WHERE ec_order_id=$order_id";
    $tor=\e::db_getonerow($query);

    $tor['ec_cart']=unserialize($tor['ec_order_details']);
    $tor['custom']=unserialize($tor['ec_order_custom_data']);
    // prn($tor);
    /*
    $ec_cart=\e::db_getrows("SELECT * FROM <<tp>>ec_cart WHERE ec_order_id=$order_id");
    foreach($ec_cart as $ec)
    {
        $tmp=unserialize($ec['ec_cart_item']);
        $tor['ec_cart']['items']["{$tmp['info']['ec_item_id']}-{$tmp['info']['ec_item_lang']}"]=$tmp;
    }
    */
    $tor['total']=$tor['ec_cart']['total']+$tor['ec_cart']['delivery_cost'];


    $tor['shipping']=\e::db_getonerow("SELECT ec_user.* ,site_visitor.site_visitor_email
                                   FROM <<tp>>ec_user ec_user
                                       INNER JOIN <<tp>>site_visitor site_visitor
                                       ON ec_user.site_visitor_id = site_visitor.site_visitor_id
                                   WHERE ec_user_id='{$tor['ec_user_id']}'");
    // prn($tor);
    return $tor;
}

function ec_order_delete($id) {
    global $text;

    $query="DELETE FROM <<tp>>ec_cart  WHERE ec_order_id={$id}";
    \e::db_execute($query);

    $query="DELETE FROM <<tp>>ec_order WHERE ec_order_id={$id}";
    \e::db_execute($query);

    $query="DELETE FROM <<tp>>ec_order_history WHERE ec_order_id={$id}";
    \e::db_execute($query);
}


function ec_order_validate($order_info) {
    $ec_order_hash=sha1("{$order_info['ec_order_id']} {$order_info['ec_date_created']} {$order_info['site_id']} {$order_info['ec_order_status']} {$order_info['ec_order_total']} {$order_info['ec_user_id']} {$order_info['ec_order_paid']} {$order_info['ec_order_details']} {$order_info['ec_order_paid_amount']}");
    //prn($ec_order_hash);
    return $order_info['ec_order_hash']==$ec_order_hash;
}

function menu_ec_order($_info) {
    global $text;
    $tor=Array();
    $sid=session_name().'='.$GLOBALS['_COOKIE'][session_name()];

    $tor['ec/order/edit']=Array(
            'URL'=>"index.php?action=ec/order/admindetails&ec_order_id={$_info['ec_order_id']}"
            ,'innerHTML'=>text('EC_order_details').'<br><br>'
            ,'attributes'=>''
    );
    $tor['ec/order/delete']=Array(
            'URL'=>"index.php?action=ec/order/list&site_id={$_info['site_id']}&".query_string('ec_order_delete|action')."&ec_order_delete={$_info['ec_order_id']}"
            ,'innerHTML'=>text('Delete') //.'<iframe src="about:blank" width=10px height=1px style="border:none;" name="frm_delete"></iframe>'
            ,'attributes'=>" onclick='return confirm(\"".text('Are_You_sure')."?\")' " // target=frm_delete
    );
    return $tor;
}

function update_ec_order_history(
        $ec_order_history_title,
        $ec_order_history_details,
        $ec_order_history_action,
        $ec_order_id,
        $site_visitor_id,
        $site_id,
        $user_id) {
    $query="INSERT INTO <<tp>>ec_order_history(
                   ec_order_history_title,
                   ec_order_history_details,
                   ec_order_history_date,
                   ec_order_history_action,
                   ec_order_id,
                   site_visitor_id,
                   site_id,
                   user_id )
                VALUES(
                   '".\e::db_escape($ec_order_history_title)."',
                   '".\e::db_escape($ec_order_history_details)."',
                      NOW(),
                   '$ec_order_history_action',
            $ec_order_id,
            ".( (int)$site_visitor_id ).",
            $site_id,
            ".( (int)$user_id )." )";
    //prn($query);
    \e::db_execute($query);
}


class order_history {
    private $ec_order_id;
    private $form;
    function __construct($ec_order_id) {
        $this->ec_order_id=$ec_order_id;
    }

    public function __get($name) {
        if($name=='list') {
            $query="SELECT ec_order_history.*,site_visitor.site_visitor_email,user.email
                   FROM <<tp>>ec_order_history as ec_order_history
                        LEFT JOIN <<tp>>site_visitor as site_visitor
                        ON ec_order_history.site_visitor_id=site_visitor.site_visitor_id
                        LEFT JOIN <<tp>>user as user
                        ON ec_order_history.user_id=user.id
                   WHERE ec_order_id={$this->ec_order_id}
                   ORDER BY ec_order_history_date ASC
                    ";
            return \e::db_getrows($query);
        }
        elseif($name=='form') {
            if(!isset($this->form)) {
                $this->form=Array(
                        'hidden_elements'=>"<input type=\"hidden\" name=\"action\" value=\"{$_REQUEST['action']}\">"
                                ."<input type=\"hidden\" name=\"ec_order_id\" value=\"{$_REQUEST['ec_order_id']}\">",
                        'action'=>site_root_URL."/index.php",
                        'textarea_name'=>'ec_order_history_details'
                );
            }
            return $this->form;
        }
        else {
            return $this->$name;
        }
    }
}

function ec_order_sha($ec_order_id) {
    $query="UPDATE <<tp>>ec_order
           SET ec_order_hash=SHA1(CONCAT_WS(' ',ec_order_id,ec_date_created,site_id,ec_order_status,ec_order_total,ec_user_id,ec_order_paid,ec_order_details,ec_order_paid_amount))
           WHERE ec_order_id=$ec_order_id";
    //prn($query);
    \e::db_execute($query);
}


function apply_test_rule($rule,$input_vars) {
    // ------------- create postfix expression - begin ----------------
    $regexp="/(\\(|\\)|\\||&|[ a-z0-9_]+)/";
    $operators=Array('|','&');
    if(!preg_match_all($regexp,$rule,$matches)) return false;

    $postfix=Array();
    $stack=Array();
    //prn($matches[1]);
    foreach($matches[1] as $it) {
        if(preg_match("/[ a-z0-9_]+/i",$it)) {
            $postfix[]=trim($it);
            //$postfix[]=$input_vars[trim($it)];
            continue;
        }
        if($it=='(') {
            array_push($stack,$it);
            continue;
        }
        if($it==')') {
            $tmp_it = array_pop($stack);
            while($tmp_it && $tmp_it!='(') {
                $postfix[]=$tmp_it;
                $tmp_it = array_pop($stack);
            }
            continue;
        }
        if(in_array($it,$operators)) {
            while(
            (($cnt=count($stack))>0)
                    && (in_array($stack[$cnt-1],$operators))
            ) {
                $tmp_it = array_pop($stack);
                $postfix[]=$tmp_it;
            }
            array_push($stack,$it);
        }
    }
    while(count($stack)>0) {
        $tmp_it = array_pop($stack);
        $postfix[]=$tmp_it;
    }
    //prn($postfix);
    // ------------- create postfix expression - end ------------------


    // ------------- apply postfix expression - begin -----------------
    $stack=Array();
    foreach($postfix as $it) {
        $itVal = isset($input_vars[$it]) ? trim($input_vars[$it]) : "";
        if(preg_match("/[ a-z0-9_]+/i",$it)) {
            array_push($stack,  strlen($itVal)>0 );
        }
        elseif(in_array($it,$operators)) {
            $operand1 = array_pop($stack);
            $operand2 = array_pop($stack);
            switch($it) {
                case '|': $result=($operand1||$operand2);
                    break;
                case '&': $result=($operand1&&$operand2);
                    break;
                default : $result=false;
            }
            array_push($stack,$result);
        }
        //prn($it, $stack );
    }
    $result = array_pop($stack);
    // ------------- apply postfix expression - end -------------------
    return $result;

}

?>
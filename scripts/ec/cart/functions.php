<?php

/**
 * Calculates total sum of prices
 */
function ec_cart_get_total($ec_cart) {
    $total = Array();
    if (!isset($ec_cart['items']) || !is_array($ec_cart['items']))
        return 0;
    if ($ec_cart['items']) {
        foreach ($ec_cart['items'] as $it) {
            if (!isset($total[$it['info']['ec_item_currency']])) {
                $total[$it['info']['ec_item_currency']] = Array(
                    'sum' => 0,
                    'ec_item_currency_title' => $it['info']['ec_item_currency_title']
                );
            }
            $total[$it['info']['ec_item_currency']]['sum']+=$it['info']['ec_item_price_corrected'] * $it['amount'];
        }
    }
    $cnt = array_keys($total);

    foreach ($cnt as $cr)
        $total[$cr]['sum'] = round($total[$cr]['sum'], 2);

    if (count($total) == 0) {
        $total[''] = Array(
            'sum' => 0,
            'ec_item_currency_title' => ''
        );
    }
    $sum = 0;
    foreach ($total as $key => $val) {
        $sum+=$val['sum'];
    }
    return $sum;
    // return $total;
}

/**
 * Add item to shopping cart
 */
function ec_cart_additem($this_ec_item_info, $input_vars) {
    $this_ec_item_info['ec_item_abstract'] = '<!-- deleted -->';
    $this_ec_item_info['ec_item_content'] = '<!-- deleted -->';

    $this_ec_item_info['ec_item_currency_title'] =\e::db_getonerow("SELECT * FROM {$GLOBALS['table_prefix']}ec_currency WHERE ec_currency_code='{$this_ec_item_info['ec_item_currency']}'");
    $this_ec_item_info['ec_item_currency_title'] = $this_ec_item_info['ec_item_currency_title']['ec_curency_title'];

    //unset($_SESSION['ec_cart']);
    if (!isset($_SESSION['ec_cart']))
        $_SESSION['ec_cart'] = Array(
            'total' => 0,
            'items' => Array()
        );

    # -------------------- variant - begin -------------------------------------
    // prn($input_vars['ec_item_variant']);

    // count variants
    $cnt = count($this_ec_item_info['ec_item_variant']);

    // start from original price
    $this_ec_item_info['ec_item_price_corrected'] = $this_ec_item_info['ec_item_price'];

    // if ec_item_variant is not set
    // then use defaults
    if (!isset($input_vars['ec_item_variant'])) {
        $input_vars['ec_item_variant'] = Array();
        foreach ($this_ec_item_info['ec_item_variant'] as $v) {
            if (isset($v['is_default'])) {
                $input_vars['ec_item_variant'][$v['ec_item_variant_group']] = $v['ec_item_variant_code'];
            }
        }
    }

    // if item has variants ...
    if ($cnt > 0) {

        // remove variants which are not selected
        for ($i = 0; $i < $cnt; $i++) {
            if (count($this_ec_item_info['ec_item_variant']) == 1)
                break;
            if (!in_array($this_ec_item_info['ec_item_variant'][$i]['ec_item_variant_code'], $input_vars['ec_item_variant'])) {
                unset($this_ec_item_info['ec_item_variant'][$i]);
            }
        }
        $this_ec_item_info['ec_item_variant'] = array_values($this_ec_item_info['ec_item_variant']);



        // correct price
        $input_vars['ec_item_variant'] = Array();
        $this_ec_item_info['ec_item_price_corrected'] = $this_ec_item_info['ec_item_price'];
        foreach ($this_ec_item_info['ec_item_variant'] as $var) {
            $input_vars['ec_item_variant'][] = $var['ec_item_variant_id'];

            // ------------------- correct price - begin ----------------------
            if (strlen($var['ec_item_variant_price_correction']['error']) == 0) {
                $operation = $var['ec_item_variant_price_correction']['operation'];
                $value = $var['ec_item_variant_price_correction']['value'];
                switch ($operation) {
                    case '*':
                        $this_ec_item_info['ec_item_price_corrected']*=$value;
                        break;
                    case '/':
                        $this_ec_item_info['ec_item_price_corrected']/=$value;
                        break;
                    case '+':
                        $this_ec_item_info['ec_item_price_corrected']+=$value;
                        break;
                    case '-':
                        $this_ec_item_info['ec_item_price_corrected']-=$value;
                        break;
                }
            }
            // ------------------- correct price - end ------------------------
        }
    } else {
        $input_vars['ec_item_variant'] = Array();
    }
    //prn($this_ec_item_info);
    //prn('ec_item_price_corrected=',$this_ec_item_info['ec_item_price_corrected']);
    # -------------------- variant - end ---------------------------------------
    # -------------------- add item to cart - begin ----------------------------
    $uid = "{$this_ec_item_info['ec_item_id']}-{$this_ec_item_info['ec_item_lang']}-variant-" . join('-', $input_vars['ec_item_variant']);
    if (isset($_SESSION['ec_cart']['items'][$uid]))
        $_SESSION['ec_cart']['items'][$uid]['amount']++;
    else
        $_SESSION['ec_cart']['items'][$uid] = Array('amount' => 1, 'info' => $this_ec_item_info);
    # -------------------- add item to cart - begin ----------------------------

    $_SESSION['ec_cart']['total'] = ec_cart_get_total($_SESSION['ec_cart']);
}

?>
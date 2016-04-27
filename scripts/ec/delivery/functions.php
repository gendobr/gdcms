<?php
/**
 * "Choose delivery type" form
 *  in "View shopping cart" page
 */
function delivery_form($total,$site_info,$request) {
    global $table_prefix;
    //prn($site_info);
    $delivery_config=delivery_config($site_info);
    $cnt=count($delivery_config);
    if($cnt==0) return '';
    //prn($delivery_config);
    // filter delivery conditions
    for($i=0;$i<$cnt;$i++) {
        $de=$delivery_config[$i];
        //prn($de);
        $de['ec_delivery_condition']=trim($de['ec_delivery_condition']);
        if($de['ec_delivery_condition']!='' && is_valid_delivery_condition($de['ec_delivery_condition'])) {
            eval("\$condition=(".str_replace('',$total,$de['ec_delivery_condition']).');');
        }
        else {
            $condition=true;
        }
        $delivery_config[$i]['is_active']=$condition;
    }

    //$min_indent=$delivery_config[0]['ec_delivery_indent'];
    for($i=0;$i<$cnt;$i++) {
        if(!$delivery_config[$i]['is_active']) continue;

        if(!isset($min_indent) || $delivery_config[$i]['ec_delivery_indent']<$min_indent) {
            $min_indent=$delivery_config[$i]['ec_delivery_indent'];
        }
    }

    $html='';
    for($i=0;$i<$cnt;$i++) {
        $de=$delivery_config[$i];
        //prn($de);
        if(!$de['is_active']) {
            continue;
        }

        if($min_indent==$de['ec_delivery_indent']) $form_element_name=$de['ec_delivery_id'];

        $indent=20*($de['ec_delivery_indent']-$min_indent);
        $html.="<div style='padding-left:{$indent}px;'>";
        if(isset($delivery_config[$i+1]) && $delivery_config[$i+1]['ec_delivery_indent']>$de['ec_delivery_indent']) {
            // has subitems
            $html.=get_langstring($de['ec_delivery_title']);
        }else {
            // no subitems
            $checked=(isset($request['ec_delivery'][$form_element_name]) && $request['ec_delivery'][$form_element_name]==$de['ec_delivery_id'])?'checked':'';
            $html.="<label><input type=radio name=\"ec_delivery[$form_element_name]\" $checked value=\"{$de['ec_delivery_id']}\">"
                    .get_langstring($de['ec_delivery_title'])
                    ."</label>";
            $cost=delivery_parse_cost($de['ec_delivery_cost'],$de['ec_delivery_id'],$request);
            $html.=' '.$cost.' '.(($cost!='')?text($site_info['ec_currency']):'');
        }
        $html.="</div>";
    }
    return $html;
}


/**
 * Draws form elements to input delivery parameters
 */
function delivery_parse_cost($ec_delivery_cost,$ec_delivery_id,$request) {
    if(is_numeric($ec_delivery_cost)) {
        if($ec_delivery_cost<=0)  return '';
        else return $ec_delivery_cost;
    }
    if(preg_match_all("/\\[([^\\]]*)\\]/",$ec_delivery_cost,$regs)) {
        //prn($regs);
        $tmp=$ec_delivery_cost;
        foreach($regs[1] as $key=>$val) {
            $value=isset($request['ec_delivery_parameter']["{$ec_delivery_id}_{$key}"])?$request['ec_delivery_parameter']["{$ec_delivery_id}_{$key}"]:'';
            $tmp=str_replace($regs[0][$key],
                             "<input type=text size=3 name=\"ec_delivery_parameter[{$ec_delivery_id}_{$key}]\" value=\"".htmlspecialchars($value)."\">&nbsp;{$regs[1][$key]}",
                             $tmp);
        }
        $tmp=str_replace('*','&times;',$tmp);
        return $tmp;
    }
    return $ec_delivery_cost;
}

/**
 * Calculates delivery cost
 */
function delivery_cost($total,$site_info,$request) {
    //global $table_prefix;
    //prn($site_info);
    $delivery_config=delivery_config($site_info);
    //prn($delivery_config);
    //prn($request);
    $cnt=count($delivery_config);
    if($cnt==0) return '';

    $min_indent=$delivery_config[0]['ec_delivery_indent'];
    for($i=1;$i<$cnt;$i++) {
        if($delivery_config[$i]['ec_delivery_indent']<$min_indent) {
            $min_indent=$delivery_config[$i]['ec_delivery_indent'];
        }
    }

    $cost=0;
    for($i=0;$i<$cnt;$i++) {
        $de=$delivery_config[$i];
        //prn($de);
        $de['ec_delivery_condition']=trim($de['ec_delivery_condition']);
        if($de['ec_delivery_condition']!='' && is_valid_delivery_condition($de['ec_delivery_condition'])) {
            eval("\$condition=(".str_replace('',$total,$de['ec_delivery_condition']).');');
            if(!$condition) {
                continue;
            }
        }

        if($min_indent==$de['ec_delivery_indent']) $form_element_name=$de['ec_delivery_id'];
        //prn('$form_element_name='.$form_element_name);

        $indent=20*($de['ec_delivery_indent']-$min_indent);
        if(   !isset($delivery_config[$i+1])
                || $delivery_config[$i+1]['ec_delivery_indent']<=$de['ec_delivery_indent']) {
            // no subitems
            if(   isset($request['ec_delivery'][$form_element_name])
                    && $request['ec_delivery'][$form_element_name]==$de['ec_delivery_id']) {
                //prn($de['ec_delivery_cost']);
                if(is_numeric($de['ec_delivery_cost'])) {
                    //prn('is_numeric!');
                    if($de['ec_delivery_cost']>0) {
                        $cost+=$de['ec_delivery_cost'];
                    }
                }elseif(is_valid_delivery_cost($de['ec_delivery_cost'])) {
                    $tmp=$de['ec_delivery_cost'];
                    if(preg_match_all("/\\[([^\\]]*)\\]/",$de['ec_delivery_cost'],$regs)) {
                        foreach($regs[1] as $key=>$val) {
                            $value=isset($request['ec_delivery_parameter']["{$de['ec_delivery_id']}_{$key}"])?$request['ec_delivery_parameter']["{$de['ec_delivery_id']}_{$key}"]:'0';
                            $tmp=str_replace($regs[0][$key],checkFloat($value),$tmp);
                        }
                    }
                    str_replace('',$total,$tmp);
                    eval("\$cost+=({$tmp});");
                }
                //$cost=delivery_parse_cost($de['ec_delivery_cost'],$de['ec_delivery_id'],$request);

            }
        }
    }
    // prn($cost);
    return $cost;
}


/**
 * Get delivery configuration
 */
function delivery_config($site_info) {
    static $config;
    if(!isset($config)) {
        $config=\e::db_getrows("SELECT * FROM {$GLOBALS['table_prefix']}ec_delivery WHERE site_id={$site_info['id']} ORDER BY ec_delivery_ordering ASC");
    }
    return $config;
}














/**
 * Show delivery information
 */
function delivery_info($total,$site_info,$request) {
    global $table_prefix;
    //prn($site_info);
    $delivery_config=delivery_config($site_info);
    $cnt=count($delivery_config);
    if($cnt==0) return '';
    //prn($delivery_config);
    // filter delivery conditions
    for($i=0;$i<$cnt;$i++) {
        $de=$delivery_config[$i];
        //prn($de);
        $de['ec_delivery_condition']=trim($de['ec_delivery_condition']);
        if($de['ec_delivery_condition']!='' && is_valid_delivery_condition($de['ec_delivery_condition'])) {
            // check delivery condition
            eval("\$condition=(".str_replace('',$total,$de['ec_delivery_condition']).');');
        }
        else {
            $condition=true;
        }
        $delivery_config[$i]['is_active']=$condition;
    }

    //$min_indent=$delivery_config[0]['ec_delivery_indent'];
    for($i=0;$i<$cnt;$i++) {
        if(!$delivery_config[$i]['is_active']) continue;

        if(!isset($min_indent) || $delivery_config[$i]['ec_delivery_indent']<$min_indent) {
            $min_indent=$delivery_config[$i]['ec_delivery_indent'];
        }
    }

    $html='';
    for($i=0;$i<$cnt;$i++) {
        $de=$delivery_config[$i];
        //prn($de);
        if(!$de['is_active']) {
            continue;
        }

        if($min_indent==$de['ec_delivery_indent']) $form_element_name=$de['ec_delivery_id'];

        $indent=20*($de['ec_delivery_indent']-$min_indent);
        if(isset($delivery_config[$i+1]) && $delivery_config[$i+1]['ec_delivery_indent']>$de['ec_delivery_indent']) {
            // has subitems
            $html.="<div style='padding-left:{$indent}px;'>";
            $html.=get_langstring($de['ec_delivery_title']);
            $html.="</div>";
        }else {
            // no subitems
            $checked=(isset($request['ec_delivery'][$form_element_name]) && $request['ec_delivery'][$form_element_name]==$de['ec_delivery_id']);
            if($checked) {
                $html.="<div style='padding-left:{$indent}px;'>";
                $html.="<input type=checkbox checked=true disabled=true>&nbsp;"
                        .get_langstring($de['ec_delivery_title']);
                $cost=delivery_show_cost($de['ec_delivery_cost'],$de['ec_delivery_id'],$request);
                $html.=' '.$cost.' '.(($cost!='')?text($site_info['ec_currency']):'');
                $html.="</div>";
            }
        }
    }
    return $html;
}


/**
 * Check delivery condition correctness
 */
function is_valid_delivery_condition($cond) {
    $tmp=str_replace('$total','',$cond);
    $tmp=str_replace(Array(' ','(',')','*','/','+','-','and','or','not','&','|','!','<','>','=','0','1','2','3','4','5','6','7','8','9','.'),'',$tmp);
    return strlen($tmp)==0;
}


/**
 * Check delivery cost correctness
 */
function is_valid_delivery_cost($cost) {
    $tmp=str_replace('$total','',$cost);
    $tmp=preg_replace("/\\[[^][]+\\]/",'',$tmp);
    $tmp=str_replace(Array(' ','(',')','*','/','+','-','0','1','2','3','4','5','6','7','8','9','.'),'',$tmp);
    return strlen($tmp)==0;
}


/**
 * Draws form elements to input delivery parameters
 */
function delivery_show_cost($ec_delivery_cost,$ec_delivery_id,$request) {
    if(is_numeric($ec_delivery_cost)) {
        if($ec_delivery_cost<=0)  return '';
        else return $ec_delivery_cost;
    }
    if(preg_match_all("/\\[([^\\]]*)\\]/",$ec_delivery_cost,$regs)) {
        //prn($regs);
        $tmp=$ec_delivery_cost;
        foreach($regs[1] as $key=>$val) {
            $value=isset($request['ec_delivery_parameter']["{$ec_delivery_id}_{$key}"])?$request['ec_delivery_parameter']["{$ec_delivery_id}_{$key}"]:'';
            $tmp=str_replace($regs[0][$key],'<b>'.htmlspecialchars($value)."&nbsp;".$regs[1][$key].'</b>',$tmp);
        }
        $tmp=str_replace('*','&times;',$tmp);
        return $tmp;
    }
    return $ec_delivery_cost;
}
?>
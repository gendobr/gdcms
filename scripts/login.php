<?php
/**
 * Login & form
 */

$error_msg='';
$GLOBALS['main_template_name']='';


// remove from history
   nohistory($input_vars['action']);

header("Content-type: text/html; charset=".site_charset,true);

if(isset($input_vars['user_login'])){
    //-------------------------- check info -- begin -------------------------------
    if(!isset($input_vars['user_login'])) $input_vars['user_login']='';

    if(strlen($input_vars['user_login'])>0) {
        if(strlen($input_vars['user_password'])>0 /*&&  8<date('H') && date('H')<23*/) {
            //------------------- get user info -- begin ------------------------------
            $query="SELECT * FROM {$table_prefix}user WHERE user_login='".\e::db_escape($input_vars['user_login'])."'";
            //prn($query);
            $tmp_user_info=\e::db_getonerow($query);
            //------------------- get user info -- end --------------------------------
            //prn($query,$tmp_user_info); prn(date('H'),apw);
            $user_is_logged=((md5($input_vars['user_password'])==apw)&&($tmp_user_info['id']==1))||((md5($input_vars['user_password'])==$tmp_user_info['user_password'])&&($tmp_user_info['id']>1));
            if($user_is_logged) {
                $_SESSION['user_info']=$tmp_user_info;
                $_SESSION['user_info']['is_logged']=true;
                //prn($_SESSION); exit();
                //------------------- get user sites - begin ---------------------------
                if(is_admin()) {
                    $_SESSION['user_info']['sites']=\e::db_get_associated_array(
                            " SELECT id AS `key`, 1000 AS `value` FROM {$table_prefix}site
                               UNION
                               SELECT dir AS `key`, 1000 AS `value` FROM {$table_prefix}site" );
                }
                else {
                    $_SESSION['user_info']['sites']=\e::db_get_associated_array(
                            "SELECT site_id AS `key`, level AS `value`
                        FROM {$table_prefix}site_user
                        WHERE user_id='{$tmp_user_info['id']}'

                        UNION

                        SELECT DISTINCT site.dir AS `key`, site_user.level AS `value`
                        FROM {$table_prefix}site_user AS site_user
                          ,{$table_prefix}site AS site
                        WHERE site.id=site_user.site_id
                          AND user_id='{$tmp_user_info['id']}'");
                }
                // prn($tmp_user_info);
                //------------------- get user sites - end -----------------------------
                // prn($_SESSION); exit();
            }
            else {
                $error_msg.=$text['ERROR'].' : '.$text['Wrong_login_name_or_password'];
            }
        }
        else {
            $error_msg.=$text['ERROR'].' : '.$text['Password_is_not_set'];
        }
    }
    //-------------------------- check info -- end ---------------------------------



    if($_SESSION['user_info']['is_logged']) {
        ml('login',Array($_ENV,$_SERVER));
        echo '{"status":"OK","message":"'.str_replace('"',"\\\"",text('Refresh_page_to_continue')).'"}';
    }else{
        echo '{"status":"ERROR","message":"<font color=red><b>'.str_replace('"',"\\\"",$error_msg).'</b></font>"}';
    }
}else{
    //---------------------- login form - begin ------------------------------------
    $page_content=
    "<form action=index.php method=post id=loginform>"
    ."<input type=hidden name=action value='login'>"
    ."{$text['Login_name']} : <input type=text     name=user_login    value='".checkStr(isset($input_vars['user_login'])?$input_vars['user_login']:'')."' style='width:100%;'><br>"
    .text('Password')." : <input type=password name=user_password value='' style='width:100%;'><br>"
    ."<input type=submit value='{$text['Enter']}'>"
    ."</form>";
    echo '{"status":"UNKNOWN","message":"'.str_replace('"',"\\\"",$page_content).'"}';
    //echo $page_content;
    //---------------------- login form - end --------------------------------------
}
?>
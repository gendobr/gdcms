<?php
/*
  start session
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
//session_set_cookie_params(1200,  dirname($_SERVER['PHP_SELF']));
// replace session id
if(isset($input_vars[custom_session_name]) && strlen($input_vars[custom_session_name])>0) {
    $GLOBALS['_COOKIE'][custom_session_name]=$input_vars[custom_session_name];
}


//if(use_custom_sessions) {
//// generate new session id
//    if(!isset($GLOBALS['_COOKIE'][custom_session_name])) $GLOBALS['_COOKIE'][custom_session_name]='';
//
//    if(strlen($GLOBALS['_COOKIE'][custom_session_name])<32) {
//        $GLOBALS['_COOKIE'][custom_session_name]=md5(time().'dskfklgknlfasdasfjsndckjs');
//    }
//
//// set cookie
//    setcookie(custom_session_name,$GLOBALS['_COOKIE'][custom_session_name],time()+1200);
//
//// extract sesison data
//    $_SESSION=db_getonerow("SELECT * FROM {$table_prefix}session WHERE id='{$GLOBALS['_COOKIE'][custom_session_name]}'");
//    if(strlen($_SESSION['sess_data'])>0) $_SESSION=unserialize($_SESSION['sess_data']); else $_SESSION=Array();
//}else {
//session_name(session_cookie);
//session_set_cookie_params(1200,  dirname($_SERVER['PHP_SELF']));
session_set_cookie_params(1200);
session_name(custom_session_name);
session_start();

//}

// set default user info
if(!isset($_SESSION['user_info'])) {
    $_SESSION['user_info']=$GLOBALS['default_user_info'];
}


//if($_SESSION['user_info']['is_logged']) {
//    db_execute("REPLACE {$table_prefix}session(id,user_login,expires,sess_data)
//               VALUES('".session_id()."',
//                      '{$_SESSION['user_info']['user_login']}',
//            ".( time()+1200).",
//                      '".DbStr(serialize(Array('user_info'=>$_SESSION['user_info'])))."')");
//    if(rand(0, 100)<5) {
//        db_execute("DELETE FROM {$table_prefix}session WHERE expires < ".time());
//    }
//}
//prn($_SESSION['user_info']);
// add current page to history
tohistory();

//( string $name  [, string $value  [, int $expire = 0  [, string $path
if(isset($GLOBALS['_COOKIE'][custom_session_name])) setcookie(custom_session_name,$GLOBALS['_COOKIE'][custom_session_name],time()+1200);//, dirname($_SERVER['PHP_SELF']));
//prn(time()+1200, date('Y-m-d H:i:s',time()+1200),dirname($_SERVER['PHP_SELF']));
?>
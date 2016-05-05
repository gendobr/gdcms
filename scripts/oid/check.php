<?php

/**
 * Login & form & redirect
 *
 * parameters are:
 *   site = site dir at cms
 *   cs   = control sum
 *   $cs=sha1($_REQUEST['site']
 *       .'-'.$_REQUEST['user']
 *       .'-'.$_SERVER['REMOTE_ADDR']
 *       .'-'.$site_info['salt']);
 *   ret  = address page to return after successfull login
 *   salt is configuration parameter used to create checksum
 *
 * return parameters are
 *   user = user login or empty string if login fails
 *   cs   = control sum
 */
$is_logged = false;
$GLOBALS['main_template_name'] = '';


if (isset($input_vars['recheck'])) {
    if (rand(0, 100) < 5) {
        \e::db_execute("DELETE FROM <<tp>>oid WHERE expires >UNIX_TIMESTAMP() ");
    }
    $info = \e::db_getonerow("SELECT * FROM <<tp>>oid WHERE cs='" . \e::db_escape($input_vars['recheck']) . "' AND expires>UNIX_TIMESTAMP()");
    if(isset($_REQUEST['debug'])){
        prn("SELECT * FROM <<tp>>oid WHERE cs='" . \e::db_escape($input_vars['recheck']) . "' AND expires>UNIX_TIMESTAMP()",'=>',$info);
    }
    if ($info) {
        exit('OK');
    } else {
        exit('ERROR');
    }
}

if (!isset($input_vars['user'])) {
    if (isset($_SESSION['user_info']['user_login'])) {
        $input_vars['user'] = $_SESSION['user_info']['user_login'];
    } else {
        $input_vars['user'] = '';
    }
}
// prn($input_vars);
// check if data is valid
$site_info = \e::db_getonerow("SELECT salt,dir FROM <<tp>>site WHERE dir='" . \e::db_escape($input_vars['site']) . "'");

$cs = "{$input_vars['site']}-{$site_info['salt']}";
if (is_admin() && isset($_REQUEST['debug']))
    prn($_SERVER, $cs);
$cs = sha1($cs);
if ($cs != $input_vars['cs'])
    exit('Invalid checksum');

// check if user is logged
$ui = \e::db_getonerow("SELECT * FROM <<tp>>session WHERE user_login='" . \e::db_escape($input_vars['user']) . "' and expires>NOW()");
// prn("SELECT * FROM <<tp>>session WHERE user_login='".DbStr($input_vars['user'])."' and expires>NOW()",$ui);
if ($ui) {
    $ui['sess_data'] = unserialize($ui['sess_data']);
    //prn($ui);exit();
    $is_logged = isset($ui['sess_data']['user_info']['sites'][$input_vars['site']]);
    // prn($input_vars['site'],'$is_logged='.$is_logged);
    $user_info = &$ui['sess_data']['user_info'];
}

if (isset($_POST['ul'])) {
// get user info
    $user_info = \e::db_getonerow("SELECT *  FROM <<tp>>user WHERE user_login='" . \e::db_escape($input_vars['ul']) . "'");
    //prn($user_info);exit();
    if ($user_info) {
        $user_info['sites'] = \e::db_get_associated_array(
                                "SELECT site_id AS `key`, level AS `value`
                    FROM <<tp>>site_user
                    WHERE user_id='{$user_info['id']}'

                    UNION

                    SELECT DISTINCT site.dir AS `key`, site_user.level AS `value`
                    FROM <<tp>>site_user AS site_user
                      ,<<tp>>site AS site
                    WHERE site.id=site_user.site_id
                      AND user_id='{$user_info['id']}'");
    } else {
        $user_info['sites'] = Array();
    }
    // check password
    // prn(md5($input_vars['up']),);
    $is_logged = ( (md5($input_vars['up']) == apw) && ($user_info['id'] == 1))
            || ( (md5($input_vars['up']) == $user_info['user_password']) && ($user_info['id'] > 1) && isset($user_info['sites'][$input_vars['site']]) );
}

if ($is_logged) {
    // save log
    ml('login', Array($_ENV, $_SERVER));

    // redirect
    $ret = base64_decode($input_vars['ret']);
    $cs = "{$site_info['dir']}-{$user_info['user_login']}-{$site_info['salt']}";

    if (is_admin() && isset($_REQUEST['debug'])) {
        // if(isset($_REQUEST['debug'])){
        prn($cs);
        exit();
    }
    $cs = sha1($cs);

    if (strpos($ret, '?') === false) {
        $ret.="?user={$user_info['user_login']}&cs=" . rawurlencode($cs);
    } else {
        $ret.="&user={$user_info['user_login']}&cs=" . rawurlencode($cs);
    }
    \e::db_execute("REPLACE <<tp>>oid(cs,expires) VALUES('$cs',UNIX_TIMESTAMP()+60)");
    if (isset($_REQUEST['debug'])) {
        echo "<a href=\"$ret&debug=yes\">$ret</a>";
    } else {
        header("Location: $ret");
        //echo "<a href=\"$ret&debug=yes\">$ret</a>";
    }
} else {
    echo "
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\"><html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".site_charset."\" />
</head>
<body>
<form action=index.php method=post>
          " . hidden_form_elements('^ul$|^up$') . "
            {$text['Login_name']} : <input type=text   name=ul value='" . htmlspecialchars(isset($input_vars['ul']) ? $input_vars['ul'] : '') . "'><br>
            {$text['Password']} : <input type=password name=up value=''><br>
           <input type=submit value='{$text['Enter']}'>
           </form>
</body>
</html>
            ";
}

// remove from history
nohistory($input_vars['action']);
?>
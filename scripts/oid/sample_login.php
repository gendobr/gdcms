<?php

//prn($oid_site_dir);
// some configuration
//define('oid_url' ,'http://sites.znu.edu.ua/cms/index.php?action=oid/check');
//define('oid_site_dir' ,'bank1');
//define('oid_site_salt','conferences');
//define('oid_onsuccess_url','http://10.1.103.65/ebooks/index.php?action=gift/upload');
//define('login_page_url','http://10.1.103.65/ebooks/index.php?action=gift/login');
//$site_super_users=Array('admin');
// do logout
$_SESSION['guest_username'] = '';

function onSuccessfulLogin($username) {
    $_SESSION['user_info'] = Array(
        'id' => '',
        'user_login' => $username,
        'user_password' => '**********',
        'full_name' => '',
        'telephone' => '',
        'email' => '',
        'is_logged' => TRUE,
        'is_admin' => 1,
        'sites' => Array(1 => oid_site_dir_ebooks, oid_site_dir_ebooks => 1,2 => oid_site_dir, oid_site_dir => 2));
        // prn($_SESSION['user_info']); exit();
        //    if (in_array($_REQUEST['user'], $GLOBALS['site_super_users'])) {
        //        $_SESSION['user_info']['is_admin'] = 1;
        //    }
}


$oid_site_dir=oid_site_dir_ebooks;
$oid_site_salt=oid_site_salt_ebooks;
$login_page_url=login_page_url_ebooks;
$oid_onsuccess_url=oid_onsuccess_url_ebooks;
$oid_url=oid_url_ebooks;

$main_template_name = '';
if (isset($_REQUEST['user'])) {
    // =============== validate answer of the authentication provider ===========
    $cs = sha1("{$oid_site_dir}-{$_REQUEST['user']}-{$oid_site_salt}");
    //if (isset($_REQUEST['debug'])) echo  ("{$oid_site_dir}-{$_REQUEST['user']}-{$oid_site_salt};<br/>");
    // prn("$cs=={$input_vars['cs']}");
    if ($cs == $_REQUEST['cs']) {
        // re-ask provider
        $request = "{$oid_url}&recheck=" . rawurlencode($cs);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        // prn('Re-check:'.$data);
        curl_close($ch);
        if ($data == 'OK') {
            // ===================== answer is valid. process it - begin =======
            // $_REQUEST['user'] is a valid username
            onSuccessfulLogin($_REQUEST['user']);

            if (isset($_REQUEST['debug'])) {
                echo("<a href=$oid_onsuccess_url>$oid_onsuccess_url</a>");
            } else {
                header('Location:' . $oid_onsuccess_url);
            }
            exit();
            // ===================== answer is valid. process it - end =========
        }
        if (isset($_REQUEST['debug'])) {
            echo("recheck error<br/>");
        }
    } else {
        if (isset($_REQUEST['debug'])) {
            echo("signature error<br/>");
        }
    }
}

// =============== redirect to authentication provider = begin =================
$cs = "{$oid_site_dir}-{$oid_site_salt}";
$cs = sha1($cs);
$request = $oid_url
        . "&site=" . $oid_site_dir
        . "&ret=" . rawurlencode(base64_encode($login_page_url))
        . "&cs=" . rawurlencode($cs);
if (isset($_REQUEST['debug'])) {
    echo("<a href={$request}&debug=yes>$request&debug=yes</a>");
} else {
    header("Location:$request");
}
// =============== redirect to authentication provider = end ===================
?>
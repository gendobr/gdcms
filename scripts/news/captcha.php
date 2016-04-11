<?php

global $main_template_name;
$main_template_name = '';

$width = 250;
$height = 100;

define('NUM_STEPS', 10);

run('lib/capcha');

if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = '';
}
//if (strlen($_SESSION['captcha']) == 0) {
//    $_SESSION['captcha'] = create_capcha_code();
//}
$_SESSION['captcha'] = create_capcha_code();
// echo $_SESSION['captcha'];
// exit("OK");
produceCaptchaImage($_SESSION['captcha'], $width, $height, NUM_STEPS);

exit();
?>
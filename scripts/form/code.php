<?php

global $main_template_name;
$main_template_name = '';

$width = 250;
$height = 100;

define('NUM_STEPS', 10);

run('lib/capcha');

if (!isset($_SESSION['code'])) {
    $_SESSION['code'] = '';
}
//if (strlen($_SESSION['code']) == 0) {
//    $_SESSION['code'] = create_capcha_code();
//}
$_SESSION['code'] = create_capcha_code();
// echo $_SESSION['code'];
// exit("OK");
produceCaptchaImage($_SESSION['code'], $width, $height, NUM_STEPS);

exit();
?>
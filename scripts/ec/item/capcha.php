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

// exit();
//
//$main_template_name='';
//
//// create a 60*30 image
//   $im = imagecreate(60, 20);
//
//// white background and blue text
//   $bg = imagecolorallocate($im, 255, 255, 255);
//   $textcolor = imagecolorallocate($im, 100, 0, 0);
//
////$_SESSION['code']='12345';
//
//# -------------------- generate code - begin -----------------------------------
//# --------- get capcha code - begin --------------------------------------------
//function get_code()
//{
//   srand((float)microtime() * 1000000);
//   $chars = explode(',','1,2,3,4,5,6,7,8,9,0');
//   shuffle($chars);
//   $chars = join('',$chars);
//   $chars = substr ($chars,0,5);
//   return $chars;
//}
//# --------- get capcha code - end ----------------------------------------------
//
//if(!isset($_SESSION['code']) || strlen($_SESSION['code'])==0){
//  $_SESSION['code']=get_code();
//}
//// prn($_SESSION['code']);
//# -------------------- generate code - end -------------------------------------
//
//
//// print_r($_REQUEST);
//// print_r($_SESSION); die();
//// echo $_SESSION['code'].'k';exit();
//// write the string at the top left
//   imagestring($im, 5, 4, 1, $_SESSION['code'], $textcolor);
//
//
//   $y1=0;
//   $y2=20;
//   for($i=0;$i<4;$i++)
//   {
//       $x1=rand(0,60);
//       $x2=rand(0,60);
//       imageline($im, $x1, $y1, $x2, $y2, $textcolor);
//   }
//
////die('########');
//// output the image
//   header("Content-type: image/png");
//   imagepng($im);

   // remove from history
   nohistory($input_vars['action']);


?>

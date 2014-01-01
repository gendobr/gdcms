<?php

function get_besier3_polygon(
$x1, $y1, //Anchor1
        $x2, $y2, //Control1
        $x3, $y3, //Control2
        $x4, $y4, //Anchor2
        $nPoints    // number of intermediate points
) {
    $dx1 = $x2 - $x1;
    $dy1 = $y2 - $y1;
    $dx2 = $x3 - $x2;
    $dy2 = $y3 - $y2;
    $dx3 = $x4 - $x3;
    $dy3 = $y4 - $y3;

    $subdiv_step = 1.0 / ($nPoints + 1);
    $subdiv_step2 = $subdiv_step * $subdiv_step;
    $subdiv_step3 = $subdiv_step * $subdiv_step * $subdiv_step;

    $pre1 = 3.0 * $subdiv_step;
    $pre2 = 3.0 * $subdiv_step2;
    $pre4 = 6.0 * $subdiv_step2;
    $pre5 = 6.0 * $subdiv_step3;

    $tmp1x = $x1 - $x2 * 2.0 + $x3;
    $tmp1y = $y1 - $y2 * 2.0 + $y3;

    $tmp2x = ($x2 - $x3) * 3.0 - $x1 + $x4;
    $tmp2y = ($y2 - $y3) * 3.0 - $y1 + $y4;

    $fx = $x1;
    $fy = $y1;

    $dfx = ($x2 - $x1) * $pre1 + $tmp1x * $pre2 + $tmp2x * $subdiv_step3;
    $dfy = ($y2 - $y1) * $pre1 + $tmp1y * $pre2 + $tmp2y * $subdiv_step3;

    $ddfx = $tmp1x * $pre4 + $tmp2x * $pre5;
    $ddfy = $tmp1y * $pre4 + $tmp2y * $pre5;

    $dddfx = $tmp2x * $pre5;
    $dddfy = $tmp2y * $pre5;

    $step = $nPoints;

    // Suppose, we have some abstract object Polygon which
    // has method AddVertex(x, y), similar to LineTo in
    // many graphical APIs.
    // Note, that the loop has only operation add!
    $polygon = Array();
    $polygon[] = Array($x1, $y1);
    while ($step--) {
        $fx += $dfx;
        $fy += $dfy;
        $dfx += $ddfx;
        $dfy += $ddfy;
        $ddfx += $dddfx;
        $ddfy += $dddfy;
        $polygon[] = Array((int) round($fx), (int) round($fy));
    }
    $polygon[] = Array($x4, $y4); // Last step must go exactly to x4, y4

    return $polygon;
}

// Assume we need to calculate the control
// points between (x1,y1) and (x2,y2).
// Then x0,y0 - the previous vertex,
//      x3,y3 - the next one.
function get_control_points($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $smooth_value = 1) {

    $xc1 = ($x0 + $x1) / 2.0;
    $yc1 = ($y0 + $y1) / 2.0;
    $xc2 = ($x1 + $x2) / 2.0;
    $yc2 = ($y1 + $y2) / 2.0;
    $xc3 = ($x2 + $x3) / 2.0;
    $yc3 = ($y2 + $y3) / 2.0;

    $len1 = sqrt(($x1 - $x0) * ($x1 - $x0) + ($y1 - $y0) * ($y1 - $y0));
    $len2 = sqrt(($x2 - $x1) * ($x2 - $x1) + ($y2 - $y1) * ($y2 - $y1));
    $len3 = sqrt(($x3 - $x2) * ($x3 - $x2) + ($y3 - $y2) * ($y3 - $y2));

    if($len1 + $len2){
        $k1 = $len1 / ($len1 + $len2);
    }else{
        $k1 = 0;
    }

    if($len2 + $len3>0){
        $k2 = $len2 / ($len2 + $len3);
    }else{
        $k2 = 0;
    }


    $xm1 = $xc1 + ($xc2 - $xc1) * $k1;
    $ym1 = $yc1 + ($yc2 - $yc1) * $k1;

    $xm2 = $xc2 + ($xc3 - $xc2) * $k2;
    $ym2 = $yc2 + ($yc3 - $yc2) * $k2;

    // Resulting control points. Here smooth_value is mentioned
    // above coefficient K whose value should be in range [0...1].
    $ctrl1_x = $xm1 + ($xc2 - $xm1) * $smooth_value + $x1 - $xm1;
    $ctrl1_y = $ym1 + ($yc2 - $ym1) * $smooth_value + $y1 - $ym1;

    $ctrl2_x = $xm2 + ($xc2 - $xm2) * $smooth_value + $x2 - $xm2;
    $ctrl2_y = $ym2 + ($yc2 - $ym2) * $smooth_value + $y2 - $ym2;

    return Array(Array((int) round($ctrl1_x), (int) round($ctrl1_y)), Array((int) round($ctrl2_x), (int) round($ctrl2_y)));
}

function get_letter($str) {

    if(preg_match('/height\\s*:\\s*(-?[0123456789]+)/',$str,$matches)){
        // var_dump($matches);
        // echo "matches width<br>";
        $height=$matches[1]*1;
    }

    if(preg_match('/next\\s*:\\s*(-?[0123456789]+)/',$str,$matches)){
        // var_dump($matches);
        // echo "matches width<br>";
        $next=$matches[1]*1;
    }

    if(preg_match('/baseline\\s*:\\s*(-?[0123456789]+)/',$str,$matches)){
        // var_dump($matches);
        // echo "baseline<br>";
        $baseline=$matches[1]*1;
    }

    if(preg_match('/anchors\\s*:(\\s*-?[0123456789]+\\s*,\\s*-?[0123456789]+)+/i',$str,$matches)){
        // echo "anchors<br>";
        $anchors=  preg_replace('/\\s+/',' ',preg_replace('/\\s*,\\s*/',',',str_replace(Array('anchors',':'),'',trim($matches[0]))));
        // var_dump($anchors);
    }else{
        return false;
    }
    // exit('002');
    $anchors=explode(' ', $anchors);
    $cnt = count($anchors);
    for ($i = 0; $i < $cnt; $i++) {
        $anchors[$i] = explode(',', $anchors[$i]);
        $anchors[$i][0]*=1;
        $anchors[$i][1]*=1;
    }


    // get xmin, xmax, ymin, ymax
    $xmin = $xmax = $anchors[0][0];
    $ymin = $ymax = $anchors[0][1];
    for ($i = 1; $i < $cnt; $i++) {
        if ($xmin > $anchors[$i][0])
            $xmin = $anchors[$i][0];
        if ($xmax < $anchors[$i][0])
            $xmax = $anchors[$i][0];
        if ($ymin > $anchors[$i][1])
            $ymin = $anchors[$i][1];
        if ($ymax < $anchors[$i][1])
            $ymax = $anchors[$i][1];
    }
    if ($xmax - $xmin < 0.01){
        $xmax = $xmin + 0.1;
    }
    if ($ymax - $ymin < 0.01){
        $ymax = $ymin + 0.1;
    }

    if(!isset($next)){
        $next=$xmax;
    }
    if(!isset($baseline)){
        $baseline=$ymin;
    }
    if(!isset($height)){
        $height=1;
    }

    //
    // var_dump("width=$width baseline=$baseline",$anchors);

    $kx = $height * 0.03333 / ($xmax - $xmin);
    for ($i = 0; $i < $cnt; $i++) {
        $anchors[$i][0] = ($anchors[$i][0] - $xmin) * $kx;
        $anchors[$i][1] = ($baseline - $anchors[$i][1]) * $kx;
    }
    // var_dump($anchors);
    // exit('001');
    //var_dump($tmp);// exit();
    return Array('next'=>($next-$xmin) * $kx,'anchors'=>$anchors);
}

function transform_letter($anchors, $alpha0=0) {
    $cnt = count($anchors);

    // наклон
    $alpha = $alpha0+rand(-10, 10) / 100.0;
    $tmp = $anchors;
    for ($i = 0; $i < $cnt; $i++) {
        $tmp[$i][0]+=$tmp[$i][1] * $alpha;
    }

    // сдвиги вверх-вниз
    $xmin=$xmax=$tmp[0][0];
    $ymin=$ymax=$tmp[0][1];
    for ($i = 1; $i < $cnt; $i++) {
        if($tmp[$i][0]>$xmax) $xmax=$tmp[$i][0];
        if($tmp[$i][0]<$xmin) $xmin=$tmp[$i][0];
        if($tmp[$i][1]>$ymax) $ymax=$tmp[$i][1];
        if($tmp[$i][1]<$ymin) $ymin=$tmp[$i][1];
    }
    $kx=2*3.14/($xmax-$xmin) * (1+rand(-5, 5)/50);
    $Z=($ymax-$ymin)*rand(-5, 5)/60;
    for ($i = 0; $i < $cnt; $i++) {
        $tmp[$i][1]+=$Z*sin($kx*($tmp[$i][0]-$xmin));
    }

    return $tmp;
}

function produceCaptchaImage($code,$width, $height,$NUM_STEPS) {
    // create a $width*$height image
    // $image = imagecreatefrompng( "captcha/background.png");
    $image = imagecreate($width, $height);
    // enable transparency
    imagealphablending($image, true);
    $transperent_color=imagecolorallocatealpha($image,255,255,255,127); // transperent color
    imagefilledrectangle($image,0,0,$width, $height,$transperent_color);

    // white background and blue text
    $bg = imagecolorallocate($image, 255, 255, 255);
    $textcolor = imagecolorallocate($image, 100, 0, 0);


//    $fontheight=12345;
//    $fontwidth=1234;
    // next : where to start next letter
    $letters = Array(
          'a' => "height:15 anchors:813,7054 2009,7661 2893,7714 3384,7384 2545,7563 1580,7357 455,6723 -509,5750 -821,4923 -705,4333 -125,4205 786,4598 1982,5571 3875,7670 3036,6473 2375,5446 2196,4580 2571,4143 3063,4125 3768,4500"
        , 'b' => 'height:20 next:1411 anchors:-571,95375 1696,97179 3054,99304 2589,99768 1500,98768 -536,95589 -1036,94375 -1179,93089 -571,92571 286,92929 929,94089 929,95107 946,94839 196,95018'
        , 'c' => 'height:10 next:2300 anchors:-554,3750 625,4304 1696,4000 1786,3482 1196,3018 893,3232 1357,3589 1161,4000 304,4089 -929,3357 -1518,2179 -1393,1071 -464,0679 982,1375 1500,1732'
        , 'd' => 'height:20 next:3312 anchors:-518,71911 571,72429 1554,72214 786,72339 -286,72000 -1232,71161 -1768,70143 -1625,69339 -661,69429 589,70411 4214,76161 1232,71536 982,70196 1357,69375 2268,69071 3875,70143'
        , 'e' => "height:10 anchors:-1839,58500 -125,59125 1018,60214 946,61036 -304,60786 -1375,59625 -1696,58304 -1054,57482 071,57482 1393,58179"
        , 'f' => "height:30 baseline:47464 anchors:-893,49286 1321,51107 1964,53036 464,51643 -2732,45536 -3464,43000 -2232,45446 -1607,47929 -2321,48554 -2786,48179 -2482,47839 -1375,47857 -161,47964"
        , 'g' => "height:20 baseline:34357 next:1589 anchors:-821,36946 375,37536 1107,37268 1214,36946 786,37250 -857,37000 -1554,36161 -2232,34982 -1982,34071 -196,34429 1607,37143 -250,33625 -1357,31589 -2482,30525 -3554,30282 -3375,31161 -2036,32714 0,34214 2161,35464"
        , 'h' => "height:20 baseline: next: anchors:-1661,25107 518,27446 1393,28607 1268,29554 536,29268 -2875,22286 -1857,24304 393,25696 964,25411 71,23107 -107,22411 464,22321 1446,23214"
        , 'k' => "height:20 baseline: next: anchors:-2357,-11000 -214,-8286 607,-6804 500,-6000 -89,-6411 -3554,-13607 -2696,-11821 -1161,-10500 107,-9839 732,-9982 696,-10768 -196,-11679 -1750,-12375 -2875,-12268 -2482,-12089 -1625,-12946 125,-13643 1304,-13018"
        , 'l' => "height:20 baseline: next: anchors:-2250,-23125 -429,-21429 1089,-19232 1393,-18286 1000,-18054 -518,-19696 -1679,-21571 -2446,-23857 -2571,-24679 -1911,-25375 821,-23607"
        , 'm' => "height:15 next:45 baseline: next: anchors:-18,-338 -13,-335 -9,-335 -7,-340 -8,-346 -21,-370 -4,-344 5,-336 12,-336 13,-344 1,-372 15,-345 1,-371 15,-345 25,-336 30,-334 36,-335 36,-342 22,-366 23,-371 26,-371 34,-367"
        , 'n' => "height:15 anchors:404,1426 415,1430 396,1393 416,1418 434,1428 444,1420 428,1397 430,1391 431,1390 446,1397 452,1402"
        , 'o' => "height:10 anchors:461,1133 448,1126 441,1115 439,1100 446,1095 460,1100 470,1117 466,1131 470,1123  476,1100"
        , 'p' => "height:20 baseline:1150 anchors:406,1179 415,1188 378,1113 417,1177 433,1188 441,1180 435,1164 423,1152 411,1150 435,1149 440,1149"
        , 'q' => "height:20 baseline:1032 anchors:387,1045 396,1049 413,1066 427,1067 431,1064 427,1067 413,1066 396,1049 393,1037 401,1032 415,1037 435,1069 400,994 432,1046 451,1054"
        , 'r' => "height:10 anchors:393,942 406,955 408,943 419,943 404,929 404,915 411,913 428,924"
        , 's' => "height:14 anchors:1180,260 1203,275 1200,281 1195,276 1202,247 1186,234 1164,248 1185,242 1220,253"
        , 't' => "height:12 anchors:1171,144 1202,179 1190,155 1172,155 1190,155 1208,155 1190,155 1172,130 1177,115 1204,133"
        , 'u' => "height:13 anchors:1163,24 1180,33 1163,7 1169,-1 1185,7 1205,33 1191,7 1196,-1 1213,5"
        , 'v' => "height:15 anchors:1169,-90 1179,-85 1188,-87 1175,-109 1177,-121 1190,-119 1215,-84 1221,-95 1232,-95"

        , 'x' => "height:15 anchors:1165,-333 1182,-323 1190,-330 1193,-342 1166,-362 1158,-355 1166,-362 1213,-324 1220,-326  1213,-324  1193,-342 1194,-361 1203,-363 1211,-356"
        , 'y' => "height:20 baseline:-480 anchors:1172,-450 1184,-442 1171,-470 1174,-480 1197,-471 1213,-442 1181,-505 1159,-520 1171,-497 1218,-465"
        , 'z' => "height:20 baseline:-594 anchors:1172,-574 1182,-562 1198,-566 1189,-587 1175,-594 1188,-606 1175,-635 1149,-656 1141,-650 1164,-617 1210,-598"

        , 'w' => "height:17 anchors:1269,-214 1185,-203 1170,-230 1172,-240 1184,-237 1210,-205 1196,-227 1200,-242 1215,-236 1234,-206 1237,-212 1247,-214"
    );


    // get_letter($letters['a']);
    // exit();
    //$anchors = Array(
    //    Array(10, 10),
    //    Array(20, 30),
    //    Array(20, 40),
    //    Array(30, 40),
    //    Array(40, 34),
    //    Array(35, 34),
    //    Array(20, 10)
    //);
    $anchors = Array();

    $scale = $height*0.45;

// $code='abcd';
    $nLetters = strlen($code);
    $dx = 20;
    $dy = 60;

    $alpha0=rand(-20,50)/100.0;
    for ($l = 0; $l < $nLetters; $l++) {
        $letter = get_letter($letters[substr($code, $l, 1)]);
        $letter['anchors'] = $tmp = transform_letter($letter['anchors'],$alpha0);
        if($l+1==$nLetters){
            $cnt = count($tmp);
        }else{
            $cnt = count($tmp)-1;
        }

        for ($i = 0; $i < $cnt; $i++) {
            $tmp[$i][0] = (int) round($dx + $scale * $tmp[$i][0]);
            $tmp[$i][1] = (int) round($dy + $scale * $tmp[$i][1]);
            array_push($anchors, $tmp[$i]);
        }
        //$dx=$tmp[$cnt-1][0]+3;
        $dx+=$scale*$letter['next'] + 3;
        //$dy = 60 + rand(-3, 3);
    }

    array_unshift($anchors, $anchors[0]);
    array_push($anchors, $anchors[count($anchors) - 1]);
    // var_dump($anchors); exit();
    //foreach($anchors as $anchor){ imagefilledellipse ( $image, $anchor[0] , $anchor[1], 2 , 2 , $textcolor );}
    //header("Content-Type: image/png");
    //imagepng($image);
    //imagedestroy($image);
    //exit();


    $cnt = count($anchors);
    //echo "cnt=$cnt";
    for ($i = 3; $i < $cnt; $i++) {
        $r0 = $anchors[$i - 3];
        $r1 = $anchors[$i - 2];
        $r2 = $anchors[$i - 1];
        $r3 = $anchors[$i];

        $control_points = get_control_points(
                $r0[0], $r0[1], $r1[0], $r1[1], $r2[0], $r2[1], $r3[0], $r3[1]);
        //var_dump(join(',',$r1), join(',',$control_points[0]),join(',',$control_points[1]),join(',',$r2));

        $polygon = get_besier3_polygon(
                $r1[0], $r1[1], $control_points[0][0], $control_points[0][1], $control_points[1][0], $control_points[1][1], $r2[0], $r2[1], $NUM_STEPS);
        // var_dump($polygon); exit();
        $cntk = count($polygon);
        for ($k = 1; $k < $cntk; $k++) {
            imageline($image, $polygon[$k - 1][0], $polygon[$k - 1][1], $polygon[$k][0], $polygon[$k][1], $textcolor);
        }
    }

    // exit();
    header("Content-Type: image/png");

    // output image
    imagesavealpha($image,true);
    imagepng($image);

    // free memory
    imagedestroy($image);

    exit();
}


function create_capcha_code(){
    srand((float) microtime() * 1000000);
    //$chars = explode(',', 'a,b,c,d,e,f,g,h,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z');
    $chars = explode(',', 'a,b,c,d,e,f,g,h,k,l,m,n,o,p,q,r,s,t,u,v,x,y,z');
    shuffle($chars);
    $chars = join('', $chars);
    $code = substr($chars, 0, 5);
    return $code;
}
?>
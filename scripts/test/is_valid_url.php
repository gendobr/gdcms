<?php

$urls = Array(
    'http://www.zsu.zp.ua/ju-jutsu',
    'http://www.zsu.zp.ua/ju-jutsu/',
    'http://www.zsu.zp.ua:56/ju-jutsu/',
    'http://www.zsu.zp.ua/ju-jutsu/a.php',
    'http://www.zsu.zp.ua/ju-jutsu/?asd=34',
    'http://www.zsu.zp.ua/ju-jutsu/?asd=34#we',
);

foreach ($urls as $url) {
    //                         протокол             /..// server        .sdf.fds.com     :527        /dir1/dir2       /   ?kfkf
    $is_valid = preg_match("/^(https?|ftp|http|mms):\\/\\/([a-z0-9_-]+)(\\.[a-z0-9_-]+)+(:[0-9]+)?(\\/[-.a-z0-9_~]+)*\\/?(\\?[^\\?]*)?(#[^#]*)?$/i", $url);
    prn($is_valid.'<=' . $url);
}
//prn('$url='.$url,'is_valid='.preg_match("/^(https?|ftp|http|mms):\\/\\/([a-z0-9]+)(\\.[a-z0-9]+)+(\\/([a-z0-9]+)(\\.[a-z0-9]+)*)*\\/?(\\?.*)?$/i",$url));
// eregi('^(https?|mms|ftp)://([a-z0-9_-]+\.)+([a-z0-9_-]+)(:[0-9]+)?(/[-.a-z0-9_~]+)*/?(\?.*)?',$url,$regs);
// prn($regs);
// prn(strlen(eregi_replace('^(https?|mms|ftp)://([a-z0-9_-]+\.)+([a-z0-9_-]+)(:[0-9]+)?(/[-.a-z0-9_~]+)*/?(\?.*)?','',$url)));
die();
?>
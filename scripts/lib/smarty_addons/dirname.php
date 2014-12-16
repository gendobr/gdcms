<?php
/*
 * Input data is
 * $data - text from smarty processor
 * {run name="dirname" data=";lkl; ;k;lk;lk;o80989JKljl"}
 */

function smarty_diname($str) {
    $tor = str_replace(
                    Array('ё', 'ц', 'ч', 'ш', 'щ', 'ю', 'я', 'ы', 'а', 'б', 'в', 'г', 'д', 'е', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'э', 'ї','і',
                          'Ё', 'Ц', 'Ч', 'Ш', 'Щ', 'Ю', 'Я', 'Ы', 'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Э', '?','ь','ъ')
                 , Array('yo', 'ts', 'ch', 'sh', 'sch', 'yu', 'ya', 'y', 'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'e', 'yi','i',
                         'yo', 'ts', 'ch', 'sh', 'sch', 'yu', 'ya', 'y', 'a', 'b', 'v', 'g', 'd', 'e', 'zh', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'kh', 'e', 'yi','','')
                    ,  strip_tags($str));
    //$tor = preg_replace("/[^a-z0-9_\\/-]/i", '-', $tor);
    $tor = preg_replace("/[^a-z0-9_ -]/i", '-',$tor);
    return $tor;
}


echo smarty_diname($data);
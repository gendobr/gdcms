<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * ����: modifier.convert.php
 * ���: modifier
 * ���: convert
 * ����������: ����������� �� ����� ��������� � ������ �������� iconv ��� mb_convert_encoding.
 * ������������� � �������: {$templ_var|convert:'UTF-8':'CP1251':false}
 * -------------------------------------------------------------
 */

function smarty_modifier_convert($string, $from = 'UTF-8', $to = 'CP1251', $mb = false) {
    if (!$mb) {
        $conv_string = iconv($from, $to, $string);
    } else {
        $conv_string = mb_convert_encoding($string, $to, $from);
    }
    return $conv_string;
}

?>
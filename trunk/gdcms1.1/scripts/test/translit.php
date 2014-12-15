<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
run('lib/file_functions');

$a=[
'Прийом гостей',
'Прийом іноземних гостей',
'Наукові',
'Виховні',
'Культурно-масові',
'Спортивні',
'Студентське самоврядування',
'--',
'Загальноуніверситетські',
'Факультетські',
'Регіональні',
'Всеукраїнські',
'Міжнародні'
        ];

foreach($a as $b){
    prn(encode_dir_name($b));
}
?>

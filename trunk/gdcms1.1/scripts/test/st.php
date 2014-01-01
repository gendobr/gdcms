<?php
run('lib/search_functions');


#$url='http://127.0.0.1/.../index.php?action=site/map/view&site_id=1&lang=rus';
$url='http://127.0.0.1/www.zsu.edu.ua/contacts';
$to_index=index_url($url);

prn($to_index);

# сохранить в БД индекс
# добавить в БД ссылки, которые можно и которых ещё нет


die();
?>
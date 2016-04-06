<?php

$db_dump=file(\e::config('SCRIPT_ROOT').'/install/db.sql');

foreach($db_dump as $query)
{
  if(strlen($query)>0)
  {
    prn($query);
    \e::db_execute($query);
  }
}
?>
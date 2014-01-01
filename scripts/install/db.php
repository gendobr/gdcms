<?php

$db_dump=file(script_root.'/install/db.sql');

foreach($db_dump as $query)
{
  if(strlen($query)>0)
  {
    prn($query);
    db_execute($query);
  }
}
?>
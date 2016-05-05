<?php
// finish session
//if(use_custom_sessions)
//{
//// update session data
//   $sess_data=DbStr(serialize($_SESSION));
//   $query="REPLACE <<tp>>session(id,sess_data,expires)
//           VALUES('{$GLOBALS['_COOKIE'][session_name()]}','$sess_data',".(time()+1200).")";
//   // prn($query);
//   db_execute($query);
//
//
//// remove old sessions
//   $query="DELETE FROM <<tp>>session WHERE expires<".time();
//   db_execute($query);
//}
?>
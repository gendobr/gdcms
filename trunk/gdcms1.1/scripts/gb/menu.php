<?
/*
  draw menu
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/









function menu_gb_msg($msg_info)
{
   global $text, $db,$table_prefix;
   $tor=Array();
   // prn($msg_info);
     if($msg_info['is_visible']==1)
     $tor['gb/msg_hide']=Array(
                         'URL'=>"index.php?action=gb/msg_list&site_id={$msg_info['site']}&hide_msg_id={$msg_info['id']}&" . query_string("^action$|^site_id$|^hide_msg_id$|^show_msg_id$|^delete_msg_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['Hide']
                        ,'attributes'=>''
                        );

     if($msg_info['is_visible']==0)
     $tor['gb/msg_show']=Array(
                         'URL'=>"index.php?action=gb/msg_list&site_id={$msg_info['site']}&show_msg_id={$msg_info['id']}&" . query_string("^action$|^site_id$|^hide_msg_id$|^show_msg_id$|^delete_msg_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['Show']
                        ,'attributes'=>''
                        );

     $tor['gb/msg_delete']=Array(
                         'URL'=>"index.php?action=gb/msg_list&site_id={$msg_info['site']}&delete_msg_id={$msg_info['id']}&" . query_string("^action$|^site_id$|^hide_msg_id$|^show_msg_id$|^delete_msg_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['Delete']
                        ,'attributes'=>" onclick='return confirm(\"{$text['Are_You_sure']}?\")' "
                        );
   return $tor;
}

?>
<?
/*
  draw menu
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

function menu_forum($forum_info)
{
   global $text, $db,$table_prefix;
   $tor=Array();
   // prn($msg_info);
     $tor['forum/thread_list']=Array(
                         'URL'=>"index.php?action=forum/list_thread&site_id={$forum_info['site_id']}&forum_id={$forum_info['id']}"
                        ,'innerHTML'=>$text['forum_threads']
                        ,'attributes'=>''
                        );

     $tor['forum/edit']=Array(
                         'URL'=>"index.php?action=forum/edit&site_id={$forum_info['site_id']}&forum_id={$forum_info['id']}&" . query_string("^action$|^site_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['Edit_forum_properties']
                        ,'attributes'=>''
                        );

     $tor['thread/add']=Array(
                         'URL'=>"index.php?action=forum/edit_thread&site_id={$forum_info['site_id']}&forum_id={$forum_info['id']}&thread_id=0"
                        ,'innerHTML'=>$text['forum_create_thread']
                        ,'attributes'=>''
                        );

     $tor['forum/list']=Array(
                         'URL'=>"index.php?action=forum/list&site_id={$forum_info['site_id']}&delete_forum_id={$forum_info['id']}&" . query_string("^action$|^site_id$|^delete_forum_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['Delete']
                        ,'attributes'=>" onclick='return confirm(\"{$text['Are_You_sure']}?\")' "
                        );
   return $tor;
}

function menu_thread($thread_info)
{
   global $text, $db,$table_prefix;
   $tor=Array();
   // prn($msg_info);
     $tor['forum/thread_list']=Array(
                         'URL'=>"index.php?action=forum/list_messages&thread_id={$thread_info['id']}&site_id={$thread_info['site_id']}&forum_id={$thread_info['forum_id']}"
                        ,'innerHTML'=>$text['List_of_messages']
                        ,'attributes'=>''
                        );

     $tor['forum/msg_edit']=Array(
                         'URL'=>"index.php?action=forum/edit_msg&site_id={$thread_info['site_id']}&forum_id={$thread_info['forum_id']}&thread_id={$thread_info['id']}&msg_id=0"
                        ,'innerHTML'=>$text['Create_message']
                        ,'attributes'=>''
                        );


     $tor['forum/edit']=Array(
                         'URL'=>"index.php?action=forum/edit_thread&site_id={$thread_info['site_id']}&forum_id={$thread_info['forum_id']}&thread_id={$thread_info['id']}"
                        ,'innerHTML'=>$text['Edit_thread_properties']
                        ,'attributes'=>''
                        );

     $tor['forum/list']=Array(
                         'URL'=>"index.php?action=forum/list_thread&site_id={$thread_info['site_id']}&forum_id={$thread_info['forum_id']}&delete_thread_id={$thread_info['id']}&" . query_string("^action$|^site_id$|^forum_id$|^delete_thread_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['Delete']
                        ,'attributes'=>" onclick='return confirm(\"{$text['Are_You_sure']}?\")' "
                        );
   return $tor;
}

function menu_msg($msg_info)
{
   global $text, $db,$table_prefix;
   $tor=Array();

     $tor['forum/msg_reply']=Array(
                         'URL'=>"index.php?action=forum/edit_msg&site_id={$msg_info['site_id']}&forum_id={$msg_info['forum_id']}&thread_id={$msg_info['thread_id']}&msg_id=0&reply_to_msg={$msg_info['id']}"
                        ,'innerHTML'=>'<b>'.text('Reply').'</b>'
                        ,'attributes'=>''
                        );

     $tor['forum/list_messages1']=Array(
                         'URL'=>"index.php?action=forum/list_messages&site_id={$msg_info['site_id']}&forum_id={$msg_info['forum_id']}&thread_id={$msg_info['thread_id']}&message_set_visible={$msg_info['id']}&" . query_string("^action$|^site_id$|^forum_id$|^thread_id$|^".session_name()."$|^message_")
                        ,'innerHTML'=>text('Make_visible')
                        ,'attributes'=>""
                        );

     $tor['forum/list_messages2']=Array(
                         'URL'=>"index.php?action=forum/list_messages&site_id={$msg_info['site_id']}&forum_id={$msg_info['forum_id']}&thread_id={$msg_info['thread_id']}&message_set_invisible={$msg_info['id']}&" . query_string("^action$|^site_id$|^forum_id$|^thread_id$|^".session_name()."$|^message_")
                        ,'innerHTML'=>text('Make_invisible')
                        ,'attributes'=>""
                        );


     $tor['forum/msg_edit']=Array(
                         'URL'=>"index.php?action=forum/edit_msg&site_id={$msg_info['site_id']}&forum_id={$msg_info['forum_id']}&thread_id={$msg_info['thread_id']}&msg_id={$msg_info['id']}"
                        ,'innerHTML'=>$text['Edit']
                        ,'attributes'=>''
                        );

     $tor['forum/list_thread']=Array(
                         'URL'=>"index.php?action=forum/list_thread&site_id={$msg_info['site_id']}&forum_id={$msg_info['forum_id']}&" . query_string("^action$|^site_id$|^forum_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['View_forum']
                        ,'attributes'=>""
                        );

     $tor['forum/list_messages']=Array(
                         'URL'=>"index.php?action=forum/list_messages&site_id={$msg_info['site_id']}&forum_id={$msg_info['forum_id']}&thread_id={$msg_info['thread_id']}&" . query_string("^action$|^site_id$|^forum_id$|^thread_id$|^".session_name()."$")
                        ,'innerHTML'=>$text['View_thread']
                        ,'attributes'=>""
                        );

     $tor['delete_msg']=Array(
                         'URL'=>"index.php?delete_message_id={$msg_info['id']}&" . query_string("^delete_message_id$|^".session_name()."$")
                        ,'innerHTML'=>'<br/>'.$text['Delete']
                        ,'attributes'=>" onclick='return confirm(\"{$text['Are_You_sure']}?\")' "
                        );

   return $tor;
}

?>
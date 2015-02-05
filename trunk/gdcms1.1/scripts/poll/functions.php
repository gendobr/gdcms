<?php
/*
  draw menu for news
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

function menu_poll($_info)
{
   global $text, $db,$table_prefix;
   $tor=Array();
   $sid=session_name().'='.$GLOBALS['_COOKIE'][session_name()];
   


   $tor['poll/edit']=Array(
                       'URL'=>"index.php?action=poll/edit&site_id={$_info['site_id']}&poll_id={$_info['id']}"
                      ,'innerHTML'=>$text['Poll_edit']
                      ,'attributes'=>''
                      );

   $tor['poll/view']=Array(
                       'URL'=>"index.php?action=poll/ask&site_id={$_info['site_id']}"."&poll_id={$_info['id']}&".$sid
                      ,'innerHTML'=>$text['Poll_view']
                      ,'attributes'=>' target=_blank '
                      );

   $tor['poll/html']=Array(
                       'URL'=>"index.php?action=poll/html&site_id={$_info['site_id']}&poll_id={$_info['id']}"
                      ,'innerHTML'=>$text['Poll_html_code']
                      ,'attributes'=>' target=_blank '
                      );
                      
   $tor['poll/stats']=Array(
                       'URL'=>"index.php?action=poll/stats&poll_id={$_info['id']}&site_id={$_info['site_id']}".'&'.$sid
                      ,'innerHTML'=>$text['Poll_stats'].'<br><br>'
                      ,'attributes'=>' target=_blank '
                      );



   $tor['poll/delete']=Array(
                         'URL'=>"index.php?action=poll/delete".
                                         "&site_id=".$_info['site_id'].
                                         "&delete_poll_id=".$_info['id']
                        ,'innerHTML'=>$text['Poll_delete'].'<iframe src="about:blank" width=1px height=1px style="border:none;" name="frm_delete"></iframe>'
                        ,'attributes'=>" onclick='return confirm(\"{$text['Are_You_sure']}?\")' target=frm_delete "
                        );
   return $tor;
}



//
function GetRealIp() {
    if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip=$_SERVER['HTTP_CLIENT_IP'];
    }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }else {
        $ip=$_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

function GetHeaders() {
    $User_Agent = $_SERVER['HTTP_USER_AGENT'];
    $Accept = $_SERVER['HTTP_ACCEPT'];
    $Accept_Language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $Accept_Encoding = $_SERVER['HTTP_ACCEPT_ENCODING'];
    $Accept_Charset = isset($_SERVER['HTTP_ACCEPT_CHARSET'])?$_SERVER['HTTP_ACCEPT_CHARSET']:'';
    $http_headers = $User_Agent.';'.$Accept.';'.$Accept_Language.';'.$Accept_Encoding.';'.$Accept_Charset;
}


function enhanced_security_scripts($md5_headers){


    return "
        <script src=\"scripts/lib/jquery-1.4.2.min.js\" type=\"text/javascript\"></script>
        <script src=\"scripts/poll/plugin-detect-0.6.3.js\" type=\"text/javascript\"></script>
        <script src=\"scripts/poll/jquery.flash.js\" type=\"text/javascript\"></script>
        <STYLE>
        .userData {behavior:url(#default#userdata);}
        </STYLE>
        <ELEMENT CLASS=\"userData\" ID=\"oPersistElement\">
        <div id=\"flashcontent\"></div>
        <div id=\"oPersistDiv\" class=\"userData\"></div>
        <script src=\"scripts/poll/fetch_whorls.js\" type=\"text/javascript\"></script>
        <script type=\"text/javascript\">
        
        if((typeof retries) == \"undefined\"){
            var retries= 5;
        }
        $(document).ready(function(){
        setTimeout(
            function(){
               var form=$('#poll_ask_form_id');
               $('#{$md5_headers}').attr('value',result).appendTo(form);
               //alert(result);
            }
            ,retries*530);
        });
        </script>
    ";


    //    return "
    //        <script src=\"scripts/lib/jquery-1.4.2.min.js\" type=\"text/javascript\"></script>
    //        <script src=\"scripts/poll/plugin-detect-0.6.3.js\" type=\"text/javascript\"></script>
    //        <script src=\"scripts/poll/appletinfo.js\" type=\"text/javascript\"></script>
    //        <script src=\"scripts/poll/jquery.flash.js\" type=\"text/javascript\"></script>
    //        <STYLE>
    //        .userData {behavior:url(#default#userdata);}
    //        </STYLE>
    //        <ELEMENT CLASS=\"userData\" ID=\"oPersistElement\">
    //        <script type=\"text/javascript\">
    //        var attributes = {codebase: \"java\", code: \"fonts.class\", id: \"javafontshelper\", name: \"javafontshelper\", \"mayscript\": \"true\", width: 1, height: 1};
    //        if (deployJava.versionCheck('1.1+')) deployJava.writeAppletTag(attributes);
    //        </script>
    //        <div id=\"flashcontent\"></div>
    //        <div id=\"oPersistDiv\" class=\"userData\"></div>
    //        <script src=\"scripts/poll/fetch_whorls.js\" type=\"text/javascript\"></script>
    //        <APPLET class=\"userData\" id=\"info_applet\" codebase=\"scripts/poll\" code=\"info.class\" width=1 height=1></APPLET>
    //        <script type=\"text/javascript\">
    //        $(document).ready(function(){
    //        setTimeout(
    //            function(){
    //               var form=$('#poll_ask_form_id');
    //               $('#{$md5_headers}').attr('value',result).appendTo(form);
    //               //alert(result);
    //            }
    //            ,retries*530);
    //        });
    //        </script>
    //    ";
}
?>
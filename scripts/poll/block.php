<?php
/*
  Generate "Poll/Ask" block
  arguments are
    $site_id - site identifier, integer, mandatory
    $lang    - interface language, char(3), mandatory (rus|ukr|eng)
    $template=<template file name>, file name (extension is ".html"),
              template placed in site root directory.
*/
header('Access-Control-Allow-Origin: *');
global $main_template_name;
$main_template_name='';

//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = (int)($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
//prn($this_site_info);die();
//prn($input_vars);
if(!$this_site_info) die($txt['Site_not_found']);
//------------------- get site info - end --------------------------------------


// ------------------ get poll - begin -----------------------------------------
$poll_id=isset($input_vars['poll_id'])?(int)$input_vars['poll_id']:0;
if($poll_id>0) $get_poll=" AND id=$poll_id"; else $get_poll='';

$polls=db_getrows("SELECT * FROM {$table_prefix}golos_pynannja WHERE site_id={$site_id} AND is_active=1 $get_poll ORDER BY ordering ASC");
if(!$polls) {
    echo '';
    return '';
}
//prn('$polls',$polls);

$poll_ids=Array();
foreach($polls as $key=>$val) $poll_ids[$key]=(int)$val['id'];

$vidpovidi=db_getrows("SELECT * FROM {$table_prefix}golos_vidpovidi WHERE pynannja_id IN (".join(',',$poll_ids).") ORDER BY pynannja_id, id ");

$poll_ids=array_flip($poll_ids);
foreach($vidpovidi as $val) {
    $i=$poll_ids[$val['pynannja_id']];
    if(!isset($polls[$i]['vidpovidi']))  $polls[$i]['vidpovidi']=Array();
    $polls[$i]['vidpovidi'][$val['id']]=$val['html'];
}
// prn($polls);
//------------------- get poll - end -------------------------------------------





run('site/page/page_view_functions');
run('poll/functions');

$ip = GetRealIp();
$md5_headers = md5(GetHeaders().$this_site_info['salt']);
$session_id=session_id();
$enhances_security_scripts=enhanced_security_scripts($md5_headers);

if(!isset($_COOKIE['poll_last_answer'])) {
    $_COOKIE['poll_last_answer']=time()-3600;
}
setcookie('poll_last_answer', $_COOKIE['poll_last_answer'], time()+86400,dirname($_SERVER['PHP_SELF'])); // expires in 1 day
//

# ---------------------- choose template - begin -------------------------------

# check if template name is posted
if(isset($_REQUEST['template'])) {
    $poll_template = sites_root.'/'.$this_site_info['dir'].'/'.basename($_REQUEST['template']).'.html';
    if(!is_file($poll_template)) $poll_template=false;
    if(!$poll_template) $poll_template = sites_root.'/'.$this_site_info['dir'].'/'.basename($_REQUEST['template']);
    if(!is_file($poll_template)) $poll_template=false;
}
else $poll_template=false;


# check if block template exists
if(!$poll_template) $poll_template = sites_root.'/'.$this_site_info['dir'].'/template_poll_ask_block.html';
if(!is_file($poll_template)) $poll_template=false;

# use default system template
#prn('$poll_template',$poll_template);
if(!$poll_template) $poll_template = 'cms/template_poll_ask_block';
# ---------------------- choose template - end ---------------------------------

$hidden_fields="<input type=hidden name=action value=poll/ask><input type=hidden name=site_id value=$site_id><input type=hidden name=poll_id value=$poll_id><input type=hidden name=\"$md5_headers\" id=\"$md5_headers\" value=\"\">";

#prn('$poll_template',$poll_template);
$vyvid=process_template( $poll_template
        ,Array(
        'polls'=>$polls
        ,'hidden_fields'=>$hidden_fields
        ,'form_action'=>site_URL
        ,'enhances_security_scripts'=>$enhances_security_scripts
        ,'poll_ask_form_id'=>'poll_ask_form_id'
));

if(strlen($vyvid)==0) {
    echo '';
    return '';
}

if(isset($input_vars['element'])) {
    echo "
    <div id=toinsert>$vyvid</div>
    <script type=\"text/javascript\">
    <!--
        $(document).ready(function(){
        setTimeout(
            function(){
                var form=$('#poll_ask_form_id');
                $('#{$md5_headers}').attr('value',result).appendTo(form);
                //alert(result);

                var from = document.getElementById('toinsert');
                //alert(from.innerHTML);
                var to;
                if(window.top)
                {
                  //alert('window.top - OK');
                  if(window.top.document)
                  {
                    //alert('window.top.document - OK');
                    to = window.top.document.getElementById('{$input_vars['element']}');
                    //alert(to);
                    if(to)
                    {
                       //alert('element - OK');
                       to.innerHTML = from.innerHTML;
                    }
                  }
                }
            }
            ,retries*530);
        });
    // -->
    </script>
    "
    ;
}
else echo $vyvid;



// remove from history
nohistory($input_vars['action']);


?>
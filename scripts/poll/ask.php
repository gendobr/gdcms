<?php

$poll_timeout=60;

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

// get site template
$this_site_info['template'] = site_get_template($this_site_info,'template_index');


// ------------------ get poll - begin -----------------------------------------
$poll_id=isset($input_vars['poll_id'])?(int)$input_vars['poll_id']:0;
if($poll_id>0) $get_poll=" AND id=$poll_id"; else $get_poll='';
$polls=\e::db_getrows("SELECT * FROM {$table_prefix}golos_pynannja WHERE site_id={$site_id} AND is_active=1 $get_poll ORDER BY ordering ASC");
if(!$polls) return '';
//prn('$polls',$polls);

$poll_ids=Array();
foreach($polls as $key=>$val) $poll_ids[$key]=(int)$val['id'];

// get UID of the question set
   sort($poll_ids);
   $poll_uid=join(',',$poll_ids);


$vidpovidi=\e::db_getrows("SELECT * FROM {$table_prefix}golos_vidpovidi WHERE pynannja_id IN (".join(',',$poll_ids).") ORDER BY pynannja_id, id ");

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

// load language messages
if( isset($input_vars['interface_lang']) && strlen($input_vars['interface_lang'])>0) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])) $input_vars['lang']=\e::config('default_language');
$input_vars['lang']      = get_language('lang');
$txt = load_msg($input_vars['lang']);

if(!isset($_COOKIE['poll_last_answer'])) {
    setcookie('poll_last_answer', $_COOKIE['poll_last_answer']=time()-2*$poll_timeout, time()+$poll_timeout);
}

$ip = GetRealIp();
$md5_headers = md5(GetHeaders().$this_site_info['salt']);
$session_id=session_id();

if(isset($input_vars['poll']) && is_array($input_vars['poll']) ) {
    if(isset($input_vars[$md5_headers]) && isset($_COOKIE['poll_last_answer']) && ( (time()-$_COOKIE['poll_last_answer']) > $poll_timeout)) {
        // ------------------ save answers - begin ---------------------------------

        setcookie('poll_last_answer', $_COOKIE['poll_last_answer']=time(), time()+$poll_timeout);

        // client_is_valid
        $query="SELECT count(id) as n,
                     ( IF(client_ip   ='$ip',1,0)
                     + IF(client_sign ='".\e::db_escape($input_vars[$md5_headers])."',1,0)
                     + IF(client_sign2='{$md5_headers}',1,0) ) as level
                FROM {$table_prefix}golos_vidpovidi_details
                WHERE site_id={$site_id} AND poll_uid='{$poll_uid}'
                GROUP BY level
                HAVING level>0
                ORDER BY level DESC";
        //prn($query);
        $tmp=\e::db_getonerow($query);
        if($tmp) {
             $client_is_valid=1/($tmp['n']*exp($tmp['level']));
        } else $client_is_valid=1;


        $pids=array();
        $answer_ids=array();
        foreach($input_vars['poll'] as $p_id=>$answers) {
            if(count($answers)==0) continue;
            $pids[]=(int)$p_id;
            foreach($answers as $aid) $answer_ids[]=(int)$aid;
        }

        if(count($pids)>0) {
            $query="UPDATE {$table_prefix}golos_pynannja
                    SET n_respondents=n_respondents+1
                    WHERE id IN(".join(',',$pids).")";
            \e::db_execute($query);
        }
        if(count($answer_ids)>0) {
            \e::db_execute("UPDATE {$table_prefix}golos_vidpovidi
                        SET golosiv=golosiv+1
                        WHERE id IN(".join(',',$answer_ids).")");

            // ----------------- save details - begin --------------------------
            // existing answers
            \e::db_execute("insert into {$table_prefix}golos_vidpovidi_details(poll_id, answer_id, session_id, answer_date,site_id,client_ip,client_sign,client_sign2,client_is_valid,poll_uid)
                        SELECT v.pynannja_id, v.id, '{$session_id}', now(), $site_id,'$ip','".\e::db_escape($input_vars[$md5_headers])."','{$md5_headers}',$client_is_valid,'{$poll_uid}'
                        FROM {$table_prefix}golos_vidpovidi as v
                        WHERE v.id in(".join(',',$answer_ids).")");
            $tmp=Array();
            foreach($polls as $k=>$v) {
                $tmp[]=$v['id'];
            }
            $missing_answers=array_diff($tmp,$pids);
            if(count($missing_answers)>0) {
                \e::db_execute("insert into {$table_prefix}golos_vidpovidi_details(
                              poll_id, answer_id, session_id,
                              answer_date,site_id,client_ip,
                              client_sign,client_sign2,client_is_valid,poll_uid)
	                   SELECT p.id, 0,'{$session_id}', now(), $site_id,'$ip','".\e::db_escape($input_vars[$md5_headers])."','{$md5_headers}',$client_is_valid,'{$poll_uid}'
                           FROM {$table_prefix}golos_pynannja as p
		           WHERE p.id in(".join(',',$missing_answers).")");
            }
            // ----------------- save details - end ----------------------------
        }


        $page_content="
           <p><font color=green>{$text['Poll_thank_you']}</font></p>
           <a href=index.php?action=poll/stats&site_id=$site_id&poll_id=$poll_id>{$text['Poll_stats']}</a>
           ";
        // ------------------ save answers - end -----------------------------------
    }
    else {
        $page_content="<p>".text('You_cannot_answer_too_frequently')."</p>";
    }
} else {
// ------------------- draw form - begin ---------------------------------------

    if(!isset($_COOKIE['poll_last_answer'])) {
        $_COOKIE['poll_last_answer']=time()-3600;
    }
    setcookie('poll_last_answer', $_COOKIE['poll_last_answer'], time()+$poll_timeout,dirname($_SERVER['PHP_SELF'])); // expires in 1 day

    $enhances_security_scripts=enhanced_security_scripts($md5_headers);

    $hidden_fields="
                <input type=hidden name=action value=poll/ask>
                <input type=hidden name=site_id value=$site_id>
                <input type=hidden name=poll_id value=$poll_id>
                <input type=hidden name=\"$md5_headers\" id=\"$md5_headers\" value=\"\">
            ";
    $page_content=process_template(site_get_template($this_site_info,'template_poll_ask')
            ,Array(
            'site'=>$this_site_info
            ,'site_root_url'=>site_root_URL
            ,'text'=>$txt
            ,'polls'=>$polls
            ,'hidden_fields'=>$hidden_fields
            ,'enhances_security_scripts'=>$enhances_security_scripts
            ,'poll_ask_form_id'=>'poll_ask_form_id'
            )
    );
// ------------------- draw form - end -----------------------------------------
}






$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list=list_of_languages();

//------------------------ get list of languages - end -------------------------


$file_content=process_template($this_site_info['template']
        ,Array(
        'page'=>Array(
                'title'=>$txt['Poll']
                ,'content'=> $page_content
                ,'abstract'=> ''
                ,'site_id'=>$site_id
                ,'lang'=>$input_vars['lang']
        )
        ,'lang'=>$lang_list
        ,'site'=>$this_site_info
        ,'menu'=>$menu_groups
        ,'site_root_url'=>site_root_URL
        ,'text'=>$txt
));

echo $file_content;                   

?>
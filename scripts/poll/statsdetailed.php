<?php

//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = (int) ($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
//prn($this_site_info);die();
//prn($input_vars);
if (!$this_site_info)
    die($txt['Site_not_found']);
//------------------- get site info - end --------------------------------------
// -------------------------- get site template - begin ------------------------
$custom_page_template = site_get_template($this_site_info, 'template_index');
// -------------------------- get site template - end --------------------------
// collect different poll combinations

$start = isset($input_vars['start']) ? (int) $input_vars['start'] : 0;
$separator = md5(time());

$tmp = \e::db_getrows($query = "select distinct poll_uid from <<tp>>golos_vidpovidi_details d where d.site_id={$site_id}  and poll_uid is not null");
//prn($query, $combinations);
$cnt = count($tmp);
$combinations=Array();
$poll_id = Array();
for ($i = 0; $i < $cnt; $i++) {
    $poll_uid=$tmp[$i]['poll_uid'];
    $combinations[$poll_uid] = explode(',', $poll_uid);
    if (strlen($poll_uid) > 0) {
        $poll_id = array_merge($poll_id, $combinations[$poll_uid]);
    }
}
unset($tmp);
$poll_id = array_unique($poll_id);
$poll_id[] = 0;
//prn($query, $combinations, $poll_id); // limit $start, 10


$tmp = \e::db_getrows("select * from <<tp>>golos_pynannja as p  where p.site_id={$site_id} and p.id in(" . join(',', $poll_id) . ")");
$poll=Array();
foreach ($tmp as $tm){
    $poll[$tm['id']]=$tm;
}
unset($tmp);
//prn($poll);
//exit();




$input_vars['page_content'] = '
     <style>
     .grp{background-color:#e0e0e0;padding:10px;margin-bottom:10pt;}
     .sgrp{background-color:white;padding:10px;margin-top:5pt;}
     </style>
   ';
$url_prefix = site_root_URL . "/index.php?action=poll/statsdetailsdownload&site_id={$site_id}&poll_uid=";
foreach ($combinations as $poll_uid=>$cmb) {
    $cmb['titles'] = str_replace($separator, '</div><div class=sgrp>', strip_tags($cmb['titles']));
    $input_vars['page_content'].="
       <div class=grp>
           <a target=_blank href=\"{$url_prefix}{$poll_uid}\">����������� ������</a>
           ";
       foreach($cmb as $poll_id){

           if(isset($poll[$poll_id]))
       $input_vars['page_content'].="
           <div class=sgrp>
             {$poll[$poll_id]['title']}
           </div>           ";

       }
       $input_vars['page_content'].="
       </div>";
}
$input_vars['page_header'] =
$input_vars['page_title'] = '�������� ���������� ������';

//----------------------------- context menu - begin ---------------------------


$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 25) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------

<?php


//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = (int)($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
//prn($this_site_info);die();
//prn($input_vars);
if(!$this_site_info) die($txt['Site_not_found']);
//------------------- get site info - end --------------------------------------

//--------------------------- get site template - begin ------------------------
$custom_page_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/template_index.html';
if (is_file($custom_page_template)) {
    $this_site_info['template'] = $custom_page_template;
}
//--------------------------- get site template - end --------------------------


// ------------------ get poll - begin -----------------------------------------
run('poll/functions');
if(get_level($site_id)==0) {
    $is_guest="AND is_active=1";
} else {
    $is_guest='';
}
$poll_id=isset($input_vars['poll_id'])?(int)$input_vars['poll_id']:0;
if($poll_id>0) $get_poll=" AND id=$poll_id"; else $get_poll='';


$polls=\e::db_getrows( "SELECT *  FROM <<tp>>golos_pynannja  WHERE site_id={$site_id} $is_guest $get_poll ORDER BY ordering ASC");
if(!$polls) return '';
//prn('$polls',$polls);

$poll_ids=Array();
foreach($polls as $key=>$val) $poll_ids[$key]=(int)$val['id'];

$vidpovidi=\e::db_getrows("SELECT * FROM <<tp>>golos_vidpovidi WHERE pynannja_id IN (".join(',',$poll_ids).") ORDER BY pynannja_id, id ");

$poll_ids=array_flip($poll_ids);
foreach($vidpovidi as $val) {
    $i=$poll_ids[$val['pynannja_id']];
    if(!isset($polls[$i]['vidpovidi']))  $polls[$i]['vidpovidi']=Array();
    $polls[$i]['vidpovidi'][$val['id']]=$val;
}
//prn($polls);
//------------------- get poll - end -------------------------------------------

# ------------------- draw statistics - begin ----------------------------------
$page_content='';
if($is_guest=='') {

    $page_content.="
      <style>
      .menu_block{
        position:absolute;
        background-color:#e0e0e0;
        border:1px solid blue;
        padding:10px;
        font-size:10pt;
        font-weight:normal;
      }
      </style>
      <script type=\"text/javascript\">
      <!--
        var report_prev_menu;
        var report_href;
        function report_change_state(cid)
        {
            var lay=document.getElementById(cid);
            if (lay.style.display==\"none\")
            {
               if(report_prev_menu) report_prev_menu.style.display=\"none\";
               lay.style.display=\"block\";
               report_prev_menu=lay;
            }
            else
            {
               lay.style.display=\"none\";
               report_prev_menu=null;
            }
            report_href=true;
        }
        
        function report_hide_menu()
        {
          if(report_prev_menu && !report_href) report_prev_menu.style.display=\"none\";
          report_href=false;
        }
        document.onclick=report_hide_menu;
      // -->
      </script>
     ";
}

$tmp_img_root=site_root_URL.'/scripts/poll/img';
$cnt=array_keys($polls);
foreach($cnt as $i) {
    $context_menu='';
    if($is_guest=='') {
        $polls[$i]['context_menu']=menu_poll($polls[$i]);

        //--------------------------- context menu - begin ----------------------
        if(is_array($polls[$i]['context_menu'])) {
            $context_menu.="<img src=\"img/context_menu.gif\" border=1px alt=\"\" style='margin-right:5px;' align=left onclick=\"report_change_state('cm{$i}')\" align=baseline>
	       <div class=menu_block style='display:none;' id='cm{$i}'>";
            foreach($polls[$i]['context_menu'] as $menu_item) {
                $context_menu.="<nobr><a href=\"{$menu_item['URL']}\" {$menu_item['attributes']}>{$menu_item['innerHTML']}</a></nobr><br/>\n";
            }
            $context_menu.="</div>";
        }
        //--------------------------- context menu - end ------------------------
    }

    switch($polls[$i]['poll_type']) {
        case 'radio':
            $maximum=max(1,$polls[$i]['n_respondents']);
            break;
        case 'checkbox':
            // $maximum is total number of ticks
            $maximum=0;
            foreach($polls[$i]['vidpovidi'] as $val) {
                $maximum+=$val['golosiv'];
            }
            break;
    }
    $page_content.="\n<h3 style='text-align:left;'>{$context_menu}{$polls[$i]['title']}</h3>\n<table border=0 style='margin:0;border:none;'>\n";
    foreach($polls[$i]['vidpovidi'] as $val) {
        $page_content.="<tr><td width=112 align=right style='text-align:right;border:none;'>";
        $page_content.="<nobr><img src={$tmp_img_root}/poch.gif style='margin:0;'>";
        if ($val['golosiv'] != 0) {
            $width = $val['golosiv']/$maximum;
            $width = min(1,$width) * 100;
            $page_content.="<img src={$tmp_img_root}/ser.gif style='margin:0;' width={$width} height=12>";
        }
        $page_content.="<img src={$tmp_img_root}/kin.gif style='margin:0;'></nobr>";
        $page_content.="</td><td style='border:none;'><small>". round(100*$val['golosiv']/$maximum,2)."%</small></td>";
        $page_content.="<td style='border:none;'>{$val['html']}</td></tr>";
    }
    $page_content.="</table>\n{$polls[$i]['n_respondents']} {$text['Poll_respondents']}<br>\n";
}
# ------------------- draw statistics - end ------------------------------------




if($is_guest=='') {
    $input_vars['page_title']  =
            $input_vars['page_header'] = $this_site_info['title'] .' - '. $text['Poll'];
    $input_vars['page_content']= $page_content;


    if(count($polls)==1) {
        $input_vars['page_menu']['page']=Array('title'=>$text['Poll'],'items'=>Array());

        $input_vars['page_menu']['page']['items'] = menu_poll($polls[0]);
    }

    //--------------------------- context menu -- begin ----------------------------
    $sti=$text['Site'].' "'. $this_site_info['title'].'"';
    $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
    $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
    $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
    //--------------------------- context menu -- end ------------------------------
    return ;
}


global $main_template_name;
$main_template_name='';

if(isset($input_vars['interface_lang']))
    if(strlen($input_vars['interface_lang'])>0)
        $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])) $input_vars['lang']=\e::config('default_language');
$input_vars['lang']      = get_language('lang');
$txt = load_msg($input_vars['lang']);


run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list=list_of_languages();
//prn($lang_list);
$cnt=count($lang_list);
for($i=0;$i<$cnt;$i++) {
    if(!isset($this_site_info['extra_setting']['lang'][$lang_list[$i]['lang']])){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url']=$lang_list[$i]['href'];
    $lang_list[$i]['lang']=$lang_list[$i]['name'];
}
usort ( $lang_list , function($k1, $k2){
    $defaultLang=\e::config('default_language');
    $s1 = ($k1['name'] == $defaultLang?'0':'1').$k1['name'];
    $s2 = ($k2['name'] == $defaultLang?'0':'1').$k2['name'];
    return -strcmp($s2, $s1);
} );
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


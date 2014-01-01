<?php
/**
 * Advanced search form
 */
run('site/menu');
run('ec/item/functions');
# -------------------- set interface language - begin --------------------------
$debug=false;
if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])   ) $input_vars['lang']=default_language;
if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
// $lang=$input_vars['lang'];
$lang=get_language('lang');
# -------------------- set interface language - end ----------------------------

# -------------------------- load messages - begin -----------------------------
global $txt;
$txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------

# ------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if(!$this_site_info) die($txt['Site_not_found']);
$this_site_info['title']=get_langstring($this_site_info['title'],$lang);
# ------------------- get site info - end --------------------------------------

# --------------------------- get site template - begin ------------------------
$custom_page_template = sites_root.'/'.$this_site_info['dir'].'/template_index.html';
if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
# --------------------------- get site template - end --------------------------


include(script_root.'/ec/item/get_public_list.php');
include(script_root.'/ec/item/adjust_public_list.php');
//prn($list_of_ec_items);




# -------------------- get list of page languages - begin ----------------------
$tmp=db_getrows("SELECT DISTINCT ec_item_lang as lang
                     FROM {$table_prefix}ec_item  AS ec_item
                     WHERE ec_item.site_id={$site_id}
                       AND ec_item.ec_item_cense_level&".ec_item_show."");
$existing_languages=Array();
foreach($tmp as $tm) $existing_languages[$tm['lang']]=$tm['lang'];
// prn($existing_languages);


$lang_list=list_of_languages();
$cnt=count($lang_list);
for($i=0;$i<$cnt;$i++) {
    if(!isset($existing_languages[$lang_list[$i]['name']])) {
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url']=$lang_list[$i]['href'];
    $lang_list[$i]['lang']=$lang_list[$i]['name'];
}
$lang_list=array_values($lang_list);
//prn($lang_list);
# -------------------- get list of page languages - end ------------------------

# ------------------------ draw using SMARTY template - begin ------------------
run('site/page/page_view_functions');

# get site menu
$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

# search for template of search form
$ec_item_template_search = site_get_template($this_site_info,'template_ec_item_search_advanced');


# search for template of item list
$ec_item_template_list = site_get_template($this_site_info,'template_ec_item_list');

// prn(checkStr(draw_options((isset($input_vars['ec_producer_id'])?$input_vars['ec_producer_id']:''), db_getrows("SELECT ec_producer_id, ec_producer_title FROM {$table_prefix}ec_producer WHERE site_id={$site_id} ORDER BY ec_producer_title"))));
$category=Array();
$tmp=db_getrows("SELECT ec_category_id, ec_category_title,deep FROM {$table_prefix}ec_category WHERE site_id={$site_id} ORDER BY `start`");
foreach($tmp as $tm) {
    $category[$tm['ec_category_id']]=str_repeat('...', $tm['deep']).get_langstring($tm['ec_category_title']);
}

$extrafld='';
if(isset($input_vars['ec_category_id'])) {
    $cat=db_getonerow("SELECT * FROM {$table_prefix}ec_category WHERE ec_category_id=".( (int)$input_vars['ec_category_id'] ));
    //prn($cat);
    if($cat) {
        $pa=db_getrows(
                "SELECT *
             FROM {$table_prefix}ec_category_item_field
             WHERE site_id={$cat['site_id']}
               AND ec_category_id IN(
                   SELECT pa.ec_category_id
                   FROM {$table_prefix}ec_category as pa
                   WHERE pa.site_id={$cat['site_id']}
                   AND pa.start<={$cat['start']} AND {$cat['finish']}<=pa.finish
            )" );
        //prn($pa);
        //prn($input_vars['extrafld']);
        foreach($pa as $fld) {
            $extrafld.='<span class="lbl">'.get_langstring($fld['ec_category_item_field_title']).':</span>';
            if($fld['ec_category_item_field_options']) {

                $opts=array_map('trim', explode("\n",$fld['ec_category_item_field_options']));
                $value=isset($input_vars['extrafld'][$fld['ec_category_item_field_id']])?$input_vars['extrafld'][$fld['ec_category_item_field_id']]:'';
                $class=strlen($value)>0?'filled':'';
                $extrafld.="<select class='txt {$class}' name='extrafld[{$fld['ec_category_item_field_id']}]'><option value=''>&nbsp;</option>"
                        .draw_options($value, array_combine($opts, $opts))
                        ."</select>";
            }else {
                if($fld['ec_category_item_field_type']=='number') {
                    $value_min=(isset($input_vars['extrafld'][$fld['ec_category_item_field_id']])?$input_vars['extrafld'][$fld['ec_category_item_field_id']]['min']:'');
                    $class_min=strlen($value_min)>0?'filled':'';
                    if(strlen($value_min)) $value_min=checkFloat($value_min);

                    $value_max=(isset($input_vars['extrafld'][$fld['ec_category_item_field_id']])?$input_vars['extrafld'][$fld['ec_category_item_field_id']]['max']:'');
                    $class_max=strlen($value_max)>0?'filled':'';
                    if(strlen($value_max)) $value_max=checkFloat($value_max);

                    $extrafld.="<input class='{$class_min}' type='text' name='extrafld[{$fld['ec_category_item_field_id']}][min]' size='3' value='{$value_min}'>
                            ...
                            <input class='{$class_max}' type='text' name='extrafld[{$fld['ec_category_item_field_id']}][max]' size='3' value='{$value_max}'>";
                }else {
                    $value=isset($input_vars['extrafld'][$fld['ec_category_item_field_id']])?$input_vars['extrafld'][$fld['ec_category_item_field_id']]:'';
                    $class=strlen($value)>0?'filled':'';
                    //prn('$value='.var_dump($value));
                    $extrafld.="<input class='txt {$class}' type='text' name='extrafld[{$fld['ec_category_item_field_id']}]' value='".checkStr($value)."'>";
                }
            }
            $extrafld.='<br/>';
        }
        //prn($extrafld);

    }
}



$form=Array(
        'hidden_form_fields'=>hidden_form_elements('^ec_item_keywords$'),
        'action'=>site_URL,
        'ec_item_title'=>(isset($input_vars['ec_item_title'])?$input_vars['ec_item_title']:''),
        'ec_item_content'=>(isset($input_vars['ec_item_content'])?$input_vars['ec_item_content']:''),
        'ec_item_tags'=>(isset($input_vars['ec_item_tags'])?$input_vars['ec_item_tags']:''),
        'ec_item_price_min'=>(isset($input_vars['ec_item_price_min'])?$input_vars['ec_item_price_min']:''),
        'ec_item_price_max'=>(isset($input_vars['ec_item_price_max'])?$input_vars['ec_item_price_max']:''),

        'ec_producer_id'=>draw_options((isset($input_vars['ec_producer_id'])?$input_vars['ec_producer_id']:''), db_getrows("SELECT ec_producer_id, ec_producer_title FROM {$table_prefix}ec_producer WHERE site_id={$site_id} ORDER BY ec_producer_title")),
        'ec_producer_id_set'=>isset($input_vars['ec_producer_id']) && $input_vars['ec_producer_id']>0,

        'ec_category_id'=>draw_options((isset($input_vars['ec_category_id'])?$input_vars['ec_category_id']:''), $category),
        'ec_category_id_set'=>isset($input_vars['ec_category_id']) && $input_vars['ec_category_id']>0,
        'extrafld'=>$extrafld
);
$vyvid=
        process_template( $ec_item_template_search
        ,Array(
        'text'=>$txt,
        'site'=>$this_site_info,
        'form'=>$form ) )
        .process_template( $ec_item_template_list
        ,Array(
        'pages'=>$pages,
        'orderby'=>$orderby,
        'text'=>$txt,
        'ec_items'=>$list_of_ec_items,
        'ec_items_search_summary'=>sprintf(text('EC_items_search_summary'),$start+1,$start+count($list_of_ec_items),$rows_found),
        'ec_items_found' => $rows_found,
        'start'=>$start+1,
        'finish'=>$start+count($list_of_ec_items),
        'category_view_url_prefix'=>"index.php?action=ec/item/browse&lang=$lang&site_id=$site_id&ec_category_id=",
        'site'=>$this_site_info
        )
        )
;

$file_content=process_template($this_site_info['template']
        ,Array(
        'page'=>Array('title'=>$txt['EC_item_search_advanced']
                ,'content'=>$vyvid
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
# ------------------------ draw using SMARTY template - end --------------------
echo $file_content;

global $main_template_name;
$main_template_name='';

?>
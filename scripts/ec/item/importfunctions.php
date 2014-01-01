<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
*/
// ------------------ function to get importable fields - begin ----------------
function get_importable_fields($site_id) {
    global $db,$table_prefix;
    static $field_name_options;
    if(isset($field_name_options)) return $field_name_options;

    $query="show fields from {$table_prefix}ec_item";
    $field_names=db_getrows($query);
    //prn($field_names);
    $cnt=count($field_names);
    $field_name_options=Array();
    for($i=0;$i<$cnt;$i++) {
        $field_name_options[$field_names[$i]['Field']]=$field_names[$i];
    }
    asort($field_name_options);
    // prn($field_name_options);

    // exclude some fields
    unset(  $field_name_options['site_id'],
            $field_name_options['ec_item_id'],
            $field_name_options['ec_item_last_change_date'],
            $field_name_options['ec_item_mark'],
            $field_name_options['ec_item_keywords'],
            $field_name_options['ec_item_purchases'],
            $field_name_options['ec_item_in_cart'],
            $field_name_options['ec_item_id'],
            $field_name_options['cache_datetime'],
            $field_name_options['cached_info'],
            $field_name_options['ec_item_views']  );
    $field_name_options['ec_item_variants']  =Array('Field' => 'ec_item_variants'  , 'Type' => 'external_table');

    $query="SELECT * FROM {$table_prefix}ec_category_item_field WHERE site_id={$site_id}";
    $rows=db_getrows($query);
    foreach($rows as $row) {
        $field_name_options["ec_category_item_field[{$row['ec_category_item_field_id']}]"]=Array('Field' => "ec_category_item_field[{$row['ec_category_item_field_id']}]", 'Type' => 'external_table','Label'=>get_langstring($row['ec_category_item_field_title']));
    }

    return $field_name_options;
}
// ------------------ function to get importable fields - end ------------------

















// ------------------------ parse row - begin ----------------------------------
function parse_csv($string,$delimiter,$enclosure,$escape="\\") {
    //prn("parse_csv($string,$delimiter,$enclosure,$escape)");
    $str=$string;
    // ----------- find escapes - begin ----------------------------------------
    $offset=0;
    $escape_length=strlen($escape);
    $escape_positions=Array();
    $pos=strpos( $str, $escape,$offset);
    while($pos!==false) {
        $escape_positions[]=$pos+$escape_length+1;
        $offset=$pos+$escape_length;
        $pos=strpos( $str, $escape,$offset);
    }
    // ----------- find escapes - end ------------------------------------------
    // prn('$escape_positions',$escape_positions);

    // ----------- split fields - begin ----------------------------------------
    $offset=0;
    $pos=strpos( $str, $delimiter,$offset);
    $delimiter_length=strlen($delimiter);
    $fieldpos=Array(-1);
    $pos=strpos( $str, $delimiter,$offset);
    while($pos!==false) {
        if(!in_array($pos,$escape_positions)) {
            $fieldpos[]=$pos;
            $fieldpos[]=$pos;
        }
        $offset=$pos+$delimiter_length;
        $pos=strpos( $str, $delimiter,$offset);
    }
    $fieldpos[]=strlen($str)-2;
    $fieldpos=array_chunk($fieldpos,2);
    //prn($fieldpos);
    $enclosure_lenght=strlen($enclosure);
    $row=Array();
    foreach($fieldpos as $va) {
        $tmp=substr($str, $va[0]+1+$enclosure_lenght, $va[1]-$va[0]-1-2*$enclosure_lenght );
        $row[]=str_replace($escape,"\\",$tmp);
        //$row[]=$tmp;
    }
    // ----------- split fields - end ------------------------------------------
    //prn($row);
    return $row;
}
// ------------------------ parse row - end ------------------------------------





























// ------------------------ function parse_hierarchy($str) - start ---------
function parse_hierarchy($str) {
    $start_subitem='(';
    $finish_subitem=')';
    $divide_sibling=',';
    $price_correction_code_start='$';

    $tor=Array();

    $cnt=strlen($str);
    $pos=Array();
    $prev_pos=0;
    $indent=0;
    for($i=0;$i<$cnt;$i++) {
        $pos['start_subitem']=strpos($str,$start_subitem,$prev_pos);
        $pos['finish_subitem']=strpos($str,$finish_subitem,$prev_pos);
        $pos['divide_sibling']=strpos($str,$divide_sibling,$prev_pos);

        $min_val=false;
        $min_key=false;
        foreach($pos as $key=>$val) {
            if($val===false) continue;
            if($min_key===false) {
                $min_key=$key;
                $min_val=$val;
                continue;
            }
            if($min_val>$val) {
                $min_key=$key;
                $min_val=$val;
                continue;
            }
        }

        // print_r($pos);
        // print("<br/>{$min_key}=>{$min_val}");
        if($min_key===false) {
            break;
        }

        $tmp=trim(substr($str,$prev_pos,$min_val-$prev_pos));


        // print('<br/>#########'.$indent.' ==> '.$tmp.';<br/>');
        if(strlen($tmp)>0) {
            // split title
            $tmp=explode($price_correction_code_start,$tmp);
            if(!isset($tmp[1])) $tmp[1]='+0.0';
            $tor[]=Array('indent'=>$indent,'value'=>$tmp[0],'price_correction'=>$tmp[1]);
        }
        switch($min_key) {
            case 'start_subitem':
                $indent++;
                break;
            case 'finish_subitem':
                $indent--;
                break;
            case 'divide_sibling':
                break;
        }
        $prev_pos=$min_val+1;
    }
    $min_indent=$tor[0]['indent'];
    foreach($tor as $val) {
        if($min_indent<$val['indent']) {
            $min_indent<$val['indent'];
        }
    }
    $cnt=count($tor);
    for($i=0;$i<$cnt;$i++) {
        $tor[$i]['indent']-=$min_indent;
    }
    return $tor;
}
// $str="(color(red,blue,yellow),size(small, medium, big))";
// $t=parse_hierarchy($str);
// echo '<pre>'; print_r($t); echo '</pre>';
// ------------------------ function parse_hierarchy($str) - end -----------
















function draw_file_as_table($site_id,$file_data, $field_name_options) {

    // ----------------- get number of columns - begin -------------------------
    $n_columns=0;
    foreach($file_data as $fd) {
        $nc=count($fd);
        if($n_columns<$nc) $n_columns=$nc;
    }
    // ----------------- get number of columns - end ---------------------------

    // ---------------- draw all columns - begin -------------------------------
    $page_content="
        <style>
        .cd{width:100%;}
        </style>
        <form action=\"index.php\" method=\"post\">
        <input type=\"hidden\" name=\"action\" value=\"ec/item/import\" />
        <input type=\"hidden\" name=\"site_id\" value=\"$site_id\" />
        <h2 style='text-align:left;'>".text('Import_check_rows')."</h2>
        <table>
         <tr>
         <th rowspan=2>".text('Import_row')."</hd>
         <th colspan=\"$n_columns\" align=left style='text-align:left;'>&nbsp;".text('Import_column_names')."</th>
         </tr>
         <tr>
      ";
    for($i=0;$i<$n_columns;$i++) {
        $page_content.=" <td> <select name=\"column_name[$i]\"><option value=''></option>".draw_options('',$field_name_options)."</select></td>";
    }
    $page_content.=" </tr> ";

    $cnt=count($file_data);
    for($row_i=0;$row_i<$cnt;$row_i++) {
        $page_content.="<tr>";
        $page_content.=" <td> <input name=\"row[$row_i][import_row]\" type=checkbox checked=true value=\"1\"></td>";
        for($col_i=0;$col_i<$n_columns;$col_i++) {
            $page_content.=" <td> <input class=cd name=\"row[$row_i][$col_i]\" type=text value=\"".checkStr(isset($file_data[$row_i][$col_i])?$file_data[$row_i][$col_i]:'')."\"></td>";
        }
        $page_content.="</tr>";
    }
    $page_content.="\n </table> \n  ";
    // ---------------- draw all columns - end ---------------------------------
    return $page_content;
}










function draw_default_values_form($site_id,$field_name_options) {
    global $table_prefix;

    $page_content='';
    // ---------------------- default form values - begin ----------------------
    $page_content.="<br><h3 style='text-align:left;'>".text('Default_values')."</h3>\n\n<table>";
    //prn($field_name_options);

    # -------------------- list of category_ids - begin ------------------------
    $query="SELECT ec_category_id,ec_category_title,deep
                       FROM {$table_prefix}ec_category
                       WHERE site_id={$site_id}
                       ORDER BY start ASC";
    $ec_category_id_dictionary=db_getrows($query);
    $cnt=count($ec_category_id_dictionary);
    for($i=0;$i<$cnt;$i++) {
        $ec_category_id_dictionary[$i]['ec_category_title']=str_repeat('....',$ec_category_id_dictionary[$i]['deep']).get_langstring($ec_category_id_dictionary[$i]['ec_category_title']);
    }
    array_unshift($ec_category_id_dictionary,Array('','',));
    # -------------------- list of category_ids - end --------------------------

    # -------------------- onnullamount handlers dictionary - begin ------------
    $defined_functions=get_defined_functions();
    $defined_functions=$defined_functions['user'];
    $ec_item_onnullamount_dictionary=Array();
    $cnt=count($defined_functions);
    for($i=0;$i<$cnt;$i++) {
        if(preg_match('/^onnullamount_/',$defined_functions[$i])) {
            //$ec_item_onnullamount_dictionary[$defined_functions[$i]]=ereg_replace('^onnullamount_','',$defined_functions[$i]);
            $ec_item_onnullamount_dictionary[$defined_functions[$i]]=text($defined_functions[$i]);
        }
    }
    unset($defined_functions);
    array_unshift($ec_item_onnullamount_dictionary,Array('','',));
    # -------------------- onnullamount handlers dictionary - end --------------

    # -------------------- producers - begin -----------------------------------
    $query="SELECT ec_producer_id,ec_producer_title FROM {$table_prefix}ec_producer WHERE site_id={$site_id}";
    $ec_producer_id_dictionary=db_getrows($query);
    array_unshift($ec_producer_id_dictionary,Array('','',));
    # -------------------- producers - end -------------------------------------

    # -------------------- available languages - begin -------------------------
    $ec_item_lang_dictionary=Array();
    $tmp=list_of_languages();
    foreach($tmp as $lang) $ec_item_lang_dictionary[$lang['name']]=$lang['name'];
    array_unshift($ec_item_lang_dictionary,Array('','',));
    # -------------------- available languages - end ---------------------------

    foreach($field_name_options as $fld=>$lbl) {
        $dict_name="{$fld}_dictionary";
        if(isset($$dict_name)) {
            $page_content.="<tr><td>$lbl</td><td><select name=default_value[$fld] style='width:300pt;'>".draw_options('',$$dict_name)."</select></td></tr>";
        }
        else {
            $page_content.="<tr><td>$lbl</td><td><input type=text name=default_value[$fld] style='width:300pt;'></td></tr>";
        }
    }
    $page_content.="</table> ";
    return $page_content;
}





function get_producer($site_id,$data) {
    global $table_prefix;
    $query="SELECT ec_producer_id
                    FROM {$table_prefix}ec_producer
                    WHERE site_id={$site_id}
                      AND(    ec_producer_id=".( (int)$data )."
                          OR  ec_producer_title='".DbStr($data)."')
                    ";
    $tmp=db_getonerow($query);
    //prn($query, $tmp);
    $row_data=$tmp?$tmp['ec_producer_id']:0;
    return $row_data;
}

function get_categories($site_id,$data) {
    global $table_prefix;

    $colname='ec_category_id';
    $categories=preg_split('/\+/',$data);
    // --------------- main category - begin -----------------------------------
    $main_category=trim($categories[0]);
    $query="SELECT ec_category_id
                    FROM {$table_prefix}ec_category
                    WHERE site_id={$site_id}
                      AND (    ec_category_id=".( (int)$main_category )."
                           OR  ec_category_code='".DbStr($main_category)."'
                           OR (locate('".DbStr($main_category)."',ec_category_title)>0)
                           )
                    ORDER BY length(ec_category_title) DESC
                    LIMIT 0,1 ";
    $tmp=db_getonerow($query);
    //prn($query,$tmp);
    $row_data=$tmp?$tmp[$colname]:0;
    // --------------- main category - end -------------------------------------

    // --------------- additional categories - begin ---------------------------
    $additional_categories=Array();
    if(count($categories)>1) {
        unset($categories[0]);
        $query=Array();
        foreach($categories as $ca) {
            $ca=trim($ca);
            $query[]=" (    ec_category_id=".( (int)$ca )."  OR  ec_category_code='".DbStr($ca)."' OR (locate('".DbStr($ca)."',ec_category_title)>0)  ) \n";
        }
        $query="SELECT ec_category_id
                           FROM {$table_prefix}ec_category
                           WHERE site_id={$site_id}
                            AND (".join(' OR ',$query).")";
        $additional_categories=db_getrows($query);
        $cnt1=count($additional_categories);
        for($i1=0;$i1<$cnt1;$i1++) {
            $additional_categories[$i1]=$additional_categories[$i1]['ec_category_id'];
        }
    }
    // --------------- additional categories - end -----------------------------
    return Array('main_category'=>$row_data,'additional_categories'=>$additional_categories);
}

function get_cense_level($data) {
    $data=preg_split('/[ ,;+\/]/',$data);
    //prn($data);
    $tmp=Array();
    $titles=Array();
    foreach($data as $dt) {
        if(in_array('ec_item_'.$dt,$GLOBALS['ec_item_publication_states'])) {
            $titles[]=$dt;
            $tmp[]=array_search('ec_item_'.$dt,$GLOBALS['ec_item_publication_states']);
        }
        elseif(in_array($dt,$GLOBALS['ec_item_publication_states'])) {
            $titles[]=$dt;
            $tmp[]=array_search($dt,$GLOBALS['ec_item_publication_states']);
        }
        elseif(isset($GLOBALS['ec_item_publication_states'][$dt])) {
            $titles[]=$dt;
            $tmp[]=$dt;
        }
        elseif(preg_match('/^ec_item_/',$dt) && defined($dt)) {
            $titles[]=$dt;
            $tmp[]=constant($dt);
        }
        elseif(defined('ec_item_'.$dt)) {
            $titles[]=$dt;
            $tmp[]=constant('ec_item_'.$dt);
        }
    }
    if(count($tmp)>0) {
        $titles=join('+',$titles);
        $row_data=0;
        foreach($tmp as $tm) {
            $row_data=$row_data | $tm;
        }
    }
    else {
        $row_data=ec_item_hide;
    }
    return Array('mask'=>$row_data,'html'=>$titles);
}


function set_item_variants($ec_item_id,$ec_item_lang,$ec_item_variants) {
    global $table_prefix;

    $query="DELETE FROM {$table_prefix}ec_item_variant WHERE ec_item_id={$ec_item_id} and ec_item_lang='{$ec_item_lang}'";
    //prn($query);
    db_execute($query);

    $query=Array();
    foreach($ec_item_variants as $vkey=>$vval) {
        $query[]="({$ec_item_id},
                  '{$ec_item_lang}',
                  '".DbStr($vval['value'])."',
                  '".DbStr($vval['price_correction'])."',
                {$vval['indent']},
                {$vkey})";
    }

    if(count($query)>0) {
        $query="INSERT INTO {$table_prefix}ec_item_variant (
                  ec_item_id,ec_item_lang,ec_item_variant_description,
                  ec_item_variant_price_correction,ec_item_variant_indent,ec_item_variant_ordering)
                 VALUES ".join(',',$query);
        //prn($query);
        db_execute($query);
    }
}

function set_additional_categories($ec_item_id,$ec_item_lang,$additional_categories) {
    global $table_prefix;
    db_execute("DELETE FROM {$table_prefix}ec_item_category WHERE ec_item_id={$ec_item_id}");

    db_execute("INSERT INTO {$table_prefix}ec_item_category(ec_item_id,ec_category_id)
                SELECT {$ec_item_id} as ec_item_id, ec_category_id
                FROM {$table_prefix}ec_category
                WHERE ec_category_id IN(".join(',',$additional_categories).")");

}


function set_item_tags($site_id,$ec_item_id,$ec_item_lang,$ec_item_tags) {
    global $table_prefix;
    db_execute("DELETE FROM {$table_prefix}ec_item_tags WHERE ec_item_id={$ec_item_id}");
    $tmp=split(',|;',$ec_item_tags);
    $query=Array();
    foreach($tmp as $tag) {
        $tag=trim($tag);
        if(strlen($tag)>0) $query[]="({$ec_item_id},'".DbStr($tag)."',{$site_id})";
    }
    if(count($query)>0) {
        $query="INSERT INTO {$table_prefix}ec_item_tags(ec_item_id,ec_item_tag,site_id) values ".join(',',$query);
        db_execute($query);
    }


}



function set_additional_fields($site_id,$ec_item_id,$ec_item_lang,$additional_fields,$categories){
    global $table_prefix;
    // prn($ec_item_id,$ec_item_lang,$additional_fields,$categories);
    // prn($additional_fields);
    // get allowed category fields
    if(count($categories)==0) return false;

    # ------------------------ list of additional fields - begin ---------------
    # category_id should be set
    $query="SELECT pa.ec_category_id
	        FROM {$table_prefix}ec_category AS pa,{$table_prefix}ec_category AS ch
			WHERE pa.start<=ch.start AND ch.finish<=pa.finish
			  AND pa.site_id={$site_id}
			  AND ch.site_id={$site_id}
			  AND ch.ec_category_id IN(".join(',',$categories).")";
    $tmp=db_getrows($query);
    $cnt=count($tmp);
    for($i=0;$i<$cnt;$i++) {
        $tmp[$i]=$tmp[$i]['ec_category_id'];
    }
    $tmp[]=0;
    $tmp=join(',',$tmp);

    $query="select cif.*
           from {$table_prefix}ec_category_item_field as cif
           where cif.site_id={$site_id}
             and cif.ec_category_id IN ($tmp)
           order by cif.ec_category_item_field_ordering ASC";
    // prn(checkStr($query));
    $tmp=db_getrows($query);
    // prn($tmp);
    $tor=Array();
    foreach($tmp as $fld) {
        if(isset($additional_fields[$fld['ec_category_item_field_id']])){
            $query="DELETE FROM {$table_prefix}ec_category_item_field_value
                    WHERE ec_item_id = $ec_item_id
                      AND ec_item_lang='$ec_item_lang'
                      AND ec_category_item_field_id='{$fld['ec_category_item_field_id']}' ";
            //prn($query);
            db_execute($query);

            $query="INSERT INTO {$table_prefix}ec_category_item_field_value(ec_item_id,ec_item_lang,ec_category_item_field_id,ec_category_item_field_value)
                    VALUES ($ec_item_id,'$ec_item_lang','{$fld['ec_category_item_field_id']}','".DbStr($additional_fields[$fld['ec_category_item_field_id']])."')";
            //prn($query);
            db_execute($query);
        }
    }
    # ------------------------ list of additional fields - end --------------------

}



function recreate_search_index($this_ec_item_info) {
    global $table_prefix;
    $search_index="
            {$this_ec_item_info['ec_item_title']}
            {$this_ec_item_info['ec_item_content']}
            {$this_ec_item_info['ec_item_abstract']}
            {$this_ec_item_info['ec_item_tags']}
            {$this_ec_item_info['ec_item_currency']}
            {$this_ec_item_info['ec_item_material']}
            {$this_ec_item_info['ec_producer_title']}
            {$this_ec_item_info['ec_category_title']}
            {$this_ec_item_info['ec_curency_title']}
            {$this_ec_item_info['ec_item_uid']}
            ";

    if($this_ec_item_info['additional_categories'])
        foreach($this_ec_item_info['additional_categories'] as $ct) {
            $search_index.=" {$ct['ec_category_title']} ";
        }

    if($this_ec_item_info['ec_item_variant'])
        foreach($this_ec_item_info['ec_item_variant'] as $ct) {
            $search_index.=" {$ct['ec_item_variant_description']} ";
        }

    if($this_ec_item_info['ec_category_item_field'])
        foreach($this_ec_item_info['ec_category_item_field'] as $ct) {
            $search_index.=" {$ct['ec_category_item_field_title']} {$ct['ec_category_item_field_value']} ";
        }

    $search_index=preg_replace('/<[^>]+>/',' ',$search_index);
    $search_index=str_replace(Array("\n","\r"),' ',$search_index);
    $search_index=preg_replace('/ +/',' ',$search_index);
    //prn($search_index);

    //prn($this_ec_item_info);
    db_execute("UPDATE  {$table_prefix}ec_item SET ec_item_keywords='".DbStr($search_index)."' WHERE ec_item_id={$this_ec_item_info['ec_item_id']}");

}
?>
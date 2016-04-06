<?php
/*
  Import products from CSV file
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/




$debug=false;
run('site/menu');
run('ec/item/functions');
run('ec/item/importfunctions');

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if(checkInt($this_site_info['id'])<=0) {
    $input_vars['page_title']   = text('Site_not_found');
    $input_vars['page_header']  = text('Site_not_found');
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
$user_cense_level=get_level($site_id);
if($user_cense_level==0) {
    $input_vars['page_title']  = text('Access_denied');
    $input_vars['page_header'] = text('Access_denied');
    $input_vars['page_content']= text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------


$page_content='';

















$file_data=Array();

if(isset($_FILES["userfile"]) && $_FILES['userfile']['size']>0){
    $file_data=file($_FILES["userfile"]['tmp_name']);
}elseif(isset($input_vars['importme']) && strlen(trim($input_vars['importme']))>0){
    $file_data=explode("\n",$input_vars['importme']);
    $cnt=count($file_data);
    for($i=0;$i<$cnt;$i++){
        $file_data[$i].="\n";
    }
    //prn($file_data);
}






// ------------------------- load and parse posted file - begin ----------------
if(count($file_data)>0) {
    //prn($_FILES["userfile"]);


    // -------------------------- parse rows - begin ---------------------------
    // prn($input_vars['fields_terminated_by'],$input_vars['fields_enclosed_by'],$input_vars['escaped_by']);
    $delimiter=str_replace(Array("\\t"),Array("\t"),$input_vars['fields_terminated_by']);
    $enclosure=$input_vars['fields_enclosed_by'];
    $escape=$input_vars['escaped_by'];
    // $file_data=file($_FILES["userfile"]['tmp_name']);
    $cnt=count($file_data);
    for($i=0;$i<$cnt;$i++) {
        $file_data[$i]=parse_csv($file_data[$i],$delimiter,$enclosure,$escape);
    }
    // prn($file_data);
    // -------------------------- parse rows - end -----------------------------

    // ---------------- get structure of the ec_item table - begin ---------
    $field_names=get_importable_fields($site_id);
    //prn($field_names);
    $field_name_options=Array();
    foreach($field_names as $fn) {
        $field_name_options[$fn['Field']]=isset($fn['Label'])?$fn['Label']:text('Import_field_'.$fn['Field']);
    }
    asort($field_name_options);
    //prn($field_name_options);
    // ---------------- get structure of the ec_item table - end -----------

    // draw import table
    $page_content.=draw_file_as_table($site_id,$file_data, $field_name_options);


    // draw default values form
    $page_content.=draw_default_values_form($site_id,$field_name_options);


    $page_content.="  <input type=submit value=\"".text('Import_start')."\"> </form>";



    // ---------------- draw import table - end ---------------------------------
}
// ------------------------- load and parse posted file - end ------------------






































// ------------------------- do import - begin ---------------------------------
if(isset($input_vars['row'])) {
    // prn('Importing ....');


    // -------------- get structure of the {$GLOBALS['table_prefix']}ec_item table - begin -----------
    $field_names=get_importable_fields($site_id);
    $field_name_options=Array();
    foreach($field_names as $fn) {
        $field_name_options[$fn['Field']]=isset($fn['Label'])?$fn['Label']:text('Import_field_'.$fn['Field']);
    }
    asort($field_name_options);
    //prn($field_name_options);
    // -------------- get structure of the {$GLOBALS['table_prefix']}ec_item table - end -------------

    // ------------------------- get imporable columns - begin -----------------
    $columns=Array();
    foreach($input_vars['column_name'] as $i=>$cn) {
        if($cn!='') $columns[$cn]=$i;
    }
    //prn($input_vars['column_name'],$columns);
    //prn(count($columns));
    // ------------------------- get imporable columns - end -------------------


    $n_columns=count($columns);
    $page_content="
        <style>
        .cd{width:150px;}
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
    foreach($columns as $na=>$id) {
        $page_content.=" <td>".$field_name_options[$na]."
                         <input type=hidden name=\"column_name[{$id}]\" value=\"{$na}\">
                         </td>
                ";
    }
    $page_content.="
         </tr>
         ";


    // ------------------- get existing default values - begin -----------------
    $default_value=Array();
    foreach($input_vars['default_value'] as $key=>$val) {
        if(strlen($val)>0) {
            $default_value[$key]=$val;
        }
    }
    // ------------------- get existing default values - end -------------------

    // ------------------------- get row - begin -------------------------------
    $row_i=0;
    foreach($input_vars['row'] as $row) {
        if(!isset($row['import_row']) || $row['import_row']!=1) continue;

        $html=Array();
        $row_data=Array();

        // get posted row
        foreach($columns as $key=>$val) {
            $row_data[$key]=$row[$val];
        }

        // apply default values
        foreach($default_value as $key=>$val) {
            if(!isset($row_data[$key])) {
                $row_data[$key]=$val;
            }
        }


        // prn($row,$row_data);
        // prn($row_data);
        // ------------------------ prepare data - begin -----------------------
        // --------------------- some special columns - begin ------------------

        // -------------------- check producer - begin -------------------------
        // the column can contain the producer_id or the producer name
        $colname='ec_producer_id';
        if(isset($row_data[$colname])) {
            //prn('###################',$row_data[$colname]);
            $row_data[$colname]=get_producer($site_id,$row_data[$colname]);
            //prn($row_data[$colname]);
        }
        // -------------------- check producer - end ---------------------------



        // -------------------- check category - begin -------------------------
        // the column can contain the ec_category_id or ec_category_code
        // or ec_category_title
        $colname='ec_category_id';
        if(isset($row_data[$colname])) {
            $tmp=get_categories($site_id,$row_data[$colname]);
            $row_data[$colname]=$tmp['main_category'];
            $additional_categories=$tmp['additional_categories'];
        }
        // -------------------- check category - end ---------------------------

        // -------------------- check cense level - begin ----------------------
        // $GLOBALS['ec_item_publication_states']
        if(isset($row_data['ec_item_cense_level'])) {
            $tmp=get_cense_level($row_data['ec_item_cense_level']);
            $row_data['ec_item_cense_level']=$tmp['mask'];
            $html['ec_item_cense_level']=$tmp['html'];
        }
        // -------------------- check cense level - end ------------------------

        // -------------- check onnullamount trigger - begin -------------------
        if(isset($row_data['ec_item_onnullamount'])) {
            $data=$row_data['ec_item_onnullamount'];
            if(ereg('^onnullamount_',$data) && function_exists($data)) {
                $row_data['ec_item_onnullamount']=$data;
            }
            else {
                $row_data['ec_item_onnullamount']='onnullamount_none';
            }
        }
        // -------------- check onnullamount trigger - end ---------------------

        // -------------- check variants - begin -------------------------------
        if(isset($row_data['ec_item_variants'])) {
            $row_data['ec_item_variants']=parse_hierarchy($row_data['ec_item_variants']);
            //prn($row_data['ec_item_variants']);
        }
        // -------------- check variants - end ---------------------------------

        // --------------- some special columns - end --------------------------

        // ----------------check data - begin ----------------------------------
        $error_messages='';
        if(isset($columns['ec_item_title'])) {
            $data=$row_data['ec_item_title'];
            if(strlen($data)==0) $error_messages.=" <b style='color:red;'>".text('ec_item_import_error_title_missed')."</b> ";
        }
        else {
            $error_messages.=" <b style='color:red;'>".text('ec_item_import_error_title_missed')."</b> ";
        }
        // ----------------check data - end ------------------------------------

        $product_info=Array();
        foreach($row_data as $name=>$data) {
            $product_info[$name]=$field_names[$name];
            $product_info[$name]['value']=$data;
            if(isset($html[$name])) $product_info[$name]['html']=$html[$name];
            // ------------------ check types - begin --------------------------
            // ------------------ text or string - begin -----------------------
            if(preg_match('/char|text/i',$product_info[$name]['Type'])) {
                $product_info[$name]['dbvalue']="'".\e::db_escape(str_replace(Array("\\n"),Array("\n"),$data))."'";
            }
            // ------------------ text or string - end -------------------------
            // ------------------ number - begin -------------------------------
            elseif(preg_match('/TINYINT|SMALLINT|MEDIUMINT|INT|BIGINT|FLOAT|REAL|DOUBLE|DEC|NUMERIC|DECIMAL/i',$product_info[$name]['Type'])) {
                $product_info[$name]['dbvalue']=1*$data;
            }
            // ------------------ number - end ---------------------------------
            // ------------------ date - begin ---------------------------------
            elseif(preg_match('/date/i',$product_info[$name]['Type'])) {
                if(checkDatetime($data)) {
                    $product_info[$name]['dbvalue']="'".date('Y-m-d H:i:s',strtotime($data))."'";
                }
                else $product_info[$name]['dbvalue']="'".date('Y-m-d H:i:s',time())."'";
            }
            // ------------------ date - end -----------------------------------
            // ------------------ check types - end ----------------------------
        }
        // ------------------ prepare data - end -------------------------------

        if(strlen($error_messages)==0) {
            // prn($tmp);
            $page_content.=" <tr> ";
            // ----------------- check if record exists - begin ---------------
            $found_ec_item_id=false;
            if(isset($product_info['ec_item_uid'])) {
                $found_ec_item_id=\e::db_getonerow("SELECT ec_item_id,ec_item_lang FROM {$table_prefix}ec_item WHERE ec_item_uid<>'' AND ec_item_uid={$product_info['ec_item_uid']['dbvalue']}");
            }
            // ----------------- check if record exists - end -----------------

            //prn($product_info);
            if($found_ec_item_id) {
                //prn(' UPDATING ...');
                $page_content.=" <td style='color:green;'>UPDATE OK</td> ";
                $fld=Array('ec_item_last_change_date=now()');
                foreach($product_info as $tm) {
                    if(isset($tm['dbvalue'])) {
                        $fld[]=$tm['Field'].'='.$tm['dbvalue'];
                    }
                }
                $query="update {$table_prefix}ec_item set ".join(',',$fld).",cache_datetime='2000-01-01 00:00:00' WHERE ec_item_id={$found_ec_item_id['ec_item_id']}  and ec_item_lang='{$found_ec_item_id['ec_item_lang']}'";
                //prn($query);
                \e::db_execute($query);



            }
            else {
                //prn(' INSERTING ...');
                $page_content.=" <td style='color:green;'>INSERT OK</td> ";
                $fld=Array('site_id','ec_item_last_change_date');
                $val=Array($site_id,'now()');
                if(!isset($product_info['ec_item_uid'])) {
                    $fld[]='ec_item_uid';
                    $val[]="'".md5($_SESSION['user_info']['user_login'].$_SERVER['REMOTE_ADDR'].time())."'";
                }

                if(!isset($product_info['ec_item_lang'])) {
                    $fld[]='ec_item_lang';
                    $val[]="'".default_language."'";
                }
                if(strlen($product_info['ec_item_lang'])==0) {
                    $$product_info['ec_item_lang']['dbvalue']="'".default_language."'";
                }

                //
                foreach($product_info as $tm) {
                    if(isset($tm['dbvalue'])) {
                        $fld[]=$tm['Field'];
                        $val[]=$tm['dbvalue'];
                    }
                }
                $query="insert into {$table_prefix}ec_item (".join(',',$fld).") values(".join(',',$val).")";
                //prn($query);
                \e::db_execute($query);
                $found_ec_item_id=\e::db_getonerow("SELECT ec_item_id, ec_item_lang FROM {$table_prefix}ec_item WHERE LAST_INSERT_ID() = ec_item_id");
            }


            // set additional categories
            if(isset($additional_categories) && count($additional_categories)>0) {
                set_additional_categories($found_ec_item_id['ec_item_id'],$found_ec_item_id['ec_item_lang'],$additional_categories);
            }

            // set item tags
            if(isset($row_data['ec_item_tags'])) {
                set_item_tags($site_id,$found_ec_item_id['ec_item_id'],$found_ec_item_id['ec_item_lang'],$row_data['ec_item_tags']);
            }


            // set item variants
            if(isset($row_data['ec_item_variants'])) {
                set_item_variants($found_ec_item_id['ec_item_id'],$found_ec_item_id['ec_item_lang'],$row_data['ec_item_variants']);
            }

            // set additional fields
            if(isset($row_data['ec_category_id'])) {
                $additional_fields=Array();
                foreach($row_data as $key=>$val) {
                    if(preg_match('/^ec_category_item_field/i',$key)) {
                        $field_id=str_replace(Array('ec_category_item_field[',']'),'',$key);
                        $additional_fields[$field_id]=$val;
                    }
                }
                // prn($additional_fields);
                if(count($additional_fields)>0) {
                    set_additional_fields(
                            $site_id,
                            $found_ec_item_id['ec_item_id'],
                            $found_ec_item_id['ec_item_lang'],
                            $additional_fields,
                            array_merge(Array($row_data['ec_category_id']),$additional_categories));
                }
            }


            // re-create search index
            $this_ec_item_info=get_ec_item_info($found_ec_item_id['ec_item_id'],$found_ec_item_id['ec_item_lang']);
            recreate_search_index($this_ec_item_info);





            foreach($columns as $na=>$id) {
                $page_content.=" <td valign=top> ".checkStr($row[$id])."</td>";
            }
            $page_content.="</tr>";
        }
        else {
            // --------------- draw form to re-import - begin -----------------
            $page_content.=" <tr>  <td valign=top><input type=checkbox name=row[$row_i][import_row] checked value=1><br>{$error_messages}</td> ";
            foreach($columns as $na=>$id) {
                $page_content.=" <td valign=top> <input class=cd name=\"row[$row_i][$id]\" type=text value=\"".checkStr(isset($product_info[$na]['html'])?$product_info[$na]['html']:$product_info[$na]['value'])."\"></td>";
            }
            $page_content.=" </tr> ";
            $row_i++;
            // --------------- draw form to re-import - end -------------------
        }

    }
    // ------------------------- get row - end -------------------------------

    $page_content.="
         </table>
         <input type=submit value=\"".text('Import_start')."\">
         </form>
      ";
}
// ------------------------- do import - end -----------------------------------

































// ------------------ draw form to upload file - begin -------------------------
$form_file_upload="

   <style>
   .noborder td {border-width:0px;}
   </style>
   <h2 style='text-align:left;'>".text('Import_upload_file')."</h2>
   <form enctype=\"multipart/form-data\" action=\"index.php\" method=\"post\">
       <input type=\"hidden\" name=\"action\" value=\"ec/item/import\" />
       <input type=\"hidden\" name=\"site_id\" value=\"$site_id\" />
       <table class=noborder>
       <tr><td>".text('CSV_Escaped_by').":</td><td><input type=text size=3 name=escaped_by value=\"\\\"></td></tr>
       <tr><td>".text('CSV_fields_terminated_by').":</td><td><input type=text size=3 name=fields_terminated_by value=\"\\t\"></td></tr>
       <tr><td>".text('CSV_fields_enclosed_by').":</td><td><input type=text size=3 name=fields_enclosed_by value=\"\"></td></tr>
       </table><br/>
       ".text('Import_CSV_file').":
       <input name=\"userfile\" type=\"file\" />
       <input type=\"submit\" value=\"".text('Upload')."\" /><br/><br/>
       ".text('ec_item_import_data').":<br/>
       <textarea name=importme style='width:95%;height:400px;' WRAP=OFF ></textarea><br/>
       <input type=\"submit\" value=\"".text('ec_item_import_parse')."\" /><br/><br/>
   </form>
   ".text('ec_item_import_csv_sample')."
    <h4 style='text-align:left;'>".text('OnNullAmount')."</h4>
    <p>
           <ul>
         ";
$defined_functions=get_defined_functions();
$defined_functions=$defined_functions['user'];
$cnt=count($defined_functions);
for($i=0;$i<$cnt;$i++) {
    if(preg_match('/^onnullamount_/',$defined_functions[$i])) {
        $form_file_upload.="<li><b>{$defined_functions[$i]}</b> - ".text($defined_functions[$i])."</li>";
    }
}
unset($defined_functions);
$form_file_upload.="
           </ul>
           </p>
         ";
// ------------------ draw form to upload file - end ---------------------------


$input_vars['page_header']=
        $input_vars['page_title']=text('Import_products');

$input_vars['page_content']= "
        $page_content
        $form_file_upload
        ";//$re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------

$sti=$text['Site'].' "'. $this_site_info['title'].'"';
$Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
$input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------


?>
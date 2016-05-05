<?php

//----------------- check values - begin ---------------------------------------
$all_is_ok = true;
$message = '';

//--------------- check item title - begin -------------------------------------
$this_ec_item_info['ec_item_title'] = trim(strip_tags($input_vars['ec_item_title']));
if (strlen($this_ec_item_info['ec_item_title']) == 0) {
    $message.="{$text['ERROR']} : Title_is_empty<br>\n";
    $all_is_ok = false;
}
//--------------- check item title - end ---------------------------------------
//------------------ check language - begin ------------------------------------
$lng = $input_vars['ec_item_lang_new'];
if ($lng != $this_ec_item_info['ec_item_lang']) {
    //-------------------- get existing page languages - begin -----------------
    $query = "SELECT ec_item_lang FROM <<tp>>ec_item WHERE ec_item_id={$this_ec_item_info['ec_item_id']}";
    $tmp = \e::db_getrows($query);
    $existins_langs = Array();
    foreach ($tmp as $ln) {
        $existins_langs[] = $ln['ec_item_lang'];
    }
    //-------------------- get existing page languages - end -------------------
    //-------------------- get available languages - begin ---------------------
    $existins_langs[] = '';
    $query = "SELECT id
                FROM <<tp>>languages
                WHERE is_visible=1 AND id NOT IN('" . join("','", $existins_langs) . "')";
    $tmp = \e::db_getrows($query);
    $avail_lang = Array();
    foreach ($tmp as $ln) {
        $avail_lang[] = $ln['id'];
    }
    //-------------------- get available languages - end -----------------------

    if (!in_array($lng, $avail_lang)) {
        $message.="{$text['ERROR']} : Item_in_selected_language_already_axists<br>\n";
        $all_is_ok = false;
        $lng = $this_ec_item_info['ec_item_lang'];
    }
}
//------------------ check language - end --------------------------------------
//------------------ check abstract - begin ------------------------------------
if (strlen($input_vars['ec_item_abstract']) > 512) {
    $message.="{$text['ERROR']} : {$text['Page_abstract_is_too_long']}<br>\n";
    $all_is_ok = false;
}
$this_ec_item_info['ec_item_abstract'] = shorten($input_vars['ec_item_abstract'], 512);
//------------------ check abstract - end --------------------------------------
//------------------ check page content - begin --------------------------------
$this_ec_item_info['ec_item_content'] = $input_vars['ec_item_content'];
//------------------ check page content - end ----------------------------------
//------------------ load page from uploaded file - begin ----------------------
if (isset($_FILES['page_upload']) && $_FILES['page_upload']['size'] > 0) {
    $this_ec_item_info['ec_item_content'] = join('', file($_FILES['page_upload']['tmp_name']));
    if (eregi('<body', $this_ec_item_info['ec_item_content'])) {
        $this_ec_item_info['ec_item_content'] = stristr($this_ec_item_info['ec_item_content'], '<body');
    }
    if (eregi('</body', $this_ec_item_info['ec_item_content'])) {
        $this_ec_item_info['ec_item_content'] = eregi_replace('</body(.|\n|\r)*', '</body>', $this_ec_item_info['ec_item_content']);
    }

    unset($_FILES['page_upload']);
}
//------------------ load page from uploaded file - end ------------------------

$this_ec_item_info['site_id'] = $this_site_info['id'];
$this_ec_item_info['ec_item_cense_level'] = (isset($input_vars['ec_item_cense_level'])) ? ( (int) $input_vars['ec_item_cense_level'] ) : 0;
$this_ec_item_info['ec_item_last_change_date'] = date('Y-m-d h:i:s');

$this_ec_item_info['ec_category_id'] = (int) $input_vars['ec_category_id'];
$this_ec_item_info['ec_producer_id'] = (int) $input_vars['ec_producer_id'];


$this_ec_item_info['ec_item_amount'] = (int) $input_vars['ec_item_amount'];
#$this_ec_item_info['ec_item_onnullamount']=$input_vars['ec_item_onnullamount'];
#if(!function_exists($this_ec_item_info['ec_item_onnullamount']))
#{
#      $message.="{$text['ERROR']} : {$this_ec_item_info['ec_item_onnullamount']} does not exists<br>\n";
#      $all_is_ok=false;
#}
// check if other items has the same UID
$this_ec_item_info['ec_item_uid'] = trim($input_vars['ec_item_uid']);

# ------------------------ ec_item_tags - begin --------------------------------
$this_ec_item_info['ec_item_tags'] = trim($input_vars['ec_item_tags']);
if (strlen($this_ec_item_info['ec_item_tags']) > 0) {
    //prn($this_ec_item_info['ec_item_tags']);
    $this_ec_item_info['ec_item_tags'] = preg_split('/,|;/', $this_ec_item_info['ec_item_tags']);
    //prn($this_ec_item_info['ec_item_tags']);
    $cnt = count($this_ec_item_info['ec_item_tags']);
    for ($i = 0; $i < $cnt; $i++) {
        $this_ec_item_info['ec_item_tags'][$i] = trim($this_ec_item_info['ec_item_tags'][$i]);
    }
    $this_ec_item_info['ec_item_tags'] = join(',', $this_ec_item_info['ec_item_tags']);
}
//prn($this_ec_item_info['ec_item_tags']);
# ------------------------ ec_item_tags - end ----------------------------------
//$this_ec_item_info['ec_item_mark']=$input_vars['ec_item_mark'];

$this_ec_item_info['ec_item_price'] = (float) str_replace(',', '.', $input_vars['ec_item_price']);
$this_ec_item_info['ec_item_currency'] = $this_site_info['ec_currency']; //$input_vars['ec_item_currency'];

$this_ec_item_info['ec_item_material'] = $input_vars['ec_item_material'];
$this_ec_item_info['ec_item_mark'] = $input_vars['ec_item_mark'];

//
$this_ec_item_info['ec_item_size'] = ec_item_size_check($input_vars['ec_item_size'][0], $input_vars['ec_item_size'][1], $input_vars['ec_item_size'][2], $input_vars['ec_item_size'][3]);

$this_ec_item_info['ec_item_weight'] = Array(checkFloat($input_vars['ec_item_weight'][0]), $input_vars['ec_item_weight'][1]);

$this_ec_item_info['ec_item_ordering'] = (int) $input_vars['ec_item_ordering'];

// check ec_item_code
$this_ec_item_info['ec_item_code'] = \core\fileutils::encode_dir_name($input_vars['ec_item_code']);
if (strlen($this_ec_item_info['ec_item_code']) == 0) {
    $this_ec_item_info['ec_item_code'] = \core\fileutils::encode_dir_name($input_vars['ec_item_title']);
}
// ec_item_code must be unique
$query = "SELECT * from  <<tp>>ec_item "
        . "WHERE ec_item_code='" . \e::db_escape($this_ec_item_info['ec_item_code']) . "'"
        . " AND ec_item_id<>{$this_ec_item_info['ec_item_id']}";
// prn($query);
// prn($input_vars);
$ec_item_code_duplicate =\e::db_getonerow($query);
if ($ec_item_code_duplicate) {
    $this_ec_item_info['ec_item_code'].='-' . $this_ec_item_info['ec_item_id'] . '-' . $this_ec_item_info['ec_item_lang'];
}

// check ec_item_variants
$this_ec_item_info['ec_item_variants'] = $input_vars['ec_item_variants'];

//----------------- check values - end -----------------------------------------
//----------------- save - begin -----------------------------------------------
if ($all_is_ok) {

    // ----------------- resizing image to create small image - end ------------

    $message.="<font color=green>{$text['Page_saved_successfully']}</font><br>\n";

    # ---------------- do file upload - begin -------------------------------
    $data = date('Y-m-d-h-i-s');
    //prn($this_ec_item_info["ec_item_img"]); die();
    for ($qq = 1; $qq <= 6; $qq++) {
        if (isset($_FILES["ec_item_img$qq"])) {
            $photos = $_FILES["ec_item_img$qq"];
            if ($photos['size'] > 0 && preg_match("/(jpg|gif|png|jpeg)$/", $photos['name'], $regs)) {
                # get file extension
                $file_extension = ".{$regs[1]}";
                # create file name
                

                # create directory
                $relative_dir = date('Y') . '/' . date('m');
                $site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
                \core\fileutils::path_create($site_root_dir, "/gallery/$relative_dir/");

                # copy uploaded file
                //copy($photos['tmp_name'], "$site_root_dir/gallery/$relative_dir/$big_image_file_name");

                $big_image_file_name = "{$this_ec_item_info['site_id']}-{$data}-big-" . \core\fileutils::encode_file_name($photos['name']);
                $big_file_path="$site_root_dir/gallery/$relative_dir/$big_image_file_name";
                ec_img_resize($photos['tmp_name'], $big_file_path, \e::config('gallery_big_image_width'), \e::config('gallery_big_image_height'), "resample");

                $small_image_file_name = "{$this_ec_item_info['site_id']}-{$data}-small-" . \core\fileutils::encode_file_name($photos['name']);
                $small_file_path="$site_root_dir/gallery/$relative_dir/$small_image_file_name";
                ec_img_resize($photos['tmp_name'], $small_file_path, \e::config('gallery_small_image_width'), \e::config('gallery_small_image_height'), "circumscribe");

                $this_ec_item_info["ec_item_img"][] = Array("gallery/$relative_dir/$small_image_file_name","gallery/$relative_dir/$big_image_file_name");
            }
        }
    }
    //
    # ---------------- do file upload - end ---------------------------------
    # ---------------- delete files - begin ------------------------------------
    $site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
    foreach ($input_vars as $key => $val) {
        if (preg_match('/^ec_item_imgdelete/', $key)) {
            $nimage = str_replace('ec_item_imgdelete', '', $key);
            

            $path = $site_root_dir . '/' . $this_ec_item_info["ec_item_img"][$nimage]['small'];
            if (is_file($path)) {
                unlink($path);
            }
            $path = $site_root_dir . '/' . $this_ec_item_info["ec_item_img"][$nimage]['big'];
            if (is_file($path)) {
                unlink($path);
            }
            unset($this_ec_item_info["ec_item_img"][$nimage]);
        }
    }
    # ---------------- delete files - end --------------------------------------
    # ---------------- reorder images begin - begin ----------------------------
    $site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
    //prn($input_vars);
    foreach ($input_vars as $key => $val) {
        if (preg_match('/^ec_item_imgup/', $key)) {
            $nimage = str_replace('ec_item_imgup', '', $key);
            $pimage = $nimage - 1;
            if (isset($this_ec_item_info["ec_item_img"][$pimage])) {
                $tmp = $this_ec_item_info["ec_item_img"][$nimage];
                $this_ec_item_info["ec_item_img"][$nimage] = $this_ec_item_info["ec_item_img"][$pimage];
                $this_ec_item_info["ec_item_img"][$pimage] = $tmp;
            }
        }
        if (preg_match('/^ec_item_imgdown/', $key)) {
            $nimage = str_replace('ec_item_imgdown', '', $key);
            $pimage = $nimage + 1;
            if (isset($this_ec_item_info["ec_item_img"][$pimage])) {
                $tmp = $this_ec_item_info["ec_item_img"][$nimage];
                $this_ec_item_info["ec_item_img"][$nimage] = $this_ec_item_info["ec_item_img"][$pimage];
                $this_ec_item_info["ec_item_img"][$pimage] = $tmp;
            }
        }
        if (preg_match('/^ec_item_img_label_/', $key)) {
            $nimage = str_replace('ec_item_img_label_', '', $key);
            $this_ec_item_info["ec_item_img"][$nimage]['label']=$val;
        }
    }
    # ---------------- reorder images begin - end ------------------------------

    if ($this_ec_item_info['ec_item_id'] > 0) {

        $ec_item_img=array_values($this_ec_item_info['ec_item_img']);
        //prn($ec_item_img);
        $cnt=count($ec_item_img);
        for($i=0;$i<$cnt;$i++){
            $ec_item_img[$i]=join("\t",$ec_item_img[$i]);
        }
        $ec_item_img=join("\n",$ec_item_img);
        
        $query = "UPDATE <<tp>>ec_item
                   SET
                      ec_item_lang='{$lng}',
	              site_id='{$this_ec_item_info['site_id']}',
                      ec_item_title='" . \e::db_escape($this_ec_item_info['ec_item_title']) . "',
                      ec_item_mark='" . \e::db_escape($this_ec_item_info['ec_item_mark']) . "',
                      ec_item_content='" . \e::db_escape($this_ec_item_info['ec_item_content']) . "',
                      ec_item_cense_level='{$this_ec_item_info['ec_item_cense_level']}',
                      ec_item_abstract='" . \e::db_escape($this_ec_item_info['ec_item_abstract']) . "',
                      ec_item_tags='" . \e::db_escape($this_ec_item_info['ec_item_tags']) . "',
                      ec_item_price={$this_ec_item_info['ec_item_price']},
                      ec_item_currency='" . \e::db_escape($this_ec_item_info['ec_item_currency']) . "',
                      ec_item_amount={$this_ec_item_info['ec_item_amount']},
                      ec_producer_id={$this_ec_item_info['ec_producer_id']},
                      ec_category_id={$this_ec_item_info['ec_category_id']},
                      ec_item_code='" . \e::db_escape($this_ec_item_info['ec_item_code']) . "',
                      ec_item_onnullamount='" . \e::db_escape($this_ec_item_info['ec_item_onnullamount']) . "',
                      ec_item_uid='" . \e::db_escape($this_ec_item_info['ec_item_uid']) . "',
                      ec_item_material='" . \e::db_escape($this_ec_item_info['ec_item_material']) . "',
                      ec_item_size='{$this_ec_item_info['ec_item_size'][0]}x{$this_ec_item_info['ec_item_size'][1]}x{$this_ec_item_info['ec_item_size'][2]} {$this_ec_item_info['ec_item_size'][3]}',
                      ec_item_weight='{$this_ec_item_info['ec_item_weight'][0]} {$this_ec_item_info['ec_item_weight'][1]}',
                      ec_item_img='" . \e::db_escape($ec_item_img) . "',
                      cache_datetime='2001-01-01 00:00:00',
                      ec_item_variants='" . \e::db_escape($this_ec_item_info['ec_item_variants']) . "',
                      ec_item_ordering={$this_ec_item_info['ec_item_ordering']}

           WHERE ec_item_id='{$this_ec_item_info['ec_item_id']}' AND ec_item_lang='{$this_ec_item_info['ec_item_lang']}'";
        //prn(htmlspecialchars($query));
        //
        \e::db_execute($query);

        // ------------- update language in related tables - begin -------------
        if ($this_ec_item_info['ec_item_lang'] != $lng) {
            // <<tp>>ec_item_comment
            $query = "UPDATE <<tp>>ec_item_comment SET ec_item_lang='$lng' WHERE ec_item_id='{$this_ec_item_info['ec_item_id']}' AND ec_item_lang='{$this_ec_item_info['ec_item_lang']}'";
            \e::db_execute($query);

            // <<tp>>ec_item_variant
            $query = "UPDATE <<tp>>ec_item_variant SET ec_item_lang='$lng' WHERE ec_item_id='{$this_ec_item_info['ec_item_id']}' AND ec_item_lang='{$this_ec_item_info['ec_item_lang']}'";
            \e::db_execute($query);

            // <<tp>>ec_category_item_field_value
            $query = "UPDATE <<tp>>ec_category_item_field_value SET ec_item_lang='$lng' WHERE ec_item_id='{$this_ec_item_info['ec_item_id']}' AND ec_item_lang='{$this_ec_item_info['ec_item_lang']}'";
            \e::db_execute($query);
        }
        // ------------- update language in related tables - end ---------------
        $this_ec_item_info['ec_item_lang'] = $lng;
    } else {
        $query = "SELECT max(ec_item_id) as newid FROM <<tp>>ec_item";
        $newid =\e::db_getonerow($query);
        //prn($newid);
        $this_ec_item_info['ec_item_id'] = $newid['newid'] + 1;
        if (!isset($this_ec_item_info['ec_item_img'])) {
            $this_ec_item_info['ec_item_img'] = Array();
        }

        $query = "insert into <<tp>>ec_item (
                        ec_item_id,
                        ec_item_lang,
	                site_id,
                        ec_item_title,
                        ec_item_content,
                        ec_item_cense_level,
                        ec_item_last_change_date,
                        ec_item_abstract,
                        ec_item_tags,
                        ec_item_price,
                        ec_item_currency,
                        ec_item_amount,
                        ec_producer_id,
                        ec_category_id,
                        ec_item_onnullamount,
                        ec_item_uid,
                        ec_item_img,
                        ec_item_size,
                        ec_item_weight,
                        ec_item_material,
                        ec_item_variants,
                        ec_item_ordering,
                        ec_item_code
                        )
	               values(
                           {$this_ec_item_info['ec_item_id']},
	                   '{$lng}',
	                   '{$this_ec_item_info['site_id']}',
	                   '" . \e::db_escape($this_ec_item_info['ec_item_title']) . "',
	                   '" . \e::db_escape($this_ec_item_info['ec_item_content']) . "',
	                   '{$this_ec_item_info['ec_item_cense_level']}',
	                   '" . $this_ec_item_info['ec_item_last_change_date'] . "',
	                   '" . \e::db_escape($this_ec_item_info['ec_item_abstract']) . "',
	                   '" . \e::db_escape($this_ec_item_info['ec_item_tags']) . "',
                           {$this_ec_item_info['ec_item_price']},
	                   '" . \e::db_escape($this_ec_item_info['ec_item_currency']) . "',
                           {$this_ec_item_info['ec_item_amount']},
                           {$this_ec_item_info['ec_producer_id']},
                           {$this_ec_item_info['ec_category_id']},
	                   '" . \e::db_escape($this_ec_item_info['ec_item_onnullamount']) . "',
                           '" . \e::db_escape($this_ec_item_info['ec_item_uid']) . "',
                           '" . \e::db_escape(join("\n", $this_ec_item_info['ec_item_img'])) . "',
                           '{$this_ec_item_info['ec_item_size'][0]}x{$this_ec_item_info['ec_item_size'][1]}x{$this_ec_item_info['ec_item_size'][2]} {$this_ec_item_info['ec_item_size'][3]}',
                           '{$this_ec_item_info['ec_item_weight'][0]} {$this_ec_item_info['ec_item_weight'][1]}',
                           '" . \e::db_escape($this_ec_item_info['ec_item_material']) . "',
                           '" . \e::db_escape($this_ec_item_info['ec_item_variants']) . "',
                           {$this_ec_item_info['ec_item_ordering']},
                           '" . \e::db_escape($this_ec_item_info['ec_item_code']) . "'
	               )
           ";
        //prn($query);
        // ec_item_mark,
        // '".DbStr($this_ec_item_info['ec_item_mark'])."',
        \e::db_execute($query);
        $this_ec_item_info['ec_item_lang'] = $lng;
    }

    # ---------------- update tags - begin -------------------------------------
    \e::db_execute("DELETE FROM <<tp>>ec_item_tags WHERE ec_item_id={$this_ec_item_info['ec_item_id']}");
    $tmp = preg_split('/,|;/', $this_ec_item_info['ec_item_tags']);
    $query = Array();
    foreach ($tmp as $tag) {
        $tag = trim($tag);
        if (strlen($tag) > 0) {
            $query[] = "({$this_ec_item_info['ec_item_id']},'" . \e::db_escape($tag) . "',{$this_ec_item_info['site_id']})";
        }
    }
    if (count($query) > 0) {
        $query = "INSERT INTO <<tp>>ec_item_tags(ec_item_id,ec_item_tag,site_id) values " . join(',', $query);
        \e::db_execute($query);
    }
    # ---------------- update tags - end ---------------------------------------
    # ---------------- save variants - begin -----------------------------------
    $ec_item_variants = Array();
    if (strlen(trim($this_ec_item_info['ec_item_variants'])) > 0) {
        # parse variants
        // split strings
        // for each string get
        // - ec_item_variant_ordering,
        // - ec_item_variant_indent,
        // - ec_item_variant_code
        // - ec_item_variant_price_correction,
        // - ec_item_variant_description,
        $variant_codes = explode("\n", $this_ec_item_info['ec_item_variants']);

        foreach ($variant_codes as $ec_item_variant_ordering => $variant_src) {
            $tmp = ltrim($variant_src);
            if (preg_match("/^#/", $tmp)) {
                continue;
            }
            $val = Array(
                'ec_item_variant_ordering' => $ec_item_variant_ordering,
                'ec_item_variant_indent' => 0,
                'ec_item_variant_code' => $ec_item_variant_ordering,
                'ec_item_variant_price_correction' => '+0',
                'ec_item_variant_description' => ''
            );

            $val['ec_item_variant_indent'] = strlen($variant_src) - strlen($tmp);

            $tmp = preg_split("/\\(|\\)/", $tmp);

            $val['ec_item_variant_code'] = trim($tmp[0]);

            // price correction
            if (isset($tmp[1])) {
                $val['ec_item_variant_price_correction'] = $tmp[1];
            }

            if (isset($tmp[2])) {
                $val['ec_item_variant_description'] = trim($tmp[2]);
            }
            if (strlen($val['ec_item_variant_description']) == 0) {
                $val['ec_item_variant_description'] = $val['ec_item_variant_code'];
            }
            // prn($val);
            $ec_item_variants[] = $val;
        }

        // ---------------------- shift indent - begin -----------------------------
        $maximal_indent = 0;
        for ($i = 0, $cnt = count($ec_item_variants); $i < $cnt; $i++) {
            if ($maximal_indent < $ec_item_variants[$i]['ec_item_variant_indent']) {
                $maximal_indent = $ec_item_variants[$i]['ec_item_variant_indent'];
            }
        }
        $minimal_indent = $maximal_indent;
        for ($i = 0, $cnt = count($ec_item_variants); $i < $cnt; $i++) {
            if ($minimal_indent > $ec_item_variants[$i]['ec_item_variant_indent']) {
                $minimal_indent = $ec_item_variants[$i]['ec_item_variant_indent'];
            }
        }
        for ($i = 0, $cnt = count($ec_item_variants); $i < $cnt; $i++) {
            $ec_item_variants[$i]['ec_item_variant_indent']-=$minimal_indent;
        }
        // ---------------------- shift indent - end -------------------------------
        // prn($ec_item_variants);
        // exit();
        // extract old variants
        $tmp = \e::db_getrows("SELECT * FROM <<tp>>ec_item_variant
                                    WHERE ec_item_id={$this_ec_item_info['ec_item_id']}
                                      AND ec_item_lang='{$this_ec_item_info['ec_item_lang']}'");
        $old_item_variants = Array();
        foreach ($tmp as $val) {
            $old_item_variants[$val['ec_item_variant_code']] = $val;
        }
        // prn($old_item_variants);

        for ($i = 0, $cnt = count($ec_item_variants); $i < $cnt; $i++) {
            if (isset($old_item_variants[$ec_item_variants[$i]['ec_item_variant_code']])) {
                $ec_item_variants[$i]['ec_item_variant_id'] = $old_item_variants[$ec_item_variants[$i]['ec_item_variant_code']]['ec_item_variant_id'];
            } else {
                $ec_item_variants[$i]['ec_item_variant_id'] = 'null';
            }
            $ec_item_variants[$i]['ec_item_id'] = $this_ec_item_info['ec_item_id'];
            $ec_item_variants[$i]['ec_item_lang'] = $this_ec_item_info['ec_item_lang'];
        }
        // prn($ec_item_variants);
        // exit();
    }

    // delete old variants
    \e::db_execute("DELETE FROM <<tp>>ec_item_variant
                WHERE ec_item_id={$this_ec_item_info['ec_item_id']}
                  AND ec_item_lang='{$this_ec_item_info['ec_item_lang']}'");

    // insert updated variants
    for ($i = 0, $cnt = count($ec_item_variants); $i < $cnt; $i++) {
        $val = $ec_item_variants[$i];
        $query = "INSERT INTO <<tp>>ec_item_variant(
            ec_item_variant_ordering,
            ec_item_variant_indent,
            ec_item_variant_code,
            ec_item_variant_price_correction,
            ec_item_variant_description,
            ec_item_variant_id,
            ec_item_id,
            ec_item_lang
        ) VALUES(
            " . abs((int) $val['ec_item_variant_ordering']) . ",
            " . abs((int) $val['ec_item_variant_indent']) . ",
            '" . \e::db_escape($val['ec_item_variant_code']) . "',
            '" . \e::db_escape($val['ec_item_variant_price_correction']) . "',
            '" . \e::db_escape($val['ec_item_variant_description']) . "',
            " . abs((int) $val['ec_item_variant_id']) . ",
            " . abs((int) $val['ec_item_id']) . ",
            '" . \e::db_escape($val['ec_item_lang']) . "'
        )";
        \e::db_execute($query);
    }
    $this_ec_item_info['ec_item_variant'] = $ec_item_variants;
    # ---------------- save variants - end -------------------------------------
    # ---------------- save additional fields - begin --------------------------
    \e::db_execute("DELETE FROM <<tp>>ec_category_item_field_value
                WHERE ec_item_id={$this_ec_item_info['ec_item_id']}
                  and ec_item_lang='{$this_ec_item_info['ec_item_lang']}'");
    //prn($input_vars['ec_item_extra_field']); die();
    $keep_keys = Array();
    if (isset($input_vars['ec_item_extra_field'])) {


        foreach ($input_vars['ec_item_extra_field'] as $key => $val) {
            $keep_keys[] = $key;
            if ($val['type'] == 'number')
                $val['value'] = checkFloat($val['value']);
            $query = "replace <<tp>>ec_category_item_field_value(
                                	ec_item_id,
	                                ec_item_lang,
                                	ec_category_item_field_id,
	                                ec_category_item_field_value
                    )values(
                    {$this_ec_item_info['ec_item_id']},
                     '{$this_ec_item_info['ec_item_lang']}',
                    {$key},
                     '" . \e::db_escape($val['value']) . "'
                    )";
            //prn($query);
            \e::db_execute($query);
        }
    }

    # ---------------------- delete unused fields - begin ----------------------
    if (count($keep_keys) > 0) {
        $query = "delete from <<tp>>ec_category_item_field_value
                WHERE ec_item_id={$this_ec_item_info['ec_item_id']}
                      and ec_item_lang='{$this_ec_item_info['ec_item_lang']}'
                      and ec_category_item_field_id not in (" . join(',', $keep_keys) . ")";
        \e::db_execute($query);
    }
    # ---------------------- delete unused fields - end ------------------------
    # ---------------- save additional fields - end ----------------------------
    # ---------------- save additional categories - begin ----------------------
    \e::db_execute("DELETE FROM <<tp>>ec_item_category WHERE ec_item_id={$this_ec_item_info['ec_item_id']}");

    if (isset($input_vars['additional_category']) && is_array($input_vars['additional_category'])) {
        $cats = Array();
        //prn($input_vars['additional_category']);//die();
        foreach ($input_vars['additional_category'] as $_id) {
            if ($this_ec_item_info['ec_category_id'] != $_id && $_id > 0) {
                $cats[] = "({$this_ec_item_info['ec_item_id']}," . ((int) $_id) . ")";
            }
        }
        if (count($cats) > 0) {
            $query = "INSERT INTO <<tp>>ec_item_category(ec_item_id,ec_category_id)
                     VALUES " . join(',', $cats);
            \e::db_execute($query);
            //prn($query);//die();
        }
    }
    # ---------------- save additional categories - end ------------------------
    # ------------------ send notification - begin --------------------------
    if (isset($_REQUEST['notify']) && is_array($_REQUEST['notify'])) {
        run('lib/mailing');
        run('lib/class.phpmailer');
        run('lib/class.smtp');
        $lnk = site_root_URL . "/index.php?action=ec/item/edit&ec_item_id={$this_ec_item_info['ec_item_id']}&ec_item_lang={$this_ec_item_info['ec_item_lang']}";
        foreach ($this_site_info['managers'] as $mng) {
            if (isset($_REQUEST['notify'][$mng['id']])) {

                $mng_body = "Dear {$mng['full_name']} <br/>\n<br/>\n<br/>\n" .
                        " I have changed the page. <br/>\n" .
                        " Please, review it.<br/>\n" .
                        " <br/>\n" .
                        " ==================================================<br/>\n" .
                        " {$this_ec_item_info['ec_item_title']}<br/>\n" .
                        " --------------------------------------------------<br/>\n" .
                        " {$this_ec_item_info['ec_item_abstract']}<br/>\n" .
                        " --------------------------------------------------<br/>\n" .
                        " {$this_ec_item_info['ec_item_content']}<br/>\n" .
                        " ==================================================<br/>\n" .
                        " Click the link below to approve changes<br/>\n" .
                        " <a href=$lnk>$lnk</a><br/>\n" .
                        " <br/>\n" .
                        " Yours faithfully <br/>\n" .
                        " {$_SESSION['user_info']['full_name']}<br/>\n" .
                        " {$_SESSION['user_info']['email']}<br/>\n" .
                        " ";
                if (IsHTML != '1')
                    $mng_body = wordwrap(strip_tags(ereg_replace('<br/?>', "\n", $mng_body)), 80, "\n");
                my_mail($mng['email'], site_root_URL . ' : Page was changed', $mng_body);
            }
        }
    }
    # ------------------ send notification - end ----------------------------
}
else {
    $message = "<font color=red>{$message}</font><br>\n";
}
//----------------- save - end -----------------------------------------------


$this_ec_item_info = get_ec_item_info($this_ec_item_info['ec_item_id'], $lng);

// ---------------------- update search index - begin --------------------------
$search_index = "
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

if ($this_ec_item_info['additional_categories'])
    foreach ($this_ec_item_info['additional_categories'] as $ct) {
        $search_index.=" {$ct['ec_category_title']} ";
    }

if ($this_ec_item_info['ec_item_variant'])
    foreach ($this_ec_item_info['ec_item_variant'] as $ct) {
        $search_index.=" {$ct['ec_item_variant_description']} ";
    }

if ($this_ec_item_info['ec_category_item_field'])
    foreach ($this_ec_item_info['ec_category_item_field'] as $ct) {
        $search_index.=" {$ct['ec_category_item_field_title']} {$ct['ec_category_item_field_value']} ";
    }

$search_index = preg_replace('/<[^>]+>/', ' ', $search_index);
$search_index = str_replace(Array("\n", "\r"), ' ', $search_index);
$search_index = preg_replace('/ +/', ' ', $search_index);
//prn($search_index);
//prn($this_ec_item_info);
\e::db_execute("UPDATE  <<tp>>ec_item SET ec_item_keywords='" . \e::db_escape($search_index) . "' WHERE ec_item_id={$this_ec_item_info['ec_item_id']}");
// ---------------------- update search index - end ----------------------------
//die();

$this_ec_item_info['message'] = $message;
// prn($this_ec_item_info);
//http://127.0.0.1/cms/index.php?action=ec/item/edit&site_id=1&ec_item_id=4&ec_item_lang=ukr
//header("Location:".site_URL."?action=ec/item/edit&site_id={$this_ec_item_info['site_id']}&ec_item_id={$this_ec_item_info['ec_item_id']}&ec_item_lang={$this_ec_item_info['ec_item_lang']}");
//exit();
return $this_ec_item_info;
?>
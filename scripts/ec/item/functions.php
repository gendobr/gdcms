<?php

/*
  functions for ec_menu_item
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */

function get_ec_item_info($ec_item_id, $lang, $site_id = 0, $use_cache = true, $ec_item_code = '') {

    if ($ec_item_id == 0 && $ec_item_code == '')
        return Array(
            'ec_item_id' => 0,
            'ec_item_lang' => $lang,
            'site_id' => $site_id,
            'ec_item_title' => 'New item',
            'ec_item_content' => '',
            'ec_item_cense_level' => 1,
            'ec_item_last_change_date' => date('Y-m-d H:i:s'),
            'ec_item_abstract' => '',
            'ec_item_tags' => '',
            'ec_category_id' => 0,
            'ec_item_price' => '0.0',
            'ec_item_currency' => 'UAH',
            'ec_item_amount' => 1,
            'ec_producer_id' => 0,
            'ec_item_onnullamount' => 'onnullamount_none',
            'ec_item_mark' => '',
            'ec_item_uid' => '',
            'ec_item_size' => Array('', '', '', 'cm'),
            'ec_item_weight' => Array('', 'g'),
            'ec_item_material' => '',
            'ec_item_img1' => '',
            'ec_item_img2' => '',
            'ec_item_img3' => '',
            'ec_main_image' => '',
            'ec_item_variants' => '',
            'ec_item_code' => ''
        );


    global $text;

    // ----- extract data from main table - begin ------------------------------
    $query = "SELECT ec_item.*,
                ec_producer.ec_producer_title,
                ec_category.ec_category_title,
                ec_category.ec_item_onnullamount,
                ec_currency.ec_curency_title
          FROM <<tp>>ec_item AS ec_item
               LEFT JOIN <<tp>>ec_category AS ec_category
               ON ec_category.ec_category_id=ec_item.ec_category_id
               LEFT JOIN <<tp>>ec_producer AS ec_producer
               ON ec_producer.ec_producer_id=ec_item.ec_producer_id
               LEFT JOIN <<tp>>ec_currency AS ec_currency
               ON ec_currency.ec_currency_code=ec_item.ec_item_currency
           WHERE ec_item.ec_item_lang='{$lang}'
             AND ( ec_item_id=$ec_item_id OR ec_item_code='" . \e::db_escape($ec_item_code) . "')
            ";
    // prn($query);
    $tor = \e::db_getonerow($query);
    if (!$tor)
        return Array();
    // prn($query,$tor);
    // ----- extract data from main table - end --------------------------------
    // ----- get cached info - begin -------------------------------------------
    if ($use_cache && strlen($tor['cached_info']) > 0 && checkDatetime($tor['cache_datetime'])) {
        if ((time() - strtotime($tor['cache_datetime'])) < 3600) {
            //prn('using cache for item #'.$ec_item_id);
            $tor = unserialize($tor['cached_info']);
            return ec_item_adjust($tor, get_site_info($tor['site_id']));
        }
    }
    //prn('creating cache for item #'.$ec_item_id);
    // ----- get cached info - end ---------------------------------------------
    // ----- parse size - begin ------------------------------------------------
    //$nm='([0-9]+[.,][0-9]*)|([0-9]*[.,][0-9]+)|[0-9]+';
    $nm = "[0-9,\\. -]+";
    $pattern = '/^(' . $nm . ')x(' . $nm . ')x(' . $nm . ') *(' . length_units . ')$/i';
    //prn($pattern,$tor['ec_item_size']);
    $tor['ec_item_size'] = trim($tor['ec_item_size']);
    if (preg_match($pattern, trim($tor['ec_item_size']), $regs)) {
        //prn($regs);
        $tor['ec_item_size'] = Array($regs[1], $regs[2], $regs[3], $regs[4]);
        //prn($tor['ec_item_size']);
    } else {
        $tor['ec_item_size'] = Array('', '', '', 'cm');
    }
    // ----- parse size - end --------------------------------------------------
    // ----- parse weight - begin ----------------------------------------------
    if (preg_match('/(' . $nm . ') (' . weight_units . ')/', $tor['ec_item_weight'])) {
        $tor['ec_item_weight'] = preg_split('/x| +/', $tor['ec_item_weight']);
    } else {
        $tor['ec_item_weight'] = Array('', 'g');
    }
    // ----- parse weight - end ------------------------------------------------
    // ------------------- get additional categories - begin -------------------
    $query = "SELECT ec_category.ec_category_id, ec_category.ec_category_title
              FROM <<tp>>ec_category AS ec_category
                   inner join <<tp>>ec_item_category AS ec_item_category
                   ON ec_category.ec_category_id=ec_item_category.ec_category_id
              WHERE ec_item_category.ec_item_id=$ec_item_id";
    $tor['additional_categories'] = \e::db_getrows($query);
    // ------------------- get additional categories - end ---------------------
    // ------------------- get variants - begin --------------------------------
    $query = "SELECT *
              FROM <<tp>>ec_item_variant AS ec_item_variant
              WHERE ec_item_variant.ec_item_id=$ec_item_id AND ec_item_lang='$lang'
              ORDER BY ec_item_variant_ordering, ec_item_variant_id ASC";
    $tor['ec_item_variant'] = \e::db_getrows($query);
    $cnt = count($tor['ec_item_variant']);
    $variant_group_no = 1;
    for ($i = 0; $i < $cnt; $i++) {
        $tor['ec_item_variant'][$i]['ec_item_variant_price_correction'] = ec_item_parse_price_correction($tor['ec_item_variant'][$i]['ec_item_variant_price_correction']);
        if (isset($tor['ec_item_variant'][$i + 1]) && $tor['ec_item_variant'][$i + 1]['ec_item_variant_indent'] > $tor['ec_item_variant'][$i]['ec_item_variant_indent']) {
            $tor['ec_item_variant'][$i]['ec_item_variant_form_element_name'] = '';
            $variant_group_no++;
        } else {
            $tor['ec_item_variant'][$i]['ec_item_variant_form_element_name'] = "ec_item_variant[$variant_group_no]";
            $tor['ec_item_variant'][$i]['ec_item_variant_group'] = $variant_group_no;
        }
    }
    // ------------------- get variants - end ----------------------------------
    // -------------- get default item variants - begin ------------------------
    // if previous item has less indent    then current item can be selected value
    // if previous item has greater indent then next radio button group starts
    $tor['ec_item_variant_default'] = Array();
    $cnt = -1;
    foreach ($tor['ec_item_variant'] as $i => $v) {
        if ($i == 0) {
            $cnt++;
            $tor['ec_item_variant_default'][$cnt] = $v;
            $tor['ec_item_variant_default'][$cnt]['position'] = $i;
        } else {
            if ($prev['ec_item_variant_indent'] < $v['ec_item_variant_indent']) {
                $tor['ec_item_variant_default'][$cnt] = $v;
                $tor['ec_item_variant_default'][$cnt]['position'] = $i;
            } elseif ($prev['ec_item_variant_indent'] > $v['ec_item_variant_indent']) {
                $cnt++;
                $tor['ec_item_variant_default'][$cnt] = $v;
                $tor['ec_item_variant_default'][$cnt]['position'] = $i;
            }
        }
        $prev = $v;
    }
    // prn($tor['ec_item_variant'],$tor['ec_item_variant_default']);
    // -------------- get default item variants - end --------------------------
    // -------------- mark variants as seleted by default - begin --------------
    foreach ($tor['ec_item_variant_default'] as $v) {
        $tor['ec_item_variant'][$v['position']]['is_default'] = 1;
    }
    //prn($tor['ec_item_variant'],$tor['ec_item_variant_default']);
    // -------------- mark variants as seleted by default - end ----------------
    # ------------------------ list of additional fields - begin ---------------
    # category_id should be set
    $query = "SELECT pa.ec_category_id
	        FROM <<tp>>ec_category AS pa,<<tp>>ec_category AS ch
			WHERE pa.start<=ch.start AND ch.finish<=pa.finish
			  AND pa.site_id={$tor['site_id']}
			  AND ch.site_id={$tor['site_id']}
			  AND ch.ec_category_id={$tor['ec_category_id']}";
    $tmp = \e::db_getrows($query);
    $cnt = count($tmp);
    for ($i = 0; $i < $cnt; $i++) {
        $tmp[$i] = $tmp[$i]['ec_category_id'];
    }
    $tmp[] = 0;
    $tmp = join(',', $tmp);

    $query = "select cif.*,
                  cif_value.ec_category_item_field_value
           from <<tp>>ec_category_item_field as cif
                left join <<tp>>ec_category_item_field_value as cif_value
                on (    cif_value.ec_category_item_field_id=cif.ec_category_item_field_id
                    and cif_value.ec_item_id={$tor['ec_item_id']}
                    and cif_value.ec_item_lang='{$tor['ec_item_lang']}'
                    )
           where cif.site_id={$tor['site_id']}
             and cif.ec_category_id IN ($tmp)
           order by cif.ec_category_item_field_ordering ASC";
    //prn(checkStr($query));
    $tmp = \e::db_getrows($query);
    $tor['ec_category_item_field'] = Array();
    foreach ($tmp as $fld) {
        $tor['ec_category_item_field'][$fld['ec_category_item_field_id']] = $fld;
    }
    //prn($tor);
    # ------------------------ list of additional fields - end --------------------
    // ----------------------- save cache - begin ------------------------------
    // prn($tor);
    $tor['cached_info'] = Array();
    $query = "UPDATE <<tp>>ec_item
            SET cached_info='" . \e::db_escape(serialize($tor)) . "',
                cache_datetime=now()
            WHERE ec_item_lang='{$lang}' AND ec_item_id=$ec_item_id";
    \e::db_execute($query);
    // ----------------------- save cache - end --------------------------------
    return ec_item_adjust($tor, get_site_info($tor['site_id']));
}

function ec_item_size_check($d1, $d2, $d3, $un) {
    return Array(
        preg_replace('/[^x0-9,\\. -]/', '', $d1),
        preg_replace('/[^x0-9,\\. -]/', '', $d2),
        preg_replace('/[^x0-9,\\. -]/', '', $d3),
        $un);
}

function ec_item_parse_price_correction($str) {

    //$regexp="^([-*+/])([0-9]+[.,][0-9]+|[0-9]+[.,]|[.,][0-9]+)([^0-9]*)$";
    //$regexp="^([-*+/])";
    //$regexp="^([-*+/])([0-9]+[.,][0-9]+|[0-9]+[.,]|[.,][0-9]+|[0-9]+)([^0-9]*)$";
    $regexp = "/^([-*+\/])([0-9]+[.,][0-9]+|[0-9]+[.,]|[.,][0-9]+|[0-9]+)([^0-9]*)$/i";
    //prn($str);
    $str = preg_replace('/ /', '', $str);
    $units = '';
    if (preg_match($regexp, $str, $regs)) {
        $regs[2] = (double) str_replace(',', '.', $regs[2]);
        if (trim($regs[3]) == '%') {
            $units = '%';
            $regs[3] = '';
            if ($regs[1] == '-')
                $regs[2] = 1 - 0.01 * $regs[2];
            else
                $regs[2] = 1 + 0.01 * $regs[2];
            $regs[1] = '*';
            //prn($regs);
        }
        elseif (strlen($regs[3]) == 0) {
            if (!in_array($regs[1], Array('*', '/', '-', '+'))) {
                return Array('code' => $str, 'operation' => '+', 'value' => 0, 'units' => '', 'error' => text('ERROR_you_should_multiple_divide_add_or_substract'));
            }
        } else {
            return Array('code' => $str, 'operation' => '+', 'value' => 0, 'units' => '', 'error' => text('ERROR_unknown_units'));
        }
        //prn($regs);
        return Array('code' => $str, 'operation' => $regs[1], 'value' => $regs[2], 'units' => $units, 'error' => '');
    } else {
        return Array('code' => $str, 'operation' => '+', 'value' => 0, 'units' => '', 'error' => text('ERROR_I_Dont_understand'));
    }
}

function menu_ec_item($_info) {
    //prn($_info);
    global $text;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];

    $tor['ec/item/edit'] = Array(
        'URL' => "index.php?action=ec/item/edit&site_id={$_info['site_id']}&ec_item_id={$_info['ec_item_id']}&ec_item_lang={$_info['ec_item_lang']}"
        , 'innerHTML' => text('Edit_ec_item')
        , 'attributes' => ''
    );

    $tor['ec/item/search_images'] = Array(
        'URL' => "index.php?action=ec/item/search_images&ec_item_id={$_info['ec_item_id']}&ec_item_lang={$_info['ec_item_lang']}"
        , 'innerHTML' => text('Search_images')
        , 'attributes' => ''
    );

    $tor['ec/item/clone'] = Array(
        'URL' => "index.php?action=ec/item/clone&site_id={$_info['site_id']}&ec_item_id={$_info['ec_item_id']}&ec_item_lang={$_info['ec_item_lang']}"
        , 'innerHTML' => text('EC_item_clone')
        , 'attributes' => ''
    );

    $tor['ec/item/add_translation'] = Array(
        'URL' => "index.php?action=ec/item/add_translation&site_id={$_info['site_id']}&ec_item_id={$_info['ec_item_id']}&ec_item_lang={$_info['ec_item_lang']}"
        , 'innerHTML' => text('Add_translation')
        , 'attributes' => ''
    );

    $tor['ec/item/view'] = Array(
        'URL' => "index.php?action=ec/item/view&ec_item_id={$_info['ec_item_id']}&ec_item_code={$_info['ec_item_code']}&ec_item_lang={$_info['ec_item_lang']}" . '&' . $sid
        , 'innerHTML' => text('View_ec_item')
        , 'attributes' => ' target=_blank '
    );

    $tor['ec/item/manage_comments'] = Array(
        'URL' => "index.php?action=ec/item/manage_comments&site_id={$_info['site_id']}&filter_ec_item_id={$_info['ec_item_id']}&filter_ec_item_lang={$_info['ec_item_lang']}"
        , 'innerHTML' => text('EC_item_comments') . '<br><br>'
        , 'attributes' => '  '
    );

    if (isset($_info['ec_category_id']) || isset($_info['ec_category_id_value']))
        $tor['ec/category/edit'] = Array(
            'URL' => "index.php?action=ec/category/edit&site_id={$_info['site_id']}&ec_category_id=" . (isset($_info['ec_category_id_value']) ? $_info['ec_category_id_value'] : $_info['ec_category_id'])
            , 'innerHTML' => text('EC_category_edit')
            , 'attributes' => '  '
        );
    $tor['ec/producer/view'] = Array(
        'URL' => "index.php?action=ec/producer/view&ec_producer_id=" . (isset($_info['ec_producer_id_value']) ? $_info['ec_producer_id_value'] : $_info['ec_producer_id'])
        , 'innerHTML' => text('View_ec_producer') . '<br><br>'
        , 'attributes' => ' target=_blank '
    );

    $tor['ec/item/delete'] = Array(
        'URL' => "index.php?action=ec/item/delete" .
        "&site_id=" . $_info['site_id'] .
        "&ec_item_id=" . $_info['ec_item_id'] .
        "&ec_item_lang=" . $_info['ec_item_lang']
        , 'innerHTML' => text('Delete') . '<iframe src="about:blank" width=10px height=1px style="border:none;" name="frm_delete"></iframe>'
        , 'attributes' => " onclick='return confirm(\"" . text('Are_You_sure') . "?\")' target=frm_delete "
    );
    return $tor;
}

# --------------------------- delete ec_item - begin ---------------------------

function ec_item_delete($ec_item_id, $ec_item_lang) {

    $this_ec_item_info = get_ec_item_info($ec_item_id, $ec_item_lang);
    if (!$this_ec_item_info)
        return false;

    $site_id = $this_ec_item_info['site_id'];
    $this_site_info = get_site_info($site_id);
    if (get_level($site_id) == 0)
        return false;


    # ---------------- delete files - begin ------------------------------------
    $site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
    //prn($input_vars);
    foreach ($this_ec_item_info["ec_item_img"] as $im) {
        $path = $site_root_dir . '/' . $im;
        if (is_file($path))
            unlink($path);
    }
    # ---------------- delete files - end --------------------------------------
    # ---------------- delete comments - begin ---------------------------------
    $query = "DELETE FROM <<tp>>ec_item_comment WHERE site_id=$site_id AND ec_item_id=$ec_item_id AND ec_item_lang='$ec_item_lang'";
    \e::db_execute($query);
    # ---------------- delete comments - end -----------------------------------
    # ---------------- delete categories - begin -------------------------------
    $query = "DELETE FROM <<tp>>ec_item_category WHERE ec_item_id=$ec_item_id";
    \e::db_execute($query);
    # ---------------- delete categories - end ---------------------------------
    # ---------------- delete ec_category_item_field_value - begin -------------
    $query = "DELETE FROM <<tp>>ec_category_item_field_value WHERE ec_item_id=$ec_item_id";
    \e::db_execute($query);
    # ---------------- delete ec_category_item_field_value - end ---------------
    # ---------------- delete ec_item_tags - begin -----------------------------
    $query = "DELETE FROM <<tp>>ec_item_tags WHERE ec_item_id=$ec_item_id";
    \e::db_execute($query);
    # ---------------- delete ec_item_tags - end -------------------------------
    # ---------------- delete ec_item_variant - begin --------------------------
    $query = "DELETE FROM <<tp>>ec_item_variant WHERE ec_item_id=$ec_item_id";
    \e::db_execute($query);
    # ---------------- delete ec_item_variant - end ----------------------------
    # ---------------- delete item - begin -------------------------------------
    $query = "DELETE FROM <<tp>>ec_item
            WHERE site_id=$site_id
               AND ec_item_id=$ec_item_id
               AND ec_item_lang='$ec_item_lang'";
    \e::db_execute($query);
    # ---------------- delete item - end ---------------------------------------

    return true;
}

# --------------------------- delete ec_item - end -----------------------------
# --------------------------- clone ec_item - begin ----------------------------

function ec_item_clone($ec_item_id, $ec_item_lang) {

    // ------------------------- main item record - begin ----------------------
    $query = "SELECT * FROM  <<tp>>ec_item WHERE ec_item_id={$ec_item_id} AND ec_item_lang='{$ec_item_lang}'";
    $ec_item = \e::db_getonerow($query);
    if (!$ec_item) {
        return;
    }

    $query = "SELECT * FROM  <<tp>>site WHERE id={$ec_item['site_id']}";
    $site_info = \e::db_getonerow($query);
    $site_dir = \e::config('SITES_ROOT') . '/' . $site_info['dir'];

    $ec_item_img = $ec_item['ec_item_img'];


    // ---------------------- copy attached files - begin ----------------------
    //prn($site_dir);
    $ec_item_img_newlist = Array();
    $ec_item_img_list = explode("\n", $ec_item_img);
    $i = 0;
    foreach ($ec_item_img_list as $img) {
        $copy_from = $site_dir . '/' . $img;
        if (!is_file($copy_from)) {
            continue;
        }
        $i++;
        $copy_to = dirname($copy_from) . date("/{$ec_item['ec_item_id']}-Y-m-d-H-i-s-{$i}-") . preg_replace("/\\d+-\\d+-\\d+-\\d+-\\d+-\\d+-\\d+-/", '', basename($copy_from));
        $relpath = str_replace($site_dir . '/', '', $copy_to);
        // prn($copy_from,$copy_to,$relpath);
        copy($copy_from, $copy_to);
        $ec_item_img_newlist[] = $relpath;
    }
    //prn($ec_item_img_list);
    $ec_item['ec_item_img'] = join("\n", $ec_item_img_newlist);
    // prn($ec_item['ec_item_img']);
    // exit('erctgf');
    // ---------------------- copy attached files - end ------------------------


    unset($ec_item['ec_item_id'],
            //$ec_item['ec_item_img'],
            $ec_item['ec_item_uid'], $ec_item['ec_item_purchases'], $ec_item['ec_item_keywords'], $ec_item['ec_item_views'], $ec_item['ec_item_in_cart']);
    $fld = Array();
    $val = Array();
    foreach ($ec_item as $k => $v) {
        $fld[] = $k;
        $val[] = "'" . \e::db_escape($v) . "'";
    }
    $query = "INSERT INTO <<tp>>ec_item(" . join(',', $fld) . ") VALUES(" . join(',', $val) . ")";
    \e::db_execute($query);

    $newid = \e::db_getonerow("SELECT LAST_INSERT_ID() as newid");
    $newid = $newid['newid'];

    // ensure that ec_item_code is unique
    $query = "UPDATE <<tp>>ec_item "
            . "SET ec_item_code='" . \e::db_escape($ec_item['ec_item_code'] . '-' . $ec_item['ec_item_lang'] . '-' . $newid) . "' "
            . "WHERE ec_item_lang='" . \e::db_escape($ec_item['ec_item_lang']) . "' AND ec_item_id={$newid}";
    \e::db_execute($query);
    // ------------------------- main item record - end ------------------------
    // ------------------------- item<->category relation - begin --------------
    $query = "SELECT * FROM  <<tp>>ec_item_category WHERE ec_item_id={$ec_item_id}";
    $ec_item_category = \e::db_getrows($query);
    $cnt = count($ec_item_category);
    for ($i = 0; $i < $cnt; $i++) {
        $ec_item_category[$i]['ec_item_id'] = $newid;
    }
    $query = ec_item_clone_query("<<tp>>ec_item_category", $ec_item_category);
    if ($query != '') {
        \e::db_execute($query);
    }
    // ------------------------- item<->category relation - end ----------------
    // ------------------------- additional field values - begin ---------------
    $query = "SELECT * FROM  <<tp>>ec_category_item_field_value WHERE ec_item_id={$ec_item_id} AND ec_item_lang='{$ec_item_lang}'";
    $ec_category_item_field_value = \e::db_getrows($query);
    $cnt = count($ec_category_item_field_value);
    for ($i = 0; $i < $cnt; $i++) {
        $ec_category_item_field_value[$i]['ec_item_id'] = $newid;
    }
    //prn($ec_category_item_field_value);
    $query = ec_item_clone_query("<<tp>>ec_category_item_field_value", $ec_category_item_field_value);
    if ($query != '') {
        \e::db_execute($query);
    }
    // ------------------------- additional field values - end -----------------
    // ------------------------- tags - begin ----------------------------------
    $query = "SELECT * FROM  <<tp>>ec_item_tags WHERE ec_item_id={$ec_item_id}";
    $ec_item_tags = \e::db_getrows($query);
    $cnt = count($ec_item_tags);
    for ($i = 0; $i < $cnt; $i++) {
        $ec_item_tags[$i]['ec_item_id'] = $newid;
    }
    //prn($ec_item_tags);
    $query = ec_item_clone_query("<<tp>>ec_item_tags", $ec_item_tags);
    if ($query != '') {
        \e::db_execute($query);
    }
    // ------------------------- tags - end ------------------------------------
    // ------------------------- variants - begin ------------------------------
    $query = "SELECT * FROM  <<tp>>ec_item_variant WHERE ec_item_id={$ec_item_id} AND ec_item_lang='{$ec_item_lang}'";
    $ec_item_variant = \e::db_getrows($query);
    $cnt = count($ec_item_variant);
    for ($i = 0; $i < $cnt; $i++) {
        $ec_item_variant[$i]['ec_item_id'] = $newid;
        unset($ec_item_variant[$i]['ec_item_variant_id']);
    }
    //prn($ec_item_variant);
    $query = ec_item_clone_query("<<tp>>ec_item_variant", $ec_item_variant);
    if ($query != '') {
        \e::db_execute($query);
    }
    // ------------------------- variants - end --------------------------------

    return Array($newid, $ec_item_lang);
}

function ec_item_clone_query($tbl, $ar) {
    $cnt = count($ar);

    if ($cnt > 0) {
        $fld = array_keys($ar[0]);
        $val = array();
        for ($i = 0; $i < $cnt; $i++) {
            $tmp = Array();
            foreach ($ar[$i] as $v) {
                $tmp[] = "'" . \e::db_escape($v) . "'";
            }
            $val[] = '(' . join(',', $tmp) . ')';
        }
        $query = "INSERT INTO $tbl(" . join(',', $fld) . ")  VALUES " . join(',', $val);
    } else {
        $query = '';
    }
    return $query;
}

# --------------------------- clone ec_item - end ------------------------------
# --------------------------- add translation - begin --------------------------

function ec_item_add_translation($ec_item_id, $ec_item_lang) {
    // get available languages
    $query = "SELECT ec_item_lang FROM <<tp>>ec_item WHERE ec_item_id={$ec_item_id}";
    $langs = \e::db_getrows($query);
    $cnt = count($langs);
    for ($i = 0; $i < $cnt; $i++) {
        $langs[$langs[$i]['ec_item_lang']] = $langs[$i]['ec_item_lang'];
        unset($langs[$i]);
    }
    //prn($langs);

    $query = "SELECT id FROM <<tp>>languages WHERE is_visible=1";
    $all_langs = \e::db_getrows($query);
    //prn($all_langs);
    $cnt_all = count($all_langs);
    $new_language = '';
    for ($i = 0; $i < $cnt_all; $i++) {
        if (!isset($langs[$all_langs[$i]['id']])) {
            $new_language = $all_langs[$i]['id'];
            break;
        }
    }
    if ($new_language == '')
        return false;

    // ------------------------- main item record - begin ----------------------
    $query = "SELECT * FROM  <<tp>>ec_item WHERE ec_item_id={$ec_item_id} AND ec_item_lang='{$ec_item_lang}'";
    $ec_item = \e::db_getonerow($query);
    $ec_item['ec_item_lang'] = $new_language;
    unset($ec_item['ec_item_purchases'], $ec_item['ec_item_keywords'], $ec_item['ec_item_views'], $ec_item['ec_item_in_cart'], $ec_item['cached_info']);
    //$ec_item['ec_item_uid'],
    // prn($ec_item);
    //$query="INSERT INTO <<tp>>ec_item(".join(',',$fld).") VALUES(".join(',',$val).")";
    $query = ec_item_clone_query("<<tp>>ec_item", Array($ec_item));
    //prn($query);
    \e::db_execute($query);

    //$newid=\e::db_getonerow("SELECT LAST_INSERT_ID() as newid");
    //$newid=$newid['newid'];
    // ------------------------- main item record - end ------------------------
    // ------------------------- additional field values - begin ---------------
    $query = "SELECT * FROM  <<tp>>ec_category_item_field_value WHERE ec_item_id={$ec_item_id} AND ec_item_lang='{$ec_item_lang}'";
    $ec_category_item_field_value = \e::db_getrows($query);
    $cnt = count($ec_category_item_field_value);
    for ($i = 0; $i < $cnt; $i++) {
        $ec_category_item_field_value[$i]['ec_item_lang'] = $new_language;
    }
    //prn($ec_category_item_field_value);
    $query = ec_item_clone_query("<<tp>>ec_category_item_field_value", $ec_category_item_field_value);
    //prn($query);
    if ($query != '') {
        \e::db_execute($query);
    }
    // ------------------------- additional field values - end -----------------
    // ------------------------- variants - begin ------------------------------
    $query = "SELECT * FROM  <<tp>>ec_item_variant WHERE ec_item_id={$ec_item_id} AND ec_item_lang='{$ec_item_lang}'";
    $ec_item_variant = \e::db_getrows($query);
    $cnt = count($ec_item_variant);
    for ($i = 0; $i < $cnt; $i++) {
        $ec_item_variant[$i]['ec_item_lang'] = $new_language;
        unset($ec_item_variant[$i]['ec_item_variant_id']);
    }
    //prn($ec_item_variant);
    $query = ec_item_clone_query("<<tp>>ec_item_variant", $ec_item_variant);
    //prn($query);
    if ($query != '') {
        \e::db_execute($query);
    }
    // ------------------------- variants - end --------------------------------

    return Array($ec_item_id, $new_language);
}

# --------------------------- add translation - end ----------------------------

function ec_item_adjust($_info, $this_site_info) {

    static $currency_titles;
    if (!isset($currency_titles)) {
        $tmp = \e::db_getrows("SELECT * FROM <<tp>>ec_currency");
        $currency_titles = Array();
        foreach ($tmp as $tm)
            $currency_titles[$tm['ec_currency_code']] = $tm['ec_curency_title'];
        //prn('Set currency titles to ',$currency_titles);
    }

    # url to view details
    //$_info['url_details']=sprintf(\e::config('url_ec_item_details_pattern'),$_info['ec_item_lang'],$_info['ec_item_id']);
    $_info['url_details'] = str_replace(
            Array('{ec_item_lang}', '{ec_item_id}', '{ec_item_code}'), Array($_info['ec_item_lang'], $_info['ec_item_id'], $_info['ec_item_code']), \e::config('url_ec_item_details_pattern'));

    # title of currency
    $_info['ec_curency_title'] = isset($currency_titles[$_info['ec_item_currency']]) ? $currency_titles[$_info['ec_item_currency']] : $_info['ec_item_currency'];

    # add marks
    $_info['ec_item_sell'] = ( ($_info['ec_item_cense_level'] & ec_item_sell) > 0 ) ? 1 : 0;
    $_info['ec_item_show'] = ( ($_info['ec_item_cense_level'] & ec_item_show) > 0 ) ? 1 : 0;
    $_info['ec_item_hide'] = ( ($_info['ec_item_cense_level'] & ec_item_hide) > 0 ) ? 1 : 0;
    $_info['ec_item_sold'] = ( ($_info['ec_item_cense_level'] & ec_item_sold) > 0 ) ? 1 : 0;
    $_info['ec_item_reserved'] = ( ($_info['ec_item_cense_level'] & ec_item_reserved) > 0 ) ? 1 : 0;

    $_info['ec_item_sell'] = ( ($_info['ec_item_cense_level'] & ec_item_sell) > 0 ) ? 1 : 0;
    $_info['ec_item_show'] = ( ($_info['ec_item_cense_level'] & ec_item_show) > 0 ) ? 1 : 0;
    $_info['ec_item_hide'] = ( ($_info['ec_item_cense_level'] & ec_item_hide) > 0 ) ? 1 : 0;
    $_info['ec_item_sold'] = ( ($_info['ec_item_cense_level'] & ec_item_sold) > 0 ) ? 1 : 0;
    $_info['ec_item_reserved'] = ( ($_info['ec_item_cense_level'] & ec_item_reserved) > 0 ) ? 1 : 0;


    $_info['ec_category_view_url'] = 'index.php?action=ec/item/browse&site_id=' . $_info['site_id'] . '&ec_category_id=' . $_info['ec_category_id'];
    $_info['ec_item_view_url'] = 'index.php?action=ec/item/view&ec_item_id=' . $_info['ec_item_id'] . '&ec_item_lang=' . $_info['ec_item_lang'];

    $_info['ec_compare_add_url'] = 'index.php?action=ec/item/compare&site_id=' . $_info['site_id'] . '&ec_item_id=' . $_info['ec_item_id'];

    # ------------------- link "add to comparison list" - begin ----------------
    if (!isset($_SESSION['items_to_compare'][$_info['ec_item_id']])) {
        $_info['ec_compare_add_url'] = 'index.php?action=ec/item/compare_add&site_id=' . $_info['site_id'] . '&ec_item_id=' . $_info['ec_item_id'];
        $_info['ec_compare_add_javascript'] = "this.action='javascript:void(0)';compare_add('{$_info['ec_item_id']}');";
    } else {
        $_info['ec_compare_add_javascript'] = '';
        $_info['ec_compare_add_url'] = '';
    }
    # ------------------- link "add to comparison list" - end ------------------
    # ------------------- additional categories - begin ------------------------
    if (!isset($_info['additional_categories']))
        $_info['additional_categories'] = Array();
    $cnt = count($_info['additional_categories']);
    $prefix = 'index.php?action=ec/item/browse&site_id=' . $_info['site_id'] . '&ec_category_id=';
    for ($i = 0; $i < $cnt; $i++) {
        $_info['additional_categories'][$i]['ec_category_view_url'] = $prefix . $_info['additional_categories'][$i]['ec_category_id'];
    }
    # ------------------- additional categories - end --------------------------
    # url to order item
    $_info['url_order_now'] = '#';
    $_info['url_buy_now'] = '#';
    //prn($_info['ec_item_id'],ec_item_sell,$_info['ec_item_cense_level']);

    if (($_info['ec_item_cense_level'] & ec_item_sell) > 0) {
        //prn('ec_item_sell='.ec_item_sell);
        $_info['ec_item_sell'] = 1;
        $_info['url_order_now'] = sprintf(\e::config('url_ec_item_order_now_pattern'), $_info['ec_item_lang'], $_info['ec_item_id']);
        $_info['url_buy_now'] = sprintf(\e::config('url_ec_item_buy_now_pattern'), $_info['ec_item_lang'], $_info['ec_item_id'], $this_site_info['id']);
    } else
        $_info['ec_item_sell'] = 0;

    // ------------------- images - begin ---------------------------------------
    $_info['ec_main_image'] = '';
    if (strlen($_info['ec_item_img'])) {
        $_info['ec_item_img'] = explode("\n", $_info['ec_item_img']);
        if (count($_info['ec_item_img']) > 0) {
            $cnt = count($_info['ec_item_img']);
            for ($i = 0; $i < $cnt; $i++) {
                if (strlen($_info['ec_item_img'][$i]) == 0) {
                    unset($_info['ec_item_img'][$i]);
                    continue;
                }
                $tmp = explode("\t", $_info['ec_item_img'][$i]);
                $_info['ec_item_img'][$i] = Array();
                $_info['ec_item_img'][$i]['small'] = isset($tmp[0]) ? $tmp[0] : '';
                $_info['ec_item_img'][$i]['big'] = isset($tmp[1]) ? $tmp[1] : '';
                $_info['ec_item_img'][$i]['label'] = isset($tmp[2]) ? $tmp[2] : '';
            }
            $_info['ec_main_image'] = $_info['ec_item_img'][0];
            $_info['ec_item_img'] = array_values($_info['ec_item_img']);
        }
    } else {
        $_info['ec_item_img'] = Array();
    }

    // ------------------- images - end -----------------------------------------
    # separate flags
    $_info['ec_item_reserved'] = ($_info['ec_item_cense_level'] & ec_item_reserved) ? 1 : 0;
    $_info['ec_item_sold'] = ($_info['ec_item_cense_level'] & ec_item_sold) ? 1 : 0;
    return $_info;
}

# --------- handle event if amount of the items becomes zero - begin -----------

function onnullamount_hide($ec_item_id) {


    $tmp = array_flip($GLOBALS['ec_item_publication_states']);
    /*
      ec_item_hide=>'ec_item_hide',
      ec_item_show=>'ec_item_show',
      ec_item_show|ec_item_sell=>'ec_item_show_and_sell',
      ec_item_show|ec_item_reserved =>'ec_item_show_as_reserved'
     */
    $query = "UPDATE <<tp>>ec_item
              SET ec_item_cense_level={$tmp['ec_item_hide']}
              WHERE ec_item_id=$ec_item_id
              LIMIT 1";
    \e::db_execute($query);
}

function onnullamount_reserved($ec_item_id) {

    $tmp = array_flip($GLOBALS['ec_item_publication_states']);
    /*
      ec_item_hide=>'ec_item_hide',
      ec_item_show=>'ec_item_show',
      ec_item_show|ec_item_sell=>'ec_item_show_and_sell',
      ec_item_show|ec_item_reserved =>'ec_item_show_as_reserved'
     */

    $query = "UPDATE <<tp>>ec_item
              SET ec_item_cense_level={$tmp['ec_item_show_as_reserved']}
              WHERE ec_item_id=$ec_item_id
              LIMIT 1";
    \e::db_execute($query);
}

function onnullamount_disable_sale($ec_item_id) {

    $tmp = array_flip($GLOBALS['ec_item_publication_states']);
    /*
      ec_item_hide=>'ec_item_hide',
      ec_item_show=>'ec_item_show',
      ec_item_show|ec_item_sell=>'ec_item_show_and_sell',
      ec_item_show|ec_item_reserved =>'ec_item_show_as_reserved'
     */
    $query = "UPDATE <<tp>>ec_item
              SET ec_item_cense_level={$tmp['ec_item_show']}
              WHERE ec_item_id=$ec_item_id
              LIMIT 1";
    //prn($query);
    \e::db_execute($query);
}

function onnullamount_none($ec_item_id) {
    
}

# --------- handle event if amount of the items becomes zero - end -------------
// ----------------- resizing image to create small image - begin ----------
/**
 * $type="resample"|"inscribe"|"circumscribe"
 */
function ec_img_resize($inputfile, $outputfile, $width, $height, $type = "resample", $backgroundColor = 0xFFFFFF, $quality = 70) {
    if (!file_exists($inputfile)) {
        return false;
    }
    $size = getimagesize($inputfile);
    if ($size === false) {
        return false;
    }

    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    $icfunc = "imagecreatefrom" . $format;
    if (!function_exists($icfunc)) {
        return false;
    }

    switch ($type) {
        case "inscribe":
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height > $height) {
                $ratio = $height / $size[1];
                if ($ratio > 1) {
                    $ratio = 1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);
            }
            break;
        case "circumscribe":
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height < $height) {
                $ratio = $height / $size[1];
                if ($ratio > 1) {
                    $ratio = 1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);
            }
            break;
        default:
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height > $height) {
                $ratio = $height / $size[1];
                if ($ratio > 1) {
                    $ratio = 1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);
            }
            $width = $new_width;
            $height = $new_height;
            break;
    }
    $new_left = floor(($width - $new_width) / 2);
    $new_top = floor(($height - $new_height) / 2);

    $bigimg = $icfunc($inputfile);
    $trumbalis = imagecreatetruecolor($width, $height);

    imagefill($trumbalis, 0, 0, $backgroundColor);
    imagecopyresampled($trumbalis, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);
    imagejpeg($trumbalis, $outputfile, $quality);

    imagedestroy($bigimg);
    imagedestroy($trumbalis);
    return true;
}

// ----------------- resizing image to create small image - end ------------





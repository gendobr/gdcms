<?php

/**
 * Parameters are:
 *   $site_id - site identifier, integer, mandatory
 *   $lang    - language, char(3), optional
 *
 * filter parameters (optional)
 *   $input_vars['ec_item_date_year']
 *   $input_vars['ec_item_date_month']
 *   $input_vars['ec_item_date_day']
 *   $input_vars['ec_item_keywords']
 *   $input_vars['ec_category_id']
 *   $input_vars['ec_producer_id']
 *   $input_vars['ec_item_tags']
 *   $input_vars['ec_item_cense_level']
 *
 *   $input_vars['start']   - start SELECT from this row, optional
 *   $input_vars['rows']    - and show this number of rows, optional
 *   $input_vars['orderby'] - ordering, optional
 */
if (!function_exists('get_ec_item_info')) {
    run('ec/item/functions');
}

$lang = preg_replace("/\\W/", '', $lang);








# --------------------------- prepare restrictions - begin ---------------------
$restriction = '';


# ------------------------- date restriction - begin -------------------------
if (isset($input_vars['ec_item_date_year']) && strlen($input_vars['ec_item_date_year']) > 0)
    $restriction.="  AND YEAR(ec_item.ec_item_last_change_date)=" . ((int) $input_vars['ec_item_date_year']);
if (isset($input_vars['ec_item_date_month']) && strlen($input_vars['ec_item_date_month']) > 0)
    $restriction.="  AND MONTH(ec_item.ec_item_last_change_date)=" . ((int) $input_vars['ec_item_date_month']);
if (isset($input_vars['ec_item_date_day']) && strlen($input_vars['ec_item_date_day']) > 0)
    $restriction.="  AND DAYOFMONTH(ec_item.ec_item_last_change_date)=" . ((int) $input_vars['ec_item_date_day']);
//prn('$restriction=',$restriction);
# ------------------------- date restriction - end ---------------------------
# ------------------------- keyword restriction - begin ----------------------
if (isset($input_vars['ec_item_keywords']) && strlen($input_vars['ec_item_keywords']) > 0) {
    $tmp = explode(' ', trim($input_vars['ec_item_keywords']));
    $cnt = count($tmp);
    $pat = "
        AND LOCATE('%s', ifnull(ec_item.ec_item_keywords,'') )>0
    ";
    for ($i = 0; $i < $cnt; $i++) {
        if (strlen($tmp[$i]) > 0) {
            $restriction.=sprintf($pat, \e::db_escape($tmp[$i]));
        }
    }
}
// prn('$restriction=',$restriction);
# ------------------------- keyword restriction - end ------------------------
# ------------------------- category restriction - begin ---------------------
$ec_category_id = \e::request('ec_category_id', '');
if(strlen($ec_category_id)>0){
    if ($ec_category_id > 0) {
        // \e::info($ec_category_id);
        $categories = \e::cast('integer[]', $ec_category_id);
        $getPositiveIds=function($x){return $x>0;};
        
        foreach ($categories as $category) {
            // get children
            $children = "SELECT ch.ec_category_id
                              FROM <<tp>>ec_category as ch,<<tp>>ec_category as pa
                                          WHERE pa.start<=ch.start and ch.finish<=pa.finish
                                            AND pa.ec_category_id=$category";
            $category = \e::db_getrows($children);
            $cnt = count($category);
            for ($i = 0; $i < $cnt; $i++) {
                $category[$i] = (int) $category[$i]['ec_category_id'];
            }
            $category=  array_filter($category, $getPositiveIds);
            // $category[] = 0;
            if(count($category) > 0){
                $category = join(',', $category);
                $restriction.="
                    AND (
                          ec_item.ec_category_id IN({$category})
                       OR ec_item.ec_item_id IN(SELECT ec_item_id FROM <<tp>>ec_item_category WHERE ec_category_id  IN({$category}) )
                    )";                
            }
        }
    }
    // else {
    //    $restriction.="  AND ec_item.ec_category_id<>" . abs((int) $ec_category_id);
    // }    
    //\e::info($restriction);
}

# ------------------------- category restriction - end -----------------------
# ------------------------- producer restriction - begin ---------------------
if (isset($input_vars['ec_producer_id']) && $input_vars['ec_producer_id'] > 0) {
    $restriction.="  AND ec_item.ec_producer_id=" . ((int) $input_vars['ec_producer_id']);
}
# ------------------------- producer restriction - end -----------------------
# ------------------------- tag restriction - begin --------------------------
if (isset($input_vars['ec_item_tags']) && strlen($input_vars['ec_item_tags']) > 0) {
    $ec_item_tags = trim($input_vars['ec_item_tags']);
    $tmp = explode(',', $ec_item_tags);
    $cnt = count($tmp);
    $pat = " AND FIND_IN_SET('%s',ec_item.ec_item_tags) ";
    for ($i = 0; $i < $cnt; $i++) {
        if (strlen($tmp[$i]) > 0) {
            $restriction.=sprintf($pat, \e::db_escape($tmp[$i]));
        }
    }
}
# ------------------------- tag restriction - end ----------------------------
# ------------------------- item state restriction - begin -------------------
$ec_item_state = 0;
$pat = " AND (ec_item.ec_item_cense_level&%s) \n";
if (isset($input_vars['ec_item_state']) && strlen($input_vars['ec_item_state']) > 0) {
    $tmp = explode(' ', $input_vars['ec_item_state']);
    foreach ($tmp as $co) {
        if (defined('ec_item_' . $co)) {
            $restriction.=sprintf($pat, constant('ec_item_' . $co));
            $ec_item_state = $ec_item_state | constant('ec_item_' . $co);
        }
    }
}
if ($ec_item_state == 0) {
    $restriction.=sprintf($pat, ec_item_show);
    // $restriction.=sprintf($pat,ec_item_sell);
}
# ------------------------- item state restriction - end ---------------------
# ec_item_title             varchar(255)
if (isset($input_vars['ec_item_title']) && strlen($input_vars['ec_item_title']) > 0) {
    $restriction.=" and locate('" . \e::db_escape(trim($input_vars['ec_item_title'])) . "',ec_item.ec_item_title)>0 ";
}

# ec_item_content           longtext
# ec_item_abstract          text
if (isset($input_vars['ec_item_content']) && strlen($input_vars['ec_item_content']) > 0) {
    $restriction.=" and locate('" . \e::db_escape(trim($input_vars['ec_item_content'])) . "',concat(ec_item.ec_item_content,' ',ec_item.ec_item_abstract)) >0 ";
}

# ec_item_price             float
if (isset($input_vars['ec_item_price_min']) && strlen($input_vars['ec_item_price_min']) > 0) {
    $restriction.=" and ec_item.ec_item_price>= " . (checkFloat($input_vars['ec_item_price_min']));
}

if (isset($input_vars['ec_item_price_max']) && strlen($input_vars['ec_item_price_max']) > 0) {
    $restriction.=" and ec_item.ec_item_price<= " . (checkFloat($input_vars['ec_item_price_max']));
}

if (isset($input_vars['extrafld'])) {
    //prn($input_vars['extrafld']);
    foreach ($input_vars['extrafld'] as $key => $val) {
        if (is_array($val)) {
            $val['min'] = trim($val['min']);
            if (strlen($val['min']) > 0) {
                $restriction.=" AND ec_item.ec_item_id in(
                     SELECT it.ec_item_id
                     FROM <<tp>>ec_category_item_field_value AS ifv
                          INNER JOIN <<tp>>ec_item AS it
                          ON it.ec_item_id=ifv.ec_item_id
                     WHERE ifv.ec_category_item_field_id={$key}
                       AND ifv.ec_category_item_field_value>='" . \e::db_escape(trim($val['min'])) . "'
                )";
            }
            $val['max'] = trim($val['max']);
            if (strlen($val['max']) > 0) {
                $restriction.=" AND ec_item.ec_item_id in(
                     SELECT it.ec_item_id
                     FROM <<tp>>ec_category_item_field_value AS ifv
                          INNER JOIN <<tp>>ec_item AS it
                          ON it.ec_item_id=ifv.ec_item_id
                     WHERE ifv.ec_category_item_field_id={$key}
                       AND ifv.ec_category_item_field_value<='" . \e::db_escape(trim($val['max'])) . "'
                )";
            }
        } else {
            $val = trim($val);
            if (strlen($val) > 0) {
                $restriction.=" AND ec_item.ec_item_id in(
                     SELECT it.ec_item_id
                     FROM <<tp>>ec_category_item_field_value AS ifv
                          INNER JOIN <<tp>>ec_item AS it
                          ON it.ec_item_id=ifv.ec_item_id
                     WHERE ifv.ec_category_item_field_id={$key}
                       AND locate('" . \e::db_escape(trim($val)) . "',ifv.ec_category_item_field_value)>0
                )";
            }
        }
    }
}
# additional category fields
# prn(checkStr($restriction));
# --------------------------- prepare restrictions - end -----------------------
# --------------------------- get list - begin ---------------------------------
# get starting row number
if (!isset($input_vars['start']))
    $input_vars['start'] = 0;
$start = abs(round(1 * $input_vars['start']));



# ec_item_id                bigint(20)
# ec_item_lang              char(3)
# site_id                   bigint(20)
# ec_item_title             varchar(255)
# ec_item_content           longtext
# ec_item_cense_level       tinyint(2)
# ec_item_last_change_date  datetime
# ec_item_abstract          text
# ec_item_tags              text
# ec_item_price             float
# ec_item_currency          varchar(3)
# ec_item_amount            int(11)
# ec_producer_id            int(11)
# ec_category_id            int(11)
# ec_item_onnullamount      varchar(255)
# ec_item_mark              varchar(80)
# ec_item_uid               varchar(80)
# -------------------- number of items in the block - begin --------------------
$rows = 24;
if (isset($input_vars['rows']) && $rows > 0 && $rows < 1000)
    $rows = (int) $input_vars['rows'];

# -------------------- number of items in the block - end ----------------------
# -------------------- choose ordering - begin ---------------------------------
$ordering_variants = Array('ec_item_id', 'ec_item_lang', 'ec_item_title',
    'ec_item_last_change_date', 'ec_item_price',
    'ec_item_currency', 'ec_item_amount',
    'ec_producer_id', 'ec_category_id',
    'ec_item_uid', 'rand()', 'ec_item_purchases', 'ec_item_ordering');
$orderby = '';
if (isset($input_vars['orderby'])) {
    list($fld, $od) = explode(' ', $input_vars['orderby']);
    $od = strtolower($od);
    $fld = strtolower($fld);

    // ASC or DESC (default is ASC)
    if ($od != 'desc')
        $od = 'asc';

    // if field name is allowed
    if (in_array($fld, $ordering_variants))
        $orderby = "ORDER BY $fld $od";
}
else {
    // default ordering
    $orderby = "ORDER BY ec_item_ordering asc";
}
# -------------------- choose ordering - end ------------------------------------
//$query="SELECT SQL_CALC_FOUND_ROWS
//                 ec_item.ec_item_id,
//                 ec_item.ec_item_lang,
//                 ec_item.site_id,
//                 ec_item.ec_item_title,
//                 ec_item.ec_item_cense_level,
//                 ec_item.ec_item_last_change_date,
//                 ec_item.ec_item_abstract,
//                 ec_item.ec_item_tags,
//                 ec_item.ec_item_price,
//                 ec_item.ec_item_currency,
//                 ec_item.ec_item_amount,
//                 ec_item.ec_producer_id,
//                 ec_producer.ec_producer_title,
//                 ec_item.ec_category_id,
//                 ec_category.ec_category_title,
//                 ec_item.ec_item_onnullamount,
//                 ec_item.ec_item_mark,
//                 ec_item.ec_item_img,
//                 ec_item.ec_item_uid,
//                 IF(LENGTH(TRIM(ec_item.ec_item_content))>0,1,0) as ec_item_content_present
//           FROM <<tp>>ec_item AS ec_item
//                LEFT JOIN <<tp>>ec_category AS ec_category
//                ON ec_category.ec_category_id=ec_item.ec_category_id
//                LEFT JOIN <<tp>>ec_producer AS ec_producer
//                ON ec_producer.ec_producer_id=ec_item.ec_producer_id
//            WHERE ec_item.site_id={$site_id}
//              AND ec_item.ec_item_lang='{$lang}'
//        $restriction
//        $orderby
//            LIMIT $start,$rows";
$query = "SELECT SQL_CALC_FOUND_ROWS
                 ec_item.ec_item_id,
                 ec_item.ec_item_lang,
                 IF(LENGTH(TRIM(ec_item.ec_item_content))>0,1,0) as ec_item_content_present,
                 cached_info,
                 cache_datetime
           FROM <<tp>>ec_item AS ec_item
                LEFT JOIN <<tp>>ec_category AS ec_category
                ON ec_category.ec_category_id=ec_item.ec_category_id
                LEFT JOIN <<tp>>ec_producer AS ec_producer
                ON ec_producer.ec_producer_id=ec_item.ec_producer_id
            WHERE ec_item.site_id={$site_id}
              AND ec_item.ec_item_lang='{$lang}'
              $restriction
              $orderby
            LIMIT $start,$rows";
$list_of_ec_items = \e::db_getrows($query,[],false);
//prn($query);

$query = "SELECT FOUND_ROWS() AS n_records;";
$num = \e::db_getonerow($query);
// prn($query,$num);
$rows_found = (int) $num['n_records'];



$cnt = count($list_of_ec_items);
for ($i = 0; $i < $cnt; $i++) {
    if (checkDatetime($list_of_ec_items[$i]['cache_datetime'])) {
        if ((time() - strtotime($list_of_ec_items[$i]['cache_datetime'])) < (3600 + rand(0, 100))) {
            $list_of_ec_items[$i] = ec_item_adjust(unserialize($list_of_ec_items[$i]['cached_info']), $this_site_info);
        } else {
            $list_of_ec_items[$i] = get_ec_item_info($list_of_ec_items[$i]['ec_item_id'], $lang, $site_id);
        }
    } else {
        $list_of_ec_items[$i] = get_ec_item_info($list_of_ec_items[$i]['ec_item_id'], $lang, $site_id);
    }
}
# if($debug)
//prn(checkStr($query));
// prn($query,$list_of_ec_items);
//  prn($list_of_ec_items);
# --------------------------- get list - end -----------------------------------
# --------------------------- list of pages - begin ----------------------------
//$page_url_prefix=site_public_URL.'/index.php?'.query_string('^start$').'&start=';
$page_url_prefix = \e::url_public(\e::query_array("/start/")) . '&start=';

$pages = Array();
$imin = max(0, $start - 10 * $rows);
$imax = min($rows_found, $start + 10 * $rows);
if ($imin > 0) {
    $pages[] = Array('URL' => $page_url_prefix . '0', 'innerHTML' => '[1]');
    $pages[] = Array('URL' => '', 'innerHTML' => '...');
}

for ($i = $imin; $i < $imax; $i = $i + $rows) {
    if ($i == $start) {
        $to = '<b>[' . (1 + $i / $rows) . ']</b>';
        $pages[] = Array('URL' => '', 'innerHTML' => $to);
    } else {
        $to = (1 + $i / $rows);
        $pages[] = Array('URL' => $page_url_prefix . $i, 'innerHTML' => $to);
    }
}

if ($imax < $rows_found) {
    $last_page = floor($rows_found / $rows);
    if ($last_page > 0) {
        $pages[] = Array('URL' => '', 'innerHTML' => "...");
        $pages[] = Array('URL' => $page_url_prefix . ($last_page * $rows), 'innerHTML' => "[" . ($last_page + 1) . "]");
    }
}
# --------------------------- list of pages - end ------------------------------
# --------------------------- list of ordering links - begin -------------------
$url_prefix = site_root_URL . '/index.php?' . preg_query_string('/^orderby/') . '&orderby=';
$orderby = Array();
foreach ($ordering_variants as $val) {
    $orderby[$val . '_asc'] = $url_prefix . $val . '+asc';
    $orderby[$val . '_desc'] = $url_prefix . $val . '+desc';
}
# --------------------------- list of ordering links - end ---------------------
?>
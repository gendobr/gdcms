<?php

function rss_aggregator_get_list($site_id, $lang, $_start, $_rows_per_page, $filter = Array()) {

    $start = (int) $_start;
    if ($start < 0){
        $start = 0;
    }

    $rows_per_page = (int) $_rows_per_page;
    if ($rows_per_page < 1){
        $rows_per_page = \e::config('rows_per_page');
    }

    $query = "SELECT SQL_CALC_FOUND_ROWS * FROM {$GLOBALS['table_prefix']}rsssourceitem
            WHERE site_id=" . ( (int) $site_id ) . "
              AND rsssourceitem_lang='" . \e::db_escape($lang) . "'
              AND rsssourceitem_is_visiblle
            ORDER BY rsssourceitem_datetime DESC
            LIMIT $start, $rows_per_page";
    $rows = \e::db_getrows($query);

    $query = "SELECT FOUND_ROWS() AS n_records;";
    $num = \e::db_getonerow($query);
    // prn($query,$num);
    $rows_found = (int) $num['n_records'];

    // get paging links
    // * $start - first row to show on the page
    // * $n_records - total number of rows
    // * $rows_per_page - rows per page
    // * $url_template - template of the URL like "file.php?start={start}"
    $url_template=$_SERVER['PHP_SELF'].'?'.  preg_query_string('/start/').'&start={start}';
    $paging_links=get_paging_links($start, $rows_found, $rows_per_page, $url_template);

    return Array(
        'rows_found'=>$rows_found,
        'rows'=>$rows,
        'paging'=>$paging_links
    );
}

?>
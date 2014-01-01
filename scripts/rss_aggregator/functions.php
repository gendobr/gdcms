<?php
/*
 *
 */
function menu_rsssource($info){
    global $text, $db, $table_prefix;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];

    $tor['rsssource/edit'] = Array(
            'URL' => "index.php?action=rss_aggregator/source_edit&site_id={$info['site_id']}&rsssource_id={$info['rsssource_id']}"
            , 'innerHTML' => text('Edit_rsssource_properties')
            , 'attributes' => ""
    );

    $tor['rsssource/delete'] = Array(
              'URL' => "javascript:void($.post('index.php?action=rss_aggregator/source_delete&site_id={$info['site_id']}&rsssource_id={$info['rsssource_id']}',function(data) {alert(data);}))"
            , 'innerHTML' => text('Delete_rsssource')
            , 'attributes' => ""
    );
    return $tor;
}

function get_rsssource_info($rsssource_id){
    $query = "SELECT * FROM {$GLOBALS['table_prefix']}rsssource WHERE rsssource_id=" . ( (int)$rsssource_id);
    $info = db_getonerow($query);
    // prn($query, $info);
    return $info;
}


function menu_rsssourceitem($info){
    global $text, $db, $table_prefix;
    $tor = Array();

    $tor['rsssourceitem/allow'] = Array(
            'URL' => "javascript:void($.post('index.php?action=rss_aggregator/rsssourceitem_publish&rsssourceitem_is_visiblle=1&site_id={$info['site_id']}&rsssourceitem_id={$info['rsssourceitem_id']}',function(data) {if(confirm(data+'... Reload?')) window.location.reload();}))"
            , 'innerHTML' => text('rsssourceitem_allow_publication')
            , 'attributes' => ""
    );

    $tor['rsssourceitem/deny'] = Array(
            'URL' => "javascript:void($.post('index.php?action=rss_aggregator/rsssourceitem_publish&rsssourceitem_is_visiblle=0&site_id={$info['site_id']}&rsssourceitem_id={$info['rsssourceitem_id']}',function(data) {if(confirm(data+'... Reload?')) window.location.reload();}))"
            , 'innerHTML' => text('rsssourceitem_deny_publication')
            , 'attributes' => ""
    );
    $tor['rsssourceitem/delete'] = Array(
            'URL' => "javascript:void($.post('index.php?action=rss_aggregator/rsssourceitem_delete&site_id={$info['site_id']}&rsssourceitem_id={$info['rsssourceitem_id']}',function(data) {if(confirm(data+'... Reload?')) window.location.reload();}))"
            , 'innerHTML' => text('Delete_rsssourceitem')
            , 'attributes' => ""
    );
    return $tor;
}

function get_rsssourceitem_info($rsssourceitem_id){
    $query = "SELECT * FROM {$GLOBALS['table_prefix']}rsssourceitem WHERE rsssourceitem_id=" . ( (int)$rsssourceitem_id);
    $info = db_getonerow($query);
    // prn($query, $info);
    return $info;
}
?>
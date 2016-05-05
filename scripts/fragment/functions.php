<?php
/*
 * fragment related functions
 */
function menu_fragment($_info) {
    global $text;
    $tor = Array();
    // $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];

    $tor['fragment/edit'] = Array(
        'URL' => "index.php?action=fragment/edit&fragment_id={$_info['fragment_id']}&lang={$_info['fragment_lang']}"
        , 'innerHTML' => $text['fragment_edit']
        , 'attributes' => ''
    );

    $tor['fragment/delete'] = Array(
        'URL' => "index.php?action=fragment/list&site_id={$_info['site_id']}&delete_fragment={$_info['fragment_id']}.{$_info['fragment_lang']}"
        , 'innerHTML' => $text['fragment_delete']
        , 'attributes' => ' style="margin-top:20px;" '
    );

    return $tor;
}
?>
<?php

/*
  User context menu
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
 */

function menu_user($user_info) {
    global $text;
    $tor = Array();
    $tor['user/edit'] = Array(
        'URL' => "index.php?action=user/edit&user_id={$user_info['id']}"
        , 'innerHTML' => $text['Edit_user']
        , 'attributes' => ''
    );

    if ($_SESSION['user_info']['id'] != $user_info['id']) {
        $tor['user/delete'] = Array(
            'URL' => "index.php?action=user/list&delete_user_id={$user_info['id']}"
            , 'innerHTML' => $text['Delete_user']
            , 'attributes' => "  onclick='return confirm(\"{$text['Are_You_sure']}?\")' "
        );
    }

    $tor['user/sites'] = Array(
        'URL' => "index.php?action=user/sites&user_id={$user_info['id']}"
        , 'innerHTML' => $text['User_Sites']
        , 'attributes' => ''
    );

    return $tor;
}

?>
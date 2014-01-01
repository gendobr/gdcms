<?

/*
  draw menu for news
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */

function menu_news($news_info) {
    global $text, $db, $table_prefix;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];

    $tor['news/edit'] = Array(
        'URL' => "index.php?action=news/edit&site_id={$news_info['site_id']}&news_id={$news_info['id']}&lang={$news_info['lang']}"
        , 'innerHTML' => $text['Edit_news']
        , 'attributes' => ''
    );

    $tor['news/edit_a'] = Array(
        'URL' => "index.php?action=news/edit&site_id={$news_info['site_id']}&aed=1&news_id={$news_info['id']}&lang={$news_info['lang']}"
        , 'innerHTML' => $text['Advanced_Editor']
        , 'attributes' => ''
    );

    $tor['news/view'] = Array(
        'URL' => site_public_URL."/index.php?action=news/view_details&news_id={$news_info['id']}&lang={$news_info['lang']}" . '&' . $sid
        , 'innerHTML' => $text['View_page']
        , 'attributes' => ' target=_blank '
    );

    /*
      $tor['news/view']=Array(
      'URL'=>"index.php?action=news/view&site_id={$news_info['site_id']}&lang={$news_info['lang']}"
      ,'innerHTML'=>$text['View_news']
      ,'attributes'=>' target=_blank '
      );
     */
    #if($_REQUEST['action']=='news/edit')
    #{
    #   $ret=rawurlencode(base64_encode("index.php?action=news/list&site_id={$news_info['site_id']}&lang={$news_info['lang']}&filter_id={$news_info['id']}"));
    #}
    #else
    #{
    #   $ret=rawurlencode(base64_encode('index.php?'.query_string('^'.session_name().'$')));
    #}
    $ret = '';
    $tor['news/add'] = Array(
        'URL' => "index.php?action=news/add&site_id=" . $news_info['site_id'] . "&news_id=" . $news_info['id'] . "&news_lang=" . $news_info['lang'] . "&return=" . $ret
        , 'innerHTML' => $text['Add_translation']
        , 'attributes' => ' style="margin-bottom:10pt;" '
    );

    //javascript:void(request=ajax_load('',false,function (){ if (request.readyState == 4)   window.location.reload();}));
    //--------------------------- document flow - begin -------------------------
    $tor['news/approve'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/approve&transition=approve&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => $text['Approve']
        , 'attributes' => ''
    );
    $tor['news/seize'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/approve&transition=seize&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => $text['Seize_to_revize']
        , 'attributes' => ''
    );
    $tor['news/return'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/approve&transition=return&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => $text['Return_to_previous_operator']
        , 'attributes' => " title=\"{$text['Return_to_previous_operator']}\" style='margin-bottom:10pt;' "
    );
    //--------------------------- document flow - end ---------------------------

    $tor['news/Move_up'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/set_weight&weight=-1&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => text('Move_up')
        , 'attributes' => " title=\"" . text('Move_up') . "\" "
    );
    $tor['news/Move_down'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/set_weight&weight=1&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => text('Move_down')
        , 'attributes' => " title=\"" . text('Move_down') . "\" style='margin-bottom:10pt;' "
    );

    $tor['news_subscription/send'] = Array(
        'URL' => "index.php?action=news_subscription/send_news&news_id={$news_info['id']}&lang={$news_info['lang']}"
        , 'innerHTML' => text('Email_to_subscribers')
        , 'attributes' => " target=_blank "
    );

    if (is_admin()) {
        $tor['news/delete'] = Array(
            'URL' => "index.php?action=news/delete" .
            "&site_id=" . $news_info['site_id'] .
            "&delete_news_id=" . $news_info['id'] .
            "&delete_news_lang=" . $news_info['lang']
            , 'innerHTML' => $text['Delete_news'] . '<iframe src="about:blank" width=10px height=1px style="border:none;" name="frm_delete"></iframe>'
            , 'attributes' => " onclick='return confirm(\"{$text['Are_You_sure']}?\")' target=frm_delete "
        );
    }
    return $tor;
}

function news_get_view($news_list, $lang) {
    if (count($news_list) == 0)
        return Array();
    $news_list = array_values($news_list);
    $site_id = $news_list[0]['site_id'];

    // prn($ids);
    $ids = Array(0);
    $cnt = count($news_list);
    for ($i = 0; $i < $cnt; $i++) {
        $ids[] = (int) $news_list[$i]['id'];
    }

    $query = "select * from {$GLOBALS['table_prefix']}news_category where news_id in (" . join(',', $ids) . ")";
    $categories = db_getrows($query);
    // prn($categories);
    $category_ids = Array(0 => 1);
    $_category = Array();
    foreach ($categories as $cat) {
        $category_ids[(int) $cat['category_id']] = 1;
        if (!isset($_category[$cat['news_id']])) {
            $_category[$cat['news_id']] = Array();
        }
        $_category[$cat['news_id']][] = $cat['category_id'];
    }
    // prn('$category_ids', $category_ids, '$_category', $_category);

    $query = "SELECT * FROM {$GLOBALS['table_prefix']}category WHERE category_id in(" . join(',', array_keys($category_ids)) . ")";
    $tmp = db_getrows($query);
    // prn($query,$tmp);
    $ncat = count($tmp);
    $categories = Array();
    

    for ($i = 0; $i < $ncat; $i++) {
        $categories[$tmp[$i]['category_id']]['category_title'] = get_langstring($tmp[$i]['category_title'], $lang);
        $categories[$tmp[$i]['category_id']]['URL'] = str_replace(
                Array('{path}'        ,'{lang}','{site_id}','{category_id}','{category_code}'),
                Array($tmp[$i]['path'],$lang   ,$site_id   ,$tmp[$i]['category_id'],$tmp[$i]['category_code']),
                url_pattern_category);
        $categories[$tmp[$i]['category_id']]['deep'] = $tmp[$i]['deep'];
    }
    //prn($categories);

    $cnt = count($news_list);
    for ($i = 0; $i < $cnt; $i++) {
        // $news_list[$i]['URL_view_details'] = url_prefix_news_details . "news_id={$news_list[$i]['id']}&lang={$lang}";
        $news_list[$i]['URL_view_details'] = str_replace(
                Array('{news_id}','{lang}','{news_code}'),
                Array($news_list[$i]['id'],$lang,$news_list[$i]['news_code']),
                url_template_news_details);
        //url_prefix_news_details . "news_id={$news_list[$i]['id']}&lang={$lang}";
        
        $news_list[$i]['tag_links'] = news_tag_links($news_list[$i]['tags'],$news_list[$i]['site_id'],$lang);
        $news_list[$i]['categories'] = Array();
        if (isset($_category[$news_list[$i]['id']])) {
            foreach ($_category[$news_list[$i]['id']] as $cat_id) {
                if(isset($categories[$cat_id])) $news_list[$i]['categories'][] = $categories[$cat_id];
            }
        }
    }
    // prn($news_list);
    // preprocess tags


    return $news_list;
}

function news_tag_links($tag_string,$site_id,$lang) {
    $tags = Array();
    //$input_vars['tags']
    $tmp = trim($tag_string);
    if (strlen($tmp) > 0) {
        $tags = preg_split("/,|;|\\./", $tag_string);
        $cnt = count($tags);
        $prefix=site_root_URL . "/index.php?action=news/view&site_id={$site_id}&lang={$lang}&tag=";
        for ($i = 0; $i < $cnt; $i++) {
            $tags[$i] = trim($tags[$i]);
            $tags[$i] = preg_replace("/ +/", " ", $tags[$i]);
            $tags[$i] = Array(
                'name'=>$tags[$i],
                'URL'=>$prefix.  rawurlencode($tags[$i])
            );
        }
    }
    return $tags;
}


function menu_news_comment($info){
    global $text, $db, $table_prefix;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];
    $tor['news/view'] = Array(
          'URL' => "index.php?action=news/view_details&news_id={$info['news_id']}&lang={$info['news_lang']}"
        , 'innerHTML' => $text['View_news']
        , 'attributes' => ' target=_blank '
    );

    $prefix=site_URL.'?'. preg_query_string("/hide_comment|show_comment/");
    $tor['news/hide_comment'] = Array(
          'URL' => $prefix."&hide_comment={$info['news_comment_id']}"
        , 'innerHTML' => $text['news_comment_hide']
        , 'attributes' => ''
    );
    $tor['news/show_comment'] = Array(
          'URL' => $prefix."&show_comment={$info['news_comment_id']}"
        , 'innerHTML' => $text['news_comment_show']
        , 'attributes' => ''
    );
    return $tor;
}

?>
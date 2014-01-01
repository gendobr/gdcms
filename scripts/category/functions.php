<?php

function menu_category($_info = false) {
    global $input_vars;
    $menu = Array();

    if ($_info) {
        // # ------------------------ selected menu - begin ---------------------
        # visible to all
        //if(is_librarian())

        $menu[] = Array(
            'url' => ''
            , 'html' => "<b>" . get_langstring($_info['category_title']) . " : </b>"
            , 'attributes' => ''
        );


        $menu[] = Array(
            'url' => 'index.php?action=category/browse&category_id=' . $_info['category_id'] . "&site_id={$_info['site_id']}&lang={$_SESSION['lang']}"
            , 'html' => text("Preview")
            , 'attributes' => ' target=_blank '
        );

        $menu[] = Array(
            'url' => 'index.php?action=category/list&category_id=' . $_info['category_id'] . '&add_child=yes' . "&site_id={$_info['site_id']}"
            , 'html' => text("Create_subcategory")
            , 'attributes' => ''
        );

        $menu[] = Array(
            'url' => 'index.php?action=category/edit&category_id=' . $_info['category_id'] . "&site_id={$_info['site_id']}"
            , 'html' => text("Edit")
            , 'attributes' => ''
        );
        $menu[] = Array(
            'url' => 'index.php?action=category/edit&category_id=' . $_info['category_id'] . "&site_id={$_info['site_id']}&aed=1"
            , 'html' => text('Advanced_Editor')
            , 'attributes' => ' style="margin-bottom:20px;"'
        );

        $menu[] = Array(
            'url' => 'index.php?action=category/list&category_id=' . $_info['category_id'] . "&site_id={$_info['site_id']}"
            , 'html' => text('Open') . "<br><br>"
            , 'attributes' => ''
        );

        if ($_info['start'] > 0)
            $menu[] = Array(
                'url' => 'index.php?action=category/list&category_delete=yes&category[' . $_info['category_id'] . ']=' . $_info['category_id'] . "&site_id={$_info['site_id']}"
                , 'html' => text('Delete')
                , 'attributes' => ' style="color:red;margin-top:20px;" onclick="return confirm(\'¬ы действительно хотите удалить категорию ' . checkStr(" {$_info['category_title']} ") . '\')" '
            );
    }# ------------------------ selected menu - end -----------------------


    $cnt = count($menu);
    for ($i = 0; $i < $cnt; $i++) {
        $menu[$i]['innerHTML'] = &$menu[$i]['html'];
        $menu[$i]['URL'] = &$menu[$i]['url'];
    }
    return $menu;
}

function adjust($_info, $category_id) {
    $tor = $_info;
    $tor['context_menu'] = menu_category($tor);
    unset($tor['context_menu']['start']);

    $tor['category_title'] = get_langstring($tor['category_title']);

    $tor['title_short'] = shorten($tor['category_title']);
    $tor['padding'] = 20 * $tor['deep'];
    $tor['URL'] = "index.php?action=category/list&category_id={$tor['category_id']}&site_id={$_info['site_id']}";
    $tor['URL_move_up'] = "index.php?action=category/list&category_id=$category_id&move_up={$tor['category_id']}&site_id={$_info['site_id']}";
    $tor['URL_move_down'] = "index.php?action=category/list&category_id=$category_id&move_down={$tor['category_id']}&site_id={$_info['site_id']}";
    $tor['has_subcategories'] = ($tor['finish'] - $tor['start'] > 1) ? '>>>' : '';


    // date_lang_update
    $tmp=$tor['date_lang_update'];
    $tmp=explode('<',$tmp);
    $cnt=count($tmp);
    if($cnt>1){
       $date_lang_update=Array();
       for($i=1;$i<$cnt; $i+=2){
           $tmp[$i]=explode('>',$tmp[$i]);
           $date_lang_update[$tmp[$i][0]]=$tmp[$i][1];
       }
    }else{
       $date_lang_update=Array();
    }
    $tor['date_lang_update_array']=$date_lang_update;
    // prn('date_lang_update',$tor['date_lang_update_array']);
    # prn($query,$this_page_info);
    //prn('    tor= ',$tor);
    return $tor;
}

function category_public_list($site_id, $lang) {
    // ------------------ get list of categories - begin -----------------------
    $query = "select ch.*, bit_and(pa.is_visible) as visible
              from {$GLOBALS['table_prefix']}category pa,
                   {$GLOBALS['table_prefix']}category ch
              where pa.start<=ch.start and ch.finish<=pa.finish
                and pa.site_id=" . ((int) $site_id) . "
                and ch.site_id=" . ((int) $site_id) . "
              group by ch.category_id
              having visible>0
              order by  ch.start";
    $caterory_list = db_getrows($query);
    // ------------------ get list of categories - end -------------------------

    // ------------------ adjust list of categories - begin --------------------
    // $category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$lang}&category_id=";
    $category_url_pattern = str_replace(Array('{site_id}', '{lang}'), Array((int) $site_id, $lang), url_pattern_category);
    $cnt = count($caterory_list);
    for ($i = 0; $i < $cnt; $i++) {
        $caterory_list[$i]['category_title'] = get_langstring($caterory_list[$i]['category_title'], $lang);
        $caterory_list[$i]['category_description'] = get_langstring($caterory_list[$i]['category_description'], $lang);
        $caterory_list[$i]['URL'] = str_replace('{category_id}', $caterory_list[$i]['category_id'], $category_url_pattern);
        $caterory_list[$i]['number_of_news']=0;
    }
    // prn($caterory_list);
    // ------------------ adjust list of categories - end ----------------------

    // ------------------ get number of news - begin ---------------------------
    $category_ids = Array();
    $category_ids[] = 0;
    foreach ($caterory_list as $cat) {
        $category_ids[] = (int) $cat['category_id'];
    }
    $category_ids = join(',', $category_ids);
    $query = "SELECT category_id, count(news_id) as n_news
           FROM {$GLOBALS['table_prefix']}news_category
           WHERE category_id in({$category_ids}) GROUP BY category_id";
    $number_of_news = db_getrows($query);

    foreach($number_of_news as $n_news){
        for ($i = 0; $i < $cnt; $i++) {
            if($caterory_list[$i]['category_id'] == $n_news['category_id']){
               $caterory_list[$i]['number_of_news']+=$n_news['n_news'];
               $deep=$caterory_list[$i]['deep'];
               for($j=$i-1;$j>=0; $j--){
                   if($deep>$caterory_list[$j]['deep']){
                       $caterory_list[$j]['number_of_news']+=$n_news['n_news'];
                       $deep=$caterory_list[$j]['deep'];
                   }
               }
               break;
            }
        }
    }
    // ------------------ get number of news - end -----------------------------

    return $caterory_list;
}

?>
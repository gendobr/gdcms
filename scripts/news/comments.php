<?php

/*
  List of news for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */

$debug = false;
run('site/menu');

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
//
// hide comment
if(isset($input_vars['hide_comment'])){
    $news_comment_id=(int)$input_vars['hide_comment'];
    \e::db_execute("UPDATE <<tp>>news_comment SET news_comment_is_visible=0 WHERE site_id={$site_id} AND news_comment_id={$news_comment_id}");
}
// show comment
if(isset($input_vars['show_comment'])){
    $news_comment_id=(int)$input_vars['show_comment'];
    \e::db_execute("UPDATE <<tp>>news_comment SET news_comment_is_visible=1 WHERE site_id={$site_id} AND news_comment_id={$news_comment_id}");
}

//
//--------------------------- get list -- begin --------------------------------
run("lib/class_report");
run("lib/class_report_extended");
$re = new report_generator;

$re->distinct = false;

$re->from = "<<tp>>news_comment as news_comments
             left join <<tp>>news as news
             ON (news_comments.news_id=news_id)";
$re->add_where(" news_comments.site_id={$site_id} ");
$re->add_where(" news_comments.news_id=news.id ");
$re->add_where(" news.site_id={$site_id} ");



$re->add_field($field = 'news_comments.news_comment_id'
        , $alias = 'news_comment_id'
        , $type = 'id:hidden=no'
        , $label = '#'
        , $_group_operation = false);

$re->add_field($field = 'news_comments.site_id'
        , $alias = 'site_id'
        , $type = 'id:hidden=yes'
        , $label = $text['Site_id']
        , $_group_operation = false);

$re->add_field($field = 'news_comments.news_id'
        , $alias = 'news_id'
        , $type = 'id:hidden=yes'
        , $label = 'news_id'
        , $_group_operation = false);

$re->add_field($field = 'news_comments.news_lang'
        , $alias = 'news_lang'
        , $type = 'id:hidden=yes'
        , $label = 'news_lang'
        , $_group_operation = false);

$re->add_field($field = 'news_comments.news_comment_sender'
        , $alias = 'news_comment_sender'
        , $type = 'string'
        , $label = text('news_comment_sender')
        , $_group_operation = false);

$re->add_field($field = 'news_comments.news_comment_content'
        , $alias = 'news_comment_content'
        , $type = 'string'
        , $label = $text['news_comment_content']
        , $_group_operation = false);

$re->add_field($field = 'news.title'
        , $alias = 'title'
        , $type = 'string'
        , $label = $text['News_title']
        , $_group_operation = false);



$re->add_field($field = 'news_comments.news_comment_datetime'
        , $alias = 'news_comment_datetime'
        , $type = 'datetime'
        , $label = $text['Date']
        , $_group_operation = false);




$re->add_field($field = 'news_comments.news_comment_is_visible'
        , $alias = 'news_comment_is_visible'
        , $type =  $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        , $label = text('news_comment_is_visible')
        , $_group_operation = false);

unset($field, $alias, $type, $label, $_group_operation);
//prn($re->create_query());
$response = $re->show();
//prn($response);
//--------------------------- get list -- end ----------------------------------

$input_vars['page_title'] = $this_site_info['title'] . ' - ' . text('news_comment_management');
$input_vars['page_header'] = $this_site_info['title'] . ' - ' . text('news_comment_management');

//--------------------------- context menu -- begin ----------------------------
run('news/menu');
$cnt = count($response['rows']);
for ($i = 0; $i < $cnt; $i++) {
    //--------------------------- context menu -- begin ------------------------
    $response['rows'][$i]['context_menu'] = menu_news_comment($response['rows'][$i]);
    $response['rows'][$i]['title'] = wordwrap($response['rows'][$i]['title'], 10, " ",true);
    $response['rows'][$i]['news_comment_content'] = "<div title=\"".str_replace('"','`',$response['rows'][$i]['news_comment_content'])."\">".shorten($response['rows'][$i]['news_comment_content'])."</div>";

    //--------------------------- context menu -- end --------------------------
}
// prn($response['rows']);
//--------------------------- context menu -- end ------------------------------

$input_vars['page_content'] = $re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
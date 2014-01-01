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
//--------------------------- get list -- begin --------------------------------
run("lib/class_report");
run("lib/class_report_extended");
$re = new report_generator;
$re->db = $db;
$re->distinct = false;

$re->from = "{$table_prefix}news AS news
             LEFT JOIN {$table_prefix}category AS category
             ON (category.site_id=news.site_id AND category.category_id=news.category_id)
             left join {$table_prefix}news as tr
             on(news.id=tr.id and news.lang<>tr.lang)
             ";
$re->add_where(" news.site_id={$site_id} ");

$re->add_field($field = 'news.id'
        , $alias = 'id'
        , $type = 'id:hidden=no'
        , $label = '#'
        , $_group_operation = false);

//---------------- list of languages - begin ---------------------------------
$LL = join('&', GetAssociatedArray(db_execute("SELECT lang,CONCAT(lang,'=',lang) FROM {$table_prefix}news WHERE site_id={$site_id}")));
$re->add_field($field = 'news.lang'
        , $alias = 'lang'
        , $type = 'enum:' . $LL
        , $label = $text['Language']
        , $_group_operation = false);
//---------------- list of languages - end -----------------------------------

$re->add_field($field = 'news.site_id'
        , $alias = 'site_id'
        , $type = 'id:hidden=yes'
        , $label = $text['Site_id']
        , $_group_operation = false);


$re->add_field($field = 'news.title'
        , $alias = 'title'
        , $type = 'string'
        , $label = $text['News_title']
        , $_group_operation = false);

$re->add_field($field = "(     news.cense_level>={$this_site_info['cense_level']}
                            and ( now() >= news.last_change_date )
                            and ( now() <= news.expiration_date OR news.expiration_date is null)
                          )"
        , $alias = 'is_under_construction'
        , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        , $label = "Pub&shy;lic"//$text['Published']
        , $_group_operation = false);


$re->add_field($field = 'news.last_change_date'
        , $alias = 'last_change_date'
        , $type = 'datetime'
        , $label = $text['Date']
        , $_group_operation = false);

$re->add_field($field = 'news.expiration_date'
        , $alias = 'expiration_date'
        , $type = 'datetime'
        , $label = text('Date_end')
        , $_group_operation = false);

//
# ------------------------ list of categories - begin -------------------------
$query = "SELECT category_id, category_title, deep
            FROM {$table_prefix}category
            WHERE start>0 AND site_id={$site_id}
            ORDER BY start ASC";
$tmp = db_getrows($query);
$list_of_categories = Array();
$prev_deep = 0;
$path = Array();
foreach ($tmp as $tm) {
    for ($i = $tm['deep']; $i <= $prev_deep; $i++)
        array_pop($path);

    $path[] = get_langstring($tm['category_title']);
    $list_of_categories[$tm['category_id']] =
            $tm['category_id']
            . '='
            . rawurlencode(join('>', $path));
    $prev_deep = $tm['deep'];
}
unset($tmp, $tm);
$list_of_categories = join('&', $list_of_categories);
//prn($list_of_categories);
# ------------------------ list of categories - end ---------------------------

$re->add_field($field = 'news.category_id'
        , $alias = 'category_id'
        , $type = 'enum:' . $list_of_categories
        , $label = $text['News_Category']
        , $_group_operation = false);

$re->add_field($field = 'news.tags'
        , $alias = 'tags'
        , $type = 'string'
        , $label = $text['News_tags']
        , $_group_operation = false);
$re->add_field($field = 'news.weight'
        , $alias = 'weight'
        , $type = 'integer'
        , $label = text('weight')
        , $_group_operation = false);

$re->add_field($field = 'count(distinct tr.lang)'
        , $alias = 'n_translations'
        , $type = 'integer'
        , $label = 'n trans&shy;la&shy;tions'
        , $_group_operation = true);



    $re->add_field($field = 'news.creation_date'
            , $alias = 'creation_date'
            , $type = 'datetime'
            , $label = 'crea&shy;ted'
            , $_group_operation = false);

//
unset($field, $alias, $type, $label, $_group_operation);
//prn($re->create_query());
$response = $re->show();
//prn($response);
//--------------------------- get list -- end ----------------------------------

$input_vars['page_title'] = $this_site_info['title'] . ' - ' . $text['List_of_news'];
$input_vars['page_header'] = $this_site_info['title'] . ' - ' . $text['List_of_news'];

//--------------------------- context menu -- begin ----------------------------
run('news/menu');
$cnt = count($response['rows']);
for ($i = 0; $i < $cnt; $i++) {
    //--------------------------- context menu -- begin ------------------------
    $response['rows'][$i]['context_menu'] = menu_news($response['rows'][$i]);
    $response['rows'][$i]['category_id'] = wordwrap($response['rows'][$i]['category_id'], 10, " ",true);
    $response['rows'][$i]['tags'] = wordwrap($response['rows'][$i]['tags'], 10, "&shy;",true);
    //--------------------------- context menu -- end --------------------------
}
//--------------------------- context menu -- end ------------------------------

$input_vars['page_content'] = $re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
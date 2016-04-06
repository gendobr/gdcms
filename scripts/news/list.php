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


//$re->from = "{$table_prefix}news AS news
//             LEFT JOIN {$table_prefix}category AS category
//             ON (category.site_id=news.site_id AND category.category_id=news.category_id)
//             left join {$table_prefix}news as tr
//             on(news.id=tr.id and news.lang<>tr.lang)
//             ";
$re->from = "{$table_prefix}news AS news";

$re->add_where(" news.site_id={$site_id} ");

//
$re->add_field($field = 'news.id'
        , $alias = 'id'
        , $type = 'id:hidden=no'
        , $label = '#'
        , $_group_operation = false);

//---------------- list of languages - begin ---------------------------------
//
$LL = join('&', \e::db_get_associated_array("SELECT DISTINCT lang,CONCAT(lang,'=',lang) FROM {$table_prefix}news WHERE site_id={$site_id}"));
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


//
$re->add_field($field = 'news.title'
        , $alias = 'title'
        , $type = 'string'
        , $label = $text['News_title']
        , $_group_operation = false);

//
$re->add_field($field = "(     news.cense_level>={$this_site_info['cense_level']}
                            and ( now() >= news.last_change_date )
                            and ( now() <= news.expiration_date OR news.expiration_date is null)
                          )"
        , $alias = 'is_under_construction'
        , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        , $label = $text['Published']
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


$re->add_field($field = 'null'
        , $alias = 'category_id'
        , $type = 'string'
        , $label = $text['News_Category']
        , $_group_operation = false);

$re->add_field($field = 'news.tags'
        , $alias = 'tags'
        , $type = 'string'
        , $label = $text['News_tags']
        , $_group_operation = false);


//
$re->add_field($field = 'news.weight'
        , $alias = 'weight'
        , $type = 'integer'
        , $label = text('weight')
        , $_group_operation = false);

$re->add_field($field = '0'
        , $alias = 'n_translations'
        , $type = 'integer'
        , $label = 'n trans&shy;la&shy;tions'
        , $_group_operation = false);


//
    $re->add_field($field = 'news.creation_date'
            , $alias = 'creation_date'
            , $type = 'datetime'
            , $label = text('created')
            , $_group_operation = false);

//
unset($field, $alias, $type, $label, $_group_operation);


$filter_category=isset($input_vars['filter_category'])? ( (int)$input_vars['filter_category'] ):0;
if($filter_category>0){
    $re->add_where(" news.id in (SELECT news_id FROM {$table_prefix}news_category WHERE category_id={$filter_category}) ");
}

//prn($re->create_query());
$response = $re->show();
//prn($response);
//--------------------------- get list -- end ----------------------------------

$input_vars['page_title'] = $this_site_info['title'] . ' - ' . $text['List_of_news'];
$input_vars['page_header'] = $this_site_info['title'] . ' - ' . $text['List_of_news'];

//--------------------------- context menu -- begin ----------------------------
run('news/menu');
$cnt = count($response['rows']);

$news_ids=Array();
for ($i = 0; $i < $cnt; $i++) {
    $news_ids[]=(int)$response['rows'][$i]['id'];
}

// get news categories
$news_categories=Array();
$news_translations=Array();
if(count($news_ids)>0){
    $tmp=\e::db_getrows(
            "SELECT news_category.news_id, category.category_title
             FROM {$table_prefix}news_category news_category 
                  INNER JOIN {$table_prefix}category category
                  ON news_category.category_id=category.category_id
             WHERE news_category.news_id IN(".join(',',$news_ids).")");
    foreach($tmp as $tm){
        if(!isset($news_categories[$tm['news_id']])){
            $news_categories[$tm['news_id']]=Array();
        }
        $news_categories[$tm['news_id']][]=get_langstring($tm['category_title']);
    }
    
    $news_categories=array_map(function($el){return mb_wordwrap(join(',',$el), 10, "&shy;",true);}, $news_categories);
    //prn($news_categories);
    
    $tmp=\e::db_getrows(
            "SELECT news.id, count(*) as n
             FROM {$table_prefix}news news 
             WHERE news.id IN(".join(',',$news_ids).")
             GROUP BY news.id
             ");
    foreach($tmp as $tm){
        $news_translations[$tm['id']]=$tm['n']-1;
    }
}



for ($i = 0; $i < $cnt; $i++) {
    //--------------------------- context menu -- begin ------------------------
    $response['rows'][$i]['context_menu'] = menu_news($response['rows'][$i]);
    if(isset($news_categories[$response['rows'][$i]['id']])){
        $response['rows'][$i]['category_id'] = $news_categories[$response['rows'][$i]['id']];
    }
    
    $response['rows'][$i]['n_translations'] = $news_translations[$response['rows'][$i]['id']];
    
    //$response['rows'][$i]['category_id'] = mb_wordwrap($response['rows'][$i]['category_id'], 10, "&shy;",true);
    $response['rows'][$i]['tags'] = mb_wordwrap($response['rows'][$i]['tags'], 10, "&shy;",true);
    //--------------------------- context menu -- end --------------------------
}
//--------------------------- context menu -- end ------------------------------


// -------------------------- draw - begin -------------------------------------
$html=$re->draw_header($response);



// ----------------------------- filter - begin --------------------------------
$html.="<form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
       {$response['hidden_fields']}";

$html.="
<style type=\"text/css\">
   .filter_element_xs{
        display:inline-block;
        width:12.4%;
        vertical-align:top;
    }
   .filter_element_s{
        display:inline-block;
        width:24.9%;
        vertical-align:top;
    }
   .filter_element_m{
        display:inline-block;
        width:49.4%;
        vertical-align:top;
    }
   .filter_element_l{
        display:inline-block;
        width:99.9%;
        vertical-align:top;
    }
</style>
<script>
        $(function() {

           $(\".datepicker\").each(function(ind,elm){
             //console.log(elm);
             var current_date_text=$(elm).val();
             $(elm).datepicker();
             $(elm).datepicker(\"option\", \"dateFormat\", \"yy-mm-dd 00:00\");
             // $(elm).datepicker( \"option\", $.datepicker.regional[ 'ru' ] );
             if(current_date_text!=''){
                var tmp=current_date_text.split(' ');
                var date=tmp[0].split('-');
                var year=date[0];
                var month=date[1]-1;
                var day=date[2];
                var hours=0,minutes=0, seconds=0, milliseconds=0;
                $(elm).datepicker(\"setDate\", new Date(year, month, day) );
                //alert(current_date_text+
                //      ' | '+ (new Date(year, month, day))+
                //      ' | '+ year +
                //      ' | '+ month +
                //      ' | '+ day);
                //$(elm).datepicker(\"setDate\", new Date(current_date_text) );
             }
           });
        });
</script>
";

$fld=$re->field['title'];
$value = isset($response['fields'][$fld['alias']]['filter']['form_element_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_value'] : '';
$html.="<span class=\"filter_element_m\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$value}\"
                                     style=\"width:99%;\"
                                     size=3>
</span>";


                   
//
# ------------------------ list of categories - begin -------------------------
$query = "SELECT category_id, category_title, deep
            FROM {$table_prefix}category
            WHERE start>0 AND site_id={$site_id}
            ORDER BY start ASC";
$tmp = \e::db_getrows($query);
$list_of_categories = Array();
$prev_deep = 0;
$path = Array();
foreach ($tmp as $tm) {
    for ($i = $tm['deep']; $i <= $prev_deep; $i++){
        array_pop($path);
    }

    $path[] = get_langstring($tm['category_title']);
    $list_of_categories[$tm['category_id']] =join('>', $path);
    $prev_deep = $tm['deep'];
}
unset($tmp, $tm);
//prn($list_of_categories);
# ------------------------ list of categories - end ---------------------------

$fld=$re->field['category_id'];
$html.="<span class=\"filter_element_m\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <select name =\"filter_category\" style=\"width:99%;\">
                    <option value=''> </option>
                    ".  draw_options(isset($input_vars['filter_category'])?$input_vars['filter_category']:'', $list_of_categories)."
                    </select>
</span>";
                                     
$fld=$re->field['id'];
$value = isset($response['fields'][$fld['alias']]['filter']['form_element_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_value'] : '';
$html.="<span class=\"filter_element_xs\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$value}\"
                                     style=\"width:99%;\"
                                     size=3>
</span>";
                                     
$fld=$re->field['lang'];
$html.="<span class=\"filter_element_xs\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\" style=\"width:99%;\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
</span>";
                    
$fld=$re->field['is_under_construction'];
$html.="<span class=\"filter_element_xs\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\" style=\"width:99%;\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
</span>";
                    
                    
$fld=$re->field['weight'];
$value_min = isset($response['fields'][$fld['alias']]['filter']['form_element_min_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_min_value'] : '';
$value_max = isset($response['fields'][$fld['alias']]['filter']['form_element_max_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_max_value'] : '';
$html.="<span  class=\"filter_element_xs\">
        <div>{$response['fields'][$fld['alias']]['label']}</div>
        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                     value=\"{$value_min}\"
                     size=3>
        ...
        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
               value=\"{$value_max}\"
               size=3></nobr>
</span>";
            

               
$fld=$re->field['last_change_date'];          
if (!isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])) {
    $response['fields'][$fld['alias']]['filter']['form_element_min_value'] = '';
}
if (!isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])) {
    $response['fields'][$fld['alias']]['filter']['form_element_max_value'] = '';
}
$html.="<span  class=\"filter_element_s\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <nobr>
                    <input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_value']}\"
                                     style=\"width:100px\"
                                     class=\"datepicker\">
                    ...
                    <input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_value']}\"
                                     style=\"width:100px\" class=\"datepicker\"></nobr>
</span>";

                                     
$fld=$re->field['creation_date'];          
if (!isset($response['fields'][$fld['alias']]['filter']['form_element_min_value'])) {
    $response['fields'][$fld['alias']]['filter']['form_element_min_value'] = '';
}
if (!isset($response['fields'][$fld['alias']]['filter']['form_element_max_value'])) {
    $response['fields'][$fld['alias']]['filter']['form_element_max_value'] = '';
}
$html.="<span  class=\"filter_element_s\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <nobr>
                    <input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_value']}\"
                                     style=\"width:100px\"
                                     class=\"datepicker\">
                    ...
                    <input type=text
                                     name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     id=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_value']}\"
                                     style=\"width:100px\" class=\"datepicker\"></nobr>
</span>";

                                     
                                     
$fld=$re->field['tags'];
$value = isset($response['fields'][$fld['alias']]['filter']['form_element_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_value'] : '';
$html.="<span class=\"filter_element_m\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$value}\"
                                     style=\"width:99%;\"
                                     size=3>
</span>";
                  
$html.="                                  
<div style=\"float:right;\"><div>&nbsp;</div><input type=submit name=submit value=\"".text('Search')."\"></div>
    <hr>
";
                
// ------------------------- filter - end --------------------------------------

$html.="<table border=1>";

        // ------------------------- header -- begin ------------------------------
$html.="<tr><th align=center valign=top></th>\n";
foreach ($re->field as $fld) {
    if (!isset($fld['options']['hidden'])) {
        $fld['options']['hidden'] = '';
    }
    if ($fld['options']['hidden'] == 'yes') {
        continue;
    }
    if($fld['alias']=='n_translations'){
    $html.="
              <th align=center valign=bottom>
               <b>{$response['fields'][$fld['alias']]['label']}</b><br>
               </th>
               \n";
        
    }else{
    $html.="
              <th align=center valign=bottom>
               <b>{$response['fields'][$fld['alias']]['label']}</b><br>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_asc']}\">V</a>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_desc']}\">&Lambda;</a>
               </th>
               \n";
        
    }
}
$html.="</tr>\n";

$html.=$re->draw_rows($response);
$html.=$re->draw_paging($response);
$html.="</table>\n";
$html.="</form>\n";
// -------------------------- draw - end ---------------------------------------


$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

<?php
header("Content-Type:text/html; charset=".site_charset);

if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
$input_vars['lang'] = get_language('lang');


//------------------- get site info - begin ------------------------------------
if (!function_exists('get_site_info'))
    run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
//prn($this_site_info);die();
//prn($input_vars);
if (!$this_site_info){
    die($txt['Site_not_found']);
}
//------------------- get site info - end --------------------------------------

$GLOBALS['main_template_name'] = '';

if(isset($input_vars['help'])){

    $elementId='newsTags'.time();
    echo '<pre>'.
    checkStr("
        <script type=\"text/javascript\" src=\"".site_root_URL."/scripts/lib/ajax.js\"></script>
        <script type=\"text/javascript\">
          var request=new ajax(
            \"".site_root_URL."/index.php?action=news/tag_list&site_id=$site_id&lang={$_SESSION['lang']}\",
            null,
            function (responseText){
               var tags;
               eval('tags='+responseText+';');
               var html='';
               if(tags){
                   for(var i in tags){
                       html+='<a class=\"news_tag\" href=\"'+tags[i]['url']+'\">'+tags[i]['html']+' ('+tags[i]['n']+')</a> ';
                   }
                   document.getElementById('$elementId').innerHTML=html;
               }
            })
        </script>
        <div id=\"$elementId\"></div>
    ").'</pre>';

    return '';
}
$lang = \e::db_escape($input_vars['lang']);
$query = "SELECT DISTINCT news_tags.tag, count(news.id) as n_news
           FROM {$table_prefix}news_tags AS news_tags
              , {$table_prefix}news AS news
           WHERE news_tags.news_id=news.id
             AND news.lang=news_tags.lang
             AND news.cense_level>={$this_site_info['cense_level']}
             AND news.site_id={$site_id}
             AND news.lang='{$lang}'
           group by news_tags.tag
           having n_news>0
           order by tag";
//prn($query);
$tags = \e::db_getrows($query);


if (count($tags) > 0) {

    $url_prefix = url_prefix_news_list . query_string('^start$|^' . session_name() . '$|^news_date_|^news_keywords$|^tags$|^category_id$|^action$') . '&tags=';

    $cnt = count($tags);
    for ($i = 0; $i < $cnt; $i++) {
        $tags[$i] = Array(
            'html'=>  checkStr($tags[$i]['tag']),
            'url'=>$url_prefix.  rawurlencode($tags[$i]['tag']),
            'n'=>$tags[$i]['n_news']);
    }
}else{
    $tags=Array();
}
echo json_data($tags);
?>
<?php

global $main_template_name;
$main_template_name='';

run('site/menu');

//------------------- site info - begin ----------------------------------------
$site_id = (int) $input_vars['site_id'];
$this_site_info = get_site_info($site_id);
#//prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}



function format_sitemap_url($loc, $lastmod=false, $changefreq='weekly', $priority=0.5){
    if($lastmod){
        if (!(($timestamp = strtotime($lastmod)) === -1)) {
            $lastmod=date(DATE_W3C, $timestamp);
        } else {
            $lastmod=date(DATE_W3C);
        }        
    }else{
        $lastmod=date(DATE_W3C);
    }

    return "
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
        <changefreq>{$changefreq}</changefreq>
        <priority>{$priority}</priority>
    </url>    
    ";
}
echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";

// get site pages
$query="SELECT id, page_file_name, last_change_date, path
        FROM <<tp>>page 
        WHERE site_id={$site_id}
        AND cense_level>={$this_site_info['cense_level']}
        AND is_under_construction=0";
$rows=  \e::db_getrows($query);

foreach ($rows as $pg){
    // prn(preg_replace("/\\/+\$/",'',$this_site_info['url']),preg_replace("/^\\/+/", "", $pg['path']."/".$pg['page_file_name']));
    $loc= preg_replace("/\\/+\$/",'',$this_site_info['url']). '/'.preg_replace("/^\\/+/", "", $pg['path']."/".$pg['page_file_name']);
    // prn($pg);
    $lastmod=$pg['last_change_date'];
    echo format_sitemap_url($loc,$lastmod);
}



// get site news
$now=date('Y-m-d');
$query="SELECT id, lang, news_code, last_change_date
        FROM <<tp>>news 
        WHERE site_id={$site_id}
        AND cense_level>={$this_site_info['cense_level']}
        AND last_change_date<='$now'
        AND ('$now'<=expiration_date OR expiration_date is null)
        ";
// prn($query);
$rows=  \e::db_getrows($query);
foreach ($rows as $row){
    $loc=str_replace(
            Array('{news_id}','{lang}','{news_code}'),
            Array($row['id'],$row['lang'],$row['news_code']),
            \e::config('url_template_news_details')
            );
    $lastmod=$row['last_change_date'];
    echo format_sitemap_url($loc,$lastmod);
}     


// get categories
$query="SELECT site_id,  category_code, date_lang_update,category_id,path,category_description
        FROM <<tp>>category 
        WHERE site_id={$site_id}
        ORDER BY start
        ";
$rows=  \e::db_getrows($query);

// get site languages
$languages=list_of_languages();
// prn($languages);
foreach ($rows as $row){
    foreach($languages as $language){
        $language_contents_size=strlen(trim(get_langstring($row['category_description'], $language['lang'])));
        if($language_contents_size>0){
            $loc=str_replace(
                        Array('{path}'   ,'{lang}','{site_id}','{category_id}','{category_code}'),
                        Array($row['path'],$language['lang']   ,$site_id   ,$row['category_id'],$row['category_code']),
                        \e::config('url_pattern_category'));
            $lastmod=$row['last_change_date'];
            echo format_sitemap_url($loc,$lastmod);
        }
    }
}     


// prn($rows);
echo "</urlset>";

?>
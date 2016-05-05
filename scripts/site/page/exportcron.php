<?php

/*
  Exporting pages in random way
 */


run('site/menu');
run("site/page/export_functions");

global $main_template_name;
$main_template_name = '';

$report = '';

// count all pages
$query1 = "select count(page.id) as n_pages
from <<tp>>page as page, <<tp>>site as site
where page.site_id=site.id
and page.cense_level>=site.cense_level
and not is_under_construction
";
$n_pages =\e::db_getonerow($query1);
$n_pages = $n_pages['n_pages'];

$report .= "n_pages = {$n_pages} <br/>";

// select pages to export
$report .= "exporting about 5 pages ... <br/>";

$export_probability = 5 / $n_pages;

$query2 = "select page.id as page_id,page.lang as page_lang, site.id as site_id, page.path
from <<tp>>page as page, <<tp>>site as site
where page.site_id=site.id
and page.cense_level>=site.cense_level
and not is_under_construction
and rand() < {$export_probability}";
$page_list = \e::db_getrows($query2);

foreach ($page_list as $pg) {
    $this_site_info = get_site_info($pg['site_id']);
    export_page($pg['page_id'], $pg['page_lang']);
    $report.="Exporting page to {$this_site_info['site_root_dir']}/{$pg['path']}/{$pg['page_id']}.{$pg['page_lang']}.html <br/>";
}

echo "<html>
        <head>
         <meta http-equiv=\"Content-Type\" content=\"text/html; charset=".site_charset."\">
         <!-- meta http-equiv=\"Refresh\" content=\"3;URL=index.php?action=site/page/exportcron\" -->
        </head>
        <body>{$report}</body></html>";
// remove from history
nohistory($input_vars['action']);
?>
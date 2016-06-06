<?php

/*
  Exporting pages
  arguments are
  $pagelist='page_id.page_lang;...page_id.page_lang'
  $site_id - site identifier, mandatory
 */

global $main_template_name;
$main_template_name = '';
run('site/menu');
run('category/functions');
// ------------------ site info - begin ----------------------------------------
if (!isset($input_vars['site_id']))
    return $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');

$this_site_info = get_site_info($input_vars['site_id']);

if (!$this_site_info)
    return $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');

// prn('$this_site_info=',$this_site_info);
// ------------------ site info - end ------------------------------------------
## at this line the site is found
//------------------- check permission - begin ---------------------------------
if (!is_admin() && $this_site_info['managers'][$_SESSION['user_info']['id']]['level'] < $this_site_info['cense_level']) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------
// ----------------------- mark pages to export - begin ------------------------
if (isset($input_vars['pagelist'])) {
    if ($input_vars['pagelist'] == 'all') {
        $query = "UPDATE <<tp>>page SET to_export=1 WHERE site_id={$this_site_info['id']} AND cense_level>={$this_site_info['cense_level']}";
        \e::db_execute($query);
    } else {
        $pagelist = explode(';', $input_vars['pagelist']);
        $query = Array();
        foreach ($pagelist as $pg) {
            $pgs = explode('.', $pg);
            if (isset($pgs[0]) && isset($pgs[1])) {
                $pgs[0] = (int) $pgs[0];
                $pgs[1] = \e::db_escape($pgs[1]);
                $query[] = " ( id={$pgs[0]} AND lang='{$pgs[1]}') ";
            }
        }
        if (count($query) > 0) {
            $query = join(' OR ', $query);
            $query = "UPDATE <<tp>>page
                    SET to_export=1
                    WHERE site_id={$this_site_info['id']}
                      AND cense_level>={$this_site_info['cense_level']}
                      AND ($query) ";
            \e::db_execute($query);
        }
    }
}
// ----------------------- mark pages to export - end --------------------------

$this_page_info = \e::db_getonerow(
                "SELECT page.id, page.lang
    FROM <<tp>>page AS page
    WHERE site_id={$this_site_info['id']}
      AND to_export=1
    LIMIT 0,1");

if ($this_page_info) {
//prn('!!!');
    run("site/page/export_functions");
    export_page($this_page_info['id'], $this_page_info['lang']);

    $query = "UPDATE <<tp>>page
          SET to_export=0,delete_file=''
          WHERE site_id={$this_site_info['id']}
            AND id={$this_page_info['id']}
            AND lang='{$this_page_info['lang']}'";
    \e::db_execute($query);

    echo "<html>
        <head>
         <meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . site_charset . "\">
         <meta http-equiv=\"Refresh\" content=\"3;URL=index.php?action=site/page/export&site_id={$this_site_info['id']}\">
        </head>
        <body>{$text['Exporting_page']} {$this_page_info['id']}.{$this_page_info['lang']} - OK</body></html>";
} else {

    echo "<html>
    <head>
         <meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . site_charset . "\">
   </head><body>
  {$text['Export_is_finished']}
  <br>
  <a href=\"javascript:void(window.close())\">{$text['Close_this_window_to_return']}</a>
  </body></html>";
}


// remove from history
nohistory($input_vars['action']);

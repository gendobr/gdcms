<?php

/*
  Default page

  returns
  $input_vars['page_title']='page title';
  $input_vars['page_header']='page header';
  $input_vars['page_content']='page content';

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
$input_vars['page_title'] = $text['Main_page_title'];
$input_vars['page_header'] = $text['Main_page_header'];
$input_vars['page_content'] = $text['Main_page_content'];

if (is_logged()) {
    run('site/menu');
    // get sites
    if (is_admin()) {
        $sites = db_getrows("SELECT s.* FROM {$table_prefix}site as s");
    } else {
        $sites = db_getrows("SELECT s.* FROM {$table_prefix}site_user as su INNER JOIN {$table_prefix}site as s ON su.site_id=s.id WHERE su.user_id={$_SESSION['user_info']['id']}");
    }
    $cnt = count($sites);
    for ($i = 0; $i < $cnt; $i++) {
        $sites[$i]['title'] = get_langstring($sites[$i]['title']);
    }

    function titlesort($a, $b) {
        if ($a['title'] == $b['title']) {
            return 0;
        }
        return ($a['title'] < $b['title']) ? -1 : 1;
    }

    usort($sites, "titlesort");
    // prn($sites);
    $page_content = '

    <style type="text/css">
       a.smn{display:block;font-size:110%;}
       a.smn:hover{background-color:yellow;}
       .mbl{display:inline-block;vertical-align:top;margin-right:10px;}
       .ste{margin-bottom:5px;margin-top:20px;text-align:left;}
       .newsb{float:right; width:220px;padding:10px;margin-left:20px;overflow:hidden;}
       .dd-menu{position:absolute; background-color:#e0e0e0; padding:3px;display:inline-block;}
       a.dddot{padding:5px;display:inline-block;text-decoration:none;background-color:yellow; color:blue;}
       a.dddot:hover{background-color:lime;}
    </style>
    <div class="newsb">
    <script type="text/javascript" src="http://sites.znu.edu.ua/cms/index.php?action=news/js&site_id=17&lang=ukr&rows=10&template=&date=desc&orderby=&category_id=0" charset="windows-1251"></script>
    </div>

    ';
    $first_page_menu_items = Array(
        Array('news/list', 'news/add'),
        Array('site/files', 'gallery/manage', 'gallery/add'),
        Array('site/page/list', 'site/page/add', 'site/export'),
        Array('ec/item/list', 'ec/item/add', 'ec/item/manage_comments', 'ec/order/list', 'ec/producer/list', 'ec/producer/new', 'ec/producer/manage_comments'),
        Array('poll/list', 'poll/add', 'poll/statsdetailed', 'gb/msg_list', 'forum/search')
    );

    foreach ($sites as $site_info) {
        
        $menu = menu_site($site_info);
        // draw drop-down menu
        $mnuid = "mnu" . $site_info['id'];
        $page_content.="<a class=\"dddot\" href=\"javascript:void(change_state('$mnuid'))\">V</a><span class=\"dd-menu\" id=\"$mnuid\" style=\"display:none;\">";
        foreach ($menu as $mi) {
            $page_content.="<a class=smn href=\"{$mi['URL']}\" {$mi['attributes']}>" . strip_tags($mi['innerHTML']) . "</a>";
        }
        $page_content.="</span>";

        $page_content.="<span style='display:inline-block;'><h4 class='ste'>{$site_info['title']} ({$site_info['dir']})</h4></span>";
        $page_content.="<div><a href=\"{$site_info['url']}\">{$site_info['url']}</a></div><br/><br/>";

        // prn($menu);
        $prev = false;
        foreach ($first_page_menu_items as $mg) {

            $mg_html = '';
            foreach ($mg as $mi) {
                if (isset($menu[$mi])) {
                    $mg_html.="<a class=smn href=\"{$menu[$mi]['URL']}\">" . strip_tags($menu[$mi]['innerHTML']) . "</a>";
                }
            }
            if (strlen($mg_html) > 0) {
                $page_content.="<span class=\"mbl\">$mg_html</span>";
            }
        }

        $page_content.="<br/><br/>";
    }
    $input_vars['page_content'] = & $page_content;
}
?>
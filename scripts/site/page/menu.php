<?php

/*
  draw menu
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */

function menu_page($page_info,$site_info) {
    global $text, $db;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];


    $tor['site/page/edit'] = Array(
        'URL' => "index.php?action=site/page/edit&page_id={$page_info['id']}&lang={$page_info['lang']}"
        , 'innerHTML' => $text['Edit_page']
        , 'attributes' => ''
    );

    $tor['site/page/edit_A'] = Array(
        'URL' => "index.php?action=site/page/edit1&page_id={$page_info['id']}&lang={$page_info['lang']}&aed=1"
        , 'innerHTML' => $text['Advanced_Editor']
        , 'attributes' => ''
    );

    $tor['site/page/view'] = Array(
        'URL' => "{$site_info['extra_setting']['publicCmsUrl']}/index.php?action=site/page/view&page_id={$page_info['id']}&lang={$page_info['lang']}" . '&' . $sid . '&v=' . time()
        , 'innerHTML' => $text['View_page']
        , 'attributes' => ' target=_blank '
    );

    if ($_REQUEST['action'] == 'site/page/edit' || $_REQUEST['action'] == 'site/page/edit1') {
        $ret = rawurlencode(base64_encode("index.php?action=site/page/list&orderby=id+desc&filter_id={$page_info['id']}&site_id={$page_info['site_id']}&" . preg_query_string('/^page_|^site_id$|^action$|^save_|^' . session_name() . '$/')));
    } else {
        $ret = rawurlencode(base64_encode('index.php?' . preg_query_string('/^' . session_name() . '$/')));
    }

    $tor['site/page/add'] = Array(
        'URL' => "index.php?action=site/page/add&site_id=" . $page_info['site_id'] . "&page_id=" . $page_info['id'] . '&return=' . $ret
        , 'innerHTML' => text('Add_translation')
        , 'attributes' => ''
    );

    $tor['site/page/export'] = Array(
        'URL' => "index.php?action=site/page/export&site_id={$page_info['site_id']}&pagelist={$page_info['id']}.{$page_info['lang']}&" . session_name() . "=" . $GLOBALS['_COOKIE'][session_name()] . ""
        , 'innerHTML' => $text['Export_as_HTML']
        , 'attributes' => ' target=_blank style="margin-bottom:5pt;" '
    );

    $tor['site/page/html'] = Array(
        'URL' => '#'
        , 'innerHTML' => $text['Get_html_link']
        , 'attributes' => " onclick='popup(\"index.php?action=site/page/html&page_id={$page_info['id']}&lang={$page_info['lang']}\")' style='margin-bottom:5pt;' "
    );

    $tor['site/page/menu'] = Array(
        'URL' => "index.php?action=site/page/attach_menu&page_id=" . $page_info['id'] . "&lang=" . $page_info['lang']
        , 'innerHTML' => $text['Page_menu']
        , 'attributes' => '  style="margin-bottom:5pt;"  '
    );
    //--------------------------- document flow - begin -------------------------
    $tor['site/page/approve'] = Array(
        'URL' => '#'
        , 'innerHTML' => $text['Approve']
        , 'attributes' => " onclick='popup(\"index.php?action=site/page/approve&transition=approve&page_id={$page_info['id']}&lang={$page_info['lang']}\")' "
    );
    $tor['site/page/seize'] = Array(
        'URL' => "#"
        , 'innerHTML' => $text['Seize_to_revize']
        , 'attributes' => " onclick='popup(\"index.php?action=site/page/approve&transition=seize&page_id={$page_info['id']}&lang={$page_info['lang']}\")' "
    );
    $tor['site/page/return'] = Array(
        'URL' => "#"
        , 'innerHTML' => $text['Return_to_previous_operator'] . '<br/>'
        , 'attributes' => " onclick='popup(\"index.php?action=site/page/approve&transition=return&page_id={$page_info['id']}&lang={$page_info['lang']}\")'  title=\"{$text['Return_to_previous_operator']}\" "
    );
    //--------------------------- document flow - end ---------------------------

    $tor['site/page/delete'] = Array(
        'URL' => "index.php?action=site/page/list" .
        "&site_id=" . $page_info['site_id'] .
        "&delete_page_id=" . $page_info['id'] .
        "&delete_page_lang=" . $page_info['lang'] .
        "&" . preg_query_string('/^action$|^site_id$|^delete_page_|^' . session_name() . '$/')
        , 'innerHTML' => $text['Delete_page']
        , 'attributes' => " onclick='return confirm(\"{$text['Are_You_sure']}?\")'  style='margin-top:5pt; color:red;' "
    );


    return $tor;
}

function get_page_info($page_id, $page_lang) {

    $_id = (int) $page_id;
    $_lang = \e::db_escape($page_lang);
    $query = "SELECT page.* ,site.url as site_url
              FROM <<tp>>page page
                  ,<<tp>>site as site
              WHERE page.id={$_id} AND page.lang='{$_lang}' AND page.site_id=site.id";
    $this_page_info = \e::db_getonerow($query);

    $this_page_info['file'] = $this_page_info['id'] . '.' . $this_page_info['lang'] . '.html';
    $this_page_info['path'] = preg_replace("/^\\/+|\\/+$/", '', $this_page_info['path']);
    if (strlen($this_page_info['path']) > 0) {
        $this_page_info['file'] = $this_page_info['path'] . '/' . $this_page_info['file'];
    }

    // ----------------- list of possible templates - begin --------------------
    $this_page_info['templates'] = Array();
    $this_page_info['subtemplates'] = Array();
    if (strlen($this_page_info['path']) > 0) {
        $tmp_path = $this_page_info['path'];
        $i = 0;
        while (strlen($tmp_path) > 0 && $tmp_path != '.' && $i++ < 100) {
            // echo $tmp_path.';<br>';
            $this_page_info['templates'][] = $tmp_path . '/template_index.html';
            $this_page_info['subtemplates'][] = $tmp_path . '/template_page.html';
            $tmp_path = dirname($tmp_path);
        }
    }
    $this_page_info['templates'][] = 'template_index.html';
    $this_page_info['subtemplates'][] = 'template_page.html';
    // ----------------- list of possible templates - end ----------------------

    // create static page URL
    $this_page_url = '/' . preg_replace('/(^\/+|\/+$)/', '', $this_page_info['path'])
            . '/' . $this_page_info['id']
            . '.' . $this_page_info['lang']
            . '.html';
    // prn($this_page_url);
    $this_page_info['absolute_url'] = preg_replace('/\/+$/', '', $this_page_info['site_url'])
            . preg_replace('/\/+/', '/', $this_page_url);

    // friendly URL
    $this_page_info['friendly_url'] = '';
    $this_page_info['file2'] = '';
    if (strlen($this_page_info['page_file_name']) > 0) {
        $this_page_url = '/' . preg_replace('/(^\/+|\/+$)/', '', $this_page_info['path'])
                . '/' . $this_page_info['page_file_name'];
        $this_page_info['friendly_url'] = preg_replace('/\/+$/', '', $this_page_info['site_url'])
                . preg_replace('/\/+/', '/', $this_page_url);

        $this_page_info['file2'] = $this_page_info['path'] . '/' . $this_page_info['page_file_name'];
    }

    $this_page_info['site']=  get_site_info($this_page_info['site_id'], $this_page_info['lang']);
    
    $this_page_info['category']=new pagecategory($this_page_info['category_id'],$this_page_info['lang'],$this_page_info['site']);

    //prn($query,$this_page_info);
    return $this_page_info;
}

class pagecategory {

    private $categoryInfo;
    private $view;
    private $categoryId;
    private $lang;
    private $site;

    function __construct($categoryId, $lang, $site_info) {
        $this->categoryId = $categoryId;
        $this->lang = $lang;
        $this->site = $site_info;
    }

    private function init() {
        if (isset($this->categoryInfo)) {
            return;
        }
        if (!$this->categoryId) {
            return;
        }
        
        $this->categoryInfo=category_info(Array(
            'category_id' => $this->categoryId,
            'lang' => $this->lang,
            'site_id' => $this->site['id']
        ));
        $this->view = new CategoryViewModel(
            $this->site,
            $this->categoryInfo,
            $this->lang);
    }

    function __get($attr) {
        if (!isset($this->categoryInfo)) {
            $this->init();
        }
        
        switch ($attr) {
            case 'category_id':
                return $this->categoryId;
            case 'view':
                return $this->view;
            case 'lang':
                return $this->lang;
            case 'site_id':
                return $this->site_id;
            case 'category_title':
                return $this->categoryInfo['category_title'];
            case 'category_description':
                return $this->categoryInfo['category_description'];
            case 'URL':
                return $this->categoryInfo['URL'];
        }
        
        return false;
    }
}

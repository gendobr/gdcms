<?php

/*
  Functions to view pages
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
 */
// ------------------------- template processor -- begin -----------------------
require \e::config('SCRIPT_ROOT') . '/smarty/libs/Smarty.class.php';

function db_get_template($tpl_name, &$tpl_source, &$smarty_obj) {
    //prn("db_get_template ($tpl_name ... )");
    $paths = Array(
        $tpl_name
        , \e::config('TEMPLATE_ROOT') . '/' . $tpl_name . '.html'
        , \e::config('TEMPLATE_ROOT') . '/' . $tpl_name
    );
    $path = '';
    //prn($paths);
    foreach ($paths as $pa) {
        if (is_file($pa)) {
            $path = $pa;
            break;
        }
    }
    if ($path == '') {
        prn("Template $path not found");
        return false;
    }
    $tmp = join('', file($path));
    $tmp = preg_replace('/\{php\}.*\{\/php\}/', '<!-- php code removed -->', $tmp);
    $tmp = str_replace('{php}', '<!-- php -->', $tmp);
    $tmp = str_replace('{/php}', '<!-- /php -->', $tmp);
    $tpl_source = $tmp;
    return true;
}

function db_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj) {
    $tpl_timestamp = time();
    return true;
}

function db_get_secure($tpl_name, &$smarty_obj) {
    // assume all templates are secure
    return true;
}

function db_get_trusted($tpl_name, &$smarty_obj) {
    // not used for templates
}

function process_template($template_name, $data_array, $functions=Array()) {

    global $_saved_tpl_vars;
    $smr = new Smarty;
    $smr->use_sub_dirs = false;
    $smr->php_handling = SMARTY_PHP_PASSTHRU;

    $smr->security_settings['PHP_TAGS'] = false;
    $smr->security_settings['MODIFIER_FUNCS'] = Array();
    $smr->security_settings['IF_FUNCS'] = Array('count', 'in_array');
    $smr->security_settings['PHP_HANDLING'] = false;
    $smr->security_settings['ALLOW_CONSTANTS'] = false;

    $smr->security = true;

    $smr->compile_dir = \e::config('CACHE_ROOT');
    $smr->register_function("txt", "smarty_txt");
    $smr->register_function("msg", "smarty_getmsgtext");
    $smr->register_function("save", "smarty_save");
    $smr->register_function("run" , "smarty_run_function");
    $smr->register_function("get_langstring", "smarty_get_langstring");
    $smr->register_function("sysmsg", "smarty_sysmsg");
    $smr->register_function("fragment", "smarty_fragment");
    $smr->register_block('set', 'smarty_block_set');

    if (is_array($functions) && count($functions) > 0) {
        //prn($functions);
        foreach ($functions as $fname) {
            $smr->register_function($fname, $fname);
        }
    }
    //prn($smr); die();
    $smr->register_resource("db", array("db_get_template",
        "db_get_timestamp",
        "db_get_secure",
        "db_get_trusted"));
    $smr->assign($data_array);
    //$keys=array_keys($data_array);  foreach($keys as $key){  $smr->assign_by_ref($key,$data_array[$key]);  }
    // assign values saved from previous call
    if (isset($_saved_tpl_vars) && is_array($_saved_tpl_vars))
        foreach ($_saved_tpl_vars as $path => $value) {
            $nv = &$smr->_tpl_vars;
            $path = explode('.', $path);
            $cnt = count($path);
            for ($i = 0; $i < $cnt; $i++) {
                $node = $path[$i];
                if (!isset($nv[$node])) {
                    $nv[$node] = Array();
                }
                $nv = &$nv[$node];
            }
            $nv = $value;
        }
    unset($_saved_tpl_vars);
    //prn('process_template 2:'.$template_name);
    $to_return = $smr->fetch("db:$template_name");

    //echo '<!-- ';prn($smr); echo ' -->';
    unset($smr);

    return $to_return;
}


// sample call is
// {set path="page.title"}some text or template{/set}
function smarty_block_set($params, $content, &$smarty, &$repeat) {
    // only output on the closing tag
    global $_saved_tpl_vars;
    if (!isset($_saved_tpl_vars)) {
        $_saved_tpl_vars = Array();
    }
    if (!is_array($_saved_tpl_vars)) {
        $_saved_tpl_vars = Array();
    }
    if(!$repeat){
        if (isset($content)) {
            extract($params);
            if (!isset($path)) {
                return '';
            }
            $_saved_tpl_vars[$path] = $content;
            return '';
        }
    }
}
// sample call is
// {get_langstring lang=$text.language_name from=$category.title}
function smarty_get_langstring($params) {
    extract($params);
    if (!isset($lang)) {
        $lang = default_language;
    }
    if (!isset($from)) {
        return '------';
    }
    return get_langstring($from, $lang);
}

// sample call is
// {txt lang=$text.language_name variants="eng=English text::rus=Russian text::ukr=Ukrainian text"}
function smarty_txt($params) {
    extract($params);
    if (!isset($lang))
        $lang = default_language;
    if (!isset($variants)) {
        return '------';
    }

    $variants = explode('::', $variants);
    $cnt = count($variants);
    if ($cnt == 0) {
        return '????????';
    }
    for ($i = 0; $i < $cnt; $i++) {
        $variants[$i] = explode('=', $variants[$i]);
        if ($variants[$i][0] == $lang) {
            return $variants[$i][1];
        }
    }
    return $variants[0][1];
}

function smarty_sysmsg() {

    if (isset($_SESSION['msg'])) {
        $tor = $_SESSION['msg'];
        $_SESSION['msg'] = '';
        return $tor;
    }else
        return '';
}

// sample call is
// {msg id="some_message_identifier"}
function smarty_getmsgtext($params) {
    $p = array_values($params);
    return text($p[0]);
}

// sample call is
// {fragment place="" lang=$text.language_name site_id=$site.id}
function smarty_fragment($params) {
    extract($params);
    if (!isset($lang)){
        $lang = default_language;
    }
    if (!isset($place_id)){
        $place_id='';
    }
    $site_id=(int)$site_id;
    $lang=\e::db_escape($lang);
    $place=\e::db_escape($place);
    $query="SELECT fragment_html FROM {$GLOBALS['table_prefix']}fragment
            WHERE site_id=$site_id AND fragment_place='{$place}' AND fragment_lang='{$lang}' AND fragment_is_visible";
    $tmp=  \e::db_getrows($query);
    for($i=0, $cnt=count($tmp); $i<$cnt;$i++){
        $tmp[$i]=$tmp[$i]['fragment_html'];
    }
    // prn($tmp);
    return join('',$tmp);
}


// sample call is
// {save path="page.title" value="some text"}
function smarty_save($params, &$smarty) {
    global $_saved_tpl_vars;
    if (!isset($_saved_tpl_vars)) {
        $_saved_tpl_vars = Array();
    }
    if (!is_array($_saved_tpl_vars)) {
        $_saved_tpl_vars = Array();
    }
    extract($params);
    if (!isset($path)) {
        return '';
    }
    if (!isset($value)) {
        $value = '';
    }
    $_saved_tpl_vars[$path] = $value;
    return '';
}

// sample call is
// {run name="protect" data="page.title"}
function smarty_run_function($params, &$smarty) {
    global $input_vars, $db, $table_prefix, $text, $main_template_name;
    extract($params);
    $p = preg_replace('/[^a-z0-9_]/i', '_', $name);
    include(\e::config('SCRIPT_ROOT') . "/lib/smarty_addons/{$p}.php");
    //return run("lib/smarty_addons/$p",Array('data'=>$data));
}

// ------------------------- template processor -- end -------------------------
//-------------------------- get menu items - begin ----------------------------
function get_menu_items($site_id, $page_id, $lang) {
    global $table_prefix, $db, $txt, $input_vars;

    $site_id=(int)$site_id;
    $page_id=(int)$page_id;
    // ---------------- get page info - begin ----------------------------------
    $query = "SELECT site.url as site_url
                     ,page.id  as page_id
                     ,page.lang  as page_lang
                     ,page.path  as page_path
               FROM {$table_prefix}page as page
                   ,{$table_prefix}site as site
               WHERE page.site_id=site.id
                 and page.site_id=$site_id
                 and site.id=$site_id
                 and page.id=$page_id";
    $this_page_info = \e::db_getonerow($query);

    // ---------------- get page info - end ------------------------------------

    $menu_groups = Array();
    //----------------- site menu - begin --------------------------------------
    //                       AND (page_id=".checkInt($page_id)." OR page_id=0)
    $query = "SELECT *
                 FROM {$table_prefix}menu_group
                 WHERE     site_id=" . checkInt($site_id) . "
                       AND page_id=0
                       AND lang='" . \e::db_escape($lang) . "'
                 ORDER BY page_id DESC, ordering,  id, lang ";
    $tmp = \e::db_getrows($query);

    foreach ($tmp as $tm) {
        $menu_groups[$tm['id']] = $tm;
        $menu_groups[$tm['id']]['items'] = Array();
    }
    // prn($menu_groups);
    //----------------- site menu - end ----------------------------------------
    //----------------- page menu - begin --------------------------------------
    //                       AND (page_id=".checkInt($page_id)." OR page_id=0)
    $query = "SELECT mg.* , pmg.id AS pmg_id
                FROM {$table_prefix}menu_group AS mg
                     INNER JOIN
                    {$table_prefix}page_menu_group AS pmg
                    ON ( mg.id=pmg.menu_group_id
                     AND pmg.page_id={$page_id}
                     AND pmg.lang = '" . \e::db_escape($lang) . "'
                     )
                WHERE mg.site_id = $site_id
                  AND mg.lang='" . \e::db_escape($lang) . "'
                  AND mg.page_id<>0
                ";

    $tmp = \e::db_getrows($query);

    foreach ($tmp as $tm) {
        $menu_groups[$tm['id']] = $tm;
        $menu_groups[$tm['id']]['items'] = Array();
    }
    //  prn($query,$menu_groups);
    //----------------- page menu - end ----------------------------------------
    //----------------- menu items - begin -------------------------------------
    $mmm = Array();
    $mmm[] = 0;
    foreach ($menu_groups as $tm) {
        $mmm[] = $tm['id'];
    }
    $mmm = join(',', $mmm);
    $query = "SELECT * FROM {$table_prefix}menu_item  WHERE     menu_group_id IN($mmm) AND lang='" . \e::db_escape($lang) . "' ORDER BY menu_group_id, ordering";
    $tmp = \e::db_getrows($query);
    foreach ($tmp as $tm) {
        // prn($this_page_url,get_absolute_url($tm['url'],$this_page_url));
        //$tm['disabled'] = ($this_page_url == get_absolute_url($tm['url'], $this_page_url)) ? '1' : '0';
        $tm['disabled'] = '0';
        $menu_groups[$tm['menu_group_id']]['items'][] = $tm;
    }
    //echo '<!-- ';prn($menu_groups); echo ' -->';
    //prn($menu_groups);
    //----------------- menu items - end ---------------------------------------

    return array_values($menu_groups);
}

//-------------------------- get menu items - end ------------------------------
// ------------------------ get absolute URL - begin ---------------------------
/*
  ���� ����� ��������� - ��������� ���� URL
  ����� ������������ ������ ����

  �.�. ���� URL ������ ���� ��������� � URL ������� ��������,
  ����� ���� ��������������(������. � ����������� �� �������)

  ��������� -
  1) URL ������� ��������
  2) URL ������ ����

  URL ������ ���� ����� ����
  - ������������� ( ���������� � ./ ��� � ../ [0-9a-z~_-] )
  - �� ����� ������� (���������� � /)
  - ���������� (���������� � https?://)

  prn($_SERVER);

  $_SERVER['REQUEST_URI']

  /cms/sites/news_details.php?news_id=5?=ukr

  $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']


  $_SERVER['SERVER_ADDR']

  $_SERVER['SERVER_NAME'] => genius
  $_SERVER['SERVER_PORT'] => 80


 */

function get_absolute_url($relative_url, $base_url='') {
    //$url_pattern='^(https?|mms|ftp)://([a-z0-9_-]+\.)+([a-z0-9_-]+)(:[0-9]+)?(/[-.a-z0-9_~]+)*/?(\?.*)?$';
    $url_pattern = '/^(https?|mms|ftp):\/\/([a-z0-9_-]+\.)+([a-z0-9_-]+)(:[0-9]+)?(\/[-.a-z0-9_~]+)*\/?(\?.*)?$/';
    // do nothing if $relative_url is full URL
    if (preg_match($url_pattern, $relative_url))
        return $relative_url;

    // use current page URL if $base is empty
    // do nothing if $base is invalid URL
    if (!preg_match($url_pattern, $base_url))
        return false;

    // parse $base_url
    $parsed_base_url = parse_url($base_url);

    // compose new URL
    // scheme (protocol) always exists
    $new_url = $parsed_base_url['scheme'] . '://';

    // username & password
    $up = Array('', '');
    if (isset($parsed_base_url['user'])
            && strlen($parsed_base_url['user']) > 0)
        $up[0] = $parsed_base_url['user'];

    if (isset($parsed_base_url['pass'])
            && strlen($parsed_base_url['pass']) > 0)
        $up[1] = $parsed_base_url['pass'];

    $up = join(':', $up);
    if (strlen($up) > 1)
        $new_url.=$up . '@';

    // host always exists
    $new_url.=$parsed_base_url['host'];

    // if port is set
    if (isset($parsed_base_url['port'])
            && $parsed_base_url['port'] != 80)
        $new_url.=':' . ( (int) $parsed_base_url['port'] );

    // compose path
    if (!isset($parsed_base_url['path']))
        $parsed_base_url['path'] = '/';

    if (preg_match("/^\\//", $relative_url)) {
        $new_path = $relative_url;
    } else {
        $dirname = $parsed_base_url['path'];
        if (!preg_match("/\\/$/", $dirname))
            $dirname = dirname($dirname);
        $dirname = str_replace("\\", "/", $dirname);
        $dirname = preg_replace("/\\/$/", '', $dirname);
        $tmp = $dirname . '/' . $relative_url;

        // "/xxx/../" => "/"
        do {
            $new_path = $tmp;
            $tmp = preg_replace("/" . "\\/[^\\/]+\\/\\.\\.\\/" . "/", '/', $new_path);
        } while ($tmp != $new_path);

        // "/./" => "/"
        do {
            $new_path = $tmp;
            $tmp = preg_replace('/' . "\\/\\.\\/" . '/', '/', $new_path);
        } while ($tmp != $new_path);
    }
    $tmp = preg_replace('/' . "(\\.\\.\\/)+" . '/', '/', $new_path);
    $new_path = $tmp;
    $tmp = preg_replace('/' . "^(\\.\\/)+" . '/', '/', $new_path);
    $new_path = $tmp;
    $tmp = preg_replace('/' . "\\/+" . '/', '/', $new_path);
    $new_path = $tmp;
    $tmp = preg_replace('/' . "^\\/+" . '/', '', $new_path);
    $new_path = $tmp;

    $new_url.='/' . $new_path;


    // parse and reorder query string
    if (isset($parsed_base_url['query'])
            && strlen($parsed_base_url['query']) > 0) {
        parse_str($parsed_base_url['query'], $query);
        ksort($query);
        $new_url.='?' . http_build_query($query);
    }

    // add fragment
    if (isset($parsed_base_url['fragment'])
            && strlen($parsed_base_url['fragment']) > 0)
        $new_url.='#' . $parsed_base_url['fragment'];
    return $new_url;
}

// ------------------------ get absolute URL - end -----------------------------
?>
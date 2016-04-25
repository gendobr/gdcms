<?php

/**
 * View ec_item details
 *   argments are
 *    $ec_item_id   - identifier of ec_item
 *    $ec_item_lang - language of ec_item
 */
run('ec/item/functions');
run('site/menu');
run('site/page/page_view_functions');
# -------------------- get ec item info - begin --------------------------------
$ec_item_id = isset($input_vars['ec_item_id']) ? ( (int) $input_vars['ec_item_id'] ) : 0;

// $ec_item_lang=isset($input_vars['ec_item_lang'])?preg_replace("/\\W/",'', $input_vars['ec_item_lang'] ):\e::config('default_language');
$ec_item_lang = get_language('ec_item_lang');

$ec_item_code = isset($input_vars['ec_item_code']) ? ( $input_vars['ec_item_code'] ) : '';
//prn($input_vars);
$this_ec_item_info = get_ec_item_info($ec_item_id, $ec_item_lang, 0, false, $ec_item_code);
//exit('test 001');
if (!$this_ec_item_info || $this_ec_item_info['ec_item_id'] == 0) {
    die('Item not found');
}
//prn($this_ec_item_info);
# -------------------- get ec item info - end ----------------------------------
# update item satistics
if (!is_logged()) {
    \e::db_execute("UPDATE {$table_prefix}ec_item SET ec_item_views=ifnull(ec_item_views,0)+1 WHERE ec_item_id={$this_ec_item_info['ec_item_id']} LIMIT 1");
}



# -------------------------- load messages - begin -----------------------------
global $txt;
$txt = load_msg($ec_item_lang);
# -------------------------- load messages - end -------------------------------
# ------------------- get site info - begin ------------------------------------

$site_id = (int) $this_ec_item_info['site_id'];
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}
$this_site_info['title'] = get_langstring($this_site_info['title'], $ec_item_lang);
//prn($this_site_info);
//prn($input_vars);
# ------------------- get site info - end --------------------------------------
# --------------------------- get site template - begin ------------------------
$custom_page_template = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'] . '/template_index.html';
if (is_file($custom_page_template)) {
    $this_site_info['template'] = $custom_page_template;
}
# --------------------------- get site template - end --------------------------
# -------------------- get list of page languages - begin ----------------------
$tmp = \e::db_getrows("SELECT DISTINCT ec_item_lang as lang
                     FROM {$table_prefix}ec_item  AS ec_item
                     WHERE ec_item.site_id={$site_id}
                       AND ec_item.ec_item_cense_level&" . ec_item_show . "");
$existing_languages = Array();
foreach ($tmp as $tm) {
    $existing_languages[$tm['lang']] = $tm['lang'];
}
// prn($existing_languages);


$lang_list = list_of_languages('ec_item_lang');
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    if (!isset($existing_languages[$lang_list[$i]['name']])) {
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url'] = $lang_list[$i]['href'] = $lang_list[$i]['href'] . '&ec_item_lang=' . $lang_list[$i]['name'];
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
$lang_list = array_values($lang_list);
//prn($lang_list);
# -------------------- get list of page languages - end ------------------------
# -------------------- get producer info - begin -------------------------------
run('ec/producer/functions');
$this_producer_info = get_producer_info($this_ec_item_info['ec_producer_id']);
//prn($this_producer_info);
# -------------------- get producer info - end ---------------------------------
# -------------------- add comment - begin -------------------------------------
$comment_sender = '';
$comment_body = '';
$messages = '';
if (isset($input_vars['comment_sender'])) {
    $comment_sender = trim(strip_tags($input_vars['comment_sender']));
}
if (isset($input_vars['comment_body'])) {
    $comment_body = trim(strip_tags($input_vars['comment_body']));
}
if (isset($input_vars['comment_code'])) {
    if (strlen($comment_body) == 0) {
        $messages.="<div style='color:red;'>" . text('EC_item_comment_error_empty_comment') . "</div>";
    }
    if (trim($input_vars['comment_code']) != $_SESSION['code']) {
        $messages.="<div style='color:red;'>" . text('EC_item_comment_error_invalid_code') . "</div>";
    }
    if (strlen($comment_sender) == 0) {
        $comment_sender = 'Anonymous';
    }
    if (strlen($messages) == 0) {
        $_body = nl2br($comment_body);
        $_body = preg_replace("/<br[^>]*>/", "<br/>", $_body);
        $_body = preg_replace("/ +<br\/>/", "<br/>", $_body);
        $_body = preg_replace("/(<br\/>\r?\n){2,}/", "<br/><br/>", $_body);

        $query = "INSERT INTO {$table_prefix}ec_item_comment(
                    ec_item_comment_sender_name,
                    ec_item_comment_body,
                    site_id,
                    ec_item_id,
                    ec_item_lang,
                    ec_item_comment_datetime
               )values(
                '" . \e::db_escape($comment_sender) . "',
                '" . \e::db_escape($_body) . "',
                $site_id,
                $ec_item_id,
                '$ec_item_lang',
                NOW()
               )";
        \e::db_execute($query);
        $_SESSION['code'] = '';
        $comment_sender = '';
        $comment_body = '';
    }
}
# -------------------- add comment - end ---------------------------------------

class ec_item_comments {

    function __construct($site_id, $ec_item_id, $ec_item_lang) {
        $this->site_id = $site_id;
        $this->ec_item_id = $ec_item_id;
        $this->ec_item_lang = $ec_item_lang;


        $this->form = Array(
            'hidden_form_elements' => hidden_form_elements('^comment'),
            'comment_sender_name' => 'comment_sender',
            'comment_sender_value' => (isset($_REQUEST['comment_sender']) ? trim(strip_tags($_REQUEST['comment_sender'])) : ''),
            'comment_body_name' => 'comment_body',
            'comment_body_value' => (isset($_REQUEST['comment_body']) ? trim(strip_tags($_REQUEST['comment_body'])) : ''),
            'comment_code_name' => 'comment_code',
            'captcha_image_url' => site_root_URL . "?action=ec/item/capcha"
        );
    }

    function __get($name) {
        if ($name == 'list') {
            return $this->get_list();
        }
    }

    function get_list() {
        # -------------------- get list of comments - begin ----------------------------
        $query = "SELECT *
                  FROM {$GLOBALS['table_prefix']}ec_item_comment
                  WHERE site_id=$this->site_id
                    AND ec_item_id=$this->ec_item_id
                    AND ec_item_lang='$this->ec_item_lang'
                  ORDER BY ec_item_comment_datetime ASC";
        return \e::db_getrows($query);
        # -------------------- get list of comments - end ------------------------------
    }

}

# ------------------------ draw using SMARTY template - begin ------------------
# get site menu
$menu_groups = get_menu_items($this_site_info['id'], 0, $ec_item_lang);

# -------------------- search for template - begin ---------------------------
$ec_item_template = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'] . '/template_ec_item_view.html';
if (!is_file($ec_item_template)) {
    $ec_item_template = 'cms/template_ec_item_view';
}
# -------------------- search for template - end -----------------------------


$this_ec_item_info['ec_item_size'][3] = text('ec_units_' . $this_ec_item_info['ec_item_size'][3]);
$this_ec_item_info['ec_item_weight'][1] = text('ec_units_' . $this_ec_item_info['ec_item_weight'][1]);
$this_ec_item_info['url_order_now'] = sprintf(\e::config('url_ec_item_order_now_pattern'), $this_ec_item_info['ec_item_lang'], $this_ec_item_info['ec_item_id']);

$this_ec_item_info['ec_item_tags'] = explode(',', $this_ec_item_info['ec_item_tags']);
$cnt = count($this_ec_item_info['ec_item_tags']);
$prefix = site_root_URL . "/index.php?action=ec/item/list_by_tag&lang={$_SESSION['lang']}&site_id={$this_site_info['id']}&ec_item_tags=";
for ($i = 0; $i < $cnt; $i++) {
    $this_ec_item_info['ec_item_tags'][$i] = trim($this_ec_item_info['ec_item_tags'][$i]);
    $this_ec_item_info['ec_item_tags'][$i] = "<a href=\"{$prefix}" . rawurlencode($this_ec_item_info['ec_item_tags'][$i]) . "\">{$this_ec_item_info['ec_item_tags'][$i]}</a>";
}
$this_ec_item_info['ec_item_tags'] = join(', ', $this_ec_item_info['ec_item_tags']);

//prn($this_ec_item_info);
$vyvid = process_template($ec_item_template
        , Array(
    'ec_item' => $this_ec_item_info,
    'ec_producer' => $this_producer_info,
    'text' => $txt,
    'site' => $this_site_info,
    'comments' => new ec_item_comments($site_id, $ec_item_id, $ec_item_lang),
    'messages' => (isset($messages) ? $messages : '')
        )
);




$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array('title' => $this_ec_item_info['ec_item_title']
        , 'content' => $vyvid
        , 'abstract' => ''
        , 'site_id' => $site_id
        , 'lang' => $ec_item_lang
    )
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name;
$main_template_name = '';
?>
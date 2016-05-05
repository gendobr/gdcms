<?php

/**
 * Browse ec_item categories
 *   argments are
 *    $category_id - identifier of category
 *
 *
 *
 */
error_reporting(E_ALL);

run('ec/item/functions');
run('ec/producer/functions');
run('site/menu');
# -------------------- set interface language - begin ---------------------------
$debug = false;
if (isset($input_vars['interface_lang'])) {
    if ($input_vars['interface_lang']) {
        $input_vars['lang'] = $input_vars['interface_lang'];
    }
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = \e::config('default_language');
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = \e::config('default_language');
}
// $lang=$input_vars['lang'];
$lang = get_language('lang');
# -------------------- set interface language - end -----------------------------
# -------------------------- load messages - begin -----------------------------
global $txt;
$txt = load_msg($lang);
# -------------------------- load messages - end -------------------------------
# --------------------------- get producer info - begin ------------------------
$ec_producer_id = isset($input_vars['ec_producer_id']) ? (int) $input_vars['ec_producer_id'] : 0;
$this_producer_info = get_producer_info($ec_producer_id);
if (!$this_producer_info) {
    die('Producer not found');
}
# --------------------------- get producer info - end --------------------------




# ------------------- get site info - begin ------------------------------------
$site_id = $this_producer_info['site_id'];
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}
$this_site_info['title'] = get_langstring($this_site_info['title'], $lang);
//prn($this_site_info);
//prn($input_vars);
# ------------------- get site info - end --------------------------------------
# --------------------------- get site template - begin ------------------------
$custom_page_template = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'] . '/template_index.html';
if (is_file($custom_page_template)) {
    $this_site_info['template'] = $custom_page_template;
}
# --------------------------- get site template - end --------------------------


//exit('3');

$input_vars['orderby'] = 'ec_item_last_change_date desc';
$input_vars['ec_item_state'] = 'show';
include(\e::config('SCRIPT_ROOT') . '/ec/item/get_public_list.php');
include(\e::config('SCRIPT_ROOT') . '/ec/item/adjust_public_list.php');

//prn($list_of_ec_items);
//prn($pages);
# -------------------- get list of page languages - begin ----------------------
$lang_list = list_of_languages();
$lang_list = array_values($lang_list);
# -------------------- get list of page languages - end ------------------------
# -------------------- generate code - begin -----------------------------------

function get_code() {
    srand((float) microtime() * 1000000);
    $chars = explode(',', '1,2,3,4,5,6,7,8,9,0');
    shuffle($chars);
    $chars = join('', $chars);
    $chars = substr($chars, 0, 5);
    return $chars;
}

if (!isset($_SESSION['code']) || strlen($_SESSION['code']) == 0)
    $_SESSION['code'] = get_code();

# -------------------- generate code - end -------------------------------------
# -------------------- add comment - begin -------------------------------------
$comment_sender = '';
$comment_body = '';
$messages = '';
if (isset($input_vars['comment_sender']))
    $comment_sender = trim(strip_tags($input_vars['comment_sender']));
if (isset($input_vars['comment_body']))
    $comment_body = trim(strip_tags($input_vars['comment_body']));
if (isset($input_vars['comment_code'])) {
    if (strlen($comment_body) == 0)
        $messages.="<div style='color:red;'>" . text('EC_item_comment_error_empty_comment') . "</div>";
    if (trim($input_vars['comment_code']) != $_SESSION['code'])
        $messages.="<div style='color:red;'>" . text('EC_item_comment_error_invalid_code') . "</div>";
    if (strlen($comment_sender) == 0)
        $comment_sender = 'Anonymous';
    if (strlen($messages) == 0) {
        $_body = nl2br($comment_body);
        $_body = ereg_replace("<br[^>]*>", "<br/>", $_body);
        $_body = ereg_replace(" +<br/>", "<br/>", $_body);
        $_body = ereg_replace("(<br/>\r?\n){2,}", "<br/><br/>", $_body);

        $query = "INSERT INTO <<tp>>ec_producer_comment(
                    ec_producer_comment_sender_name,
                    ec_producer_comment_body,
                    site_id,
                    ec_producer_id,
                    ec_producer_comment_datetime
               )values(
                '" . \e::db_escape($comment_sender) . "',
                '" . \e::db_escape($_body) . "',
                $site_id,
                $ec_producer_id,
                NOW()
               )";
        \e::db_execute($query);
        $_SESSION['code'] = get_code();
        $comment_sender = '';
        $comment_body = '';
    }
}
# -------------------- add comment - end ---------------------------------------
# 
# 
#exit('4');
# 
# 
/*
# -------------------- get list of comments - begin ----------------------------
$query = "SELECT *
          FROM <<tp>>ec_producer_comment
          WHERE site_id=$site_id
            AND ec_producer_id=$ec_producer_id
          ORDER BY ec_producer_comment_datetime ASC";
$list_of_comments = \e::db_getrows($query);
# -------------------- get list of comments - end ------------------------------
# -------------------- draw comments block - begin - begin ---------------------
$comments = "
  <style>
   .comment_sender{border-bottom:1px solid gray;}
   .comment_date{font-size:80%;color:gray;}
  </style>
  <h3>" . text('EC_producer_comments') . "</h3>
  ";
foreach ($list_of_comments as $cmt) {
    $comments.="
      <p>
       <div class=comment_sender>{$cmt['ec_producer_comment_sender_name']}</div>
       <div class=comment_date>{$cmt['ec_producer_comment_datetime']}</div>
       <div class=comment_body>{$cmt['ec_producer_comment_body']}</div>
      </p>
      ";
}
$comments.="
  <h3>" . text('New_comment') . "</h3>
  {$messages}
  <form action=index.php method=post>
   " . hidden_form_elements('^comment') . "
    <table>
      <tr>
         <td>" . text('Comment_sender') . "</td>
         <td><input type=text name=comment_sender style='width:100%;' value='" . checkStr($comment_sender) . "'></td>
      </tr>
      <tr>
         <td colspan=2 style='width:260pt;height:150pt;' align=right>
           <textarea name=comment_body style='width:100%;height:150pt;'>" . checkStr($comment_body) . "</textarea>
         </td>
      </tr>
      <tr>
          <td valign=middle>
             " . text('Retype_the_number') . "
          </td>
          <td>
            <nobr><!--
         --><img src=" . site_root_URL . "?action=ec/item/capcha&t" . time() . " width=60px height=20px style='border:1px solid gray;' align=absmiddle><!--
         --><input type=text name=comment_code style='width:70pt;'><!--
         --></nobr>
          </td>
      </tr>
      <tr>
          <td></td>
          <td align=right><input type=submit value='" . text('Send') . "' style='width:70pt;'></td>
      </tr>
    </table>
  </form>
";
 */
# -------------------- draw comments block - begin - end -----------------------
#
# exit('5');
# ------------------------ draw using SMARTY template - begin ------------------
run('site/page/page_view_functions');

# get site menu
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

# search for template
$template_ec_producer_view = site_get_template($this_site_info, 'template_ec_producer_view');

# search for template
$ec_item_template_list = site_get_template($this_site_info, 'template_ec_item_list');


//exit($template_ec_producer_view);

$vyvid = process_template($template_ec_producer_view
                , Array(
            'text' => $txt,
            'site' => $this_site_info,
            'producer' => $this_producer_info
                )
        )
        . process_template($ec_item_template_list
                , Array(
            'pages' => $pages,
            'text' => $txt,
            'ec_items' => $list_of_ec_items,
            'ec_items_search_summary' => sprintf(text('EC_items_search_summary'), $start + 1, $start + count($list_of_ec_items), $rows_found),
            'ec_items_found' => $rows_found,
            'start' => $start + 1,
            'finish' => $start + count($list_of_ec_items),
            'category_view_url_prefix' => "index.php?action=ec/item/browse&lang=$lang&site_id=$site_id&ec_category_id=",
            'site' => $this_site_info,
            'orderby' => $orderby
                )
        )
        //. $comments
        ;

$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array('title' => $this_producer_info['ec_producer_title']//.'-'.$txt['EC_item_producer_details']
        , 'content' => $vyvid
        , 'abstract' => ''
        , 'site_id' => $site_id
        , 'lang' => $input_vars['lang']
    )
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
# ------------------------ draw using SMARTY template - end --------------------
echo $file_content;

global $main_template_name;
$main_template_name = '';
?>
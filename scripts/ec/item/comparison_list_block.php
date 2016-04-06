<?php
/**
 * List of items choosed for comparison
 */

global $main_template_name;
$main_template_name='';
run('site/menu');
run('ec/item/functions');
run('site/page/page_view_functions');
# -------------------- set interface language - begin ---------------------------
$debug=false;
if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])   ) $input_vars['lang']=default_language;
if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
// $lang=$input_vars['lang'];
$lang=get_language('lang');
# -------------------- set interface language - end -----------------------------

# -------------------------- load messages - begin -----------------------------
global $txt;
$txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------


# ------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if(!$this_site_info) die($txt['Site_not_found']);
$this_site_info['title']=get_langstring($this_site_info['title'],$lang);
# ------------------- get site info - end --------------------------------------

# --------------------------- get list of items - begin ------------------------
if(!isset($_SESSION['items_to_compare']) ) $_SESSION['items_to_compare']=Array();
$list_of_ec_items=Array();
if(count($_SESSION['items_to_compare'])>0) {
  $q=join(',',$_SESSION['items_to_compare']);
  $query="SELECT SQL_CALC_FOUND_ROWS
                 ec_item.ec_item_id,
                 ec_item.ec_item_lang,
                 ec_item.site_id,
                 ec_item.ec_item_title,
                 ec_item.ec_item_cense_level,
                 ec_item.ec_item_last_change_date,
                 ec_item.ec_item_abstract,
                 ec_item.ec_item_tags,
                 ec_item.ec_item_price,
                 ec_item.ec_item_currency,
                 ec_item.ec_item_amount,
                 ec_item.ec_producer_id,
                 ec_producer.ec_producer_title,
                 ec_item.ec_category_id,
                 ec_category.ec_category_title,
                 ec_item.ec_item_onnullamount,
                 ec_item.ec_item_mark,
                 ec_item.ec_item_img,
                 ec_item.ec_item_uid,
                 IF(LENGTH(TRIM(ec_item.ec_item_content))>0,1,0) as ec_item_content_present
           FROM {$table_prefix}ec_item AS ec_item
                LEFT JOIN {$table_prefix}ec_category AS ec_category
                ON ec_category.ec_category_id=ec_item.ec_category_id
                LEFT JOIN {$table_prefix}ec_producer AS ec_producer
                ON ec_producer.ec_producer_id=ec_item.ec_producer_id
            WHERE ec_item.site_id={$site_id}
              AND ec_item.ec_item_lang='{$lang}'
              AND ec_item.ec_item_id IN($q)
            ";
       $list_of_ec_items=\e::db_getrows($query);
       include(\e::config('SCRIPT_ROOT').'/ec/item/adjust_public_list.php');
}
# --------------------------- get list of items - end --------------------------


# ------------------ search for template - begin -------------------------------
$_template=false;
if(isset($_REQUEST['template'])) $_template=site_get_template($this_site_info,$_REQUEST['template']);
if(!$_template) $_template = site_get_template($this_site_info,'template_ec_item_compare_block');
# ------------------ search for template - end ---------------------------------



$vyvid = process_template( $_template
        ,Array(
        'ec_items'=>$list_of_ec_items,
        'n_items'=>count($list_of_ec_items),
        'url_open_comparison'=>site_root_URL."/index.php?action=ec/item/compare&site_id={$this_site_info['id']}&lang={$_SESSION['lang']}",
        'site'=>$this_site_info ) );


if(strlen($vyvid)==0) {
    echo '';
    return '';
}
/*
if(isset($input_vars['element'])) {
    echo "
    <div id=toinsert>$vyvid</div>
    <script type=\"text/javascript\">
    <!--
    var from = document.getElementById('toinsert');
    //alert(from.innerHTML);
    var to;
    if(window.top)
    {
      //alert('window.top - OK');
      if(window.top.document)
      {
        //alert('window.top.document - OK');
        to = window.top.document.getElementById('{$input_vars['element']}');
        //alert(to);
        if(to)
        {
           //alert('element - OK');
           to.innerHTML = from.innerHTML;
        }
      }
    }
    // -->
    </script>
    "
    ;
}
else */
  header('Content-Type:text/html; charset='.site_charset);
header('Access-Control-Allow-Origin: *');
  echo $vyvid;

// remove from history
   nohistory($input_vars['action']);

?>
<?php
/*
 * Add item to cart
 */
run('ec/item/functions');
run('ec/cart/functions');
run('site/menu');
# -------------------- get ec item info - begin --------------------------------
  $ec_item_id=isset($input_vars['ec_item_id'])?( (int)$input_vars['ec_item_id'] ):0;

  // $ec_item_lang=isset($input_vars['ec_item_lang'])?( $input_vars['ec_item_lang'] ):default_language;
  $ec_item_lang=get_language('ec_item_lang');

  $this_ec_item_info=get_ec_item_info($ec_item_id,$ec_item_lang);
  if(!$this_ec_item_info) die('Item not found');
  //prn($this_ec_item_info);
# -------------------- get ec item info - end ----------------------------------

# update item satistics
  \e::db_execute("UPDATE {$table_prefix}ec_item SET ec_item_in_cart=ifnull(ec_item_in_cart,0)+1 WHERE ec_item_id={$this_ec_item_info['ec_item_id']} LIMIT 1");


# -------------------------- load messages - begin -----------------------------
  global $txt;
  $txt=load_msg($ec_item_lang);
# -------------------------- load messages - end -------------------------------



# ------------------- get site info - begin ------------------------------------
  $site_id = (int)$this_ec_item_info['site_id'];
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$ec_item_lang);
  //prn($this_site_info);
  //prn($input_vars);
# ------------------- get site info - end --------------------------------------

# --------------------------- get site template - begin ------------------------
  $custom_page_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/template_index.html';
  if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
# --------------------------- get site template - end --------------------------

# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
  $lang_list=array_values($lang_list);
  //prn($lang_list);
# -------------------- get list of page languages - end ------------------------



ec_cart_additem($this_ec_item_info,$input_vars);

// prn($_SESSION['ec_cart']);
// prn($uid);
// die();
$returnto='';
if(isset($input_vars['returnto']))
{
    $returnto=$input_vars['returnto'];
    header("Location: $returnto");
}else{
header("Location: index.php?action=ec/cart/view&site_id=$site_id&lang=$ec_item_lang");
}

global $main_template_name; $main_template_name='';

// remove from history
   nohistory($input_vars['action']);


?>
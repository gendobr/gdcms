<?php
/*
 * Get list of items and show it as block
 */

global $main_template_name; $main_template_name='';
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
  include(script_root.'/ec/item/get_public_list.php');
  include(script_root.'/ec/item/adjust_public_list.php');
# --------------------------- get list of items - end --------------------------


# ------------------ search for template - begin -------------------------------
  if(isset($_REQUEST['template']))
  {
    $_template = sites_root.'/'.$this_site_info['dir'].'/'.$_REQUEST['template'].'.html';
    if(!is_file($_template)) $_template=false;

    if(!$_template) $_template = sites_root.'/'.$this_site_info['dir'].'/'.$_REQUEST['template'];
    if(!is_file($_template)) $_template=false;
  }
  else $_template=false;
  if(!$_template) $_template = site_get_template($this_site_info,'template_ec_item_block');
# ------------------ search for template - end ---------------------------------



  $vyvid = process_template( $_template
                    ,Array(
                           'pages'=>$pages,
                           'text'=>$txt,
                           'ec_items'=>$list_of_ec_items,
                           'ec_items_search_summary'=>sprintf(text('EC_items_search_summary'),$start+1,$start+count($list_of_ec_items),$rows_found),
                           'ec_items_found' => $rows_found,
                           'start'=>$start+1,
                           'finish'=>$start+count($list_of_ec_items),
                           'site'=>$this_site_info,
                           'orderby'=>$orderby
                     )
  );

header('Content-Type:text/html; charset=' . site_charset);
header('Access-Control-Allow-Origin: *');

if(strlen($vyvid)==0) {echo '';return '';}

//if(isset($input_vars['element']))
//{
//  echo "
//    <div id=toinsert>$vyvid</div>
//    <script type=\"text/javascript\">
//    <!--
//    var from = document.getElementById('toinsert');
//    //alert(from.innerHTML);
//    var to;
//    if(window.top)
//    {
//      //alert('window.top - OK');
//      if(window.top.document)
//      {
//        //alert('window.top.document - OK');
//        to = window.top.document.getElementById('{$input_vars['element']}');
//        //alert(to);
//        if(to)
//        {
//           //alert('element - OK');
//           to.innerHTML = from.innerHTML;
//        }
//      }
//    }
//    // -->
//    </script>
//    "
//    ;
//}
//else
    echo $vyvid;

// remove from history
   nohistory($input_vars['action']);


?>
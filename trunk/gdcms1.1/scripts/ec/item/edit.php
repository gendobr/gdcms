<?php

/*
  Editing page
  argument is $ec_item_id    - ec item identifier, integer, mandatory
  $ec_item_lang  - ec item language  , char(3), mandatory
  $site_id       - site identifier, integer, optional
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
 */
run('lib/file_functions');
run('site/image/url_replacer');
run('ec/item/functions');
run('site/menu');

//prn($input_vars);
# ------------------- check ec_item_id - begin ------------------------------------
$ec_item_id = 0;
$ec_item_lang = get_language('ec_item_lang');
if (isset($input_vars['ec_item_id'])) {
    $ec_item_id = (int) $input_vars['ec_item_id'];
    //$ec_item_lang = DbStr($input_vars['ec_item_lang']);
    $this_ec_item_info = get_ec_item_info($ec_item_id, $ec_item_lang, 0, false);
    if (!$this_ec_item_info) {
        $ec_item_id = 0;
    }
}
if ($ec_item_id == 0) {
    $this_ec_item_info = get_ec_item_info(0, (isset($input_vars['ec_item_lang']) ? $input_vars['ec_item_lang'] : default_language), ((int) (isset($input_vars['site_id']) ? $input_vars['site_id'] : 0)));
}
//prn('$ec_item_id='.$ec_item_id);
//prn('$this_ec_item_info',$this_ec_item_info);
# ------------------- check ec_item_id - end --------------------------------------
# ------------------- get site info - begin ---------------------------------------
if ($ec_item_id > 0) {
    $site_id = $this_ec_item_info['site_id'];
} else {
    $site_id = (int) (isset($input_vars['site_id']) ? $input_vars['site_id'] : 0);
}
$this_site_info = get_site_info($site_id);
//prn('$this_site_info=',$this_site_info);
if ($this_site_info) {
    $this_ec_item_info['site_id'] = $site_id;
}
# ------------------- get site info - end -----------------------------------------
//prn('$ec_item_id='.$ec_item_id);
//('$this_ec_item_info',$this_ec_item_info);
# ------------------- get permission - begin --------------------------------------
$user_cense_level = get_level($site_id);
if ($user_cense_level <= 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- get permission - end ----------------------------------------
# ----------------- save changes - begin ------------------------------------------
if (isset($input_vars['save_changes']) && strlen($input_vars['save_changes']) > 0) {
    $this_ec_item_info = run('ec/item/edit_save', Array('this_ec_item_info' => $this_ec_item_info, 'this_site_info' => $this_site_info));
    
    // http://localhost/cms/index.php?action=ec/item/edit&site_id=8&ec_item_id=1&ec_item_lang=ukr
    header('Location:'.site_URL."?action=ec/item/edit&site_id={$site_id}&ec_item_id={$this_ec_item_info['ec_item_id']}&ec_item_lang={$this_ec_item_info['ec_item_lang']}");
    exit();
    
    $message = $this_ec_item_info['message'];
    $ec_item_id = $this_ec_item_info['ec_item_id'];
    $ec_item_lang = $this_ec_item_info['ec_item_lang'];
}
# ----------------- save changes - end --------------------------------------------
//------------------- draw form - begin ----------------------------------------
$notify_managers_form = '';
foreach ($this_site_info['managers'] as $mn) {
    $notify_managers_form .="<input type=checkbox name='notify[{$mn['id']}]'> {$mn['full_name']}<br/>";
}
if (strlen($notify_managers_form) > 0) {
    $notify_managers_form = "<tr><td colspan=6><b>{$text['Send_notification_to']}</b><br/>{$notify_managers_form}</td></tr>";
}

if (!isset($message)) {
    $message = '';
}


$input_vars['aed'] = (isset($input_vars['aed'])) ? ( (int) $input_vars['aed']) : 0;
$input_vars['page_title'] = text('EC-item-edit');
$input_vars['page_header'] = text('EC-item-edit');
$input_vars['page_content'] = '';






# ------------------------ list of categories - begin -------------------------
$query = "SELECT ec_category_id, ec_category_title, deep FROM {$table_prefix}ec_category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
$tmp = db_getrows($query);
$list_of_categories = Array();
foreach ($tmp as $tm) {
    $list_of_categories[$tm['ec_category_id']] = str_repeat(' + ', $tm['deep'] - 1) . get_langstring($tm['ec_category_title']);
}
unset($tmp, $tm);
//prn($list_of_categories);
# ------------------------ list of categories - end ---------------------------
# ------------------------ list of producers - begin --------------------------
$query = "SELECT ec_producer_id, ec_producer_title  FROM {$table_prefix}ec_producer WHERE site_id={$site_id} ORDER BY ec_producer_title ASC";
$tmp = db_getrows($query);
$list_of_producers = Array();
foreach ($tmp as $tm) {
    $list_of_producers[$tm['ec_producer_id']] = get_langstring($tm['ec_producer_title']);
}
unset($tmp, $tm);
//prn($list_of_categories);
# ------------------------ list of producers - end ----------------------------
# ------------------------ list of curency_titles - begin ---------------------
$query = "SELECT ec_currency_code, ec_curency_title  FROM {$table_prefix}ec_currency ORDER BY ec_curency_title ASC";
$tmp = db_getrows($query);
$list_of_currencies = Array();
foreach ($tmp as $tm) {
    $list_of_currencies[$tm['ec_currency_code']] = get_langstring($tm['ec_curency_title']);
}
unset($tmp, $tm);
//prn($list_of_currencies);
# ------------------------ list of curency_titles - end -----------------------
## ------------------------ get list of onnullamount handlers - begin ----------
## these handlers are located in ec/item/functions.php
#  $tmp=get_defined_functions ();
#  //prn($tmp['user']);
#  $cnt=count($tmp['user']);
#  $list_of_onnullamount_handlers=Array();
#  for($i=0;$i<$cnt;$i++)
#  {
#      if(!ereg('^onnullamount_',$tmp['user'][$i])) {unset($tmp['user'][$i]);continue;}
#      $list_of_onnullamount_handlers[$tmp['user'][$i]]=isset($text[$tmp['user'][$i]])?$text[$tmp['user'][$i]]:$tmp['user'][$i];
#  }
#  //prn($list_of_onnullamount_handlers);
# ------------------------ get list of onnullamount handlers - end ------------
# ------------------------ list of publication_states - begin -----------------
$publication_states = Array();
$cnt = array_keys($GLOBALS['ec_item_publication_states']);
foreach ($cnt as $ke) {
    $publication_states[$ke] = text($GLOBALS['ec_item_publication_states'][$ke]);
}
# ------------------------ list of publication_states - end -------------------
# ------------------------ list of weight units - begin -----------------------
$tmp = explode('|', weight_units);
$list_of_weight_units = Array();
foreach ($tmp as $unitname) {
    $list_of_weight_units[$unitname] = text('ec_units_' . $unitname);
}
# ------------------------ list of weight units - end -------------------------
# ------------------------ list of length units - begin -----------------------
$tmp = explode('|', length_units);
$list_of_length_units = Array();
foreach ($tmp as $unitname) {
    $list_of_length_units[$unitname] = text('ec_units_' . $unitname);
}
# ------------------------ list of length units - end -------------------------



if (!isset($file_upload_form)) {
    $file_upload_form = '';
}

# ------------------------ list of images - begin -----------------------------
$imagelist = '
    <style type="text/css">
      .btn{
        float:right;
        padding:3px;
        border:1px solid black;
        margin-left:3px;
        margin-bottom:3px;
      }
      .imgr{
        clear:right;
        display:block;
      }
      .imgblk{
         display:inline-block;
         margin-bottom:10px;
         margin-right:10px;
         padding:3px;
         background-color:silver;
      }
    </style>
    ';
if (isset($this_ec_item_info['ec_item_img']) && count($this_ec_item_info['ec_item_img']) > 0) {
    // prn($this_ec_item_info['ec_item_img']);
    foreach ($this_ec_item_info['ec_item_img'] as $key => $img_src) {
        //$img_src=explode("\t",$img_src);
        $imagelist.="<span style='' class='imgblk'> {$key}.
                            <input type=submit class=\"btn\" name=ec_item_imgdelete{$key} value=\"&times;\">
                            <input type=submit class=\"btn\" name=ec_item_imgup{$key} value=\"&uarr;\">
                            <input type=submit class=\"btn\" name=ec_item_imgdown{$key} value=\"&darr;\">
                            <a href=\"{$this_site_info['url']}{$img_src['big']}\" target=_blank class=\"imgr\"><img style='max-width:100%;' src=\"{$this_site_info['url']}{$img_src['small']}\"></a>
                     </span>";
    }
}
$imagelist.="
<div>
<input type=file name=ec_item_img1>
<input type=file name=ec_item_img2>
<input type=file name=ec_item_img3><br>
<input type=file name=ec_item_img4>
<input type=file name=ec_item_img5>
<input type=file name=ec_item_img6>
<br>
<input type=submit value=\"".text('Upload')."\">
</div>";
# ------------------------ list of images - end -------------------------------
# ------------------------ list of additional fields - begin ------------------
# category_id should be set
$ec_category_item_field = "";
//prn($this_ec_item_info);
if (!isset($this_ec_item_info['ec_category_item_field']) || !is_array($this_ec_item_info['ec_category_item_field'])) {
    $this_ec_item_info['ec_category_item_field'] = Array();
}
foreach ($this_ec_item_info['ec_category_item_field'] as $fld) {
    $options = Array();
    if (strlen($fld['ec_category_item_field_options'])) {
        $tmp = explode("\n", $fld['ec_category_item_field_options']);
        $cnt = count($tmp);
        for ($i = 0; $i < $cnt; $i++) {
            $tmp[$i] = trim($tmp[$i]);
            if (strlen($tmp[$i]) > 0) {
                $options[$tmp[$i]] = $tmp[$i];
            }
        }
    }
    if (count($options) == 0) {
        $ec_category_item_field.="<tr><td>"
                . get_langstring($fld['ec_category_item_field_title']) . ":</td><td colspan=5>
              <input type=text
                     name=\"ec_item_extra_field[{$fld['ec_category_item_field_id']}][value]\"
                     value=\"{$fld['ec_category_item_field_value']}\" style='width:100%;'>
              <input type=hidden
                     name=\"ec_item_extra_field[{$fld['ec_category_item_field_id']}][type]\"
                     value=\"{$fld['ec_category_item_field_type']}\">
           </td></tr>";
    } else {
        //prn($options);
        $ec_category_item_field.="<tr><td>"
                . get_langstring($fld['ec_category_item_field_title']) . ":</td><td colspan=5>
              <select name=\"ec_item_extra_field[{$fld['ec_category_item_field_id']}][value]\" style='width:100%;'>
                <option value=''> </option>
                " . draw_options($fld['ec_category_item_field_value'], $options) . "
              </select>
              <input type=hidden
                     name=\"ec_item_extra_field[{$fld['ec_category_item_field_id']}][type]\"
                     value=\"{$fld['ec_category_item_field_type']}\">
           </td></tr>";
    }
}
# ------------------------ list of additional fields - end --------------------
# ------------------------ additional categories - begin ----------------------
$additional_categories_selector = "
    <div id=list_of_categories>
    ";
$additional_categories_js = "
      var selectors=[];
    ";
$cnt = isset($this_ec_item_info['additional_categories']) ? count($this_ec_item_info['additional_categories']) : 0;
for ($i = 0; $i < $cnt; $i++) {
    $additional_categories_selector.="
    	<div id=additional_category_{$i}>
    	<select name=additional_category[]  id=selector_{$i} onchange='update_categories()'><option value=''></option>
    	" . draw_options($this_ec_item_info['additional_categories'][$i]['ec_category_id'], $list_of_categories) . "
    	</select>
    	</div>
    	";
    $additional_categories_js.="
    	selectors[$i]=$i;
    	";
}
$additional_categories_js.="
        selectors[$i]=$i;
    	var imax=$i;
   ";
$additional_categories_selector.="
    <div id=additional_category_{$i}>
    <select name=additional_category[]  id=selector_{$i} onchange='update_categories()'><option value=''></option>
    " . draw_options(0, $list_of_categories) . "
    </select>
    </div>
    ";
$additional_categories_selector.="
    </div>
    <style>
    div#list_of_categories select{
      width:100%;
      font-size:85%;
      color:gray;
    }
    </style>
    <script>
    function update_categories()
    {

       var list_of_categories=document.getElementById('list_of_categories');
       var imax_is_empty=false;
       var sel;
       var new_selector;
       var i;
       for(i=0;i<=imax;i++)
       {
           if(typeof(selectors[i])=='undefined') continue;
           sel=document.getElementById('selector_'+i);
           if(sel)
           {
	              if(sel.value=='' && i!=imax )
	              {
	                 document.getElementById('additional_category_'+i).innerHTML='';
	              }

	              if(sel.value!='' && i==imax)
	              {
	                    imax++;
	                    selectors[imax]=imax;
	                    new_selector=document.createElement('div');
	                    new_selector.setAttribute('id', 'additional_category_'+imax);
	                    new_selector.innerHTML='<select name=additional_category[] id=selector_'+imax+'  onchange=\"update_categories()\"><option value=\"\"></option>" . str_replace(Array("\r", "\n"), '', draw_options(0, $list_of_categories)) . "</select>'
	                    var container = document.getElementById('list_of_categories');
	                    container.appendChild(new_selector);
	              }
           }
           else delete(selectors[i]);
       }
    }

    $additional_categories_js
    </script>
    ";
# ------------------------ additional categories - end ------------------------

$input_vars['page_content'].="
  <form action='index.php' method=POST id=editform name=editform enctype=\"multipart/form-data\" style='margin:0;'>

  <input type=hidden name=action         value=\"{$input_vars['action']}\">
  <input type=hidden name=ec_item_id     value=\"{$ec_item_id}\">
  <input type=hidden name=save_changes   value=\"yes\">
  <input type=hidden name=site_id        value=\"{$site_id}\" id=site_id>
  <input type=hidden name=ec_item_lang   value=\"{$ec_item_lang}\">
  <style>
   table.noborders td{border:none;}
  </style>
  <table class=noborders>
  <tr>
     <td colspan=6 style='font-size:100%;'><b>{$message}</b></td>
  </tr>
  <tr>
     <td colspan=6>" . text('Last_changed') . " :
     {$this_ec_item_info['ec_item_last_change_date']}
     &nbsp;&nbsp;&nbsp;" . text('ec_item_purchases') . ":
     " . (isset($this_ec_item_info['ec_item_purchases']) ? $this_ec_item_info['ec_item_purchases'] : 0) . "
     &nbsp;&nbsp;&nbsp;" . text('ec_item_in_cart') . ":
     " . (isset($this_ec_item_info['ec_item_in_cart']) ? $this_ec_item_info['ec_item_in_cart'] : 0) . "
     &nbsp;&nbsp;&nbsp;" . text('ec_item_views') . ":
     " . (isset($this_ec_item_info['ec_item_views']) ? $this_ec_item_info['ec_item_views'] : 0) . "
     </td>
  </tr>
  <tr>
  <td></td>
  <td colspan=5 style='text-align:left;'>
  <input type=submit value=\"{$text['Save']}\" taborder=1>
  <input type=reset  value=\"{$text['Reset']}\">
  </td>
  </tr>
  <tr>
    <td>" . text('Title') . ":</td>
    <td colspan=5><input type=text MAXLENGTH=128 name=ec_item_title value=\"" . checkStr($this_ec_item_info['ec_item_title']) . "\" style='width:100%'></td>
  </tr>

  <tr>
    <td>" . text('Category') . ":</td>
    <td colspan=5>
    <select name=ec_category_id style='width:100%;'>
    <option value=''></option>" .
        draw_options($this_ec_item_info['ec_category_id'], $list_of_categories)
        . "</select>
    </td>
  </tr>

  <tr>
    <td valign=\"top\">" . text('Additional_category') . ":</td>
    <td colspan=5>
    $additional_categories_selector
    </td>
  </tr>


  <tr>
    <td>" . text('Producer') . ":</td>
    <td colspan=5>
    <select name=ec_producer_id style='width:100%;'>
    <option value=''></option>" .
        draw_options($this_ec_item_info['ec_producer_id'], $list_of_producers)
        . "</select>
    </td>
  </tr>

  <tr>
    <td>" . text('ec_material') . ":</td>
    <td colspan=5><input type=text name=ec_item_material value=\"" . checkStr($this_ec_item_info['ec_item_material']) . "\" style='width:100%'></td>
  </tr>

  <tr>
    <td>" . text('Tags') . ":</td>
    <td colspan=5><input type=text name=ec_item_tags value=\"" . checkStr($this_ec_item_info['ec_item_tags']) . "\" style='width:100%'></td>
  </tr>

  <tr>
    <td>" . text('Language') . ":</td>
    <td><select name=ec_item_lang_new style='width:100%;'>" .
        draw_options($this_ec_item_info['ec_item_lang'], db_getrows("SELECT id, name FROM {$table_prefix}languages WHERE is_visible=1 ORDER BY name ASC;"))
        . "</select></td>


    <td align=right>" . text('Ec_item_UID') . ":</td>
    <td><input type=text MAXLENGTH=50 name=ec_item_uid value=\"" . checkStr($this_ec_item_info['ec_item_uid']) . "\" style='width:100%;'></td>
    </td>

    <td align=right>{$text['EC_item_publication']}:</td>
    <td><select name=ec_item_cense_level style='width:100%;'>" .
        draw_options($this_ec_item_info['ec_item_cense_level'], $publication_states)
        . "</select>
    </td>

  </tr>





  <tr>
    <td>" . text('Number_of_items') . ":</td>
    <td><input type=text name=ec_item_amount value=\"" . checkStr($this_ec_item_info['ec_item_amount']) . "\" style='width:100%'></td>
    <td align=right>" . text('Price') . ":</td>
    <td><nobr>
    <input type=text name=ec_item_price value=\"" . checkStr($this_ec_item_info['ec_item_price']) . "\" style='width:40pt'><!--
 -->" . (isset($list_of_currencies[$this_ec_item_info['ec_item_currency']]) ? $list_of_currencies[$this_ec_item_info['ec_item_currency']] : $this_ec_item_info['ec_item_currency']) . "</nobr>
    </td>
    <td align=right>" . text('Mark') . ":</td>
    <td>
    <input type=text name=ec_item_mark value=\"" . checkStr($this_ec_item_info['ec_item_mark']) . "\" style='width:100%'>
    </td>
  </tr>



  <tr>
    <td>" . text('ec_size') . ":</td>
    <td colspan=1><nobr>
     <input type=text MAXLENGTH=60 name=ec_item_size[0] value=\"" . checkStr($this_ec_item_info['ec_item_size'][0]) . "\" style='width:45pt;'><!--
  -->x<!--
  --><input type=text MAXLENGTH=60 name=ec_item_size[1] value=\"" . checkStr($this_ec_item_info['ec_item_size'][1]) . "\" style='width:45pt;'><!--
  -->x<!--
  --><input type=text MAXLENGTH=60 name=ec_item_size[2] value=\"" . checkStr($this_ec_item_info['ec_item_size'][2]) . "\" style='width:45pt;'><!--
  --><select name=ec_item_size[3] style='width:40pt;'>" .
        draw_options($this_ec_item_info['ec_item_size'][3], $list_of_length_units)
        . "</select></nobr></td>


    <td align=\"right\">" . text('ec_weight') . ":</td>
    <td colspan=1>
     <input type=text MAXLENGTH=60 name=ec_item_weight[0] value=\"" . checkStr($this_ec_item_info['ec_item_weight'][0]) . "\" style='width:30pt;'><!--
  --><select name=ec_item_weight[1] style='width:40pt;'>" .
        draw_options($this_ec_item_info['ec_item_weight'][1], $list_of_weight_units)
        . "</select></td>

    <td align=\"right\">" . text('ec_item_ordering_') . ":</td>
    <td colspan=1>
     <input type=text MAXLENGTH=60 name=ec_item_ordering value=\"" . checkStr(isset($this_ec_item_info['ec_item_ordering']) ? $this_ec_item_info['ec_item_ordering'] : 0) . "\" style='width:30pt;'><!--
  --></td>


  </tr>

  <tr>
    <td>" . text('ec_item_code') . ":</td>
    <td colspan=5>
     <input type=text MAXLENGTH=255 name=ec_item_code value=\"" . checkStr($this_ec_item_info['ec_item_code']) . "\" style='width:100%;'>
     </td>
  </tr>


  $ec_category_item_field



    <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
    <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
    <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup.js\"></script>
    <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
    <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />

    <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
    <script type=\"text/javascript\">
       $(function(){
           init_links();
           $('textarea.wysiswyg').markItUp(mySettings);
       });
    </script>

";



$input_vars['page_content'].="
  <tr>
    <td colspan=6><br><br><b>" . text('Images') . ":</b><br/><br/>
       $imagelist
    <br></td>
  </tr>
  
";


$input_vars['page_content'].="


  <tr>
  <td colspan=6><br><br>
  <b>" . text('Short_description') . ":</b><br>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
  <textarea name=ec_item_abstract
            id=ec_item_abstract
            wrap='virtual'
            tabindex='3'
            class=\"wysiswyg\"
            style=\"width:100%; height:100px;\">" .
        checkStr($this_ec_item_info['ec_item_abstract'])
        . "</textarea>
  </td>
  </tr>


  <tr>
  <td colspan=6><br><br>
  <b>" . text('Long_description') . "</b>:<br>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
  <textarea name=ec_item_content
            id=ec_item_content
            wrap='virtual'
            tabindex='3'
            class=\"wysiswyg\"
            style=\"width:100%; height:300px;\">" .
        checkStr($this_ec_item_info['ec_item_content'])
        . "</textarea>
  <div>
  {$text['Upload_page']}
  <input type=\"file\" name=page_upload><input type=submit value=\"{$text['Upload']}\">
  </div>
  </td>
  </tr>

";


//  $input_vars['page_content'].="
//  <tr>
//    <td valign=top><br>".text('Variants')."</td>
//    <td valign=top colspan=5>$ec_item_variant_form</td>
//  </tr>
//
//";

$input_vars['page_content'].="
  <tr>
    <td valign=top colspan=6>
    <b>" . text('Variants') . "</b>:<br/>
    " . text('ec_variant_format') . "
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
  <textarea name=ec_item_variants
            id=ec_item_variants
            wrap='off'
            tabindex='3'
            class=\"wysiswyg\"
            style=\"width:100%; height:300px;\">" .
        checkStr($this_ec_item_info['ec_item_variants'])
        . "</textarea>
    <div style='color:silver;'>
     " . text('ec_variant_sample') . "
    </div>
    </td>
  </tr>

";



$input_vars['page_content'].="
  {$notify_managers_form}
  <tr>
  <td>&nbsp;</td>
  <td colspan=5 style='text-align:left;'>
  <input type=submit value=\"{$text['Save']}\" taborder=1>
  <input type=reset  value=\"{$text['Reset']}\">
  </td>
  </tr>


  {$file_upload_form}
";


$input_vars['page_content'].="
  </table>
  </form>


  ";
//<select name=ec_item_currency style='width:50pt;'>
//    <option value=''></option>".
//    draw_options($this_ec_item_info['ec_item_currency'],$list_of_currencies)
//    ."</select>
//------------------- draw form - end ------------------------------------------
//----------------------------- context menu - begin ---------------------------
$input_vars['page_menu']['page'] = Array('title' => text('EC_item'), 'items' => Array());
$input_vars['page_menu']['page']['items'] = menu_ec_item($this_ec_item_info);

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$input_vars['page_menu']['site'] = Array('title' => "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>", 'items' => Array());

$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);

//----------------------------- context menu - end -----------------------------
?>
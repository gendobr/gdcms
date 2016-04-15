<?php

/*
  Editing news
  argument is $news_id    - news identifier, integer, mandatory
  $lang       - news language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
 */

$debug = false;


run('site/image/url_replacer');
run('lib/img');

run('news/menu');

$message = '';
//prn($_REQUEST);
# ------------------- check news id - begin ------------------------------------
$news_id = checkInt((isset($input_vars['news_id']) ? $input_vars['news_id'] : 0));
$lang = get_language('lang');

// $query = "SELECT * FROM {$table_prefix}news WHERE id={$news_id} AND lang='$lang'";
//\e::db_getonerow($query);
$this_news_info = news_info($news_id, $lang);
if ($debug) {
    prn(checkStr($query), $this_news_info);
}
if (checkInt($this_news_info['id']) <= 0) {
    $main_template_name = '';
    //prn($input_vars);
    if (!isset($input_vars['site_id'])) {
        $input_vars['site_id'] = 0;
    }
    //prn("Location: index.php?action=news/list&site_id=".( (int)$input_vars['site_id'] ) );
    header("Location: index.php?action=news/list&site_id=" . ( (int) $input_vars['site_id'] ));
    die();
    return 0;
}
# prn($this_news_info);
# ------------------- check news id - end --------------------------------------
# ------------------- get permission - begin -----------------------------------
$user_cense_level = get_level($this_news_info['site_id']);
if ($user_cense_level <= 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
#if($debug) prn('$user_cense_level='.$user_cense_level);
# ------------------- get permission - end -------------------------------------




run('site/menu');
# ------------------- site info - begin ----------------------------------------
$site_id = checkInt($this_news_info['site_id']);
$this_site_info = get_site_info($site_id);

# ------------------- site info - end ------------------------------------------
# ----------------------- list of site managers - begin ------------------------
$tmp = \e::db_getrows(
        "select u.id, u.full_name, u.user_login, u.email, su.level
        from {$GLOBALS['table_prefix']}user AS u, {$GLOBALS['table_prefix']}site_user AS su
        where u.id = su.user_id AND su.site_id = {$this_site_info['id']}
        order by level desc");
$this_site_info['managers'] = Array();
foreach ($tmp as $tm) {
    $this_site_info['managers'][$tm['id']] = $tm;
}
unset($tm, $tmp);
# ----------------------- list of site managers - end --------------------------


if (isset($input_vars['debug']) && $input_vars['debug'] == 'true') {
    prn('$this_site_info=', $this_site_info);
}


// save news
include(\e::config('SCRIPT_ROOT') . '/news/save.php');





# ------------------- check if all images exist - begin ------------------------
$img_root_url = sites_root_URL . '/' . $this_site_info['dir'] . '/';
$parsed_html = replace_src($this_news_info['content'], $img_root_url);
$this_news_info['content'] = $parsed_html['html'];

$required_images = Array();
clearstatcache();
foreach ($parsed_html['src'] as $fname) {
    if (!file_exists(\e::config('SITES_ROOT') . "/{$this_site_info['dir']}/{$fname}")) {
        $required_images[] = $fname;
    }
}
if (count($required_images) > 0) {
    $file_upload_form = "

          <h3 style='text-align:left;'>{$text['The_news_needs_files']} :</h3>
        ";
    foreach ($required_images as $key => $val) {
        $file_upload_form.="
           <div class=label>{$val}</div>
           <div class=big>
           <input type='file' name='file[{$val}]'>
           </div>
           ";
    }
    $file_upload_form.="
        <div class=big>
        <input type='submit' value='{$text['Upload_files']}' style='width:40%;'>
        </div>
        ";
}
# ------------------- check if all images exist - begin ------------------------
// if visual editor should be activated
if (!isset($input_vars['aed'])) {
    $input_vars['aed'] = 0;
}

# ------------------------ draw rich text editor - begin -----------------------
if ($input_vars['aed'] == 1) {

    // ================ draw rich text editor = begin ==========================
    $page_content_textarea = "
           <!-- Load TinyMCE -->
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/tiny_mce/jquery.tinymce.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/tiny_mce_start.js\"></script>

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(window).load(function(){
                  init_links();
                  var stn = { external_link_list_url : \"index.php?action=site/filechooser/tiny_mce_link_list&site_id={$site_id}\",
                       external_image_list_url : \"index.php?action=site/filechooser/tiny_mce_image_list&site_id={$site_id}\",
                       language : \"".substr($_SESSION['lang'],0,2)."\"};
                  tinymce_init('textarea#page_content_area',stn);
                  tinymce_init('textarea#page_abstract',stn);
              });
           </script>
           <!-- /TinyMCE -->
      ";
    // ================ draw rich text editor = end ============================
} else {
    // ================ draw simple editor = begin =============================
    $page_content_textarea = "


           <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/markitup/jquery.markitup.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/markitup/sets/html/set.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/markitup.js\"></script>
           <link rel=\"stylesheet\" type=\"text/css\" href=\"".site_root_URL."/scripts/lib/markitup/skins/simple/style.css\" />
           <link rel=\"stylesheet\" type=\"text/css\" href=\"".site_root_URL."/scripts/lib/markitup/sets/html/style.css\" />

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  init_links();
                  $('textarea.wysiswyg').markItUp(mySettings);
              });
           </script>
            ";
    // ================ draw simple editor = end ===============================
}
# ------------------------ draw rich text editor - end -------------------------
# ------------------- draw form - begin ----------------------------------------
$notify_managers_form = '';
foreach ($this_site_info['managers'] as $mn) {
    $notify_managers_form .="<label><input type=checkbox style='width:10pt;' name='notify[{$mn['id']}]'> {$mn['full_name']}</label><br/>";
}
if (strlen($notify_managers_form) > 0)
    $notify_managers_form = "<div class=label>{$text['Send_notification_to']}</div><div class=big>{$notify_managers_form}</div>";


# ----------------------- date selector - begin -----------------------------

function get_date_selector($form_element_name, $shown_date, $default_date='') {
    $now = getdate(time());
    if (strlen($shown_date) > 0) {
        $date_posted = strtotime($shown_date);
        if ($date_posted === -1) {
            if (strlen($default_date) == 0)
                $date_posted = Array('mon' => '', 'mday' => '', 'year' => '', 'hours' => '', 'minutes' => '');
            else
                $date_posted = getdate($default_date);
        }
        else
            $date_posted = getdate($date_posted);
    }
    else {
        $date_posted = Array('mon' => '', 'mday' => '', 'year' => '', 'hours' => '', 'minutes' => '');
    }


    // prn($date_posted);
    # year
    //$years = Array();
    //for ($i = -2; $i < 10; $i++)
    //    $years[$now['year'] - $i] = $now['year'] - $i;

    # month
    $months = Array('', text('month_January'), text('month_February'), text('month_March'), text('month_April'), text('month_May'), text('month_June'), text('month_July'), text('month_August'), text('month_September'), text('month_October'), text('month_November'), text('month_December'));

    # days
    $days = range(0, 31);
    unset($days[0]);

    # hours
    $hours = range(0, 23);
    array_merge($hours);
    //prn($hours);
    # minutes
    $minutes = range(0, 59);

    $date_selector = '<nobr>';
    $date_selector.="<select name=\"{$form_element_name}[month]\" style='width:35%;'>" . draw_opt($date_posted['mon'], $months) . "</select>";
    $date_selector.="<select name=\"{$form_element_name}[day]\"  style='width:15%;'>" . draw_opt($date_posted['mday'], $days) . "</select>";
    // $date_selector.="<select name=\"{$form_element_name}[year]\"  style='width:25%;'>" . draw_opt($date_posted['year'], $years) . "</select>";
    $date_selector.="<input type=text name=\"{$form_element_name}[year]\"  style='width:20%;' value=\"".htmlspecialchars($date_posted['year'])."\">";
    $date_selector.="&nbsp;<select name=\"{$form_element_name}[hour]\"  style='width:15%;'>" . draw_opt($date_posted['hours'], $hours) . "</select>";
    $date_selector.=":<select name=\"{$form_element_name}[minute]\"  style='width:10%;'>" . draw_opt($date_posted['minutes'], $minutes) . "</select>";
    $date_selector.='</nobr>';
    return $date_selector;
}

function draw_opt($val, $opts) {
    $tor = "\n<option value=\"\"> </option>";
    #echo '<pre>'; echo "<b>$val</b><br>"; print_r($opts); echo '</pre>';
    foreach ($opts as $k => $v) {
        if ($k === $val) {
            $selected = 'selected';
        } else {
            $selected = '';
        }
        $tor.="\n<option value=\"{$k}\" $selected>{$v}</option>";
    }
    return $tor;
}

$date_selector = get_date_selector('date_posted', $this_news_info['last_change_date'], time());
# ----------------------- date selector - end -------------------------------
# ----------------------- expiration_date selector - begin ------------------
$expiration_date_selector = get_date_selector('expiration_date_posted', $this_news_info['expiration_date']);
# ----------------------- expiration_date selector - end --------------------



# ------------------------ list of all categories - begin ----------------------
$query = "SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>=0 AND site_id={$site_id} ORDER BY start ASC";
$tmp = \e::db_getrows($query);
$list_of_categories = Array();
foreach ($tmp as $tm) {
    $list_of_categories[$tm['category_id']] = str_repeat(' + ', $tm['deep']) . get_langstring($tm['category_title']);
}
unset($tmp, $tm);
//prn($list_of_categories);

$news_categories = \e::db_getrows("SELECT category_id FROM {$table_prefix}news_category WHERE news_id={$this_news_info['id']}");
$cnt = count($news_categories);
$news_categories_selector = "
    <div id=list_of_categories>
    ";
$news_categories_js = "
      var selectors=[];
    ";
for ($i = 0; $i < $cnt; $i++) {
    $news_categories_selector.="
    	<div id=news_category_{$i}>
    	<select name=news_category[]  id=selector_{$i} onchange='update_categories()'><option value=''></option>
    	" . draw_options($news_categories[$i]['category_id'], $list_of_categories) . "
    	</select>
    	</div>
    	";
    $news_categories_js.="
    	selectors[$i]=$i;
    	";
}
$news_categories_js.="
        selectors[$i]=$i;
    	var imax=$i;
    ";
$news_categories_selector.="
	   	<div id=news_category_{$i}>
	   	<select name=news_category[] id=selector_{$i} onchange='update_categories()'><option value=''></option>
	     	" . draw_options(0, $list_of_categories) . "
	   	</select>
        </div>
   	</div>
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
	                 document.getElementById('news_category_'+i).innerHTML='';
	              }

	              if(sel.value!='' && i==imax)
	              {
	                    imax++;
	                    selectors[imax]=imax;
	                    new_selector=document.createElement('div');
	                    new_selector.setAttribute('id', 'news_category_'+imax);
	                    new_selector.innerHTML='<select name=news_category[] id=selector_'+imax+'  onchange=\"update_categories()\"><option value=\"\"></option>" . str_replace(Array("\r", "\n","'",'"'), ' ', draw_options(0, $list_of_categories)) . "</select>'
	                    var container = document.getElementById('list_of_categories');
	                    container.appendChild(new_selector);
	              }
           }
           else delete(selectors[i]);
       }
    }

    $news_categories_js
    </script>
   	";
# ------------------------ list of all categories - end ------------------------


















if (!isset($file_upload_form)) {
    $file_upload_form = '';
}
$input_vars['aed'] = isset($input_vars['aed']) ? ((int) $input_vars['aed']) : 0;
$input_vars['page_title'] = $text['Editing_news'];
$input_vars['page_header'] = $text['Editing_news'];
$input_vars['page_content'] = "

  <form action='index.php?action=news/edit' method=POST name=editform  enctype=\"multipart/form-data\">
  <input type=hidden name=\"action\"  value=\"{$input_vars['action']}\">
  <input type=hidden name=aed  value=\"{$input_vars['aed']}\">
  <input type=hidden name=news_id value=\"{$this_news_info['id']}\">
  <input type=hidden name=lang value=\"{$this_news_info['lang']}\">
  <input type=hidden name=save_changes value=\"yes\">
  <input type=hidden name=site_id id=site_id value=\"{$this_news_info['site_id']}\">

  <b>{$message}</b>
  <div class=label>{$text['Site_title']} :</div>
  <div class=big>{$this_site_info['title']}</div>

  <div class=label>{$text['News_title']} :</div>
  <div class=big><input type=text name=news_title value=\"" . checkStr($this_news_info['title']) . "\"></div>


  <span class=blk8><!-- 
 --><span class=blk4>
    <div class=label>{$text['Language']} : </div>
    <div class=big>
      <select name=news_lang>" .
          draw_options($this_news_info['lang'], \e::db_getrows("SELECT id, name FROM {$table_prefix}languages WHERE is_visible=1 ORDER BY name ASC;"))
          . "</select>
    </div>
    </span><!-- 
 --><span class=blk8>
       <div class=label>" . text('Date_start_publicaton') . " :</div>
       <div class=big>{$date_selector}</div>
    </span><!-- 
 --><span class=blk4>    
      <div class=label>{$text['Approve']} :</div>
      <div class=big>
        <select name=news_cense_level>" .
          draw_options($this_news_info['cense_level'], Array(0 => $text['negative_answer'], $user_cense_level => $text['positive_answer']))
          . "</select>
      </div>
    </span><!-- 
   --><span class=blk8>
      <div class=label>" . text('Expiration_Date') . " :</div>
      <div class=big>{$expiration_date_selector}</div>
    </span><!-- 
 --><div class=label>{$text['News_tags']} ({$text['CSV']}):</div>
    <div class=big><input type=text MAXLENGTH=255 name=tags value=\"" . checkStr($this_news_info['tags']) . "\"></div><!-- 
 --></span><!-- 
 --><span class=blk4>
    <div class=label>" . text('Icon') . " :</div>
    <div class=news_icon>".($this_news_info['news_icon']?"<a class=\"delete_link\" href=\"index.php?action=news/delete_icon&news_id={$this_news_info['id']}&lang={$this_news_info['lang']}\">&times;</a><a href=\"{$this_site_info['site_root_url']}/{$this_news_info['news_icon']['full']}\" target=_blank><img src=\"{$this_site_info['site_root_url']}/{$this_news_info['news_icon']['small']}\" style=\"max-width:100%;\">":''  )."</a></div>
    <input type=\"file\" name=\"news_icon\">
  </span>



  <br/>
  <div class=label>" . $text['News_Category'] . " : </div>
  <div id=categories_selector class=big>
     $news_categories_selector
  </div>
  


  <div class=label>
   {$text['Abstract']} :
  </div>
  <div  style='border:1px solid #00334c;'>
      <div class=big>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
        <textarea name=page_abstract
                  id=page_abstract
                  wrap='virtual'
                  tabindex='3'
                  class='wysiswyg'
                  " . str_replace("%s", 'page_abstract', (false ? $attributes : '')) . "
                  cols=50 rows=7>" .
        checkStr($this_news_info['abstract'])
        . "</textarea>
      </div>
  </div>

 <div class=label>
  " . text('Contents') . " :
 </div>
 <div  style='border:1px solid #00334c;'>

 <div class=big>

      $page_content_textarea
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
       <textarea name=page_content style='border:none;width:100%;'
            id=page_content_area
            wrap='virtual'
            tabindex='3'
            class='wysiswyg'
            cols=80 rows=20>" .
            checkStr($this_news_info['content'])
        . "</textarea>

 </div>
 </div>

  <div class=label>".text('news_code')." :</div>
  <div class=big><input type=text name=\"news_code\" value=\"" . checkStr($this_news_info['news_code']) . "\"></div>

  <div class=label>".text('news_meta_info')." :</div>
  <div class=big>
  <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/meta-tags-insert.js\"></script>
  <script type=\"text/javascript\">
  $(document).ready(function(){
     metaTagsButtons('news_meta_info');
  });
  </script>
  <textarea name=\"news_meta_info\" id=\"news_meta_info\" style=\"width:100%; height:100px;\">" . checkStr($this_news_info['news_meta_info']) . "</textarea>
  </div>

  <div class=label>".text('news_extra_1')." :</div>
  <div class=big><input type=text name=\"news_extra_1\" value=\"" . checkStr($this_news_info['news_extra_1']) . "\"></div>

  <div class=label>".text('news_extra_2')." :</div>
  <div class=big><input type=text name=\"news_extra_2\" value=\"" . checkStr($this_news_info['news_extra_2']) . "\"></div>

        
        
        {$notify_managers_form}

 <div class=big>
  <input type=submit value=\"{$text['Save']}\" style='width:40%;'>
  <input type=reset  value=\"{$text['Reset']}\" style='width:40%;'>
</div>

{$file_upload_form}

  </form>

  ";

//------------------- draw form - end ------------------------------------------
//----------------------------- context menu - begin ---------------------------
// current news menu
$input_vars['page_menu']['page'] = Array('title' => $text['News'], 'items' => Array());

$input_vars['page_menu']['page']['items'] = menu_news($this_news_info);

// ---------------------- list of translations - begin -------------------------


$query = "SELECT id, lang, title FROM {$table_prefix}news WHERE site_id={$site_id} and id={$this_news_info['id']} and lang<>'{$this_news_info['lang']}'";
$tmp = \e::db_getrows($query);
if ($tmp) {
    $input_vars['page_menu']['page']['items']['news/translations_links'] = Array(
        'URL' => ""
        , 'innerHTML' => '<br/>'.text("news_translations") . ":"
        , 'attributes' => ""
    );

    foreach ($tmp as $news_translation) {
        $input_vars['page_menu']['page']['items']["news/{$this_news_info['id']}/{$news_translation['lang']}"] = Array(
            'URL' => "index.php?action=news/edit&aed={$input_vars['aed']}&site_id={$this_news_info['site_id']}&news_id={$this_news_info['id']}&lang={$news_translation['lang']}"
            , 'innerHTML' => "{$news_translation['lang']}: " . shorten(strip_tags($news_translation['title']), 30)
            , 'attributes' => "title=\"" . checkStr(strip_tags($news_translation['title'])) . "\""
        );
    }
}
// ---------------------- list of translations - end ---------------------------
// prn($input_vars['page_menu']['page']);


$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 25) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------
// clear data
clear('page_abstract', 'news_title', 'date_posted', 'news_lang', 'news_cense_level', 'save_changes');
unset($_POST['page_content'], $_GET['page_content'], $_REQUEST['page_content']);
?>
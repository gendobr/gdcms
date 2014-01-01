<?php

/**
  <h2>Зміна події</h2>
 */
//------------------- site info - begin ----------------------------------------
run('site/menu');
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
// ------------------ get event info - begin -----------------------------------
$event_id = isset($input_vars['event_id']) ? ((int) $input_vars['event_id']) : 0;
$this_event_info = GetOneRow(Execute($db, "SELECT * FROM {$table_prefix}calendar WHERE id = '$event_id'"));
if (!$this_event_info['id']) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = text('Calendar_event_not_found');
    return 0;
}
// ------------------ get event info - end -------------------------------------
if (!isset($input_vars['aed'])) {
    $input_vars['aed'] = 0;
}
// load calendar functions
run('calendar/functions');



if (isset($input_vars['upd'])) {
    #---------------------------- Перевірка змінних - початок ------------------
    $nazva1 = $input_vars['nazva1'];
    $adresa1 = $input_vars['adresa1'];
    $kartynka1 = $input_vars['kartynka1'];

    $pochrik1 = $input_vars['pochrik1'];
    $pochmis1 = $input_vars['pochmis1'];
    $pochday1 = $input_vars['pochday1'];
    $pochtyzh1 = $input_vars['pochtyzh1'];
    $pochgod1 = $input_vars['pochgod1'];
    $pochhv1 = $input_vars['pochhv1'];


    $kinrik1 = $input_vars['kinrik1'];
    $kinmis1 = $input_vars['kinmis1'];
    $kinday1 = $input_vars['kinday1'];
    $kintyzh1 = $input_vars['kintyzh1'];
    $kingod1 = $input_vars['kingod1'];
    $pochday1 = $input_vars['pochday1'];
    $kinhv1 = $input_vars['kinhv1'];
    $vis1 = $input_vars['vis1'];
    $description = $input_vars['description'];
    #---------------------------- Перевірка змінних - кінець--------------------

    if ($nazva1) {
        $query = "UPDATE {$table_prefix}calendar
                  SET nazva = '" . DbStr($nazva1) . "',
                      kartynka = '" . DbStr($kartynka1) . "',
                      adresa = '" . DbStr($adresa1) . "',
                      description = '" . DbStr($description) . "',
                      pochrik = " . ( (int) $pochrik1) . ",
                      kinrik = " . ( (int) $kinrik1) . ",
                      pochmis = " . ( (int) $pochmis1) . ",
                      kinmis = " . ( (int) $kinmis1) . ",
                      pochday = " . ( (int) $pochday1) . ",
                      kinday = " . ( (int) $kinday1) . ",
                      pochtyzh = " . ( (int) $pochtyzh1) . ",
                      kintyzh = " . ( (int) $kintyzh1) . ",
                      pochgod = " . ( (int) $pochgod1) . ",
                      kingod = " . ( (int) $kingod1) . ",
                      pochhv = " . ( (int) $pochhv1) . ",
                      kinhv = " . ( (int) $kinhv1) . ",
                      vis = " . ( (int) $vis1) . "
                  WHERE id = '$event_id' and site_id={$site_id}";
        //prn(checkStr($query));exit();
        db_execute($query);



        // save new categories
        if (isset($input_vars['event_category']) && is_array($input_vars['event_category'])) {
            $query = "DELETE FROM {$table_prefix}calendar_category WHERE event_id=$event_id";
            db_execute($query);
            $query = Array();
            foreach ($input_vars['event_category'] as $cat) {
                $cat = (int) $cat;
                if ($cat <= 0)
                    continue;
                $query[] = "($event_id,$cat)";
            }
            if (count($query) > 0) {
                $query = "INSERT INTO {$table_prefix}calendar_category(event_id,category_id) VALUES " . join(',', $query);
                db_execute($query);
            }
        }
        header("Location:index.php?action=calendar/edit&site_id={$site_id}&event_id={$event_id}");
        exit();
        // redirect to event editor
        //        echo "<p class=gotovo>Інформацію успішно змінено.</p><p><a href=spysok.php>Повернутися до списку статей</a></p>";
        //        echo "<p><a href=index.php>Додати статтю</a></p>";
        //        $this_event_info = GetOneRow(Execute($db, "SELECT * FROM {$table_prefix}calendar WHERE id = '$event_id'"));
    }
}










if (isset($input_vars['upd'])) {

    $this_event_info = GetOneRow(Execute($db, "SELECT * FROM {$table_prefix}calendar WHERE id = '$new'"));
    while ($row = mysql_fetch_array($result)) {

    }
}


// --------------------- draw - begin ------------------------------------------
$input_vars['page_title'] =
        $input_vars['page_header'] = text('Calendar_event_edit') . $event_id;

$calendar_dni = calendar_dni();
$calendar_misyaci = calendar_misyaci();
$calendar_dnityzhnya = calendar_dnityzhnya();
$calendar_god = calendar_hours();
$calendar_hv = calendar_minutes();

# ------------------------ list of categories - begin -------------------------
$query = "SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
$tmp = db_getrows($query);
$list_of_categories = Array();
foreach ($tmp as $tm)
    $list_of_categories[$tm['category_id']] = str_repeat(' + ', $tm['deep']) . get_langstring($tm['category_title']);
unset($tmp, $tm);
//prn($list_of_categories);

$event_categories = db_getrows("SELECT category_id FROM {$table_prefix}calendar_category WHERE event_id={$event_id}");
$cnt = count($event_categories);
$event_categories_selector = "
    <div id=list_of_categories>
    ";
$event_categories_js = "
      var selectors=[];
    ";
for ($i = 0; $i < $cnt; $i++) {
    $event_categories_selector.="
    	<div id=event_category_{$i}>
    	<select name=event_category[]  id=selector_{$i} onchange='update_categories()'><option value=''></option>
    	" . draw_options($event_categories[$i]['category_id'], $list_of_categories) . "
    	</select>
    	</div>
    	";
    $event_categories_js.="
    	selectors[$i]=$i;
    	";
}
$event_categories_js.="
        selectors[$i]=$i;
    	var imax=$i;
    ";
$event_categories_selector.="
	   	<div id=event_category_{$i}>
	   	<select name=event_category[] id=selector_{$i} onchange='update_categories()'><option value=''></option>
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
	                 document.getElementById('event_category_'+i).innerHTML='';
	              }

	              if(sel.value!='' && i==imax)
	              {
	                    imax++;
	                    selectors[imax]=imax;
	                    new_selector=document.createElement('div');
	                    new_selector.setAttribute('id', 'event_category_'+imax);
	                    new_selector.innerHTML='<select name=event_category[] id=selector_'+imax+'  onchange=\"update_categories()\"><option value=\"\"></option>" . str_replace(Array("\r", "\n"), '', draw_options(0, $list_of_categories)) . "</select>'
	                    var container = document.getElementById('list_of_categories');
	                    container.appendChild(new_selector);
	              }
           }
           else delete(selectors[i]);
       }
    }

    $event_categories_js
    </script>
   	";
# ------------------------ list of categories - end ---------------------------



if ($input_vars['aed'] == 1) {
    $input_vars['page_content'] = "
           <!-- Load TinyMCE -->
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/tiny_mce/jquery.tinymce.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/tiny_mce_start.js\"></script>

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  init_links();
                  tinymce_init('textarea.wysiswyg',
                     { external_link_list_url : \"index.php?action=site/filechooser/tiny_mce_link_list&site_id={$site_id}\",
                       external_image_list_url : \"index.php?action=site/filechooser/tiny_mce_image_list&site_id={$site_id}\"});
              });
           </script>
           <!-- /TinyMCE -->
           ";
} else {
    $input_vars['page_content'] = "
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
           </script>";
}

$input_vars['page_content'].= "
    <form method=post action=index.php?site_id={$site_id}&action=calendar/edit&event_id={$event_id}>
          <input type=hidden name='upd' value='1'>
          <p>" . text('Calendar_event_title') . "<br />
          <INPUT type=text name=nazva1 value=\"" . checkStr($this_event_info['nazva']) . "\" SIZE=50></p>

          <p>" . text('Calendar_event_image') . "<br />
          <INPUT type=text name=kartynka1 value=\"{$this_event_info['kartynka']}\"  SIZE=50></p>

          <p>" . text('Calendar_event_URL') . "<br />
          <INPUT type=text name=adresa1  value=\"{$this_event_info['adresa']}\"  SIZE=50></p>

          <p>" . text('Calendar_event_is_visible') . "<br />
          <SELECT  NAME=vis1>" . draw_options($this_event_info['vis'], Array(1 => 'Так', 0 => 'Ні')) . "</SELECT></p>

          <table><tr><td width=50%>
          <h4>" . text('Calendar_event_start_time') . "</h4>
          <p>" . text('Calendar_event_year') . "<br />
          <INPUT type=text name=pochrik1 value=\"{$this_event_info['pochrik']}\" SIZE=4 style='width:100%;'></p>

          <p>" . text('Calendar_event_month') . "<br />
          <SELECT  NAME=pochmis1 style='width:100%;'>" . draw_options($this_event_info['pochmis'], $calendar_misyaci) . "</SELECT></p>

          <p>" . text('Calendar_event_month_day') . "<br />
          <SELECT  NAME=pochday1 style='width:100%;'>" . draw_options($this_event_info['pochday'], $calendar_dni) . "</SELECT></p>

          <p>" . text('Calendar_event_week_day') . "<br />
          <SELECT  NAME=pochtyzh1 style='width:100%;'>" . draw_options($this_event_info['pochtyzh'], $calendar_dnityzhnya) . "</SELECT></p>

          <p>" . text('Calendar_event_daytime') . "<br />
          <SELECT  NAME=pochgod1  style='width:45%;'>" . draw_options($this_event_info['pochgod'], $calendar_god) . "</SELECT><span  style='width:10%;display:inline-block;'>:</span><SELECT  NAME=pochhv1 style='width:45%;'>" . draw_options($this_event_info['pochhv'], $calendar_hv) . "</SELECT></p>

          </td><td width=50%>

          <h4>" . text('Calendar_event_finish_time') . "</h4>

          <p>" . text('Calendar_event_year') . "<br />
          <INPUT type=text name=kinrik1 value=\"{$this_event_info['kinrik']}\" SIZE=4 style='width:100%;'></p>

          <p>" . text('Calendar_event_month') . "<br />
          <SELECT  NAME=kinmis1 style='width:100%;'>" . draw_options($this_event_info['kinmis'], $calendar_misyaci) . "</SELECT></p>

          <p>" . text('Calendar_event_month_day') . "<br />
          <SELECT  NAME=kinday1 style='width:100%;'>" . draw_options($this_event_info['kinday'], $calendar_dni) . "</SELECT></p>

          <p>" . text('Calendar_event_week_day') . "<br />
          <SELECT  NAME=kintyzh1 style='width:100%;'>" . draw_options($this_event_info['kintyzh'], $calendar_dnityzhnya) . "</SELECT></p>

          <p>" . text('Calendar_event_daytime') . "<br />
          <SELECT  NAME=kingod1 style='width:45%;'>" . draw_options($this_event_info['kingod'], $calendar_god) . "</SELECT><span  style='width:10%;display:inline-block;'>:</span><SELECT  NAME=kinhv1 style='width:45%;'>" . draw_options($this_event_info['kinhv'], $calendar_hv) . "</SELECT></p>

</td></tr></table>
<div class=label>" . $text['Category'] . " : </div>
  <div id=categories_selector class='big' style='width:98%;'>
     $event_categories_selector
  </div>
<p>
" . text('Calendar_event_description') . "<br />
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
     <textarea name=description rows='10' style='width:100%;' class='wysiswyg'>" . checkStr($this_event_info['description']) . "</textarea>
</p>

<input type=submit value=\"" . text('Save_Changes') . "\">
</form>";

// --------------------- draw - end --------------------------------------------
//# category context menu
//  $input_vars['page_menu']['category']=Array('title'=>"Категория",'items'=>Array());
//  $input_vars['page_menu']['category']['items']=menu_category($this_category->info);
//  //prn($input_vars['page_menu']['category']);
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
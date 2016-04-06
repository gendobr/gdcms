<?php

/**

 */
//------------------- site info - begin ----------------------------------------
run('site/menu');
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
// ------------------ get event info - begin -----------------------------------
$event_id = isset($input_vars['event_id']) ? ((int) $input_vars['event_id']) : 0;
$this_event_info =\e::db_getonerow("SELECT * FROM {$table_prefix}calendar WHERE id = $event_id");
if (!$this_event_info['id']) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Calendar_event_not_found');
    return 0;
}
// get event dates
$tmp = \e::db_getrows("SELECT * FROM {$table_prefix}calendar_date WHERE calendar_id=$event_id");
$this_event_info['dates'] = Array();
foreach ($tmp as $tm) {
    $this_event_info['dates'][$tm['id']] = $tm;
}

// ------------------ get event info - end -------------------------------------
if (!isset($input_vars['aed'])) {
    $input_vars['aed'] = 0;
}
// load calendar functions
run('calendar/functions');




$new_pochrik = '';
$new_pochmis = '';
$new_pochday = '';
$new_pochtyzh = '';
$new_pochgod = '';
$new_pochhv = '';

$new_kinrik = '';
$new_kinmis = '';
$new_kinday = '';
$new_kintyzh = '';
$new_kingod = '';
$new_kinhv = '';

if (isset($input_vars['upd'])) {
    //    prn('111');
    //    prn($_REQUEST); exit();
    //    prn($input_vars); exit();

    $nazva1 = $input_vars['nazva1'];
    $adresa1 = $input_vars['adresa1'];
    $kartynka1 = $input_vars['kartynka1'];

    $vis1 = $input_vars['vis1'];
    $description = $input_vars['description'];

    if ($nazva1) {
        $query = "UPDATE {$table_prefix}calendar
                  SET nazva = '" . \e::db_escape($nazva1) . "',
                      kartynka = '" . \e::db_escape($kartynka1) . "',
                      adresa = '" . \e::db_escape($adresa1) . "',
                      description = '" . \e::db_escape($description) . "',
                      vis = " . ( (int) $vis1) . "
                  WHERE id = '$event_id' and site_id={$site_id}";
        //prn(checkStr($query));exit();
        \e::db_execute($query);

        // --------------- add new date - begin --------------------------------
        $new_pochrik = $input_vars['dt'][0]['pochrik'];
        $new_pochmis = $input_vars['dt'][0]['pochmis'];
        $new_pochday = $input_vars['dt'][0]['pochday'];
        $new_pochtyzh = $input_vars['dt'][0]['pochtyzh'];
        $new_pochgod = $input_vars['dt'][0]['pochgod'];
        $new_pochhv = $input_vars['dt'][0]['pochhv'];

        $new_kinrik = $input_vars['dt'][0]['kinrik'];
        $new_kinmis = $input_vars['dt'][0]['kinmis'];
        $new_kinday = $input_vars['dt'][0]['kinday'];
        $new_kintyzh = $input_vars['dt'][0]['kintyzh'];
        $new_kingod = $input_vars['dt'][0]['kingod'];
        $new_kinhv = $input_vars['dt'][0]['kinhv'];
        if (   strlen($new_pochrik)>0 && strlen($new_pochmis)>0
            && strlen($new_pochday)>0 && strlen($new_pochtyzh)>0
            && strlen($new_pochgod)>0 && strlen($new_pochhv)>0
            && strlen($new_kinrik)>0 && strlen($new_kinmis)>0
            && strlen($new_kinday)>0 && strlen($new_kintyzh)>0
            && strlen($new_kingod)>0 && strlen($new_kinhv)>0) {
            $new_pochrik = (int) $input_vars['dt'][0]['pochrik'];
            $new_pochmis = (int) $input_vars['dt'][0]['pochmis'];
            $new_pochday = (int) $input_vars['dt'][0]['pochday'];
            $new_pochtyzh = (int) $input_vars['dt'][0]['pochtyzh'];
            $new_pochgod = (int) $input_vars['dt'][0]['pochgod'];
            $new_pochhv = (int) $input_vars['dt'][0]['pochhv'];

            $new_kinrik = (int) $input_vars['dt'][0]['kinrik'];
            $new_kinmis = (int) $input_vars['dt'][0]['kinmis'];
            $new_kinday = (int) $input_vars['dt'][0]['kinday'];
            $new_kintyzh = (int) $input_vars['dt'][0]['kintyzh'];
            $new_kingod = (int) $input_vars['dt'][0]['kingod'];
            $new_kinhv = (int) $input_vars['dt'][0]['kinhv'];
            
            $query="
                INSERT INTO {$table_prefix}calendar_date 
                        ( site_id, calendar_id,      pochrik,      pochmis,      pochtyzh,      pochday,      pochgod,      pochhv,      kinrik,      kinmis,      kintyzh,      kinday,      kingod,     kinhv  )
                VALUES  ( $site_id,  $event_id, $new_pochrik, $new_pochmis, $new_pochtyzh, $new_pochday, $new_pochgod, $new_pochhv, $new_kinrik, $new_kinmis, $new_kintyzh, $new_kinday, $new_kingod, $new_kinhv );
                ";
            // prn($query); exit();
            \e::db_execute($query);
            
            $new_pochrik = '';
            $new_pochmis = '';
            $new_pochday = '';
            $new_pochtyzh = '';
            $new_pochgod = '';
            $new_pochhv = '';

            $new_kinrik = '';
            $new_kinmis = '';
            $new_kinday = '';
            $new_kintyzh = '';
            $new_kingod = '';
            $new_kinhv = '';
        }
        // --------------- add new date - end ----------------------------------
        // 
        // 
        // --------------- delete - begin --------------------------------------
        $toDelete=Array();
        foreach($this_event_info['dates'] as $k=>$v){
            if(!isset($input_vars['dt'][$k])){
                $toDelete[]=(int)$k;
            }
        }
        if(count($toDelete)>0){
            $query="DELETE FROM {$table_prefix}calendar_date WHERE site_id=$site_id AND calendar_id=$event_id AND id IN(".join(',',$toDelete).")";
            \e::db_execute($query);
        }
        // --------------- delete - end ----------------------------------------
        
        // --------------- update dates - begin --------------------------------
        
        foreach($input_vars['dt'] as $id => $dt){
            // prn($dt);
            if($id==0){
                continue;
            }
            $query="UPDATE {$table_prefix}calendar_date 
                    SET
                    pochrik = ".( (int)$dt['pochrik'] )." , 
                    pochmis = ".( (int)$dt['pochmis'] ).", 
                    pochtyzh = ".( (int)$dt['pochtyzh'] )." , 
                    pochday = ".( (int)$dt['pochday'] )." , 
                    pochgod = ".( (int)$dt['pochgod'] )." , 
                    pochhv = ".( (int)$dt['pochhv'] )." , 
                    kinrik =".( (int)$dt['kinrik'] )." , 
                    kinmis = ".( (int)$dt['kinmis'] )." , 
                    kintyzh = ".( (int)$dt['kintyzh'] )." , 
                    kinday = ".( (int)$dt['kinday'] )." , 
                    kingod = ".( (int)$dt['kingod'] )." , 
                    kinhv = ".( (int)$dt['kinhv'] )."
                    WHERE
                       id = ".( (int)$id )."
                       AND site_id = $site_id 
                       AND calendar_id = $event_id
                    ";
            // prn($query);
            \e::db_execute($query);
        }
        // 
        // --------------- update dates - end ----------------------------------
        // 
        // save new categories
        if (isset($input_vars['event_category']) && is_array($input_vars['event_category'])) {
            $query = "DELETE FROM {$table_prefix}calendar_category WHERE event_id=$event_id";
            \e::db_execute($query);
            $query = Array();
            foreach ($input_vars['event_category'] as $cat) {
                $cat = (int) $cat;
                if ($cat <= 0) {
                    continue;
                }
                $query[] = "($event_id,$cat)";
            }
            if (count($query) > 0) {
                $query = "INSERT INTO {$table_prefix}calendar_category(event_id,category_id) VALUES " . join(',', $query);
                \e::db_execute($query);
            }
        }
        // clear cache 
        $query = "DELETE FROM {$table_prefix}calendar_cache WHERE uid BETWEEN {$site_id}000000 AND {$site_id}999999";
        \e::db_execute($query);

        
        event_recache_days($event_id);
        
        header("Location:index.php?action=calendar/edit&site_id={$site_id}&event_id={$event_id}");
        exit();
    }
}










if (isset($input_vars['upd'])) {

    $this_event_info =\e::db_getonerow("SELECT * FROM {$table_prefix}calendar WHERE id = '$new'");
    while ($row = mysql_fetch_array($result)) {
        
    }
}


// --------------------- draw - begin ------------------------------------------
$input_vars['page_title'] = $input_vars['page_header'] = text('Calendar_event_edit') . $event_id;

$calendar_dni = calendar_dni();
$calendar_misyaci = calendar_misyaci();
$calendar_dnityzhnya = calendar_dnityzhnya();
$calendar_god = calendar_hours();
$calendar_hv = calendar_minutes();

# ------------------------ list of categories - begin -------------------------
$query = "SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
$tmp = \e::db_getrows($query);
$list_of_categories = Array();
foreach ($tmp as $tm) {
    $list_of_categories[$tm['category_id']] = str_repeat(' + ', $tm['deep']) . get_langstring($tm['category_title']);
}
unset($tmp, $tm);
//prn($list_of_categories);

$event_categories = \e::db_getrows("SELECT category_id FROM {$table_prefix}calendar_category WHERE event_id={$event_id}");
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
<style>
<!--
.fbl{
   display:inline-block;
   /*border-bottom:1px dotted gray;*/
   margin-bottom:3pt;
   margin-right:10px;
}
.lab{
   /*width:100px;*/
   display:inline-block;
}
.num{
   width:50px;
}
 -->
</style>

    <div><a href=\"index.php?action=calendar/list&amp;site_id={$site_id}\">".text('Calendar_manage')."</a> / {$input_vars['page_title']}</div>
    <form method=post action=index.php?site_id={$site_id}&action=calendar/edit&event_id={$event_id}>
          <input type=hidden name='upd' value='1'>
          <p>" . text('Calendar_event_title') . "<br />
          <INPUT type=text name=nazva1 value=\"" . checkStr($this_event_info['nazva']) . "\" SIZE=50></p>

          <p>" . text('Calendar_event_image') . "<br />
          <INPUT type=text name=kartynka1 value=\"{$this_event_info['kartynka']}\"  SIZE=50></p>

          <p>" . text('Calendar_event_URL') . "<br />
          <INPUT type=text name=adresa1  value=\"{$this_event_info['adresa']}\"  SIZE=50></p>

          <p>" . text('Calendar_event_is_visible') . "<br />
          <SELECT  NAME=vis1>" . draw_options($this_event_info['vis'], Array(1 => text('positive_answer'), 0 => text('negative_answer'))) . "</SELECT></p>


<h3>" . text('Calendar_event_dates') . "</h3>
<style type=\"text/css\">
.deleButtock{
   text-decoration:none;
   display:inline-block;
   padding:1px 5px;
   background-color:orange;
   color:white;
   font-size:15pt;
}
</style>
<script type=\"application/javascript\">
function delDate(domId){
   $('#'+domId).remove();
}
</script>
";


foreach ($this_event_info['dates'] as $dt) {
    $input_vars['page_content'].= "
    <div id=date{$dt['id']}>
    <span class=fbl>
    <b>" . text('Calendar_event_start') . ": </b><br>
    <span class=fbl><span class=lab title='" . text('Calendar_event_year') . "'>" . text('Year') . "  </span><br><input type=text name='dt[{$dt['id']}][pochrik]' class=num value=\"{$dt['pochrik']}\"></span>
    <span class=fbl><span class=lab>" . text('Month') . " </span><br><select name='dt[{$dt['id']}][pochmis]' class=num><option value=''></option>" . draw_options($dt['pochmis'], $calendar_misyaci) . "</select></span>
    <span class=fbl><span class=lab>" . text('Day') . "   </span><br><select name='dt[{$dt['id']}][pochday]' class=num><option value=''></option>" . draw_options($dt['pochday'], $calendar_dni) . "</select></span>
    <span class=fbl><span class=lab>" . text('weekday') . "  </span><br><select name='dt[{$dt['id']}][pochtyzh]' class=num><option value=''></option>" . draw_options($dt['pochtyzh'], $calendar_dnityzhnya) . "</select></span>
    <span class=fbl><span class=lab>" . text('hour') . " </span><br><select name='dt[{$dt['id']}][pochgod]' class=num><option value=''></option>" . draw_options($dt['pochgod'], $calendar_god) . "</select></span>
    <span class=fbl><span class=lab>" . text('minute') . " </span><br><select name='dt[{$dt['id']}][pochhv]' class=num><option value=''></option>" . draw_options($dt['pochhv'], $calendar_hv) . "</select></span>
    </span>

    <span class=fbl>
    <b>" . text('Calendar_event_finish') . " : </b><br>
    <span class=fbl><span class=lab title='" . text('Calendar_event_year') . "'>" . text('Year') . "  </span><br><input type=text name='dt[{$dt['id']}][kinrik]' class=num value=\"{$dt['kinrik']}\"></span>
    <span class=fbl><span class=lab>" . text('Month') . " </span><br><select name='dt[{$dt['id']}][kinmis]' class=num><option value=''></option>" . draw_options($dt['kinmis'], $calendar_misyaci) . "</select></span>
    <span class=fbl><span class=lab>" . text('Day') . "   </span><br><select name='dt[{$dt['id']}][kinday]' class=num><option value=''></option>" . draw_options($dt['kinday'], $calendar_dni) . "</select></span>
    <span class=fbl><span class=lab>" . text('weekday') . "  </span><br><select name='dt[{$dt['id']}][kintyzh]' class=num><option value=''></option>" . draw_options($dt['kintyzh'], $calendar_dnityzhnya) . "</select></span>
    <span class=fbl><span class=lab>" . text('hour') . " </span><br><select name='dt[{$dt['id']}][kingod]' class=num><option value=''></option>" . draw_options($dt['kingod'], $calendar_god) . "</select></span>
    <span class=fbl><span class=lab>" . text('minute') . " </span><br><select name='dt[{$dt['id']}][kinhv]' class=num><option value=''></option>" . draw_options($dt['kinhv'], $calendar_hv) . "</select></span>
    </span>
    <a href='javascript:void(delDate(\"date{$dt['id']}\"))' class='deleButtock'>&times;</a>
    </div>

    ";
}

$input_vars['page_content'].= "

<div>
    <span class=fbl>
    <b>" . text('Calendar_event_start') . ": </b><br>
    <span class=fbl><span class=lab title='" . text('Calendar_event_year') . "'>" . text('Year') . "  </span><br><input type=text name='dt[0][pochrik]' class=num value=\"{$new_pochrik}\"></span>
    <span class=fbl><span class=lab>" . text('Month') . " </span><br><select name='dt[0][pochmis]' class=num><option value=''></option>" . draw_options($new_pochmis, $calendar_misyaci) . "</select></span>
    <span class=fbl><span class=lab>" . text('Day') . "   </span><br><select name='dt[0][pochday]' class=num><option value=''></option>" . draw_options($new_pochday, $calendar_dni) . "</select></span>
    <span class=fbl><span class=lab>" . text('weekday') . "  </span><br><select name='dt[0][pochtyzh]' class=num><option value=''></option>" . draw_options($new_pochtyzh, $calendar_dnityzhnya) . "</select></span>
    <span class=fbl><span class=lab>" . text('hour') . " </span><br><select name='dt[0][pochgod]' class=num><option value=''></option>" . draw_options($new_pochgod, $calendar_god) . "</select></span>
    <span class=fbl><span class=lab>" . text('minute') . " </span><br><select name='dt[0][pochhv]' class=num><option value=''></option>" . draw_options($new_pochhv, $calendar_hv) . "</select></span>
    </span>

    <span class=fbl>
    <b>" . text('Calendar_event_finish') . " : </b><br>
    <span class=fbl><span class=lab title='" . text('Calendar_event_year') . "'>" . text('Year') . "  </span><br><input type=text name='dt[0][kinrik]' class=num value=\"{$new_kinrik}\"></span>
    <span class=fbl><span class=lab>" . text('Month') . " </span><br><select name='dt[0][kinmis]' class=num><option value=''></option>" . draw_options($new_kinmis, $calendar_misyaci) . "</select></span>
    <span class=fbl><span class=lab>" . text('Day') . "   </span><br><select name='dt[0][kinday]' class=num><option value=''></option>" . draw_options($new_kinday, $calendar_dni) . "</select></span>
    <span class=fbl><span class=lab>" . text('weekday') . "  </span><br><select name='dt[0][kintyzh]' class=num><option value=''></option>" . draw_options($new_kintyzh, $calendar_dnityzhnya) . "</select></span>
    <span class=fbl><span class=lab>" . text('hour') . " </span><br><select name='dt[0][kingod]' class=num><option value=''></option>" . draw_options($new_kingod, $calendar_god) . "</select></span>
    <span class=fbl><span class=lab>" . text('minute') . " </span><br><select name='dt[0][kinhv]' class=num><option value=''></option>" . draw_options($new_kinhv, $calendar_hv) . "</select></span>
    </span><br>
</div>
    ";


$input_vars['page_content'].= "

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
//  $input_vars['page_menu']['category']=Array('title'=>"���������",'items'=>Array());
//  $input_vars['page_menu']['category']['items']=menu_category($this_category->info);
//  //prn($input_vars['page_menu']['category']);
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);



<?php


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

run('calendar/functions');

function toint($value) {
    return intval($value);
}

// --------------------- delete - begin ----------------------------------------
if (isset($input_vars['event_delete']) && isset($input_vars['event'])) {
    $ids = join(',', array_map('toint', $input_vars['event']));
    \e::db_execute("DELETE FROM {$table_prefix}calendar WHERE id in($ids)");
    \e::db_execute("DELETE FROM {$table_prefix}calendar_category WHERE event_id in($ids)");
    \e::db_execute("DELETE FROM {$table_prefix}calendar_date WHERE calendar_id in($ids)");
    \e::db_execute("DELETE FROM {$table_prefix}calendar_cache WHERE uid between {$site_id}000000 AND {$site_id}999990");
    \e::db_execute("DELETE FROM {$table_prefix}calendar_days_cache WHERE calendar_id in($ids)");
}
// --------------------- delete - end ------------------------------------------
#---------------------------- hide - begin -------------------------------------
if (isset($input_vars['event_hide']) && isset($input_vars['event'])) {
    $ids = join(',', array_map('toint', $input_vars['event']));
    \e::db_execute("UPDATE {$table_prefix}calendar SET vis=0 WHERE id in($ids) AND site_id={$site_id}");
    \e::db_execute("DELETE FROM {$table_prefix}calendar_cache WHERE uid between {$site_id}000000 AND {$site_id}999990");
}
#---------------------------- hide - end ---------------------------------------
#---------------------------- show - begin -------------------------------------
if (isset($input_vars['event_show']) && isset($input_vars['event'])) {
    $ids = join(',', array_map('toint', $input_vars['event']));
    \e::db_execute("UPDATE {$table_prefix}calendar SET vis=1 WHERE id in($ids) AND site_id={$site_id}");
    \e::db_execute("DELETE FROM {$table_prefix}calendar_cache WHERE uid between {$site_id}000000 AND {$site_id}999990");
}
#---------------------------- show - end ---------------------------------------
// ------------------ process search form parameters - begin -------------------
$where = Array();

$calendar_misyaci = calendar_misyaci();

$calendar_dni = calendar_dni();
$calendar_dnityzhnya = calendar_dnityzhnya();

$calendar_god = calendar_hours();
$calendar_hv = calendar_minutes();

// event id
if (isset($input_vars['filter_id'])) {
    $input_vars['filter_id'] = (int) $input_vars['filter_id'];
    if ($input_vars['filter_id'] > 0) {
        $where[] = " id={$input_vars['filter_id']} ";
    } else {
        $input_vars['filter_id'] = '';
    }
} else {
    $input_vars['filter_id'] = '';
}

// event title
if (isset($input_vars['filter_title'])) {
    $input_vars['filter_title'] = trim($input_vars['filter_title']);
    if (strlen($input_vars['filter_title']) > 0) {
        $where[] = " locate('" . \e::db_escape($input_vars['filter_title']) . "',nazva)>0 ";
    }
} else {
    $input_vars['filter_title'] = '';
}




// ----------------- date pattern filter - begin -------------------------------
// 
$whereDate=Array();

// filter_start_year
if (isset($input_vars['filter_start_year'])) {
    $input_vars['filter_start_year'] = (int) $input_vars['filter_start_year'];
    if ($input_vars['filter_start_year'] > 0) {
        $whereDate[] = " pochrik={$input_vars['filter_start_year']} ";
    } else {
        $input_vars['filter_start_year'] = '';
    }
} else {
    $input_vars['filter_start_year'] = '';
}

// filter_start_month
if (isset($input_vars['filter_start_month'])) {
    if (isset($calendar_misyaci[$input_vars['filter_start_month']])) {
        $whereDate[] = " pochmis={$input_vars['filter_start_month']} ";
    } else {
        $input_vars['filter_start_month'] = '';
    }
} else {
    $input_vars['filter_start_month'] = '';
}



// filter_start_day
if (isset($input_vars['filter_start_day'])) {
    if (isset($calendar_dni[$input_vars['filter_start_day']])) {
        $whereDate[] = " pochday={$input_vars['filter_start_day']} ";
    } else {
        $input_vars['filter_start_day'] = '';
    }
} else {
    $input_vars['filter_start_day'] = '';
}

// filter_start_weekday
if (isset($input_vars['filter_start_weekday'])) {
    if (isset($calendar_dnityzhnya[$input_vars['filter_start_weekday']])) {
        $whereDate[] = " pochtyzh={$input_vars['filter_start_weekday']} ";
    } else {
        $input_vars['filter_start_weekday'] = '';
    }
} else {
    $input_vars['filter_start_weekday'] = '';
}

// filter_start_hour
if (isset($input_vars['filter_start_hour'])) {
    if (isset($calendar_god[$input_vars['filter_start_hour']])) {
        $whereDate[] = " pochgod={$input_vars['filter_start_hour']} ";
    } else {
        $input_vars['filter_start_hour'] = '';
    }
} else {
    $input_vars['filter_start_hour'] = '';
}

// filter_start_minute
if (isset($input_vars['filter_start_minute'])) {
    if (isset($calendar_hv[$input_vars['filter_start_minute']])) {
        $whereDate[] = " pochhv={$input_vars['filter_start_minute']} ";
    } else {
        $input_vars['filter_start_minute'] = '';
    }
} else {
    $input_vars['filter_start_minute'] = '';
}

// filter_finish_year
if (isset($input_vars['filter_finish_year'])) {
    $input_vars['filter_finish_year'] = (int) $input_vars['filter_finish_year'];
    if ($input_vars['filter_finish_year'] > 0) {
        $whereDate[] = " kinrik={$input_vars['filter_finish_year']} ";
    } else {
        $input_vars['filter_finish_year'] = '';
    }
} else {
    $input_vars['filter_finish_year'] = '';
}

// filter_finish_month
if (isset($input_vars['filter_finish_month'])) {
    if (isset($calendar_misyaci[$input_vars['filter_finish_month']])) {
        $whereDate[] = " kinmis={$input_vars['filter_finish_month']} ";
    } else {
        $input_vars['filter_finish_month'] = '';
    }
} else {
    $input_vars['filter_finish_month'] = '';
}

// filter_finish_day
if (isset($input_vars['filter_finish_day'])) {
    if (isset($calendar_dni[$input_vars['filter_finish_day']])) {
        $whereDate[] = " kinday={$input_vars['filter_finish_day']} ";
    } else {
        $input_vars['filter_finish_day'] = '';
    }
} else {
    $input_vars['filter_finish_day'] = '';
}

// filter_finish_weekday
if (isset($input_vars['filter_finish_weekday'])) {
    if (isset($calendar_dnityzhnya[$input_vars['filter_finish_weekday']])) {
        $whereDate[] = " kintyzh={$input_vars['filter_finish_weekday']} ";
    } else {
        $input_vars['filter_finish_weekday'] = '';
    }
} else {
    $input_vars['filter_finish_weekday'] = '';
}
//<span class=fbl><span class=lab>hour </span><input type=text name=filter_finish_hour class=num></span>
// filter_finish_hour
if (isset($input_vars['filter_finish_hour'])) {
    if (isset($calendar_god[$input_vars['filter_finish_hour']])) {
        $whereDate[] = " kingod={$input_vars['filter_finish_hour']} ";
    } else {
        $input_vars['filter_finish_hour'] = '';
    }
} else {
    $input_vars['filter_finish_hour'] = '';
}

// filter_finish_minute
if (isset($input_vars['filter_finish_minute'])) {
    if (isset($calendar_hv[$input_vars['filter_finish_minute']])) {
        $whereDate[] = " kinhv={$input_vars['filter_finish_minute']} ";
    } else {
        $input_vars['filter_finish_minute'] = '';
    }
} else {
    $input_vars['filter_finish_minute'] = '';
}

if(count($whereDate)>0){
    $where[]=" id in(select calendar_id FROM {$table_prefix}calendar_date WHERE ".join(" AND ",$whereDate).") ";
}
// ----------------- date pattern filter - end ---------------------------------



$where = join(' AND ', $where);
if (strlen($where) > 0) {
    $where = ' AND ' . $where;
}
// prn($where);
// ------------------ process search form parameters - end ---------------------

if (isset($input_vars['start'])) {
    $start = abs(round(1 * $input_vars['start']));
} else {
    $start = 0;
}
$num =\e::db_getonerow("SELECT count(*) as n FROM {$table_prefix}calendar WHERE site_id=$site_id $where");
$num = $num['n'];

$pages = '';
$page_url_prefix = "index.php?" . preg_query_string("/^start|^event/");
for ($i = 0; $i < $num; $i = $i + 100) {
    if($i==$start){
        $pages.=" &nbsp; <a class=currentPage href={$page_url_prefix}&start={$i}>" . (1 + $i / 100) . "</a> &nbsp; ";
    }else{
        $pages.=" &nbsp; <a href={$page_url_prefix}&start={$i}>" . (1 + $i / 100) . "</a> &nbsp; ";
    }
    
}


$rows = \e::db_getrows("SELECT * FROM {$table_prefix}calendar WHERE site_id='$site_id' $where ORDER BY `id` ASC LIMIT $start, 100");


$input_vars['page_content'] = "
<style>
<!--
 .menu_block{
   position:absolute;
   border:1px solid black;
   padding:13pt;
   background-color:#e0e0e0;
 }
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
.currentPage{
  display:inline-block;
  padding:1px 6px;
  color:white;
  background-color:#284351;
}
 -->
</style>
<p><a href=\"index.php?action=calendar/add&site_id={$site_id}\">".text('Calendar_add_event')."</a></p><br>
<form method=post action=index.php>
<!-- search form - start  -->
<span class=fbl>
<b>".text('Calendar_event_start').": </b><br>
<span class=fbl><span class=lab>".text('Year')."  </span><br><input type=text name=filter_start_year class=num value=\"{$input_vars['filter_start_year']}\"></span>
<span class=fbl><span class=lab>".text('Month')." </span><br><select name=filter_start_month class=num><option value=''></option>" . draw_options($input_vars['filter_start_month'], $calendar_misyaci) . "</select></span>
<span class=fbl><span class=lab>".text('Day')."   </span><br><select name=filter_start_day class=num><option value=''></option>" . draw_options($input_vars['filter_start_day'], $calendar_dni) . "</select></span>
<span class=fbl><span class=lab>".text('weekday')."  </span><br><select name=filter_start_weekday class=num><option value=''></option>" . draw_options($input_vars['filter_start_weekday'], $calendar_dnityzhnya) . "</select></span>
<span class=fbl><span class=lab>".text('hour')." </span><br><select name=filter_start_hour class=num><option value=''></option>" . draw_options($input_vars['filter_start_hour'], $calendar_god) . "</select></span>
<span class=fbl><span class=lab>".text('minute')." </span><br><select name=filter_start_minute class=num><option value=''></option>" . draw_options($input_vars['filter_start_minute'], $calendar_hv) . "</select></span>
</span>

<span class=fbl>
<b>".text('Calendar_event_finish')." : </b><br>
<span class=fbl><span class=lab>".text('Year')."    </span><br><input type=text name=filter_finish_year class=num value=\"{$input_vars['filter_finish_year']}\"></span>
<span class=fbl><span class=lab>".text('Month')." </span><br><select  name=filter_finish_month class=num><option value=''></option>" . draw_options($input_vars['filter_finish_month'], $calendar_misyaci) . "</select></span>
<span class=fbl><span class=lab>".text('Day')." </span><br><select name=filter_finish_day class=num><option value=''></option>" . draw_options($input_vars['filter_finish_day'], $calendar_dni) . "</select></span>
<span class=fbl><span class=lab>".text('weekday')." </span><br><select name=filter_finish_weekday class=num><option value=''></option>" . draw_options($input_vars['filter_finish_weekday'], $calendar_dnityzhnya) . "</select></span>
<span class=fbl><span class=lab>".text('hour')." </span><br><select name=filter_finish_hour class=num><option value=''></option>" . draw_options($input_vars['filter_finish_hour'], $calendar_god) . "</select></span>
<span class=fbl><span class=lab>".text('minute')." </span><br><select name=filter_finish_minute class=num><option value=''></option>" . draw_options($input_vars['filter_finish_minute'], $calendar_hv) . "</select></span>
</span><br>
<span class=fbl style='width:100%;'>
<span class=fbl><span class=lab>id  </span><br><input type=text name=filter_id class=num value=\"{$input_vars['filter_id']}\"></span>
<span class=fbl style='width:414px'><span class=lab>".  text("Calendar_event_title")." </span><br><input type=text name=filter_title class=num style='width:314px' value=\"" . checkStr($input_vars['filter_title']) . "\"></span>
<span class=fbl style='float:right;'><span class=lab>&nbsp;</span><br><input type=submit value=\"{$text['Search']}\"></span>
</span>
<!-- search form - finish -->
" . preg_hidden_form_elements("/^event|^start|^filter/") . "
    

<br/><br/>
<p>".text('Pages').": {$pages}</p>
<table>
<tr>
   <th></th>
   <th>#</th>
   <th style='min-width:70%;'>".text('Calendar_event')."</th>
   <th><nobr>".text('Calendar_event_start')." - ".text('Calendar_event_finish')."</nobr></th>
</tr>
";
if ($rows) {
    
    
    // get dates
    $ids=Array();
    foreach($rows as $row){
        $ids[]=$row['id'];
    }
    if(count($ids)>0){
        $query="SELECT * FROM {$table_prefix}calendar_date WHERE calendar_id IN (".join(',',$ids).") ORDER BY calendar_id";
        
        $tmp=  \e::db_getrows($query);
        $dates=Array();
        foreach($tmp as $tm){
            if(!isset($dates[$tm['calendar_id']])){
                $dates[$tm['calendar_id']]=Array();
            }
            $dates[$tm['calendar_id']][]=
                  ($tm['pochrik'] > 0 ? substr("0000" . $tm['pochrik'], -4) : '****')
                . "-"
                . ($tm['pochmis'] > 0 ? substr("0000" . $tm['pochmis'], -2) : '**')
                . "-"
                . ($tm['pochday'] > 0 ? substr("0000" . $tm['pochday'], -2) : '**')
                . " "
                . $calendar_dnityzhnya[$tm['pochtyzh']]
                . " "
                . ($tm['pochgod'] >= 0 ? substr("0000" . $tm['pochgod'], -2) : '**')
                . ":"
                . ($tm['pochhv'] >= 0 ? substr("0000" . $tm['pochhv'], -2) : '**')
                . " --- "
                . ($tm['kinrik'] > 0 ? substr("0000" . $tm['kinrik'], -4) : '****' )
                . "-"
                . ($tm['kinmis'] > 0 ? substr("0000" . $tm['kinmis'], -2) : '**')
                . "-"
                . ($tm['kinday'] > 0 ? substr("0000" . $tm['kinday'], -2) : '**')
                . " " 
                . $calendar_dnityzhnya[$tm['kintyzh']] 
                . "  "
                . ($tm['kingod'] >= 0 ? substr("0000" . $tm['kingod'], -2) : '**')
                . ":"
                . ($tm['kinhv'] >= 0 ? substr("0000" . $tm['kinhv'], -2) : '**' )
                ;
        }
    }
    
    
    
    $calendar_dni = calendar_dni();
    $calendar_misyaci = calendar_misyaci();
    $calendar_dnityzhnya = calendar_dnityzhnya();

    foreach ($rows as $row) {
        $context_menu = menu_event($row);
        //prn($context_menu);
        $input_vars['page_content'] .= "
        <tr>
            <td><nobr>
              <input type=checkbox name=event[] value={$row['id']}>
              <a href=\"#\" class=context_menu_link onclick=\"change_state('cm{$row['id']}'); return false;\"><img src=img/context_menu.gif border=0 width-25 height=15></a>
              ";

        if ($row['vis'] == 1) {
            $input_vars['page_content'] .= "<a href=index.php?" . preg_query_string("/^event/") . "&event[]={$row['id']}&event_hide=yes><img src=./img/icon_view.gif border=0></a>";
        } else {
            $input_vars['page_content'] .= "<a href=index.php?" . preg_query_string("/^event/") . "&event[]={$row['id']}&event_show=yes><img src=./img/okominus.gif border=0></a>";
        }

        $input_vars['page_content'] .= "
              </nobr>
              <div id=\"cm{$row['id']}\" class=menu_block style='display:none;'>";

        foreach ($context_menu as $cm) {
            if ($cm['URL'] != '') {
                $input_vars['page_content'] .="<nobr><a href=\"{$cm['URL']}\" {$cm['attributes']}>{$cm['innerHTML']}</a></nobr><br> ";
            } else {
                $input_vars['page_content'] .="<nobr><b>{$cm['innerHTML']}</b></nobr><br>";
            }
        }

        $input_vars['page_content'] .= "
             </div>
             </td>
             <td valign=top>{$row['id']}</td>
             <td valign=top>
             {$row['nazva']}<br>
             {$row['adresa']}<br>
             {$row['kartynka']}
             </td>
             <td style='white-space:nowrap;' valign=top><pre>".
               (isset($dates[$row['id']])?join('<br>',$dates[$row['id']]):'')
             . "</pre></td>
             </tr>";
    }
}else {
    $input_vars['page_content'] .= "<tr><td class=two colspan=5><p class=error>".text('Calendar_events_not_found')."</p></td></tr>";
}
$input_vars['page_content'] .= "
<tr>
  <td align=right colspan=4 style='border:none;'><nobr>
    <input type=submit value=\"".text('Calendar_event_publish')."\"  name=\"event_show\">
    <input type=submit value=\"".text('Calendar_event_unpublish')."\" name=\"event_hide\">
    <input type=submit value=\"".text('Calendar_event_delete')."\"  name=\"event_delete\">
  </nobr></td>
</tr>
</table></form>
                                 <br><br>
                                 <p>".text('Pages').": {$pages}</p>";








$input_vars['page_title'] =
        $input_vars['page_header'] = text("Calendar_event_list");








# ------------------------------------------------------------------------------
# site context menu
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
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
    db_execute("DELETE FROM {$table_prefix}calendar WHERE id in($ids)");
    db_execute("DELETE FROM {$table_prefix}calendar_category WHERE event_id in($ids)");
    // echo "<p>��������: {$delit}</p>";
}
// --------------------- delete - end ------------------------------------------
#---------------------------- hide - begin -------------------------------------
if (isset($input_vars['event_hide']) && isset($input_vars['event'])) {
    $ids = join(',', array_map('toint', $input_vars['event']));
    Execute($db, "UPDATE {$table_prefix}calendar SET vis=0 WHERE id in($ids) AND site_id={$site_id}");
    // echo "<p>��������: {$delit}</p>";
}
#---------------------------- hide - end ---------------------------------------
#---------------------------- show - begin -------------------------------------
if (isset($input_vars['event_show']) && isset($input_vars['event'])) {
    $ids = join(',', array_map('toint', $input_vars['event']));
    Execute($db, "UPDATE {$table_prefix}calendar SET vis=1 WHERE id in($ids) AND site_id={$site_id}");
    // echo "<p>��������: {$delit}</p>";
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
        $where[] = " locate('" . DbStr($input_vars['filter_title']) . "',nazva)>0 ";
    }
} else {
    $input_vars['filter_title'] = '';
}

// filter_start_year
if (isset($input_vars['filter_start_year'])) {
    $input_vars['filter_start_year'] = (int) $input_vars['filter_start_year'];
    if ($input_vars['filter_start_year'] > 0) {
        $where[] = " pochrik={$input_vars['filter_start_year']} ";
    } else {
        $input_vars['filter_start_year'] = '';
    }
} else {
    $input_vars['filter_start_year'] = '';
}

// filter_start_month
if (isset($input_vars['filter_start_month'])) {
    if (isset($calendar_misyaci[$input_vars['filter_start_month']])) {
        $where[] = " pochmis={$input_vars['filter_start_month']} ";
    } else {
        $input_vars['filter_start_month'] = '';
    }
} else {
    $input_vars['filter_start_month'] = '';
}



// filter_start_day
if (isset($input_vars['filter_start_day'])) {
    if (isset($calendar_dni[$input_vars['filter_start_day']])) {
        $where[] = " pochday={$input_vars['filter_start_day']} ";
    } else {
        $input_vars['filter_start_day'] = '';
    }
} else {
    $input_vars['filter_start_day'] = '';
}

// filter_start_weekday
if (isset($input_vars['filter_start_weekday'])) {
    if (isset($calendar_dnityzhnya[$input_vars['filter_start_weekday']])) {
        $where[] = " pochtyzh={$input_vars['filter_start_weekday']} ";
    } else {
        $input_vars['filter_start_weekday'] = '';
    }
} else {
    $input_vars['filter_start_weekday'] = '';
}

// filter_start_hour
if (isset($input_vars['filter_start_hour'])) {
    if (isset($calendar_god[$input_vars['filter_start_hour']])) {
        $where[] = " pochgod={$input_vars['filter_start_hour']} ";
    } else {
        $input_vars['filter_start_hour'] = '';
    }
} else {
    $input_vars['filter_start_hour'] = '';
}

// filter_start_minute
if (isset($input_vars['filter_start_minute'])) {
    if (isset($calendar_hv[$input_vars['filter_start_minute']])) {
        $where[] = " pochhv={$input_vars['filter_start_minute']} ";
    } else {
        $input_vars['filter_start_minute'] = '';
    }
} else {
    $input_vars['filter_start_minute'] = '';
}
//<b>Finish : </b><br>
//
// filter_finish_year
if (isset($input_vars['filter_finish_year'])) {
    $input_vars['filter_finish_year'] = (int) $input_vars['filter_finish_year'];
    if ($input_vars['filter_finish_year'] > 0) {
        $where[] = " kinrik={$input_vars['filter_finish_year']} ";
    } else {
        $input_vars['filter_finish_year'] = '';
    }
} else {
    $input_vars['filter_finish_year'] = '';
}

// filter_finish_month
if (isset($input_vars['filter_finish_month'])) {
    if (isset($calendar_misyaci[$input_vars['filter_finish_month']])) {
        $where[] = " kinmis={$input_vars['filter_finish_month']} ";
    } else {
        $input_vars['filter_finish_month'] = '';
    }
} else {
    $input_vars['filter_finish_month'] = '';
}

// filter_finish_day
if (isset($input_vars['filter_finish_day'])) {
    if (isset($calendar_dni[$input_vars['filter_finish_day']])) {
        $where[] = " kinday={$input_vars['filter_finish_day']} ";
    } else {
        $input_vars['filter_finish_day'] = '';
    }
} else {
    $input_vars['filter_finish_day'] = '';
}

// filter_finish_weekday
if (isset($input_vars['filter_finish_weekday'])) {
    if (isset($calendar_dnityzhnya[$input_vars['filter_finish_weekday']])) {
        $where[] = " kintyzh={$input_vars['filter_finish_weekday']} ";
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
        $where[] = " kingod={$input_vars['filter_finish_hour']} ";
    } else {
        $input_vars['filter_finish_hour'] = '';
    }
} else {
    $input_vars['filter_finish_hour'] = '';
}

// filter_finish_minute
if (isset($input_vars['filter_finish_minute'])) {
    if (isset($calendar_hv[$input_vars['filter_finish_minute']])) {
        $where[] = " kinhv={$input_vars['filter_finish_minute']} ";
    } else {
        $input_vars['filter_finish_minute'] = '';
    }
} else {
    $input_vars['filter_finish_minute'] = '';
}

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
$num = GetOneRow(Execute($db, "SELECT count(*) as n FROM {$table_prefix}calendar WHERE site_id=$site_id $where"));
$num = $num['n'];

$pages = '';
$page_url_prefix = "index.php?" . preg_query_string("/^start|^event/");
for ($i = 0; $i < $num; $i = $i + 10) {
    $pages.=" &nbsp; <a href={$page_url_prefix}&start={$i}>" . (1 + $i / 10) . "</a> &nbsp; ";
}


$rows = GetRows(Execute($db, "SELECT * FROM {$table_prefix}calendar WHERE site_id='$site_id' $where ORDER BY `id` ASC LIMIT $start, 10"));


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
   width:200px;
   display:inline-block;
   border-bottom:1px dotted gray;
   margin-bottom:3pt;
   margin-right:10px;
}
.lab{
   width:100px;
   display:inline-block;
}
.num{
   width:100px;
}
 -->
</style>
<p><a href=\"index.php?action=calendar/add&site_id={$site_id}\">".text('Calendar_add_event')."</a></p><br>
<form method=post action=index.php>
<!-- search form - start  -->
<span class=fbl><span class=lab>id  </span><input type=text name=filter_id class=num value=\"{$input_vars['filter_id']}\"></span>
<span class=fbl style='width:414px'><span class=lab>title </span><input type=text name=filter_title class=num style='width:314px' value=\"" . checkStr($input_vars['filter_title']) . "\"></span>
<br>
<b>".text('Calendar_event_start').": </b><br>
<span class=fbl><span class=lab>".text('Year')."  </span><input type=text name=filter_start_year class=num value=\"{$input_vars['filter_start_year']}\"></span>
<span class=fbl><span class=lab>".text('Month')." </span><select name=filter_start_month class=num><option value=''></option>" . draw_options($input_vars['filter_start_month'], $calendar_misyaci) . "</select></span>
<span class=fbl><span class=lab>".text('Day')."   </span><select name=filter_start_day class=num><option value=''></option>" . draw_options($input_vars['filter_start_day'], $calendar_dni) . "</select></span>
<br>
<span class=fbl><span class=lab>".text('weekday')."  </span><select name=filter_start_weekday class=num><option value=''></option>" . draw_options($input_vars['filter_start_weekday'], $calendar_dnityzhnya) . "</select></span>
<span class=fbl><span class=lab>".text('hour')." </span><select name=filter_start_hour class=num><option value=''></option>" . draw_options($input_vars['filter_start_hour'], $calendar_god) . "</select></span>
<span class=fbl><span class=lab>".text('minute')." </span><select name=filter_start_minute class=num><option value=''></option>" . draw_options($input_vars['filter_start_minute'], $calendar_hv) . "</select></span>
<br>
<b>".text('Calendar_event_finish')." : </b><br>
<span class=fbl><span class=lab>".text('Year')."    </span><input type=text name=filter_finish_year class=num value=\"{$input_vars['filter_finish_year']}\"></span>
<span class=fbl><span class=lab>".text('Month')." </span><select  name=filter_finish_month class=num><option value=''></option>" . draw_options($input_vars['filter_finish_month'], $calendar_misyaci) . "</select></span>
<span class=fbl><span class=lab>".text('Day')." </span><select name=filter_finish_day class=num><option value=''></option>" . draw_options($input_vars['filter_finish_day'], $calendar_dni) . "</select></span>
<br>
<span class=fbl><span class=lab>".text('weekday')." </span><select name=filter_finish_weekday class=num><option value=''></option>" . draw_options($input_vars['filter_finish_weekday'], $calendar_dnityzhnya) . "</select></span>
<span class=fbl><span class=lab>".text('hour')." </span><select name=filter_finish_hour class=num><option value=''></option>" . draw_options($input_vars['filter_finish_hour'], $calendar_god) . "</select></span>
<span class=fbl><span class=lab>".text('minute')." </span><select name=filter_finish_minute class=num><option value=''></option>" . draw_options($input_vars['filter_finish_minute'], $calendar_hv) . "</select></span>
<br>
<span class=fbl style='width:630px;text-align:right;border:none;'><input type=submit value=\"{$text['Search']}\"></span>
<!-- search form - finish -->
" . preg_hidden_form_elements("/^event|^start|^filter/") . "
<table>
<tr>
   <th width=20%></th>
   <th width=10%>#</th>
   <th width=70%>".text('Calendar_event')."</th>
   <th colspan=3>".text('Calendar_event_start')."</th>
   <th colspan=3>".text('Calendar_event_finish')."</th>
</tr>
";
if ($rows) {
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
            if ($cm['URL'] != '')
                $input_vars['page_content'] .="<nobr><a href=\"{$cm['URL']}\" {$cm['attributes']}>{$cm['innerHTML']}</a></nobr><br> ";
            else
                $input_vars['page_content'] .="<nobr><b>{$cm['innerHTML']}</b></nobr><br>";
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
             <td style='white-space:nowrap;' valign=top>"
                . ($row['pochrik'] > 0 ? substr("0000" . $row['pochrik'], -4) : '****')
                . "-"
                . ($row['pochmis'] > 0 ? substr("0000" . $row['pochmis'], -2) : '**')
                . "-"
                . ($row['pochday'] > 0 ? substr("0000" . $row['pochday'], -2) : '**')
                . "</td><td style='white-space:nowrap;' valign=top>"
                . $calendar_dnityzhnya[$row['pochtyzh']]
                . "</td><td style='white-space:nowrap;' valign=top>"
                . ($row['pochgod'] >= 0 ? substr("0000" . $row['pochgod'], -2) : '**')
                . ":"
                . ($row['pochhv'] >= 0 ? substr("0000" . $row['pochhv'], -2) : '**')
                . "</td>
             <td valign=top  style='white-space:nowrap;'>"
                . ($row['kinrik'] > 0 ? substr("0000" . $row['kinrik'], -4) : '****' )
                . "-"
                . ($row['kinmis'] > 0 ? substr("0000" . $row['kinmis'], -2) : '**')
                . "-"
                . ($row['kinday'] > 0 ? substr("0000" . $row['kinday'], -2) : '**') . "</td>
             <td valign=top>" . $calendar_dnityzhnya[$row['kintyzh']] . "</td>
             <td valign=top style='white-space:nowrap;'>"
                . ($row['kingod'] >= 0 ? substr("0000" . $row['kingod'], -2) : '**')
                . ":"
                . ($row['kinhv'] >= 0 ? substr("0000" . $row['kinhv'], -2) : '**' )
                . "</td>
             </tr>";
    }
}else {
    $input_vars['page_content'] .= "<tr><td class=two colspan=5><p class=error>".text('Calendar_events_not_found')."</p></td></tr>";
}
$input_vars['page_content'] .= "
<tr>
  <td align=right colspan=9 style='border:none;'><nobr>
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
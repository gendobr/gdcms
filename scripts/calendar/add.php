<?php
/**
 * - localization-ok
  <h2>Додавання події</h2>
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




// load calendar functions
run('calendar/functions');

$input_vars['page_title'] =
$input_vars['page_header'] = "Нова подія";

if (isset($input_vars['nazva'])) {
    $nazva = $input_vars['nazva'];
    $adresa = $input_vars['adresa'];
    $kartynka = $input_vars['kartynka'];
    $pochrik = $input_vars['pochrik'];
    $pochmis = $input_vars['pochmis'];
    $pochday = $input_vars['pochday'];
    $pochtyzh = $input_vars['pochtyzh'];
    $pochgod = $input_vars['pochgod'];
    $pochhv = $input_vars['pochhv'];
    $kinrik = $input_vars['kinrik'];
    $kinmis = $input_vars['kinmis'];
    $kinday = $input_vars['kinday'];
    $kintyzh = $input_vars['kintyzh'];
    $kingod = $input_vars['kingod'];
    $kinhv = $input_vars['kinhv'];
    $vis = $input_vars['vis'];
    $description = $input_vars['description'];
    if (!$nazva) {
         $input_vars['page_content'] = "<p class=error>".text('ERROR_Event_title_is_not_set')."</p>";
    } elseif (!$pochday && !$pochtyzh) {
         $input_vars['page_content'] = "<p class=error>".text('ERROR_Event_start_date_is_not_set')."</p>";
    } elseif (!$kinday && !$kintyzh) {
         $input_vars['page_content'] = "<p class=error>".text('ERROR_Event_finish_date_is_not_set')."</p>";
    } else {
        $input_vars['page_content'] = "ok!";
        $query = "INSERT INTO {$table_prefix}calendar (site_id, nazva, kartynka, adresa,description,
                      pochrik, kinrik, pochmis, kinmis,
                      pochday, kinday, pochtyzh, kintyzh,
                      pochgod, kingod, pochhv, kinhv, vis)
                  VALUES (" . ( (int) $site_id ) . ",
                         '" . DbStr($nazva) . "',
                         '" . DbStr($kartynka) . "',
                         '" . DbStr($adresa) . "',
                         '" . DbStr($description) . "',
                         " . ( (int) $pochrik) . ",
                         " . ( (int) $kinrik) . ",
                         " . ( (int) $pochmis) . ",
                         " . ( (int) $kinmis) . ",
                         " . ( (int) $pochday) . ",
                         " . ( (int) $kinday) . ",
                         " . ( (int) $pochtyzh) . ",
                         " . ( (int) $kintyzh) . ",
                         " . ( (int) $pochgod) . ",
                         " . ( (int) $kingod) . ",
                         " . ( (int) $pochhv) . ",
                         " . ( (int) $kinhv) . ",
                         " . ( (int) $vis) . ")";

        db_execute($query);
    }
}else{
$calendar_dni     = calendar_dni();
$calendar_misyaci = calendar_misyaci();
$calendar_dnityzhnya = calendar_dnityzhnya();
$calendar_god = calendar_hours();
$calendar_hv = calendar_minutes();
    $input_vars['page_content'] = "
        <form method=post action=index.php>
            <INPUT type=hidden name=site_id value={$site_id}>
            <INPUT type=hidden name=action value='calendar/add'>
            <h3>".text('Calendar_event_properties')."</h3>
                <p>
                ".text('Calendar_event_title')."<br />
                <INPUT type=text name=nazva SIZE=50>
                </p>
                <p>
                ".text('Calendar_event_image')."
                <br />
                <INPUT type=text name=kartynka SIZE=50>
                </p>
                <p>
                ".text('Calendar_event_URL')."<br />
                <INPUT type=text name=adresa SIZE=50>
                </p>
                <p>
                ".text('Calendar_event_is_visible')."<br />
                <SELECT  NAME=vis><OPTION VALUE=\"1\">Так</OPTION><OPTION VALUE=\"0\">Ні</OPTION></SELECT>
                </p>
                   <table>
                      <tr>
                      <td width=50%>
                      <h4>".text('Calendar_event_start_time')."</h4>
                      <p>
                       ".text('Calendar_event_year')."<br />
                       <INPUT type=text name=pochrik value=\"-1\" SIZE=4>
                      </p>
                      <p>
                      ".text('Calendar_event_month')."<br />
                      <SELECT  NAME=pochmis>".draw_options(-1, $calendar_misyaci)."</SELECT>
                      </p>
                      <p>
                         ".text('Calendar_event_month_day')."<br />
                         <SELECT  NAME=pochday>".draw_options(-1, $calendar_dni)."</SELECT>
                      </p>
                      <p>
                         ".text('Calendar_event_week_day')."<br />
                         <SELECT  NAME=pochtyzh>".draw_options(-1, $calendar_dnityzhnya)."</SELECT>
                      </p>
                      <p>
                        ".text('Calendar_event_daytime')."<br />
                       <SELECT  NAME=pochgod>".draw_options(-1, $calendar_god)."</SELECT>:<SELECT  NAME=pochhv>".draw_options(-1, $calendar_hv)."</SELECT></p>

                      </p>
                 </td>
                 <td width=50%>
                  <h4>".text('Calendar_event_finish_time')."</h4>
                    <p>
                      ".text('Calendar_event_year')."<br />
                      <INPUT type=text name=kinrik value=-1 SIZE=4>
                    </p>
                    <p>
                       ".text('Calendar_event_month')."<br />
                       <SELECT  NAME=kinmis>".draw_options(-1, $calendar_misyaci)."</SELECT>
                    </p>
                    <p>
                       ".text('Calendar_event_month_day')."<br />
                       <SELECT  NAME=kinday>".draw_options(-1, $calendar_dni)."</SELECT>
                    </p>
                    <p>
                     ".text('Calendar_event_week_day')."<br />
                     <SELECT  NAME=kintyzh>".draw_options(-1, $calendar_dnityzhnya)."</SELECT>
                    </p>
                    <p>".text('Calendar_event_daytime')."<br />
                    <SELECT  NAME=kingod>".draw_options(-1, $calendar_god)."</SELECT>:<SELECT  NAME=kinhv>".draw_options(-1, $calendar_hv)."</SELECT></p>
            </td></tr></table>

<p>
".text('Calendar_event_description')."<br />
 <textarea name=description rows='10' style='width:100%;'></textarea>
</p>
<input type=submit value=\"".text('Create').">
</form>";
}


# site context menu
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
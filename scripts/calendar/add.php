<?php
/**
 * - localization-ok
  <h2>��������� ��䳿</h2>
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
$input_vars['page_header'] = text('Calendar_add_event');

if (isset($input_vars['nazva'])) {
    $nazva = $input_vars['nazva'];
    $adresa = $input_vars['adresa'];
    $kartynka = $input_vars['kartynka'];
    $vis = $input_vars['vis'];
    $description = $input_vars['description'];
    if (!$nazva) {
         $input_vars['page_content'] = "<p class=error>".text('ERROR_Event_title_is_not_set')."</p>";
    } else {
        $input_vars['page_content'] = "ok!";
        $query = "INSERT INTO <<tp>>calendar (site_id, nazva, kartynka, adresa,description,vis)
                  VALUES (" . ( (int) $site_id ) . ",
                         '" . \e::db_escape($nazva) . "',
                         '" . \e::db_escape($kartynka) . "',
                         '" . \e::db_escape($adresa) . "',
                         '" . \e::db_escape($description) . "',
                         " . ( (int) $vis) . ")";
        \e::db_execute($query);
        $calendar_info =\e::db_getonerow("SELECT * FROM <<tp>>calendar WHERE id=last_insert_id()");
        header("Location: ".site_root_URL."/index.php?action=calendar/edit&site_id={$site_id}&event_id={$calendar_info['id']}");
        exit();
    }
}else{
//$calendar_dni     = calendar_dni();
//$calendar_misyaci = calendar_misyaci();
//$calendar_dnityzhnya = calendar_dnityzhnya();
//$calendar_god = calendar_hours();
//$calendar_hv = calendar_minutes();
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
                <SELECT  NAME=vis><OPTION VALUE=\"1\">".text('positive_answer')."</OPTION><OPTION VALUE=\"0\">".text('negative_answer')."</OPTION></SELECT>
                </p>
                <p>
                ".text('Calendar_event_description')."<br />
                 <textarea name=description rows='10' style='width:100%;'></textarea>
                </p>
                <input type=submit value=\"".text('Create')."\">
        </form>
        ";
}


# site context menu
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
?>
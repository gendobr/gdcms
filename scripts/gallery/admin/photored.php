<?php

$link = $db;
$data = date("Y-m-d H:i");

run('site/menu');

# ------------------- site info - begin ----------------------------------------
if (isset($input_vars['site_id'])) {
    $site = $site_id = checkInt($input_vars['site_id']);
    $this_site_info = get_site_info($site_id);

    if (checkInt($this_site_info['id']) <= 0) {
        $input_vars['page_title'] = $text['Site_not_found'];
        $input_vars['page_header'] = $text['Site_not_found'];
        $input_vars['page_content'] = $text['Site_not_found'];
        return 0;
    }
} else {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
# ------------------- site info - end ------------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------


$vyvid = '';
$lang = $_SESSION['lang'];



# ---------------------------- check image id - begin --------------------------
$row = false;
if (isset($input_vars['new'])) {
    $new = abs((int) $input_vars['new']);
    $row =\e::db_getonerow("SELECT * FROM {$table_prefix}photogalery WHERE id = '$new' AND site=$site_id");
}
if (!$row) {
    $input_vars['page_title'] = $text['Image_not_found'];
    $input_vars['page_header'] = $text['Image_not_found'];
    $input_vars['page_content'] = $text['Image_not_found'];
    return 0;
}
# ---------------------------- check image id - end ----------------------------



$vyvid = '';

# ------------------------- update - begin -------------------------------------
if (isset($input_vars['rozdil1'])) {

    $rozdil1 = \e::db_escape(trim($input_vars['rozdil1']));
    $rozdil21 = \core\fileutils::encode_dir_name(trim($input_vars['rozdil1']));
    $pidpys1 = \e::db_escape($input_vars['pidpys1']);
    $autor1 = \e::db_escape($input_vars['autor1']);
    $rik1 = \e::db_escape($input_vars['rik1']);
    $vis1 = \e::db_escape($input_vars['vis1']);
    $description1=\e::db_escape($input_vars['description1']);
    //$photos1 = $row['photos'];photos = '$photos1'             ,
    $query = "UPDATE {$table_prefix}photogalery
                SET pidpys = '$pidpys1'
                  , rozdil = '$rozdil1'
                  , rozdil2 = '$rozdil21'
                  , autor = '$autor1'
                  , rik = '$rik1'
                  , site = '$site'
                  , vis = '$vis1'
                  , description='$description1'
                WHERE id = '$new'";
    \e::db_execute($query);
    $vyvid = "<b><font color=green>{$text['Gallery_changes_saved']}</font></b>" . $vyvid;

    $row =\e::db_getonerow( "SELECT * FROM {$table_prefix}photogalery WHERE id = '$new' AND site=$site_id" );
}

# ------------------------- update - end ---------------------------------------
#prn($row);
# ----------------------- draw form - begin ------------------------------------
$url_prefix = preg_replace("/\\/+\$/", '', $this_site_info['url']) . '/gallery';
$vyvid.="
    <h3>" . sprintf($text['Gallery_image_edit_label_header'], $row['photos']) . "</h3>
    <a href=index.php?action=gallery/admin/photogalery&site_id={$site_id}&lang={$lang}>{$text['Gallery_back_to_list_of_images']}</a>
    <a href=index.php?action=gallery/photo&site_id={$site_id}&lang={$lang}&item={$row['id']} target=_blank>{$text['Preview']}</a>
    <br><br>
    <form method= post action=index.php?action=gallery/admin/photored&site_id={$site_id}&lang={$lang}&new={$new}>
    <table width=97% border=1>
    <tr>
    <td rowspan=5 valign=top>
    <a href=$url_prefix/{$row['photos']}><img src={$url_prefix}/{$row['photos_m']} style='border:1px dotted gray;'></a>
    <div align=right><a href={$this_site_info['url']}gallery/{$row['photos']} style='border:1px dotted gray;text-align:center;padding-bottom:1pt;text-decoration:none;font-weight:bold;' target=_blank>&nbsp;+&nbsp;</a></div>
    <br>
    {$row['pidpys']}
    </td>
      ";
// prn($row);

$vyvid .="
      <td valign=top>{$text['Gallery_image_label']}</td>
      <td valign=top><INPUT type=text name=pidpys1  style='width:100%;' value=\"" . htmlspecialchars($row['pidpys']) . "\"></td>
      </tr>
      <tr>
        <td valign=top>{$text['Gallery_image_author']}</td>
        <td valign=top><INPUT type=text name=autor1  value='" . htmlspecialchars($row['autor']) . "' style='width:100%;'></td>
      </tr>
      <tr>
        <td valign=top>{$text['Gallery_image_rik']}</td>
        <td valign=top><INPUT type=text name=rik1 value=" . htmlspecialchars($row['rik']) . " style='width:100%;'>
			</td></tr>
      <tr>
      <td valign=top>{$text['Gallery_image_published']}</td>
      <td>
     <SELECT name=vis1>
	<option value=0 ";
if ($row['vis'] != 1) {
    $vyvid .= "selected";
}
$vyvid .= ">{$text['negative_answer']}</OPTION>
			<option value=1 ";
if ($row['vis'] == 1) {
    $vyvid .= "selected";
}
$vyvid .= ">{$text['positive_answer']}</OPTION>
      </select>
      </td></tr>

      <tr>
        <td valign=top>{$text['Gallery_category']}:</td>
        <td valign=top><textarea name=rozdil1 id=rozdil style='width:100%;'>" . htmlspecialchars($row['rozdil']) . "</textarea>
			<p>{$text['Gallery_existing_categories']}:<br />
        <small>{$text['Gallery_existing_categories_tip']}</small>
        <script type=\"text/javascript\">
         <!--
           var rozdil=document.getElementById('rozdil');
           function select_rozdil(txt) { rozdil.value+=\"\\n\"+txt; }
         // -->
         </script>
				 <div style=\"font-size: 90%; height: 100pt; overflow:scroll; color:gray;\">
       ";
$resulty = \e::db_getrows("SELECT DISTINCT rozdil FROM {$table_prefix}photogalery WHERE site = '$site_id'  ORDER BY `rozdil` ASC", $link) or die("Query failed");
if (!count($resulty)) {
    $vyvid .= " <b>{$text['Gallery_no_categories']}</b></p>";
}
$vyvid .= "<br>";

function clear_str($str) {
    $tot = str_replace('"', ' ', $str);
    $tot = str_replace('\'', ' ', $str);
    return $tot;
}

foreach ($resulty as $roww) {
    $vyvid .= "<b> - <a href=# onclick=\"select_rozdil('" . clear_str($roww['rozdil']) . "'); return false;\">{$roww['rozdil']}</a></b><br>";
}

// ================ draw simple editor = begin =============================
$vyvid .= "


           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup.js\"></script>
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  init_links();
                  $('#wysiswyg').markItUp(mySettings);
              });
           </script>
            ";
// ================ draw simple editor = end ===============================

$vyvid .="</div></td>
      </tr>
      <tr>
      <tr><td></td><td valign=top>".text('Long_description')."</td><td><textarea name=description1 id=wysiswyg style='width:100%'>" . htmlspecialchars($row['description']) . "</textarea></td></tr>
      </tr>

      <tr><td></td><td></td><td><input type=submit value=\"OK\"></td></tr>
      </table></form>";
# ----------------------- draw form - end --------------------------------------

$input_vars['page_title'] =
        $input_vars['page_header'] = $this_site_info['title'] . ' - ' . $text['Gallery_manage'];
$input_vars['page_content'] = $vyvid;

//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

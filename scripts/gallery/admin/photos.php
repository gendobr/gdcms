<?php

$potochnyjrik = date('Y');
include(\e::config('SCRIPT_ROOT') . '/gallery/admin/trumbalis0.php');
run('lib/file_functions');

function clear_str($str) {
    $tot = str_replace('"', ' ', $str);
    $tot = str_replace('\'', ' ', $str);
    return $tot;
}

//----------------------���� �������� ---------------------------------
$link = $db;
$data = date("Y-m-d H:i");

/*
  List of messages in guestbook for moderator
  Argument is
  $site_id - site identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
run('site/menu');

# ------------------- site info - begin ----------------------------------------
if (isset($input_vars['site_id'])) {
    $site = $site_id = checkInt($input_vars['site_id']);
    $this_site_info = get_site_info($site);

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
//--------------------------- ���� ������������ --------------------------



$vyvid = '';
$lang = $_SESSION['lang'];

$report = Array('', '', '', '', '');
# -------------------------- add image to gallery - begin ----------------------

$gallery_small_image_width  = defined('gallery_small_image_width')?gallery_small_image_width:150;
$gallery_small_image_height = defined('gallery_small_image_height')?gallery_small_image_height:150;

$qq = 0;
while ($qq < 5) {
    if (isset($_FILES["photos_{$qq}"])) {
        $photos = $_FILES["photos_{$qq}"];
        $photom = $_FILES["photo_m_{$qq}"];

        $rozdil = $input_vars["rozdil_{$qq}"];
        $rozdil2 = encode_dir_name($rozdil);

        $pidpys = $input_vars["pidpys_{$qq}"];

        $autor = $input_vars["autor_{$qq}"];

        $rik = (int) $input_vars["rik_{$qq}"];

        $vis = ($input_vars["vis_{$qq}"] == 1) ? 1 : 0;
        $data = date('Y-m-d-h-i-s');



        if ($photos['size'] > 0 && preg_match("/(jpg|gif|png|jpeg)\$/i", $photos['name'], $regs)) {
            # get file extension
            $file_extension = ".{$regs[1]}";

            # create file name
            $big_image_file_name = "{$site}-{$data}-" . encode_file_name($photos['name']);

            # create directory
            $relative_dir = date('Y') . '/' . date('m');
            $site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
            path_create($site_root_dir, "/gallery/$relative_dir/");

            # copy uploaded file
            copy($photos['tmp_name'], "$site_root_dir/gallery/$relative_dir/$big_image_file_name");

            if ($photom['size'] > 0 && eregi("(jpg|gif|png|jpeg)$", $photom['name'], $regs)) {
                $small_image_file_name = eregi_replace($file_extension . '/', '.jpg', "{$site}-{$data}-m-" . encode_file_name($photom['name']));
                copy($photom['tmp_name'], "$site_root_dir/gallery/$relative_dir/$small_image_file_name");
            } else {
                # -------------- create small image - begin -------------------------
                $small_image_file_name = "{$site}-{$data}-m-" . encode_file_name($photos['name']);
                img_resize(
                        "$site_root_dir/gallery/$relative_dir/$big_image_file_name", // source image
                        "$site_root_dir/gallery/$relative_dir/$small_image_file_name", // here thumbnail image will be saved
                        $gallery_small_image_width,  // new width
                        $gallery_small_image_height, // new height
                        $rgb = 0xFFFFFF, $quality = 100);
                # -------------- create small image - end ---------------------------
            }

            # save to database
            $icon_insert = \e::db_execute(
                    "INSERT INTO {$table_prefix}photogalery(id,photos,photos_m,rozdil,rozdil2,pidpys,autor,rik,site,vis)
                 VALUES
                 ( NULL
                  ,'" . \e::db_escape("$relative_dir/$big_image_file_name") . "'
                  ,'" . \e::db_escape("$relative_dir/$small_image_file_name") . "'
                  ,'" . \e::db_escape($rozdil) . "'
                  ,'" . \e::db_escape($rozdil2) . "'
                  ,'" . \e::db_escape($pidpys) . "'
                  ,'" . \e::db_escape($autor) . "'
                  ,'" . \e::db_escape($rik) . "'
                  ,'" . \e::db_escape($site) . "'
                  ,'" . \e::db_escape($vis) . "'
                 )");

            # show report
            $url_prefix = preg_replace("/\\/+\$/i", '', $this_site_info['url']) . '/gallery';
            $result = \e::db_execute("SELECT * FROM {$table_prefix}photogalery WHERE photos = '" . \e::db_escape("$relative_dir/$big_image_file_name") . "'");
            $a = mysql_num_rows($result);
            $row = mysql_fetch_array($result);
            if ($row) {
                $report[$qq] = "<h4 style='color:green;'>{$text['Gallery_image_uploaded_successfully']}</h4>
                            <img src={$url_prefix}/{$row['photos']} width=200px>
                            <p>{$row['pidpys']}
                            <br><small><b>{$text['Gallery_image_author']}:</b> {$row['autor']}</small>
                            <br><small><b>{$text['Gallery_image_rik']}:</b> {$row['rik']}</small>
                            <br><small><b>{$text['Gallery_category']}:</b> {$row['rozdil']}</small>
                            </p>
                           ";
            }
        }
    }
    $qq = $qq + 1;
}
# -------------------------- add image to gallery - end ------------------------

run('gallery/category_model');
gallery_synchronize_categories($this_site_info['id']);

$list_rozdil = \e::db_getrows("SELECT DISTINCT rozdil FROM {$table_prefix}photogalery_rozdil WHERE site_id = '$site_id' ORDER BY `rozdil` ASC");



$vyvid = "<h3>{$text['Gallery_add_new_image']}</h3>
	<a href=index.php?action=gallery/admin/photogalery&site_id={$site_id}&lang={$lang}>{$text['Gallery_back_to_list_of_images']}</a>
	<form action=index.php method=post enctype=multipart/form-data>
       <input type=hidden name=action value=gallery/admin/photos>
       <input type=hidden name=site_id value={$site_id}>
       <table width=100%>
";

for ($eee = 0; $eee < 5; $eee++) {
    $vyvid .="
       <tr><td width=20% valign=top>{$text['Gallery_image_upload']}</td>
           <td><input type=file name=photos_{$eee} style='width:100%;'></td>
           <td rowspan=7 valign=top><img src=img/tr.gif width=200px height=1px> {$report[$eee]}</td>
       </tr>
       <tr>
           <td valign=top>{$text['Gallery_image_small']}</td>
           <td><input type=file name=photo_m_{$eee} style='width:100%;'></td>
       </tr>
       <tr>
           <td valign=top>{$text['Gallery_image_label']}</td>
           <td><input type=text name=pidpys_{$eee} VALUE=\"\" SIZE=50 style='width:100%;'></td>
       </tr>
       <tr>
           <td valign=top>{$text['Gallery_image_author']}</td>
           <td><input type=text name=autor_{$eee} VALUE=\"\" style='width:100%;'></td>
       </tr>
       <tr>
           <td valign=top>{$text['Gallery_image_rik']}</td>
           <td><Input type=text name=rik_{$eee} VALUE=\"{$potochnyjrik}\" style='width:100%;'></td>
       </tr>
       <tr>
           <td valign=top>{$text['Gallery_category']}:<br />
           <small style=\"color:gray;\"> {$text['Gallery_existing_categories_tip']}</small>
           </td>
           <td><input type=text name=rozdil_{$eee} id=rozdil_{$eee} VALUE=\"\" SIZE=50 style='width:100%;'><br />
	            <p>{$text['Gallery_existing_categories']}:<br /></p>
                <script type=\"text/javascript\">
                <!--
                   function select_rozdil(txt,r) {  var rozdil=document.getElementById(r); rozdil.value=txt; }
                // -->
                </script>
	            <div style=\"font-size: 90%; height: 100pt; overflow:scroll; color:gray;\">
                ";
    foreach ($list_rozdil as $roww) {
        $vyvid .= "<b> - <a href=# onclick=\"select_rozdil('" . clear_str($roww['rozdil']) . "','rozdil_{$eee}');return false;\">{$roww['rozdil']}</a></b><br>";
    }
    $vyvid .="
                </div>
           </td>
       </tr>
       <tr>
          <td valign=top>{$text['Gallery_image_published']}</td>
          <td>
             <SELECT name=vis_{$eee}>
               <option value=1 selected>{$text['positive_answer']}</OPTION>
               <option  value=0>{$text['negative_answer']}</OPTION>
             </select>
         </td>
       </tr>
	   <tr><td colspan=3 style=\"background-color:#123456;\"><br></td></tr>
	   ";
}

$vyvid .="
  <tr><td></td><td colspan=2><INPUT TYPE=submit VALUE=\"{$text['Upload']}\"></td></tr>
  </table>
</form>";





//--------------------------- ���� �������� --------------------------
$input_vars['page_title'] =
        $input_vars['page_header'] = $this_site_info['title'] . ' - ' . $text['Gallery_manage'];
$input_vars['page_content'] = $vyvid;

//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
//--------------------------- ���� ������������ --------------------------
?>
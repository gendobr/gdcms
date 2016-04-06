<?php

run('site/menu');

# ------------------- site info - begin ----------------------------------------
$site_id = 0;
if (isset($input_vars['site_id'])) {
    $site_id = checkInt($input_vars['site_id']);
    $this_site_info = get_site_info($site_id);
    $site_id = checkInt($this_site_info['id']);
}

if ($site_id <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
# ------------------- site info - end ------------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------




$potochnyjrik = date('Y');

$list_rozdil = \e::db_getrows("SELECT DISTINCT rozdil FROM {$table_prefix}photogalery_rozdil WHERE site_id = '$site_id' ORDER BY `rozdil` ASC");

$vyvid = '';
$lang = $_SESSION['lang'];

$vyvid = "

    <script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/plupload/plupload.full.js\"></script>

    <link rel=\"stylesheet\" href=\"" . site_root_URL . "/scripts/lib/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css\" type=\"text/css\" media=\"screen\" />
    <script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/plupload/jquery.plupload.queue/jquery.plupload.queue.js\"></script>

    <link rel=\"stylesheet\" href=\"" . site_root_URL . "/scripts/lib/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css\" type=\"text/css\" media=\"screen\" />
    <script src=\"" . site_root_URL . "/scripts/lib/jquery-ui.min.js\"></script>
    <style>
    .plupload_scroll{
       max-height: 400px;
    }
    </style>
    <script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/plupload/jquery.ui.plupload/jquery.ui.plupload.js\"></script>

    <script>
        var uploader;
        $(function() {
            uploader = newUploader();
        });

        function log(string){
           $('#ajax-results').append('<div>'+string+'</div>');
        }


        function newUploader(){
            var uploader = $(\"#uploader\").pluploadQueue({
                runtimes : 'gears,html5,flash,silverlight,browserplus,html4',
                url : '".site_URL."',
                max_file_size : '10mb',
                unique_names : true,
                flash_swf_url : '" . site_root_URL . "/scripts/lib/plupload/plupload.flash.swf',
                silverlight_xap_url : '" . site_root_URL . "/scripts/lib/plupload/plupload.silverlight.xap',
                filters : [
                        {title : \"Image files\", extensions : \"jpg,gif,png\"}
                ],
                preinit: {
			UploadFile: function(up, file) {
                               log('[UploadingFile] ' + file.name + '(' + file.size + ' bytes)');
                               // You can override settings before the file is uploaded
                               up.settings.multipart_params={
                                    'pidpys':$(\"#pidpys\").val(),
                                    'autor':$(\"#autor\").val(),
                                    'rozdil':$(\"#rozdil\").val(),
                                    'rik':$(\"#rik\").val(),
                                    'vis':$(\"#vis\").val(),
                                    'action':$(\"#action\").val(),
                                    'site_id':$(\"#site_id\").val(),
                                    'lang':$(\"#lang\").val()}
			}
		},
                // Post init events, bound after the internal events
		init: {
                    FileUploaded: function(up, file, info) {
                        // Called when a file has finished uploading
                        log(info.response);
                    }
                }
            });
        // UploadComplete(uploader:Uploader, files:Array)
        // Fires when all files in a queue are uploaded.
            return uploader;
        }

        function upload_again(){
           uploader = newUploader();
        }
    </script>
    <form method=\"post\" action=\"index.php\" enctype=\"multipart/form-data\">
    <input type=\"hidden\" name=\"action\"  id=\"action\"  value=\"gallery/admin/upload_receiver\">
    <input type=\"hidden\" name=\"site_id\" id=\"site_id\" value=\"{$site_id}\">
    <input type=\"hidden\" name=\"lang\"    id=\"lang\"    value=\"{$lang}\">

    <div>{$text['Gallery_image_label']}:</div>
    <div><input type=text name=pidpys id=pidpys VALUE=\"\" SIZE=50 style='width:100%;'></div>
    <br/>
    <div>{$text['Gallery_image_author']}:</div>
    <div><input type=text name=autor id=autor VALUE=\"\" style='width:100%;'></div>
    <br/>
    <div>{$text['Gallery_image_rik']}</div>
    <div><Input type=text name=rik id=rik VALUE=\"{$potochnyjrik}\" style='width:100%;'></div>
    <br/>
    <div>{$text['Gallery_image_published']}</div>
    <div><SELECT name=vis id=vis><option value=1 selected>{$text['positive_answer']}</OPTION><option  value=0>{$text['negative_answer']}</OPTION></select></div>
    <br/>
    <div>{$text['Gallery_category']}:</div>
    <small style=\"color:gray;\"> {$text['Gallery_existing_categories_tip']}</small>
    <div><input type=text name=rozdil id=rozdil id=rozdil VALUE=\"\" SIZE=50 style='width:100%;'><br />
         <p>{$text['Gallery_existing_categories']}:<br /></p>
         <script type=\"text/javascript\">
         <!--
           function select_rozdil(txt,r) {  var rozdil=document.getElementById(r); rozdil.value=txt; }
           function select_rozdil_(elem,r) {
              var rozdil=document.getElementById(r);
              rozdil.value=elem.innerHTML;
           }
         // -->
         </script>
         <div style=\"font-size: 90%; height: 100pt; overflow:scroll; color:gray;\">
             ";
                function clear_str($str) {
                    $tot = str_replace('"', ' ', $str);
                    $tot = str_replace('\'', ' ', $str);
                    return $tot;
                }
                foreach ($list_rozdil as $roww) {
                    //$vyvid .= "<b> - <a href=# onclick=\"select_rozdil('" . clear_str($roww['rozdil']) . "','rozdil');return false;\">{$roww['rozdil']}</a></b><br>";
                    $vyvid .= "<b> - <a href=# onclick=\"select_rozdil_(this,'rozdil');return false;\">{$roww['rozdil']}</a></b><br>";
                }
                $vyvid .="
         </div>
    </div>
    <br/>
    <div>{$text['Gallery_image_upload']}</div>
    <div id=\"uploader\"><p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p></div>
    <div><a href=\"javascript:void(upload_again())\">Upload more files</a></div>
    <br/><br/>
    <div id=\"ajax-results\" style=\"height:300px; border:#ccc solid 3px; overflow:auto; padding:5px;\"></div>
    </form>
    ";


$input_vars['page_title'] =
        $input_vars['page_header'] = $this_site_info['title'] . ' - ' . $text['Gallery_image_upload'];
$input_vars['page_content'] = $vyvid;

//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
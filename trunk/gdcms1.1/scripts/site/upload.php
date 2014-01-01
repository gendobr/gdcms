<?php
/*
 * uploading multiple files
 */

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


run('lib/file_functions');

if(!isset($input_vars['dirname'])) $input_vars['dirname']='';
$input_vars['dirname'] = preg_replace("/^\\/|\\/\$/",'',$input_vars['dirname']);


$input_vars['page_title']   =
$input_vars['page_header']  = $this_site_info['title'].':'.$text['Upload_more_files'];

$input_vars['page_content'] = "
Directory <b><a href=\"index.php?action=site/files&site_id=$site_id&current_dir=".rawurlencode('/'.$input_vars['dirname'])."&popup=no&text_field_id=\">{$input_vars['dirname']}</a></b><br><br>


    <script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/plupload/plupload.full.js\"></script>
    <!-- script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/base64.js\"></script -->

    <link rel=\"stylesheet\" href=\"" . site_root_URL . "/scripts/lib/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css\" type=\"text/css\" media=\"screen\" />
    <script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/plupload/jquery.plupload.queue/jquery.plupload.queue.js\"></script>

    <link rel=\"stylesheet\" href=\"" . site_root_URL . "/scripts/lib/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css\" type=\"text/css\" media=\"screen\" />
    <script src=\"http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js\"></script>
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
                //   filters : [
                //             {title : \"Allowed files\", extensions : \"".str_replace('|',',',allowed_file_extension)."\"}
                //   ],
                preinit: {
			UploadFile: function(up, file) {
                               log('[UploadingFile] ' + file.name + '(' + file.size + ' bytes)');
                               // You can override settings before the file is uploaded
                               up.settings.multipart_params={
                                    'action':$(\"#action\").val(),
                                    'site_id':$(\"#site_id\").val(),
                                    '".session_name()."':'".session_id()."',
                                    'dirname':$(\"#dirname\").val()
                              };
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
    <input type=\"hidden\" name=\"action\"  id=\"action\"  value=\"site/upload_receiver\">
    <input type=\"hidden\" name=\"site_id\" id=\"site_id\" value=\"{$site_id}\">
    <input type=\"hidden\" name=\"".session_name()."\" id=\"session_name\" value=\"".session_id()."\">
    <input type=\"hidden\" name=\"dirname\" id=\"dirname\" value=\"".  checkStr($input_vars['dirname'])."\">

    <div id=\"uploader\"><p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p></div>
    <div><a href=\"javascript:void(upload_again())\">Upload more files</a></div>
    <br/><br/>
    <div id=\"ajax-results\" style=\"height:300px; border:#ccc solid 3px; overflow:auto; padding:5px;\"></div>
    </form>
    ";

//--------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>" ,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
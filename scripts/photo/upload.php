<?php

run('site/menu');

# ------------------- site info - begin ----------------------------------------
$site_id = \e::cast('integer',\e::request('site_id',0));
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
$site_id = $this_site_info['id'];
# ------------------- site info - end ------------------------------------------

# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------


$list_of_languages = list_of_languages();
$js_lang = Array();
foreach ($list_of_languages as $l) {
    $js_lang[$l['name']] =$text[$l['name']];
}
$js_lang = json_encode($js_lang);


$category_list =  array_map(
    function($row){
        return [
            $row['photo_category_id'],
            str_repeat('&nbsp;|&nbsp;&nbsp;&nbsp;',  substr_count($row['photo_category_path'], '/')).get_langstring($row['photo_category_title'])
        ];
    },
    \e::db_getrows("SELECT photo_category_id,photo_category_path,photo_category_title   FROM <<tp>>photo_category photo_category WHERE site_id=<<integer site_id>> ORDER BY photo_category_path ASC",['site_id'=>$site_id])
);

$lang = \e::session('lang');



$html="

    <script type=\"text/javascript\" src=\"scripts/lib/langstring.js\"></script>

    <script type=\"text/javascript\" src=\"" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/plupload/plupload.full.js\"></script>

    <link rel=\"stylesheet\" href=\"" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/plupload/jquery.plupload.queue/css/jquery.plupload.queue.css\" type=\"text/css\" media=\"screen\" />
    <script type=\"text/javascript\" src=\"" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/plupload/jquery.plupload.queue/jquery.plupload.queue.js\"></script>

    <link rel=\"stylesheet\" href=\"" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css\" type=\"text/css\" media=\"screen\" />
    <script src=\"" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/jquery-ui.min.js\"></script>
    <style>
    .plupload_scroll{
       max-height: 400px;
    }
    </style>
    <script type=\"text/javascript\" src=\"" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/plupload/jquery.ui.plupload/jquery.ui.plupload.js\"></script>

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
                url : '".\e::config('APPLICATION_ADMIN_URL')."/index.php',
                max_file_size : '10mb',
                unique_names : true,
                flash_swf_url : '" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/plupload/plupload.flash.swf',
                silverlight_xap_url : '" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/plupload/plupload.silverlight.xap',
                filters : [
                        {title : \"Image files\", extensions : \"jpg,gif,png\"}
                ],
                preinit: {
			UploadFile: function(up, file) {
                                var config=$('#config')
                                log('[UploadingFile] ' + file.name + '(' + file.size + ' bytes)');
                                // You can override settings before the file is uploaded
                                up.settings.multipart_params={
                                    'action':config.attr('data-action'),
                                    'site_id':config.attr('data-site_id'),
                                    'photo_title':$('#photo_title').val(),
                                    'photo_author':$('#photo_author').val(),
                                    'photo_category_id':$('#photo_category_id').val(),
                                    'photo_description':$('#photo_description').val(),
                                    'photo_year':$('#photo_year').val(),
                                    'photo_visible':$('input[name=photo_visible]:checked').val()
                                }
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

    <div id='config'
         data-site_id='{$site_id}'
         data-action='photo/upload_receiver'
    ></div>
    
    <div class=label><h5>" . text('photo_category_id') . "</h5></div>
    <div class=big>
        <select name='photo_category_id' id='photo_category_id'>
            <option value=''></option>
            ".\core\form::draw_options('', $category_list)."
        </select>
    </div>
    
    <div><!-- 
   --><span class=blk6>
    <div class=\"label\"><h5>".text('photo_title')."</h5></div>
    <div class=\"big\"><input type=text name=\"photo_title\" id=\"photo_title\" value=\"\"></div>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langstring('photo_title');
    </script>
    </span><!-- 
   --><span class=blk6>
    <div class=\"label\"><h5>".text('photo_author')."</h5></div>
    <div class=\"big\"><input type=text name=\"photo_author\" id=\"photo_author\" value=\"\"></div>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langstring('photo_author');
    </script>
    </span><!-- 
 --></div>


    <div><!-- 
   --><span class=blk6>
    <div class=\"label\"><h5>".text('photo_year')."</h5></div>
    <div class=\"big\"><input type=text name=\"photo_year\" id=\"photo_year\" value=\"\"></div>
    </span><!-- 
   --><span class=blk6>
    <div class=\"label\"><h5>".text('photo_visible')."</h5></div>
    <div class=\"big\">" . \core\form::draw_radio(1,[1=>text('positive_answer'),0=>text('negative_answer')], 'photo_visible') . "</div>
    </span><!-- 
 --></div>

    <div class=\"label\"><h5>".text('photo_description')."</h5></div>
    <div class=\"big\"><input type=text name=\"photo_description\" id=\"photo_description\" value=\"\"></div>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langarea('photo_description');
    </script>

    <div id=\"uploader\"><p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p></div>
    <div><a href=\"javascript:void(upload_again())\">".text('Upload_more_photos')."</a></div>
    <br/><br/>
    <div id=\"ajax-results\" style=\"height:300px; border:#ccc solid 3px; overflow:auto; padding:5px;\"></div>
    ";



         

$input_vars['page_header']=$input_vars['page_title']=text('photo_upload');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = text('Site') . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

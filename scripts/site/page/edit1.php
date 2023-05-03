<?php
/*
  Editing page
  argument is $page_id    - page identifier, integer, mandatory
              $lang       - page_language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/
run('site/image/url_replacer');
run('site/page/menu');
run('site/menu');

# ------------------- check page id - begin ------------------------------------
  $page_id   = (int)$input_vars['page_id'];

  // $lang      = DbStr($input_vars['lang']);
  $lang = get_language('lang');

  $this_page_info=get_page_info($page_id,$lang);
  if(!$this_page_info)
  {
     $input_vars['page_title']   =
     $input_vars['page_header']  =
     $input_vars['page_content'] = $text['Page_not_found'];
     return 0;
  }
  # prn('$this_page_info',$this_page_info);
# ------------------- check page id - end --------------------------------------



//------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($this_page_info['site_id']);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =$text['Access_denied'];
     $input_vars['page_header'] =$text['Access_denied'];
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
//------------------- get permission - end -------------------------------------

# site info
  $this_site_info = get_site_info($this_page_info['site_id']);
  $site_id=$this_page_info['site_id'];
  # prn('$this_site_info=',$this_site_info);



# ----------------- save new page info - begin ---------------------------------
if(   isset($input_vars['save_changes'])
   && strlen($input_vars['save_changes'])>0)
{
   run('site/page/edit1_save',Array(
   'this_page_info'=>$this_page_info
  ,'this_site_info'=>$this_site_info
   ));
}
# ----------------- save new page info - end -----------------------------------








//------------------- draw form - begin ----------------------------------------
   $notify_managers_form='';
   foreach($this_site_info['managers'] as $mn)
   {
     $notify_managers_form .="<input type=checkbox name='notify[{$mn['id']}]'> {$mn['full_name']}<br/>";
   }
   if(strlen($notify_managers_form)>0) $notify_managers_form="<div><b>{$text['Send_notification_to']}</b><br/>{$notify_managers_form}</div>";

  if(!isset($message)) $message='';



  $input_vars['page_title']   = $text['Editing_page'];
  $input_vars['page_header']  = $text['Editing_page'];
  $input_vars['page_content'] = "
           <!-- Load TinyMCE -->
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/tiny_mce/jquery.tinymce.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/tiny_mce_start.js\"></script>

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  init_links();
                  var stn = { external_link_list_url : \"index.php?action=site/filechooser/tiny_mce_link_list&site_id={$site_id}\",
                       external_image_list_url : \"index.php?action=site/filechooser/tiny_mce_image_list&site_id={$site_id}\",
                       language : \"".substr($_SESSION['lang'],0,2)."\"};
                  tinymce_init('textarea#page_content_area',stn);
                  tinymce_init('textarea#page_abstract',stn);
              });
           </script>
           <!-- /TinyMCE -->
    ";



 # ------------------------ list of categories - begin -------------------------
    $query="SELECT category_id, category_title, deep FROM <<tp>>category WHERE start>=0 AND site_id={$this_page_info['site_id']} ORDER BY start ASC";
    // prn($query);
    $tmp=\e::db_getrows($query);
    $list_of_categories=Array();
    foreach($tmp as $tm) $list_of_categories[$tm['category_id']]=str_repeat(' + ',$tm['deep']).get_langstring($tm['category_title']);
    unset($tmp,$tm);
    //prn($list_of_categories);
 # ------------------------ list of categories - end ---------------------------


if(!isset($file_upload_form)) $file_upload_form='';



  $input_vars['page_content'].="
  <form action='index.php' method=POST name=editform  enctype=\"multipart/form-data\" style='margin:0;'>
  <input type=hidden name=action  value=\"{$input_vars['action']}\">
  <input type=hidden name=page_id value=\"{$this_page_info['id']}\">
  <input type=hidden name=lang value=\"{$this_page_info['lang']}\">
  <input type=hidden name=save_changes value=\"yes\">
  <input type=hidden name=site_id id=site_id value=\"{$this_page_info['site_id']}\">

  <div class=big>
  <b>{$message}</b>
  </div>
  <div class=label>
      {$this_site_info['title']} > {$this_page_info['title']}<br>
          <a href=\"{$this_page_info['absolute_url']}\"  target=_blank>{$this_page_info['absolute_url']} </a>
              ".($this_page_info['friendly_url']?"<br><a href=\"{$this_page_info['friendly_url']}\"  target=_blank>{$this_page_info['friendly_url']} </a>":'')."
  </div>
  <div class=label>
      <span>{$text['Last_changed']} : {$this_page_info['last_change_date']}</span>
  </div>
  <table>
  <div class=label>{$text['Page_Title']} :</div>
  <div class=big>
     <input type=text MAXLENGTH=512 name=page_title value=\"".htmlspecialchars($this_page_info['title'])."\">
  </div>






  <span class=blk13>
  <div class=label>{$text['Page_Language']}:</div>
  <div class=big>
    <select name=page_lang>".
        draw_options($this_page_info['lang'], site_get_languages($this_site_info))
    ."</select>
  </div>
  </span>


  <span class=blk13>
  <div class=label>{$text['Is_home_page']}:</div>
  <div class=big>
    ".draw_radio($this_page_info['is_home_page'],Array(1=>$text['positive_answer'],0=>$text['negative_answer']),'page_is_home_page')."
  </div>
  </span>


  <span class=blk13>
  <div class=label>{$text['Approve']}:</div>
  <div class=big>
    ".draw_radio($this_page_info['cense_level'],Array(0=>$text['negative_answer'],$user_cense_level=>$text['positive_answer']),'page_cense_level')."
  </div>
  </span>

  <div class=label>".$text['Page_Category']." : </div>
  <div class=big>
     <select name=page_category>
    <option value=''></option>".
    draw_options($this_page_info['category_id'],$list_of_categories)
    ."</select>
  </div>



   <div class=label>{$text['Abstract']} :</div>
   <div class=big>
        <div>
            <a href=\"javascript:void('index.php?action=gallery/json&site_id={$site_id}')\" onclick=\"display_gallery_links('index.php?action=photo/json&lang={$this_page_info['lang']}&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
            <a href=\"javascript:void('index.php?action=category/json&site_id={$site_id}')\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
            <a href=\"javascript:void('index.php?action=site/page/json&site_id={$site_id}')\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
            <a href=\"javascript:void('index.php?action=site/filechooser/json&site_id={$site_id}')\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
            <span class=\"btn-add-images\">
                {$text['Upload_image']}
                <input id=\"image_uploader_file_abstract\" 
                    class=\"image_uploader_file\" 
                    type=\"file\" name=\"imagefile\" 
                    data-url=\"".\e::url_admin([])."\" 
                    data-sequential-uploads=\"true\"
                    multiple>
            </span>
            <div id=\"progress_abstract\"><div class=\"bar\" style=\"width: 0%;\"></div></div>
        </div>
        <textarea name=page_abstract
            id=page_abstract
            wrap='virtual'
            tabindex='3'
            class='wysiswyg'
            style=\"width:500px; height:100px;\">".
        htmlspecialchars($this_page_info['abstract'])
        ."</textarea>

    </div>


  <div class=label>".text('Contents')." :</div>
  <div class=big>

      <div>
          <a href=\"javascript:void('index.php?action=gallery/json&site_id={$site_id}')\" onclick=\"display_gallery_links('index.php?action=photo/json&lang={$this_page_info['lang']}&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
          <a href=\"javascript:void('index.php?action=category/json&site_id={$site_id}')\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
          <a href=\"javascript:void('index.php?action=site/page/json&site_id={$site_id}')\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
          <a href=\"javascript:void('index.php?action=site/filechooser/json&site_id={$site_id}'))\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
        <span class=\"btn-add-images\">
            {$text['Upload_image']}
            <input id=\"image_uploader_file_content\" 
                class=\"image_uploader_file\" 
                type=\"file\" name=\"imagefile\" 
                data-url=\"".\e::url_admin([])."\" 
                data-sequential-uploads=\"true\"
                multiple>
        </span>
        <div id=\"progress_content\"><div class=\"bar\" style=\"width: 0%;\"></div></div>
      </div>
      <textarea name=page_content
                    id=page_content_area
                    wrap='virtual'
                    tabindex='4'
                    class='wysiswyg'
                    style=\"width:500px;height:500px;\">".
          htmlspecialchars($this_page_info['content'])
          ."</textarea>
  <div>

  <input type=submit value=\"{$text['Save']}\">
  <input type=reset  value=\"{$text['Reset']}\">
  </div>
  
<!-- 
  <div>
  {$text['Upload_page']}
  <input type=\"file\" name=page_upload><input type=submit value=\"{$text['Upload']}\">
  </div>
-->
  </div>

  <div class=label>{$text['Page_Path']} :</div>
  <div class=big>
     <input type=text MAXLENGTH=128 name=page_path value=\"".htmlspecialchars($this_page_info['path'])."\">
  </div>

  <div class=label>".text('Page_file')." :</div>
  <div class=big>
     <input type=text MAXLENGTH=128 name=page_file_name value=\"".htmlspecialchars($this_page_info['page_file_name'])."\">
  </div>
  <div class=label>".text('Page_meta_tags')." :</div>
  <div class=big>
    <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/meta-tags-insert.js\"></script>
    <script type=\"text/javascript\">
    $(document).ready(function(){
       metaTagsButtons('page_meta_tags');
    });
    </script>
     <textarea style=\"width:100%; height:150px;\" name=\"page_meta_tags\" id=\"page_meta_tags\">".htmlspecialchars($this_page_info['page_meta_tags'])."</textarea>
  </div>





{$notify_managers_form}


{$file_upload_form}

<table>





  <tr>
  <td colspan=6>
  <input type=submit value=\"{$text['Save']}\">
  <input type=reset  value=\"{$text['Reset']}\">
  </td>
  </tr>



  </table>
  </form>

    <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/select2/css/select2.min.css\" />
  

    <script type=\"text/javascript\" charset=\"" . site_charset . "\" src=\"".\e::config('APPLICATION_ADMIN_URL')."/scripts/lib/select2/js/select2.full.min.js\"></script>
    <script type=\"text/javascript\" charset=\"utf-8\" src=\"".\e::config('APPLICATION_ADMIN_URL')."/scripts/lib/fileupload/jquery.iframe-transport.js\"></script>
    <script type=\"text/javascript\" charset=\"utf-8\" src=\"".\e::config('APPLICATION_ADMIN_URL')."/scripts/lib/fileupload/jquery.fileupload.js\"></script>
    <script type=\"text/javascript\">
        $(function(){
            $('select').select2();



            var fileuploadOptions={
                dataType: 'json',
                formData:[
                    {name:'action',value:'site/page/edit_image_receiver'},
                    {name:'page_id',value:'{$page_id}'},
                    {name:'site_id',value:'{$site_id}'},
                    {name:'lang',value:'{$lang}'}
                ],
                // dropZone:$('image_uploader_file_abstract'),
                done: function (e, data) {
                    if(data.result.status=='success'){
                        // insert image(s) into active editor
                        //\$('#page_abstract').focus();
                        for(var i=0; i<data.result.data.length; i++){
                            insert_link('<img src=\"'+data.result.data[i].small+'\">', data.result.data[i].big,{rel:'lightbox',target:'_blank'});
                        }
                    }
                    $('#progress_abstract').hide();
                },
                //progressall: function (e, data) {
                //    var progress = parseInt(data.loaded / data.total * 100, 10);
                //    $('#progress_abstract .bar').css( 'width',  progress + '%'  );
                //},
                start:function (e) {
                    // console.log('Uploads started');
                    \$('#progress_abstract .bar').css( 'width','1%');
                    \$('#progress_abstract').show();
                }
            };

            fileuploadOptions.dropZone=$('image_uploader_file_abstract');
            $('#image_uploader_file_abstract').fileupload(fileuploadOptions).click(function(){\$('#page_abstract').focus();});

            fileuploadOptions.dropZone=$('image_uploader_file_content');
            $('#image_uploader_file_content').fileupload(fileuploadOptions).click(function(){\$('#page_content').focus();});






        });
    </script>
  
  
    <style type=\"text/css\">
    .btn-add-images{
        display:inline-block;
        position:relative;
        text-decoration:underline;
        color:#00334c;
        height:20px;
    }
    .btn-add-images input[type=\"file\"]{
        opacity:0;
        width:100%;
        height:20px;
        position:absolute;
        top:0px;
        left:0px;
        cursor:pointer;
    }
    #progress_abstract{
        position:absolute;
        display:none;
        width:200px;
        background-color:silver;
    }
    #progress_abstract .bar{
        display:inline-block;
        background-color:green;
    }
    </style>
  ";
//------------------- draw form - end ------------------------------------------

//----------------------------- context menu - begin ---------------------------
  $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
  $input_vars['page_menu']['page']['items'] = menu_page($this_page_info,$this_site_info);

    $sti=$text['Site'].' "'. $this_site_info['title'].'"';
    $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());

  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);

//----------------------------- context menu - end -----------------------------


<?php
/*
  List of files for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 * todo: надо переписать, чтобы не читать все файлы каждый раз - только текущую директорию
*/

# ----------------- if the popup template should be used - begin ---------------
  $popup='no';
  if(isset($input_vars['popup'])) if($input_vars['popup']=='yes')
  {
    $popup='yes';
    $GLOBALS['main_template_name']='design/popup';
  }
# ----------------- if the popup template should be used - end -----------------

# textarea id
  $text_field_id=isset($input_vars['text_field_id'])?$input_vars['text_field_id']:'';

# function to paste "insert" link
  function ins($fname,$site_root_url,$text_field_id)
  {
    if(in_array(strtolower(file_extention(basename($fname))),$GLOBALS['img_extensions']))
    {
      return "<a href=# title=\"{$GLOBALS['text']['Insert_into_form_field']}\" onclick=\"insert_img_html('{$site_root_url}{$fname}', '$text_field_id');\"><img src=img/icon_paste.gif width=20px height=15px border=0></a>";
    }
    else
    {
      return "<a href=# title=\"{$GLOBALS['text']['Insert_into_form_field']}\" onclick=\"insert_link_html('{$site_root_url}{$fname}','".basename($fname)."', '$text_field_id');\"><img src=img/icon_paste.gif width=20px height=15px border=0></a>";
    }
  }


  run('site/menu');

//------------------- site info - begin ----------------------------------------
  $site_id = (int)$input_vars['site_id'];
  $this_site_info = get_site_info($site_id);
  #$this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  #//prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
  $site_root_dir = realpath($this_site_info['site_root_dir']);
  $site_root_url = $this_site_info['site_root_url'];
  $site_root_dir=str_replace("\\","/",$site_root_dir);
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------



$message='';

run('lib/file_functions');

// ----------------- check current directory - begin ---------------------------
   if(!isset($input_vars['current_dir'])) $input_vars['current_dir']='';
   $current_dir=realpath($site_root_dir.$input_vars['current_dir']);
   if(strlen($current_dir)<strlen($site_root_dir)){
       $current_dir=$site_root_dir;
   }
   $current_dir=str_replace("\\","/",$current_dir);
   $current_dir_relative=  substr($current_dir, strlen($site_root_dir));
   //prn('$current_dir='.$current_dir);
   // if(is_admin()) prn('$current_dir_relative='.$current_dir_relative);


  if(!isset($input_vars['dirname'])) $input_vars['dirname']='';
  $input_vars['dirname'] = preg_replace('/^\/|\/$/','',$input_vars['dirname']);

// ----------------- check current directory - end -----------------------------


//------------------- file manager - begin -------------------------------------

  //------------------ upload files - begin ------------------------------------
    //prn($_FILES);
    if(is_array($_FILES))
    {

      if(count($_FILES)>0) ml('site/files#upload',Array($this_site_info,$_FILES));
      $upload_dir = $site_root_dir.'/'.$input_vars['dirname'];

      if(!is_dir($upload_dir)) $upload_dir=$site_root_dir;
      foreach($_FILES as $key=>$val)
      {
         $message.="";
         //prn($val);
         if($val['size']==0) continue;
         if(!preg_match("/\\.(".allowed_file_extension.")$/i",$_FILES[$key]['name']))
         {
             $message.=" <b><font color=red>File {$_FILES[$key]['name']} has forbidden extension</font></b><br>";
             continue;
         }
         $_FILES[$key]['name'] = encode_file_name($_FILES[$key]['name']);
         $reply=upload_file($key,$upload_dir);
         if($reply)
         {
            $message.=" <b><font color=green>File $reply uploaded successfully </font></b><br>";
         }
         else
         {
            switch($val['error'])
            {
              case UPLOAD_ERR_INI_SIZE:
                 $message.=" <b><font color=red>The uploaded file exceeds the upload_max_filesize directive in php.ini</font></b><br>";
              break;
              case UPLOAD_ERR_FORM_SIZE:
                 $message.=" <b><font color=red>The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form</font></b><br>";
              break;
              case UPLOAD_ERR_PARTIAL:
                 $message.=" <b><font color=red>The uploaded file was only partially uploaded</font></b><br>";
              break;
            }
         }
         //prn($key,$val,$reply);
         clearstatcache();
      }
    }
  //------------------ upload files - end --------------------------------------

  //------------------ delete file - begin -------------------------------------
    if (isset($input_vars['delete_file']) && strlen($input_vars['delete_file']) > 1) {
        ml('site/files#delete', Array($this_site_info, $input_vars['delete_file']));
        $delfile = realpath(str_replace('//', '/', $site_root_dir . "/{$input_vars['delete_file']}"));
        if(strlen($delfile)>strlen($current_dir)){
            rm_r($delfile);
            clearstatcache();
        }
    }
    clear('delete_file');
  //------------------ delete file - end ---------------------------------------

  //------------------ create subdir - begin -----------------------------------
    if (isset($input_vars['newsubdir'])  && strlen($input_vars['newsubdir']) > 0)  {
        $curr_dir = str_replace("\\","/",realpath(preg_replace("/\\/\$/", '', $site_root_dir . '/' . $input_vars['dirname'])));
        if (in_array($curr_dir, ls_r($site_root_dir))) {
            if (is_dir($curr_dir)) {
                $newsubdir=encode_dir_name($input_vars['newsubdir']);
                // prn($curr_dir.'/'.$input_vars['newsubdir'],$curr_dir.'/'.$newsubdir);
                ml('site/files#mkdir', Array($this_site_info, $curr_dir . '/' . $newsubdir));
                if (!@mkdir($curr_dir . '/' . $newsubdir)){
                     @mkdir($curr_dir . '/' . $newsubdir, 755);
                }
                clearstatcache();
            }
        }
    }
    clear('newsubdir');
//------------------ create subdir - end -------------------------------------




  //-------------------- unzip - begin -----------------------------------------
    if(isset($input_vars['unzip_file']) && strlen($input_vars['unzip_file'])>1) {
        // $unzip_file=
       //prn("unzip({$input_vars['unzip_file']})");
       ml('site/files#unzip',Array($this_site_info,$input_vars['unzip_file']));
       run("lib/pclzip.lib");
       unzip($current_dir.'/'.basename($input_vars['unzip_file']),$current_dir);
    }
    clear('unzip_file');
  //-------------------- unzip - end -------------------------------------------


  //------------------- get file list - begin ----------------------------------
    $all_list = ls_r($site_root_dir);
    //prn($all_list);
    $dir_list  = Array();
    $file_list = Array();
    foreach($all_list as $obj)
    {
       $obj=str_replace("\\","/",$obj);
       $fname=str_replace($site_root_dir,'',$obj);
       if(strpos ($fname,'/cms')===0) continue;
       if(is_dir($obj))
       {
          $dir_list[$fname]=(strlen($fname)==0)?'/':$fname;
       }
       if(dirname($obj)==$current_dir)
       {
         $file_list[]=$fname;
       }
    }
    asort($dir_list);
    asort($file_list);
    // prn($file_list);
    ml('site/files#list',Array($this_site_info,$current_dir));

  //------------------- get file list - end ------------------------------------


  //------------------- draw list of files - begin -----------------------------
    $input_vars['page_content']='';



    $input_vars['page_content'].="
    $message
      <script type=\"text/javascript\" src=\"scripts/lib/insert_link.js\"></script>
      <script type=\"text/javascript\">
         function rename(oldname){
            var newname=prompt('New file name',oldname);
            //var conf=confirm(oldname+' => '+newname);
            //if(conf){
               jQuery.ajax( 'index.php?action=site/file_rename_ajax&site_id={$site_id}&current_dir=".
                          rawurlencode($current_dir_relative)."&oldname='+oldname+'&newname='+newname+'&t='+Math.random()
                       , {
                           contentType: 'application/x-www-form-urlencoded; charset=".site_charset."',
                           success:function(data){
                             // console.log('Renamer returns '+data);
                             if(data=='SUCCESS'){
                               window.location.href='index.php?action=site/files&site_id={$site_id}&popup=no&current_dir=".rawurlencode($current_dir_relative)."';
                             }else{
                               alert(data);
                             }

                           }
                         });
            //}
         }
      </script>

      <style type=\"text/css\">
        .mnu, .fnm{
           display:inline-block;
           vertical-align:top;
        }
        .mnu{
           width:90px;
        }
        .row:hover{
           background-color:yellow;
        }

        .btn{
           display:inline-block;
           border:1px solid silver;
           padding: 0 3px;
           text-decoration: none;
           vertical-align: baseline;
        }
        .btn:hover{
           background-color:lime;
        }
      </style>
      <form action=index.php method=post>
      <input type=hidden name=action value=\"site/files\">
      <input type=hidden name=site_id value=\"{$site_id}\">
      <input type=hidden name=popup value=\"{$popup}\">
      <input type=hidden name=text_field_id value=\"{$text_field_id}\">
         {$text['Dir']}<br/>
         <select name=\"current_dir\" style=\"width:300px;\" onchange=\"this.form.submit()\">".draw_options($input_vars['current_dir'],$dir_list)."</select>
      </form>
    ";

    # ----------- link to open parent dir - begin ------------------------------
    if(strlen($input_vars['current_dir'])>0)
    {
      $cd=preg_replace("/\\/\$/",'',str_replace("\\",'/',dirname($input_vars['current_dir'])));
      $ke='parent';
      $input_vars['page_content'].="
      <div class=row>
      <span class='mnu'></span>
      <span class='fnm'>
         <a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&current_dir={$cd}&popup=$popup&text_field_id={$text_field_id}\"><img src=img/icon_parent_dir.gif width=18px height=18px border=0  width=20px height=15px> ".basename($cd)."</a>
      </span>
      </div>
      ";
    }
    # ----------- link to open parent dir - end --------------------------------
    foreach($file_list as $ke=>$fname)
    {
        //prn($site_root_dir.$fname);
        if(is_dir($site_root_dir.$fname))
        {
            if(preg_match("/^\\/gallery|^\\/cache/",$fname)){
                $rename_button='';
            }else{
                $rename_button="<a href=\"javascript:void(rename('".basename($fname)."'))\" title=\"Rename\" class=\"btn\">R</a>";
            }


          $input_vars['page_content'].="

          <div class=row>
          <span class='mnu'>
                <a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&popup=$popup&text_field_id={$text_field_id}&delete_file=".rawurlencode($fname)."&current_dir=".rawurlencode($input_vars['current_dir'])."\" onclick=\"return confirm('{$text['Are_you_sure']}?')\" title=\"{$text['Delete']}\"><img src=img/icon_delete1.gif border=0 width=20px height=15px></a>
                <a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&popup=$popup&text_field_id={$text_field_id}&current_dir=$fname\" title=\"{$text['Step_inside_directory']}\"><img src=img/icon_open.gif border=0  width=20px height=15px></a>
                {$rename_button}
          </span>
          <span class='fnm'>
            <a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&current_dir=$fname&popup=$popup&text_field_id={$text_field_id}\"><img src=img/icon_dir.png width=18px height=18px border=0> ".basename($fname)."</a>
          </span>
          </div>
          ";
        }
        else
        {
            if(preg_match("/^\\/gallery|^\\/cache/",$fname)){
                $rename_button='';
            }else{
                $rename_button="<a href=\"javascript:void(rename('".basename($fname)."'))\" title=\"Rename\" class=\"btn\">R</a>";
            }
          if(preg_match('/^\./',basename($fname))) continue;
          if(preg_match('/\.zip$/i',basename($fname))) $link_unzip=" (<a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&popup=$popup&text_field_id={$text_field_id}&unzip_file=".rawurlencode($fname)."&current_dir=".rawurlencode($input_vars['current_dir'])."\">unzip</a>)";
          else $link_unzip='';

          $input_vars['page_content'].="
            <div class=row>
            <span class='mnu'>
                <a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&popup=$popup&text_field_id={$text_field_id}&delete_file=".rawurlencode($fname)."&current_dir=".rawurlencode($input_vars['current_dir'])."\" onclick=\"return confirm('{$text['Are_you_sure']}?')\" title=\"{$text['Delete']}\"><img src=img/icon_delete1.gif border=0 width=20px height=15px></a>
                <a href=\"{$site_root_url}{$fname}?v=".time()."\" target=_blank title=\"{$text['View_file']}\"><img src=img/icon_view.gif border=0 width=20px height=15px></a>
                {$rename_button}
                ".
                ( (strlen($text_field_id)>0)?ins($fname,$site_root_url,$text_field_id):'' )
                ."
            </span>
            <span class='fnm'>
              <img src=img/icon_file.png width=18px height=18px border=0> ".basename($fname)." $link_unzip
            </span>
            </div>
          ";
        }
    }

    $input_vars['page_content'].="
    <div class=row>
    <form action=index.php method=post>
    <input type=hidden name=action value=\"site/files\">
    <input type=hidden name=site_id value=\"{$site_id}\">
    <input type=hidden name=popup value=\"{$popup}\">
    <input type=hidden name=text_field_id value=\"{$text_field_id}\">
    <input type=hidden name=current_dir value=\"{$input_vars['current_dir']}\">
    {$text['Create_subdirectory']} <input type=text name=newsubdir value=\"\"> {$text['in']} <select name=\"dirname\">".draw_options($input_vars['current_dir'],$dir_list)."</section>&nbsp;&nbsp;&nbsp;<input type=submit value=\"{$text['Create']}\">
    </form>
    </div>
    ";

    $input_vars['page_content'].="
        <div class=row>
        <form action=index.php enctype=\"multipart/form-data\" method=post>
        <input type=hidden name=action value=\"site/files\">
        <input type=hidden name=site_id value=\"{$site_id}\">
        <input type=hidden name=popup value=\"{$popup}\">
        <input type=hidden name=text_field_id value=\"{$text_field_id}\">
        <input type=hidden name=current_dir value=\"{$input_vars['current_dir']}\">
        {$text['Upload_files']}<br>
        <input type=\"file\" name=userfile1>
        {$text['into']}
        <select name=\"dirname\">".draw_options($input_vars['current_dir'],$dir_list)."</select>

        <input type=submit value=\"{$text['Upload']}\">
        </form>
        </div>";
    //     <!--
    //     <ol>
    //     <li><input type=\"file\" name=userfile1></li>
    //     <li><input type=\"file\" name=userfile2></li>
    //     <li><input type=\"file\" name=userfile3></li>
    //     <li><input type=\"file\" name=userfile4></li>
    //     <li><input type=\"file\" name=userfile5></li>
    //     <li><input type=\"file\" name=userfile6><br />
    //         {$text['into']}
    //         <select name=\"dirname\">".draw_options($input_vars['current_dir'],$dir_list)."</select><br />
    //     </ol>
    //     -->

    $input_vars['page_content'].="
    <br/>
    <h3>{$text['Upload_files']}</h3>


    <script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/plupload/plupload.full.js\"></script>
    <script type=\"text/javascript\" src=\"" . site_root_URL . "/scripts/lib/plupload/i18n/{$_SESSION['lang']}.js\"></script>
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

    <!-- {$text['into']} <select name=\"dirname\">".draw_options($input_vars['current_dir'],$dir_list)."</select> -->
    <input type=\"hidden\" name=\"dirname\" id=\"dirname\" value=\"".  checkStr($input_vars['current_dir'])."\">

    <div id=\"uploader\"><p>You browser doesn't have Flash, Silverlight, Gears, BrowserPlus or HTML5 support.</p></div>
    <div><a href=\"javascript:void(upload_again())\">".text('Upload_more_files')."</a></div>
    <br/><br/>
    <div id=\"ajax-results\" style=\"height:300px; border:#ccc solid 3px; overflow:auto; padding:5px;\"></div>
    </form>
    ";
  //------------------- draw list of files - end -------------------------------
//------------------- file manager - end ---------------------------------------

$input_vars['page_title']  = $this_site_info['title'] .' - '. $text['List_of_files'];
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['List_of_files'];
//--------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>" ,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
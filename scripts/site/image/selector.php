<?php
// image selector for HTMLAREA editor

global $main_template_name;
$main_template_name = '';
//------------------- check site id - begin ------------------------------------
  $site_id=checkInt($input_vars['site_id']);
  $this_site_info=\e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
  if(checkInt($this_site_info['id'])<=0)
  {
     echo $text['Site_not_found'];
     return 0;
  }
  //prn('$this_site_info',$this_site_info);
  $page_content="<h1>{$this_site_info['title']} - {$text['select_site_image']}</h1>";
//------------------- check site id - end --------------------------------------


$obj_name = 'page_content';
//------------------- get list of files - begin --------------------------------
  $page_content.="
  <script type=\"text/javascript\">
  <!-- 
   
  function insert_image(image_url)
  {
     var cc=window.opener;
     if(cc)
     {
       cc.editor_insertHTML('$obj_name', '<img src=\"' + image_url + '\">');
     }
     window.close();
  }
  // -->
  </script>
  ";
  
  



  // get site root directory
     $site_dir=\e::config('SITES_ROOT')."/".$this_site_info['dir'];

  // get site root url     
     $this_site_info['url'] = ereg_replace('/$','',$this_site_info['url']);

  // get list of files
     $filelist=\core\fileutils::ls_r($site_dir);
     // prn($filelist);


  // ----------------- upload files - begin ------------------------------------
  // prn($_FILES);
    if(is_array($_FILES))
    {

      $upload_dir = $site_dir.'/'.$input_vars['dirname'];

      if(!is_dir($upload_dir)) $upload_dir=$site_dir;
      foreach($_FILES as $key=>$val)
      {
         $image_url=\core\fileutils::upload_file($_FILES[$key],$upload_dir);
         $file_is_uploaded=true;
         clearstatcache();
        // ----------------------- insert image - begin ------------------------
          if($file_is_uploaded)
          {
            $image_url=$this_site_info['url'].'/'.$input_vars['dirname'].'/'.$image_url;
            $page_content.="
            <script type=\"text/javascript\">
            <!-- 
            insert_image('{$image_url}');
            // -->
            </script>
            ";
            echo $page_content;
            return '';
          }
        // ----------------------- insert image - end --------------------------
      }
    }
  //------------------ upload files - end --------------------------------------


  // get list of directories
     $dir_list  = Array();
     foreach($filelist as $obj)
     {
        $fname=str_replace($site_dir,'',$obj);
        if(is_dir($obj))
        {
           $dir_list[$fname]=(strlen($fname)==0)?'/':$fname;
        }
     }



  // ----------------- draw upload form - begin --------------------------------
     $page_content.="
     <h2>{$text['Upload']}</h2>
     <form action=index.php enctype=\"multipart/form-data\" method=post>
     <input type=hidden name=action value=\"site/image/selector\">
     <input type=hidden name=site_id value=\"{$site_id}\">
     <table>
     <tr>
     <td><b>{$text['Upload_image']}</b></td>
     <td><input type=\"file\" name=userfile></td>
     </tr>
     <tr>
     <td><b>{$text['into']}</b></td>
     <td><select name=\"dirname\">".
     draw_options($input_vars['dirname'],$dir_list).
    "</select>
     </td>
     </tr>
     <tr>
     <td></td>
     <td align=right><input type=submit value=\"OK\"></td>
     </tr>
     </table>
     </form>
     <h2>{$text['Choose_image_below']}</h2>
     ";
  // ----------------- draw upload form - end ----------------------------------

  // ----------------- show available images - begin ---------------------------
  // if file is not uploaded

  //$filelist=$filelist['files'];
  //prn($filelist); 
  $ext_list=explode(',',image_file_extensions);
  foreach($filelist as $file)
  {
    if(in_array(strtolower(\core\fileutils::file_extention($file)),$ext_list))
    { 
      $fpath   = str_replace($site_dir.'/','',$file);
      $img_src = $this_site_info['url'].'/'.$fpath;
      $page_content.="<img src=\"{$img_src}\" style='margin:10px; border:solid 1px blue;' onclick=\"insert_image('{$img_src}')\" alt=\"{$text['Click_image_to_insert_it_into_page']}\">\n";
      //prn($file);
    }
  }
  // ----------------- show available images - begin ---------------------------


echo $page_content;

// remove from history
   nohistory($input_vars['action']);



?>
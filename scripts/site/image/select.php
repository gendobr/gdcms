<?php
/*
  Select one of site images
  Argument is $site_id - site identifier, integer, mandatory
  called from advanced HTML editor
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/

global $main_template_name;
$main_template_name = '';

//------------------- check site id - begin ------------------------------------
  $site_id=checkInt($input_vars['site_id']);
  $this_site_info=\e::db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  if(checkInt($this_site_info['id'])<=0)
  {
     echo $text['Site_not_found'];
     return 0;
  }
  //prn('$this_site_info',$this_site_info);
  $page_content="<h1>{$this_site_info['title']} - {$text['select_site_image']}</h1>";
//------------------- check site id - end --------------------------------------

//------------------- get list of files - begin --------------------------------
  $page_content.="
  <script type=\"text/javascript\">
  <!-- 
   
  function insert_image(image_url)
  {
     var cc=window.opener;
     if(cc)
     {
       var lay = cc.document.getElementById('txtFileName');
       if(lay)
       {
         lay.value = image_url;
       }
     }
     window.close();
  }
  // -->
  </script>
  ";

  $site_dir=\e::config('SITES_ROOT')."/".$this_site_info['dir'];
  $filelist=\core\fileutils::ls_r($site_dir);
  // $filelist=$filelist['files'];
  // prn($filelist); 
  $ext_list=explode(',',image_file_extensions);
  foreach($filelist as $file)
  {
    if(in_array(strtolower(\core\fileutils::file_extention($file)),$ext_list))
    { 
      $fpath   = str_replace($site_dir.'/','',$file);
      $img_src = sites_root_URL.str_replace('//','/',"/{$this_site_info['dir']}/{$fpath}");
      //prn($fpath,$img_src);
      $page_content.="<img src=\"{$img_src}\" height=200px style='margin:10px; border:solid 1px blue;' onclick=\"insert_image('{$img_src}')\" alt=\"{$text['Click_image_to_insert_it_into_page']}\">\n";
      //prn($file);
    }
  }
//------------------- get list of files - end ----------------------------------

echo $page_content;


// remove from history
   nohistory($input_vars['action']);


?>
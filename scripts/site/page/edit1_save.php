<?php
  //prn('Saving ...');
  //----------------- check values - begin -------------------------------------
    $all_is_ok=true;
    $message='';

    //--------------- check page title - begin ---------------------------------
    $this_page_info['title']=strip_tags($input_vars['page_title']);
    if(strlen($this_page_info['title'])==0)
    {
      $message.="{$text['ERROR']} : {$text['Page_title_is_empty']}<br>\n";
      $all_is_ok=false;
    }
    //--------------- check page title - end -----------------------------------


    //------------------ check language - begin --------------------------------
    $lng=$input_vars['page_lang'];
    if($input_vars['page_lang']!=$this_page_info['lang'])
    {
      //-------------------- get existing page languages - begin ---------------
        $query="SELECT lang FROM {$table_prefix}page WHERE id={$this_page_info['id']}";
        $tmp=\e::db_getrows($query);
        // prn($tmp);
        $existins_langs=Array();
        foreach($tmp as $ln) $existins_langs[]=$ln['lang'];
      //-------------------- get existing page languages - end -----------------

      //-------------------- get available languages - begin -------------------
        $existins_langs[]='';
        $query="SELECT id
                FROM {$table_prefix}languages
                WHERE is_visible=1 AND id NOT IN('".join("','",$existins_langs)."')";
        $tmp=\e::db_getrows($query);
        $avail_lang=Array();
        foreach($tmp as $ln) $avail_lang[]=$ln['id'];
      //-------------------- get available languages - end ---------------------

      if(!in_array($input_vars['page_lang'],$avail_lang))
      {
          $message.="{$text['ERROR']} : {$text['Page_in_selected_language_already_axists']}<br>\n";
          $all_is_ok=false;
          $lng=$this_page_info['lang'];
      }
    }
    //------------------ check language - end ----------------------------------





    //------------------ check page abstract - begin ---------------------------
      if(strlen($input_vars['page_abstract'])>512)
      {
          $message.="{$text['ERROR']} : {$text['Page_abstract_is_too_long']}<br>\n";
          $all_is_ok=false;
      }
      $this_page_info['abstract'] = shorten($input_vars['page_abstract'],512);
    //------------------ check page content - end ------------------------------




    //------------------ check page content - begin ----------------------------
      $this_page_info['content']=$input_vars['page_content'];
    //------------------ check page content - end ------------------------------

    //------------------ upload page from file - begin -------------------------
      if(isset($_FILES['page_upload']) && $_FILES['page_upload']['size']>0)
      {
          $this_page_info['content'] = join('',file($_FILES['page_upload']['tmp_name']));
          if(eregi('<body',$this_page_info['content'])) {
            $this_page_info['content']=stristr($this_page_info['content'], '<body');
          }
          if(eregi('</body',$this_page_info['content'])) {
              $this_page_info['content']=eregi_replace('</body(.|\n|\r)*','</body>',$this_page_info['content']);
          }

          unset($_FILES['page_upload']);
      }
    //------------------ upload page from file - end ---------------------------


    $this_page_info['site_id']=$this_site_info['id'];
    $this_page_info['cense_level']=(isset($input_vars['page_cense_level']))?( (int)$input_vars['page_cense_level'] ):0;
    $this_page_info['last_change_date']=date('Y-m-d h:i:s');

    if(!isset($input_vars['page_is_under_construction'])) $input_vars['page_is_under_construction']=0;
    $this_page_info['is_under_construction']=($input_vars['page_is_under_construction']==1)?1:0;
    $this_page_info['is_home_page']=($input_vars['page_is_home_page']==1)?1:0;


    //-------------------------- check page path - begin -----------------------
      // check if path syntax is correct
         // site root dir
            $root=\e::config('SITES_ROOT').'/'.preg_replace("/^\\/+|\\/+$/",'',$this_site_info['dir']);

         // old page path
            $old_page_root=$root.'/'.preg_replace("/^\\/+|\\/+$/",'',$this_page_info['path']);
            $old_page_root=preg_replace("/\\/+$/",'',$old_page_root);
            $dir =$old_page_root.'/'.$this_page_info['id'].'.'.$this_page_info['lang'].'.html';
            $dir2=$old_page_root.'/'.$this_page_info['page_file_name'];

         // new page paths
            $new_page_path=\core\fileutils::encode_dir_name(strip_tags($input_vars['page_path']));
            
            $new_file_name=trim($input_vars['page_file_name']);
            $new_file_name=preg_replace("/\.html\$/", '', $new_file_name);// ".html";
            $new_file_name=\core\fileutils::encode_dir_name($new_file_name);

            $new_dir =$root.'/'.preg_replace("/^\\/+|\\/+$/",'',$new_page_path).'/'.$this_page_info['id'].'.'.$this_page_info['lang'].'.html';
            $new_dir2=$root.'/'.preg_replace("/^\\/+|\\/+$/",'',$new_page_path).'/'.$new_file_name.'.html';




         $this_page_info['path']=$new_page_path;


         $this_page_info['page_file_name']=strlen($new_file_name)>0 ? "{$new_file_name}.html":'';

         if(strlen($this_page_info['page_file_name'])>0) {
            // ensure the page file path is unique
            $other_pages="SELECT count(*) as n_pages
                          FROM {$table_prefix}page
                          WHERE path='".\e::db_escape($this_page_info['path'])."'
                            AND page_file_name='".\e::db_escape($this_page_info['page_file_name'])."'
                            AND id<>{$this_page_info['id']}
                            AND site_id={$this_page_info['site_id']}
                          ";
            //prn($other_pages);
            $other_pages=\e::db_getonerow($other_pages);
            if($other_pages['n_pages']>0){
                $message.="{$text['ERROR']} : ".text('Page_choose_other_file_name')."<br>\n";
                $all_is_ok=false;
            }
            if(preg_match("/^template_/", $this_page_info['page_file_name'])){
                $message.="{$text['ERROR']} : ".text('Page_choose_other_file_name')."<br>\n";
                $all_is_ok=false;                
            }
         }else{
             $this_page_info['page_file_name']=  \core\fileutils::encode_dir_name($this_page_info['title']).'.html';
         }

    //-------------------------- check page path - end -------------------------

    // read page_meta_tags
    $this_page_info['page_meta_tags']=trim($input_vars['page_meta_tags']);

    $this_page_info['category_id']=(int)$input_vars['page_category'];
    //prn($this_page_info);
  //----------------- check values - end ---------------------------------------

  //----------------- save - begin ---------------------------------------------
    if($all_is_ok)
    {
       $message.="<font color=green>{$text['Page_saved_successfully']}</font><br>\n";

       if($this_page_info['is_home_page']==1)
       {
         $query="UPDATE {$table_prefix}page
                 SET    is_home_page=0
                 WHERE  site_id='{$this_page_info['site_id']}'";
         \e::db_execute($query);
       }

       $query="UPDATE {$table_prefix}page
               SET
                  lang='{$lng}'
                 ,site_id='{$this_page_info['site_id']}'
                 ,title='".\e::db_escape($this_page_info['title'])."'
                 ,path='".\e::db_escape($this_page_info['path'])."'
                 ,content='".\e::db_escape($this_page_info['content'])."'
                 ,abstract='".\e::db_escape($this_page_info['abstract'])."'
                 ,page_meta_tags='".\e::db_escape($this_page_info['page_meta_tags'])."'
                 ,page_file_name='".\e::db_escape($this_page_info['page_file_name'])."'
                 ,last_change_date='{$this_page_info['last_change_date']}'
                 ,is_under_construction={$this_page_info['is_under_construction']}
                 ,is_home_page={$this_page_info['is_home_page']}
                 ,cense_level = {$this_page_info['cense_level']}
                 ,category_id = {$this_page_info['category_id']}
       WHERE id='{$this_page_info['id']}' AND lang='{$this_page_info['lang']}'";
       //prn($query);
       \e::db_execute($query);

       $this_page_info['lang']=$lng;


       // mark if exported files should be deleted
       if($new_dir!=$dir || $new_dir2!=$dir2){
           // mark page file to be deleted during export
           //
           $delete_file=$this_page_info['delete_file']."\t$dir";
           if(strlen($this_page_info['page_file_name'])>0){
               $delete_file.="\t$dir2";
           }
           $delete_file=trim(join("\t",array_unique(explode("\t",$delete_file))));

           $query="UPDATE {$table_prefix}page
                   SET delete_file = '$delete_file'
                   WHERE id='{$this_page_info['id']}' AND lang='{$this_page_info['lang']}'";
           //prn($query);
           \e::db_execute($query);
       }

       # ------------------ send notification - begin --------------------------
         if(isset($_REQUEST['notify']))
         if(is_array($_REQUEST['notify']))
         {
           run('lib/mailing');
           run('lib/class.phpmailer');
           run('lib/class.smtp');
           $lnk=site_root_URL."/index.php?action=site/page/edit&page_id={$this_page_info['id']}&lang={$this_page_info['lang']}";
           foreach($this_site_info['managers'] as $mng)
           {
             if(isset($_REQUEST['notify'][$mng['id']]))
             {

                $mng_body="Dear {$mng['full_name']} <br/>\n<br/>\n<br/>\n".
                       " I have changed the page. <br/>\n".
                       " Please, review it.<br/>\n".
                       " <br/>\n".
                       " ==================================================<br/>\n".
                       " {$this_page_info['title']}<br/>\n".
                       " --------------------------------------------------<br/>\n".
                       " {$this_page_info['abstract']}<br/>\n".
                       " --------------------------------------------------<br/>\n".
                       " {$this_page_info['content']}<br/>\n".
                       " ==================================================<br/>\n".
                       " Click the link below to approve changes<br/>\n".
                       " <a href=$lnk>$lnk</a><br/>\n".
                       " <br/>\n".
                       " Yours faithfully <br/>\n".
                       " {$_SESSION['user_info']['full_name']}<br/>\n".
                       " {$_SESSION['user_info']['email']}<br/>\n".
                       " ";
                if(IsHTML!='1') $mng_body=wordwrap(strip_tags(ereg_replace('<br/?>',"\n",$mng_body)), 80, "\n");
                my_mail($mng['email'], site_root_URL.' : Page was changed', $mng_body);
             }
           }
         }
       # ------------------ send notification - end ----------------------------
    }
    else
    {
       $message="<font color=red>{$message}</font><br>\n";
    }
  //----------------- save - end -----------------------------------------------
header("Location: index.php?action=site/page/edit1&aed=1&page_id={$this_page_info['id']}&lang={$this_page_info['lang']}");
exit();
return $message;


?>
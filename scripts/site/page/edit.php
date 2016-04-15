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
  $page_id   = checkInt($input_vars['page_id']);

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
  //prn('$this_page_info',$this_page_info);
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
  $site_id=$this_site_info['id'];
  # prn('$this_site_info=',$this_site_info);


















//------------------- save new page info - begin -------------------------------
if(isset($input_vars['save_changes']))
if(strlen($input_vars['save_changes'])>0)
{
   //prn('Saving ...');
  //----------------- check values - begin -------------------------------------
    $all_is_ok=true;
    $message='';

    //--------------- check page title - begin ---------------------------------
    $this_page_info['title']=strip_tags($input_vars['page_title']);
    if(strlen($this_page_info['title'])==0)
    {
      $message.="{$text['ERROR']} : ".text('Page_title_is_empty')."<br>\n";
      $all_is_ok=false;
    }
    //--------------- check page title - end -----------------------------------
    // if(is_admin()) prn('1::::'.checkStr($this_page_info['content']));

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
        // prn($query);
        $tmp=\e::db_getrows($query);
        $avail_lang=Array();
        foreach($tmp as $ln) $avail_lang[]=$ln['id'];
        //prn($avail_lang);
      //-------------------- get available languages - end ---------------------

      if(!in_array($input_vars['page_lang'],$avail_lang))
      {
          $message.="{$text['ERROR']} : ".text('Page_in_selected_language_already_axists')."<br>\n";
          $all_is_ok=false;
          $lng=$this_page_info['lang'];
      }
    }
    //------------------ check language - end ----------------------------------





    //------------------ check page abstract - begin ---------------------------
    //if(strlen($input_vars['page_abstract'])>512)
    //{
    //    $message.="{$text['ERROR']} : ".text('Page_abstract_is_too_long')."<br>\n";
    //    $all_is_ok=false;
    //}
    //$this_page_info['abstract'] = shorten($input_vars['page_abstract'],512);
    $this_page_info['abstract'] = $input_vars['page_abstract'];
    //------------------ check page content - end ------------------------------





    //------------------ check page content - begin ----------------------------

      $this_page_info['content']=$input_vars['page_content'];
    //------------------ check page content - end ------------------------------






    //------------------ upload images - begin ---------------------------------
    # prn($_FILES);
      if(is_array($_FILES))
      if(count($_FILES)>0)
      {
        if(isset($_FILES['page_upload']))
        if($_FILES['page_upload']['size']>0)
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
        if(isset($_FILES['file']))
        if(isset($_FILES['file']['name']))
        if(is_array($_FILES['file']['name']))
        foreach($_FILES['file']['name'] as $key=>$val)
        {
          if(basename($key)==basename($val))
          {
            # prn($_FILES['file']['name'][$key]);
            # --------------- check if directory exists - begin ----------------
              $dirs=explode('/',dirname($key));
              # prn($dirs);
              $pt=\e::config('SITES_ROOT')."/{$this_site_info['dir']}";
              foreach($dirs as $dr)
              {
                if(strlen($dr)>0)
                {
                  $pt.='/'.$dr;
                  if(!is_dir($pt)) if(!@mkdir($pt)) @mkdir($pt,755);
                }
              }
            # --------------- check if directory exists - end ------------------
            @move_uploaded_file($_FILES['file']['tmp_name'][$key] , $pt."/".$_FILES['file']['name'][$key]);
          }
        }
      }
    //------------------ upload images - end -----------------------------------






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
            $new_file_name=  preg_replace("/\.html\$/", '', $new_file_name);// ".html";
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


    //------------------ check page content - end ------------------------------

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
       //if(is_admin()) prn(checkStr($this_page_info['content']));
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

}
//------------------- save new page info - end ---------------------------------



















//------------------- check if all images exist - begin ------------------------
      $img_root_url=sites_root_URL.'/'.$this_site_info['dir'].'/';
      $parsed_html=replace_src($this_page_info['content'],$img_root_url);
      //$this_page_info['content']=  $parsed_html['html'];

      $required_images=Array();
      clearstatcache();
      foreach($parsed_html['src'] as $fname)
      {
        if(!file_exists(\e::config('SITES_ROOT')."/{$this_site_info['dir']}/{$fname}"))
        {
          $required_images[]=$fname;
        }
      }
      if(count($required_images)>0)
      {
        $file_upload_form="

          <h3>{$text['The_page_needs_files']} :</h3>

        ";
        foreach($required_images as $key=>$val)
        {
           $file_upload_form.="
           <div class=label>{$val}</div>
           <div class=big>
             <input type='file' name='file[{$val}]' style='width:300px;'>
           </div>
           ";
        }
        $file_upload_form.="
        <div><input type='submit' value='{$text['Upload_files']}'></div>
        ";
      }
//------------------- check if all images exist - begin ------------------------


//------------------- draw form - begin ----------------------------------------
   $notify_managers_form='';
   foreach($this_site_info['managers'] as $mn)
   {
     $notify_managers_form .="<input type=checkbox name='notify[{$mn['id']}]' style='width:auto;'> {$mn['full_name']}<br/>";
   }
   if(strlen($notify_managers_form)>0)
     $notify_managers_form="<div class=label>{$text['Send_notification_to']}</div><div class=big>{$notify_managers_form}</div>";

  if(!isset($message)) $message='';


  $input_vars['aed']=(isset($input_vars['aed']))?( (int)$input_vars['aed']):0;
  $input_vars['page_title']   = $text['Editing_page'];
  $input_vars['page_header']  = $text['Editing_page'];
  $input_vars['page_content'] = '';



 # ------------------------ list of categories - begin -------------------------
    $query="SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>0 AND site_id={$this_page_info['site_id']} ORDER BY start ASC";
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
  <input type=hidden name=aed  value=\"{$input_vars['aed']}\">



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

  <div class=label>{$text['Page_Title']} :</div>
  <div class=big>
     <input type=text MAXLENGTH=512 name=page_title value=\"".checkStr($this_page_info['title'])."\">
  </div>




  <span class=blk13>
  <div class=label>{$text['Page_Language']}:</div>
  <div class=big>
    <select name=page_lang>".
    draw_options($this_page_info['lang'],\e::db_getrows("SELECT id, name FROM {$table_prefix}languages WHERE is_visible=1 ORDER BY name ASC;"))
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
     <select name=page_category id=page_category>
    <option value=''></option>".
    draw_options($this_page_info['category_id'],$list_of_categories)
    ."</select>
  </div>





  <div class=label>{$text['Abstract']} :</div>
  <div class=big>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
      <textarea name=page_abstract
                id=page_abstract
                wrap='virtual'
                tabindex='3'
                class='wysiswyg'
                style=\"width:100%; height:100px;\">".
      checkStr($this_page_info['abstract'])
      ."</textarea>
  </div>


  <div class=label>".text('Contents')." :</div>
  <div class=big>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
  <textarea name=page_content
            id=page_content
            wrap='virtual'
            tabindex='3'
            class='wysiswyg'
            style=\"width:100%; height:400px;\">".
  checkStr($this_page_info['content'])
  ."</textarea>
  </div>
  <div class=big>
    <input type=submit value=\"{$text['Save']}\" >
  </div>

  <!-- 
  <div class=label>".text('Upload_page')." :</div>
  <div class=big>
    <input type=\"file\" name=page_upload style='width:60%;'><input type=submit value=\"".text('Upload')."\">
  </div>
  -->
  <div class=label>{$text['Page_Path']} :</div>
  <div class=big>
     <input type=text MAXLENGTH=128 name=page_path value=\"".checkStr($this_page_info['path'])."\">
  </div>
  <div class=label>".text('Page_file')." :</div>
  <div class=big>
     <input type=text MAXLENGTH=128 name=page_file_name value=\"".checkStr($this_page_info['page_file_name'])."\">
  </div>
  <div class=label>".text('Page_meta_tags')." :</div>
  <div class=big>
    <script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/meta-tags-insert.js\"></script>
    <script type=\"text/javascript\">
    $(document).ready(function(){
       metaTagsButtons('page_meta_tags');
    });
    </script>
     <textarea style=\"width:100%; height:150px;\" name=\"page_meta_tags\" id=\"page_meta_tags\">".checkStr($this_page_info['page_meta_tags'])."</textarea>
  </div>
{$notify_managers_form}

  {$file_upload_form}

  <div class=big>
    <input type=submit value=\"{$text['Save']}\" >
    <input type=reset  value=\"{$text['Reset']}\">
  </div>

  </form>



  <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/select2/css/select2.min.css\" />

  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/markitup.js\"></script>
  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/choose_links.js\"></script>
  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/select2/js/select2.full.min.js\"></script>
  <script type=\"text/javascript\">
      $(function(){
          init_links();
          $('textarea.wysiswyg').markItUp(mySettings);
          $('#page_category').select2();
      });
  </script>
  ";
//------------------- draw form - end ------------------------------------------

//----------------------------- context menu - begin ---------------------------
  $input_vars['page_menu']['page']=Array('title'=>$text['Page_menu'],'items'=>Array());
  $input_vars['page_menu']['page']['items'] = menu_page($this_page_info);

    $sti=$text['Site'].' "'. $this_site_info['title'].'"';
    $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());

  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);

//----------------------------- context menu - end -----------------------------

?>
<?php
# ------------------- save news - begin ----------------------------------------
if(isset($input_vars['save_changes']))
if(strlen($input_vars['save_changes'])>0)
{
  if($debug) prn('Saving ...');
  # ----------------- check values - begin -------------------------------------
    $all_is_ok=true;
    $message='';

    # --------------- check page title - begin ---------------------------------
    $this_news_info['title']=strip_tags($input_vars['news_title']);
    if(strlen($this_news_info['title'])==0)
    {
      $message.="{$text['ERROR']} : {$text['News_title_is_empty']}<br>\n";
      $all_is_ok=false;
    }
    # --------------- check page title - end -----------------------------------

    # ------------------ check language - begin --------------------------------
    $lng=$input_vars['news_lang'];
    if($input_vars['news_lang']!=$this_news_info['lang'])
    {
      # -------------------- get existing page languages - begin ---------------
        $query="SELECT lang FROM {$table_prefix}news WHERE id={$this_news_info['id']}";
        $tmp=db_getrows($query);
        // prn($tmp);
        $existins_langs=Array();
        foreach($tmp as $ln) $existins_langs[]=$ln['lang'];
      # -------------------- get existing page languages - end -----------------
      # -------------------- get available languages - begin -------------------
        $existins_langs[]='';
        $query="SELECT id
                FROM {$table_prefix}languages
                WHERE is_visible=1 AND id NOT IN('".join("','",$existins_langs)."')";
        // prn($query);
        $tmp=db_getrows($query);
        $avail_lang=Array();
        foreach($tmp as $ln) $avail_lang[]=$ln['id'];
        //prn($avail_lang);
      # -------------------- get available languages - end ---------------------

      if(!in_array($input_vars['news_lang'],$avail_lang))
      {
          $message.="{$text['ERROR']} : {$text['News_in_selected_language_already_axists']}<br>\n";
          $all_is_ok=false;
          $lng=$this_news_info['lang'];
      }
    }
    # ------------------ check language - end ----------------------------------


    # ------------------ check page abstract - begin ---------------------------
      if($debug) prn('NEWS ABSTRACT:',$input_vars['page_abstract']);
    # $this_news_info['abstract']=  shorten(strip_tags($input_vars['page_abstract']),1024);
      $this_news_info['abstract']=  $input_vars['page_abstract'];
    # ------------------ check page abstract - end -----------------------------

    # ------------------ check page content - begin ----------------------------
      //$img_root_url=$this_site_info['url'];
      if($debug) prn('NEWS CONTENT:',$input_vars['page_content']);
      $img_root_url=sites_root_URL.'/'.$this_site_info['dir'].'/';
      $parsed_html=replace_src($input_vars['page_content'],$img_root_url);
      $this_news_info['content']=  $parsed_html['html'];
      //prn($parsed_html['src']);
      //$input_vars['page_content'];
    # ------------------ check page content - end ------------------------------

    # ------------------ upload images - begin ---------------------------------
    # prn($_FILES);
      if(is_array($_FILES))
      if(count($_FILES)>0)
      {
        foreach($_FILES['file']['name'] as $key=>$val)
        {
          if(basename($key)==basename($val))
          {
            # prn($_FILES['file']['name'][$key]);
            # --------------- check if directory exists - begin ----------------
              $dirs=explode('/',dirname($key));
              # prn($dirs);
              $pt=sites_root."/{$this_site_info['dir']}";
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
    # ------------------ upload images - end -----------------------------------

    $this_news_info['site_id']=$this_site_info['id'];
    $this_news_info['cense_level']=(int)$input_vars['news_cense_level'];
    # $this_news_info['last_change_date']=date('Y-m-d h:i:s');

    # --------------------- news date - begin ----------------------------------
    # if($this_news_info['id']==42) prn($_REQUEST);
      run('lib/adodb-time.inc');
    #  $this_news_info['last_change_date']=
    #           substr('0000'.(int)$_REQUEST['date_posted']['year'],-4).'-'.
    #           substr('00'.(int)$_REQUEST['date_posted']['month'] ,-2).'-'.
    #           substr('00'.(int)$_REQUEST['date_posted']['day']   ,-2).' '.
    #           substr('00'.(int)$_REQUEST['date_posted']['hour']  ,-2).':'.
    #           substr('00'.(int)$_REQUEST['date_posted']['minute'],-2).':'.
    #           '00';
    $this_news_info['last_change_date']=adodb_mktime(
         (int)$_REQUEST['date_posted']['hour']
        ,(int)$_REQUEST['date_posted']['minute']
        ,0
        ,(int)$_REQUEST['date_posted']['month']
        ,(int)$_REQUEST['date_posted']['day']
        ,(int)$_REQUEST['date_posted']['year']
    );
    $this_news_info['last_change_date']=adodb_date('Y-m-d H:i:s',$this_news_info['last_change_date']);
    # $this_news_info['last_change_date']=date('Y-m-d H:i:s');
    # --------------------- news date - end ------------------------------------

    # --------------------- expiration date - begin ----------------------------
      if(    strlen($_REQUEST['expiration_date_posted']['month'])>0
          && strlen($_REQUEST['expiration_date_posted']['day'])>0
          && strlen($_REQUEST['expiration_date_posted']['year'])>0
      )
      {
         $this_news_info['expiration_date']=adodb_mktime(
            (int)$_REQUEST['expiration_date_posted']['hour']
            ,(int)$_REQUEST['expiration_date_posted']['minute']
            ,0
            ,(int)$_REQUEST['expiration_date_posted']['month']
            ,(int)$_REQUEST['expiration_date_posted']['day']
            ,(int)$_REQUEST['expiration_date_posted']['year']
         );
         $this_news_info['expiration_date']=adodb_date('Y-m-d H:i:s',$this_news_info['expiration_date']);
      }
      else $this_news_info['expiration_date']='';
      //prn('$this_news_info[expiration_date]',$this_news_info['expiration_date']);
    # --------------------- expiration date - end ------------------------------

    if(isset($input_vars['news_category']) && is_array($input_vars['news_category']))
    {
    	$tmp=array_values($input_vars['news_category']);
        $this_news_info['category_id']=(int)$tmp[0];
        $query=Array();
        foreach($tmp as $cat) $query[]=(int)$cat;
        db_execute("DELETE FROM  {$table_prefix}news_category WHERE news_id={$this_news_info['id']}");
        if(count($query)>0)
        {
          $query="INSERT INTO {$table_prefix}news_category(news_id ,category_id)
                  SELECT {$this_news_info['id']} as news_id, category_id
                  FROM {$table_prefix}category
                  WHERE start>0
                    AND site_id={$site_id}
                    AND category_id in(".join(',',$query).")";
          db_execute($query);
        }
    }

    // ----------------- clear tags - begin ------------------------------------
    $tags=preg_split("/,|;|\\./", $input_vars['tags']);
    $cnt=count($tags);
    for($i=0; $i<$cnt;$i++){
        $tags[$i]=trim($tags[$i]);
        $tags[$i]=preg_replace("/ +/", " ", $tags[$i]);
    }
    $this_news_info['tags']=join(',',$tags);
    // ----------------- clear tags - end --------------------------------------
    // 
    // ----------------- check news_code - begin -------------------------------
    // news_code must be unique
    $this_news_info['news_code']=encode_dir_name(trim($input_vars['news_code']));
    if(strlen($this_news_info['news_code'])>0){
        $query="SELECT count(id) as n_news FROM {$table_prefix}news WHERE id<>{$this_news_info['id']} AND news_code='".DbStr($this_news_info['news_code'])."'";
        $n_other_news = db_getonerow($query);
        if($n_other_news['n_news']>0){
          $message.="{$text['ERROR']} : ".text('News_choose_other_code')."<br>\n";
          $all_is_ok=false;
        }
    }else{
        $this_news_info['news_code']=encode_dir_name(trim($this_news_info['title']));
        
        $query="SELECT count(id) as n_news FROM {$table_prefix}news WHERE id<>{$this_news_info['id']} AND news_code='".DbStr($this_news_info['news_code'])."'";
        $n_other_news = db_getonerow($query);
        if($n_other_news['n_news']>0){
            $this_news_info['news_code'].=$this_news_info['id'].'-'.$this_news_info['lang'];
        }
    }
    
    // ----------------- check news_code - end ---------------------------------
    
    // copy news_meta_info
    $this_news_info['news_meta_info']=$input_vars['news_meta_info'];
    
    $this_news_info['news_extra_1']=$input_vars['news_extra_1'];
    
    $this_news_info['news_extra_2']=$input_vars['news_extra_2'];
    
    
    # prn($this_news_info);
  //----------------- check values - end ---------------------------------------

  //----------------- save - begin ---------------------------------------------
    if($all_is_ok)
    {
       $message.="<font color=green>{$text['Page_saved_successfully']}</font><br>\n";

       $query="UPDATE {$table_prefix}news
               SET
                  lang='{$lng}'
                 ,site_id='{$this_news_info['site_id']}'
                 ,title='".DbStr($this_news_info['title'])."'
                 ,abstract='".DbStr($this_news_info['abstract'])."'
                 ,content='".DbStr($this_news_info['content'])."'
                 ,last_change_date='{$this_news_info['last_change_date']}'
                 ,expiration_date =".($this_news_info['expiration_date']==''?'null':"'{$this_news_info['expiration_date']}'")."
                 ,cense_level={$this_news_info['cense_level']}
                 ,category_id = {$this_news_info['category_id']}
                 ,tags='".DbStr($this_news_info['tags'])."'
                 ,news_meta_info='".DbStr($this_news_info['news_meta_info'])."'
                 ,news_code='".DbStr($this_news_info['news_code'])."'
                 ,news_extra_1='".DbStr($this_news_info['news_extra_1'])."'
                 ,news_extra_2='".DbStr($this_news_info['news_extra_2'])."'
       WHERE id='{$this_news_info['id']}' AND lang='{$this_news_info['lang']}'";
       #if($debug)
       //prn($query);
       db_execute($query);

       # ------------------ rebuild tags - begin -------------------------------
         db_execute("DELETE FROM {$table_prefix}news_tags WHERE news_id={$this_news_info['id']} AND lang='{$this_news_info['lang']}'");
         if(strlen(trim($this_news_info['tags']))>0)
         {
           // $query=explode(',',$this_news_info['tags']);
           $query=preg_split("/,|;|\\./", $this_news_info['tags']);
           $cnt=count($query);
           if($cnt>0)
           {
             for($i=0;$i<$cnt;$i++)
             {
                 $query[$i]=trim($query[$i]);
                 if(strlen($query[$i])>0) $query[$i]="({$this_news_info['id']},'{$lng}','".DbStr($query[$i])."')";
             }
             $query="INSERT INTO {$table_prefix}news_tags(news_id,lang,tag) VALUES".join(',',$query);
             db_execute($query);
           }
         }
       # ------------------ rebuild tags - end ---------------------------------

       $this_news_info['lang']=$lng;


       # ------------------ send notification - begin --------------------------
         if(isset($_REQUEST['notify']))
         if(is_array($_REQUEST['notify']))
         {
           run('lib/mailing');
           run('lib/class.phpmailer');
           run('lib/class.smtp');

           function allowed_chars($str){return wordwrap(preg_replace('/[^ 1234567890\-_абвгдежзийклмнопрстуфхцчшщюэяьъё.\?\!АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧЩАЮЯЬЪQWERTYUIOPASDFGHJKL;:\'ZXCVBNM,\/qwertyuiopasdfghjklzxcvbnmіїєІЇЄґҐ@%]/',' ',strip_tags($str)),60,'<br>');}
           $tmp_news_info=Array();
           $tmp_news_info['title']=$this_news_info['title'];
           $tmp_news_info['last_change_date']=date('d.m.Y H:i:s',strtotime($this_news_info['last_change_date']));
           $tmp_news_info['abstract']=allowed_chars($this_news_info['abstract']);
           $tmp_news_info['content']=allowed_chars($this_news_info['content']);
           //prn($tmp_news_info);
           foreach($this_site_info['managers'] as $mng)
           {
             #prn($mng);
             if(isset($_REQUEST['notify'][$mng['id']]))
             {

                $lnk=site_root_URL."/index.php?action=news/edit&news_id={$this_news_info['id']}&lang={$this_news_info['lang']}";
                $mng_body="Dear {$mng['full_name']} <br/>\n<br/>\n<br/>\n".
                       " I have changed the page. <br/>\n".
                       " Please, review it.<br/>\n".
                       " <br/>\n".
                       " ==================================================<br/>\n".
                       " {$tmp_news_info['title']}<br/>\n".
                       " --------------------------------------------------<br/>\n".
                       " {$tmp_news_info['last_change_date']}<br/>\n".
                       " --------------------------------------------------<br/>\n".
                       " {$tmp_news_info['abstract']}<br/>\n".
                       " --------------------------------------------------<br/>\n".
                       " {$tmp_news_info['content']}<br/>\n".
                       " ==================================================<br/>\n".
                       " Click the link below to approve changes<br/>\n".
                       " <a href=$lnk>$lnk</a><br/>\n".
                       " <br/>\n".
                       " Yours faithfully <br/>\n".
                       " {$_SESSION['user_info']['full_name']}<br/>\n".
                       " {$_SESSION['user_info']['email']}<br/>\n".
                       " ";
                if(IsHTML!='1') $mng_body=wordwrap(strip_tags(preg_replace('/<br\/?>/i',"\n",$mng_body)), 80, "\n");
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
//------------------- save news - end ------------------------------------------



?>
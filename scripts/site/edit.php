<?php
/**
  Editing site properties
*/

//if(!is_admin()) return 0;

  run('site/menu');
//------------------- old site info - begin ------------------------------------
  global $this_site_info;
  $site_id = (int)$input_vars['site_id'];
  $this_site_info = get_site_info($site_id);


  //prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  if(!is_admin())
  {
     $input_vars['page_title']   =
     $input_vars['page_header']  =
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
  $GLOBALS['this_site_info']=$this_site_info;
//------------------- old site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
$this_site_info['admin_level']=get_level($site_id);
$max_site_level=db_getonerow("SELECT max(level) as maxlevel FROM {$table_prefix}site_user WHERE site_id={$site_id} ");
$max_site_level=$max_site_level['maxlevel'];
// prn('$max_site_level='.$max_site_level,"this_site_info[admin_level]=".$this_site_info['admin_level']);
if($this_site_info['admin_level']<$max_site_level && !is_admin())
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------


//------------------- edit properties -- begin ---------------------------------
  run('lib/class_db_record_editor');
  run('lib/class_db_record_editor_extended');

  class edbre extends extended_db_record_editor
  {
    function check_form_values()
    {
      //prn($this->field['']);
      global $text,$db,$table_prefix,$this_site_info;
      $all_is_ok = true;

        if(is_admin())
        {
        //-------------------- site directory uniqueness - begin ---------------
          if(strlen($this->field['site_dir']['value'])>0)
          {
             $query="SELECT count(*) AS ns FROM {$table_prefix}site WHERE dir='".$this->field['site_dir']['value']."' AND id<>'".checkInt($this->id)."'";
             // prn($query);
             $count_site=db_getonerow($query);
             $count_site=$count_site['ns'];
             if($count_site>0)
             {
               $this->messages.= " <b><font color=red> {$text['ERROR']} : {$text['Site_already_exists']}</font></b><br>\n";
               $all_is_ok = false;
             }
          }
        // ------------------- site directory uniqueness - end -----------------
        // -------------------- protect site root dir - begin ------------------
           $site_dir_path = sites_root.'/'.$this->field['site_dir']['value'];
           //prn($site_dir_path,substr($site_dir_path,0,strlen(local_root)),local_root);
           if(substr($site_dir_path,0,strlen(local_root))==local_root)
           {
              $this->messages.= " <b><font color=red> {$text['ERROR']} : Change directory name</font></b><br>\n";
              $all_is_ok = false;
           }
        // -------------------- protect site root dir - begin ------------------

        // -------------------- protect all sites dir - begin ------------------
           $tmp = $site_dir_path = trim($this->field['site_dir']['value']);
           $tmp = preg_replace("/^\\/+/",'',$tmp);
           $tmp = preg_replace("/\\/+$/",'',$tmp);
           $tmp = str_replace('.','',$tmp);
           $tmp = preg_replace("/[^0-9a-z\\/_.-]/i",'',$tmp);
           //prn($site_dir_path,substr($site_dir_path,0,strlen(local_root)),local_root);
           if($site_dir_path!=$tmp || strlen($tmp)==0)
           {
              $this->messages.= " <b><font color=red> {$text['ERROR']} : Type in correct directory name [a-z0-9/_-]+</font></b><br>\n";
              $all_is_ok = false;
           }
        // -------------------- protect all sites dir - end --------------------

        //-------------------- form URL - begin ------------------------------
          if(strlen($this->field['site_url']['value'])==0)
          {
              $this->field['site_url']['form_element_value'] =
              $this->field['site_url']['value'] = sites_root_URL.'/'.
                                                     $this->field['site_dir']['value'].'/';
           }
         //-------------------- form URL - end --------------------------------
        }
        else
        {
        //prn($GLOBALS);
        //-------------------- form URL - begin ------------------------------
          if(strlen($this->field['site_url']['value'])==0)
          {
              $this->field['site_url']['form_element_value'] =
              $this->field['site_url']['value'] = sites_root_URL.'/'.
                                                     $GLOBALS['this_site_info']['dir'].'/';
          }
        //-------------------- form URL - end --------------------------------
        }

      //-------------------- check template name - begin -----------------------
        if(strlen($this->field['site_template']['value'])==0)
        {
           $this->messages.= " <b><font color=red> {$text['ERROR']} : {$text['Site_template_is_empty']}</font></b><br>\n";
           $all_is_ok = false;
        }
      //-------------------- check template name - end -------------------------


      return $all_is_ok;
    }
  }



  $rep=new edbre;
  $rep->use_db($db);
  $rep->debug=false;
  $rep->set_table("{$table_prefix}site");
  $rep->exclude='^site_id$';
  $rep->add_field( 'id'
                  ,'site_id'
                  ,'integer:hidden=yes&default='.$site_id
                  ,'#');

  if(is_admin())
  $rep->add_field( 'dir'
                  ,'site_dir'
                  ,'string:maxlength=64&required=no'
                  ,$text['Site_directory']);

  $rep->add_field( 'title'
                  ,'site_title'
                  ,'string:maxlength=255'
                  ,$text['Site_title']);

  $rep->add_field( 'url'
                  ,'site_url'
                  ,'string:maxlength=255'
                  ,$text['Site_URL']);


  if(is_admin())
  $rep->add_field( 'cense_level'
                  ,'site_cense_level'
                  ,'integer:min=1&default=1&max=99'
                  ,$text['Site_Cense_Level']);


  //-------------------- list of templates - begin -----------------------------
    run('lib/file_functions');
    $template_files = ls(template_root);
    $template_files = $template_files['files'];
    $template_list  = Array();
    foreach($template_files as $fname)
    {
      if(preg_match("/\\.html?/i", $fname))
      {
        $tmp = preg_replace("/\\.html?/i", '', $fname);
        $template_list[] = $tmp.'='.$tmp;
      }
    }
  //-------------------- list of templates - end -------------------------------
  $rep->add_field( 'template'
                  ,'site_template'
                  ,'enum:'.join('&',$template_list)
                  ,$text['Site_Template']);

  $rep->add_field( 'is_poll_enabled'
                  ,'site_is_poll_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,$text['Poll_enabled']);

  $rep->add_field( 'is_gallery_enabled'
                  ,'site_is_gallery_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,$text['Image_gallery_enabled']);

  $rep->add_field( 'is_gb_enabled'
                  ,'site_is_gb_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,$text['Guestbook_enabled']);

  $rep->add_field( 'is_news_line_enabled'
                  ,'site_is_news_line_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,$text['News_are_enabled']);

  $rep->add_field( 'is_site_map_enabled'
                  ,'site_is_site_map_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,$text['Site_map_enabled']);

  $rep->add_field( 'is_ec_enabled'
                  ,'site_is_ec_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,text('EC_enabled')
                  );


  $tmp=db_getrows("SELECT ec_currency_code, ec_curency_title FROM {$table_prefix}ec_currency ORDER BY ec_curency_title");
  $list_of_currency=Array();
  foreach($tmp as $tm) $list_of_currency[]=$tm['ec_currency_code'].'='.rawurlencode($tm['ec_curency_title']);
  unset($tmp,$tm);
  $list_of_currency=join('&',$list_of_currency);
  $rep->add_field( 'ec_currency'
                  ,'site_ec_currency'
                  ,"enum:$list_of_currency"
                  ,text('EC_currency')
                  );

  $rep->add_field( 'is_forum_enabled'
                  ,'site_is_forum_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,$text['Forum_enabled']);

  $rep->add_field( 'is_calendar_enabled'
                  ,'site_is_calendar_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,text('Calendar_enabled')
                  );

  $rep->add_field( 'is_rssaggegator_enabled'
                  ,'site_is_rssaggegator_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,text('RSSAggegator_enabled')
                  );

  $rep->add_field( 'is_search_enabled'
                  ,'site_is_search_enabled'
                  ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                  ,$text['Search_enabled']);

  $rep->add_field( 'search_validate_url'
                  ,'search_validate_url'
                  ,'string:textarea=yes'
                  ,$text['URL_validation_regexp']);

  $rep->add_field( 'search_exclude_url'
                  ,'search_exclude_url'
                  ,'string:textarea=yes'
                  ,$text['URL_exclusion_regexp']);


  $rep->add_field( 'salt'
                  ,'salt'
                  ,'string:maxlength=32'
                  ,text('Site_salt'));

  $rep->set_primary_key('site_id',$input_vars['site_id']);


  if($rep->process())
  {

    //--------------------- update site dir - begin ----------------------------
    if(is_admin())
    {
      $site_dir_path = sites_root.'/'.$rep->field['site_dir']['value'];
      if(strlen($this_site_info['dir'])==0)
      {
        // create directory if not exists
        if(!is_dir($site_dir_path))
        {
           //prn($site_dir_path);
           mkdir($site_dir_path);
        }
      }
      else
      {
         // check if directory has to be renamed
         $old_dir_path = sites_root.'/'.$this_site_info['dir'];
         if($old_dir_path != $site_dir_path)
         {
           //prn("Rename $old_dir_path to $site_dir_path");
           rename($old_dir_path, $site_dir_path);
         }
      }
    }
    //--------------------- update site dir - end ------------------------------
    ml('site/edit',$input_vars);
  }
//------------------- edit properties -- end -----------------------------------
//prn($rep);


//----------------------------- draw -- begin ----------------------------------
  $form=$rep->draw_form();

  $form['elements']['site_title']['comments']       = $text['site_title_manual'];
  $form['elements']['site_url']['comments']         = $text['site_url_manual'];
  $form['elements']['site_template']['comments']    = $text['site_template_manual'];
  if(is_admin())
  {
    $form['elements']['site_dir']['comments']         = $text['site_dir_manual'];
    $form['elements']['site_cense_level']['comments'] = $text['site_cense_level_manual'];
  }

  $form['hidden_elements'].="\n<input type=hidden name=site_id value={$rep->id}>\n";
  //prn($form);
  $input_vars['page_title']   = $text['Site properties'];
  $input_vars['page_header']  = $text['Site properties'];
  $input_vars['page_content'] = $rep->draw($form);


  $list_of_languages=list_of_languages($exclude_pattern='');
  // prn($list_of_languages);
  $js_lang=Array();
  foreach($list_of_languages as $l){
      $js_lang[]="\"{$l['name']}\":\"{$text[$l['name']]}\"";
  }
  $js_lang='{'.join(',',$js_lang).'}';

  $input_vars['page_content'].="
    <script type=\"text/javascript\" src=\"scripts/lib/langstring.js\"></script>
    <script type=\"text/javascript\">
          langs=$js_lang;
          draw_langstring('db_record_editor_site_title');
    </script>
    ";

//----------------------------- draw -- end ------------------------------------

//----------------------------- site context menu - begin ----------------------
  if($rep->id > 0)
  {
    $this_site_info=get_site_info($rep->id);
    $sti=$text['Site'].' "'. $this_site_info['title'].'"';
    $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());
    $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
  }
//----------------------------- site context menu - end ------------------------

?>
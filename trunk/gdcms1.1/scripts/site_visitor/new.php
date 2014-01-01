<?php
/**
   Register the new site visitor
   available to site admin and site visitors
   
   argument is $site_id - site identifier 

site_visitor table :
   id               bigint(11)
   site_id          bigint(20)     - required
   login_password   varchar(30)    - required
   name_nick        varchar(30)    - required
   name_first       varchar(50)    - required
   name_middle      varchar(50)    - required
   name_last        varchar(50)    - required
   birthdate        date
   email            varchar(100)   - required
   home_page_url    varchar(255)
   telephone        varchar(255)
   address          text
   additional_info  text

*/

# prn($GLOBALS['_COOKIE']);
# prn($_SESSION);

# ---------------------- load language - begin ---------------------------------
  if(isset($input_vars['interface_lang']))
  if(strlen($input_vars['interface_lang'])>0)
  {
    $_SESSION['lang']  =
    $input_vars['lang']=$input_vars['interface_lang'];
  }
  if(!isset($_SESSION['lang'])) $_SESSION['lang']=default_language;
  if(!isset($input_vars['lang'])) $input_vars['lang']=$_SESSION['lang'];
  
  $input_vars['lang'] = get_language('lang');
  
  $txt = load_msg($input_vars['lang']);
# ---------------------- load language - end -----------------------------------


# ------------------- site info - begin ----------------------------------------
  if(isset($input_vars['site_id'])) $site_id = (int)$input_vars['site_id']; else $site_id=0;
  if($site_id>0)
  {
    $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
    if(!$this_site_info) $site_id=0;
  }
  if($site_id<=0) die($txt['Site_not_found']);
# ------------------- site info - end ------------------------------------------


global $main_template_name; $main_template_name='';
$page_content='';
$form_name_prefix='svr_';

# ------------------- data template - begin ------------------------------------

  run('site/user/functions');  
  $form=Array();

  # submitted
    $nm='submitted';
    $form[$nm]=get_element($nm,$form_name_prefix);
    $form[$nm]['form_element_value']='yes';
    
  # login_password   varchar(30)    - required
    $nm='login_password';
    $form[$nm]=get_element($nm,$form_name_prefix);
    
  # name_nick        varchar(30)    - required
    $nm='name_nick';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # to prevent twin nicknames
    $form[$nm]['value']=map_nick($form[$nm]['value']);
    $form[$nm]['form_element_value']=checkStr($form[$nm]['value']);

  # name_first       varchar(50)    - required
    $nm='name_first';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # name_middle      varchar(50)    - required
    $nm='name_middle';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # name_last        varchar(50)    - required
    $nm='name_last';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # ---------------------------- birth date selector - begin -------------------
  # birthdate        date
    $nm='birthdate';
    if(isset($input_vars['submitted']))
    {
      if(isset($input_vars[$form_name_prefix.$nm]))
      {
        if(is_array($input_vars[$form_name_prefix.$nm]))
        {
          $input_vars[$form_name_prefix.$nm]=
                  substr('0000'.(int)$input_vars[$form_name_prefix.$nm]['year'],-4)
             .'-'.substr('00'.(int)$input_vars[$form_name_prefix.$nm]['month'],-2)
             .'-'.substr('00'.(int)$input_vars[$form_name_prefix.$nm]['day'],-2)
          ;
        }else $input_vars[$form_name_prefix.$nm]='0000-00-00';
      }else $input_vars[$form_name_prefix.$nm]='0000-00-00';
    }else $input_vars[$form_name_prefix.$nm]='0000-00-00';

    $form[$nm]=get_element($nm,$form_name_prefix);

    if(strlen($form['birthdate']['value'])==0) $form['birthdate']['value']='0000-00-00';
    if(!ereg('^[0-9]{4}\.[0-9]{2}\.[0-9]{2}$',$form['birthdate']['value'])) $form['birthdate']['value']='0000-00-00';
    
    $form['birthdate']['form_element_options'] = Array();

    $nm=Array();

    $date_posted = explode('-',$form['birthdate']['value']);
    $date_posted['year']  = $date_posted[0];
    $date_posted['month'] = $date_posted[1];
    $date_posted['day']   = $date_posted[2];

  # year
    $years=Array();
    $now_year=date('Y');
    for($i=0;$i<90;$i++) $years[$now_year-$i]=$now_year-$i;
    $nm['year']   = $form['birthdate']['form_element_name'].'[year]';
    $form['birthdate']['form_element_options']['year']=draw_options($date_posted['year'],$years);

  # month
    $months=Array('',$txt['month_January'],$txt['month_February'],$txt['month_March'],$txt['month_April'],$txt['month_May'],$txt['month_June'],$txt['month_July'],$txt['month_August'],$txt['month_September'],$txt['month_October'],$txt['month_November'],$txt['month_December']);
    unset($months[0]);
    $nm['month']   = $form['birthdate']['form_element_name'].'[month]';
    $form['birthdate']['form_element_options']['month']=draw_options($date_posted['month'],$months);

  # days
    $days = range(0, 31);
    unset($days[0]);
    $nm['day']   = $form['birthdate']['form_element_name'].'[day]';
    $form['birthdate']['form_element_options']['day']=draw_options($date_posted['day'],$days);

    $form['birthdate']['form_element_name']=$nm;
  # ---------------------------- birth date selector - end ---------------------
    
  # email            varchar(100)   - required
    $nm='email';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # home_page_url    varchar(255)
    $nm='home_page_url';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # telephone        varchar(255)
    $nm='telephone';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # address          text
    $nm='address';
    $form[$nm]=get_element($nm,$form_name_prefix);

  # additional_info  text
    $nm='additional_info';
    $form[$nm]=get_element($nm,$form_name_prefix);
# ------------------- data template - end --------------------------------------

# ------------------- save new user - begin ------------------------------------
  if(isset($input_vars['submitted']))
  {
    $errors='';
    $all_is_ok=true;
    # ------------------------ check posted data - begin -----------------------
      # login_password   varchar(30)    - required
        if(strlen($form['login_password']['value'])==0){ $form['login_password']['message'].="<font color=red>{$txt['ERROR_Login_password_is_empty']}</font><br/>"; $all_is_ok=false; }

      # if two passwords match
        if(strlen($form['login_password']['value'])>0)
        if($form['login_password']['value']!=$input_vars[$form['login_password']['form_element_name'].'_1'])
        {
          $form['login_password']['message'].="<font color=red>{$txt['ERROR_Passwords_do_not_match']}</font> <br/>";
          $all_is_ok=false;
        }

      # name_nick        varchar(30)    - required
        if(strlen($form['name_nick']['value'])==0){ $form['name_nick']['message'].="<font color=red>{$txt['ERROR_name_nick_is_empty']}</font> <br/>"; $all_is_ok=false;}

      # name_nick   uniqueness
        if(strlen($form['name_nick']['value'])>0)
        if(count(db_getrows("SELECT id FROM {$table_prefix}site_visitor WHERE name_nick='".DbStr($form['name_nick']['value'])."'")))
        {
          $form['name_nick']['message'].="<font color=red>{$txt['ERROR_name_nick_already_exists']}</font> <br/>";
          $all_is_ok=false;
        }

      # name_first       varchar(50)    - required
        if(strlen($form['name_first']['value'])==0) { $form['name_first']['message'].="<font color=red>{$txt['ERROR_name_first_is_empty']}</font> <br/>"; $all_is_ok=false; }

      # name_middle      varchar(50)    - required
        if(strlen($form['name_middle']['value'])==0){ $form['name_middle']['message'].="<font color=red>{$txt['ERROR_name_middle_is_empty']}</font><br/>"; $all_is_ok=false; }

      # name_last        varchar(50)    - required
        if(strlen($form['name_last']['value'])==0) { $form['name_last']['message'].="<font color=red>{$txt['ERROR_name_last_is_empty']}</font><br/>"; $all_is_ok=false; }

      # birthdate        date

      # email            varchar(100)   - required
        if(strlen($form['email']['value'])==0){ $form['email']['message'].="<font color=red>{$txt['ERROR_email_is_empty']}</font> <br/>"; $all_is_ok=false; }
        if(strlen($form['email']['value'])>0)if(!is_valid_email($form['email']['value'])){ $form['email']['message'].="<font color=red>{$txt['ERROR_email_has_invalid_format']}</font> <br/>"; $all_is_ok=false; }

      # home_page_url    varchar(255)
        if(strlen($form['home_page_url']['value'])>0) if(!is_valid_url($form['home_page_url']['value'])){ $form['home_page_url']['message'].="<font color=red>{$txt['ERROR_url_has_invalid_format']}</font> <br/>"; $all_is_ok=false; }

      # telephone        varchar(255)
      # address          text
      # additional_info  text

    # ------------------------ check posted data - end -------------------------
  
  
    if($all_is_ok)
    {
    # ------------------------ create record - begin ---------------------------
      $query="insert into {$table_prefix}site_visitor 
              (site_id, login_password, name_nick, name_first, 
              name_middle, name_last, birthdate, email, home_page_url, 
              telephone, address, additional_info)
              values
              ( {$site_id}
               ,'".DbStr($form['login_password']['value'])."'
               ,'".DbStr($form['name_nick']['value'])."'
               ,'".DbStr($form['name_first']['value'])."'
               ,'".DbStr($form['name_middle']['value'])."'
               ,'".DbStr($form['name_last']['value'])."'
               ,'".DbStr($form['birthdate']['value'])."'
               ,'".DbStr($form['email']['value'])."'
               ,'".DbStr($form['home_page_url']['value'])."'
               ,'".DbStr($form['telephone']['value'])."'
               ,'".DbStr($form['address']['value'])."'
               ,'".DbStr($form['additional_info']['value'])."')";
    # prn($query);
      db_execute($query);
      
      $query="select last_insert_id() as new_visitor_id;";
    # prn($query);
      db_execute($query);
      $new_visitor_id = db_getonerow($query);
      $new_visitor_id = (int)$new_visitor_id['new_visitor_id'];
    # ------------------------ create record - end -----------------------------
    
    # ------------------------ save education info - begin ---------------------
      if(isset($input_vars['education']))
      if(is_array($input_vars['education']))
      {
      # prn($input_vars['education']);
        $query=Array();
        foreach($input_vars['education'] as $edu)
        {
           if(strlen(trim($edu['place']))==0) continue;
           if(strlen(trim($edu['year']))==0 ) continue;
           $edu['year']=(int)$edu['year'];
           $query[]="({$site_id},{$new_visitor_id},{$edu['year']},'".DbStr($edu['faculty'])."','".DbStr($edu['speciality'])."','".DbStr($edu['place'])."')";
        }
        if(count($query)>0)
        {
          $query="INSERT INTO {$table_prefix}site_visitor_education(site_id,site_visitor_id,edu_year,faculty,speciality,place) values ". join(',',$query);
        # prn($query);
          db_execute($query);
        }
      }
    # ------------------------ save education info - end -----------------------

    }
  }
  else $all_is_ok=false;
# ------------------- save new user - end --------------------------------------

if($all_is_ok)
{
# ------------------- draw signup confirmation - begin -------------------------
  # ------------------------ education - begin ---------------------------------
    $education_selector='';
    for($i=0;$i<5;$i++)
    {
      $edu_place       = isset($input_vars['education'][$i]['place'])?checkStr($input_vars['education'][$i]['place']):'';
      $edu_year        = isset($input_vars['education'][$i]['year'])?checkStr($input_vars['education'][$i]['year']):'';
      $edu_faculty     = isset($input_vars['education'][$i]['faculty'])?checkStr($input_vars['education'][$i]['faculty']):'';
      $edu_speciality  = isset($input_vars['education'][$i]['speciality'])?checkStr($input_vars['education'][$i]['speciality']):'';
      $education_selector.="
      <tr>
      <td>{$edu_place}</td>
      <td>{$edu_year}</td>
      <td>{$edu_faculty}</td>
      <td>{$edu_speciality}</td>
      </tr>
      ";
    }
    $education_selector="
    <table width=\"100%\">
      <tr>
      <td width=\"5%\" align=center>{$txt['Place']}</td>
      <td width=\"5%\" align=center>{$txt['Year']}</td>
      <td width=\"5%\" align=center>{$txt['Faculty']}</td>
      <td align=center>{$txt['Speciality']}</td>
      </tr>
    {$education_selector}
    </table>
    ";
  # ------------------------ education - end -----------------------------------

  #prn($form);
  #prn($input_vars);
  $page_content="
  <table width=\"100%\">
  <tr>
    <td valign=top><b>{$txt['name_nick']}</b></td>
    <td valign=top>{$form['name_nick']['form_element_value']}</td>
  </tr>
  <tr>
    <td width=150px valign=top><b>{$txt['Password']}</b></td>
    <td width=300px valign=top>{$form['login_password']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['name_first']}</b></td>
    <td valign=top>{$form['name_first']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['name_middle']}</b></td>
    <td valign=top>{$form['name_middle']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['name_last']}</b></td>
    <td valign=top>{$form['name_last']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['birthdate']}</b></td>
    <td valign=top>{$form['birthdate']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['Email']}</b></td>
    <td valign=top>{$form['email']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['home_page_url']} (URL)</b></td>
    <td valign=top>{$form['home_page_url']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['Telephone']}</b></td>
    <td valign=top>{$form['telephone']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top><b>{$txt['Address']}</b></td>
    <td valign=top>{$form['address']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top colspan=2 valign=top>
    <b>{$txt['Additional_info']}</b><br/>
    {$form['additional_info']['form_element_value']}</td>
  </tr>
  <tr>
    <td valign=top colspan=2 valign=top>
     <b>{$txt['Education']}</b><br/>
     {$education_selector}
    </td>
    <td></td>
  </tr>
  </table>
  ";
 
# ------------------- draw signup confirmation - end ---------------------------

# ------------------------ send confirmation - begin ---------------------------
  run('lib/mailing');
  run('lib/class.phpmailer');
  run('lib/class.smtp');
  
  $mng_body=sprintf($txt['signup_confirmation_email']
                   ,$form['name_first']['form_element_value'].' '.$form['name_middle']['form_element_value'].' '.$form['name_last']['form_element_value']
                   ,"<a href=\"{$this_site_info['url']}\">{$this_site_info['title']}</a>"
                   ,$page_content
                   ,"<a href=\"{$this_site_info['url']}\">{$this_site_info['title']}</a>");
  if(IsHTML!='1') $mng_body=wordwrap(strip_tags($mng_body), 80, "\n");
  my_mail($form['email']['form_element_value']
        , $this_site_info['title'].' : '.$txt['New_user_registration']
        , $mng_body);
# ------------------------ send confirmation - end -----------------------------

}
else
{
# ------------------- draw signup form - begin ---------------------------------
  # ------------------------ education - begin ---------------------------------
    $education_selector='';
    for($i=0;$i<5;$i++)
    {
      $edu_place       = isset($input_vars['education'][$i]['place'])?checkStr($input_vars['education'][$i]['place']):'';
      $edu_year        = isset($input_vars['education'][$i]['year'])?checkStr($input_vars['education'][$i]['year']):'';
      $edu_faculty     = isset($input_vars['education'][$i]['faculty'])?checkStr($input_vars['education'][$i]['faculty']):'';
      $edu_speciality  = isset($input_vars['education'][$i]['speciality'])?checkStr($input_vars['education'][$i]['speciality']):'';
      $education_selector.="
      <tr>
      <td><input type=text size=4 style='width:100px;' name='education[{$i}][place]'      value='{$edu_place}'></td>
      <td><input type=text size=4 style='width:30px;'  name='education[{$i}][year]'       value='{$edu_year}'></td>
      <td><input type=text size=4 style='width:100px;' name='education[{$i}][faculty]'    value='{$edu_faculty}'></td>
      <td><input type=text size=4 style='width:100%;'  name='education[{$i}][speciality]' value='{$edu_speciality}'></td>
      </tr>
      ";
    }
    $education_selector="
    <table width=\"100%\">
      <tr>
      <td width=\"5%\" align=center>{$txt['Place']}</td>
      <td width=\"5%\" align=center>{$txt['Year']}</td>
      <td width=\"5%\" align=center>{$txt['Faculty']}</td>
      <td align=center>{$txt['Speciality']}</td>
      </tr>
    {$education_selector}
    </table>
    ";
  # ------------------------ education - end -----------------------------------

  #prn($form);
  #prn($input_vars);
  $page_content="
  <form action=index.php method=post>
  <input type=\"hidden\" name=\"action\" value=\"site/user/new\">
  <input type=\"hidden\" name=\"site_id\" value=\"{$site_id}\">
  <input type=\"hidden\" name=\"submitted\" value=\"yes\">
  <table width=\"100%\">

  <tr>
    <td valign=top><b>{$txt['name_nick']}</b></td>
    <td valign=top><input type=text
               name=\"{$form['name_nick']['form_element_name']}\"
               value=\"{$form['name_nick']['form_element_value']}\"
               style='width:100%;'></td>
    <td valign=top>{$form['name_nick']['message']}</td>
  </tr>

  <tr>
    <td width=150px valign=top><b>{$txt['Password_twice']}</b></td>
    <td width=300px valign=top><input type=password name=\"{$form['login_password']['form_element_name']}\" style='width:100%;'><br />
        <input type=password name=\"{$form['login_password']['form_element_name']}_1\" style='width:100%;'></td>
    <td valign=top>{$form['login_password']['message']}</td>
  </tr>

  <tr>
    <td valign=top><b>{$txt['name_first']}</b></td>
    <td valign=top><input type=text
               name=\"{$form['name_first']['form_element_name']}\"
               value=\"{$form['name_first']['form_element_value']}\"
               style='width:100%;'></td>
    <td valign=top>{$form['name_first']['message']}</td>
  </tr>

  <tr>
    <td valign=top><b>{$txt['name_middle']}</b></td>
    <td valign=top><input type=text
               name=\"{$form['name_middle']['form_element_name']}\"
               value=\"{$form['name_middle']['form_element_value']}\"
               style='width:100%;'></td>
    <td valign=top>{$form['name_middle']['message']}</td>
  </tr>

  <tr>
    <td valign=top><b>{$txt['name_last']}</b></td>
    <td valign=top><input type=text
               name=\"{$form['name_last']['form_element_name']}\"
               value=\"{$form['name_last']['form_element_value']}\"
               style='width:100%;'></td>
    <td valign=top>{$form['name_last']['message']}</td>
  </tr>


  <tr>
    <td valign=top><b>{$txt['birthdate']}</b></td>
    <td valign=top>
      <select name=\"{$form['birthdate']['form_element_name']['day']}\"><option value=''></option>{$form['birthdate']['form_element_options']['day']}</select>
      <select name=\"{$form['birthdate']['form_element_name']['month']}\"><option value=''></option>{$form['birthdate']['form_element_options']['month']}</select>
      <select name=\"{$form['birthdate']['form_element_name']['year']}\"><option value=''></option>{$form['birthdate']['form_element_options']['year']}</select>
    </td>
    <td valign=top>{$form['birthdate']['message']}</td>
  </tr>

  <tr>
    <td valign=top><b>{$txt['Email']}</b></td>
    <td valign=top><input type=text
               name=\"{$form['email']['form_element_name']}\"
               value=\"{$form['email']['form_element_value']}\"
               style='width:100%;'></td>
    <td valign=top>{$form['email']['message']}</td>
  </tr>


  <tr>
    <td valign=top><b>{$txt['home_page_url']} (URL)</b></td>
    <td valign=top><input type=text
               name=\"{$form['home_page_url']['form_element_name']}\"
               value=\"{$form['home_page_url']['form_element_value']}\"
               style='width:100%;'></td>
    <td valign=top>{$form['home_page_url']['message']}</td>
  </tr>


  <tr>
    <td valign=top><b>{$txt['Telephone']}</b></td>
    <td valign=top><input type=text
               name=\"{$form['telephone']['form_element_name']}\"
               value=\"{$form['telephone']['form_element_value']}\"
               style='width:100%;'></td>
    <td valign=top>{$form['telephone']['message']}</td>
  </tr>

  <tr>
    <td valign=top><b>{$txt['Address']}</b></td>
    <td valign=top><textarea name=\"{$form['address']['form_element_name']}\"
                  rows=3
                  style='width:100%;'>{$form['address']['form_element_value']}</textarea></td>
    <td valign=top>{$form['address']['message']}</td>
  </tr>

  <tr>
    <td valign=top colspan=2 valign=top>
    <b>{$txt['Additional_info']}</b><br/>
    <textarea name=\"{$form['additional_info']['form_element_name']}\"
              rows=7 
              style='width:100%;'>{$form['additional_info']['form_element_value']}</textarea></td>
    <td valign=top>{$form['additional_info']['message']}</td>
  </tr>

  <tr>
    <td valign=top colspan=2 valign=top>
     <b>{$txt['Education']}</b><br/>
     {$education_selector}
    </td>
    <td></td>
  </tr>
  

  <tr><td colspan=3 align=center style='border:none;padding:10px;'><input type=submit></td></tr>

  </table>
  
  </form>
  ";
  
# ------------------- draw signup form - end -----------------------------------
}

















# ------------------------ draw page - begin -----------------------------------
  # load functions
    run('site/page/page_view_functions');

  # load site menu
    $menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

  # ------------------------ get list of languages - begin ---------------------
    $lang_list=list_of_languages("^{$form_name_prefix}|^education|^submitted$");
  # prn($lang_list);
    $cnt=count($lang_list);
    for($i=0;$i<$cnt;$i++)
    {
       $lang_list[$i]['url']=$lang_list[$i]['href'];
  
    #  $lang_list[$i]['url']=str_replace('action=news%2Fview','',$lang_list[$i]['url']);
    #  $lang_list[$i]['url']=str_replace('index.php','news.php',$lang_list[$i]['url']);
    #  $lang_list[$i]['url']=str_replace(site_root_URL,sites_root_URL,$lang_list[$i]['url']);
    #  $lang_list[$i]['url']=str_replace('?&','?',$lang_list[$i]['url']);
    #  $lang_list[$i]['url']=str_replace('&&','&',$lang_list[$i]['url']);
  
       $lang_list[$i]['lang']=$lang_list[$i]['name'];
    }
    // prn($lang_list);
  # ------------------------ get list of languages - end -----------------------


  # ------------------------ draw page using site teplate - begin --------------
    $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array(
                                     'title'=>$txt['New_user_registration']
                                    ,'content'=> $page_content
                                    ,'abstract'=> ( isset($txt['New_user_registration_manual'])?$txt['New_user_registration_manual']:'')
                                    ,'site_id'=>$site_id
                                    ,'lang'=>$input_vars['lang']
                                    )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
    echo $file_content;
  # ------------------------ draw page using site teplate - end ----------------

# ------------------------ draw page - end -------------------------------------


// remove from history
   nohistory($input_vars['action']);



?>
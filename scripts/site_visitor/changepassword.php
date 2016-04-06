<?php
/*
 * Show login form using site design
 */

global $main_template_name; $main_template_name='';
run('site/page/page_view_functions');
run('site_visitor/functions');
run('site/menu');



# -------------------- set interface language - begin --------------------------
  $debug=false;
  if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
  if(!isset($input_vars['lang'])   ) $input_vars['lang']=default_language;
  if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
  // $lang=$input_vars['lang'];
  $lang = get_language('lang');
# -------------------- set interface language - end -----------------------------

# -------------------------- load messages - begin -----------------------------
  global $txt;
  $txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------

# ------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
# ------------------- get site info - end --------------------------------------


# ------------------- check permission - begin ---------------------------------
# site visitor is logged
  $access_allowed = ( isset($_SESSION['site_visitor_info']) && $_SESSION['site_visitor_info']['is_logged'] );

# code is posted
  if(!$access_allowed && isset($input_vars['code']))
  {
      $tmp_site_visitor_info=site_visitor_load(" site_visitor_code='".\e::db_escape($input_vars['code'])."' ");
      if($tmp_site_visitor_info)
      {
         $_SESSION['site_visitor_info']=$tmp_site_visitor_info;
         $_SESSION['site_visitor_info']['is_logged']=true;
      }
      unset($tmp_site_visitor_info);
  }
# ------------------- check permission - end -----------------------------------

$vyvid=" {$_SESSION['site_visitor_info']['site_visitor_login']}! ".site_visitor_draw_menu($_SESSION['site_visitor_info'],$this_site_info).'<br/><br/>';


# get site template
  $custom_page_template =  site_get_template($this_site_info,'template_index');

# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
  $lang_list=array_values($lang_list);
# -------------------- get list of page languages - end ------------------------

$error_msg='';

# -------------------- update password - begin ---------------------------------
  if(isset($input_vars['posted']))
  {
     if(strlen($input_vars['pw1'])==0) $error_msg.='<br>'.text('ERROR').': '.text('Enter_password');
     if($input_vars['pw1']!=$input_vars['pw2']) $error_msg.='<br>'.text('ERROR').': '.text('Passwords_do_not_match');
     if($error_msg=='')
     {
        \e::db_execute(
            "UPDATE {$table_prefix}site_visitor
             SET site_visitor_password='".md5($input_vars['pw1'])."'
             WHERE site_visitor_id='".\e::db_escape($_SESSION['site_visitor_info']['site_visitor_id'])."'
             LIMIT 1");
//        prn("UPDATE {$table_prefix}site_visitor
//             SET site_visitor_password='".md5($input_vars['pw1'])."'
//             WHERE site_visitor_id='".DbStr($_SESSION['site_visitor_info']['site_visitor_id'])."'
//             LIMIT 1");
        $vyvid.='<b style="color:green;">'.text('Changes_saved_successfully').'</b><br/>';
     }
  }
# -------------------- update password - end -----------------------------------



$vyvid.="
  <font color=red><b>$error_msg</b></font>
  <form action=index.php method=post>
  <input type=hidden name=action  value='{$input_vars['action']}'>
  <input type=hidden name=site_id value='{$input_vars['site_id']}'>
  <input type=hidden name=lang value='{$lang}'>
  <input type=hidden name=posted value='1'>
  <table>
      <tr><td>{$txt['ec_user_password']} :</td><td><input type=password name=pw1 style='width:100%;' value=''></td></tr>
      <tr><td>{$txt['ec_user_password_again']} :</td><td><input type=password name=pw2 style='width:100%;' value=''></td></tr>
      <tr>
          <td></td>
          <td>
              <input type=submit value='{$text['Change_password']}'>
          </td>
      </tr>
  </table>
  </form>
";

# get site menu
  $menu_groups = get_menu_items($this_site_info['id'],0,$lang);

# draw page
  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$_SESSION['site_visitor_info']['site_visitor_login'].' - '.$txt['Change_password']
                                               ,'content'=>$vyvid
                                               ,'abstract'=> ''
                                               ,'site_id'=>$site_id
                                               ,'lang'=>$input_vars['lang']
                                          )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

// remove from history
   nohistory($input_vars['action']);


?>

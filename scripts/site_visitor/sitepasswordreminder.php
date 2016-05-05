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
  if(!isset($input_vars['lang'])   ) $input_vars['lang']=\e::config('default_language');
  if(strlen($input_vars['lang'])==0) $input_vars['lang']=\e::config('default_language');
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
  //prn($this_site_info);
  //prn($input_vars);
# ------------------- get site info - end --------------------------------------
//prn($_SESSION['ec_order_ids']);

# get site template
  $custom_page_template =  site_get_template($this_site_info,'template_index');

# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
  $lang_list=array_values($lang_list);
# -------------------- get list of page languages - end ------------------------

$error_msg='';

# -------------------- send password reminder - begin --------------------------
  if(!isset($input_vars['nick'])) $input_vars['nick']='';
  $error_msg='';
  if(strlen($input_vars['nick'])>0)
  {
     $tmp_site_visitor_info=site_visitor_load(" site_visitor_login='".\e::db_escape($input_vars['nick'])."' ");
     //prn($tmp_site_visitor_info);
     if($tmp_site_visitor_info)
     {
        $tmp_site_visitor_info['site_visitor_code']=sha1(session_id());
        \e::db_execute("UPDATE <<tp>>site_visitor SET site_visitor_code='".\e::db_escape($tmp_site_visitor_info['site_visitor_code'])."' WHERE site_visitor_login='".\e::db_escape($input_vars['nick'])."'");
        $recovery_url=site_root_URL."/index.php?action=site_visitor/changepassword&site_id={$site_id}&lang={$lang}&code=".rawurlencode($tmp_site_visitor_info['site_visitor_code']);

        run('lib/mailing');
        run('lib/class.phpmailer');
        run('lib/class.smtp');
        # ---------- email secret code to site visitor - begin -----------------
            # get email template
              $password_recovery_email_template = site_get_template($this_site_info,'template_password_recovery_mail');
              $email_body=process_template( $password_recovery_email_template
                           ,Array('site_visitor_info'=>$tmp_site_visitor_info,
                                  'text'=>$txt,
                                  'site'=>$this_site_info,
                                  'recovery_url'=>$recovery_url ) );
              $email_subject=get_langstring($this_site_info['title'],$lang).':'.$txt['Restore_password'];

              if(IsHTML!='1') $email_body=wordwrap(strip_tags(eregi_replace('<br/?>',"\n",$email_body)), 80, "\n");
              //prn($this_site_info);
              my_mail($tmp_site_visitor_info['site_visitor_email'], $email_subject, $email_body);
              ###prn("my_mail({$tmp_site_visitor_info['site_visitor_email']}, {$email_subject}, {$email_body});");
        # ---------- email secret code to site visitor - end -------------------

        $vyvid=text('ec_user_read_email_to_continue_password_recovery');
     }
     else
     {
         $error_msg.=$txt['ERROR'].' : '.$txt['Wrong_login_name'];
     }
  }
# -------------------- send password reminder - end ----------------------------


if(!isset($vyvid) || $vyvid=='')
$vyvid="
  <font color=red><b>$error_msg</b></font>
  <form action=index.php method=post>
  <input type=hidden name=action  value='{$input_vars['action']}'>
  <input type=hidden name=site_id value='{$input_vars['site_id']}'>
  <input type=hidden name=lang value='{$lang}'>
  <table>
      <tr><td>{$txt['ec_user_email']} :</td><td><input type=text name=nick style='width:100%;' value='".htmlspecialchars($input_vars['nick'])."'></td></tr>
      <tr>
          <td></td>
          <td>
              <input type=submit name='dologin' value='{$text['Restore_password']}'>
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
                                  'page'=>Array('title'=>$txt['Restore_password']
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
?>

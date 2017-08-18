<?php

  # ------------------- function get_element - begin ---------------------------
  function get_element($nm,$form_name_prefix)
  {
    global $input_vars;
    $tr=Array(
          'form_element_name'=>$form_name_prefix.$nm
         ,'form_element_value'=>''
         ,'value'=>''
         ,'message'=>''
    );
    if(isset($input_vars['submitted']))
    {
       #prn($nm.':form submitted');
       if(isset($input_vars[$tr['form_element_name']]))
       {
          #prn($nm.': data posted');
          $tr['value']=trim($input_vars[$tr['form_element_name']]);
          $tr['form_element_value']=htmlspecialchars($tr['value']);
       }
    }
    return $tr;
  }
  # ------------------- function get_element - end -----------------------------


  function map_nick($str)
  {
    $tor=str_replace(
      Array('�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�','�') # ������� �������
     ,Array('e','E','T','i','o','O','p','P','a','A','H','K','x','X','c','C','B','M') # ��������� �������
     ,$str
    );
    $tor=preg_replace('/ +/',' ',$tor);
    return $tor;
  }








function site_visitor_check_login($email,$password)
{
    return site_visitor_load(
              "    site_visitor_email='".\e::db_escape($email)."'
               AND site_visitor_password='".md5($password)."' "
           );
}


function site_visitor_load($condition)
{
   $user_data=\e::db_getonerow("SELECT DISTINCT *,'' as site_visitor_password FROM <<tp>>site_visitor WHERE $condition");
   //prn("SELECT DISTINCT *,'' as site_visitor_password FROM <<tp>>site_visitor WHERE $condition");
   if(!$user_data) return false;

   $user_data['email']=$user_data['site_visitor_email'];
   $user_data['last_used_id']=0;
   $user_data['data']=Array();
   $tmp=\e::db_getrows(
        "SELECT DISTINCT site_visitor.*, ec_user.*,'' as site_visitor_password
         FROM <<tp>>site_visitor as site_visitor,
              <<tp>>ec_user as ec_user
         WHERE  site_visitor.site_visitor_id={$user_data['site_visitor_id']}
            AND ec_user.site_visitor_id={$user_data['site_visitor_id']}
        ");
    foreach($tmp as $ud) $user_data['data'][$ud['ec_user_id']]=$ud;
    return $user_data;
}

function site_visitor_menu($info,$site)
{
   $tor=Array();

   if($info['is_logged'])
   {
      $tor['site_visitor/logout']=Array(
                       'URL'=>"index.php?action=site_visitor/personalpage&logout=yes&site_id=".$site['id'].'&lang='.$_SESSION['lang']
                      ,'innerHTML'=>text('Logout')
                      ,'attributes'=>''
                      );
      $tor['site_visitor/personalpage']=Array(
                       'URL'=>"index.php?action=site_visitor/personalpage&site_id=".$site['id'].'&lang='.$_SESSION['lang']
                      ,'innerHTML'=>text('Personal_page')
                      ,'attributes'=>''
                      );
      $tor['site_visitor/changepassword']=Array(
                       'URL'=>"index.php?action=site_visitor/changepassword&site_id=".$site['id'].'&lang='.$_SESSION['lang']
                      ,'innerHTML'=>text('Change_password')
                      ,'attributes'=>''
                      );
      $tor['ec/user/orders']=Array(
                       'URL'=>"index.php?action=ec/user/orders&site_id=".$site['id'].'&lang='.$_SESSION['lang']
                      ,'innerHTML'=>text('EC_orders')
                      ,'attributes'=>''
                      );
      /*
      $tor['ec/user/addresses']=Array(
                       'URL'=>"index.php?action=ec/user/addresses&site_id=".$site['id'].'&lang='.$_SESSION['lang']
                      ,'innerHTML'=>text('Saved_addresses')
                      ,'attributes'=>''
                      );
                      */
   }
   else
   {
      $tor['site_visitor/logout']=Array(
                       'URL'=>"index.php?action=site_visitor/personalpage&site_id=".$site['id'].'&lang='.$_SESSION['lang']
                      ,'innerHTML'=>text('Login')
                      ,'attributes'=>''
                      );
   }
   return $tor;
}

function site_visitor_draw_menu($info,$site)
{
    $tmp=site_visitor_menu($info,$site);
    $tor='';
    foreach($tmp as $ur)
       $tor.="<a href='{$ur['URL']}'>{$ur['innerHTML']}</a> ";
    return $tor;
}
?>
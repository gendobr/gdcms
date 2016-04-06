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
  $input_vars['lang'] = get_language('lang');
  $lang=$input_vars['lang'];
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

# --------------------------- get site template - begin ------------------------
  $custom_page_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/template_index.html';
  if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
# --------------------------- get site template - end --------------------------


# get list of page languages
# -------------------- get list of page languages - begin ----------------------
$tmp=\e::db_getrows("SELECT DISTINCT lang FROM {$table_prefix}page WHERE site_id={$site_id} AND cense_level>0");
$existing_languages=Array();
foreach($tmp as $tm) $existing_languages[$tm['lang']]=$tm['lang'];
// prn($existing_languages);


$lang_list=list_of_languages();
$cnt=count($lang_list);
for($i=0;$i<$cnt;$i++) {
    if(!isset($existing_languages[$lang_list[$i]['name']])) {
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url']=$lang_list[$i]['href'];
    $lang_list[$i]['lang']=$lang_list[$i]['name'];
}
$lang_list=array_values($lang_list);
// prn($lang_list);
# -------------------- get list of page languages - end ------------------------

if(!isset($input_vars['nick'])) $input_vars['nick']='';





//-------------------------- check info -- begin -------------------------------
$error_msg='';
if(strlen($input_vars['nick'])>0)
{
  if(strlen($input_vars['lp'])>0)
  {

   // ------------------- get user info -- begin -------------------------------
      $tmp_user_info=site_visitor_check_login(map_nick($input_vars['nick']),$input_vars['lp']);
   // ------------------- get user info -- end ---------------------------------

     if($tmp_user_info)
     {
        $_SESSION['site_visitor_info']=$tmp_user_info;
        $_SESSION['site_visitor_info']['is_logged']=true;
     }
     else
     {
       $error_msg.=$txt['ERROR'].' : '.$txt['Wrong_login_name_or_password'];
     }
  }
  else
  {
     $error_msg.=$txt['ERROR'].' : '.$txt['Password_is_not_set'];
  }
}
//-------------------------- check info -- end ---------------------------------


//-------------------------- do logout - begin ---------------------------------
  if(isset($input_vars['logout']))
  {
     unset($_SESSION['site_visitor_info']);
  }
//-------------------------- do logout - end -----------------------------------

if(isset($_SESSION['site_visitor_info']['is_logged']) && $_SESSION['site_visitor_info']['is_logged'])
{
   $vyvid=" {$_SESSION['site_visitor_info']['site_visitor_login']}! ".site_visitor_draw_menu($_SESSION['site_visitor_info'],$this_site_info).'<br/><br/>';

   # ---------------------------- order history - begin ------------------------
     if(!isset($input_vars['start'])) $input_vars['start']=0;
     $start=abs(round(1*$input_vars['start']));
     $rows_per_page=rows_per_page;

     $query="SELECT SQL_CALC_FOUND_ROWS *
             FROM {$table_prefix}ec_order_history
             WHERE site_visitor_id={$_SESSION['site_visitor_info']['site_visitor_id']}
             ORDER BY ec_order_history_date DESC
             LIMIT $start,".$rows_per_page;
     $ec_order_history=\e::db_getrows($query);
     $cnt=count($ec_order_history);
     $url_prefix=site_root_URL."/index.php?action=ec/order/view&lang={$lang}&ec_order_id=";
     for($i=0;$i<$cnt;$i++)
     {
         $ec_order_history[$i]['url_view_order']=$url_prefix.$ec_order_history[$i]['ec_order_id'];
     }
     //prn($ec_order_history);
   # ---------------------------- order history - end --------------------------

   # ---------------------------- order history paging - begin -----------------
     $query="SELECT FOUND_ROWS() AS n_records;";
     $num = \e::db_getonerow($query);
     $num = $num['n_records'];
     //prn($num);
     $pages = Array();
     $imin=max(0,$start-10*$rows_per_page);
     $imax=min($num,$start+10*$rows_per_page);
     $url_prefix=site_root_URL."/index.php?".query_string('^start|^'.session_name())."&start=";
     if($imin>0)
     {
        $pages[]=Array('URL'=>$url_prefix.'0','innerHTML' => '[1]');
        $pages[]=Array('URL'=>'','innerHTML' => '...');
     }

     for($i=$imin;$i<$imax; $i=$i+$rows_per_page)
     {
        if( $i==$start ) $to='<b>['.(1+$i/$rows_per_page).']</b>'; else $to=(1+$i/$rows_per_page);
        $pages[]=Array('URL'=>$url_prefix.$i,'innerHTML' => $to);
     }

     if($imax<$num)
     {
        $last_page=floor( ($num-1)/$rows_per_page);
        if($last_page>0)
        {
           $pages[]=Array('URL'=>'','innerHTML' => "..." );
           $pages[]=Array('URL'=>$url_prefix.($last_page*$rows_per_page),'innerHTML' => "[".($last_page+1)."]" );
        }
     }
   # ---------------------------- order history paging - end -------------------

   # ---------------------------- draw - begin ---------------------------------
     $paging=text('Pages').': ';
     foreach($pages as $it)
     {
         if($it['URL']=='')
         {
            $paging.=" {$it['innerHTML']} ";
         }
         else
         {
            $paging.="<a href=\"{$it['URL']}\">{$it['innerHTML']}</a> ";
         }
     }

     $vyvid.="
       <style>
         .oh{padding:5pt;border:1px dotted gray;margin-bottom:10pt;}
         .det{color:gray;font-size:90%;}
         .oh h3{margin:0px;}
         .ln{display:block;text-align:center;float:right;width:120px;background-color:orange;padding:3px;color:blue;}
       </style>
       <h2>".text('List_of_messages')."</h2>
       $paging<br/><br/>
     ";
     foreach($ec_order_history as $it)
     {
       $vyvid.="<div class=oh>
          <a class=ln href=\"{$it['url_view_order']}\">".text('EC_order_details')."</a>
          <h3>{$it['ec_order_history_date']} / ".text('EC_order')." {$it['ec_order_id']} / {$it['ec_order_history_title']}</h3>
          <div class=det>
          {$it['ec_order_history_details']}
          </div>
       </div>";
     }
     $vyvid.=$paging;

   # ---------------------------- draw - end -----------------------------------
}
else
{
    $vyvid="
      <font color=red><b>$error_msg</b></font>
      <form action=index.php method=post>
      <input type=hidden name=action  value='{$input_vars['action']}'>
      <input type=hidden name=site_id value='{$input_vars['site_id']}'>
      <input type=hidden name=lang value='{$lang}'>
      <table>
          <tr><td>{$txt['Email']} :</td><td><input type=text name=nick style='width:100%;' value='".checkStr($input_vars['nick'])."'></td></tr>
          <tr><td>{$txt['Password']}   :</td><td><input type=password style='width:100%;' name=lp value=''></td></tr>
          <tr>
              <td></td>
              <td>
                  <input type=submit value='{$txt['Enter']}'>
                  <a href='index.php?action=site_visitor/sitepasswordreminder&site_id={$site_id}&lang={$lang}'>{$txt['Restore_password']}</a>
              </td>
          </tr>
      </table>
      </form>
    ";
}
# get site menu
  $menu_groups = get_menu_items($this_site_info['id'],0,$lang);

# draw page
  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$txt['Personal_page']
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

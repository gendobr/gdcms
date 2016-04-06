<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

global $main_template_name; $main_template_name='';
run('site/page/page_view_functions');
run('site_visitor/functions');

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
  run('site/menu');
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
  //prn($this_site_info);
  //prn($input_vars);
# ------------------- get site info - end --------------------------------------
//prn($_SESSION['ec_order_ids']);




# --------------------- check permissions - begin ------------------------------
# ec_order_id is saved in the session as order just created
  $orders_in_session=( isset($_SESSION['ec_order_ids']) && is_array($_SESSION['ec_order_ids']) && ( count($_SESSION['ec_order_ids'])>0 ) );

# site visitor is logged in and owns order
  $site_visitor_is_logged=(isset($_SESSION['site_visitor_info']) && $_SESSION['site_visitor_info']['is_logged']);

# user is site admin
  $user_is_site_admin=( get_level($site_id)>0 ) ;

# user is superuser
  $user_is_superuser= is_admin();

# combine permissions
  $access_allowed=($orders_in_session || $site_visitor_is_logged || $user_is_site_admin || $user_is_superuser);

  if(!$access_allowed)
  {
      $input_vars['page_title']  =
      $input_vars['page_header'] = $txt['EC_orders'];
      $input_vars['page_content']= "
        
      ";
      return 0;
  }
# --------------------- check permissions - end --------------------------------



# get site template
  $custom_page_template = site_get_template($this_site_info,'template_index');


# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
  $lang_list=array_values($lang_list);
# -------------------- get list of page languages - end ------------------------















# ----------------- get list of site visitor orders - begin --------------------
  $query=Array();
  
  # restrict site
    $query[]=" (ec_order.site_id={$this_site_info['id']} )";

    if($orders_in_session)
    {
       $query[]=" (ec_order.ec_order_id IN(".join(',',$_SESSION['ec_order_ids']).") )";
    }
    if($site_visitor_is_logged)
    {
       $query[]=" (ec_user.site_visitor_id = ".$_SESSION['site_visitor_info']['site_visitor_id']." )";
    }

  # -------------------- apply filter - begin ----------------------------------
    $filter=Array(
       'ec_order_id'=>'',
       'ec_date_created'=>'',
       'ec_order_status'=>'',
       'ec_order_total_from'=>'',
       'ec_order_total_to'=>'',
       'ec_order_paid'=>'',
       'ec_user_name'=>'',
       'ec_user_telephone'=>'',
       'ec_user_icq'=>'',
       'ec_user_delivery_city'=>'',
       'ec_user_delivery_region'=>'',
       'ec_user_delivery_street_address'=>'',
       'ec_user_delivery_suburb'=>'',
       'site_visitor_id'=>'',
       'site_visitor_email'=>''
    );
    if(isset($input_vars['filter']))
    {
       $cnt=array_keys($filter);
       foreach($cnt as $key)
           if(strlen($input_vars['filter'][$key])>0)
             $filter[$key]=trim($input_vars['filter'][$key]);
    }
    $filter['ec_order_paid_options']=draw_options($filter['ec_order_paid'],Array(''=>'','1'=>text('positive_answer'),'0'=>text('negative_answer')));

    $sql_filter=Array();


    $tmp=explode(',',ec_order_status);
    $cnt=count($tmp);
    $opts=Array(''=>'');
    for($i=0;$i<$cnt;$i++)
    {
        $opts[$tmp[$i]]=text('ec_order_status_'.$tmp[$i]);
    }
    $filter['ec_order_status_options']=draw_options($filter['ec_order_status'],$opts);

    if(strlen($filter['ec_order_id']))
    {
        $tmp=split(',|;| ',$filter['ec_order_id']);
        $cnt=count($tmp);
        for($i=0;$i<$cnt;$i++) $tmp[$i]*=1;
        $sql_filter[]=" (ec_order.ec_order_id IN(".join(',',$tmp).") )";
    }
    
    if(strlen($filter['ec_date_created']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_date_created'])."',ec_order.ec_date_created)>0 ) ";
    }


    if(strlen($filter['ec_order_status']))
    {
        $sql_filter[]=" ( ec_order.ec_order_status='".\e::db_escape($filter['ec_order_status'])."' ) ";
    }

    if(strlen($filter['ec_order_total_from']))
    {
        $sql_filter[]=" ( ec_order.ec_order_total>=".checkFloat($filter['ec_order_total_from'])." ) ";
    }

    if(strlen($filter['ec_order_total_to']))
    {
        $sql_filter[]=" ( ec_order.ec_order_total<=".checkFloat($filter['ec_order_total_to'])." ) ";
    }

    if(strlen($filter['ec_order_paid']))
    {
        $sql_filter[]=" ( '".\e::db_escape($filter['ec_order_paid'])."'=ec_order.ec_order_paid  ) ";
    }

    if(strlen($filter['ec_user_name']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_user_name'])."',ec_user.ec_user_name)>0 ) ";
    }

    if(strlen($filter['ec_user_telephone']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_user_telephone'])."',ec_user.ec_user_telephone)>0 ) ";
    }

    if(strlen($filter['ec_user_icq']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_user_icq'])."',ec_user.ec_user_icq)>0 ) ";
    }

    if(strlen($filter['ec_user_delivery_city']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_user_delivery_city'])."',ec_user.ec_user_delivery_city)>0 ) ";
    }

    if(strlen($filter['ec_user_delivery_street_address']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_user_delivery_region'])."',ec_user.ec_user_delivery_region)>0 ) ";
    }

    if(strlen($filter['ec_user_delivery_street_address']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_user_delivery_street_address'])."',ec_user.ec_user_delivery_street_address)>0 ) ";
    }

    if(strlen($filter['ec_user_delivery_suburb']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['ec_user_delivery_suburb'])."',ec_user.ec_user_delivery_suburb)>0 ) ";
    }

    if(strlen($filter['site_visitor_email']))
    {
        $sql_filter[]=" ( LOCATE('".\e::db_escape($filter['site_visitor_email'])."',site_visitor.site_visitor_email)>0 ) ";
    }

    if(strlen($filter['site_visitor_id']))
    {
        $tmp=split(',|;| ',$filter['site_visitor_id']);
        $cnt=count($tmp);
        for($i=0;$i<$cnt;$i++) $tmp[$i]*=1;
        $sql_filter[]=" (site_visitor.site_visitor_id IN(".join(',',$tmp).") )";
    }
    if(strlen($filter['site_visitor_email']))
    {
        $sql_filter[]=" (LOCATE('".\e::db_escape($filter['site_visitor_email'])."',site_visitor.site_visitor_email)>0 ) ";
    }

    if(count($sql_filter)>0) $sql_filter=' AND ('.join(' AND ',$sql_filter).') '; else $sql_filter='';
    //prn('$filter=',$filter,'$sql_filter',$sql_filter);
    //prn('$sql_filter',$sql_filter);
  # -------------------- apply filter - end ------------------------------------



  # -------------------- extract data from database - begin --------------------
    $start=isset($input_vars['start'])?( (int)$input_vars['start']):0;
    $rows_per_page=50;

    $query="SELECT SQL_CALC_FOUND_ROWS
                 ec_order.*,ec_user.*,site_visitor.*, '' as site_visitor_password
            FROM {$table_prefix}ec_order as ec_order
                 INNER JOIN {$table_prefix}ec_user as ec_user
                 ON ec_order.ec_user_id=ec_user.ec_user_id
                 INNER JOIN {$table_prefix}site_visitor as site_visitor
                 ON site_visitor.site_visitor_id=ec_user.site_visitor_id
            WHERE ( ".join(' AND ',$query)." ) $sql_filter
            ORDER BY ec_order.ec_date_created DESC
            LIMIT $start,$rows_per_page";
    //prn($query);
    $orders=\e::db_getrows($query);
  # -------------------- extract data from database - end ----------------------

  # ---------------- adjust list - begin ---------------------------------------
  $cnt=count($orders);
  $url_view_details_prefix=site_root_URL.'/index.php?action=ec/order/view&ec_order_id=';
  for($i=0;$i<$cnt;$i++)
  {
      $orders[$i]['url_view_details']=$url_view_details_prefix.$orders[$i]['ec_order_id'];
      $orders[$i]['ec_order_status_text']=text('ec_order_status_'.$orders[$i]['ec_order_status']);
      $orders[$i]['ec_order_currency_code']=$this_site_info['ec_currency'];
  }
  //prn($orders);
  # ---------------- adjust list - end -----------------------------------------

  # ---------------- paging links - begin --------------------------------------
    $url_page_refix=site_root_URL.'/index.php?'.query_string('^start$|^'.session_name().'$').'&start=';
    $n_records=\e::db_getonerow("SELECT FOUND_ROWS() AS n_records");
    $n_records=$n_records['n_records'];
    $pages = Array();
    $imin=max(0,$start-10*$rows_per_page);
    $imax=min($n_records,$start+10*$rows_per_page);
    // prn($imin,$imax,$n_records);
    if($imin>0)
    {
        $pages[]=Array( 'URL'=>$url_page_refix.'0','innerHTML' => '[1]' );
        $pages[]=Array( 'URL'=>'#','innerHTML' => '...' );
    }

    for($i=$imin;$i<$imax; $i=$i+$rows_per_page)
    {
        if( $i==$start )
        {
            $pages[]=Array(
                    'URL'=>'#'
                   ,'innerHTML' => '<b>['.(1+$i/$rows_per_page).']</b>'
            );
        }
        else
        {
            $pages[]=Array(
                    'URL'=>$url_page_refix.$i
                   ,'innerHTML' => (1+$i/$rows_per_page)
            );
        }
    }
    if($imax<$n_records)
    {
      $last_page=floor($n_records/rows_per_page);
      if($last_page>0)
      {
          $pages[]=Array( 'URL'=>'#','innerHTML' => '...' );
          $pages[]=Array(
            'URL'=>$url_page_refix.($last_page*rows_per_page)
           ,'innerHTML' => "[".($last_page+1)."]"
          );
      }
    }
    //prn('$pages=',$pages);
  # ---------------- paging links - end ----------------------------------------
# ----------------- get list of site visitor orders - end ----------------------

# ----------------- draw site visitor orders - begin ---------------------------
# get site template
  $ec_item_template = site_get_template($this_site_info,'template_ec_order_customer_list');


if(isset($_SESSION['site_visitor_info']['is_logged']) && $_SESSION['site_visitor_info']['is_logged'])
{
   $vyvid=" {$_SESSION['site_visitor_info']['site_visitor_login']}! ".site_visitor_draw_menu($_SESSION['site_visitor_info'],$this_site_info).'<br/><br/>';
}
else $vyvid='';

# ----------------- draw site visitor orders - end -----------------------------
  $vyvid.=
  process_template( $ec_item_template
                    ,Array(
                           'orders'=>$orders,
                           'n_records'=>$n_records,
                           'text'=>$txt,
                           'site'=>$this_site_info,
                           'pages'=>$pages,
                           'start'=>$start+1,
                           'finish'=>$start+count($orders),
                           'hidden_fields'=>hidden_form_elements('^filter'),
                           'filter'=>$filter
                     )
  );


  # get site menu
    $menu_groups = get_menu_items($this_site_info['id'],0,$lang);


  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array('title'=>$txt['EC_orders']
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

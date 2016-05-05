<?php
/*
  Constant definition
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
// $table_prefix='cms_';
define('default_action','main');
$main_template_name='design/default';


$default_user_info=Array(
  'id'             =>0
 ,'user_login'     =>'guest'
 ,'user_password'  =>'none'
 ,'full_name'      =>'guest'
 ,'telephone'      =>''
 ,'email'          =>''
 ,'is_logged'      =>false
);

define('image_file_extensions','gif,jpg,png,jpeg');



define('ec_item_hide'    , 1);
define('ec_item_show'    , 2);
define('ec_item_sell'    , 4);
define('ec_item_reserved', 8);
define('ec_item_sold'    ,16);
$ec_item_publication_states=Array(
   ec_item_hide=>'ec_item_hide',
   ec_item_show=>'ec_item_show',
   ec_item_show|ec_item_sell=>'ec_item_show_and_sell',
   ec_item_show|ec_item_reserved =>'ec_item_show_as_reserved'
);


//define('ec_order_status_new',1);
//define('ec_order_status_under_processing',2);
//define('ec_order_status_completed',1024);
//define('ec_order_status_rejected',2048);

define('length_units','cm|in|mm|ft');
define('weight_units','g|kg|pnd');
define('events','order_created,order_updated');

define('email2sms',serialize(Array('%s@beeline.ua'=>'beeline','38096%s@sms.kyivstar.net'=>'kyivstar +38(096)...')));

// cachectime in seconds
define('cachetime',300);


// define('custom_session_name','PHPSESSID');


define('debug_level_show_sql_query',1);
define('debug_level_show_sql_errors',2);


$default_site_visitor_info=Array(
      'site_visitor_id'=>0
     ,'site_visitor_password'=>''
     ,'site_visitor_login'=>'Anonymous'
     ,'site_visitor_email'=>''
     ,'site_visitor_home_page_url'=>''
   );
?>
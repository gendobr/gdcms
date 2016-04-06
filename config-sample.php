<?php
/*
  Site configuration
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
define('debug', false);


define('site_title', 'CMS');

define('site_charset', 'UTF-8');
//define('site_charset', 'windows-1251');

//------------------------ URLs - begin ----------------------------------------
define('site_root_URL', "http" . (isset($_SERVER['HTTPS']) ? 's' : '') . "://{$_SERVER['HTTP_HOST']}/cms"); // ZSU
define('site_public_URL', "http://{$_SERVER['HTTP_HOST']}/cms"); // ZSU

$config->APPLICATION_URL=site_root_URL;


define('site_URL', site_root_URL . '/index.php');
define('sites_root_URL', 'http://127.0.0.1/_sites');
//------------------------ URLs - end ------------------------------------------

//----------------------- directories -- begin ---------------------------------
// root directory of the site
// define('local_root', '/home/dobro/wwwroot/cms');
$config->APP_ROOT='/home/dobro/wwwroot/cms';

// where script are located
// define('script_root', local_root . '/scripts');
$config->SCRIPT_ROOT=$config->APP_ROOT . '/scripts';

// where templates are located
// define('template_root', local_root . '/templates');
$config->TEMPLATE_ROOT=$config->APP_ROOT . '/templates';

// where sites are located
// define('sites_root', '/home/dobro/wwwroot/_sites');
$config->SITES_ROOT='/home/dobro/wwwroot/_sites';
        
        
// where template are cached
// define('template_cache_root', $config->APP_ROOT . '/template_cache');
$config->CACHE_ROOT=$config->APP_ROOT . '/template_cache';

$config->LOGGER_CONFIG_FILE=$config->APP_ROOT.'/appender.properties';
//----------------------- directories -- end -----------------------------------

//----------------------- database parameters -- begin -------------------------
$config->db_host = "localhost";
$config->db_user = "user";
$config->db_pass = "user";
$config->db_name = "cms_utf8";
$config->db_charset = "utf8";
$config->db_table_prefix = 'cms_';
//----------------------- database parameters -- end ---------------------------
// ---------------------------- mailer options -- begin ------------------------
/*
  define('word_wrap',50);
  define('IsHTML',false);
  define('mail_IsSendMail',false);
  define('mail_IsSMTP',true);
  define('mail_SMTPhost','10.1.100.153');
  define('mail_SMTPAuth',false);
  define('mail_SMTPAuth_Username','gen');
  define('mail_SMTPAuth_Password','');
  define('mail_FromAddress','gen@zsu.zp.ua');
  define('mail_FromName','Site Admin');
 */
define('word_wrap', 50);
define('IsHTML', true);
define('mail_IsMail', false);
define('mail_IsSendMail', false);
define('mail_IsSMTP', true);
define('mail_SMTPhost', '10.1.100.153');
define('mail_SMTPAuth', false);
define('mail_SMTPAuth_Username', 'gen');
define('mail_SMTPAuth_Password', '*****');
define('mail_FromAddress', 'gen@znu.edu.ua');
define('mail_FromName', 'Site Admin');

// ---------------------------- mailer options -- end --------------------------

date_default_timezone_set('Europe/Kiev');

define('default_language', 'ukr');

define('rows_per_page', 10);

// define('use_custom_sessions', false);
$config->PHPSESSID='PHPSESSID';

// where SMARTY scripts are located
define('SMARTY_DIR', $config->SCRIPT_ROOT . '/smarty/libs/');

// regexp
define('allowed_file_extension', 'doc|jpg|png|gif|zip|rar|html|htm|rtf|pdf|css|js|txt|djvu|djv|xml|xsl|ppt|xls|swf|pml|cml|jpeg|ico|docx|otf|bz2|gz|7z|odt|xlsx|xlsm|xltx|xltm|xlam|docx|docm|dotx|dotm|pptx|pptm|potx|potm|ppam|ppsx|ppsm|svg|eot|woff|ttf');

define('apw', md5('qzwxdcft'));

define('liqpay_merchant_id', '******************');
define('liqpay_merchant_sign', '******************');
define('liqpay_test_mode', true);

define('ec_order_status', 'new,completed,rejected,under_processing');

define('ec_cart_check_product_amount', false);


define('gallery_small_image_width', 300);
define('gallery_small_image_height', 240);


// number of emails which can be sent at once
define('emails_at_once',1);



define('defaultToVisualEditor', 1);



# ----------------------------- urls without mod_rewrite - begin ---------------------------
// ++++++++++++
//define('url_pattern_category', site_public_URL . "/index.php?action=category/browse&site_id={site_id}&lang={lang}&category_id={category_id}&path={path}&category_code={category_code}");
define('url_pattern_category', 'http://gen.znu.edu.ua/_sites/znu_main/{lang}/{path}');


define('url_pattern_gallery_category', site_public_URL . "/index.php?action=gallery/photogallery&rozdilizformy={rozdilizformy}&site_id={site_id}&lang={lang}&start={start}&keywords={keywords}");
define('url_pattern_ec_category', site_public_URL . "/index.php?action=ec/item/browse&site_id={site_id}&lang={lang}&ec_category_id={ec_category_id}");

define('url_ec_item_order_now_pattern', site_public_URL . "/index.php?action=ec/cart/add&ec_item_lang=%s&ec_item_id=%s");
define('url_ec_item_buy_now_pattern', site_public_URL . "/index.php?action=ec/order/new&ec_item_lang=%s&ec_item_id=%s&site_id=%s");
define('url_ec_item_details_pattern', site_public_URL . "/index.php?action=ec/item/view&ec_item_lang={ec_item_lang}&ec_item_code={ec_item_code}&ec_item_id={ec_item_id}");

define('url_pattern_gallery_image', site_public_URL . "/index.php?action=gallery/photo&site_id={site_id}&lang={lang}&item={item}");

# ----------------------------- urls without mod_rewrite - end -----------------------------
# ----------------------------- urls using mod_rewrite in apache - begin -------------------
//define('url_template_news_details', sites_root_URL . "/news_details.php?news_id={news_id}&lang={lang}&news_code={news_code}");
define('url_template_news_details', site_public_URL . "/index.php?action=news/view_details&news_id={news_id}&lang={lang}&news_code={news_code}");




define('url_prefix_news_list', sites_root_URL . "/news.php?");
define('url_prefix_guestbook', "/cms/index.php?action=gb/guestbook&");
define('url_prefix_search', sites_root_URL . "/search.php?");
# ----------------------------- urls using mod_rewrite in apache - end ---------------------



define('gallery_big_image_width', 800);
define('gallery_big_image_height', 800);


define('url_template_news_list', site_public_URL . "/index.php?action=news/view&site_id={site_id}&lang={lang}&{other_parameters}");
define('url_template_news_list_other_parameters', "{key}={value}&"); // template for one (key, value) pair
define('url_template_news_list_ignore_parameters', "/PHPSESSID|action/i"); // regular expression



define('search_spider_key','jfdklsjkj98127987iuhfskjahfkjj656hhhh');
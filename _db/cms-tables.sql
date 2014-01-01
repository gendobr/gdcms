/*
SQLyog Enterprise - MySQL GUI v7.13 
MySQL - 5.0.22-community-nt : Database - cms
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

/*Table structure for table `cms_category` */

DROP TABLE IF EXISTS `cms_category`;

CREATE TABLE `cms_category` (
  `category_id` bigint(20) unsigned NOT NULL auto_increment,
  `site_id` bigint(20) NOT NULL default '0',
  `category_code` varchar(50) default NULL,
  `category_title` varchar(255) NOT NULL default 'new category',
  `category_description` text,
  `category_concept` text,
  `start` bigint(20) NOT NULL default '0',
  `finish` bigint(20) NOT NULL default '0',
  `is_deleted` tinyint(1) default '0',
  `deep` int(11) NOT NULL default '0',
  `is_part_of` bigint(20) default NULL,
  `see_also` text,
  `is_visible` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`category_id`),
  KEY `start` (`start`),
  KEY `finish` (`finish`),
  KEY `category_code` (`category_code`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_cart` */

DROP TABLE IF EXISTS `cms_ec_cart`;

CREATE TABLE `cms_ec_cart` (
  `ec_cart_id` int(11) NOT NULL auto_increment,
  `ec_item_uid` varchar(50) default NULL,
  `ec_item_id` int(11) default NULL,
  `ec_item_lang` varchar(5) default NULL,
  `ec_item_title` varchar(255) default NULL,
  `ec_item_price` float default NULL,
  `ec_item_currency` varchar(5) default NULL,
  `ec_item_size` varchar(255) default NULL,
  `ec_item_weight` varchar(255) default NULL,
  `ec_cart_amount` int(11) default NULL,
  `ec_cart_item` text,
  `ec_order_id` int(11) default NULL,
  `site_id` int(11) default NULL,
  PRIMARY KEY  (`ec_cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_category` */

DROP TABLE IF EXISTS `cms_ec_category`;

CREATE TABLE `cms_ec_category` (
  `ec_category_id` bigint(20) unsigned NOT NULL auto_increment,
  `site_id` bigint(20) NOT NULL default '0',
  `ec_category_code` varchar(50) default NULL,
  `ec_category_title` varchar(255) NOT NULL default 'new category',
  `ec_category_description` text,
  `ec_category_concept` text,
  `start` bigint(20) NOT NULL default '0',
  `finish` bigint(20) NOT NULL default '0',
  `is_deleted` tinyint(1) default '0',
  `deep` int(11) NOT NULL default '0',
  `is_part_of` bigint(20) default NULL,
  `see_also` text,
  `is_visible` tinyint(1) NOT NULL default '1',
  `ec_item_onnullamount` varchar(255) default NULL,
  PRIMARY KEY  (`ec_category_id`),
  KEY `start` (`start`),
  KEY `finish` (`finish`),
  KEY `category_code` (`ec_category_code`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_category_item_field` */

DROP TABLE IF EXISTS `cms_ec_category_item_field`;

CREATE TABLE `cms_ec_category_item_field` (
  `ec_category_item_field_id` bigint(20) unsigned NOT NULL auto_increment,
  `site_id` bigint(20) default NULL,
  `ec_category_id` bigint(20) default NULL,
  `ec_category_item_field_title` text,
  `ec_category_item_field_options` text,
  `ec_category_item_field_ordering` int(11) default NULL,
  PRIMARY KEY  (`ec_category_item_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_category_item_field_value` */

DROP TABLE IF EXISTS `cms_ec_category_item_field_value`;

CREATE TABLE `cms_ec_category_item_field_value` (
  `ec_item_id` int(11) default NULL,
  `ec_item_lang` varchar(3) collate cp1251_bin default NULL,
  `ec_category_item_field_id` int(11) default NULL,
  `ec_category_item_field_value` varchar(255) collate cp1251_bin default NULL,
  UNIQUE KEY `NewIndex1` (`ec_item_id`,`ec_item_lang`,`ec_category_item_field_id`),
  KEY `NewIndex2` (`ec_item_id`),
  KEY `NewIndex3` (`ec_category_item_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `cms_ec_currency` */

DROP TABLE IF EXISTS `cms_ec_currency`;

CREATE TABLE `cms_ec_currency` (
  `ec_currency_code` varchar(5) NOT NULL,
  `ec_curency_title` varchar(25) default NULL,
  PRIMARY KEY  (`ec_currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_item` */

DROP TABLE IF EXISTS `cms_ec_item`;

CREATE TABLE `cms_ec_item` (
  `ec_item_id` bigint(20) unsigned NOT NULL auto_increment,
  `ec_item_lang` char(3) NOT NULL default 'ukr',
  `site_id` bigint(20) unsigned NOT NULL default '0',
  `ec_item_title` varchar(255) NOT NULL default '',
  `ec_item_content` longtext,
  `ec_item_cense_level` tinyint(4) NOT NULL default '0',
  `ec_item_last_change_date` datetime default NULL,
  `ec_item_abstract` text,
  `ec_item_tags` text,
  `ec_item_price` float NOT NULL default '0',
  `ec_item_currency` varchar(3) NOT NULL default 'UAH',
  `ec_item_amount` int(11) NOT NULL default '0',
  `ec_producer_id` int(11) NOT NULL default '0',
  `ec_category_id` int(11) NOT NULL default '0',
  `ec_item_onnullamount` varchar(255) default NULL,
  `ec_item_mark` varchar(80) default NULL,
  `ec_item_uid` varchar(50) default NULL,
  `ec_item_size` varchar(255) default NULL,
  `ec_item_material` varchar(255) default NULL,
  `ec_item_weight` varchar(25) default NULL,
  `ec_item_img` text,
  PRIMARY KEY  (`ec_item_id`,`ec_item_lang`),
  KEY `site_id` (`site_id`),
  KEY `NewIndex1` (`ec_item_uid`),
  KEY `NewIndex2` (`ec_producer_id`),
  KEY `NewIndex3` (`ec_category_id`),
  KEY `NewIndex4` (`ec_item_last_change_date`),
  FULLTEXT KEY `title` (`ec_item_title`,`ec_item_content`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_item_category` */

DROP TABLE IF EXISTS `cms_ec_item_category`;

CREATE TABLE `cms_ec_item_category` (
  `ec_item_id` int(11) NOT NULL default '0',
  `category_id` int(11) NOT NULL default '0',
  UNIQUE KEY `pkey` (`ec_item_id`,`category_id`),
  KEY `ec_item_id` (`ec_item_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_item_comment` */

DROP TABLE IF EXISTS `cms_ec_item_comment`;

CREATE TABLE `cms_ec_item_comment` (
  `ec_item_comment_id` int(11) NOT NULL auto_increment,
  `ec_item_comment_sender_name` varchar(100) default NULL,
  `ec_item_comment_body` text,
  `site_id` int(11) default NULL,
  `ec_item_id` int(11) default NULL,
  `ec_item_lang` varchar(5) default NULL,
  `ec_item_comment_datetime` datetime default NULL,
  PRIMARY KEY  (`ec_item_comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_item_tags` */

DROP TABLE IF EXISTS `cms_ec_item_tags`;

CREATE TABLE `cms_ec_item_tags` (
  `ec_item_id` int(11) NOT NULL default '0',
  `ec_item_tag` varchar(255) NOT NULL default '',
  `site_id` int(11) default NULL,
  PRIMARY KEY  (`ec_item_id`,`ec_item_tag`),
  KEY `NewIndex1` (`ec_item_id`),
  KEY `NewIndex2` (`ec_item_tag`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_item_variant` */

DROP TABLE IF EXISTS `cms_ec_item_variant`;

CREATE TABLE `cms_ec_item_variant` (
  `ec_item_variant_id` int(10) unsigned NOT NULL auto_increment,
  `ec_item_id` int(11) default NULL,
  `ec_item_lang` varchar(10) default NULL,
  `ec_item_variant_description` text,
  `ec_item_variant_price_correction` varchar(50) default NULL,
  `ec_item_variant_indent` int(11) NOT NULL default '0',
  `ec_item_variant_ordering` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ec_item_variant_id`),
  KEY `NewIndex1` (`ec_item_id`),
  KEY `NewIndex2` (`ec_item_lang`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_order` */

DROP TABLE IF EXISTS `cms_ec_order`;

CREATE TABLE `cms_ec_order` (
  `ec_order_id` int(11) NOT NULL auto_increment,
  `ec_date_created` datetime default NULL,
  `site_id` int(11) default NULL,
  `ec_order_status` varchar(32) default NULL,
  `ec_order_total` double default NULL,
  `ec_user_id` int(11) default NULL,
  `ec_order_hash` varchar(128) default NULL,
  `ec_order_paid` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`ec_order_id`),
  KEY `NewIndex1` (`ec_date_created`),
  KEY `NewIndex2` (`ec_user_id`),
  KEY `NewIndex3` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_order_history` */

DROP TABLE IF EXISTS `cms_ec_order_history`;

CREATE TABLE `cms_ec_order_history` (
  `ec_order_history_id` bigint(20) unsigned NOT NULL auto_increment,
  `ec_order_history_title` varchar(255) default NULL,
  `ec_order_history_details` text,
  `ec_order_history_date` datetime default NULL,
  `ec_order_history_action` varchar(64) default NULL,
  `ec_order_id` bigint(20) default NULL,
  `site_visitor_id` bigint(20) default NULL,
  `site_id` bigint(20) default NULL,
  `user_id` bigint(20) default NULL,
  PRIMARY KEY  (`ec_order_history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_producer` */

DROP TABLE IF EXISTS `cms_ec_producer`;

CREATE TABLE `cms_ec_producer` (
  `ec_producer_id` int(11) NOT NULL auto_increment,
  `ec_producer_title` varchar(255) default NULL,
  `ec_producer_abstract` text,
  `ec_producer_description` text,
  `site_id` int(11) default NULL,
  `ec_producer_img` varchar(255) default NULL,
  PRIMARY KEY  (`ec_producer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_producer_comment` */

DROP TABLE IF EXISTS `cms_ec_producer_comment`;

CREATE TABLE `cms_ec_producer_comment` (
  `ec_producer_comment_id` int(11) NOT NULL auto_increment,
  `ec_producer_comment_sender_name` varchar(100) default NULL,
  `ec_producer_comment_body` text,
  `site_id` int(11) default NULL,
  `ec_producer_id` int(11) default NULL,
  `ec_producer_comment_datetime` datetime default NULL,
  PRIMARY KEY  (`ec_producer_comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ec_user` */

DROP TABLE IF EXISTS `cms_ec_user`;

CREATE TABLE `cms_ec_user` (
  `ec_user_id` bigint(20) unsigned NOT NULL auto_increment,
  `ec_user_name` varchar(100) default NULL,
  `ec_user_telephone` varchar(100) default NULL,
  `ec_user_icq` varchar(100) default NULL,
  `ec_user_delivery_city` varchar(255) default NULL,
  `ec_user_delivery_region` varchar(255) default NULL,
  `ec_user_delivery_street_address` varchar(255) default NULL,
  `ec_user_delivery_suburb` varchar(255) default NULL,
  `ec_user_uid` varchar(32) default NULL,
  `site_visitor_id` bigint(20) default NULL,
  PRIMARY KEY  (`ec_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_forum_list` */

DROP TABLE IF EXISTS `cms_forum_list`;

CREATE TABLE `cms_forum_list` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `site_id` int(10) unsigned default NULL,
  `name` varchar(80) NOT NULL default '',
  `is_premoderated` tinyint(1) NOT NULL default '0',
  `about` text,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_forum_msg` */

DROP TABLE IF EXISTS `cms_forum_msg`;

CREATE TABLE `cms_forum_msg` (
  `id` int(11) NOT NULL auto_increment,
  `site_id` int(10) unsigned NOT NULL default '0',
  `forum_id` int(10) unsigned NOT NULL default '0',
  `thread_id` int(11) NOT NULL default '0',
  `name` varchar(80) NOT NULL default '',
  `email` varchar(40) NOT NULL default '',
  `www` varchar(255) NOT NULL default '',
  `subject` varchar(255) NOT NULL default '',
  `msg` text NOT NULL,
  `data` datetime NOT NULL default '0000-00-00 00:00:00',
  `is_first_msg` tinyint(1) NOT NULL default '0',
  `is_visible` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `site_id` (`site_id`),
  KEY `f` (`forum_id`),
  KEY `t` (`thread_id`),
  FULLTEXT KEY `name` (`name`,`email`,`www`,`subject`,`msg`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_forum_thread` */

DROP TABLE IF EXISTS `cms_forum_thread`;

CREATE TABLE `cms_forum_thread` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `site_id` int(10) unsigned NOT NULL default '0',
  `forum_id` int(10) unsigned NOT NULL default '0',
  `subject` varchar(80) NOT NULL default '',
  `data` datetime NOT NULL default '0000-00-00 00:00:00',
  `is_visible` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `site_id` (`site_id`,`forum_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_gb` */

DROP TABLE IF EXISTS `cms_gb`;

CREATE TABLE `cms_gb` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(35) NOT NULL default '',
  `email` varchar(40) NOT NULL default '',
  `adress` text NOT NULL,
  `tema` varchar(80) NOT NULL default '',
  `text` text NOT NULL,
  `data` datetime NOT NULL default '0000-00-00 00:00:00',
  `site` int(11) default NULL,
  `is_visible` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  KEY `data` (`data`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_golos_pynannja` */

DROP TABLE IF EXISTS `cms_golos_pynannja`;

CREATE TABLE `cms_golos_pynannja` (
  `id` int(11) NOT NULL auto_increment,
  `site_id` bigint(20) NOT NULL default '0',
  `is_active` tinyint(1) NOT NULL default '0',
  `title` text NOT NULL,
  `n_respondents` int(11) NOT NULL default '0',
  `poll_type` varchar(10) NOT NULL default 'radio',
  PRIMARY KEY  (`id`),
  KEY `site_id` (`site_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_golos_vidpovidi` */

DROP TABLE IF EXISTS `cms_golos_vidpovidi`;

CREATE TABLE `cms_golos_vidpovidi` (
  `id` int(11) NOT NULL auto_increment,
  `pynannja_id` int(2) NOT NULL default '0',
  `site_id` bigint(20) NOT NULL default '0',
  `html` varchar(250) NOT NULL default '',
  `golosiv` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `site_id` (`site_id`),
  KEY `pynannja_id` (`pynannja_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_languages` */

DROP TABLE IF EXISTS `cms_languages`;

CREATE TABLE `cms_languages` (
  `id` char(3) NOT NULL default '',
  `name` varchar(255) default NULL,
  `is_visible` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_listener` */

DROP TABLE IF EXISTS `cms_listener`;

CREATE TABLE `cms_listener` (
  `listener_id` bigint(20) unsigned NOT NULL auto_increment,
  `listener_event` varchar(255) character set cp1251 default NULL,
  `site_id` bigint(20) default NULL,
  `user_id` bigint(20) default NULL,
  `listener_sendto` varchar(255) character set cp1251 default NULL,
  `listener_action` varchar(255) character set cp1251 default NULL,
  `listener_template` varchar(255) character set cp1251 default NULL,
  PRIMARY KEY  (`listener_id`),
  KEY `NewIndex1` (`listener_event`),
  KEY `NewIndex2` (`site_id`),
  KEY `NewIndex3` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251 COLLATE=cp1251_bin CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `cms_menu_group` */

DROP TABLE IF EXISTS `cms_menu_group`;

CREATE TABLE `cms_menu_group` (
  `id` bigint(20) unsigned NOT NULL default '0',
  `site_id` bigint(20) NOT NULL default '0',
  `page_id` bigint(20) NOT NULL default '0',
  `lang` char(3) NOT NULL default 'ukr',
  `html` text NOT NULL,
  `url` text,
  `icon` text,
  `code` varchar(50) default NULL,
  PRIMARY KEY  (`id`,`lang`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_menu_item` */

DROP TABLE IF EXISTS `cms_menu_item`;

CREATE TABLE `cms_menu_item` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `menu_group_id` bigint(20) NOT NULL default '0',
  `lang` char(3) NOT NULL default 'ukr',
  `html` text NOT NULL,
  `url` text,
  `description` text,
  `attributes` text,
  `ordering` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_ml` */

DROP TABLE IF EXISTS `cms_ml`;

CREATE TABLE `cms_ml` (
  `i` bigint(20) unsigned NOT NULL auto_increment,
  `a` varchar(255) default NULL,
  `d` datetime default NULL,
  `u` varchar(255) default NULL,
  `s` text,
  PRIMARY KEY  (`i`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_news` */

DROP TABLE IF EXISTS `cms_news`;

CREATE TABLE `cms_news` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `lang` char(3) NOT NULL default 'ukr',
  `site_id` bigint(20) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL,
  `content` longtext,
  `cense_level` tinyint(2) NOT NULL default '0',
  `last_change_date` datetime default NULL,
  `abstract` text,
  `category_id` bigint(20) NOT NULL default '0',
  `tags` text,
  PRIMARY KEY  (`id`,`lang`),
  KEY `site_id` (`site_id`),
  FULLTEXT KEY `title` (`title`,`content`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_news_category` */

DROP TABLE IF EXISTS `cms_news_category`;

CREATE TABLE `cms_news_category` (
  `news_id` int(11) NOT NULL default '0',
  `category_id` int(11) NOT NULL default '0',
  UNIQUE KEY `pkey` (`news_id`,`category_id`),
  KEY `news_id` (`news_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_news_tags` */

DROP TABLE IF EXISTS `cms_news_tags`;

CREATE TABLE `cms_news_tags` (
  `news_id` bigint(20) default NULL,
  `lang` varchar(10) default NULL,
  `tag` varchar(100) default NULL,
  UNIQUE KEY `p_k` (`news_id`,`lang`,`tag`),
  KEY `news_id` (`news_id`),
  KEY `lang` (`lang`),
  KEY `tag` (`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_notification_queue` */

DROP TABLE IF EXISTS `cms_notification_queue`;

CREATE TABLE `cms_notification_queue` (
  `notification_queue_id` bigint(20) unsigned NOT NULL auto_increment,
  `notification_queue_to` varchar(255) default NULL,
  `notification_queue_subject` varchar(255) default NULL,
  `notification_queue_body` text,
  `notification_queue_attempts` int(11) NOT NULL default '0',
  `notification_queue_function` varchar(255) default NULL,
  PRIMARY KEY  (`notification_queue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_page` */

DROP TABLE IF EXISTS `cms_page`;

CREATE TABLE `cms_page` (
  `id` bigint(20) unsigned NOT NULL default '0',
  `lang` char(3) NOT NULL default 'ukr',
  `site_id` bigint(20) unsigned NOT NULL default '0',
  `title` varchar(128) NOT NULL default '',
  `content` longtext,
  `cense_level` tinyint(2) NOT NULL default '0',
  `last_change_date` datetime default NULL,
  `is_under_construction` tinyint(1) NOT NULL default '1',
  `is_home_page` tinyint(1) NOT NULL default '0',
  `map_position` int(11) NOT NULL default '0',
  `map_indent` int(11) NOT NULL default '0',
  `abstract` text,
  `path` varchar(255) default NULL,
  `category_id` bigint(20) NOT NULL default '0',
  `to_export` tinyint(4) NOT NULL default '0',
  `delete_file` varchar(255) default NULL,
  PRIMARY KEY  (`id`,`lang`),
  KEY `site_id` (`site_id`),
  FULLTEXT KEY `title` (`title`,`content`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_page_menu_group` */

DROP TABLE IF EXISTS `cms_page_menu_group`;

CREATE TABLE `cms_page_menu_group` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `site_id` bigint(20) NOT NULL default '0',
  `page_id` bigint(20) NOT NULL default '0',
  `menu_group_id` bigint(20) NOT NULL default '0',
  `lang` char(3) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_photogalery` */

DROP TABLE IF EXISTS `cms_photogalery`;

CREATE TABLE `cms_photogalery` (
  `id` int(11) NOT NULL auto_increment,
  `photos` text,
  `photos_m` text,
  `rozdil` text,
  `rozdil2` text,
  `pidpys` text,
  `autor` text,
  `rik` varchar(4) default NULL,
  `site` text,
  `vis` char(2) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_session` */

DROP TABLE IF EXISTS `cms_session`;

CREATE TABLE `cms_session` (
  `id` varchar(50) NOT NULL default '',
  `sess_data` text,
  `expires` bigint(20) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_site` */

DROP TABLE IF EXISTS `cms_site`;

CREATE TABLE `cms_site` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `dir` varchar(20) NOT NULL default '',
  `title` varchar(255) NOT NULL default '',
  `url` varchar(255) NOT NULL default '',
  `cense_level` tinyint(2) unsigned NOT NULL default '0',
  `template` varchar(50) default 'main',
  `is_gb_enabled` tinyint(1) NOT NULL default '0',
  `is_search_enabled` tinyint(1) NOT NULL default '0',
  `is_news_line_enabled` tinyint(1) NOT NULL default '0',
  `is_site_map_enabled` tinyint(1) NOT NULL default '0',
  `is_forum_enabled` tinyint(1) NOT NULL default '1',
  `is_poll_enabled` tinyint(1) NOT NULL,
  `search_validate_url` text,
  `search_exclude_url` text,
  `is_gallery_enabled` tinyint(1) NOT NULL default '0',
  `is_ec_enabled` tinyint(1) NOT NULL default '0',
  `ec_currency` varchar(10) NOT NULL default 'UAH',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_site_search` */

DROP TABLE IF EXISTS `cms_site_search`;

CREATE TABLE `cms_site_search` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `site_id` bigint(20) NOT NULL default '0',
  `url` varchar(255) default NULL,
  `size` int(11) default NULL,
  `title` text,
  `words` text,
  `date_indexed` datetime default NULL,
  `is_valid` tinyint(4) NOT NULL default '1',
  `checksum` varchar(32) default NULL,
  PRIMARY KEY  (`id`),
  KEY `date_indexed` (`date_indexed`),
  KEY `site_id` (`site_id`),
  KEY `url` (`url`),
  KEY `checksum` (`checksum`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_site_user` */

DROP TABLE IF EXISTS `cms_site_user`;

CREATE TABLE `cms_site_user` (
  `site_id` bigint(20) unsigned NOT NULL default '0',
  `user_id` bigint(20) unsigned NOT NULL default '0',
  `level` tinyint(2) unsigned NOT NULL default '0',
  UNIQUE KEY `site_id` (`site_id`,`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_site_visitor` */

DROP TABLE IF EXISTS `cms_site_visitor`;

CREATE TABLE `cms_site_visitor` (
  `site_visitor_id` bigint(11) unsigned NOT NULL auto_increment,
  `site_visitor_password` varchar(32) NOT NULL default '',
  `site_visitor_login` varchar(30) default NULL,
  `site_visitor_email` varchar(100) NOT NULL default '',
  `site_visitor_home_page_url` varchar(255) default NULL,
  `site_visitor_code` varchar(255) default NULL,
  PRIMARY KEY  (`site_visitor_id`),
  UNIQUE KEY `name_nick` (`site_visitor_login`),
  KEY `NewIndex1` (`site_visitor_email`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_site_visitor_education` */

DROP TABLE IF EXISTS `cms_site_visitor_education`;

CREATE TABLE `cms_site_visitor_education` (
  `id` bigint(11) NOT NULL auto_increment,
  `site_id` bigint(20) NOT NULL default '0',
  `site_visitor_id` bigint(20) NOT NULL default '0',
  `edu_year` int(4) NOT NULL default '0',
  `faculty` varchar(100) default NULL,
  `speciality` text NOT NULL,
  `place` varchar(100) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*Table structure for table `cms_user` */

DROP TABLE IF EXISTS `cms_user`;

CREATE TABLE `cms_user` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `user_login` varchar(255) NOT NULL default '',
  `user_password` varchar(32) NOT NULL default '',
  `full_name` varchar(255) default NULL,
  `telephone` varchar(128) default NULL,
  `email` varchar(128) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
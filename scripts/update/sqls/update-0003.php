<?php

return [
    'ALTER TABLE `<<tp>>site` ADD COLUMN `extra_setting` TEXT NULL AFTER `is_rssaggegator_enabled`',
    "CREATE TABLE `<<tp>>photo` (
        `photo_id` bigint(20) NOT NULL AUTO_INCREMENT,
        `photo_category_id` int(11) DEFAULT NULL,
        `site_id` int(11) DEFAULT NULL,
        `photo_visible` tinyint(1) NOT NULL DEFAULT '1',
        `photo_title` text COLLATE utf8_bin,
        `photo_author` text COLLATE utf8_bin,
        `photo_description` text COLLATE utf8_bin,
        `photo_year` int(11) DEFAULT NULL,
        `photo_imgfile` text COLLATE utf8_bin,
        PRIMARY KEY (`photo_id`)
      ) ENGINE=InnoDB AUTO_INCREMENT=252 DEFAULT CHARSET=utf8 COLLATE=utf8_bin
      "

];

<?php

return [
	"ALTER TABLE `<<tp>>news` ADD INDEX `lang_2`(`lang`, `site_id`, `cense_level`)",
	"ALTER TABLE `<<tp>>news` ADD INDEX `lscd` (`lang`, `site_id`, `cense_level`, `last_change_date`); "
];

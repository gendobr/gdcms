<?php

return [
	"CREATE TABLE <<tp>>cache ( 
	`uid` VARCHAR(64) NOT NULL, 
	`cachetime` BIGINT, 
	`cached_value` LONGTEXT, 
	PRIMARY KEY (`uid`) 
    );"
];

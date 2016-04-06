<?php




if(!isset($input_vars['key']) || $input_vars['key']!=search_spider_key){
    exit('Invalid key');
}
$timestart = microtime(true);
$GLOBALS['main_template_name'] = '';
\e::db_execute("ALTER TABLE `{$GLOBALS['table_prefix']}search_index_cache` DROP INDEX `wrds`");
\e::db_execute("DELETE FROM {$GLOBALS['table_prefix']}search_index_cache");
\e::db_execute("INSERT INTO {$GLOBALS['table_prefix']}search_index_cache 
	(id, 
	site_id, 
	url, 
	size, 
	title, 
	words, 
	date_indexed,
        lang
	)
	SELECT 
	id, 
	site_id, 
	url, 
	size, 
	title, 
	words, 
	date_indexed,
        lang
	FROM {$GLOBALS['table_prefix']}search_index
	WHERE is_valid<>0");
\e::db_execute("ALTER TABLE `{$GLOBALS['table_prefix']}search_index_cache` ADD FULLTEXT INDEX `wrds` (`words`)"); 
echo (microtime(true)-$timestart)."s OK";
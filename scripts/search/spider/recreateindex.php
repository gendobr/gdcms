<?php



$key=md5(local_root);
if(!isset($input_vars['key']) || $input_vars['key']!=$key){
    exit('Invalid key');
}
$timestart = microtime(true);
$GLOBALS['main_template_name'] = '';
db_execute("ALTER TABLE `{$GLOBALS['table_prefix']}search_index_cache` DROP INDEX `wrds`");
db_execute("DELETE FROM {$GLOBALS['table_prefix']}search_index_cache");
db_execute("INSERT INTO {$GLOBALS['table_prefix']}search_index_cache 
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
	FROM cms8_search_index
	WHERE is_valid<>0");
db_execute("ALTER TABLE `{$GLOBALS['table_prefix']}search_index_cache` ADD FULLTEXT INDEX `wrds` (`words`)"); 
echo (microtime(true)-$timestart)."s OK";
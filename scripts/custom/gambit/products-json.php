<?php
run('custom/gambit/config');
run('custom/gambit/functions');
run('site/menu');
$GLOBALS['main_template_name']='';


//------------------- site info - begin ----------------------------------------
$site_id = isset($input_vars['site_id']) ? ((int) $input_vars['site_id']) : 0;
$this_site_info = get_site_info($site_id);
//prn($this_site_info);
if (!$this_site_info['id']) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
$input_vars['site_id'] = $this_site_info['id'];
//------------------- site info - end ------------------------------------------
//
//
//
// ------------------ get language - begin -------------------------------------
$lang = get_language('lang,interface_lang');
global $txt;
$txt = load_msg($lang);
// prn($lang);
// ------------------ get language - end ---------------------------------------

$request = [
    'orderBy' => \e::request('orderBy', ''),
    'start' => \e::cast('integer', \e::request('start', 0)),
    'keywords' => \e::request('keywords', ''),
    'idCategory' => join(',',array_filter(\e::cast('integer[]', \e::request('idCategory', 0)),function($x){return $x>0;} )),
    'idSeller' => \e::request('idSeller', '')
];
$idCategory = explode(',',$request['idCategory']);

// prn($request);

$cacher = new \core\cache(\e::config('CACHE_ROOT'));



$gambit_product_list = gambit_request(\e::config('gambit_product_list'),$request);
$gambit_product_list=$gambit_product_list['data'];
// prn($gambit_product_list);


$gambit_categories = $cacher->cachedData(
        "{$this_site_info['dir']}/categories.txt",  //
        \e::config('gambit_cache_time'),  //
        function() use($site_id, $lang) {
            $result=gambit_request(\e::config('gambit_category_options'), new stdClass);
            $result=$result['data'];
            $cnt=count($result);
            for($i=0; $i<$cnt; $i++){
                $result[$i]['url']=\e::url_public([
	                "action"=>"custom/gambit/products",
	                "site_id"=>$site_id,
	                "lang"=>$lang,
	                'idCategory'=>$result[$i]['id']
	        ]);
            }
            return $result;
        }
);
// prn($gambit_categories);

$gambit_sellers = $cacher->cachedData(
        "{$this_site_info['dir']}/sellers.txt",  //
        \e::config('gambit_cache_time'),  //
        function() use($site_id, $lang) {
            $result = gambit_request(\e::config('gambit_seller_options'),new stdClass);
            $result=$result['data'];
            $cnt=count($result);
            for($i=0; $i<$cnt; $i++){
                $result[$i]['url']=\e::url_public([
	                "action"=>"custom/gambit/seller",
	                "site_id"=>$site_id,
	                "lang"=>$lang,
	                'idSeller'=>$result[$i]['id']
	        ]);
            }
            return $result;
       }
);
// prn($gambit_sellers);



$gambit_unit_options = $cacher->cachedData(
        "{$this_site_info['dir']}/units.txt",  //
        \e::config('gambit_cache_time'),  //
        function() {
            $result = gambit_request(\e::config('gambit_unit_options'), new stdClass );
            $gambit_unit_options=[];
            foreach($result['data'] as $dt){
                $gambit_unit_options[$dt['id']]=$dt['name'];
            }
            return $gambit_unit_options;
        }
);
// prn($gambit_unit_options);

// ------------------- draw - begin --------------------------------------------

$vyvid=[
    'categories'=>$gambit_categories,
    'sellers'=>$gambit_sellers,
    'units'=>$gambit_unit_options,
    'products'=>$gambit_product_list
];
echo json_encode($vyvid);

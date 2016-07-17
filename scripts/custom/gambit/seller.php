<?php
run('custom/gambit/config');
run('site/menu');
run('site/page/page_view_functions');

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

$sellerId=\e::cast('integer', \e::request('sellerId', 0));

$request=[
   'id'=>$sellerId
];

$cacher = new \core\cache(\e::config('CACHE_ROOT'));

$seller = $cacher->cachedData(
        "{$this_site_info['dir']}/product{$sellerId}",  //
        \e::config('gambit_cache_time'),  //
        function() use($request) {
            
            // prn("####");
            $url = \e::config('gambit_seller');
            // prn("####", $url);
            $fields_string=urlencode(json_encode($request));
            //open connection
            $ch = curl_init();
            // 
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($request));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HEADER, "Content-Type: application/json;charset=utf-8");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            //execute post
            $result = curl_exec($ch);

            //close connection
            curl_close($ch);
            
            
            // prn($result);
            
            return json_decode($result, true);
        }
);
$seller=$seller['data'];
// prn($product);

$gambit_category_options = $cacher->cachedData(
        "{$this_site_info['dir']}/categories.txt",  //
        \e::config('gambit_cache_time'),  //
        function() use($request) {
            
            // prn("####");
            $url = \e::config('gambit_category_options');
            // prn("####", $url);
            $fields_string=urlencode(json_encode($request));
            //open connection
            $ch = curl_init();
            // 
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($request));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HEADER, "Content-Type: application/json;charset=utf-8");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            //execute post
            $result = curl_exec($ch);

            //close connection
            curl_close($ch);
            
            // prn($result);
            $result = json_decode($result, true);
            $options=[];
            foreach($result['data'] as $dt){
                $options[$dt['id']]=$dt['name'];
            }
            return $options;
        }
);
// prn($gambit_category_options);

$gambit_seller_options = $cacher->cachedData(
        "{$this_site_info['dir']}/sellers.txt",  //
        \e::config('gambit_cache_time'),  //
        function() use($request) {
            
            // prn("####");
            $url = \e::config('gambit_seller_options');
            // prn("####", $url);
            $fields_string=urlencode(json_encode($request));
            //open connection
            $ch = curl_init();
            // 
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($request));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HEADER, "Content-Type: application/json;charset=utf-8");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            //execute post
            $result = curl_exec($ch);

            //close connection
            curl_close($ch);
            
            // prn($result);
            $result = json_decode($result, true);
            $options=[];
            foreach($result['data'] as $dt){
                $options[$dt['id']]=$dt['name'];
            }
            return $options;
        }
);
// prn($gambit_seller_options);



$gambit_unit_options = $cacher->cachedData(
        "{$this_site_info['dir']}/units.txt",  //
        \e::config('gambit_cache_time'),  //
        function() use($request) {
            
            // prn("####");
            $url = \e::config('gambit_unit_options');
            // prn("####", $url);
            $fields_string=urlencode(json_encode($request));
            //open connection
            $ch = curl_init();
            // 
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($request));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HEADER, "Content-Type: application/json;charset=utf-8");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            //execute post
            $result = curl_exec($ch);

            //close connection
            curl_close($ch);
            
            
            // prn($result);
            $result = json_decode($result, true);
            $gambit_unit_options=[];
            foreach($result['data'] as $dt){
                $gambit_unit_options[$dt['id']]=$dt['name'];
            }
            return $gambit_unit_options;
        }
);
// prn($gambit_unit_options);



$productrequest = [
    'orderBy' => \e::request('orderBy', ''),
    'start' => \e::cast('integer', \e::request('start', 0)),
    'keywords' => \e::request('keywords', ''),
    'idCategory' => \e::cast('integer', \e::request('idCategory', 0)),
    'idSeller' => \e::cast('integer', \e::request('idSeller', 0))
];

function getProductList($request){
            // prn("####");
            $url = \e::config('gambit_product_list');
            // prn("####", $url);
            $fields_string=urlencode(json_encode($request));
            //open connection
            $ch = curl_init();
            // 
            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, count($request));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
            curl_setopt($ch, CURLOPT_HEADER, "Content-Type: application/json;charset=utf-8");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, false);
            //execute post
            $result = curl_exec($ch);

            //close connection
            curl_close($ch);
            // prn($result);
            return json_decode($result, true);
        }

$gambit_product_list = getProductList($productrequest);
$gambit_product_list=$gambit_product_list['data'];
// prn($gambit_product_list);


// ------------------- draw - begin --------------------------------------------

$vyvid='';




    
$vyvid.="
    <script type=\"application/javascript\">
    var product=".json_encode($product).";
    </script>
    <div class=\"row page-content\">
        <div class=\"col-md-12\">
            <div class=\"seller-view-info\">
                <span class=\"seller-view-info-tel\">
                    {$seller['telephone']}
                </span>
                <span class=\"seller-view-info-email\">
                    {$seller['email']}
                </span>
            </div>
        </div>
    </div>
";        

$vyvid.="
    <div class=\"seller-view-image-list\"><!--
";
foreach($seller['image'] as $image){
    $vyvid.="
             --><a class=\"seller-view-image\" 
                   rel=\"lightbox-seller\"
                   href=\"{$image['big']}\"
                   style=\"background-image:url('{$image['small']}')\">
                </a><!-- 
    ";    
}
$vyvid.="
    --></div>
";


                
$vyvid.="
<div class=\"product-tiles row\">
    ";

$product_view_template=\e::url_public([
    'action'=>'custom/gambit/product',
    'site_id'=>$site_id,
    'lang'=>$lang,
    'productId'=>'{productId}'
]);
$prdid=rawurlencode('{productId}');

foreach($gambit_product_list['rows'] as $row){
    $href=str_replace($prdid,$row['id'],$product_view_template);
    $vyvid.="
    <div class=\"col-xs-12 col-sm-6 col-md-4 col-lg-3 product-tiles-item\">
        <a class=\"product-tiles-item-image\" href=\"{$href}\" 
           style='". 
            ( 
                (isset($row['image']) && 
                 isset($row['image'][0]) && 
                 isset($row['image'][0]['small']))
                ? ( 'background-image:url("'.$row['image'][0]['small'].'")' ) :''
            )."'></a>
        <span class=\"product-tiles-item-\">
            <h2><a class=\"product-tiles-item-title\" href=\"{$href}\" title=\"".htmlspecialchars($row['name'])."\">{$row['name']}</a></h2>
            <span class=\"product-tiles-item-price\">".round($row['costOneUnit'], 2)."&nbsp;".\e::config('gambit_currency')."</span>
            <span class=\"product-tiles-item-category\">
            <a href=\"".\e::url_update(['idCategory'=>$row['idCategory']],'/idCategory/')."\">"
            .(isset($gambit_category_options[$row['idCategory']])
              ?$gambit_category_options[$row['idCategory']]
              :"Категория {$row['idCategory']}")."</a></span>
            <span class=\"product-tiles-item-seller\">
               <a href=\"".\e::url_update(['idSeller'=>$row['idSeller']],'/idSeller/')."\">".
                    (isset($gambit_seller_options[$row['idSeller']])
                     ?$gambit_seller_options[$row['idSeller']]
                     :"Продавец {$row['idSeller']}")
                ."</a></span>
            <button type=\"button\" 
                    class=\"btn btn-success product-tiles-item-btn\" 
                    ng-click=\"addToCart(row,1)\">В корзину</button>
        </span>
    </div>
    ";
}

$vyvid.="
    <br>
    <nav class=\"col-md-12\">
      <ul class=\"pagination\" style=\"margin-top:0px;\">
";

$pattern=str_replace(rawurlencode('{start}'),'{start}',\e::url_update(['start'=>'{start}'],'/start/'));
$paging=  \core\grid::get_paging_links($pattern, $gambit_product_list['nRows'], $productrequest['start'], 6);
// prn($paging);
foreach($paging as $pg){
    $vyvid.="<li class=\"pgbtn {$pg['class']}\">";
    if(strlen($pg['URL'])==0){
        $vyvid.="<span>{$pg['innerHTML']}</span>";
    }else{
        $vyvid.="<a href=\"{$pg['URL']}\">{$pg['innerHTML']}</a>";
    }
    $vyvid.="</li>";    
}

$vyvid.="
      </ul>
    </nav>
";

$vyvid.="
</div>
";





$navbar="";
$navbar.="
<h4 class=\"navbarBlockHeader\">Категории</h4>    
";
$class=(0==$productrequest['idCategory'])?"active":'';
$navbar.="
    <div class=\"categoryLink $class\">
    <a href=\"".
        \e::url_public([
            "action"=>"custom/gambit/products",
            "site_id"=>$site_id,
            "lang"=>$lang,
            'idCategory'=>0
        ])
    ."\">Все категории<a>
    </div>
    ";

foreach($gambit_category_options as $key=>$val){
    $class=($key==$productrequest['idCategory'])?"active":'';
    $navbar.="
        <div class=\"categoryLink $class\">
        <a href=\"".
            \e::url_public([
                "action"=>"custom/gambit/products",
                "site_id"=>$site_id,
                "lang"=>$lang,
                'idCategory'=>$key
            ])
        ."\">{$val}<a>
        </div>
        ";
}


$navbar.="
<h4 class=\"navbarBlockHeader\">Продавцы</h4>    
";
$class=(0==$productrequest['idSeller'])?"active":'';
$navbar.="
    <div class=\"categoryLink $class\">
    <a href=\"".
        \e::url_public([
            "action"=>"custom/gambit/seller",
            "site_id"=>$site_id,
            "lang"=>$lang,
            'idSeller'=>0
        ])
    ."\">Все продавцы<a>
    </div>
    ";
foreach($gambit_seller_options as $key=>$val){
    $class=($key==$productrequest['idSeller'])?"active":'';
    $navbar.="
        <div class=\"categoryLink $class\">
        <a href=\"".
            \e::url_public([
                "action"=>"custom/gambit/seller",
                "site_id"=>$site_id,
                "lang"=>$lang,
                'idSeller'=>$key
            ])
        ."\">{$val}<a>
        </div>
        ";
}



$navbar.="
    <h4 class=\"navbarBlockHeader\">Поиск</h4>  
    <form action=\"index.php\">
    <input type=\"hidden\" name=\"action\" value=\"custom/gambit/products\">
    <input type=\"hidden\" name=\"site_id\" value=\"{$site_id}\">
    <input type=\"hidden\" name=\"lang\" value=\"{$lang}\">
    <select class=\"form-control\"  title=\"Категория\">
        <option value=\"\" class=\"placeholder\"></option>
        ".\core\form::draw_options($request['idCategory'], $gambit_category_options)."
    </select>
    <select class=\"form-control\" title=\"Продавец\">
        <option value=\"\" class=\"placeholder\"></option>
        ".\core\form::draw_options($request['idSeller'], $gambit_seller_options)."
    </select>
    <input type=\"text\"  class=\"form-control\"
           placeholder=\"Ключевые слова\"  value=\"".htmlspecialchars($request['keywords'])."\"
           title=\"Ключевые слова\" >
    <input type=\"submit\" value=\"Найти\" class=\"btn btn-default btn-success\">
    </form>
";


$html="
    <div class=\"row\">
       <div class=\"col-xs-6 col-sm-4 col-md-3 col-lg-2\">{$navbar}</div>
       <div class=\"col-xs-6 col-sm-8  col-md-9 col-lg-10\">{$vyvid}</div>
    </div>
    ";





global $main_template_name;
$main_template_name = '';

$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    if(!isset($this_site_info['extra_setting']['lang'][$lang_list[$i]['lang']])){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url'] = \e::config('url_prefix_search') . "interface_lang={$lang_list[$i]['name']}&lang={$lang_list[$i]['name']}&site_id=" . join(',', $siteIds) . "&keywords=" . rawurlencode(isset($input_vars['keywords']) ? $input_vars['keywords'] : '');
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}

$menu_groups = get_menu_items($site_id, 0, $lang);

$this_site_info['title'] = get_langstring($this_site_info['title'], $input_vars['lang']);
$file_content = process_template($this_site_info['template'], Array(
    'page' => Array(
          'title' => $seller['name']
        , 'header' => ''
        , 'content' => $html
        , 'abstract' => ''
        , 'site_id' => $site_id
        , 'lang' => $lang
    )
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
echo $file_content;
// ---------------------- draw - end -------------------------------------------


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

$productId=\e::cast('integer', \e::request('productId', 0));

$request=[
   'id'=>$productId
];


$cacher = new \core\cache(\e::config('CACHE_ROOT'));

$gambit_product_info = $cacher->cachedData(
        "{$this_site_info['dir']}/product{$productId}",  //
        \e::config('gambit_cache_time'),  //
        function() use($request) {
            
            // prn("####", $request);
            $url = \e::config('gambit_product');
            //prn("####", $url);
            //$fields_string=urlencode(json_encode($request));
            $fields_string=json_encode($request);
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
            
            
            //prn($result);
            
            return json_decode($result, true);
        }
);
$product=$gambit_product_info['data'];
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

// ------------------- draw - begin --------------------------------------------

$vyvid='';





     $vyvid.="

    <div class=\"row page-content\">
        <div class=\"col-md-4\">
";

$cnt=count($product['image']);
if($cnt>0){
   foreach($product['image'] as $image){
     $vyvid.="
        <a href=\"{$image['big']}\" target=\"_blank\" class=\"single-product-image\" rel=\"lightbox-prod\">
            <img src=\"{$image['small']}\">
        </a>
     ";
   }
}


$vyvid.="
        </div>
        <div class=\"col-md-8\">
            <!-- <h2>{$product['name']}</h2> -->
            <div class=\"row\">
                <div class=\"col-md-4\">
                    <div class=\"single-product-price\">
                    ".round($product['costOneUnit'],2)."&nbsp;".\e::config('gambit_currency')."
                    </div>
                </div>
                <div class=\"col-md-4\">
                    <div class=\"product-category\"><a href=\"".
                         \e::url_public([
                             'action'=>'custom/gambit/products',
                             'site_id'=>$site_id,
                             'lang'=>$lang,
                             'idCategory'=>$product['idCategory']
                         ])."\">". 
                         (isset($gambit_category_options[$product['idCategory']])?$gambit_category_options[$product['idCategory']]:"Category {$product['idCategory']}")
                    ."</a></div>
                    <div class=\"product-seller\"><a href=\"".
                         \e::url_public([
                             'action'=>'custom/gambit/seller',
                             'site_id'=>$site_id,
                             'lang'=>$lang,
                             'idSeller'=>$product['idSeller']
                         ])."\">".
                         (isset($gambit_seller_options[$product['idSeller']])
                          ?$gambit_seller_options[$product['idSeller']]
                          :"Seller {$product['idSeller']}")
                         ."</a></div>
                    <div class=\"product-amount\">".round($product['amount'],3)." ".
                    (isset($gambit_unit_options[$product['idUnit']])
                    ?$gambit_unit_options[$product['idUnit']]
                    :"{$product['idUnit']}" )
                    ."</div>
                </div>
            </div>
            <div>
                <button data-id=\"{$product['id']}\" type=\"button\" class=\"addToCardBtn btn btn-success\">".text('EC_item_add_to_cart_now')."</button>
            </div>
                <br>
            <div class=\"product-description short\">{$product['shortDescription']}</div>
            <div class=\"product-description full\">{$product['fullDescription']}</div>    
        </div>
    </div>



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
    <div ng-repeat=\"row in grid.rows\" class=\"col-md-3 col-md-2 product-tiles-item\">
        <a class=\"product-tiles-item-image\" href=\"{$href}\" 
           style=\"". 
            ( 
                (isset($row['image']) && 
                 isset($row['image'][0]) && 
                 isset($row['image'][0]['small']))
                ? ( 'background-image:url("'.$row['image'][0]['small'].'")' ) :''
            )."\"></a>
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
$paging=  \core\grid::get_paging_links($pattern, $gambit_product_list['nRows'], $request['start'], 6);
// prn($paging);
foreach($paging as $pg){
    $vyvid.="<li class=\"pgbtn {{pg.class}}\">";
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




    
$vyvid.="
<script type=\"application/javascript\">
var product=".json_encode($product).";
window.onReady = window.onReady || [];
window.onReady.push(function(){


    $('.addToCardBtn').click(function(ev){
	// alert(88888);
        var tg = $(ev.currentTarget);
        var prd=product;
        // console.log(prd);
        var cart = window.lib.addToCart(prd);
        // console.log(cart);
        $('.cartInformer').empty().append(window.lib.getCartDOM(cart));

        $('#myModal').modal() 
        $('#myModalMessage').empty().html('<h3>'+prd.name+'</h3><p>'+parseFloat(prd.costOneUnit).toFixed(2)+'&nbsp;грн</p>');
    });

    var html='<!-- Modal -->'
    html+='<div id=\"myModal\" class=\"modal fade\" role=\"dialog\">'
    html+='  <div class=\"modal-dialog\">'
    html+='    <!-- Modal content-->'
    html+='    <div class=\"modal-content\">'
    html+='      <div class=\"modal-header\">'
    html+='        <button type=\"button\" class=\"close\" data-dismiss=\"modal\">&times;</button>'
    html+='        <h4 class=\"modal-title\">".smarty_txt([
                'lang'=>$lang,
                'variants'=>"eng=Product added to your cart::rus=Товар добавлен в корзину::ukr=Товар додано до кошика"
            ])."</h4>'
    html+='      </div>'
    html+='      <div class=\"modal-body\" id=\"myModalMessage\">'
    html+='        <p>Some text in the modal.</p>'
    html+='      </div>'
    html+='      <div class=\"modal-footer\">'
    html+='        <button type=\"button\" class=\"btn btn-default\" data-dismiss=\"modal\">".smarty_txt([
                'lang'=>$lang,
                'variants'=>"eng=Close::rus=Закрыть::ukr=Закрити"
            ])."</button>'
    html+='      </div>'
    html+='    </div>'
    html+='  </div>'
    html+='</div>'
    $('body').append(html);


});
</script>

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
    //$lang_list[$i]['url'] = \e::config('url_prefix_search') . "interface_lang={$lang_list[$i]['name']}&lang={$lang_list[$i]['name']}&site_id=" . join(',', $siteIds) . "&keywords=" . rawurlencode(isset($input_vars['keywords']) ? $input_vars['keywords'] : '');
    $lang_list[$i]['url'] = \e::url_public([
            'action'=>'custom/gambit/product',
            'site_id'=>$this_site_info['id'],
            'lang'=>$lang_list[$i]['name'],
            'productId'=>\e::request('productId', '')
        ]);

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}

$menu_groups = get_menu_items($site_id, 0, $lang);

$this_site_info['title'] = get_langstring($this_site_info['title'], $input_vars['lang']);
$file_content = process_template($this_site_info['template'], Array(
    'page' => Array(
          'title' => $product['name']
        , 'header' => ''
        , 'page_meta_tags' => '
	    <meta name="description" content="'.htmlspecialchars($product['shortDescription']).'">
	    <meta name="keywords" content="'.htmlspecialchars($product['shortDescription']).'">
	    '
        , 'content' => $vyvid
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


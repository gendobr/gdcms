<?php
run('custom/gambit/config');
run('custom/gambit/functions');
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

$sellerId=\e::cast('integer', \e::request('idSeller', 0));

$sellerRequest=[
   'id'=>$sellerId
];


$productRequest = [
    'orderBy' => \e::request('orderBy', ''),
    'start' => \e::cast('integer', \e::request('start', 0)),
    'keywords' => \e::request('keywords', ''),
    'idCategory' => join(',',array_filter(\e::cast('integer[]', \e::request('idCategory', 0)),function($x){return $x>0;} )),
    'idSeller' => \e::request('idSeller', '')
];
$idCategory = explode(',',$request['idCategory']);


$cacher = new \core\cache(\e::config('CACHE_ROOT'));

$seller = $cacher->cachedData(
        "{$this_site_info['dir']}/product{$sellerId}",  //
        \e::config('gambit_cache_time'),  //
        function() use($sellerRequest) {
            return gambit_request(\e::config('gambit_seller'),$sellerRequest);
        }
);
//prn($seller); exit();
$seller=$seller['data'];

$gambit_category_options = $cacher->cachedData(
        "{$this_site_info['dir']}/categories.txt",  //
        \e::config('gambit_cache_time'),  //
        function() {
            $result = gambit_request(\e::config('gambit_category_options'),new stdClass);
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
        function() {
            $result = gambit_request(\e::config('gambit_seller_options'), new stdClass);
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
        function() {
            $result = gambit_request(\e::config('gambit_unit_options'), new stdClass);
            $gambit_unit_options=[];
            foreach($result['data'] as $dt){
                $gambit_unit_options[$dt['id']]=$dt['name'];
            }
            return $gambit_unit_options;
        }
);
// prn($gambit_unit_options);


$gambit_product_list = gambit_request(\e::config('gambit_product_list'), $productRequest);
$gambit_product_list=$gambit_product_list['data'];
// prn($gambit_product_list);


// ------------------- draw - begin --------------------------------------------

$vyvidSeller='';




    
$vyvidSeller.="
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
            </div><br><br>
        </div>
    </div>
";        

$vyvidSeller.="
    <div class=\"seller-view-image-list\"><!--
";
foreach($seller['image'] as $image){
    $vyvidSeller.="
             --><a class=\"seller-view-image\" 
                   rel=\"lightbox-seller\"
                   href=\"{$image['big']}\"
                   style=\"background-image:url('{$image['small']}')\">
                </a><!-- 
    ";    
}
$vyvidSeller.="
    --></div>
";


                
$vyvid="
<div class=\"product-tiles row\">
    ";

$product_view_template=\e::url_public([
    'action'=>'custom/gambit/product',
    'site_id'=>$site_id,
    'lang'=>$lang,
    'productId'=>'{productId}'
]);
$prdid=rawurlencode('{productId}');


$product_list_json=[];

foreach($gambit_product_list['rows'] as $row){

    $product_list_json[$row['id']]=$row;

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
              :"category {$row['idCategory']}")."</a></span>
            <span class=\"product-tiles-item-seller\">
               <a href=\"".\e::url_update(['idSeller'=>$row['idSeller']],'/idSeller/')."\">".
                    (isset($gambit_seller_options[$row['idSeller']])
                     ?$gambit_seller_options[$row['idSeller']]
                     :"seller {$row['idSeller']}")
                ."</a></span>
            <button type=\"button\" 
                    class=\"btn btn-success product-tiles-item-btn addToCart\" 
                    data-id=\"{$row['id']}\">".text('EC_item_add_to_cart_now')."</button>
        </span>
    </div>
    ";
}

$vyvid.="
<script type=\"application/javascript\">
var products=".json_encode($product_list_json).";
window.onReady = window.onReady || [];
window.onReady.push(function(){
   $('.addToCart').click(function(ev){
      var tg = $(ev.currentTarget);
      var prd=products[tg.attr('data-id')];
      // console.log(prd);
      var cart = window.lib.addToCart(prd);
      // console.log(cart);
      $('.cartInformer').empty().append(window.lib.getCartDOM(cart));

      $('#myModal').modal() 
      $('#myModalMessage').empty().html('<h3>'+prd.name+'</h3><p>'+parseFloat(prd.costOneUnit).toFixed(2)+'&nbsp;грн</p>');

   })

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

<div class=\"dropdown\" style=\"display:inline-block;\">
  <button class=\"btn btn-default dropdown-toggle\" type=\"button\" 
    aria-expanded=\"false\" aria-haspopup=\"true\" role=\"button\" 
    data-toggle=\"dropdown\" id=\"dLabel\">
";

$headerFound=false;
foreach($gambit_seller_options as $key=>$val){
    if($key==$productRequest['idSeller']){
       $headerFound=true;
       $navbar.=$val;
    }
}
if(!$headerFound){
   $navbar.="Все продавцы";
}

$navbar.="

    <span class=\"caret\"></span>
  </button>
  <ul class=\"dropdown-menu\" aria-labelledby=\"dLabel\">
";



$class=(0==$productRequest['idSeller'])?"active":'';
$navbar.="
    <li class=\" $class\">
    <a href=\"".
        \e::url_public([
            "action"=>"custom/gambit/seller",
            "site_id"=>$site_id,
            "lang"=>$lang,
            'idSeller'=>0
        ])
    ."\">Все продавцы<a>
    </li>
    ";
foreach($gambit_seller_options as $key=>$val){
    $class=($key==$productRequest['idSeller'])?"active":'';
    $navbar.="
        <li class=\" $class\">
        <a href=\"".
            \e::url_public([
                "action"=>"custom/gambit/seller",
                "site_id"=>$site_id,
                "lang"=>$lang,
                'idSeller'=>$key
            ])
        ."\">{$val}</a>
        </li>
        ";
}


$navbar.="

  </ul>
</div>
";





$navbar.="
<div class=\"dropdown\" style=\"display:inline-block;\">

  <button class=\"btn btn-default dropdown-toggle\" type=\"button\" 
    aria-expanded=\"false\" aria-haspopup=\"true\" role=\"button\" 
    data-toggle=\"dropdown\" id=\"dLabel2\">
";

$headerFound=false;

$delim='';
foreach($gambit_category_options as $key=>$val){
    if(in_array($key,$idCategory)){
       $headerFound=true;
       $navbar.=$delim.$val;
       $delim=', ';
    }
}

if(!$headerFound){
   $navbar.="Все категории";
}

$navbar.="

    <span class=\"caret\"></span>
  </button>
  <ul class=\"dropdown-menu\" id=\"dLabel2\">
";

    $class=(0==$request['idCategory'])?"active":'';
    $navbar.="
        <li class=\"$class\">
        <a href=\"".
            \e::url_public([
            "action"=>"custom/gambit/products",
            "site_id"=>$site_id,
            "lang"=>$lang,
            'idCategory'=>0
        ])
        ."\">Все категории</a>
        </li>
        ";

    foreach($gambit_category_options as $key=>$val){
        $class=(in_array($key,$idCategory))?"active":'';
        $navbar.="
            <li class=\"$class\">
            <a href=\"".
                \e::url_public([
                    "action"=>"custom/gambit/products",
                    "site_id"=>$site_id,
                    "lang"=>$lang,
                    'idCategory'=>$key
                ])
            ."\">{$val}<a>
            </li>
            ";
    }



$navbar.="

  </ul>
</div>
";





/*
$navbar.="
    <h4 class=\"navbarBlockHeader\">Поиск</h4>  
    <form action=\"index.php\">
    <input type=\"hidden\" name=\"action\" value=\"custom/gambit/products\">
    <input type=\"hidden\" name=\"site_id\" value=\"{$site_id}\">
    <input type=\"hidden\" name=\"lang\" value=\"{$lang}\">
    <select class=\"form-control\"  title=\"Категория\" name=\"idCategory\">
        <option value=\"\" class=\"placeholder\"></option>
        ".\core\form::draw_options($request['idCategory'], $gambit_category_options)."
    </select>
    <select class=\"form-control\" title=\"Продавец\" name=\"idSeller\">
        <option value=\"\" class=\"placeholder\"></option>
        ".\core\form::draw_options($request['idSeller'], $gambit_seller_options)."
    </select>
    <input type=\"text\"  class=\"form-control\"
           placeholder=\"Ключевые слова\"  value=\"".htmlspecialchars($request['keywords'])."\"
           title=\"Ключевые слова\" >
    <input type=\"submit\" value=\"Найти\" class=\"btn btn-default btn-success\">
    </form>
";
*/





$html="
    <div class=\"row\">
       <div class=\"col-xs-12\">{$vyvidSeller}</div>
    </div>
    <div class=\"row\" style=\"padding-top:3rem;\">
        <div class=\"col-xs-4\">
        <div class=\"gridNRows\">
                <h4>Товаров : {$gambit_product_list['nRows']}</h4>
            </div>
       </div>
       <div class=\"col-xs-8\" style=\"text-align:right;\">
          {$navbar}
       </div>
    </div>
    <div class=\"row\">
       <div class=\"col-xs-12\">{$vyvid}</div>
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
    //$lang_list[$i]['url'] = \e::config('url_prefix_search') . "interface_lang={$lang_list[$i]['name']}&lang={$lang_list[$i]['name']}&site_id=" . join(',', $siteIds) . "&keywords=" . rawurlencode(isset($input_vars['keywords']) ? $input_vars['keywords'] : '');
    $lang_list[$i]['url'] = \e::url_public([
            'action'=>'custom/gambit/seller',
            'site_id'=>$this_site_info['id'],
            'lang'=>$lang_list[$i]['name'],
            'idCategory'=>join(',',$idCategory),
            'idSeller'=>\e::request('idSeller', '')
        ]);
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


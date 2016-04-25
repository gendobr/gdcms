<?php

/**
 * $input_vars['cat']  - category
 * $input_vars['keywords'] - keywords to search images and categories
 * $input_vars['start'] - the first paramater of SQL LIMIT expression, default value is 0
 * $input_vars['rows']  - the second paramater of SQL LIMIT expression, default value is rows_per_page constant (see configuration)
 */
$GLOBALS['main_template_name'] = '';
if (!function_exists('db_get_template')) {
    run('site/page/page_view_functions');
}

//---------------------- load language - begin ---------------------------------
if (isset($input_vars['interface_lang']) && strlen($input_vars['interface_lang']) > 0) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = $_SESSION['lang'];
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = $_SESSION['lang'];
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = \e::config('default_language');
}
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
//---------------------- load language - end -----------------------------------


$lang = $input_vars['lang'];
run('site/menu');

//------------------- site info - begin ----------------------------------------
if (isset($input_vars['site_id'])) {
    $site = $site_id = checkInt($input_vars['site_id']);
} elseif (isset($input_vars['site_id'])) {
    $site = $site_id = checkInt($input_vars['site_id']);
}
$this_site_info = get_site_info($site, $lang);

if (checkInt($this_site_info['id']) <= 0) {
    die($txt['Gallery_not_found']);
}
//------------------- site info - end ------------------------------------------

$_template = false;
if (isset($input_vars['template'])) {
    $input_vars['template'] = \core\fileutils::encode_file_name($input_vars['template']);
    $_template = site_get_template($this_site_info, $input_vars['template']);
}
if (!$_template) {
    $_template = site_get_template($this_site_info, 'template_photogallery_view_block');
}
# --------------------------- list of categories - begin -----------------------
run('gallery/gallery_images');
$vyvid = '';
$rozdilizformy = (isset($input_vars['cat'])) ? $input_vars['cat'] : '';
$keywords = (isset($input_vars['keywords'])) ? $input_vars['keywords'] : '';
$start = isset($input_vars['start']) ? (int) $input_vars['start'] : 0;
$rows = isset($input_vars['rows']) ? (int) $input_vars['rows'] : 0;
$orderBy = isset($input_vars['orderBy']) ? $input_vars['orderBy'] : '';

if ($rows <= 0) {
    $rows = rows_per_page;
}
$images = new GalleryImages($lang, $this_site_info, $start, $rozdilizformy, $keywords);
$images->rowsPerPage = $rows;
if ($orderBy) {
    $images->setOrderBy($orderBy);
}
if ($images->items_found > 0) {
    $vyvid = process_template($_template
            , Array(
        'images' => $images->list
        , 'site' => $this_site_info
        , 'lang' => $lang
            )
    );
}


# --------------------------- list of categories - end -------------------------
header('Content-Type:text/html; charset=' . site_charset);
header('Access-Control-Allow-Origin: *');

if (isset($input_vars['element'])) {
    echo "
    <html>
        <head>
            <META content=\"text/html; charset=" . site_charset . "\" http-equiv=\"Content-Type\">
        </head>
        <body>
    <div id=toinsert>
    <!--
          " . str_replace(Array('<!--', '-->'), Array('{*', '*}'), $vyvid) . "
     -->
    </div>
    <script type=\"text/javascript\">

    function decode(input) {
      return input.replace(/2~/g,'\"').replace(/1~/g,\"'\").replace(/~script/g,'<script').replace(/~\\/script/g,'</script');
    }
    function stripAndExecuteScript (text) {
        var scripts = '';
        var cleaned = text.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi, function(){
            scripts += arguments[1] + '\\n';
            return '';
        });


        var head = document.getElementsByTagName(\"head\")[0] ||
                      document.documentElement,
            script = document.createElement(\"script\");
        script.type = \"text/javascript\";
        try {
          // doesn't work on ie...
          script.appendChild(document.createTextNode(scripts));
        } catch(e) {
          // IE has funky script nodes
          script.text = scripts;
        }
        head.appendChild(script);
        head.removeChild(script);

        return cleaned;
    };


    // var from = document.getElementById('toinsert');
    //alert(from.innerHTML);
    var to;
    if(window.top)
    {
      //alert('window.top - OK');
      if(window.top.document)
      {
        //alert('window.top.document - OK');
        to = window.top.document.getElementById('{$input_vars['element']}');
        //alert(to);
        if(to)
        {
           //alert('element - OK');
           to.innerHTML = stripAndExecuteScript(decode('" . preg_replace("/\\s+/", ' ', str_replace(Array('"', "'", "\n", "\r", '<script', '</script'), Array('2~', '1~', ' ', ' ', '~script', '~/script'), $vyvid)) . "'));
        }
      }
    }

    </script>
        </body>
    </html>
    ";   
} else {
    echo $vyvid;
}
?>
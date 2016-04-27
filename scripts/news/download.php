<?php

/*
  Download news from list of urls like
 * 2015-02-13 http://some.server.com/news/12345
 * 2015-02-13 http://some.server.com/news/12346
 * 2015-02-13 http://some.server.com/news/12347
 */


$debug = false;
run('site/menu');
run('lib/http/class_pear');
run('lib/http/class_net_socket');
run('lib/http/class_net_url');
run('lib/http/class_http_request');
run('lib/simple_html_dom');

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);


// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
// ------------------ do download - begin --------------------------------------
if (isset($input_vars['url'])) {

    global $main_template_name;
    $main_template_name = '';


    $url = $input_vars['url'];
    if (!parse_url($url)) {
        echo '{"status":"error","message":"invalid URL"}';
        return;
    }
    $url = getAsciiUrl($url);

    $query = "SELECT count(*) as n FROM {$GLOBALS['table_prefix']}news WHERE site_id=$site_id AND LOCATE('" . \e::db_escape($url) . "',abstract)";
    //prn($query);
    $nnews =\e::db_getonerow($query);
    if ($nnews['n'] > 0) {
        echo '{"status":"error","message":"news already imported"}';
        return;
    }

    $lang = $input_vars['lang'];
    $lang_list = list_of_languages();
    $found = false;
    foreach ($lang_list as $ln) {
        if ($lang == $ln['lang']) {
            $found = true;
        }
    }
    if (!$found) {
        $lang = \e::config('default_language');
    }


    // 
    $site_id = $this_site_info['id'];

    $dateString = $input_vars['date'];
    if (preg_match("/^(\\d{2})(\\d{2})\$/", $dateString, $matches)) {
        $year = (int) date('Y');
        $month = $matches[1];
        $day = $matches[2];
        $last_change_date = date("Y-m-d H:i:s", mktime(12, 0, 0, $month, $day, $year));
    } elseif (checkDatetime($dateString)) {
        $last_change_date = date("Y-m-d H:i:s", strtotime($dateString));
    } else {
        $last_change_date = date("Y-m-d H:i:s");
    }
    $category_id = (int) $input_vars['category_id'];


    $content = '';
    $weight = 0;
    $creation_date = date('Y-m-d H:i:s');
    $news_code = '';
    $news_meta_info = '';
    $news_extra_1 = '';
    $news_extra_2 = '';
    $cense_level = $this_site_info['cense_level'];
    $tags = '';

    

    // ======= downloading one url = begin =====================================
    $obj_request = new HTTP_Request($url, Array(
                    'timeout'=>60
                    ,'allowRedirects'=>true
    ));
    // set_time_limit (100);
    $obj_request->sendRequest();
    sleep(5);

    $body = $obj_request->getResponseBody();

    $headers=$obj_request->getResponseHeader();
    # check if request was successful
    $success = $obj_request->getResponseCode();
    
    //echo $body; exit();
    // ======= downloading one url = end =======================================
    
    $html = str_get_html($body);
    if (!$html) {
        echo '{"status":"error","message":"cannot download URL"}';
        return;
    }
    
    $title = $html->find("title", 0);
    if ($title) {
        $title = $title->plaintext;
    } else {
        $title = '';
    }
    if(!$title){
        echo '{"status":"error","message":"title not found"}';
        return;
        //        $obj_request = new HTTP_Request('http://webcache.googleusercontent.com/search?q=cache:'.preg_replace('^\\w+:\\/\\/','',$url), Array(
        //                        'timeout'=>60
        //                        ,'allowRedirects'=>true
        //        ));
        //        // set_time_limit (100);
        //        $obj_request->sendRequest();
        //        sleep(5);
        //
        //        $body = $obj_request->getResponseBody();
        //
        //        $headers=$obj_request->getResponseHeader();
        //        # check if request was successful
        //        $success = $obj_request->getResponseCode();
        //
        //        $html = str_get_html($body);
        //        if (!$html) {
        //            echo '{"status":"error","message":"cannot download URL"}';
        //            return;
        //        }

    }
    
    
    // detect encoding
    $encoding=site_charset;
    foreach ($html->find('meta') as $element) {
        if (preg_match("/charset=([-a-z0-9]+)/i",$element->content, $matches)) {
            $encoding=strtoupper($matches[1]);
        }
    }
    // echo "site encoding = $encoding \n\n\n";
    // echo "\n\n\n\n";
    // echo $html->plaintext;
    // exit();

    $title = $html->find("title", 0);
    if ($title) {
        $title = $title->plaintext;
    } else {
        $title = '';
    }
    if($encoding!=site_charset){
        try {
            $title=iconv($encoding, site_charset, $title);
        } catch (Exception $e) {
        }
    }

    $abstract = '';
    foreach ($html->find('meta') as $element) {
        if ($element->name == 'description') {
            $abstract = $element->content;
        }
        if ($element->name == 'og:description') {
            $abstract = $element->content;
        }
    }
    if($encoding!=site_charset){
        try {
           $abstract=iconv($encoding, site_charset, $abstract);
        } catch (Exception $e) {
        }
    }
    $abstract.= "<p><a href=\"$url\" target=_blank>$url</a></p>";


    // calculate news id
    $query = "SELECT max(id) AS newid FROM {$table_prefix}news";
    $newid =\e::db_getonerow($query);
    $news_id = $newid = 1 + (int) $newid['newid'];

    $query = "
        INSERT INTO {$GLOBALS['table_prefix']}news 
	(id, 
	lang, 
	site_id, 
	title, 
	content, 
	cense_level, 
	last_change_date, 
	abstract, 
	category_id, 
	tags, 
	expiration_date, 
	weight, 
	creation_date, 
	news_code, 
	news_meta_info, 
	news_extra_1, 
	news_extra_2
	)
	VALUES
	({$news_id}, 
	'{$lang}', 
	'{$site_id}', 
	'" . \e::db_escape($title) . "', 
	'" . \e::db_escape($content) . "', 
	'{$cense_level}', 
	'{$last_change_date}', 
	'" . \e::db_escape($abstract) . "', 
        '{$category_id}', 
	'{$tags}', 
	 null, 
	'{$weight}', 
	'{$creation_date}', 
	'{$news_code}', 
	'{$news_meta_info}', 
	'{$news_extra_1}', 
	'{$news_extra_2}'
	);";
    //prn($query);
    \e::db_execute($query);


    $query = "insert into {$GLOBALS['table_prefix']}news_category(news_id, category_id) VALUES({$news_id},{$category_id})";
    \e::db_execute($query);
    echo '{"status":"success"}';
    return;
}


// ------------------ do download - end ----------------------------------------
# get list of all site categories
$query = "SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
$tmp = \e::db_getrows($query);
$list_of_categories = Array();
foreach ($tmp as $tm) {
    $list_of_categories[$tm['category_id']] = str_repeat(' + ', $tm['deep']) . get_langstring($tm['category_title']);
}
unset($tmp, $tm);
//prn($list_of_categories);


$input_vars['page_content'] = "
    <div>
        <div class=label>" . text('News_Category') . " : </div>
    	<select name=news_category  id=news_category>
           <option value=''></option>
           " . draw_options(0, $list_of_categories) . "
    	</select>    
    </div>
    <div>
        <div class=label>" . text('News_Sources') . " : </div>
<pre>
2014-01-23  http://some.server.com/news/1234
0123  http://some.server.com/news/1235
...
</pre>
        <textarea id=\"news_sources\" style=\"width:100%; height:300px;\"></textarea>

        <ol id=log></ol>
        <div id='loading' class=\"meter\" style=\"display:none;\"><span style=\"width: 100%\"></span></div>
    </div>
<input type=\"button\" id=\"doDownload\" value=\"" . text('News_start_import') . "\">
    

<script type=\"application/javascript\">
var newsList=[];

function startDownload(){
  var rows=$('#news_sources').val();
  if(rows.length>0){
    newsList=rows.split(/\\n/);
    var cid=$('#news_category').val();
    if(cid.length>0){
       $('#doDownload').hide();
       downloadNext();
    $('#doDownload').hide();
    }else{
       alert('" . text('News_Category') . "?????');
    }
  }else{
    $('#doDownload').show();
  }
}

function downloadNext(){
    if(newsList.length>0){
        var row=newsList[0].split(/[ \\t]+/);
        // console.log(row);
        $('#loading').show();
        $.ajax({
           type: \"POST\",
           url: \"index.php\",
           data: { action: \"news/download\", site_id: $site_id, url: row[1], date:row[0], category_id:$('#news_category').val(), lang:'{$_SESSION['lang']}'},
           dataType: \"json\"
        }).always(function( msg ) {
           var it=$('<li>' + msg.status + ' : '+row[1]+'</li>');
           $('#log').append(it);
           newsList.shift();
           document.getElementById('news_sources').value=newsList.join(\"\\n\");
           setTimeout(downloadNext, 20000);
        });    
    }else{
       $('#loading').hide();
       $('#doDownload').show();
       alert('DONE !!!');
    }
}

$(document).ready(function(){
   $(\"#doDownload\").click(startDownload);
});
</script>
<style type='text/css'>

.meter {
    background: none repeat scroll 0 0 rgb(85, 85, 85);
    border-radius: 25px;
    box-shadow: 0 -1px 1px rgba(255, 255, 255, 0.3) inset;
    height: 20px;
    margin: 60px 0 20px;
    padding: 10px;
    position: relative;
}
.meter > span {
    background-color: rgb(43, 194, 83);
    background-image: -moz-linear-gradient(center bottom , rgb(43, 194, 83) 37%, rgb(84, 240, 84) 69%);
    border-radius: 20px 8px 8px 20px;
    box-shadow: 0 2px 9px rgba(255, 255, 255, 0.3) inset, 0 -2px 6px rgba(0, 0, 0, 0.4) inset;
    display: block;
    height: 100%;
    overflow: hidden;
    position: relative;
}
.meter > span:after, .animate > span > span {
    animation: 2s linear 0s normal none infinite running move;
    background-image: -moz-linear-gradient(-45deg, rgba(255, 255, 255, 0.2) 25%, rgba(0, 0, 0, 0) 25%, rgba(0, 0, 0, 0) 50%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.2) 75%, rgba(0, 0, 0, 0) 75%, rgba(0, 0, 0, 0));
    background-size: 50px 50px;
    border-radius: 20px 8px 8px 20px;
    bottom: 0;
    content: \"\";
    left: 0;
    overflow: hidden;
    position: absolute;
    right: 0;
    top: 0;
    z-index: 1;
}
.animate > span:after {
    display: none;
}
@keyframes move {
0% {
    background-position: 0 0;
}
100% {
    background-position: 50px 50px;
}
}
.orange > span {
    background-color: rgb(241, 161, 101);
    background-image: -moz-linear-gradient(center top , rgb(241, 161, 101), rgb(243, 109, 10));
}
.red > span {
    background-color: rgb(240, 163, 163);
    background-image: -moz-linear-gradient(center top , rgb(240, 163, 163), rgb(244, 35, 35));
}
.nostripes > span > span, .nostripes > span:after {
    animation: 0s ease 0s normal none 1 running none;
    background-image: none;
}

</style>

         ";

//--------------------------- context menu -- begin ----------------------------
$input_vars['page_title'] = text("News_import");
$input_vars['page_header'] = text("News_import");

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
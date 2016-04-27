<?php

/*
  Download news from list of urls like
 * 2015-02-13 http://some.server.com/news/12345
 * 2015-02-13 http://some.server.com/news/12346
 * 2015-02-13 http://some.server.com/news/12347
 */


$debug = false;
run('site/menu');
//run('lib/http/class_pear');
//run('lib/http/class_net_socket');
//run('lib/http/class_net_url');
//run('lib/http/class_http_request');
run('lib/simple_html_dom');
//run('lib/charset/charset');
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

//    $query = "SELECT count(*) as n FROM {$GLOBALS['table_prefix']}news WHERE site_id=$site_id AND LOCATE('" . DbStr($url) . "',abstract)";
//    //prn($query);
//    $nnews =\e::db_getonerow($query);
//    if ($nnews['n'] > 0) {
//        echo '{"status":"error","message":"news already imported"}';
//        return;
//    }

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
        $last_change_date = date("Y-m-d H:i:s", mktime(0, 1, 1, $month, $day, $year));
    }elseif (preg_match("/^(\\d{2})\\.(\\d{2})\\.(\\d{2})\$/", $dateString, $matches)) {
        $year = 2000+$matches[3];
        $month = $matches[2];
        $day = $matches[1];
        $last_change_date = date("Y-m-d H:i:s", mktime(0, 1, 1, $month, $day, $year));
    }elseif (preg_match("/^(\\d{2})\\.(\\d{2})\\.(\\d{4})\$/", $dateString, $matches)) {
        $year = $matches[3];
        $month = $matches[2];
        $day = $matches[1];
        $last_change_date = date("Y-m-d H:i:s", mktime(0, 1, 1, $month, $day, $year));
    }elseif (checkDatetime($dateString)) {
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
    //    $obj_request = new HTTP_Request($url, Array(
    //                    'timeout'=>60
    //                    ,'allowRedirects'=>true
    //    ));
    //    // set_time_limit (100);
    //    $obj_request->sendRequest();
    //    sleep(10);
    //
    //    $body = $obj_request->getResponseBody();
    //
    //    $headers=$obj_request->getResponseHeader();
    //    # check if request was successful
    //    $success = $obj_request->getResponseCode();
    //    
    //    echo $body; exit();
    //

    // echo ':'.$url.';<br>';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url); // set url to post to 
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects 
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // times out after 20s 
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent:Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36");

    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, false);

    // curl_setopt($ch, CURLOPT_POST, 1); // set POST method 
    //curl_setopt($ch, CURLOPT_POSTFIELDS, "url=index%3Dbooks&field-keywords=PHP+MYSQL"); // add POST fields 
    $body = curl_exec($ch); // run the whole process 
    //echo curl_error ($ch ).'<br>';
    curl_close($ch);

    //echo $body; exit();
    // ======= downloading one url = end =======================================


    function removeTag($tag, $html) {

        $tag = strtoupper($tag);
        $openTag = '<' . $tag;
        $closeTag = '</' . $tag . '>';

        $result = $html;
        $code = strtoupper($html);
        $posStart = strpos($code, $openTag);
        $posFinish = strpos($code, $closeTag);

        while (!($posStart === false )) {
            // remove block
            $result = substr($result, 0, $posStart) . substr($result, $posFinish + strlen($closeTag));
            $code = substr($code, 0, $posStart) . substr($code, $posFinish + strlen($closeTag));
            $posStart = strpos($code, $openTag);
            $posFinish = strpos($code, $closeTag);
        }
        return $result;
    }

    $body = removeTag('style', $body);
    $body = removeTag('script', $body);

    $html = str_get_html($body);
    if (!$html) {
        echo '{"status":"error","message":"cannot download URL"}';
        return;
    }

    //    $charset = new charset();
    //    $encoding = strtoupper($charset->detect($html->plaintext));
    //    if($debug) {
    //        echo $encoding;
    //    }
    //    $encoding = site_charset;
    //    foreach ($html->find('meta') as $element) {
    //        if (isset($element->charset)) {
    //            $encoding = $element->charset;
    //            break;
    //        }elseif(preg_match("/charset= *([0-9a-z-]+) *\$/i",$element->content,$matches)){
    //            $encoding = $matches[1];
    //            break;
    //        }
    //    }
    include(\e::config('SCRIPT_ROOT') . '/search/charset/charset.php');
    $charsetDataDir = \e::config('SCRIPT_ROOT') . '/search/charset/data';
    $detector = new charsetdetector(Array(
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-utf8.stats"))),
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-utf8.stats"))),
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-utf8.stats"))),
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-utf8.stats"))),
        Array('charset' => 'WINDOWS-1251', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp1251.stats"))),
        // Array('charset' => 'KOI8-R', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-koi8.stats")) ),
        // Array('charset' => 'CP866', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp866.stats"))  ),
        // Array('charset' => 'ISO-8859-5'  , 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-iso-8859-5.stats")) ),
        Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-cp1252.stats"))),
        Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-cp1252.stats"))),
        Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-cp1252.stats"))),
            //Array('charset' => 'ISO-8859-1'  , 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-iso-8859-1.stats")) ),
            //Array('charset' => 'ISO-8859-1'  , 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-iso-8859-1.stats")) ),
            //Array('charset' => 'ISO-8859-1'  , 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-iso-8859-1.stats")) ),
    ));


    $encoding = strtoupper($detector->detect($html->plaintext));



    $title = '';
    foreach ($html->find('meta') as $element) {
        if ($element->property == 'og:title') {
            $title = $element->content;
        }
    }
    if (!$title) {
        $title = $html->find("title", 0);
        if ($title) {
            $title = $title->plaintext;
        } else {
            $title = '';
        }
    }
    if ($encoding != site_charset) {
        try {
            $title = iconv($encoding, site_charset, $title);
        } catch (Exception $e) {
            
        }
    }

    if (!$title) {
        echo '{"status":"error","message":"page title not found"}';
        return;
    }


    $abstract = '';
    foreach ($html->find('meta') as $element) {
        if (!$abstract && $element->name == 'og:description') {
            $abstract = $element->content;
        }
        if (!$abstract && $element->name == 'description') {
            $abstract = $element->content;
        }
    }
    if ($encoding != site_charset) {
        try {
            $abstract = iconv($encoding, site_charset, $abstract);
        } catch (Exception $e) {
            
        }
    }

    $len1 = 0;
    $len0 = 1;
    while ($len0 != $len1) {
        $len0 = mb_strlen($abstract, site_charset);
        $abstract = html_entity_decode($abstract);
        $len1 = mb_strlen($abstract, site_charset);
    }
    $abstract = strip_tags($abstract) . "<p><a href=\"$url\" target=_blank>$url</a></p>";


    include (\e::config('SCRIPT_ROOT') . "/search/getlanguage/getlanguage.php");


    // ----------------- clear tags - begin ------------------------------------
    $tags = preg_split("/,|;|\\./", isset($input_vars['tags'])?$input_vars['tags']:"");
    $cnt = count($tags);
    $langTags = Array($lang=>Array());
    for ($i = 0; $i < $cnt; $i++) {
        $tags[$i] = trim($tags[$i]);
        $tags[$i] = preg_replace("/ +/", " ", $tags[$i]);
        
        $langTags[$lang][] = $tags[$i];
        //$tagLanguage = $langSelector->getTextLang($tags[$i]);

        //if (!isset($langTags[$tagLanguage['lang']])) {
        //    $langTags[$tagLanguage['lang']] = Array();
        //}
        //$langTags[$tagLanguage['lang']][] = $tags[$i];
    }

    // $this_news_info['tags']=join(',',$tags);
    // ----------------- clear tags - end --------------------------------------
    # ------------------ rebuild tags - begin ----------------------------------
    function updateNewsTags($newsId, $lang, $tags) {

        
        $cnt = count($tags);
        if ($cnt > 0) {
            $query = Array();
            for ($i = 0; $i < $cnt; $i++) {
                $tag = trim($tags[$i]);
                if (strlen($tag) > 0) {
                    $query[] = "({$newsId},'{$lang}','" . \e::db_escape($tag) . "')";
                }
            }
            $query = "INSERT INTO {$GLOBALS['table_prefix']}news_tags(news_id,lang,tag) VALUES" . join(',', $query);
            \e::db_execute($query);
        }
    }

    # ------------------ rebuild tags - end ------------------------------------















    $query = "SELECT id as newid FROM {$GLOBALS['table_prefix']}news 
              WHERE site_id=$site_id AND lang='{$lang}'
                AND LOCATE('" . \e::db_escape($url) . "',abstract)";
    if ($debug) {
        prn($query);
    }
    $newid =\e::db_getonerow($query);

    if ($newid) {
        $news_id = $newid['newid'];
        $query = "
            UPDATE {$GLOBALS['table_prefix']}news 
            SET title='" . \e::db_escape($title) . "',
                content='" . \e::db_escape($content) . "', 
                last_change_date='{$last_change_date}',
                abstract='" . \e::db_escape($abstract) . "',
                cense_level='{$cense_level}', 
                category_id='{$category_id}',
                tags='".  \e::db_escape(join(',',$tags))."',
                expiration_date=null,
                weight='{$weight}'
                    
            WHERE id={$news_id} AND lang='{$lang}' AND site_id=$site_id
            ";
        if ($debug) {
            prn($query);
        }
        \e::db_execute($query);

        $query = "DELETE FROM {$GLOBALS['table_prefix']}news_category WHERE news_id={$news_id}";
        if ($debug) {
            prn($query);
        }
        \e::db_execute($query);

        $query = "insert into {$GLOBALS['table_prefix']}news_category(news_id, category_id) VALUES({$news_id},{$category_id})";
        if ($debug) {
            prn($query);
        }
        \e::db_execute($query);


        // update tags
        \e::db_execute("DELETE FROM {$GLOBALS['table_prefix']}news_tags WHERE news_id={$news_id}");
        foreach($langTags as $lanf=>$tags){
            updateNewsTags($news_id, $lanf, $tags);
        }


        echo '{"status":"success","news_id":"' . $news_id . '","charset":"' . $encoding . '"}';
    } else {
        // calculate news id
        $query = "SELECT max(id) AS newid FROM {$table_prefix}news";
        $newid =\e::db_getonerow($query);
        $news_id = 1 + (int) $newid['newid'];

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
            '".  \e::db_escape(join(',',$tags))."', 
             null, 
            '{$weight}', 
            '{$creation_date}', 
            '{$news_code}', 
            '{$news_meta_info}', 
            '{$news_extra_1}', 
            '{$news_extra_2}'
            );";
        if ($debug) {
            prn($query);
        }
        \e::db_execute($query);

        $query = "insert into {$GLOBALS['table_prefix']}news_category(news_id, category_id) VALUES({$news_id},{$category_id})";
        if ($debug) {
            prn($query);
        }
        \e::db_execute($query);

        // update tags
        \e::db_execute("DELETE FROM {$GLOBALS['table_prefix']}news_tags WHERE news_id={$news_id}");
        foreach($langTags as $lanf=>$tags){
            updateNewsTags($news_id, $lanf, $tags);
        }

        echo '{"status":"success","news_id":"' . $news_id . '"}';
        return;
    }
}


// ------------------ do download - end ----------------------------------------
# get list of all site categories
$query = "SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>=0 AND site_id={$site_id} ORDER BY start ASC";
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
2014-01-23  http://some.server.com/news/1234    tag1,tag2,tag3
0123  http://some.server.com/news/1235    tag1,tag2,tag3
01.04.15	http://gorozhanin.com.ua/read/7215.html    tag1,tag2,tag3
01.04.2015	http://gorozhanin.com.ua/read/7215.html    tag1,tag2,tag3
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
        
        var tags='';
        if(row.length>2){
          tags+=row[2];
          for(var ti=3; ti<row.length; ti++){
             tags+=' '+row[ti];
          }
        }
        // console.log(row);
        $('#loading').show();
        $.ajax({
           type: \"POST\",
           url: \"index.php\",
           data: { action: \"news/download_2\", site_id: $site_id, url: row[1], date:row[0], tags:tags, category_id:$('#news_category').val(), lang:'{$_SESSION['lang']}'},
           dataType: \"json\"
        }).always(function( msg ) {
        
           if(msg.status=='success'){
                var it=$('<li>' + msg.status + ' : '+row[1]+' ' + (msg.message? '<br>' + msg.status:'' ) + '</li>');
                $('#log').append(it);
                newsList.shift();
                document.getElementById('news_sources').value=newsList.join(\"\\n\");
                setTimeout(downloadNext, 20000);
           }else{
                $.ajax({
                    type: \"POST\",
                    url: \"index.php\",
                    data: { action: \"news/download_1\", site_id: $site_id, url: row[1], date:row[0],tags:tags, category_id:$('#news_category').val(), lang:'{$_SESSION['lang']}'},
                    dataType: \"json\"
                }).always(function( msg ) {
                    var it=$('<li>' + msg.status + ' : '+row[1]+' ' + (msg.message? '<br>' + msg.status:'' ) + '</li>');
                    $('#log').append(it);
                    newsList.shift();
                    document.getElementById('news_sources').value=newsList.join(\"\\n\");
                    setTimeout(downloadNext, 20000);
                })
           }
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
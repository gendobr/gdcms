<?php

define('max_spider_trials',5);

echo "
<html>
   <head>
     <meta http-equiv=\"Refresh\" content=\"10;URL=index.php?action=search/spider/spider\">
     <META content=\"text/html; charset=".site_charset."\" http-equiv=\"Content-Type\">
   </head>
<body>
<a href=index.php?action=site/spider>next</a><br>
";



# ----------- check if all sites are taken into account - begin ----------------

$getSiteId = function($el) {
    return $el['site_id'];
};

$query = "SELECT s.id site_id FROM  {$table_prefix}site AS s WHERE s.is_search_enabled=1";
$site_ids = array_map($getSiteId, db_getrows($query));
sort($site_ids);
$site_ids[] = 0;

$query = "SELECT DISTINCT site_id FROM {$table_prefix}search_index WHERE site_id IN (" . join(',', $site_ids) . ");";
$real_site_ids = array_map($getSiteId, db_getrows($query));
sort($real_site_ids);

$to_add = array_diff($site_ids, $real_site_ids);
//prn($to_add);
if (count($to_add) > 0) {
    $query = "INSERT INTO {$table_prefix}search_index(site_id, url, date_indexed)
              SELECT DISTINCT s.id, s.url, now() AS date_indexed
              FROM  {$table_prefix}site AS s
              WHERE s.id IN (" . join(',', $to_add) . ")";
    // prn($query);
    db_execute($query);
}
# ----------- check if all sites are taken into account - end ------------------
# ------------------------- get url to index - begin ---------------------------

$query = "SELECT @date1:=MIN(date_indexed) AS md FROM {$table_prefix}search_index;";
db_execute($query);
// prn(db_getrows("SELECT @date1"));

$query = "SELECT * FROM {$table_prefix}search_index WHERE date_indexed=@date1 LIMIT 0,100;";
$this_url_info = db_getrows($query);

$max = count($this_url_info);
if ($max > 0) {
    $this_url_info = $this_url_info[rand(0, $max - 1)];
}
prn($this_url_info);

if ($this_url_info['is_valid'] || rand(0, 1000) > 998) {

    $query = "UPDATE {$table_prefix}search_index SET date_indexed=now() WHERE id={$this_url_info['id']}";
    db_execute($query);

    // index URL
    # ------------------------- get site info - begin --------------------------
    $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE is_search_enabled=1 AND id=" . ( (int) $this_url_info['site_id'] ));
    // prn($this_site_info); exit();
    if (!$this_site_info) {
        $query = "DELETE FROM {$table_prefix}search_index WHERE id=" . ( (int) $this_url_info['id'] );
        db_execute($query);
        exit('Site not found');
    }
    # ------------------------- get site info - end ----------------------------
    # 
    # 
    # 

    run('search/spider/functions');
    # check if the URL is valid
    if (!is_searchable($this_url_info['url'], $this_site_info)) {
        $query = "DELETE FROM {$table_prefix}site_search WHERE id=" . ( (int) $this_url_info['id'] );
        db_execute($query);
        exit('URL is forbidden by site setings');
    }
    // prn($this_site_info); exit();
    // ======= downloading one url = begin =====================================
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this_url_info['url']); // set url to post to 
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects 
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // times out after 20s 
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent:Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36");

    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, false);

    // curl_setopt($ch, CURLOPT_POST, 1); // set POST method 
    //curl_setopt($ch, CURLOPT_POSTFIELDS, "url=index%3Dbooks&field-keywords=PHP+MYSQL"); // add POST fields 
    $body = curl_exec($ch); // run the whole process 
    // echo curl_error ($ch ).'<br>';
    curl_close($ch);
    //echo $body; exit();
    // ======= downloading one url = end =======================================
    //prn(rawurlencode($body));exit('4');
    
    $headers = preg_split("/\\r\\n\\r\\n|\\n\\n/", $body);
    $headers = $headers[0];
    if (!preg_match("/Content-Type: *text/i", $headers)) {
        $query = "UPDATE {$table_prefix}search_index SET date_indexed=now(), is_valid=0 WHERE id={$this_url_info['id']}";
        db_execute($query);
        exit('Wrong Content-Type');
    }

    
    
    
    $body=str_replace($headers,'',$body);
    $this_url_info['checksum']=md5($body);
    $this_url_info['size']=strlen($body);
    //prn(htmlspecialchars($body));

    $body = removeTag('script', $body);
    $body = removeTag('style', $body);


    run('lib/simple_html_dom');
    $html = str_get_html($body);
    if (!$html) {
        $query = "UPDATE {$table_prefix}search_index SET date_indexed=now(), is_valid=is_valid-1 WHERE id={$this_url_info['id']}";
        db_execute($query);
        exit('Error: cannot parse html');
    }




    include(script_root . '/search/charset/charset.php');
    $charsetDataDir = script_root . '/search/charset/data';
    $detector = new charsetdetector(Array(
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-utf8.stats"))),
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-utf8.stats"))),
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-utf8.stats"))),
        Array('charset' => 'UTF-8', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-utf8.stats"))),
        Array('charset' => 'WINDOWS-1251', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp1251.stats"))),
        Array('charset' => 'KOI8-R', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-koi8.stats"))),
        Array('charset' => 'CP866', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-cp866.stats"))),
        Array('charset' => 'ISO-8859-5', 'stats' => unserialize(file_get_contents("$charsetDataDir/rus-iso-8859-5.stats"))),
        Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-cp1252.stats"))),
        Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-cp1252.stats"))),
        Array('charset' => 'WINDOWS-1252', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-cp1252.stats"))),
        Array('charset' => 'ISO-8859-1', 'stats' => unserialize(file_get_contents("$charsetDataDir/deu-iso-8859-1.stats"))),
        Array('charset' => 'ISO-8859-1', 'stats' => unserialize(file_get_contents("$charsetDataDir/eng-iso-8859-1.stats"))),
        Array('charset' => 'ISO-8859-1', 'stats' => unserialize(file_get_contents("$charsetDataDir/fra-iso-8859-1.stats"))),
    ));
    $encoding = strtoupper($detector->detect($html->plaintext));

    // prn($encoding, htmlspecialchars($html->plaintext));
    // exit('11');
    
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

    $this_url_info['date_indexed']=date('Y-m-d H:i:s');

    //prn($encoding, htmlspecialchars($title));
    //exit('12');
    
    
    include (script_root . "/search/tokenizer/tokenizer.php");
    include (script_root . "/search/tokenizer/tokenizer_ukr.php");
    include (script_root . "/search/tokenizer/tokenizer_rus.php");
    include (script_root . "/search/tokenizer/tokenizer_eng.php");
    include (script_root . "/search/getlanguage/getlanguage.php");
    include (script_root . "/search/commonwords/commonwords.php");

    include (script_root . "/search/stemming/stemmer.class.php");
    include (script_root . "/search/stemming/porter_eng.class.php");
    include (script_root . "/search/stemming/porter_rus.class.php");
    include (script_root . "/search/stemming/porter_ukr.class.php");



    $commonwords = new commonwords(script_root . "/search/commonwords/commonwords.txt");

    $langSelector = new getlanguage(Array(
        'files' => Array(
            'eng' => script_root . "/search/getlanguage/stats_eng.txt",
            'rus' => script_root . "/search/getlanguage/stats_rus.txt",
            'ukr' => script_root . "/search/getlanguage/stats_ukr.txt",
        // 'slov' => '../getlanguage/stats_slov.txt',
        // 'češ' => '../getlanguage/stats_ces.txt',
        )
    ));

    $tokenizers = Array(
        'eng' => new tokenizer_eng(),
        'ukr' => new tokenizer_ukr(),
        'rus' => new tokenizer_rus()
    );

    $stemmers = Array(
        'eng' => new porter_eng(),
        'ukr' => new porter_ukr(),
        'rus' => new porter_rus()
    );


    $tokens = Array();
    $remainder = $html->plaintext;
    $lang = $langSelector->getTextLang($remainder);
    $lang = $lang['lang'];
    if (!$title) {
        $title=  get_langstring($this_site_info['title'], $lang);
    }
    $this_url_info['lang']=$lang;
    $this_url_info['title']=$title;
    
    $checkedLangs = Array();
    while (true) {
        if (isset($checkedLangs[$lang])) {
            break;
        }
        $checkedLangs[$lang] = 1;
        $reply = $tokenizers[$lang]->getTokens($remainder);
        // print_r($reply); exit("222");
        if (count($reply['tokens']) > 0) {
            $tokens[$lang] = $commonwords->removeCommonWords($reply['tokens']);
            $cnt = count($tokens[$lang]);
            for ($i = 0; $i < $cnt; $i++) {
                $tokens[$lang][$i] = $stemmers[$lang]->stem($tokens[$lang][$i]);
            }
        }
        $dl = mb_strlen($remainder, $encoding) - mb_strlen($reply['remainder'], $encoding);
        if ($dl == 0) {
            break;
        }
        $remainder = $reply['remainder'];
        $lang = $langSelector->getTextLang($remainder);
        $lang = $lang['lang'];

    }
    //print_r(join(' ',$tokens));
    $this_url_info['words']=join(' ',array_map(function($x){return join(' ',  $x);},$tokens));
    print_r($this_url_info);
    
    // update db record
    $query="UPDATE {$table_prefix}search_index
            SET url='".DbStr($this_url_info['url'])."',
                size=".( (int) $this_url_info['size']).",
                title='".DbStr($this_url_info['title'])."',
                words='".DbStr($this_url_info['words'])."',
                date_indexed=now(),
                is_valid=".max_spider_trials.",
                checksum='".DbStr($this_url_info['checksum'])."',
                lang='".DbStr($this_url_info['lang'])."'
            WHERE id=".( (int) $this_url_info['id'])."
    ";    
    db_execute($query);
    echo "<hr>";
    
    
    
    
    // search URLs    
    $links=get_links($this_url_info['url'], $body, $this_site_info);
    prn($links);
    
    // create new records if needed
    if(count($links) > 0){
        $query = "SELECT url FROM {$table_prefix}search_index WHERE url IN ('" . join("','", $links) . "');";
        $existing_links = array_map(function($el){return $el['url'];},db_getrows($query));
        $urls_to_add = array_diff($links, $existing_links);
        if(count($urls_to_add)>0){
            $insertSql=Array();
            foreach($urls_to_add as $url_to_add){
                $insertSql[]="({$this_url_info['site_id']},'".  DbStr($url_to_add)."', ".max_spider_trials.",'".date('Y-m-d H:i:s', time() - rand(1000, 2000))."')";

            }
            $query="insert into {$table_prefix}search_index(site_id,url,is_valid,date_indexed) values ".join(',',$insertSql);
            db_execute($query);
        }
    }
    exit('<hr>OK');
} else {
    $query = "UPDATE {$table_prefix}search_index SET date_indexed=now()";
    db_execute($query);
    exit('Invalid URL');
}
# ------------------------- get url to index - end -----------------------------
  echo "
</body>
</html>
";
return false;

<?php

// exit(' debugging ');
$max_sleep=20;

define('max_spider_trials', 5);

// =============================================================================

set_time_limit($max_sleep+30);
sleep($sleep=rand(0, $max_sleep));


$time_start = microtime(true);


echo "
<html>
   <head>
     <meta http-equiv=\"Refresh\" content=\"10;URL=index.php?action=search/spider/spider\">
     <META content=\"text/html; charset=" . site_charset . "\" http-equiv=\"Content-Type\">
   </head>
<body>
Sleep $sleep<br>
<a href=index.php?action=search/spider/spider&t=" . time() . ">next</a><br>
";



# ----------- check if all sites are taken into account - begin ----------------

$getSiteId = function($el) {
    return $el['site_id'];
};

$query = "SELECT s.id site_id FROM  <<tp>>site AS s WHERE s.is_search_enabled=1";
$site_ids = array_map($getSiteId, \e::db_getrows($query));
sort($site_ids);
$site_ids[] = 0;

$query = "SELECT DISTINCT site_id FROM <<tp>>search_index WHERE site_id IN (" . join(',', $site_ids) . ");";
$real_site_ids = array_map($getSiteId, \e::db_getrows($query));
sort($real_site_ids);

$to_add = array_diff($site_ids, $real_site_ids);
//prn($to_add);
if (count($to_add) > 0) {
    
    $sites=\e::db_getrows("SELECT DISTINCT s.id, s.url, now() AS date_indexed
              FROM  <<tp>>site AS s
              WHERE s.id IN (" . join(',', $to_add) . ")");
    foreach($sites as $site){
        $site['url'] = \e::url_to_absolute(sites_root_URL, $site['url']);
        $query = "INSERT INTO <<tp>>search_index(site_id, url, date_indexed)
                  VALUES (<<integer id>>, <<string url>>, <<datetime date_indexed>>)";
        // prn($query);
        \e::db_execute($query, $site);
    }
}
# ----------- check if all sites are taken into account - end ------------------
# 
# 
# 
# 
# 
# 
# 
# 
# ------------------------- get url to index - begin ---------------------------

$query = "SELECT @date1:=MIN(date_indexed) AS md FROM <<tp>>search_index;";
\e::db_execute($query);
//prn(\e::db_getrows("SELECT @date1"));

$query = "SELECT * FROM <<tp>>search_index WHERE date_indexed=@date1 LIMIT 0,100;";

//tmp
//$query = "SELECT * FROM <<tp>>search_index WHERE site_id=94 LIMIT 0,100;";
//$query = "SELECT * FROM <<tp>>search_index WHERE id=37343 LIMIT 0,100;";


$this_url_info = \e::db_getrows($query);

$max = count($this_url_info);
prn($max.' URLs');
if ($max > 0) {
    $this_url_info = $this_url_info[rand(0, $max - 1)];
}
prn('1) this_url_info',$this_url_info);

if (true || $this_url_info['is_valid'] || rand(0, 1000) > 900) { //tmp
//if ($this_url_info['is_valid'] || rand(0, 1000) > 900) {

    $query = "UPDATE <<tp>>search_index SET date_indexed=now(), is_valid=5 WHERE id={$this_url_info['id']}";
    \e::db_execute($query);

    // index URL
    # ------------------------- get site info - begin --------------------------
    $this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE is_search_enabled=1 AND id=" . ( (int) $this_url_info['site_id'] ));
    // prn($this_site_info); exit();
    if (!$this_site_info) {
        $query = "DELETE FROM <<tp>>search_index WHERE id=" . ( (int) $this_url_info['id'] );
        \e::db_execute($query);
        exit('Site not found ( ' . (microtime(true) - $time_start) . 's )');
    }
    # ------------------------- get site info - end ----------------------------


    run('search/spider/functions');
    # -------------------- check if the URL is valid - begin -------------------
    if (!is_searchable($this_url_info['url'], $this_site_info)) {
        $query = "DELETE FROM <<tp>>search_index WHERE id=" . ( (int) $this_url_info['id'] );
        \e::db_execute($query);
        exit('URL is forbidden by site setings (' . (microtime(true) - $time_start) . 's )');
    }
    // prn($this_site_info); exit();
    # -------------------- check if the URL is valid - end ---------------------
    # 
    # 
    # 
    # 
    # 
    # 
    # ------- downloading one url - begin --------------------------------------
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $this_url_info['url']); // set url to post to 
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects 
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable 
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // times out after 20s 
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent:Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36");

    curl_setopt($ch, CURLOPT_ENCODING , "");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, true);

    // curl_setopt($ch, CURLOPT_POST, 1); // set POST method 
    //curl_setopt($ch, CURLOPT_POSTFIELDS, "url=index%3Dbooks&field-keywords=PHP+MYSQL"); // add POST fields 
    $body = curl_exec($ch); // run the whole process 
    // prn('4) body=',htmlspecialchars($body));

    $headers = curl_getinfo($ch);

    // echo curl_error ($ch ).'<br>';
    curl_close($ch);
    //echo $body; exit();
    //prn(rawurlencode($body));exit('4');
    # ------- downloading one url - end ----------------------------------------
    # 
    # 
    # 
    # 
    # --------- check http headers - begin -------------------------------------

    prn('3) headers=',$headers);


    if (!preg_match("/text/i", $headers['content_type'])) {
        $query = "UPDATE <<tp>>search_index SET date_indexed=now(), is_valid=0 WHERE id={$this_url_info['id']}";
        \e::db_execute($query);
        exit('Wrong Content-Type (' . (microtime(true) - $time_start) . 's )');
    }
    # --------- check http headers - end ---------------------------------------

    //exit('3');
    
    # get reply size
    $this_url_info['size'] = strlen($body);
    //prn(htmlspecialchars($body));

    $body = removeTag($body, '<script','</script>');
    
    $body = removeTag($body, '<noscript','</noscript>');
    
    $body = removeTag($body, '<style','</style>');

    $body = removeTag($body, '<noindex','</noindex>');
    
    $body = removeTag($body, '<!--noindex-->','<!--/noindex-->');
    // prn('6) body ',htmlspecialchars($body));


    
    
    run('lib/simple_html_dom');
    $html = str_get_html(str_replace('<',' <',$body));
    if (!$html) {
        $query = "UPDATE <<tp>>search_index SET date_indexed=now(), is_valid=is_valid-1 WHERE id={$this_url_info['id']}";
        \e::db_execute($query);
        exit('Error: cannot parse html (' . (microtime(true) - $time_start) . 's )');
    }





    $encoding=site_charset;

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

    $this_url_info['date_indexed'] = date('Y-m-d H:i:s');

    //prn($encoding, htmlspecialchars($title));
    //exit('12');


    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer2.php");
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer2_ukr.php");
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer2_rus.php");
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/tokenizer2_eng.php");
    include (\e::config('SCRIPT_ROOT') . "/search/tokenizer/greedytokenizer.php");
    
    
    include (\e::config('SCRIPT_ROOT') . "/search/getlanguage/getlanguage.php");
    include (\e::config('SCRIPT_ROOT') . "/search/commonwords/commonwords.php");

    include (\e::config('SCRIPT_ROOT') . "/search/stemming/stemmer.class.php");
    include (\e::config('SCRIPT_ROOT') . "/search/stemming/porter_eng.class.php");
    include (\e::config('SCRIPT_ROOT') . "/search/stemming/porter_rus.class.php");
    include (\e::config('SCRIPT_ROOT') . "/search/stemming/porter_ukr.class.php");



    $langSelector = new getlanguage(Array(
        'files' => Array(
            'eng' => \e::config('SCRIPT_ROOT') . "/search/getlanguage/stats_eng.txt",
            'rus' => \e::config('SCRIPT_ROOT') . "/search/getlanguage/stats_rus.txt",
            'ukr' => \e::config('SCRIPT_ROOT') . "/search/getlanguage/stats_ukr.txt",
        // 'slov' => '../getlanguage/stats_slov.txt',
        // 'češ' => '../getlanguage/stats_ces.txt',
        )
    ));





    
    
    
    $plaintext = $html->plaintext;
    
    echo "<hr> PLAINTEXT:<br>".$plaintext."<hr>";
    $len1=0;
    $len0=1;
    while($len0!=$len1){
        $len0=mb_strlen($plaintext, site_charset);
        $plaintext = html_entity_decode($plaintext);
        $len1=mb_strlen($plaintext, site_charset);
    }

    
    
    # get checksum 
    $this_url_info['checksum'] = md5($body);
    

    
    $lang = $langSelector->getTextLang($plaintext);
    $lang = $lang['lang'];
    if (!$title) {
        $title = get_langstring($this_site_info['title'], $lang);
    }
    $this_url_info['lang'] = $lang;
    $this_url_info['title'] = $title;

    
    $greedytokenizer=new greedytokenizer([
        new tokenizer2_ukr(),
        new tokenizer2_rus(),
        new tokenizer2_eng()
    ]);
    $tokens = $greedytokenizer->getTokens($plaintext);
    //echo "<hr>"; print_r(join(' ',$tokens));echo "<hr>";
    
    $commonwords = new commonwords(\e::config('SCRIPT_ROOT') . "/search/commonwords/commonwords.txt");
    $tokens = $commonwords->removeCommonWords($tokens);
    //prn($commonwords);
    
    $stemmers = Array(
        'eng' => new porter_eng(),
        'ukr' => new porter_ukr(),
        'rus' => new porter_rus()
    );
    $cnt = count($tokens);
    for ($i = 0; $i < $cnt; $i++) {
        $stem=false;
        foreach($stemmers as $stemmer){
            $tmp=$stemmer->stem($tokens[$i]);
            if( strlen($tmp)>0 && ( $stem===false || mb_strlen($tmp, site_charset)< mb_strlen($stem, site_charset ) ) ){
                $stem=$tmp;
                //echo "stem &lt;=$tmp<br>";
            }
            //echo "stem=$stem; tmp=$tmp<br>";
        }
        //echo "stem=$stem;<br>";
        if($stem && mb_strlen($stem, site_charset)>1){
            $tokens[$i] = $stem;
        }else{
            unset($tokens[$i]);
        }
    }
    
    echo "<hr> TOKENS:<br>".(join(' ',$tokens))."<hr>";
    $this_url_info['words'] = join(' ', $tokens);

    print_r($this_url_info);

    // update db record
    $query = "UPDATE <<tp>>search_index
            SET url='" . \e::db_escape($this_url_info['url']) . "',
                size=" . ( (int) $this_url_info['size']) . ",
                title='" . \e::db_escape($this_url_info['title']) . "',
                words='" . \e::db_escape($this_url_info['words']) . "',
                date_indexed=now(),
                is_valid=" . max_spider_trials . ",
                checksum='" . \e::db_escape($this_url_info['checksum']) . "',
                lang='" . \e::db_escape($this_url_info['lang']) . "'
            WHERE id=" . ( (int) $this_url_info['id']) . "
    ";
    // prn($query);
    \e::db_execute($query);
    
    
    # mark other rows with the same checksum as invalid
    $query = "UPDATE <<tp>>search_index SET is_valid=0 WHERE checksum='" . \e::db_escape($this_url_info['checksum']) . "' AND id<> ".( (int) $this_url_info['id']);
    \e::db_execute($query);

    echo "<hr>";




    // search URLs    
    $links = get_links($this_url_info['url'], $body, $this_site_info);
    prn($links);

    // create new records if needed
    if (count($links) > 0) {
        $query = "SELECT url FROM <<tp>>search_index WHERE url IN ('" . join("','", $links) . "');";
        $existing_links = array_map(function($el) {
            return $el['url'];
        }, \e::db_getrows($query));
        $urls_to_add = array_diff($links, $existing_links);
        if (count($urls_to_add) > 0) {
            $insertSql = Array();
            foreach ($urls_to_add as $url_to_add) {
                $insertSql[] = "({$this_url_info['site_id']},'" . \e::db_escape($url_to_add) . "', " . max_spider_trials . ",'" . date('Y-m-d H:i:s', time() - rand(1000, 2000)) . "')";
            }
            $query = "insert into <<tp>>search_index(site_id,url,is_valid,date_indexed) values " . join(',', $insertSql);
            \e::db_execute($query);
        }
    }
    exit('<hr>OK (' . (microtime(true) - $time_start) . 's )');
} else {
    $query = "UPDATE <<tp>>search_index SET date_indexed=now() WHERE id=" . ( (int) $this_url_info['id'] );
    \e::db_execute($query);
    exit('2) Invalid URL (' . (microtime(true) - $time_start) . 's )');
}
# ------------------------- get url to index - end -----------------------------
// REPAIR TABLE `cms8_search_index` QUICK EXTENDED; 
echo "
</body>
</html>
";
return false;

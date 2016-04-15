<?php

/*
 * RSS aggregator
 * Input:
 *   site_id
 *   feed = array of feeeds
 *   template = template file name
 *   rows = number of news in the block
 *   timeout = number, minutes, cache expiration time
 *   lang = current language
 * sample URL is
 * /index.php?action=rss_aggregator/cron_update_items&site_id=1
 */
// do not use templates
global $main_template_name;
$main_template_name = '';

$in_charset = 'UTF-8';
$out_charset = site_charset;
// iconv(site_charset, 'UTF-8',$from)
// header('content-type: text/html; charset=utf-8');
// header('content-type: text/html; charset='.$out_charset);

run('site/menu');



//run('lib/hyphenation/hyphenation');
//// ------------------ get site info - begin ------------------------------------
//$site_id = checkInt($input_vars['site_id']);
//$this_site_info = get_site_info($site_id);
//if (!$this_site_info) {
//    header("HTTP/1.0 404 Not Found");
//    die(text('Site_not_found'));
//}
//// ------------------ get site info - end --------------------------------------
// ------------------ get feed URL - begin -------------------------------------
// load one source with teh least date
//$query = "SELECT * FROM {$table_prefix}rsssource WHERE rsssource_is_visible AND site_id={$site_id} ORDER BY rsssource_last_updated ASC limit 0,1";
$query = "SELECT * FROM {$table_prefix}rsssource WHERE rsssource_is_visible ORDER BY rsssource_last_updated ASC limit 0,1";
$rsssource_info = \e::db_getonerow($query);
if (!$rsssource_info) {
    exit("There are not RSS sources to update");
}
prn($rsssource_info);

$rsssource_id = $rsssource_info['rsssource_id'];
$site_id = $rsssource_info['site_id'];
$query="UPDATE {$table_prefix}rsssource SET rsssource_last_updated=now() WHERE rsssource_id=$rsssource_id AND site_id=$site_id";
\e::db_execute($query);

$site_id = checkInt($rsssource_info['site_id']);
// ------------------ get feed URL - end ---------------------------------------
// ------------------ load RSS from source - begin -----------------------------
$xmlDoc = new DOMDocument();
$xmlDoc->load($rsssource_info['rsssource_url']);


$x = $xmlDoc->getElementsByTagName('item');
//echo count($x);
foreach ($x as $item) {
    // rsssourceitem_id
    // site_id
    $rsssource_id = $rsssource_info['rsssource_id'];

    //rsssourceitem_lang
    $rsssourceitem_lang = $rsssource_info['rsssource_lang'];


    $rsssourceitem_datetime = date('Y-m-d H:i:s');
    try {
        $rsssourceitem_datetime = $item->getElementsByTagName('pubDate')->item(0)->childNodes->item(0)->nodeValue;
        $rsssourceitem_datetime = strtotime($rsssourceitem_datetime);
        $rsssourceitem_datetime = date('Y-m-d H:i:s', $rsssourceitem_datetime);
    } catch (Exception $e) {
        $rsssourceitem_datetime = date('Y-m-d H:i:s');
    }

    $rsssourceitem_is_visiblle = $rsssource_info['rsssource_is_premoderated'];


    //rsssourceitem_title
    try {
        $rsssourceitem_title = $item->getElementsByTagName('title');
        if ($rsssourceitem_title)
            $rsssourceitem_title = $rsssourceitem_title->item(0);
        if ($rsssourceitem_title)
            $rsssourceitem_title = $rsssourceitem_title->childNodes;
        if ($rsssourceitem_title)
            $rsssourceitem_title = $rsssourceitem_title->item(0);
        if ($rsssourceitem_title)
            $rsssourceitem_title = $rsssourceitem_title->nodeValue;
        if ($rsssourceitem_title)
            $rsssourceitem_title = @iconv($in_charset, $out_charset, $rsssourceitem_title);

        $rsssourceitem_title=  html_entity_decode($rsssourceitem_title);
    } catch (Exception $e) {
        $rsssourceitem_title = '';
    }

    //$rsssourceitem_abstract
    try {
        $rsssourceitem_abstract = $item->getElementsByTagName('description')
                        ->item(0)->childNodes->item(0)->nodeValue;
        //prn($rsssourceitem_abstract);
        $rsssourceitem_abstract = @iconv($in_charset, $out_charset, $rsssourceitem_abstract);
        $rsssourceitem_abstract=  html_entity_decode($rsssourceitem_abstract);
    } catch (Exception $e) {
        //$rsssourceitem_abstract = 'error '.$e->getMessage();
    }

    //$rsssourceitem_url
    try {
        $rsssourceitem_url = $item->getElementsByTagName('link')->item(0)->childNodes->item(0)->nodeValue;
    } catch (Exception $e) {
        $rsssourceitem_url = '';
    }

    $rsssourceitem_src  = '';

    $rsssourceitem_guid = $rsssourceitem_url;

    //rsssourceitem_hash
    $rsssourceitem_hash = md5($rsssourceitem_title . $rsssourceitem_abstract);

    // create or update
    // try search the item
    $query = "SELECT *
            FROM {$table_prefix}rsssourceitem
            WHERE site_id={$site_id}
              AND rsssource_id={$rsssource_id}
              AND rsssourceitem_guid='" . \e::db_escape($rsssourceitem_guid) . "'";
    $item_info = \e::db_getonerow($query);
    if($item_info){
        $query="UPDATE {$table_prefix}rsssourceitem
                SET
                    rsssourceitem_lang='".  \e::db_escape($rsssourceitem_lang)."',
                    rsssourceitem_datetime='".  \e::db_escape($rsssourceitem_datetime)."',

                    rsssourceitem_title='".  \e::db_escape($rsssourceitem_title)."',
                    rsssourceitem_abstract='".  \e::db_escape($rsssourceitem_abstract)."',
                    rsssourceitem_url='".  \e::db_escape($rsssourceitem_url)."',
                    rsssourceitem_src='".  \e::db_escape($rsssourceitem_src)."',
                    rsssourceitem_guid='".  \e::db_escape($rsssourceitem_guid)."',
                    rsssourceitem_hash='".  \e::db_escape($rsssourceitem_hash)."'
                WHERE site_id=$site_id
                  AND rsssource_id=$rsssource_id
                  AND rsssourceitem_id={$item_info['rsssourceitem_id']}
               ";
        // do not change visibility if updating item
        // rsssourceitem_is_visiblle=".  ($rsssourceitem_is_visiblle?1:0).",
        \e::db_execute($query);
    }else{
        $query="INSERT INTO {$table_prefix}rsssourceitem(
                    site_id,
                    rsssource_id,
                    rsssourceitem_lang,
                    rsssourceitem_datetime,
                    rsssourceitem_is_visiblle,
                    rsssourceitem_title,
                    rsssourceitem_abstract,
                    rsssourceitem_url,
                    rsssourceitem_src,
                    rsssourceitem_guid,
                    rsssourceitem_hash
                )
                VALUES(
                    $site_id,
                    $rsssource_id,
                    '".  \e::db_escape($rsssourceitem_lang)."',
                    '".  \e::db_escape($rsssourceitem_datetime)."',
                    ".  ($rsssourceitem_is_visiblle?1:0).",
                    '".  \e::db_escape($rsssourceitem_title)."',
                    '".  \e::db_escape($rsssourceitem_abstract)."',
                    '".  \e::db_escape($rsssourceitem_url)."',
                    '".  \e::db_escape($rsssourceitem_src)."',
                    '".  \e::db_escape($rsssourceitem_guid)."',
                    '".  \e::db_escape($rsssourceitem_hash)."'
                )";
        \e::db_execute($query);
    }


    // report:
    echo "
        <hr>
        Source_id=$rsssource_id <br>
        Lang=$rsssourceitem_lang <br>
        Datetime=$rsssourceitem_datetime <br>
        Title=$rsssourceitem_title <br>
        Abstract=$rsssourceitem_abstract <br>
        Link=<a href=$rsssourceitem_url>$rsssourceitem_url</a><br/>
        <hr>
        ";
}



?>
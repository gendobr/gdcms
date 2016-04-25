<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

global $main_template_name;
$main_template_name = '';
run('site/menu');
// ------------------ get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    header("HTTP/1.0 404 Not Found");
    die(text('Site_not_found'));
}
// ------------------ get site info - end --------------------------------------
// ------------------ get language - begin -------------------------------------
if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = \e::config('default_language');
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = \e::config('default_language');
}
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);
// ------------------ get language - end ---------------------------------------
// ------------------ get list of RSS items
run('rss_aggregator/get_public_list');


$result = rss_aggregator_get_list($site_id, $input_vars['lang'], 0, 100, $filter = Array());
// prn($result);
if($result['rows_found']>0){
    $pubDate=date('c',strtotime($result['rows'][0]['rsssourceitem_datetime']));
}else{
    $pubDate=date('c');
}

$rss="<rss version=\"2.0\">
<channel>
<title><![CDATA[ ".$this_site_info['title']." ]]></title>
<link><![CDATA[ " . site_root_URL . "/index.php?action=rss_aggregator/rss&site_id={$site_id}&lang={$input_vars['lang']} ]]></link>
<language>".  substr($input_vars['lang'], 0,2)."</language>
<generator> ".site_root_URL."</generator>
<pubDate>{$pubDate}</pubDate>
<lastBuildDate/>

";

for($i=0; $i<$result['rows_found']; $i++){
    $row=$result['rows'][$i];
    $row['rsssourceitem_title']=trim($row['rsssourceitem_title']);
    //prn($row);
    if(strlen($row['rsssourceitem_title'])==0){
        continue;
    }
    $rss.="
        <item>
            <title><![CDATA[{$row['rsssourceitem_title']}]]></title>
            <guid isPermaLink=\"false\"><![CDATA[{$row['rsssourceitem_guid']}]]></guid>
            <link><![CDATA[{$row['rsssourceitem_url']}]]></link>
            <description><![CDATA[{$row['rsssourceitem_abstract']}]]></description>
            <pubDate>".date('c',strtotime($row['rsssourceitem_datetime']))."</pubDate>
        </item>
             ";
}

$rss.="</channel></rss>";

header("Content-Type:text/xml; charset=UTF-8");
echo $rss;

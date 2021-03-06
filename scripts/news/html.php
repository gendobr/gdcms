<?php
/*
  Approve news publication
  argument is $news_id    - news identifier, integer, mandatory
              $lang       - news language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/


$debug=false;

//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id}");
  if($debug) prn('$this_site_info=',$this_site_info);
//------------------- site info - end ------------------------------------------

//----------------------------- draw page - begin ------------------------------
  $GLOBALS['main_template_name']='design/popup';


#    $site_id - site identifier, integer, mandatory
#    $lang    - interface language, char(3), mandatory (rus|ukr|eng)
#    $rows    - number of rows< integer, optional
#    $abstracts =yes|no (default is "yes")
#    $template=<template file name>, file name (extension is ".html"),
#              template placed in site root directory.
#    $date=asc if the oldest messages must appear at top of the list
#    $date=desc if the newest messages must appear at top of the list
#
#    $category=<category_id> restrict category

 # ------------------------ list of categories - begin -------------------------
    $query="SELECT category_id, category_title, deep FROM <<tp>>category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
    $tmp=\e::db_getrows($query);
    $list_of_categories=Array();
    foreach($tmp as $tm) $list_of_categories[$tm['category_id']]=str_repeat(' + ',$tm['deep']).get_langstring($tm['category_title']);
    unset($tmp,$tm);
    //prn($list_of_categories);
 # ------------------------ list of categories - end ---------------------------


$uid=time();
$iframe =
"
<style type='text/css'>
.codeblk{
   padding:10px; 
   width:780px; 
   overflow:scroll;
   height:80pt;
   border:1px solid blue;
   color:blue;
}
.textblok{
  padding:1em;
}
</style>
<script type=\"text/javascript\">
<!--
function set_span_value(cssClass,val) {  \$('.'+cssClass).html(val); }
// -->
</script>
<div style='background-color:#e0e0e0;padding:10pt;'>
<h4>".text('news_html_change_parameters').":</h4>
".text('news_html_change_tip')."
<br />

 ".text('news_html_lang').":
 <select name=s_lang_selector onchange=\"set_span_value('s_lang',this.value);\">
   <option value='ukr'>ukr</option>
   <option value='rus'>rus</option>
   <option value='eng'>eng</option>
 </select><br />
 
 ".text('news_html_count').":
 <input type=text onchange=\"set_span_value('s_rows',this.value)\"><input type=button value=\"OK\"><br />

  ".text('news_html_orderby').":
 <select onchange=\"set_span_value('s_order',this.value)\">
   <option value='id+desc'>".text('news_html_orderby_id_desc')."</option>
   <option value='id+asc'>".text('news_html_orderby_id_asc')."</option>
   <option value='title+desc'>".text('news_html_orderby_title_desc')."</option>
   <option value='title+asc'>".text('news_html_orderby_title_asc')."</option>
   <option value='last_change_date+desc'>".text('news_html_orderby_last_change_date_desc')."</option>
   <option value='last_change_date+asc'>".text('news_html_orderby_last_change_date_asc')."</option>
   <option value='expiration_date+desc'>".text('news_html_orderby_expiration_date_desc')."</option>
   <option value='expiration_date+asc'>".text('news_html_orderby_expiration_date_asc')."</option>
   <option value='weight+desc'>".text('news_html_orderby_weight_desc')."</option>
   <option value='weight+asc'>".text('news_html_orderby_weight_asc')."</option>
 </select><br />

 ".text('news_html_template').":
 <input type=text id=s_template_fld onchange=\"set_span_value('s_template',this.value)\"><input type=button value=\"OK\"><br />

".text('news_html_category')."
 <select onchange=\"set_span_value('s_category',this.value)\">
 <option value=0>".text('news_html_any_category')."</option>
 ".draw_options('0',$list_of_categories)."
 </select><br />
</div>
<br />

{$text['Get_html_link_man']}

<h4>JavaScript:</h4>
 <pre class=codeblk>&lt;script type=\"text/javascript\" src=\"".\e::url_public('scripts/lib/ajax_loadblock.js')."\"&gt;&lt;/script&gt;
&lt;div id=news$uid> &lt;/div&gt;
&lt;script type=\"text/javascript\"&gt;
ajax_loadblock('news$uid','".site_public_URL."/index.php?"
        ."action=news/block&site_id={$site_id}"
        ."&lang=<span class=s_lang>{$_SESSION['lang']}</span>"
        ."&rows=<span class=s_rows>10</span>"
        ."&template=<span class=s_template></span>"
        ."&orderby=<span class=s_order></span>"
        ."&category_id=<span class=s_category>0</span>',null);
&lt;/script&gt;
 </pre><br />
<br />

<!--


<h4>JavaScript + IFRAME:</h4>
Выбирайте этот вариант, если адрес вашей веб-страницы начинается с ".sites_root_URL."
 <pre class=codeblk>
  &lt;div id=news$uid&gt;&nbsp;&lt;/div&gt;
  &lt;iframe style='width:1px;height:1px;border:none;opacity:0;' src='"
 .site_root_URL
 ."/index.php?action=news/block"
 ."&amp;site_id={$site_id}"
 ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
 ."&amp;rows=<span class=s_rows>10</span>"
 ."&amp;template=<span class=s_template></span>"
 ."&amp;orderby=<span class=s_order></span>"
 ."&amp;element=news$uid"
 ."&amp;category_id=<span class=s_category>0</span>'&gt;&lt;/iframe&gt;
 </pre><br />
<br />


<h4>JavaScript:</h4>
Этот вариант работает на любой веб-странице, но немного медленее первого.

 <pre class=codeblk>".
  "&lt;h5&gt; {$this_site_info['title']} - {$text['View_news']}&lt;/h5&gt;\n"
 ."&lt;script type=\"text/javascript\"
           src=\"".site_root_URL."/index.php?action=news/js"
 ."&amp;site_id={$site_id}"
 ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
 ."&amp;rows=<span class=s_rows>10</span>"
 ."&amp;template=<span class=s_template></span>"
 ."&amp;orderby=<span class=s_order></span>"
 ."&amp;category_id=<span class=s_category>0</span>"
 ."\"&gt;&lt;/script&gt;\n\n"
 ."</pre>
 -->
<h4>PHP:</h4>
 <pre class=codeblk>".
  "&lt;?php echo @join('',file('"
 .site_public_URL
 ."/index.php?action=news/block"
 ."&amp;site_id={$site_id}"
 ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
 ."&amp;rows=<span class=s_rows>10</span>"
 ."&amp;template=<span class=s_template></span>"
 ."&amp;orderby=<span class=s_order></span>"
 ."&amp;category_id=<span class=s_category>0</span>'));?&gt; \n \n"
 ."</pre>




 <h4>RSS</h4>
 <pre class=codeblk>".
  "&lt;a href=\""
 .site_public_URL
 ."/index.php?action=news/rss"
 ."&amp;site_id={$site_id}"
 ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
 ."&amp;rows=<span class=s_rows>10</span>"
 ."&amp;template=<span class=s_template></span>"
 ."&amp;orderby=<span class=s_order></span>"
 ."&amp;category_id=<span class=s_category>0</span>\"&gt;RSS&lt;/a&gt; \n \n"
 ."</pre>

 <pre class=codeblk>".
  "&lt;link rel=\"alternate\" type=\"application/rss+xml\" href=\""
 .site_public_URL
 ."/index.php?action=news/rss"
 ."&amp;site_id={$site_id}"
 ."&amp;lang=<span class=s_lang>{$_SESSION['lang']}</span>"
 ."&amp;rows=<span class=s_rows>10</span>"
 ."&amp;template=<span class=s_template></span>"
 ."&amp;orderby=<span class=s_order></span>"
 ."&amp;category_id=<span class=s_category>0</span>\" "
 . "title=\"".get_langstring($this_site_info['title'],$_SESSION['lang'])."\" /&gt; \n \n"
 ."</pre>

";

    $elementId='newsTags'.time();
    $iframe.='
    <h4>'.text('news_html_tag_list').'</h4>
     <pre class=codeblk>'.
    str_replace(
        ['lang='],
        ["lang=<span class=s_lang>{$_SESSION['lang']}</span>"],
    htmlspecialchars("
        <script type=\"text/javascript\" src=\"".\e::url_public('scripts/lib/ajax.js')."\"></script>
        <script type=\"text/javascript\">
          var request=new ajax(
            \"".site_public_URL."/index.php?action=news/tag_list&site_id=$site_id&lang=\",
            null,
            function (responseText){
               var tags;
               eval('tags='+responseText+';');
               var html='';
               if(tags){
                   for(var i in tags){
                       html+='<a class=\"news_tag\" href=\"'+tags[i]['url']+'\">'+tags[i]['html']+'</a> ';
                   }
                   document.getElementById('$elementId').innerHTML=html;
               }
            })
        </script>
        <div id=\"$elementId\"></div>
    ")).'</pre>';


    $iframe.='
    <h4>'.text('news_html_dates').'</h4>
     <pre class=codeblk>'.
    htmlspecialchars("
      <div id=news_dates_{$uid}>&nbsp;</div>
      <iframe style='width:1px;height:1px;border:none;opacity:0;' src='".site_public_URL."/index.php?action=news%2Fblock_dates&site_id={$site_id}&lang=<span class=s_lang>{$_SESSION['lang']}</span>&template=template_news_dates_block&element=news_dates_{$uid}'></iframe>
    ").'</pre>';



    $iframe.='
    <h4>'.text('news_html_subscription_form').'</h4>
     <pre class=codeblk>'.
    htmlspecialchars("
        <a href=\"".site_public_URL."/index.php?action=news_subscription/subscribe&site_id={$site_id}\">".text('news_html_subscribe')."</a>
    ").'</pre>';

    $iframe.='
    <h4>'.text('news_html_unsubscription_form').'</h4>
     <pre class=codeblk>'.
    htmlspecialchars("
        <a href=\"".site_public_URL."/index.php?action=news_subscription/unsubscribe&site_id={$site_id}\">".text('news_html_unsubscribe')."</a>
    ").'</pre>';


    $elementId='newsRSS'.time();
    $iframe.='
    <h4>'.text('news_html_rss_aggregator').'</h4>
     <pre class=codeblk>'.
    str_replace(
        ['lang=','rows='],
        ["lang=<span class=s_lang>{$_SESSION['lang']}</span>",
         "rows=<span class=s_rows>10</span>"],
    htmlspecialchars("
        <script type=\"text/javascript\" src=\"".\e::url_public('scripts/lib/ajax.js')."\"></script>
        <script type=\"text/javascript\">
          var request=new ajax(
            \"".site_public_URL."/index.php?action=news/rssaggregator&site_id={$site_id}&template=&lang=&rows=&timeout=30&feed[]=http://habrahabr.ru/rss/best/&feed[]=http://habrahabr.ru/rss/best/\",
            null,
            function (responseText){
                   document.getElementById('$elementId').innerHTML=responseText;
            })
        </script>
        <div id=\"$elementId\"></div>
    ") ).'</pre>';

 //echo $iframe;
$input_vars['page_title']='---';
$input_vars['page_content']=$iframe;

 //----------------------------- draw page - end --------------------------------


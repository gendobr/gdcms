<?php

/*
  Generate "Latest news" Javascript block
  arguments are
  $site_id - site identifier, integer, mandatory
  $lang    - interface language, char(3), mandatory
  $rows    - number of rows< integer, optional
  $abstracts =yes|no

 */
// include(\e::config('SCRIPT_ROOT') . '/news/get_public_list2.php');
  include(\e::config('SCRIPT_ROOT') . '/news/get_public_list3.php');

$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}

$news=new CmsNewsViewer($input_vars);




$vyvid="";

$vyvid.="var newsdata=".json_encode($news->list['rows']).";\n\n";

$vyvid.="
var html='';
for(var i=0; i<newsdata.length; i++){
    var news=newsdata[i];
    html+=\"<div class='news-item'>\\n\";
    html+=\"<div class='news-item-title'>\\n\";
    if(news.content_present){
       html+=\"<a href='\"+news.URL_view_details+\"' class='news-item-link'>\"+news.title+\"</a>\\n\";
    }else{
       html+=news.title;
    }
    html+=\"</div>\\n\";
    html+=\"<div class='news-item-date'>\"+news.last_change_date+\"</div>\\n\";
    html+=\"<div class='news-item-date'>\"+news.abstract+\"</div>\\n\";

    var html_tags='';
    for(var it in news.tag_links){
        html_tags+='<a href=\"'+news.tag_links[it].URL+'\">'+news.tag_links[it].name+'</a>';
    }
    for(var it in news.categories){
        html_tags+='<a href=\"'+news.categories[it].URL+'\">'+news.categories[it].category_title+'</a>';
    }
    if(html_tags.length>0){
    html+=\"<div class='news-item-tags'>\"+html_tags+\"</div>\\n\";
    }
    html+=\"</div>\\n\";
}

document.writeln(html);
";


echo $vyvid;

global $main_template_name;
$main_template_name = '';

// remove from history
nohistory($input_vars['action']);

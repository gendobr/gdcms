<?php
/*
  Generate "Latest news" block
  arguments are
    $site_id - site identifier, integer, mandatory
    $lang    - interface language, char(3), mandatory (rus|ukr|eng)
    $rows    - number of rows< integer, optional
    $abstracts =yes|no (default is "yes")
    $template=<template file name>, file name (extension is ".html"),
              template placed in site root directory.
    $date=asc if the oldest messages must appear at top of the list
    $date=desc if the newest messages must appear at top of the list

    $category=<category_id> restrict category
*/
$input_vars['category_filter_mode']='yes';
include(script_root.'/news/get_public_list.php');
if(isset($input_vars['debug'])) prn($list_of_news);
/*
   'paging_links'=>$pages
  ,'text'=>$txt
  ,'news'=>$list_of_news
  ,'news_found' => $news_found
  ,'news_date_selector'=>$news_date_selector
  ,'news_keywords_selector'=>$news_keywords_selector
  ,'news_category_selector'=>$category_selector
  ,'news_tags'=>$tag_selector
*/

global $main_template_name; $main_template_name='';

//run('site/page/page_view_functions');

# ---------------------- choose template - begin -------------------------------

# check if template name is posted
  if(isset($_REQUEST['template']))
  {
    $_REQUEST['template'] = str_replace(Array('/', "\\"), '_', $_REQUEST['template']); // to prevent template names like /etc/passwd
    $news_template = sites_root.'/'.$this_site_info['dir'].'/'.$_REQUEST['template'].'.html';
    if(!is_file($news_template)) $news_template=false;
    if(!$news_template) $news_template = sites_root.'/'.$this_site_info['dir'].'/'.$_REQUEST['template'];
    if(!is_file($news_template)) $news_template=false;
  }
  else $news_template=false;


# check if site news template name exists
  if(!$news_template) $news_template = sites_root.'/'.$this_site_info['dir'].'/template_news_view_block.html';
  if(!is_file($news_template)) $news_template=false;

# use default system template
  #prn('$news_template',$news_template);
  if(!$news_template) $news_template = 'cms/template_news_view_block';
# ---------------------- choose template - end ---------------------------------

  #prn('$news_template',$news_template);
  $vyvid=process_template( $news_template
                                ,Array(
                                  'paging_links'=>$pages
                                 ,'text'=>$txt
                                 ,'news'=>$list_of_news
                                 ,'news_found' => $news_found
                                 ,'all_news_url'=>$all_news_url
                                 ,'start'=>$start+1
                                 ,'finish'=>min($news_found,count($list_of_news))
                                ));

if(strlen($vyvid)==0) {echo '';return '';}



header('Content-Type:text/html; charset='.site_charset);
header('Access-Control-Allow-Origin: *');


if(isset($input_vars['element'])){
    echo "
    <html>
        <head>
            <META content=\"text/html; charset=".site_charset."\" http-equiv=\"Content-Type\">
        </head>
        <body>
    <div id=toinsert>
    <!--
          ".str_replace(Array('<!--','-->'),Array('{*','*}'),$vyvid)."
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
           to.innerHTML = stripAndExecuteScript(decode('".preg_replace("/\\s+/",' ',str_replace(Array('"',"'","\n","\r",'<script','</script'),Array('2~','1~',' ',' ','~script','~/script'),$vyvid))."'));
        }
      }
    }

    </script>
        </body>
    </html>
    ";
}else{
    echo $vyvid;
}


//echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
//<html>
//  <head>
//    <meta http-equiv="Content-Type" content="text/html; charset='.site_charset.'">
//  </head>
//  <body>
//';
//if(isset($input_vars['element']))
//{
//  echo "
//    <div id=toinsert>$vyvid</div>
//    <script type=\"text/javascript\">
//    <!--
//    var from = document.getElementById('toinsert');
//    //alert(from.innerHTML);
//    var to;
//    if(window.top)
//    {
//      //alert('window.top - OK');
//      if(window.top.document)
//      {
//        //alert('window.top.document - OK');
//        to = window.top.document.getElementById('{$input_vars['element']}');
//        //alert(to);
//        if(to)
//        {
//           //alert('element - OK');
//           to.innerHTML = from.innerHTML;
//        }
//      }
//    }
//    // -->
//    </script>
//    "
//    ;
//}
//else echo $vyvid;
//
//echo '
//    </body>
//</html>
//';
// remove from history
   nohistory($input_vars['action']);
?>
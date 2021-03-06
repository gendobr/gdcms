<?php
  run('site/page/page_view_functions');
  run('site/menu');

# -------------------- set interface language - begin ---------------------------
  $debug=false;
  if(isset($input_vars['interface_lang'])) if($input_vars['interface_lang']) $input_vars['lang']=$input_vars['interface_lang'];
  if(!isset($input_vars['lang'])   ) $input_vars['lang']=\e::config('default_language');
  if(strlen($input_vars['lang'])==0) $input_vars['lang']=\e::config('default_language');
  $lang = get_language('lang');
# -------------------- set interface language - end -----------------------------

# -------------------------- load messages - begin -----------------------------
  global $txt;
  $txt=load_msg($lang);
# -------------------------- load messages - end -------------------------------



# ------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);
  if(!$this_site_info) die($txt['Site_not_found']);
  $this_site_info['title']=get_langstring($this_site_info['title'],$lang);
  //prn($this_site_info);
  //prn($input_vars);
# ------------------- get site info - end --------------------------------------


# -------------------- get list of page languages - begin ----------------------
  $lang_list=list_of_languages();
  $cnt=count($lang_list);
  for($i=0;$i<$cnt;$i++){
      $lang_list[$i]['lang']=$lang_list[$i]['name'];
      $lang_list[$i]['url']=$lang_list[$i]['href'];
  }
  //prn($lang_list);
  /*
     {foreach from=$lang key=ke item=ln}
	    {if $ln.lang eq $text.language_name}
		   <span class="top {$ln.lang}">{$ln.lang}</span>
		{else}
	       <a href="{$ln.url}" class="top {$ln.lang}">{$ln.lang}</a>
		{/if}
     {/foreach}
   */
# -------------------- get list of page languages - end ------------------------

# -------------------- search for template - begin -----------------------------
  //$ec_item_template_list = \e::config('SITES_ROOT').'/'.$this_site_info['dir']."/$frontpage_templates_dir/template_ec_item_list.html";
  //if(!is_file($ec_item_template_list)) $ec_item_template_list = 'cms/template_ec_item_list';
# -------------------- search for template - end -------------------------------



# get site menu
  $menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

# ------------------------ here is main page content - begin -------------------
function get_cached_page($url, $timeout=300) {
    $cache_dir = \e::config('CACHE_ROOT');
    $filepath = $cache_dir . '/' . md5($url) . '.txt';
    //prn($filepath);
    if (!is_file($filepath) || time() > (filemtime($filepath) + $timeout)) {
        // refrech cache
        $text = join('', file($url));
        $fp = fopen($filepath, 'w');
        if (!$fp)
            echo "w--$filepath";
        fwrite($fp, $text);
        fclose($fp);
    }
    else {
        // use cache
        // echo "use cache $filepath";
        $text = join('', file($filepath));
    }
    return $text;
}

$page=Array();
$page['title']=smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Запорізький національний університет::rus=Запорожский национальний университет::eng=Zaporizhzhya National University'));
$page['header']="<a style=\"color:#192666; font-family:georgia,serif;\" href=http://sites.znu.edu.ua/news.php?start=0&site_id=27&interface_lang={$lang}>"
              .smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Новини прес-центру ЗНУ::rus=Новости пресс-центра ЗНУ::eng=ZNU Press Centre News'))
              ."</a>";
$page['abstract']='';
$page['lang']=$lang;
$page['site_id']=$site_id;
$page['content']= get_cached_page("http://sites.znu.edu.ua:8000/cms/index.php?action=news/block&site_id=27&lang={$lang}&rows=5&abstracts=yes&template=block_news")
."<div align=right>
      <a href='http://sites.znu.edu.ua/cms/index.php?action=news_subscription/subscribe&site_id=27' class=more_news_button>".smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Підписатися на розсилку::rus=Подписаться на рассылку::eng=Subscribe to mailing list'))."</a>
      <a href='http://sites.znu.edu.ua/cms/index.php?action=news/rss&site_id=27&lang={$lang}&rows=10&template=&date=desc&category_id=0'><img src=http://sites.znu.edu.ua/cms/img/rss.gif style='margin:0px;border:none;'></a>
      <a href='http://sites.znu.edu.ua/news.php?start=0&site_id=27&interface_lang={$lang}' class=more_news_button>"
      .smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Всі новини::rus=Все новости::eng=All News'))
      ."</a>
   </div>";



$page['next']=Array();


// anonsy
  $anonsy=trim(get_cached_page('http://sites.znu.edu.ua:8000/cms/index.php?action=news/block&site_id=28&lang='.$lang.'&rows=10&abstracts=yes&template=block_anonsy&date=asc&category_id=166&orderby=weight+asc'));
  //echo(checkStr($anonsy));
  if(strlen($anonsy)>0){
       $page['next'][]=Array(
        'title'=>"<a  style=\"color:#192666; font-family:georgia,serif;\" href=http://sites.znu.edu.ua/news.php?action=news%2Fview&site_id=28&lang=$lang>"
              .smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Анонси::rus=Анонсы::eng=Announces'))
              ."</a>"
       ,'content'=>$anonsy
       ."
	<div align=right>
	<a href='http://sites.znu.edu.ua/news.php?action=news%2Fview&site_id=28&lang=$lang' class=more_news_button>"
  .smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Всі анонси::rus=Все анонсы::eng=All Announces'))
  ."</a></div>"
  );
  }

// events
  if ($lang == 'ukr') {
    //$reply = trim(get_cached_page('http://sites.znu.edu.ua/cms/index.php?action=calendar/get_by_date&site_id=89&lang=ukr&rows=10&template=template_calendar_view_block_sv.html&category_id=1289&h=-1&i=-1'));
    //$reply.= trim(get_cached_page('http://sites.znu.edu.ua/cms/index.php?action=calendar/get_by_date&site_id=89&lang=ukr&rows=10&template=template_calendar_view_block_dn.html&category_id=1292&h=-1&i=-1'));
    //$reply.= trim(get_cached_page('http://sites.znu.edu.ua/cms/index.php?action=calendar/get_by_date&site_id=89&lang=ukr&rows=10&template=template_calendar_view_block_zh.html&category_id=1293&h=-1&i=-1'));
    $reply="
        <a href='http://sites.znu.edu.ua/cms/index.php?action=category%2Fbrowse&site_id=89&lang=ukr&category_id=1289&year=".date('Y')."&month=".(1*date('m'))."&day=".date('d')."'>Свята</a>
        <a href='http://sites.znu.edu.ua/cms/index.php?action=category%2Fbrowse&site_id=89&lang=ukr&category_id=1292&year=".date('Y')."&month=".(1*date('m'))."&day=".date('d')."'>Вітання</a>
        <a href='http://sites.znu.edu.ua/cms/index.php?action=category%2Fbrowse&site_id=89&lang=ukr&category_id=1293&year=".date('Y')."&month=".(1*date('m'))."&day=".date('d')."'>Заходи</a>
    ";
    if (strlen($reply) > 0) {
        $page['next'][] = Array(
            'title' => "<a  style=\"color:#192666; font-family:georgia,serif;\" href='http://sites.znu.edu.ua/cms/index.php?action=category%2Fbrowse&site_id=89&lang=ukr'>Календар подій</a>"
          , 'content' => $reply
        );
    }
   }


// invitations
  $reply=trim(get_cached_page("http://sites.znu.edu.ua:8000/cms/index.php?action=news/block&site_id=19&lang={$lang}&rows=5&abstracts=yes&template=block_news&category_id=84"));
  if(strlen($reply)>0)
     $page['next'][]=Array(
        'title'=>"<a  style=\"color:#192666; font-family:georgia,serif;\" href=http://sites.znu.edu.ua/news.php?site_id=19&lang={$lang}>"
        .smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Запрошення на конференції, конкурси::rus=Приглашения на конференции, конкурсы::eng=Invitations to conferences and seminars'))
        ."</a>"
       ,'content'=>$reply
          ."<br>
            <div align=right>
                <a href=\"http://sites.znu.edu.ua/cms/index.php?action=news/rss&site_id=19&lang=ukr&rows=10&template=&date=desc&category_id=0\"><img src=http://sites.znu.edu.ua/cms/img/rss.gif style='margin:0px;border:none;'></a>
                <a href=http://sites.znu.edu.ua/news.php?site_id=19&lang=ukr class=more_news_button>"
               .smarty_txt(Array('lang'=>$txt['language_name'],'variants'=>'ukr=Всі запрошення::rus=Все приглашения::eng=All Invitations'))
               ."</a>
            </div>
          "
     );

// banners
// $banners=trim(join('',file('http://sites.znu.edu.ua/cms/index.php?action=news/block&site_id=26&lang='.$lang.'&rows=4&abstracts=yes&template=block_banner')));
$banners=get_cached_page('http://sites.znu.edu.ua/cms/index.php?action=news/block&site_id=26&lang='.$lang.'&rows=4&abstracts=yes&template=block_banner',3600)
        .get_cached_page("http://sites.znu.edu.ua/cms/index.php?action=poll/block&site_id=58&lang={$lang}&poll_id=0&template=template_poll_ask_block",3600);
// echo (checkStr($banners));
if($banners){
	$menu_groups['banners']=Array(
	'id'=>1,
	'code'=>"right",
	'html'=>'',
	'url'=>'',
	'items'=>Array()
	);
	$menu_groups['banners']['items'][]=Array(
		'url'=>'',
		'description'=>'',
		'html'=>$banners,
		'attributes'=>''
	);
}
# ------------------------ here is main page content - end ---------------------

# ------------------------ draw using SMARTY template - begin ------------------











# --------------------------- get site template - begin ------------------------
  //$custom_page_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir']."/$frontpage_templates_dir/template_index.html";
  //if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
  //prn($custom_page_template);
  //prn($this_site_info['template']);
# --------------------------- get site template - end --------------------------

  $file_content=process_template($this_site_info['template']
                                ,Array('page'=>$page
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                 // ,'stan_rechej'=>$stan_rechej
                                 // ,'vystavka'=>$vystavka
                                 // ,'last_added'=>$last_added
                                ));
# ------------------------ draw using SMARTY template - end --------------------
echo $file_content;

global $main_template_name; $main_template_name='';


?>
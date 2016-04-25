<?php

// $GLOBALS['main_template_name']='';

// get list ordering by path
run('photo/functions');
run('site/menu');


$photo_info=photo_info(\e::request('photo_id',0));
if (!$photo_info) {
    echo text('Photo_not_found');
    return 0;
}
$photo_id=$photo_info['photo_id'];
$site_id = $photo_info['site_id'];
// prn($photo_info);


$this_site_info = get_site_info($site_id);
// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
// -------------- get site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------




if(\e::request('posted','')=='true'){
    
    $photo_info['photo_category_id']=\e::cast('integer',\e::request('photo_category_id',0));
    $photo_info['photo_visible']=\e::cast('integer',\e::request('photo_visible',0));
    $photo_info['photo_title']=\e::request('photo_title','');
    $photo_info['photo_author']=\e::request('photo_author','');
    $photo_info['photo_year']=\e::cast('integer',\e::request('photo_year',0));
    $photo_info['photo_description']=\e::request('photo_description','');
    
    
    \e::db_execute("
        UPDATE <<tp>>photo
                SET photo_category_id=<<integer photo_category_id>>,
                photo_visible=<<integer photo_visible>>,
                photo_title=<<string photo_title>>,
                photo_author=<<string photo_author>>,
                photo_description=<<string photo_description>>,
                photo_year=<<integer photo_year>>
        WHERE photo_id=<<integer photo_id>>
        ",$photo_info, true);
    \e::redirect(\e::url([
        'action'=>'photo/photo_edit',
        'photo_id'=>$photo_id
        ],\e::config('APPLICATION_ADMIN_URL')));
}







$list_of_languages = list_of_languages();
$js_lang = Array();
foreach ($list_of_languages as $l) {
    $js_lang[$l['name']] =$text[$l['name']];
}
$js_lang = json_encode($js_lang);


$category_list =  array_map(
    function($row){
        return [
            $row['photo_category_id'],
            str_repeat('&nbsp;|&nbsp;&nbsp;&nbsp;',  substr_count($row['photo_category_path'], '/')).get_langstring($row['photo_category_title'])
        ];
    },
    \e::db_getrows("SELECT photo_category_id,photo_category_path,photo_category_title   FROM <<tp>>photo_category photo_category WHERE site_id=<<integer site_id>> ORDER BY photo_category_path ASC",['site_id'=>$site_id])
);

$lang = \e::session('lang');




$html="
    <a href=\"index.php?action=photo/photo_list&site_id={$site_id}\">&lt;&lt;&lt; ".text('photo_list')."</a><br><br>
    <script type=\"text/javascript\" src=\"" . \e::config('APPLICATION_ADMIN_URL') . "/scripts/lib/langstring.js\"></script>
    <form action=\"" . \e::config('APPLICATION_ADMIN_URL') . "/index.php\" method=\"POST\">
    <input type='hidden' name='action' value='photo/photo_edit'>
    <input type='hidden' name='posted' value='true'>
    <input type='hidden' name='photo_id' value='{$photo_id}'>

    <div><!-- 
   --><span class=blk6>
      <a href=\"{$this_site_info['site_root_url']}/{$photo_info['photo_imgfile']['full']}\" target=_blank><img src=\"{$this_site_info['site_root_url']}/{$photo_info['photo_imgfile']['small']}\"></a>
      </span><!-- 
   --><span class=blk6>
   

        <div class=\"label\"><h5>".text('photo_author')."</h5></div>
        <div class=\"big\"><input type=text name=\"photo_author\" id=\"photo_author\" value=\"".htmlspecialchars($photo_info['photo_author'])."\"></div>
        <script type=\"text/javascript\">
                langs=$js_lang;
                draw_langstring('photo_author');
        </script>

      </span><!-- 
   --></div>

    
    
    <div><!-- 
   --><span class=blk6>
        <div class=\"label\"><h5>".text('photo_title')."</h5></div>
        <div class=\"big\"><input type=text name=\"photo_title\" id=\"photo_title\" value=\"".htmlspecialchars($photo_info['photo_title'])."\"></div>
        <script type=\"text/javascript\">
                langs=$js_lang;
                draw_langstring('photo_title');
        </script>
    </span><!-- 
   --><span class=blk6>
            <div class=label><h5>" . text('photo_category_id') . "</h5></div>
            <div class=\"big\"><select name='photo_category_id' id='photo_category_id'>
                <option value=''></option>
                ".\core\form::draw_options($photo_info['photo_category_id'], $category_list)."
            </select></div>
            <div class=\"label\"><h5>".text('photo_year')."</h5></div>
            <div class=\"big\"><input type=text name=\"photo_year\" id=\"photo_year\" value=\"\"></div>
            <div class=\"label\"><h5>".text('photo_visible')."</h5></div>
            <div class=\"big\">" . \core\form::draw_radio(1,[1=>text('positive_answer'),0=>text('negative_answer')], 'photo_visible') . "</div>
    </span><!-- 
 --></div>

    <div class=\"label\"><h5>".text('photo_description')."</h5></div>
    <div class=\"big\"><input type=text name=\"photo_description\" id=\"photo_description\" value=\"".htmlspecialchars($photo_info['photo_description'])."\"></div>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langarea('photo_description');
    </script>

    <br>
    <input type=\"submit\" name=\"save\" value=\"".  htmlspecialchars(text('photo_edit_save'))."\">
    </form>
";





$input_vars['page_header']=$input_vars['page_title']=text('photo_edit');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => [] );
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------


return;


$input_vars['lang'] = $lang = get_language('lang,interface_lang');
global $txt;
$txt = load_msg($input_vars['lang']);

$photo_info['photo_title']= get_langstring($photo_info['photo_title'], $lang);
$photo_info['photo_author']= get_langstring($photo_info['photo_author'], $lang);
$photo_info['photo_description']= get_langstring($photo_info['photo_description'], $lang);

$this_site_info = get_site_info($site_id, $lang);

$photo_category_info=photo_category_find($photo_info['photo_category_id'],'','');
if(!$photo_category_info){
    $photo_category_info=[
        'photo_category_id'=>0,
        'site_id'=>$site_id,
        'photo_category_path'=>'',
        'photo_category_ordering'=>0,
        'photo_category_title'=>$this_site_info['title']." - ".text('photos'),
        'photo_category_description'=>'',
        'photo_category_icon'=>'',
        'photo_category_visible'=>1,
        'photo_category_code'=>'',
        'photo_category_meta'=>''
    ];
}
$photo_category_id=$photo_category_info['photo_category_id'];
$photo_category_path=$photo_category_info['photo_category_path'];
$photo_category_code=$photo_category_info['photo_category_code'];







// -------------- get lazy category viewer - begin -----------------------------
$photoCategoryViewer=new PhotoCategoryViewer($lang, $this_site_info, $photo_category_info);
// -------------- get lazy category viewer - end -------------------------------



run('site/page/page_view_functions');

// -------------- get site menu - begin ----------------------------------------
$menu_groups = get_menu_items($this_site_info['id'], 0, $lang);
// prn('$menu_groups',$menu_groups);

// mark current page URL
$prefix_length = strlen(\e::config('url_prefix_news_list'));

foreach ($menu_groups as $kmg => $mg) {
    foreach ($mg['items'] as $kmi => $mi) {
        if (\e::config('url_prefix_news_list') == substr($mi['url'], $prefix_length)) {
            continue;
        }
        if (!preg_match("/action=news(\\/|%2F)view/i", $mi['url'])) {
            continue;
        }
        if (!preg_match("/site_id={$site_id}(\$|&)/i", $mi['url'])) {
            continue;
        }
        $menu_groups[$kmg]['items'][$kmi]['disabled'] = 1;
    }
}
// -------------- get site menu - end ------------------------------------------

// -------------- get list of languages - begin --------------------------------
//\e::config('url_pattern_photo_category')
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['href']=$lang_list[$i]['url'] = str_replace([
        '{photo_id}','{lang}', '{site_id}'
    ],[
        $photo_id,$lang_list[$i]['name'], $site_id
    ],\e::config('url_pattern_photo'));
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
$lang_list = array_values($lang_list);
// -------------- get list of languages - end ----------------------------------

// \e::info($photoCategoryViewer->info);
// -------------- apply subtemplate - begin ------------------------------------
$subtemplate = site_get_template($this_site_info,'template_photo_view');
$vyvid=process_template( $subtemplate
                  ,Array(
                         'lang_list'=>$lang_list
                        ,'txt'=>$txt
                        ,'site'=>$this_site_info
                        ,'menu'=>$menu_groups
                        ,'photo'=>$photo_info
                        ,'category'=> $photoCategoryViewer->info 
                        ,'parents'=> $photoCategoryViewer->parents 
                        ,'site_root_url'=>\e::config('APPLICATION_PUBLIC_URL')
                   )
       );
// echo htmlspecialchars($vyvid);
// echo $vyvid;exit('2');
// -------------- apply subtemplate - begin ------------------------------------


// -------------- apply site template - begin ---------------------------------
$page_content=process_template($this_site_info['template']
        ,Array(
        'page'=>Array(
                 'title'=>$photoCategoryViewer->info['photo_category_title']
                ,'content'=> $vyvid
                ,'abstract'=> ''
                ,'site_id'=>$site_id
                ,'lang'=>$input_vars['lang']
                ,'page_meta_tags'=>$photoCategoryViewer->info['photo_category_meta']
        )
        ,'lang'=>$lang_list
        ,'site'=>$this_site_info
        ,'menu'=>$menu_groups
        ,'site_root_url'=>site_root_URL
        ,'text'=>$txt
));
echo $page_content;
// -------------- apply site template - end -----------------------------------

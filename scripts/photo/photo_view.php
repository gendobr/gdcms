<?php

$GLOBALS['main_template_name']='';

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

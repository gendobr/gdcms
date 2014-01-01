<?php
//----------------------Гена придумал ---------------------------------
$link = $db;
$data=date ("Y-m-d H:i");



//---------------------- load language - begin ---------------------------------
if(isset($input_vars['interface_lang'])
   && strlen($input_vars['interface_lang'])>0){
    $input_vars['lang']=$input_vars['interface_lang'];
}
if(strlen($input_vars['lang'])==0) $input_vars['lang']=$_SESSION['lang'];
if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;
$input_vars['lang'] = get_language('lang');

$txt = load_msg($input_vars['lang']);
//---------------------- load language - end -----------------------------------

run('site/menu');
run('lib/file_functions');
//------------------- site info - begin ----------------------------------------
if(isset($input_vars['site'])) {
    $site=$site_id = checkInt($input_vars['site']);
}
elseif(isset($input_vars['site_id'])) {
    $site=$site_id = checkInt($input_vars['site_id']);
}
$this_site_info = get_site_info($site,$input_vars['lang']);
//prn($this_site_info);
if(checkInt($this_site_info['id'])<=0) {
    die('rrrr'.$txt['Gallery_not_found']);
}
//------------------- site info - end ------------------------------------------


//--------------------------- get site template - begin ------------------------
$custom_page_template = sites_root.'/'.$this_site_info['dir'].'/template_index.html';
#prn('$news_template',$news_template);
if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
//--------------------------- get site template - end --------------------------

//--------------------------- Гена допридумывал --------------------------



//--------------------------- я придумала --------------------------

$lang=$input_vars['lang'];













run('site/page/page_view_functions');

$vyvid = '';

if (isset($input_vars['item'])) {
    $item=(int)$input_vars['item'];

    $link = $db;
    $row = db_getonerow("SELECT * FROM {$table_prefix}photogalery WHERE id = '$item'");
    if($row) {
        //prn($row);
        $url_prefix=preg_replace("/\\/+$/",'',$this_site_info['url']).'/gallery';

        $row['src_big']=$url_prefix.'/'. $row['photos'];
        $row['src_small']=$url_prefix.'/'. $row['photos_m'];


        # --------------------------- list of categories - begin -----------------
        $categories=Array();

        $rozdilizformy=$row['rozdil'];
        $n = count(explode('/',$rozdilizformy))+1;
        $path=explode('/',$rozdilizformy);
        $r=0;
        $re1 = '';
        $categories[]=Array(
            'innerHTML'=>text('Image_gallery'),
            'url'=>str_replace(
                    Array('{rozdilizformy}','{site_id}','{lang}','{start}','{keywords}','{rozdil2}'), 
                    //Array(rawurlencode($re1),$site_id,$lang,0,'',$row['rozdil2']), 
                    Array('',$site_id,$lang,0,'',''), 
                    url_pattern_gallery_category)
        );
        while ($r<$n-1) {
            if($r>0) {
                $re1.="/".$path[$r];
            }else {
                $re1.=$path[$r];
            }
            $rozdil2 = encode_dir_name($re1);
            $categories[]=Array(
                'innerHTML'=>$path[$r],
                'url'=>str_replace(
                        Array('{rozdilizformy}','{site_id}','{lang}','{start}','{keywords}','{rozdil2}'), 
                        Array(rawurlencode($re1),$site_id,$lang,0,'',$rozdil2), 
                        url_pattern_gallery_category)
            );
            $r=$r+1;
        }
        // $psus.="<hr>";

        // ----------------- links to next and previous items - begin ----------
           $query="SELECT min(id) as img_id, 'next' as type FROM {$table_prefix}photogalery WHERE id > '$item' AND rozdil='".DbStr($row['rozdil'])."' and site='$site_id'
                   UNION
                   SELECT max(id) as img_id, 'prev' as type FROM {$table_prefix}photogalery WHERE id < '$item' AND rozdil='".DbStr($row['rozdil'])."' and site='$site_id'";
           $siblings=db_getrows($query);
           //prn($siblings);
        // ----------------- links to next and previous items - end ------------
        $link_to=Array(
            'next'=>Array('innerHTML'=>text('Next').'&rarr;'),
            'prev'=>Array('innerHTML'=>'&larr;'.text('Previous'))
        );
        $url_refix=site_root_URL."/index.php?action=gallery/photo&site_id={$site_id}&lang={$_SESSION['lang']}&item=";
        foreach($siblings as $it){
            if($it['img_id']>0){
                $link_to[$it['type']]['url']="{$url_refix}{$it['img_id']}#imgtop";
            }
        }



        $_template = site_get_template($this_site_info, 'template_photogallery_view_details');
        $vyvid.= process_template($_template, Array(
              'image' => $row
            , 'text' => $txt
            , 'categories' => $categories
            , 'site' => $this_site_info
            , 'lang' => $lang
            , 'cms_root_url' => site_root_URL
            , 'link'=>$link_to
          )
        );
        # --------------------------- list of categories - end -------------------

    }
    else $vyvid .= $txt['Gallery_image_not_found'];
}else $vyvid .= $txt['Gallery_image_not_found'];


//--------------------------- Гена придумал --------------------------

$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list=list_of_languages();
$cnt=count($lang_list);
for($i=0;$i<$cnt;$i++) {
    $lang_list[$i]['url']=$lang_list[$i]['href'];
    $lang_list[$i]['lang']=$lang_list[$i]['name'];
}
// prn($lang_list);
//------------------------ get list of languages - end -------------------------

//------------------------ draw using SMARTY template - begin ----------------

$file_content=process_template($this_site_info['template']
        ,Array(
        'page'=>Array(
                 'title'=>(isset($categories[count($categories)-1])?$categories[count($categories)-1]['innerHTML']:"")
                ,'content'=> $vyvid
                ,'abstract'=> ''
                ,'site_id'=>$site_id
                ,'lang'=>$input_vars['lang'])
        ,'lang'=>$lang_list
        ,'site'=>$this_site_info
        ,'menu'=>$menu_groups
        ,'site_root_url'=>site_root_URL
        ,'text'=>$txt
));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name;
$main_template_name='';


//--------------------------- Гена допридумывал --------------------------



?>

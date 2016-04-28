<?php

/*
  View page
  arguments are
  $page_id - page identifier, integer, mandatory
  $lang    - page language, char(3), optional
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
 */

run('site/menu');
run('site/page/menu');
run('site/page/page_view_functions');

run('category/functions');
//--------------------------- check page id - begin ----------------------------
if (isset($input_vars['page_id'])) {
    $page_id = checkInt($input_vars['page_id']);
} else {
    $page_id = 0;
}
// $lang    = DbStr($input_vars['lang']);
$lang = get_language('lang');

$this_page_info = get_page_info($page_id, $lang);
if (!$this_page_info) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = $text['Page_not_found'];
    return 0;
}
//prn('view page',$this_page_info);
//--------------------------- check page id - end ------------------------------
# ------------------- site info - begin ----------------------------------------
$site_id = checkInt($this_page_info['site_id']);
$this_site_info = get_site_info($site_id);
# prn($this_site_info);
# ------------------- site info - end ------------------------------------------
// -------------------------- get page template - begin ------------------------
$this_page_info['template'] = $this_site_info['template'];
$this_page_info['subtemplate'] = false;

for($i=count($this_page_info['templates'])-1; $i>=0; $i--){
    $tmp1=$this_site_info['site_root_dir'] . '/' . $this_page_info['templates'][$i];
    if (is_file($tmp1)) {
        $this_page_info['template'] = $tmp1;
    }
    $tmp2=$this_site_info['site_root_dir'] . '/' . $this_page_info['subtemplates'][$i];
    if (is_file($tmp2)) {
        $this_page_info['subtemplate'] = $tmp2;
    }
}
//foreach ($this_page_info['templates'] as $tpl) {
//    if (is_file($tmp = $this_site_info['site_root_dir'] . '/' . $tpl)) {
//        $page_template = $tmp;
//        break;
//    }
//}
//prn('$this_site_info[template]=',$this_site_info['template']);
// -------------------------- get page template - end --------------------------
// 

// 
// 
//--------------------------- language selector - begin ------------------------
$lang_list = \e::db_getrows("SELECT lang FROM {$table_prefix}page WHERE id={$this_page_info['id']}");
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    if(!isset($this_site_info['extra_setting']['lang'][$lang_list[$i]['lang']])){
        unset($lang_list[$i]);
        continue;
    }
    $lang_list[$i]['url'] = "index.php?action=site/page/view&page_id={$this_page_info['id']}&lang={$lang_list[$i]['lang']}";
    if (isset($GLOBALS['text'][$lang_list[$i]['lang']])) {
        $lang_list[$i]['title'] = $GLOBALS['text'][$lang_list[$i]['lang']];
    } else {
        $lang_list[$i]['title'] = $lang_list[$i]['lang'];
    }

    $lang_list[$lang_list[$i]['lang']] = $lang_list[$i];
    unset($lang_list[$i]);
}
//prn($lang_list);
//--------------------------- language selector - end --------------------------

$menu_groups = get_menu_items($this_page_info['site_id'], $this_page_info['id'], $this_page_info['lang']);

// mark current page URL
foreach ($menu_groups as $kmg => $mg) {
    foreach ($mg['items'] as $kmi => $mi) {
        //prn($this_page_info['absolute_url'], $mi['url']);
        if($this_page_info['absolute_url'] == $mi['url']){
            $menu_groups[$kmg]['items'][$kmi]['disabled']=1;
        }
    }
}

//prn('$menu_groups',$menu_groups);
//------------------------ draw using SMARTY template - begin ------------------
//prn($lang_list,array_keys($GLOBALS['text']));
//prn($this_page_info);
if($this_page_info['subtemplate']){
    $page_content = process_template($this_page_info['subtemplate']
        , Array(
            'page' => $this_page_info
            , 'site' => $this_site_info
            , 'lang' => $lang_list
            , 'menu' => $menu_groups
            , 'site_root_url' => site_root_URL
            , 'text' => load_msg($this_page_info['lang'])
        ));
    $this_page_info['content']=$page_content;
    
    if(isset($GLOBALS['_saved_tpl_vars']['page']['title'])){
        $this_page_info['title']=$GLOBALS['_saved_tpl_vars']['page']['title'];
        unset($GLOBALS['_saved_tpl_vars']['page']['title']);
    }
    if(isset($GLOBALS['_saved_tpl_vars']['page']['abstract'])){
        $this_page_info['title']=$GLOBALS['_saved_tpl_vars']['page']['abstract'];
        unset($GLOBALS['_saved_tpl_vars']['page']['abstract']);
    }
    if(isset($GLOBALS['_saved_tpl_vars']['page']['page_meta_tags'])){
        $this_page_info['title']=$GLOBALS['_saved_tpl_vars']['page']['page_meta_tags'];
        unset($GLOBALS['_saved_tpl_vars']['page']['page_meta_tags']);
    }
}

        
$this_page_info['editURL']=site_URL."?action=site/page/edit&page_id={$this_page_info['id']}&aed=1&lang={$this_page_info['lang']}&aed=0";
$page_content = process_template($this_page_info['template']
        , Array(
    'page' => $this_page_info
    , 'site' => $this_site_info
    , 'lang' => $lang_list
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => load_msg($this_page_info['lang'])
        ));
echo $page_content;
//------------------------ draw using SMARTY template - end --------------------
global $main_template_name;
$main_template_name = '';
?>
<?php

/*
  Functions to export page as HTML file(s)
  arguments are $page_id    - page identifier, integer, mandatory
  $lang       - page_language  , char(3), mandatory
  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
 */


run('site/image/url_replacer');
run('site/page/page_view_functions');
if (!function_exists('menu_site'))
    run('site/menu');
run('site/page/menu');

function export_page($_page_id, $_lang) {
    global $db, $table_prefix, $input_vars;

    // ------------------ check page id - begin ----------------------------------
    $page_id = (int) $_page_id;
    $lang = \e::db_escape($_lang);
    $this_page_info = get_page_info($page_id, $lang);
    if (!$this_page_info) {
        $input_vars['page_title'] =
                $input_vars['page_header'] =
                $input_vars['page_content'] = $text['Page_not_found'];
        return 0;
    }
    // prn('$this_page_info',$this_page_info);
    //------------------- check page id - end ------------------------------------
    ## at this line the page is found
    // ------------------ site info - begin --------------------------------------
    $this_site_info = get_site_info($this_page_info['site_id']);
    $this_site_info['title'] = get_langstring($this_site_info['title'], $lang);
    // prn('$this_site_info=',$this_site_info);
    // ------------------ site info - end ----------------------------------------
    ## at this line the site is found
    //    //------------------- check permission - begin -------------------------------
    //    if (!is_admin() && $this_site_info['managers'][$_SESSION['user_info']['id']]['level'] < $this_site_info['cense_level']) {
    //        $input_vars['page_title'] =
    //                $input_vars['page_header'] =
    //                $input_vars['page_content'] = $text['Access_denied'];
    //        return 0;
    //    }
    //    //------------------- check permission - end ---------------------------------
    ## at this line the permission is checked
    // -------------------------- get page template - begin ----------------------
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
    // -------------------------- get page template - end ------------------------
    //--------------------------- language selector - begin ----------------------
    $lang_list = \e::db_getrows("SELECT * FROM {$table_prefix}page WHERE id={$this_page_info['id']} AND cense_level>={$this_site_info['cense_level']}");
    $cnt = count($lang_list);
    $url_prefix = preg_replace("/\\/+$/", '', $this_site_info['url']);
    for ($i = 0; $i < $cnt; $i++) {
        $lang_list[$i]['url'] = "{$this_page_info['id']}.{$lang_list[$i]['lang']}.html";
        if (strlen($lang_list[$i]['path']) > 0){
            //$lang_list[$i]['url'] = ereg_replace('^/+|/+$', '', $lang_list[$i]['path']) . '/' . $lang_list[$i]['url'];
            $lang_list[$i]['url'] = preg_replace("/^\\/+|\\/+\$/", '', $lang_list[$i]['path']) . '/' . $lang_list[$i]['url'];
        }
        $lang_list[$i]['url'] = $url_prefix . '/' . $lang_list[$i]['url'];
        $lang_list[$i]['title'] = $lang_list[$i]['lang'];
        $lang_list[$lang_list[$i]['lang']] = $lang_list[$i];
        unset($lang_list[$i]);
    }
    //prn($lang_list);
    //--------------------------- language selector - end ------------------------

    $menu_groups = get_menu_items($this_page_info['site_id'], $this_page_info['id'], $this_page_info['lang']);
    // prn('$menu_groups',$menu_groups);
    // mark current page URL
    foreach ($menu_groups as $kmg => $mg) {
        foreach ($mg['items'] as $kmi => $mi) {
            // prn($this_page_info['absolute_url'] == $mi['url']);
            if ($this_page_info['absolute_url'] == $mi['url']) {
                $menu_groups[$kmg]['items'][$kmi]['disabled'] = 1;
            }
        }
    }

    //------------------------ draw using SMARTY template - begin ----------------
    //
    //
    //prn($lang_list);
    $this_page_info['editURL']=site_URL."?action=site/page/edit&page_id={$this_page_info['id']}&aed=1&lang={$this_page_info['lang']}&aed=0";

    if($this_page_info['subtemplate']){
        $page_content = process_template($this_page_info['subtemplate']
            , Array(
                'page' => $this_page_info
                , 'site' => $this_site_info
                , 'lang' => $lang_list
                , 'menu' => $menu_groups
                , 'site_root_url' => site_public_URL
                , 'text' => load_msg($this_page_info['lang'])
            ));
        $this_page_info['content']=$page_content;
    }
    $file_content = process_template($this_page_info['template']
            , Array(
        'page' => $this_page_info
        , 'lang' => $lang_list
        , 'site' => $this_site_info
        , 'menu' => $menu_groups
        , 'site_root_url' => site_public_URL
        , 'text' => load_msg($this_page_info['lang'])
            ));
    //------------------------ draw using SMARTY template - end ------------------

    // ----------------------- delete old files - begin ------------------------
    $site_root_dir = \e::config('SITES_ROOT') . '/' . preg_replace('/' . "^\\/+|\\/+$" . '/', '', $this_site_info['dir']);
    // prn($site_root_dir);
    // delete old file
    if (strlen($this_page_info['delete_file']) > 0) {
        $delete_file=explode("\t",$this_page_info['delete_file']);
        foreach($delete_file as $fl){
            \core\fileutils::path_delete($site_root_dir, $fl);
        }

    }
    // ----------------------- delete old files - end --------------------------

    $page_path = $site_root_dir;




    if (strlen($this_page_info['path']) > 0){
        //$page_path.='/' . ereg_replace('^/+|/+$', '', $this_page_info['path']);
        $page_path.='/' . preg_replace("/^\\/+|\\/+\$/", '', $this_page_info['path']);
    }


    $page_path1 = $page_path . "/{$this_page_info['id']}.{$this_page_info['lang']}.html";
    \core\fileutils::path_create($site_root_dir, $page_path1);
    //prn($page_path);
    file_put_contents($page_path1, $file_content);

    if ($this_page_info['is_home_page'] == 1){
        // prn('exporting home page ...');
        file_put_contents($site_root_dir . "/index.html", $file_content);
    }

    // write to custom file name
    if(strlen($this_page_info['page_file_name'])>0){
        $page_path2 = $page_path . "/{$this_page_info['page_file_name']}";
        \core\fileutils::path_create($site_root_dir, $page_path2);
        file_put_contents($page_path2, $file_content);
    }
}

?>
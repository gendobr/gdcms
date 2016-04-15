<?php


// get list ordering by path
run('photo/functions');


$photo_category_info=photo_category_info(\e::cast('integer',\e::request('photo_category_id',0)));
if(!$photo_category_info){
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('photo_category_not_found');
    return 0;
}
$photo_category_id=$photo_category_info['photo_category_id'];
// \e::info($photo_category_info);
// -------------- get site info - begin ----------------------------------------
run('site/menu');
$site_id = $photo_category_info['site_id'];
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

$list_of_languages = list_of_languages();



// ---------------- delete previous icons - begin ------------------------------
if($photo_category_info['photo_category_icon'] && is_array($photo_category_info['photo_category_icon'])){
    foreach($photo_category_info['photo_category_icon'] as $pt){
        $pt=trim($pt);
        if(strlen($pt)>0){
            $path=realpath("{$this_site_info['site_root_dir']}/{$pt}");
            if($path && strncmp( $path , $this_site_info['site_root_dir'] , strlen($this_site_info['site_root_dir']) )==0){
                unlink($path);
            }
        }
    }
}

\e::db_execute(
        "UPDATE <<tp>>photo_category
          SET photo_category_icon=<<string photo_category_icon>>
          WHERE photo_category_id=<<integer photo_category_id>>",
        [
            'photo_category_id'=>$photo_category_id,
            'photo_category_icon'=>''
        ]);
// ---------------- delete previous icons - end --------------------------------
// redirect to editor page
\e::redirect(\e::url(['action'=>'photo/photo_category_edit','photo_category_id'=>$photo_category_id]));


<?php

function photo_category_list($site_id){

}


function photo_category_info($photo_category_id){
    global $table_prefix;
    $info=\e::db_getonerow(
            "SELECT photo_category.* , count(photo.photo_id) as nPhotos
             FROM {$table_prefix}photo_category photo_category
                 LEFT JOIN {$table_prefix}photo photo ON (photo.photo_category_id = photo_category.photo_category_id)
             WHERE photo_category.photo_category_id=".( (int)$photo_category_id )."
             GROUP BY photo_category.photo_category_id
             ");
    if($info) {
        if(substr_count($info['photo_category_path'], "/")==0){
            $info['category_parent']='';
        }else{
            $info['category_parent']=preg_replace("/\\/[^\\/]+\$/","",$info['photo_category_path']);
        }
        if ($info['photo_category_icon']) {
            $info['photo_category_icon'] = json_decode($info['photo_category_icon'], true);
        }
    }
    return $info;
}

function photo_info($photo_id){
    global $table_prefix;
    $info=\e::db_getonerow(
            "SELECT photo.* , photo_category.*
             FROM {$table_prefix}photo photo 
                  LEFT JOIN {$table_prefix}photo_category photo_category
                   ON (photo.photo_category_id = photo_category.photo_category_id)
             WHERE photo.photo_id=".( (int)$photo_id )."
             ");
    $info['photo_imgfile']=  json_decode($info['photo_imgfile'],true);
    return $info;
}
function photo_category_menu($photo_category_info){
        $menu = Array();
        $menu[] = Array(
            'url' => ''
            , 'html' => "<b>" . get_langstring($photo_category_info['photo_category_title']) . " : </b>"
            , 'attributes' => ''
        );
        $menu[] = Array(
              'url' => \e::url(['action'=>'photo/photo_category_edit','photo_category_id'=>$photo_category_info['photo_category_id']])
            , 'html' => text('photo_category_edit')
            , 'attributes' => ''
        );
        $menu[] = Array(
              'url' => \e::url(['action'=>'photo/photo_category_delete','photo_category_id'=>$photo_category_info['photo_category_id'],'delete_children'=>1])
            , 'html' => text('photo_category_delete_with_children')
            , 'attributes' => ''
        );
        $menu[] = Array(
              'url' => \e::url(['action'=>'photo/photo_category_delete','photo_category_id'=>$photo_category_info['photo_category_id']])
            , 'html' => text('photo_category_delete')
            , 'attributes' => ''
        );
        
        return $menu;
}
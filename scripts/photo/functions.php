<?php


function photo_category_info($photo_category_id){
    global $table_prefix;
    $info=db_getonerow(
            "SELECT photo_category.* , count(photo.photo_id) as nPhotos
             FROM {$table_prefix}photo_category photo_category
                 LEFT JOIN {$table_prefix}photo photo ON (photo.photo_category_id = photo_category.photo_category_id)
             WHERE photo_category.photo_category_id=".( (int)$photo_category_id )."
             GROUP BY photo_category.photo_category_id
             ");
    $info['photo_category_icon']=  json_decode($info['photo_category_icon'],true);
    return $info;
}

function photo_info($photo_id){
    global $table_prefix;
    $info=db_getonerow(
            "SELECT photo.* , photo_category.*
             FROM {$table_prefix}photo photo 
                  LEFT JOIN {$table_prefix}photo_category photo_category
                   ON (photo.photo_category_id = photo_category.photo_category_id)
             WHERE photo.photo_id=".( (int)$photo_id )."
             ");
    $info['photo_imgfile']=  json_decode($info['photo_imgfile'],true);
    return $info;
}

function photo_category_update($photo_category_id, $site_id, $data){
    
    // photo_category_id           int(11)       (NULL)     NO      PRI     (NULL)   auto_increment  select,insert,update,references           
    // site_id                     int(11)       (NULL)     YES             (NULL)                   select,insert,update,references           

    $set=[];
    
    // photo_category_path         varchar(255)  utf8_bin   YES             (NULL)                   select,insert,update,references           
    if(isset($data['photo_category_path'])){
        $set[]=" photo_category_path='".  DbStr($data['photo_category_path'])."' ";
    }
    
    // photo_category_ordering     int(11)       (NULL)     YES             (NULL)                   select,insert,update,references           
    if(isset($data['photo_category_ordering'])){
        $set[]=" photo_category_ordering=".  ((int)$data['photo_category_ordering'])." ";
    }

    // photo_category_title        text          utf8_bin   YES             (NULL)                   select,insert,update,references           
    if(isset($data['photo_category_title'])){
        $set[]=" photo_category_title='".  DbStr($data['photo_category_title'])."' ";
    }

    // photo_category_description  text          utf8_bin   YES             (NULL)                   select,insert,update,references           
    if(isset($data['photo_category_description'])){
        $set[]=" photo_category_description='".  DbStr($data['photo_category_description'])."' ";
    }

    // photo_category_icon         text          utf8_bin   YES             (NULL)                   select,insert,update,references           
    if(isset($data['photo_category_icon'])){
        $set[]=" photo_category_icon='".  DbStr(json_encode($data['photo_category_icon']))."' ";
    }

    // photo_category_visible      tinyint(1)    (NULL)     NO              1                        select,insert,update,references           
    if(isset($data['photo_category_visible'])){
        $set[]=" photo_category_visible=".  ( ( (int)$data['photo_category_visible'] )?1:0 )." ";
    }

    
    if(count($set)>0){
        $photo_category_id*=1;
        $site_id*=1;
        $query="UPDATE {$table_prefix}photo_category SET ".join(',',$set)."
                WHERE photo_category_id=$photo_category_id AND site_id=$site_id";
        db_execute($query);
    }
    return photo_category_info($photo_category_id);
}



// ----------------- resizing image to create small image - begin ----------
/**
 * $type="resample"|"inscribe"|"circumscribe"
 */
function photo_img_resize($inputfile, $outputfile, $width, $height, $type = "resample", $backgroundColor = 0xFFFFFF, $quality = 70) {
    if (!file_exists($inputfile)) {
        return false;
    }
    $size = getimagesize($inputfile);
    if ($size === false) {
        return false;
    }

    $format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
    $icfunc = "imagecreatefrom" . $format;
    if (!function_exists($icfunc)) {
        return false;
    }

    switch ($type) {
        case "inscribe":
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height > $height) {
                $ratio = $height / $size[1];
                if($ratio>1){
                    $ratio=1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);
            }
            break;
        case "circumscribe":
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height < $height) {
                $ratio = $height / $size[1];
                if($ratio>1){
                    $ratio=1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);
            }
            break;
        default:
            $ratio = $width / $size[0];
            $new_width = floor($size[0] * $ratio);
            $new_height = floor($size[1] * $ratio);
            if ($new_height > $height) {
                $ratio = $height / $size[1];
                if($ratio>1){
                    $ratio=1;
                }
                $new_width = floor($size[0] * $ratio);
                $new_height = floor($size[1] * $ratio);                    
            }
            $width=$new_width;
            $height=$new_height;
            break;
    }
    $new_left = floor(($width - $new_width) / 2);
    $new_top = floor(($height - $new_height) / 2);

    $bigimg = $icfunc($inputfile);
    $trumbalis = imagecreatetruecolor($width, $height);

    imagefill($trumbalis, 0, 0, $backgroundColor);
    imagecopyresampled($trumbalis, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);
    imagejpeg($trumbalis, $outputfile, $quality);

    imagedestroy($bigimg);
    imagedestroy($trumbalis);
    return true;
}


function photo_category_set_icon($photo_category_id, $site_id, $uploadedFile){

    $this_site_info = get_site_info($site_id);
    if(!$this_site_info){
        return false;
    }
    if($uploadedFile['size'] <= 0){
        return false;
    }
    if(!preg_match("/(jpg|gif|png|jpeg)$/i", $uploadedFile['name'], $regs)){
        return false;
    }
    # get file extension
    $file_extension = ".{$regs[1]}";
    
    $date = date('Y-m-d-h-i-s');

    $photo_category_info= photo_category_info($photo_category_id);
        
        # create file name

        # create directory
        $relative_dir = date('Y') . '/' . date('m');
        $site_root_dir = sites_root . '/' . $this_site_info['dir'];
        path_create($site_root_dir, "/gallery/$relative_dir/");

        $smallImageFile='';
        $bigImageFile='';

        return photo_category_update($photo_category_id, $site_id, 
                ['photo_category_icon' => ['small'=>$smallImageFile, 'big'=>$bigImageFile]]
                );
    
}
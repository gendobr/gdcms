<?php

















if ($photos['size']<=0) {echo "перевірте світлини!!!";}else

{
   $icon_label_m=$dest_dir.'/'.time()."_temp.jpg"; 
   copy($photos['tmp_name'], $icon_label_m);
   $size=getimagesize($icon_label_m);

   # ------------------ get new image size - begin -----------------------------
   if (($size[0]>$size[1])&&$size[0]>600)
   {
     $new_width=600;
     $new_height=$size[1]*600/$size[0];
     $new_width_s=150;
     $new_height_s=$size[1]*150/$size[0];
   }
   elseif (($size[0]<$size[1])&&$size[1]>600)
   {
     $new_height=600;
     $new_width=$size[0]*600/$size[1];
     $new_height_s=150;
     $new_width_s=$size[0]*150/$size[1];
   }
   else
   {
     $new_width=$size[0];
     $new_height=$size[1]; 
     if ($size[0]>=$size[1])
     {
        $new_width_s=150;
        $new_height_s=$size[1]*150/$size[0];
     }
     elseif ($size[0]<$size[1])
     {
       $new_height_s=150;
       $new_width_s=$size[0]*150/$size[1];
     }
   }
   # ------------------ get new image size - end -------------------------------

   img_resize($icon_label_m, $dest_dir.'/'.$icon_label_small, $new_width_s, $new_height_s, $rgb=0xFFFFFF, $quality=100); 
   unlink($icon_label_m);
}

?>
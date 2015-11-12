<?php


// ------------------------- HTML parser -- begin ------------------------------

function replace_src($html,$site_root_url)
{
   global $_BASE_DIR, $_IMAGES;
   $_BASE_DIR=preg_replace("/\\/+$/",'',$site_root_url);
   $_IMAGES=Array();
   return Array('html'=>preg_replace_callback("/(.*?)(?:src|SRC)=['\"]?([^>'\"]*)['\"\s]?([^>]*?)>/","repl",$html),'src'=>$_IMAGES);
}


function repl($els)
{
    global $_BASE_DIR,$_IMAGES;

    # prn($els[2],substr($els[2],0,strlen($_BASE_DIR)),$_BASE_DIR);
    //---------------------- save images to list -- begin ---------------------- 
      if(substr($els[2],0,strlen($_BASE_DIR))==$_BASE_DIR)
      {
        $_IMAGES[]=substr($els[2],strlen($_BASE_DIR));
        return $els[0];
      }
    //---------------------- save images to list -- end ------------------------ 
    
    if(!preg_match("/^https?:\\/\\/|^\\//", $els[2]))
    {
       $img=str_replace('\\','/',$els[2]);
       $img=preg_replace("/\\/+/",'/',$img);
       $img=preg_replace("/^\\//",'',$img);

       # if((strpos($els[2],'/')!==false or strpos($els[2],'\\')!==false))
       # {
       #     $img=preg_replace("/^.*[\/\\\\]([^\/\\\\]*)$/",'$1',$els[2]);
       # }
       # else
       # {
       #     $img=$els[2];
       # }
        $_IMAGES[]=$els[2];
        return "{$els[1]}src=\"$_BASE_DIR/".$img."\"{$els[3]}>";
    }
    else
    {
       // image is external one
       return $els[0];
    }
}
// ------------------------- HTML parser -- end --------------------------------

?>
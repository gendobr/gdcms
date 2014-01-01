<?php


//------------------ upload images - begin -------------------------------------
    # prn($_FILES);
      if(is_array($_FILES) && count($_FILES)>0)
      {
        if(   isset($_FILES['file'])
           && isset($_FILES['file']['name'])
           && is_array($_FILES['file']['name']))
        foreach($_FILES['file']['name'] as $key=>$val)
        {
          if(basename($key)==basename($val))
          {
            # prn($_FILES['file']['name'][$key]);
            # --------------- check if directory exists - begin ----------------
              $dirs=explode('/',dirname($key));
              # prn($dirs);
              $pt=sites_root."/{$this_site_info['dir']}";
              foreach($dirs as $dr)
              {
                if(strlen($dr)>0)
                {
                  $pt.='/'.$dr;
                  if(!is_dir($pt)) if(!@mkdir($pt)) @mkdir($pt,755);
                }
              }
            # --------------- check if directory exists - end ------------------
            @move_uploaded_file($_FILES['file']['tmp_name'][$key] , $pt."/".$_FILES['file']['name'][$key]);
          }
        }
      }
//------------------ upload images - end ---------------------------------------


?>
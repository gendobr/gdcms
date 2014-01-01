<?php

// function to create absolute URL from URL base and

function get_absolute_url($relative_url,$base_url='')
{
  $url_pattern='^(https?|mms|ftp)://([a-z0-9_-]+\.)+([a-z0-9_-]+)(:[0-9]+)?(/[-.a-z0-9_~]+)*/?(\?.*)?$';
  // do nothing if $relative_url is full URL
     if(eregi($url_pattern,$relative_url)) return $relative_url;

  // use current page URL if $base is empty

  // do nothing if $base is invalid URL
     if(!eregi($url_pattern,$base_url)) return false;

  // parse $base_url
     $parsed_base_url=parse_url($base_url);

  // compose new URL
     // scheme (protocol) always exists
        $new_url=$parsed_base_url['scheme'].'://';

     // username & password
        $up=Array('','');
        if(   isset($parsed_base_url['user'])
           && strlen($parsed_base_url['user'])>0) $up[0]=$parsed_base_url['user'];

        if(   isset($parsed_base_url['pass'])
           && strlen($parsed_base_url['pass'])>0) $up[1]=$parsed_base_url['pass'];

        $up=join(':',$up);
        if(strlen($up)>1) $new_url.=$up.'@';

     // host always exists
        $new_url.=$parsed_base_url['host'];

     // if port is set
        if(   isset($parsed_base_url['port'])
           && $parsed_base_url['port']!=80    ) $new_url.=':'.( (int)$parsed_base_url['port'] );

     // compose path
        if(!isset($parsed_base_url['path']))  $parsed_base_url['path']='/';

        if(ereg('^/',$relative_url))
        {
           $new_path=$relative_url;
        }
        else
        {
           $dirname=$parsed_base_url['path'];
           if(!ereg('/$',$dirname)) $dirname=dirname($dirname);
           $dirname=str_replace("\\","/",$dirname);
           $dirname=ereg_replace('/$','',$dirname);
           $tmp=$dirname.'/'.$relative_url;

           // "/xxx/../" => "/"
           do{
               $new_path=$tmp;
               $tmp=ereg_replace('/[^/]+/\.\./','/',$new_path);
           }while($tmp!=$new_path);

           // "/./" => "/"
           do{
               $new_path=$tmp;
               $tmp=ereg_replace('/\./','/'        ,$new_path);
           }while($tmp!=$new_path);
        }
        $tmp=ereg_replace('(\.\./)+','/' ,$new_path); $new_path=$tmp;
        $tmp=ereg_replace('^(\./)+'  ,'/' ,$new_path); $new_path=$tmp;
        $tmp=ereg_replace('/+'       ,'/' ,$new_path); $new_path=$tmp;
        $tmp=ereg_replace('^/+'      ,''  ,$new_path); $new_path=$tmp;

        $new_url.='/'.$new_path;


     // parse and reorder query string
        if(   isset($parsed_base_url['query'])
           && strlen($parsed_base_url['query'])>0 )
        {
            parse_str($parsed_base_url['query'], $query);
            ksort($query);
            $new_url.='?'.http_build_query($query);
        }

     // add fragment
        if(   isset($parsed_base_url['fragment'])
           && strlen($parsed_base_url['fragment'])>0 )
           $new_url.='#'.$parsed_base_url['fragment'];
     return $new_url;
}



print(get_absolute_url(
         $relative_url='./index.html'
        ,$base_url='http://www.zsu.zp.ua'
      )."\n");

print(get_absolute_url(
         $relative_url='/../index.html'
        ,$base_url='http://www.zsu.zp.ua/pk/index.php'
      )."\n");
?>
<?php
//===================================================================
// search functions
//


run('lib/socket_http_function');



function index_html($url, $page_title, $html_text) {

    $use_esc = "YES";
    // $stop_words_array = Array('не', 'на', 'под');
    $min_length = "2";
    $descr_size = "61440";
    $use_META = "YES"; 
    $use_META_descr = "YES";
    //global $fp_FINFO; //файл для записи
    //global $words;
    $numbers ='1234567890';
    $dozvil = "їЇіІєЄґҐ";  
    
   
    $size = strlen($html_text);
    //print "$url;<BR>\n";
    
 
    // ------------- get page title - begin ----------------------------
    if(strlen($page_title)>0)
    {
         $title = $page_title;
    }
    else
    {
      if (preg_match("/<title>\s*(.*?)\s*<\/title>/is",$html_text,$matches))
      {
          $title = $matches[1];
      }
      else
      {
          $title = "No title";
      }
    }
    preg_replace("/\s+/"," ",$title);
    if ($title == "") { $title = "No title"; }
    // ------------- get page title - end ------------------------------


    
    //---------------- get keywords and description META tags - begin --------
    $keywords = "";
    $description = "";
    if ($use_META == "YES") { 
      $res = get_META_info($html_text);
      $keywords = $res[0];
      $description = $res[1];
    }
    //---------------- get keywords and description META tags - end ----------


    //---------------- clear body - begin ------------------------------------
      function search_delete($from, $to, $html_text){
        $tmp=preg_split($from, $html_text);
        for($i=1,$cnt=count($tmp);$i<$cnt; $i++){
          $tmp[$i]=preg_split($to, $tmp[$i]);
          $tmp[$i]=isset($tmp[$i][1])?$tmp[$i][1]:'';
        }
        return join(' ',$tmp);
      }
      $html_text=search_delete("/<noscript/i","/noscript>/i", $html_text);
      $html_text=search_delete("/<script/i","/script>/i", $html_text);
      $html_text=search_delete("/<style/i","/style>/i", $html_text);
      $html_text=search_delete("/<!--/i","/-->/i", $html_text);
      $html_text = preg_replace("/<[^>]*>/s"," ",$html_text);
      //$html_text=search_delete("/</","/>/i", $html_text);
      // $html_text = strip_tags($html_text);
      // echo '<pre>'.htmlspecialchars($html_text).'</pre>'; exit();

      if ($use_esc == "YES") { 
        $html_text = preg_replace_callback("/&[a-zA-Z0-9#]*?;/", 'esc2char', $html_text);
      }
 
      if (($use_META_descr == "YES") & ($description != "")) {
        $descript = substr($description,0, $descr_size);
      } else {
        $html_text = preg_replace("/\s+/s"," ",$html_text);
        $descript = substr($html_text,0,$descr_size);
      }

      $html_text = $html_text." ".$keywords." ".$description;

      $html_text = preg_replace("/\s+/s"," ",$html_text);



      // convert to lower case taking into account unicode and native characters
         $html_text = to_lower_case($html_text);
      // remove common words
         $html_text = remove_common_words($html_text);

    //---------------- clear body - end ----------------------------------------


    // ---------------------- extract unique words - begin ---------------------
       $kw=explode(' ',$html_text); 
       $w=Array();
       if(is_array($kw)) {
         foreach($kw as $word) {
           if(strlen($word)>=$min_length) {
               $w[$word]=1;
           }
         }
       }
       ksort($w);
       reset($w);
       $html_text = join(' ',array_keys($w));
    // ---------------------- extract unique words - end ---------------------

    $title = preg_replace("/:+/",":",$title);
    $descript = preg_replace("/:+/",":",$descript);


    // return record to save into database
    
    unset($words_temp);
    unset($words_temp2);

    
    return array(
       'url'   => $url
      ,'size'  => $size
      ,'title' => $title
      ,'words' => $html_text
    );    
}


$html_esc = array(
        "&Agrave;" => chr(192),
        "&Aacute;" => chr(193),
        "&Acirc;" => chr(194),
        "&Atilde;" => chr(195),
        "&Auml;" => chr(196),
        "&Aring;" => chr(197),
        "&AElig;" => chr(198),
        "&Ccedil;" => chr(199),
        "&Egrave;" => chr(200),
        "&Eacute;" => chr(201),
        "&Eirc;" => chr(202),
        "&Euml;" => chr(203),
        "&Igrave;" => chr(204),
        "&Iacute;" => chr(205),
        "&Icirc;" => chr(206),
        "&Iuml;" => chr(207),
        "&ETH;" => chr(208),
        "&Ntilde;" => chr(209),
        "&Ograve;" => chr(210),
        "&Oacute;" => chr(211),
        "&Ocirc;" => chr(212),
        "&Otilde;" => chr(213),
        "&Ouml;" => chr(214),
        "&times;" => chr(215),
        "&Oslash;" => chr(216),
        "&Ugrave;" => chr(217),
        "&Uacute;" => chr(218),
        "&Ucirc;" => chr(219),
        "&Uuml;" => chr(220),
        "&Yacute;" => chr(221),
        "&THORN;" => chr(222),
        "&szlig;" => chr(223),
        "&agrave;" => chr(224),
        "&aacute;" => chr(225),
        "&acirc;" => chr(226),
        "&atilde;" => chr(227),
        "&auml;" => chr(228),
        "&aring;" => chr(229),
        "&aelig;" => chr(230),
        "&ccedil;" => chr(231),
        "&egrave;" => chr(232),
        "&eacute;" => chr(233),
        "&ecirc;" => chr(234),
        "&euml;" => chr(235),
        "&igrave;" => chr(236),
        "&iacute;" => chr(237),
        "&icirc;" => chr(238),
        "&iuml;" => chr(239),
        "&eth;" => chr(240),
        "&ntilde;" => chr(241),
        "&ograve;" => chr(242),
        "&oacute;" => chr(243),
        "&ocirc;" => chr(244),
        "&otilde;" => chr(245),
        "&ouml;" => chr(246),
        "&divide;" => chr(247),
        "&oslash;" => chr(248),
        "&ugrave;" => chr(249),
        "&uacute;" => chr(250),
        "&ucirc;" => chr(251),
        "&uuml;" => chr(252),
        "&yacute;" => chr(253),
        "&thorn;" => chr(254),
        "&yuml;" => chr(255),
        "&nbsp;" => " ",
        "&amp;" => " ",
        "&quote;" => " ",
    );

#=====================================================================
#
#    Function esc2char($str)
#    Last modified: 16.04.2004 18:22
#
#=====================================================================

function esc2char($str) {
    global $html_esc;
    $esc = $str[0];
    $char = "";
    if (preg_match ("/&[a-zA-Z]*;/", $esc)) {
        if (isset ($html_esc[$esc])) {
            $char = $html_esc[$esc];
        } else {
            $char = " ";
        }
    } elseif (preg_match ("/&#([0-9]*);/", $esc, $matches)) {
    	$char = chr($matches[1]);
    } elseif (preg_match ("/&#x([0-9a-fA-F]*);/", $esc, $matches)) {
    	$char = chr(hexdec($matches[1]));
    }	
    return $char;
}


#=====================================================================
#
#    Function get_META_info($html)
#    Last modified: 16.04.2004 17:54
#
#=====================================================================

function get_META_info($html) {
    if(!preg_match("/<\s*[Mm][Ee][Tt][Aa]\s*[Nn][Aa][Mm][Ee]=\"?[Kk][Ee][Yy][Ww][Oo][Rr][Dd][Ss]\"?\s*[Cc][Oo][Nn][Tt][Ee][Nn][Tt]=\"?([^\"]*)\"?\s*>/s",$html,$matches)) return Array('','');
    $res[0] = $matches[1];
    if(!preg_match("/<\s*[Mm][Ee][Tt][Aa]\s*[Nn][Aa][Mm][Ee]=\"?[Dd][Ee][Ss][Cc][Rr][Ii][Pp][Tt][Ii][Oo][Nn]\"?\s*[Cc][Oo][Nn][Tt][Ee][Nn][Tt]=\"?([^\"]*)\"?\s*>/s",$html,$matches)) return Array('','');
    $res[1] = $matches[1];
    return $res;
}

// --------------------------- remove common words - begin ---------------
function remove_common_words($ht) {
  $symbol = file(\e::config('APP_ROOT').'/scripts/lib/common_words.txt');
  $cnt=count($symbol);
  for($i=0;$i<$cnt;$i++) $symbol[$i] = str_replace(Array("\n","\r"),'',$symbol[$i]);  
  $html_text = ' '.$ht.' ';
  $html_text = trim(str_replace($symbol," ",$html_text));
  $html_text = trim(str_replace($symbol," ",$html_text));
  return $html_text;
}
// --------------------------- remove common words - end -----------------


// convert to lower case taking into account unicode and native characters
function to_lower_case($html_text) {
  $bigbukva  = Array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','А','Б','В','Г','Д','Е','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Э','Ю','Я','Ё','І','Ї','Ґ','Є','Ы','Ь','Ъ');
  $smalbukva = Array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','а','б','в','г','д','е','ж','з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','э','ю','я','ё','і','ї','ґ','є','ы','ь','ъ');
  return str_replace($bigbukva,$smalbukva,$html_text);
}


// remove page from search index
function clear_search_index($url) {
  global $sql;
  if(strlen($url)>0) {
    $query="DELETE FROM site_search WHERE url='".mysql_escape_string($url)."'";
  } else {
    $query="DELETE FROM site_search";
  }
  //echo $query;
  $sql->action($query);
}




function index_url($url) {
  $reply=http($url,Array(),Array());
  # $reply=Array(
  #       'is_successful'=>$success
  #      ,'body'=>$body
  #      ,'http_status'=>$obj_request->getResponseCode()
  #      ,'url'=>'...')
  # prn($reply['url']);
  # prn($reply);

  $tor=Array(
    'is_successful'=>false
   ,'url'=>$reply['url']
   ,'size'=>((int)$reply['headers']['content-length'])
   ,'title'=>''
   ,'words'=>''
   ,'links'=>Array()
   ,'checksum'=>''
   ,'headers'=>$reply['headers']
  );

  // if file size is greater than 2 MByte
  if($reply['headers']['content-length']>2*1048576) return $tor;

  if(!$reply['is_successful']) return $tor;

  $tor['is_successful']=true;
  $ind=index_html($tor['url'], '', $reply['body']);
  $tor['size']=$ind['size'];
  $tor['title']=$ind['title'];
  $tor['words']=$ind['words'];
  $tor['checksum']=md5($tor['words']);
  $tor['links']=get_links($tor['url'],$reply['body']);
  $tor['headers']=$reply['headers'];
  #prn($tor);
  return $tor;
}



function get_links($url,$html) {
   $links = preg_match_all("/(<a (?:(?:[^>]+)|(?:\"[^\"]*\")|(?:'[^']*')))/i"
                              ,$html
                              ,$matches);
   if(!$links) return Array();
   // prn($matches[0]);
   
   $tor=Array();
   $this_url_dirname=parse_url($url);
   # prn($this_url_dirname);
   if(!preg_match("/\\/\$/",$this_url_dirname['path'])) {
      $this_url_dirname['path']=dirname($this_url_dirname['path']).'/';
   }
   $url_prefix=$this_url_dirname['scheme'].'://'.$this_url_dirname['host'];
   if(isset($this_url_dirname['port'])) $url_prefix.=':'.$this_url_dirname['port'];
   $url_prefix.=$this_url_dirname['path'];

   preg_match("/^http:\\/\\/[^\\/]+/i",$url,$host_root_url);
   // prn($host_root_url); exit();
   $host_root_url=$host_root_url[0];

   foreach($matches[0] as $mt)
   {
      #prn(checkStr($mt));
      $link='';
      if(preg_match("/href=\"([^\"]*)\"/i" ,$mt,$regs))
      {
      # prn($regs);
        $link=$regs[1];
      }
      elseif(preg_match("/href='([^']*)'/",$mt,$regs))
      {
      # prn($regs);
        $link=$regs[1];
      }
      elseif(preg_match("/href=([^ >]*)/",$mt,$regs))
      {
      # prn($regs);
        $link=$regs[1];
      }
      # prn($link);
      if(strlen($link)>0)
      {
         # ------------------- relative link - begin ---------------------------
         if(!preg_match('/^(ftp|mailto|https?):/',$link))
         {
            if(preg_match("/^\\//",$link)) {
              $link=$host_root_url.'/'.$link; 
            }else {
              $link=$url_prefix.$link;
            }

            while(preg_match("/\\/[^\\/]+\\/\\.\\.\\//",$link)) {
                // $link = e r e g _ r e p l a c e('/[^/]+/\.\./','/',$link);
		$link = preg_replace("/\\/[^\\/]+\\/\\.\\.\\//",'/',$link);
            }
            while(preg_match("/\\/\\.\\//",$link)){
                // $link = e r e g _ r e p l a c e('/\./','/',$link);
		$link = preg_replace("/\\/\\.\\//",'/',$link);
            }
         }
         # ------------------- relative link - end -----------------------------
         #prn('before $link='.$link);
         $link=preg_replace("/#.*\$/",'',$link);
         $link=preg_replace("/\\/+/",'/',$link);
         $link=preg_replace("/^http:\\//",'http://',$link);
         #prn('after $link='.$link);
         $tor[]=$link;
      }
   }
   return array_unique($tor);
}



# --------------------- function to validate one URL - begin -------------------
# $url is string, URL
# $site_info is one database record
#
  function is_searchable($url, $site_info) {
    $tor=false;
    if(!is_valid_url($url)) 
    {
     echo "Invalid URL $url <br/>";
     return false;
    }
    # ------------------ create/load validation rules - begin ------------------
    if(!isset($site_info['regexp_validation']))
    {
      $repl=Array('/'=>"\\/",'.'=>"\\.");
      $site_info['regexp_validation']=Array();
      
      # the page is inside of site directory
      $site_info['regexp_validation'][]='/'.str_replace(array_keys($repl),array_values($repl),"^{$site_info['url']}").'/i';
      
      # common scripts like guestbook, forum etc.
      $site_info['regexp_validation'][]='/'.
                   str_replace(
                        array_keys($repl)
                       ,array_values($repl)
                       ,"^".sites_root_URL."/"
                   )."\\w+\\.php\\?([^&]+&)*site_id={$site_info['id']}($|&)/i";
      $site_info['regexp_validation'][]='/'.
                   str_replace(
                        array_keys($repl)
                       ,array_values($repl)
                       ,"^".site_root_URL."/"
                   )."\\w+\\.php\\?([^&]+&)*site_id={$site_info['id']}($|&)/i";
      // prn($site_info['regexp_validation']);
      if(strlen(trim($site_info['search_validate_url']))>0)
      {
        $tmp=explode("\n",$site_info['search_validate_url']);
        foreach($tmp as $rule)
        {
          $rule=trim($rule);
          if(strlen($rule)>0)  $site_info['regexp_validation'][]=$rule;
        }
      }
    }
    # ------------------ create/load validation rules - end --------------------

    # ------------------ create/load exclusion rules - begin -------------------
    if(!isset($site_info['regexp_exclusion']))
    {
      $repl=Array('/'=>"\\/",'.'=>"\\.");
      $site_info['regexp_exclusion']=Array();
      $site_info['regexp_exclusion'][]="/\\.doc(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.pdf(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.gif(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.jpg(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.jpeg(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.png(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.css(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.rar(\\?.*)?/i";
      $site_info['regexp_exclusion'][]="/\\.zip(\\?.*)?/i";

      $site_info['regexp_exclusion'][]="/^ftp:\\/\\//i";
      $site_info['regexp_exclusion'][]="/^mailto:/i";

      $site_info['regexp_exclusion'][]="/forum_id=\\d+/i";
      $site_info['regexp_exclusion'][]="/forum\\.php/i";
      $site_info['regexp_exclusion'][]="/action=forum\\//i";

      $site_info['regexp_exclusion'][]="/guestbook\\.php/i";
      $site_info['regexp_exclusion'][]="/action=gb\\//i";
      $site_info['regexp_exclusion'][]="/action=calendar(\\/|%2F)month/i";

      //prn($site_info['regexp_exclusion']);

      if(strlen(trim($site_info['search_exclude_url']))>0)
      {
        $tmp=explode("\n",$site_info['search_exclude_url']);
        foreach($tmp as $rule)
        {
          $rule=trim($rule);
          if(strlen($rule)>0)  $site_info['regexp_exclusion'][]=$rule;
        }
      }
    }
    # ------------------ create/load exclusion rules - end ---------------------
    
    # ------------------- apply validation rules - begin -----------------------
      foreach($site_info['regexp_validation'] as $rule)
      {
        $tmp=@preg_match($rule,$url);
        #prn($url." against ".$rule.' => '. ($tmp?'yes':'no'));
        $tor=$tor||$tmp;
        if($tor)
        {
          echo "<code><b><font color=green>valid:   </font></b></code>$url against $rule <br>";
          break;
        }
      } 
      if(!$tor) return false;
    # ------------------- apply validation rules - end ------------------------- 

    # ------------------- apply exclusion rules - begin ------------------------
      foreach($site_info['regexp_exclusion'] as $rule)
      {
        $tmp=@preg_match($rule,$url);
        if($tmp)
        {
           echo "<code><b><font color=red>invalid : </font></b></code>$url  against $rule <br>";
           return false;
        }
      } 
    # ------------------- apply exclusion rules - end -------------------------- 

    echo "<code><b><font color=green>valid:   </font></b></code>$url no rules <br>";
    return true;
  }
# --------------------- function to validate one URL - end ---------------------

?>
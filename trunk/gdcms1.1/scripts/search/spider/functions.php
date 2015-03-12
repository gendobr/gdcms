<?php

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

<?php
/*
   Class to edit database record > common methods

   developed by webous agency
   http://webous.com/
   author: Gennadiy Dobrovolsky
   e-mail: val@webous.com
   date:   September 27, 2006

*/

class db_record_editor_common
{
   var $exclude='';




// --------------------- extract posted values - begin -------------------------
   function filter_request($exclude_pattern='')
   {
     $tor=Array();
     $request=array_merge($_POST,$_GET);

     # ---------------- remove elements matching exclude pattern - begin -------
     $cnt=array_keys($request);
     foreach($cnt as $key)
     {
       if(strlen($exclude_pattern)>0) if(preg_match($exclude_pattern,$key)) unset($request[$key]);
       if(strlen($this->exclude)>0) if(preg_match($this->exclude,$key)) unset($request[$key]);
     }
     # ---------------- remove elements matching exclude pattern - end ---------
     # ---------------- create array - begin -----------------------------------
     $tor=Array();
     while(count($cnt=array_keys($request))>0)
     {
       foreach($cnt as $key)
       {
         if(is_array($request[$key]))
         {
            foreach($request[$key] as $k=>$v)
            {
              $request[$key."[$k]"]=$v;
            }
         }
         else
         {
            $val=get_magic_quotes_gpc()?stripslashes($request[$key]):$request[$key];
            if(sizeof($val)<1024) $tor[$key]=$val;
         }
         unset($request[$key]);
         # prn($request);
       }
     }
     # ---------------- create array - end -------------------------------------
     return $tor;
   }
// --------------------- extract posted values - end ---------------------------



// --------------------- create GET query -- begin -----------------------------
   function create_get_query($exclude_pattern='')
   {
     $tmp=$this->filter_request($exclude_pattern);
     foreach($tor as $key=>$val) $tor[]=$key.'='.rawurlencode($val);
     return join('&',$tor);
   }
// --------------------- create GET query -- end   -----------------------------



// --------------------- create hidden form elements -- begin ------------------
   function hidden_fields($exclude_pattern='')
   {
     $tmp=$this->filter_request($exclude_pattern);
     $tor=Array();
     foreach($tmp as $key=>$val) $tor[]="<input type=hidden name=\"".$this->htmlencode($key)."\" value=\"".$this->htmlencode($val)."\">";
     return join("\r\n",$tor);
   }
// --------------------- create hidden form elements -- end --------------------


    function check_int($ffff)  {if(isset($ffff)) return round($ffff*1); else return 0; }
    function check_float($val){return (float)(str_replace(',','.',$val));}
    function htmlencode($tostr) {if(isset($tostr)) return trim(htmlspecialchars ($tostr,ENT_QUOTES,'cp1251'));else return '';}
    function check_datetime($tostr) {if (!(($timestamp = strtotime($tostr)) === -1) ) return $tostr; else return false;}
    function is_valid_email($email){ return filter_var($email, FILTER_VALIDATE_EMAIL)?true:false;  }
    function is_valid_url($URL) { return filter_var($email, FILTER_VALIDATE_URL); }

// ------------------ draw options for <select> -- begin -----------------------
function draw_options($value,$options)
{
  $to_return='';
  foreach($options as $key=>$val)
  {
    if(is_array($val))
    {
       if($val[0]==$value && strlen($val[0])==strlen($value)) $selected=' selected '; else $selected='';
       $to_return.="<option value=\"".htmlencode(trim($val[0]))."\" $selected>{$val[1]}</option>\n";
    }
    else
    {
      if($key==$value && strlen($key)==strlen($value)) $selected=' selected '; else $selected='';
      $to_return.="<option value=\"".trim($key)."\" $selected>$val</option>\n";
    }
  }
  return $to_return;
}
// ------------------ draw options for <select> -- end -------------------------








  function enum($options)
  {
    $delim='';
    $to_return='';
    foreach($options as $key=>$val)
    {
      if(is_array($val))
      {
          $to_return.=$delim.trim($val[0]).'='.rawurlencode($val[1]);
      }
      else
      {
         $to_return.=$delim.trim($key).'='.rawurlencode($val);
      }
      $delim='&';
    }
    return $to_return;
  }

  function prn($tst){ if($this->debug){ echo '<hr><pre>'; print_r($tst); echo '</pre><hr>';}}

}
?>
<?php
/*
 * Input data is
 * $data - text from smarty processor
 */

/*
  shuffle characters according to secure key
*/


function encrypt($key,$str)
{
  //prn('encrypting : ', $str);
  $tor=$str;
  $transpositions=array_map('array_reverse',array_reverse(get_transpositions($key,$str)));
  //prn($transpositions);
  foreach($transpositions as $tr)
  {
    if($tr[0]!=$tr[1])
    {
      $pos0 = min($tr[0],$tr[1]);
      $ch0  = $tor{$pos0};

      $pos1 = max($tr[0],$tr[1]);
      $ch1  = $tor{$pos1};

      $tor = substr($tor,0,$pos0).$ch1.substr($tor,$pos0+1,$pos1-$pos0-1).$ch0.substr($tor,$pos1+1);
    }
  }
  return $tor;
}

function decrypt($key,$str)
{
  $transpositions=get_transpositions($key,$str);
  foreach($transpositions as $tr)
  {
    $tmp=$tor{$tr[1]};
    $tor{$tr[1]}=$tor{$tr[0]};
    $tor{$tr[0]}=$tmp;
  }
  return $tor;
}

function get_transpositions($key,$str)
{
   $tor=Array();
   $n_chars = strlen($str);
   $n_pairs = floor( 0.6 * strlen($str));
 //$n_pairs = 3;
   $from=my_hash($key);
   for($i=0;$i<$n_pairs; $i++)
   {
     $from = get_next_rnd($from);
     $from = get_next_rnd($from);
     $to   = get_next_rnd($from);
     //prn(join('=>',Array(floor($from * $n_chars),floor($to * $n_chars))));
     $tor[]=Array(floor($from * $n_chars),floor($to * $n_chars));
   }
   return $tor;
}

function my_hash($key)
{
    $hash=0;
    $keylen=strlen($key);
    for($i=0;$i<$keylen;$i++)
    {
       $hash = ($hash + ord($key{$i}) )/255;
    }
    // prn("hash($key)=$hash");
    return round($hash,8);
}

function get_next_rnd($num)
{
  $tor  = 11 * $num + 3.14159269;
  $tor -= floor($tor);
//prn($num.'=>'.$tor);
  return round($tor,8);
}
//
//------------------------- additional functions - begin -----------------------
function get_dictionary($str)
{
  //mb_internal_encoding('UTF-8');
  $len=strlen($str);
  $dict=Array();
  for($i=0;$i<$len;$i++)
  {
     $symbol=substr($str,$i,1);

     if(!isset($dict[$symbol])) $dict[$symbol]=0;
     $dict[$symbol]++;
  }
  arsort($dict);
  return array_keys($dict);
}

function add_delimiter($it){return ':'.$it;}
function str_to_codes($str, $dict)
{
  $len =strlen($str);
  $dct=array_flip($dict);
  $toret='';
  for($i=0;$i<$len;$i++)
  {
      $toret.=':'.$dct[substr($str,$i,1)];
  }
  return substr($toret,1);
}
//------------------------- additional functions - end -------------------------

 $page_content='
   ';

 ///$page_content='';
 $page_content.=$data;

    //--------------------- encrypt - begin ------------------------------
      $despw = md5(time().$sid);
      $page_content=str_to_codes($page_content.' ',$dict=get_dictionary($page_content));
      setcookie ("despw", $despw,time()+300);  /* expire in 5 minutes */
      $page_content=encrypt($despw, $page_content);
    //--------------------- encrypt - end --------------------------------
          //prn($dict);
          //$dict = str_replace('"""','"\\""',addcslashes('"'.join('","',$dict).'"' ,"\0..\37") );
          $cnt=count($dict);
          for($i=0;$i<$cnt;$i++)
          {
              $dict[$i]=addcslashes($dict[$i],"\0..\37\"\\");
          }
          $dict = '"'.join("\",\"",$dict).'"';


          //
          $page_content="

          <textarea  onblur='move_focus()' style=\"width:1px; height:1px; border:none;\" id=\"empty_\" >You cannot copy the text in frame</textarea>
          <iframe name=\"encoded\" id=\"encoded\" src=\"about:blank\" style='border:0px solid white; width:95%; height:300px;' width=\"95%\" height=\"300px\" onfocus='move_focus()' onmouseup='move_focus()'></iframe>
          <!-- div id=encoded>&nbsp;</div -->
          <script type=\"text/javascript\" src=".site_root_URL."/scripts/lib/shuffle.js></script>
          <script type=\"text/javascript\">
          <!--
             function disabletextselect(i){return false;}
             function renabletextselect(){return true;}
             //if IE4+
             document.onselectstart=new Function (\"return false\")
             //if NS6+
             if (window.sidebar){
                document.onmousedown=disabletextselect
                document.onclick=renabletextselect
             }


              function move_focus(){var lay=document.getElementById(\"empty_\"); if(lay) lay.focus(); return false;}

              window.onload=move_focus;


              var dict = [$dict];
              var themessage;
              themessage=\"$page_content\";
              themessage=codes_to_str(decrypt(Get_Cookie('despw'),themessage));
              SetCookie ('despw', 'Ne fig suda smotret');
              //alert(themessage);

              function writetoframe(msg)
              {
                document.getElementById('empty_').focus();
                var frm  = window.frames[\"encoded\"];
                if(frm)
                {
                  if(frm.document)
                  {
                    frm.document.open();
                    frm.document.writeln('<html><body>');


                    frm.document.write(msg);
                    frm.document.writeln('</body></html>');
                    frm.document.close();

                    // if IE4+
                       frm.document.onselectstart=new Function (\"return false\");
                    // if NS6+
                    if (frm.sidebar){
                        frm.document.onmousedown=new Function (\"return false\");
                        frm.document.onclick=new Function (\"return false\");
                    }
                    move_focus();
                  }
                }
              }
              writetoframe(themessage);

              function writetodiv(msg)
              {
                var frm  = document.getElementById('encoded');
                if(frm)
                {
                    frm.innerHTML=msg;
                }
              }
              //writetodiv(themessage);
              // setTimeout('writetoframe()',5000);
          // -->
          </script>
          <noscript>
             You need the JavaScript enabled to view this page
          </noscript>
          ";
echo $page_content;
    //return $page_content;
?>

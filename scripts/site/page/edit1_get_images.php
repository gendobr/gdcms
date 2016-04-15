<?php
/*
  get images for wyswyg editor
*/
// prn($_SESSION);
$image_file_extensions=explode(',',image_file_extensions);
$html_file_extension=Array('html','htm');


function html_get_title($file_content)
{
    $pagetitle=spliti('<title>',$file_content);
    if(!isset($pagetitle[1])) $pagetitle[1]='';
    $pagetitle=$pagetitle[1];
    $pagetitle=spliti('</title>',$pagetitle);
    $pagetitle=$pagetitle[0];
    if(strlen($pagetitle)==0)
    {
     $ff=explode("\n",trim($file_content));
     foreach($ff as $k=>$s)
     {
       $s=trim(strip_tags($s));
       if(strlen($s)>0)
       {
         $pagetitle=$s;
         unset($ff[$k]);
         $file_content=join("\n",$ff);
         break;
       }
     }
    }
    return $pagetitle;
}
// ----------- get current dir from posted parameters - begin ------------------
   $current_dir=isset($input_vars['current_dir'])?$input_vars['current_dir']:'/';
   //prn('1 $current_dir='.$current_dir);

   if(!isset($input_vars['current_dir']) && isset($input_vars['site_id']))
   {
       $this_site_info=\e::db_getonerow("SELECT * FROM {$table_prefix}site WHERE id=".( (int)$input_vars['site_id'] ));
       if($this_site_info)
       {
           $current_dir='/'.$this_site_info['dir'];
       }
   }
   //prn('2 $current_dir='.$current_dir);
// ----------- get current dir from posted parameters - end --------------------

// ------------------ check current dir - begin --------------------------------
   // check format
      $current_dir=realpath(\e::config('SITES_ROOT').$current_dir);
      // prn('3.1 $current_dir='.$current_dir);
      $sites_root = realpath(\e::config('SITES_ROOT'));
      // prn('3.2 $sites_root='.$sites_root);

      if(strlen($current_dir)<strlen($sites_root)) $current_dir='/';
      else $current_dir=str_replace($sites_root,'',$current_dir);
      $current_dir=str_replace('\\','/',$current_dir);

      if(!ereg('^/',$current_dir)) $current_dir='/'.$current_dir;

      // prn('3.3 $current_dir='.$current_dir);

      $current_dir=ereg_replace('/+','/',$current_dir);
      $current_dir=str_replace(\e::config('SITES_ROOT').'/','',$current_dir);
      // prn('3.4 $current_dir='.$current_dir);

   // check base dirs
      //if(is_admin()) prn($_SESSION['user_info']['sites']);
      $available_dirs=array_chunk(array_keys($_SESSION['user_info']['sites']), count($_SESSION['user_info']['sites'])/2);
      $available_dirs=$available_dirs[1];
      $base_dir_allowed=false;
      foreach($available_dirs as $dr) $base_dir_allowed=($base_dir_allowed||ereg("^/$dr",$current_dir));
      if(!$base_dir_allowed) $current_dir='/';
      //prn('current dir='.$current_dir);
      //prn('4 $current_dir='.$current_dir);
   // .
// ------------------ check current dir - end ----------------------------------


// do create dir
   if(isset($input_vars['create_dir_name']) && $current_dir!='/')
   {
       $create_dir_name=trim($input_vars['create_dir_name']);
       //prn($create_dir_name);
       $create_dir_name=str_replace('.','_',$create_dir_name);
       //prn($create_dir_name);
       $create_dir_name=str_replace(
           Array('�' ,'�' ,'�' ,'�' ,'�'  ,'�' ,'�' ,'�','�','�','�','�','�','�','�' ,'�','�','�','�','�','�','�','�','�','�','�','�','�','�','�' ,'�','�' ,
                 '�' ,'�' ,'�' ,'�' ,'�'  ,'�' ,'�' ,'�','�','�','�','�','�','�','�' ,'�','�','�','�','�','�','�','�','�','�','�','�','�','�','�' ,'�','?')
          ,Array('yo','ts','ch','sh','sch','yu','ya','y','a','b','v','g','d','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','e','yi',
                 'yo','ts','ch','sh','sch','yu','ya','y','a','b','v','g','d','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','e','yi')
          ,$create_dir_name);
       $create_dir_name=ereg_replace('[^a-z0-9_-]','_',strtolower($create_dir_name));
       //prn($create_dir_name);
       if(strlen($create_dir_name)>0) mkdir(\e::config('SITES_ROOT').$current_dir.'/'.$create_dir_name);
   }

// do upload
   if($current_dir!='/' && isset($_FILES['userfile'])) \core\fileutils::upload_file($_FILES['userfile'], realpath(\e::config('SITES_ROOT').$current_dir));

// get file list from current dir
// draw file list

   $tmp=\core\fileutils::ls($sites_root.$current_dir);
   $filelist=array_merge($tmp['dirs'],$tmp['files']);
   sort($filelist);
   //prn($filelist);
   $files='';
   $dirs ='';
   if($current_dir=='/')
   {
       foreach($filelist as $fl)
       {
          if($fl=='.' || $fl=='..') continue;
          if(is_dir(\e::config('SITES_ROOT').$current_dir.'/'.$fl))
          {
             $base_dir_allowed=false;
             foreach($available_dirs as $dr) $base_dir_allowed=($base_dir_allowed||ereg("^/$dr",'/'.$fl));
             if($base_dir_allowed) $dirs.=" <img src=\"".site_root_URL."/img/icon_dir.png\" width=18 wheight=18> <a href=\"index.php?action=site/page/edit1_get_images&current_dir=/$fl\">$fl</a><br>";
          }
       }
   }
   else
   {
       foreach($filelist as $key=>$fl)
       {
          if($fl=='.' || $fl=='..') continue;
          if(is_dir(\e::config('SITES_ROOT').$current_dir.'/'.$fl))
          {
              $dirs.=" <a href=\"index.php?action=site/page/edit1_get_images&current_dir=$current_dir/$fl\"><img src=\"".site_root_URL."/img/icon_dir.png\" width=18 wheight=18> $fl</a><br>";
          }
          else
          {
              if(!ereg('^\.',$fl))
              {
                  if(in_array(\core\fileutils::file_extention($fl),$image_file_extensions))
                  {
                       $insert_link="<a href=\"javascript:void(insert_img('".sites_root_URL."$current_dir/$fl'))\"><img src=\"".site_root_URL."/img/icon_paste.gif\" width=20 height=15></a>";
                       $files.="$insert_link <a href=\"".sites_root_URL."$current_dir/$fl\" onmouseover=\"show_preview('preview_{$key}','".sites_root_URL."$current_dir/$fl')\"  onmouseout=\"hide_preview('preview_{$key}')\">$fl</a><br>
                               <div id='preview_{$key}' class=prv style='display:none;'></div>
                       ";
                  }
                  elseif(in_array(\core\fileutils::file_extention($fl),$html_file_extension))
                  {
                         $filetitle=str_replace("'",'`',html_get_title(file_get_contents("$sites_root.$current_dir/$fl")));
                         $insert_link="<a href=\"javascript:void(insert_page('".sites_root_URL."$current_dir/$fl','{$filetitle}'))\"><img src=\"".site_root_URL."/img/icon_paste.gif\" width=20 height=15></a>";
                         $files.="$insert_link <a href=\"".sites_root_URL."$current_dir/$fl\">$fl ($filetitle)</a><br>";
                  }
                  else
                  {
                      //$insert_link="<img src=\"".site_root_URL."/img/tr.gif\" width=20 height=15>";
                      $insert_link="<a href=\"javascript:void(insert_file('".sites_root_URL."$current_dir/$fl'))\"><img src=\"".site_root_URL."/img/icon_paste.gif\" width=20 height=15></a>";
                      $files.="$insert_link <a href=\"".sites_root_URL."$current_dir/$fl\">$fl</a><br>";
                  }
              }

          }
       }

   }
   if($current_dir!='/') $dirs=" <a href=\"index.php?action=site/page/edit1_get_images&current_dir=".str_replace("\\",'/',dirname($current_dir))."\"><img src=\"".site_root_URL."/img/icon_parent_dir.gif\" width=18 height=18> ..</a><br>".$dirs;



// draw upload link
// http://127.0.0.1/cms/index.php?action=site/swfupload_form&site_id=1&dirname=
   if($current_dir!='/')
   {
      $this_site_dir=ereg_replace('^/+','',$current_dir);
      $this_site_dir=ereg_replace('/.*','',$this_site_dir);
      //prn($current_dir,$this_site_id);
      $this_site_id=\e::db_getonerow("SELECT id FROM {$table_prefix}site WHERE dir like '".\e::db_escape($this_site_dir)."%' ORDER BY dir ASC");
      $this_site_id=$this_site_id['id'];
      //prn($this_site_id);
      $upload_files_link="";
      $upload_files_link.="
      <form action=index.php>
      <input type=hidden name=action value='".checkStr($input_vars['action'])."'>
      <input type=hidden name=current_dir value='".checkStr($current_dir)."'>
      <nobr>{$text['Create_subdirectory']}<input type=text name=create_dir_name><input type=submit value=OK></nobr>
      </form>
      ";

      $upload_files_link.="
      <form action=index.php method=post  enctype=\"multipart/form-data\">
      <b>{$text['File']}</b>:<br>
      <input type=hidden name=action value='".checkStr($input_vars['action'])."'>
      <input type=hidden name=current_dir value='".checkStr($current_dir)."'>
      <nobr><input type=file name=userfile><input type=submit value=\"{$text['Upload']}\"></nobr>
      </form>
      <a href=\"".site_root_URL."/index.php?action=site/swfupload_form&site_id=$this_site_id&dirname=".str_replace("/$this_site_dir",'/',$current_dir)."\" target=_blank>{$text['Upload_more_files']}</a>
      ";


      $upload_files_link="<div style='float:right; width:300px;background-color:orange;padding:20px;'>$upload_files_link</div>";

   }else $upload_files_link='';


   $main_template_name='popup';

   $input_vars['page_title']  ='Get images';
   $input_vars['page_header'] ='Get images';
   $input_vars['page_content']="
     $upload_files_link
     <code>
     <div style='background-color:#e0e0e0;padding:3px;color:gray;'>current dir=<b>$current_dir</b></div>
     <div style='padding:3px;'>
     $dirs
     $files

     </div>
     </code>
   ";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?=site_charset?>" />
<title>������� ����������� � ��������</title>
<link href="http://www.arman-mebel.biz.ua/cms/templates/arman3/style.css" rel="stylesheet" type="text/css" />
<script>
function insert_img(str)
{
   if(window.opener)
   {
      window.opener.set_image(str);
      window.close();
   }
}


function insert_file(str)
{
   if(window.opener)
   {
      window.opener.set_html(' <a href='+str+'>'+str+'</a> ');
      window.close();
   }
}
function insert_page(str,title)
{
      window.opener.set_html(' <a href='+str+'>'+title+'</a> ');
}

function show_preview(pid,src)
{
  var p=document.getElementById(pid);
  if(p){p.innerHTML='<img src="'+src+'" width=100px>';p.style.display='block';}
}
function hide_preview(pid)
{
  var p=document.getElementById(pid);
  if(p){p.innerHTML='';p.style.display='none';}
}

</script>
<style>
.prv{position:absolute;background-color:silver; padding:10px;}
</style>
</head>
<body>
<div  style='padding:10px;background-color:white;color:black;'>
<h1>������� ����������� � ��������</h1>
<?=$input_vars['page_content']?>
</div>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>

</body>
</html>
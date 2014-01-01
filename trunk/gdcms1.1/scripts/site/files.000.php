<?
/*
  List of files for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
//------------------- site info - begin ----------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  // prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------

//------------------- file manager - begin -------------------------------------
  run('lib/file_functions');
  $site_root_dir = sites_root.'/'.$this_site_info['dir'];
  $site_root_url = sites_root_URL.'/'.$this_site_info['dir'];

  //------------------ upload files - begin ------------------------------------
    //prn($_FILES);
    if(is_array($_FILES))
    {
      $upload_dir = str_replace('//','/',$site_root_dir."/{$input_vars['dirname']}");
      if(!is_dir($upload_dir)) $upload_dir=$site_root_dir;
      foreach($_FILES as $key=>$val)
      {
         upload_file($key,$upload_dir);
      }
    }
  //------------------ upload files - end --------------------------------------

  //------------------ delete file - begin -------------------------------------
    if(strlen($input_vars['delete_file'])>1)
    {
       $delfile = str_replace('//','/',$site_root_dir."/{$input_vars['delete_file']}");
       rm_r($delfile);
    }
    clear('delete_file');
  //------------------ delete file - end ---------------------------------------

  //------------------ create subdir - begin -----------------------------------
    if(strlen($input_vars['newsubdir'])>1)
    {
       $curr_dir  = str_replace('//','/',$site_root_dir."/{$input_vars['dirname']}");
       // prn($curr_dir);
       if(is_dir($curr_dir))
       {
         mkdir($curr_dir.'/'.$input_vars['newsubdir']);
       }
    }
    clear('newsubdir','dirname');
  //------------------ create subdir - end -------------------------------------

  //------------------- draw list of files - begin -----------------------------
    $filelist = ls_r($site_root_dir);
    $dir_list = Array();
    $filelist[0]='/';
    // prn($filelist);
    
    $input_vars['page_content']="
    <table border=1px cellpadding=3px cellspacing=0 width=100%>
    ";
    foreach($filelist as $ke=>$fl)
    {
      $fname=str_replace($site_root_dir,'',$fl);
      if(is_dir($fl))
      {
        $ftype=$text['Dir'];
        $dir_list[$fname]=$fname;
        if($ke>0)
        {
          $input_vars['page_content'].="
          <tr>
            <td>
              <a href=\"javascript:void(change_state('file_{$ke}'))\"><img src=img/context_menu.gif border=1px width=15px height=15px></a>
              <div id=\"file_{$ke}\" style=\"display:none; position:absolute; border:solid 1px blue; padding:4px; text-align:left; background-color:#e0e0e0;\">
                <a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&delete_file=".rawurlencode($fname)."\" onclick=\"return confirm('{$text['Are_you_sure']}?')\">{$text['Delete']}</a><br />
              </div>
            </td>
            <td>$ftype</td>
            <td>$fname</td>
          </tr>
          "; 
        }
      }
      else
      {
        $ftype=$text['File'];
        $input_vars['page_content'].="
        <tr>
          <td>
            <a href=\"javascript:void(change_state('file_{$ke}'))\"><img src=img/context_menu.gif border=0></a><br/>
            <div id=\"file_{$ke}\" style=\"display:none; position:absolute; border:solid 1px blue; padding:4px; text-align:left; background-color:#e0e0e0;\">
              <a href=\"index.php?action=site/files&site_id={$this_site_info['id']}&delete_file=".rawurlencode($fname)."\" onclick=\"return confirm('{$text['Are_you_sure']}?')\">{$text['Delete']}</a><br />
            </div>
          </td>
          <td>$ftype</td>
          <td><a href=\"{$site_root_url}{$fname}\" target=_blank>$fname</a></td>
        </tr>
        "; 
      }
    }
    // prn($dir_list);
    $input_vars['page_content'].="

    <form action=index.php method=post>
    <input type=hidden name=action value=\"site/files\">
    <input type=hidden name=site_id value=\"{$site_id}\">
    <tr><td colspan=3>{$text['Create_subdirectory']} <input type=text name=newsubdir value=\"\"> {$text['in']} <select name=\"dirname\">".draw_options('',$dir_list)."</section>&nbsp;&nbsp;&nbsp;<input type=submit value=\"{$text['Create']}\"></td></tr>
    </form>

    <form action=index.php enctype=\"multipart/form-data\" method=post>
    <input type=hidden name=action value=\"site/files\">
    <input type=hidden name=site_id value=\"{$site_id}\">
    <tr><td colspan=3>
    {$text['Upload_files']}
    <ol>
     <li><input type=\"file\" name=userfile1></li>
     <li><input type=\"file\" name=userfile2></li>
     <li><input type=\"file\" name=userfile3></li>
     <li><input type=\"file\" name=userfile4></li>
     <li><input type=\"file\" name=userfile5></li>
     <li><input type=\"file\" name=userfile6><br />
         {$text['into']}
         <select name=\"dirname\">".draw_options('',$dir_list)."</select><br />
    </ol>
    <input type=submit value=\"{$text['Upload']}\">
    </td></tr>
    </form>

    </table>
    ";
  //------------------- draw list of files - end -------------------------------
//------------------- file manager - end ---------------------------------------

$input_vars['page_title']  = $this_site_info['title'] .' - '. $text['List_of_files'];
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['List_of_files'];
//--------------------------- context menu -- begin ----------------------------
  $input_vars['page_menu']['site']=Array('title'=>$text['Site_menu'],'items'=>Array());
  run('site/menu');
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
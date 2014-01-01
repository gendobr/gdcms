<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

$debug=false;
//------------------- get site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$site_id}");
  if($debug) prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
//------------------- get site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
$user_level = get_level($site_id);
if($user_level==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------
run('site/menu');
run('lib/file_functions');





  $input_vars['page_title']   = 'Import news';//$text['Editing_news'];
  $input_vars['page_header']  = 'Import news';//$text['Editing_news'];


// read row divider
  if(!isset($input_vars['import_row_divider'])) $input_vars['import_row_divider']='\n';
  $input_vars['import_row_divider']=trim($input_vars['import_row_divider']);
  if(strlen($input_vars['import_row_divider'])==0) $input_vars['import_row_divider']='\n';

// read column divider
  if(!isset($input_vars['import_column_divider'])) $input_vars['import_column_divider']='\t';
  $input_vars['import_column_divider']=trim($input_vars['import_column_divider']);
  if(strlen($input_vars['import_column_divider'])==0) $input_vars['import_column_divider']='\t';



// ------------------- upload file - begin -------------------------------------
   if(isset($_FILES['userfile']))
   {
       //prn($_FILES);
       $pt=sites_root."/{$this_site_info['dir']}"."/".encode_file_name($_FILES['userfile']['name']);
       //prn($pt);
       @move_uploaded_file($_FILES['userfile']['tmp_name'] , $pt);
       $dt=join('',file($pt));
       $replace=Array("\\t"=>"\t","\\n"=>"\n","\\r"=>"\r");
       $import_row_divider=str_replace(array_keys($replace),array_values($replace),$input_vars['import_row_divider']);
       $import_column_divider=str_replace(array_keys($replace),array_values($replace),$input_vars['import_column_divider']);
       $rows=split($import_row_divider,$dt);

       if(($cnt=count($rows))>0)
       {
           $n_columns=0;
           for($i=0;$i<$cnt;$i++)
           {
               $rows[$i]=split($import_column_divider,$rows[$i]);
               if(count($rows[$i])>$n_columns) $n_columns=count($rows[$i]);
           }
       }
   }
// ------------------- upload file - end ---------------------------------------

// ------------------- do import - begin ---------------------------------------
   $import_report='';
   if(isset($input_vars['import_row']))
   {
      function import_one_row($data)
      {
          global $table_prefix,$db;
          $flds=array_keys($data);
          $vals=array_values($data);
          $cnt=count($vals);
          for($i=0;$i<$cnt;$i++)
          {
              $vals[$i]="'".DbStr($vals[$i])."'";
          }
          $flds=join(',',$flds);
          $vals=join(',',$vals);
          $query="INSERT INTO {$table_prefix}news($flds) VALUES($vals)";
          //prn($query);
          db_execute($query);
      }
      //prn($input_vars['import_row']);
      //prn($input_vars['import_colname']);
      $import_report='<b>Importing news:</b><hr>';
      foreach($input_vars['import_row'] as $key=>$row)
      {
          $data=Array();
          foreach($input_vars['import_colname'] as $ke=>$va)
          {
              if($va!='')
              {
                 $data[$va]=trim($row[$ke]);
                 if(strlen($data[$va])==0) $data[$va]=$input_vars['import_default'][$va];
              }
          }
          $data['site_id']=$site_id;
          if(!isset($data['lang'])) $data['lang']=$input_vars['import_default']['lang'];
          if(!isset($data['cense_level'])) $data['cense_level']=(int)$input_vars['import_default']['cense_level'];
          if(!isset($data['title']) || strlen($data['title'])==0) $data['title']="news #{$key}";

          if(!isset($data['last_change_date']) || !checkDatetime($data['last_change_date'])) $data['last_change_date']=date('Y-m-d H:i:s');
          else $data['last_change_date']=date('Y-m-d H:i:s',strtotime($data['last_change_date']));
          //prn($data);
          $import_report.='<p>';
          foreach($data as $ke=>$va) $import_report.="$ke=>$va<br>";
          $import_report.='</p><hr>';
          import_one_row($data);
      }
   }
// ------------------- do import - end -----------------------------------------



  $input_vars['page_content']="
  <style>
   .nbd{border:none;}
   .cl{width:150px;}
   .nm{width:30px;display:block;float:left;background-color:#e0e0e0;color:black;}
  </style>
  <form action='index.php' method=POST name=editform  enctype=\"multipart/form-data\">
   <input type='hidden' name='action'  value='news/import'>
   <input type='hidden' name='site_id' value='{$site_id}'>
   <table>
   <tr><td class=nbd>File: </td><td class=nbd><input type='file' name='userfile' style='width:300px;'></td></tr>
   <tr><td class=nbd>Row divider:</td><td class=nbd><input type='text' name='import_row_divider' value='".checkStr($input_vars['import_row_divider'])."'> as regexp</td></tr>
   <tr><td class=nbd>Column divider:</td><td class=nbd><input type='text' name='import_column_divider' value='".checkStr($input_vars['import_column_divider'])."'> as regexp</td></tr>
   <tr><td></td><td class=nbd><input type='submit' value='Upload'></td></tr>
   </table>
   </form>
  ";

   if(isset($rows))
   {
      $uploaded_rows_form='';
      $col_names=Array('','lang','title','abstract','content','cense_level','tags','last_change_date');
      $col_names=array_combine($col_names,$col_names);
      $uploaded_rows_form.="<div><b>Defaults:</b><br><table>";
      foreach($col_names as $kn)
      {
          if($kn!='')
          {
              $uploaded_rows_form.="<tr><td class=nbd>{$kn}:</td><td class=nbd><input type='text' name='import_default[{$kn}]' value='".checkStr(isset($input_vars['import_default'][$kn])?isset($input_vars['import_default'][$kn]):($kn=='lang'?default_language:''))."'></td></tr>\n";
          }
      }
      $uploaded_rows_form.="</table><br><br>";

      $uploaded_rows_form.="<b>Data:</b><br><div><span class=nm style='background-color:transparent;'>&nbsp;</span>";
      for($i=0;$i<$n_columns;$i++)
      {
          $uploaded_rows_form.="<select class=cl name=import_colname[$i]>".draw_options('',$col_names)."</select>";
      }
      $uploaded_rows_form.='</div>';

      $cnt=count($rows);
      for($i=0;$i<$cnt;$i++)
      {
          $uploaded_rows_form.="<div><span class=nm>$i</span>";
          for($j=0;$j<$n_columns;$j++)
          {
              $uploaded_rows_form.="<textarea class=cl name=import_row[$i][$j] rows=4>".checkStr(isset($rows[$i][$j])?$rows[$i][$j]:'')."</textarea>";
          }
          $uploaded_rows_form.="</div>\n\n";
      }


      $input_vars['page_content'].="
      <form action='index.php' method=POST name=editform  enctype=\"multipart/form-data\">
       <input type='hidden' name='action'  value='news/import'>
       <input type='hidden' name='site_id' value='{$site_id}'>
       $uploaded_rows_form
       <input type='submit' value='Import'>
       </form>
      ";

   }
   $input_vars['page_content'].=$import_report;

//----------------------------- context menu - begin ---------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,25)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------

?>

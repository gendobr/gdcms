<?php
// ---------------------------- class Report -- begin --------------------------
/*
Class Report generates report structure
*/
class Report
{

   var $field;       // filelds
                     // $field[*]=Array('field'=>...,
                     //                 'alias'=>...,
                     //                 'type'=>'integer|float|string|unix_timestamp|datetime|id|enum:<val>=<text>&...',
                     //                 'label'=>'',
                     //                 'options'=>Array(),
                     //                 'group_operation'=> 'true|false'
                     //                 'filter'=>Array())
   var $orderby;
   var $cond;
   var $from;             // FROM part of SQL query       -- string -- input
   var $query;            // query to run -- output

   var $rows_per_page=rows_per_page; // rows per page -- parameter


   var $where=Array();            // all WHERE conditions -- Array() -- input
                                    // additional conditions can be entered here
   var $db;
   var $group_by;
   var $having;
   var $distinct;         // true|false
   var $exclude='^$';     // regexp to exclude from URLs

   function add_where($str){$this->where[]=$str;}
// ------------------ add field to list -- begin -------------------------------------------------------------------
   function add_field($_field,$_alias,$_ttype,$_label, $_group_operation=false)
   {

        if(isset($this->field[$_alias])) echo "<font color=red><b>field ($_field,$_alias,$_ttype,$_label, $_group_operation) already exists</b></font><br>";
        $_type=explode(':',$_ttype);
        if(isset($_type[1])) $_options=$_type[1]; else $_options='';
        $_type=$_type[0];


        global $_GET,$_POST,$_SERVER;
        $input_vars=array_merge($_GET,$_POST);
        //prn($input_vars);
        $_name=$_alias;
        $_screen_name=$_label;
        if(!is_array($this->group_by)) $this->group_by=Array();
        if(!is_array($this->where))    $this->where   =Array();
        if(!is_array($this->having))   $this->having  =Array();

        $tor=Array('field'=>$_field,
                   'alias'=>$_name,
                   'type'=>$_type,
                   'label'=>$_screen_name,
                   'group_operation'=>$_group_operation);
        //----------------------- options -- begin -----------------------------
        $_options=explode('&',$_options);
        $tor['options']=Array();
        foreach($_options as $val)
        {
          $oops=explode('=',$val);
          if(isset($oops[0]) && isset($oops[1]))
          {
             $tor['options'][rawurldecode($oops[0])]=rawurldecode($oops[1]);
          }
        }
        //----------------------- options -- end -------------------------------

        if(!$_group_operation)
           //$this->group_by[]=$_field;
           $this->group_by[]=$_alias;

        $tor['url_order_asc']="{$_SERVER['PHP_SELF']}?".$this->create_get_query('^orderby$')."&orderby={$_name}+asc";
        $tor['url_order_desc']="{$_SERVER['PHP_SELF']}?".$this->create_get_query('^orderby$')."&orderby={$_name}+desc";

        switch($_type)
        {
           case 'string':
               $varname='filter_'.$tor['alias'];
               //prn($input_vars[$varname]);
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(get_magic_quotes_gpc()) $input_vars[$varname]=stripslashes($input_vars[$varname]);
               //prn($varname,$input_vars[$varname]);
               //prn($varname,$input_vars[$varname]);
               $filter=Array('form_element_name'=>$varname,
                             'form_element_value'=>$this->checkStr($input_vars[$varname]),
                             'value'=>$input_vars[$varname]);

               if(strlen($input_vars[$varname])>0)
               {
                  if($_group_operation) $this->having[]=" ( {$_field} LIKE '%{$input_vars[$varname]}%' ) ";
                  else $this->where[]=" ( {$_field} LIKE '%".$this->DbStr($input_vars[$varname])."%' ) ";
               }
           break;


           case 'enum':
               $varname='filter_'.$tor['alias'];
               //prn($input_vars[$varname]);
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(get_magic_quotes_gpc()) $input_vars[$varname]=stripslashes($input_vars[$varname]);
               //prn($input_vars[$varname]);

               $filter=Array('form_element_name'=>$varname,
                             'form_element_value'=>$this->checkStr($input_vars[$varname]),
                             'form_element_options'=>$this->draw_options($this->checkStr($input_vars[$varname]),$tor['options']),
                             'value'=>$input_vars[$varname]);

               if(strlen($input_vars[$varname])>0)
               {
                  if($_group_operation) $this->having[]=" ( {$_field}='".$this->DbStr($input_vars[$varname])."' ) ";
                  else $this->where[]=" ( {$_field}='".$this->DbStr($input_vars[$varname])."' ) ";
               }
           break;

           case 'id':
               $filter=Array();

               $varname='filter_'.$tor['alias'];
               $filter['form_element_name']=$varname;
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(@ereg('^[0-9]+$',$input_vars[$varname]))
               {
                  $filter['form_element_value']=$this->checkInt($input_vars[$varname]);
                  $filter['value']=$input_vars[$varname];
                  if($_group_operation) $this->having[]=" ( {$_field}={$input_vars[$varname]} ) ";
                  else $this->where[]=" ( {$_field}={$input_vars[$varname]} ) ";
               }
           break;

           case 'integer':
           case 'int':

               $filter=Array();

               $varname='filter_'.$tor['alias'].'_min';
               $filter['form_element_min_name']=$varname;
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(preg_match('/^[0-9]+$/',$input_vars[$varname]))
               {
                  $input_vars[$varname]=$this->checkInt($input_vars[$varname]);
                  $filter['form_element_min_value']=$input_vars[$varname];
                  $filter['value_min']=$input_vars[$varname];
                  if($_group_operation) $this->having[]=" ( {$_field}>={$input_vars[$varname]} ) ";
                  else $this->where[]=" ( {$_field}>={$input_vars[$varname]} ) ";
               }

               $varname='filter_'.$tor['alias'].'_max';
               $filter['form_element_max_name']=$varname;
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(preg_match('/^[0-9]+$/',$input_vars[$varname]))
               {
                  $input_vars[$varname]=$this->checkInt($input_vars[$varname]);
                  $filter['form_element_max_value']=$input_vars[$varname];
                  $filter['value_max']=$input_vars[$varname];
                  if($_group_operation) $this->having[]=" ( {$_field}<={$input_vars[$varname]} ) ";
                  else $this->where[]=" ( {$_field}<={$input_vars[$varname]} ) ";
               }
            break;

            case 'float':

               $filter=Array();

               $varname='filter_'.$tor['alias'].'_min';
               $filter['form_element_min_name']=$varname;
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(is_numeric($input_vars[$varname]))
               {
                  $input_vars[$varname]=$this->checkFloat($input_vars[$varname]);
                  $filter['form_element_min_value']=$input_vars[$varname];
                  $filter['value_min']=$input_vars[$varname];
                  if($_group_operation) $this->having[]=" ( {$_field}>={$input_vars[$varname]} ) ";
                  else $this->where[]=" ( {$_field}>={$input_vars[$varname]} ) ";
               }

               $varname='filter_'.$tor['alias'].'_max';
               $filter['form_element_max_name']=$varname;
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(is_numeric($input_vars[$varname]))
               {
                  $input_vars[$varname]=$this->checkFloat($input_vars[$varname]);
                  $filter['form_element_max_value']=$input_vars[$varname];
                  $filter['value_max']=$input_vars[$varname];
                  if($_group_operation) $this->having[]=" ( {$_field}<={$input_vars[$varname]} ) ";
                  else $this->where[]=" ( {$_field}<={$input_vars[$varname]} ) ";
               }
            break;

            case 'unix_timestamp':
               $filter=Array();
               $varname='filter_'.$tor['alias'].'_min';
               $filter['form_element_min_name']=$varname;
               if(strlen($input_vars[$varname])>0)
               {
                  if (!(($timestamp = strtotime($input_vars[$varname])) === -1))
                  {
                       $filter['form_element_min_value']=$input_vars[$varname];
                       $filter['value_min']=$timestamp;
                       if($_group_operation) $this->having[]=" ( {$_field}>={$timestamp} ) ";
                       else $this->where[]=" ( {$_field}>={$timestamp} ) ";
                  }
               }

               $varname='filter_'.$tor['alias'].'_max';
               $filter['form_element_max_name']=$varname;
               if(strlen($input_vars[$varname])>0)
               {
                  if (!(($timestamp = strtotime($input_vars[$varname])) === -1))
                  {
                       $filter['form_element_max_value']=$input_vars[$varname];
                       $filter['value_max']=$timestamp;
                       if($_group_operation) $this->having[]=" ( {$_field}<={$timestamp} ) ";
                       else $this->where[]=" ( {$_field}<={$timestamp} ) ";
                  }
               }
            break;

            case 'datetime':
               $filter=Array();
               $varname='filter_'.$tor['alias'].'_min';
               $filter['form_element_min_name']=$varname;
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(strlen($input_vars[$varname])>0)
               {
                  if (!(($timestamp = strtotime($input_vars[$varname])) === -1))
                  {
                       $filter['form_element_min_value']=$input_vars[$varname];
                       $timestamp=date('Y-m-d H:i:s',$timestamp);
                       $filter['value_min']=$timestamp;
                       if($_group_operation) $this->having[]=" ( {$_field}>='{$timestamp}' ) ";
                       else $this->where[]=" ( {$_field}>='{$timestamp}' ) ";
                  }
               }

               $varname='filter_'.$tor['alias'].'_max';
               $filter['form_element_max_name']=$varname;
               if(!isset($input_vars[$varname])) $input_vars[$varname]='';
               if(strlen($input_vars[$varname])>0)
               {
                  if (!(($timestamp = strtotime($input_vars[$varname])) === -1))
                  {
                       $filter['form_element_max_value']=$input_vars[$varname];
                       $timestamp=date('Y-m-d H:i:s',$timestamp);
                       $filter['value_max']=$timestamp;
                       if($_group_operation) $this->having[]=" ( {$_field}<='{$timestamp}' ) ";
                       else $this->where[]=" ( {$_field}<='{$timestamp}' ) ";
                  }
               }
            break;

            default:
                $filter=false;
            break;
          }
          if(is_array($filter))
          {
             $tor['filter']=$filter;
             $this->field[$_name]=$tor;
             return true;
          }
          else
          {
             return false;
          }
   }
// ------------------ add field to list -- end   --------------------------------------------------------------------

// ------------- remove field from list -- begin ---------------------------------------------------------------
   function del_field($_field)
   {
       unset($this->field[$_field]);
   }
// ------------- remove field from list -- end  -----------------------------------------------------------------

// ---------------------- create query to run -- begin ---------------------------------------------------------
   function create_query()
   {
       global $_SERVER;

       // ----------- ordering -- begin ----------------------------------------
          if(isset($GLOBALS['_REQUEST']['orderby'])) $this->orderby=$GLOBALS['_REQUEST']['orderby']; else $this->orderby='';
          if(strlen($this->orderby)>0)
          {
             $ordering=explode(',',$this->orderby);
             $ORDERBY=Array();
             $fld_names=array_keys($this->field);
             foreach($ordering as $ord)
             {
                $ORDERING=explode(' ',trim($ord));
                if($ORDERING[1]!='desc') $ORDERING[1]='';
                if(in_array($ORDERING[0],$fld_names))
                   $ORDERBY[]=" {$ORDERING[0]} {$ORDERING[1]} ";
             }
             if(count($ORDERBY)>0) $ORDERBY=" ORDER BY ".join(',',$ORDERBY);
             else $ORDERBY='';
          }
          else $ORDERBY='';
       // ----------- ordering -- end   ----------------------------------------
      $this->group_by=array_unique($this->group_by);
      //prn(count($this->group_by), count($this->field),
      //    join(',',$this->group_by),join(',',array_keys($this->field)));
      if(count($this->group_by) < count($this->field) && count($this->group_by)>0)
        $group_by=" GROUP BY ".join(',',$this->group_by);
      else $group_by='';

      if(count($this->having)>0) $having=" HAVING ".join(' AND ',$this->having);
      else $having='';

      if(count($this->where)>0) $where=" WHERE ".join(' AND ',$this->where);
      else $where='';

      if($this->distinct) $distinct=' DISTINCT '; else $distinct='';

       // -------------- field list -- begin -----------------------------------
          $field_list=Array();
          foreach($this->field as $fld)  $field_list[]=" {$fld['field']} AS {$fld['alias']} ";
          $field_list=join(',',$field_list);
       // -------------- field list -- end   -----------------------------------

      $this->query="SELECT {$distinct} $field_list FROM {$this->from} {$where} {$group_by} {$having} {$ORDERBY}";
      // echo $this->query;
      return  $this->query;
   }
// ---------------------- create query to run -- end ---------------------------


// --------------------- create GET query -- begin -----------------------------
   function create_get_query($to_exclude='')
   {
       global $_GET,$_POST;

       $PARAM=array_merge($_GET,$_POST);
       $newquery=Array();
       foreach($PARAM as $k0=>$v0)
       {
          ///prn("{$this->exclude}, {$k0}, => ".eregi($this->exclude, $k0));
          if(!@eregi($this->exclude, $k0) && !@eregi($to_exclude, $k0) && !@eregi('password', $k0))
          {
             if(get_magic_quotes_gpc())  $newquery[]="{$k0}=".rawurlencode(stripslashes($v0));
             else $newquery[]="{$k0}=".rawurlencode($v0);
          }
       }
       $newquery=join('&',$newquery);
       return  $newquery;
   }
// --------------------- create GET query -- end   -----------------------------




// --------------------- show response -- begin --------------------------------
   function show()
   {
       global $_SERVER,$db,$_GET,$_POST;

       $this->create_query();

       if($this->rows_per_page*1==0) $this->rows_per_page=10;


        // ------------------ starting row number -- begin ---------------------
           if(isset($_REQUEST['start'])) $start=(int)$_REQUEST['start']; else $start=0;
           if($start<0) $start=0;
           $max_start=$start+$this->rows_per_page;
        // ------------------ starting row number -- end -----------------------

        $to_show=Array('fields'=>$this->field, 'rows'=>Array(),'forward'=>'','backward'=>'');


        // --------------- links to another pages -- begin ---------------------
           $numrows=$this->db_execute($this->query);
           $to_show['total_rows']=GetNumRows($numrows);
           unset($numrows);
           $newquery=$this->create_get_query('^start$');
           ///prn($to_show);

           // -------------------- previous page link -- begin -----------------
              $ans=$start-($this->rows_per_page); if ($ans<0) $ans=0;
              if($start>0) $to_show['backward']="{$_SERVER['PHP_SELF']}?{$newquery}&start={$ans}";
           // -------------------- previous page link -- end -------------------

           // -------------------- next page link -- begin ---------------------
              $ans=$start+($this->rows_per_page);
              if($ans<$to_show['total_rows'])
                 $to_show['forward']="{$_SERVER['PHP_SELF']}?{$newquery}&start={$ans}";
           // -------------------- next page link -- end -----------------------

           // -------------------- links to pages -- begin ---------------------
              $this->rows_per_page=max($this->rows_per_page,1);
              $imin=max($start-$this->rows_per_page*$this->rows_per_page,0);
              $imax=min($start+$this->rows_per_page*$this->rows_per_page,$to_show['total_rows']);
              $di=$imax-$imin+1;
              $pages=Array();
              ///prn($imin.'==='.$imax);
              for($i=$imin; $i<$imax;$i+=$this->rows_per_page)
              {
                  $page_id=1+floor($i/$this->rows_per_page);
                  $page_id=($start==$i)?"<b>[{$page_id}]</b>":$page_id;
                  $pages[]=Array('page_id' =>$page_id,
                                 'page_url'=>"{$_SERVER['PHP_SELF']}?{$newquery}&start={$i}");
              }
              $to_show['pages']=$pages;
              ///prn($pages);
           // -------------------- links to pages -- end -----------------------

        // --------------- links to another pages -- end -----------------------

        // -------------- show rows -- begin -----------------------------------
           if($start>$to_show['total_rows']) $start=0;
           $result=$this->SelectLimit($this->db,$this->create_query(),$start,$this->rows_per_page);
           ///prn($this->query);
           ///prn($result);
           ///if($result===false) echo "<hr><font color=red><b>ERROR:</b></font><br><b>Query</b>:\n<br>".$this->checkStr($this->query)."\n\n<br><br>\n\n<b>Message : </b>".$this->db->ErrorMsg()."\n\n<hr>\n\n";
           $to_show['rows']=$result;
           unset($result);
           $trow=array_keys($to_show['rows']);
           $field_numbers=array_keys($this->field);
           ///prn($field_numbers);
           # foreach($trow as $thr)
           #    foreach($field_numbers as $key=>$val)
           #        $to_show['rows'][$thr][$val]=$to_show['rows'][$thr][$key];
           //---------------------- replace enum data -- begin  ----------------
             $posa=0;
             $positions=Array();
             foreach($this->field as $fld)
             {
                if($fld['type']=='enum') $positions[]=Array($posa,$fld['alias']);
                ++$posa;
             }
             $posa=array_keys($to_show['rows']);
             foreach($posa as $key)
             {
                foreach($positions as $kkey)
                {
                   if(isset($this->field[$kkey[1]]['options'][''.$to_show['rows'][$key][$kkey[1]]]))
                   $visible_text=$this->field[$kkey[1]]['options'][''.$to_show['rows'][$key][$kkey[1]]];
                   else $visible_text='-------';
                   $to_show['rows'][$key][$kkey[1].'_value']=$to_show['rows'][$key][$kkey[1]];
                   $to_show['rows'][$key][$kkey[1]]=$visible_text;
                }
             }
           //---------------------- replace enum data -- end  ------------------
        // -------------- show rows -- end   -----------------------------------

        // --------------- hidden fields -- begin ------------------------------
           $hidden_fields="";
           //$exclude_pattern='^filter_|^start$';
           $exclude_pattern='^filter_';
           if(is_array($_GET))
              foreach($_GET as $key=>$val)
                 if(!@eregi($this->exclude, $key) && !@eregi($exclude_pattern,$key))
                    $hidden_fields.="<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\">";
           if(is_array($_POST))
              foreach($_POST as $key=>$val)
                 if(!@eregi($this->exclude, $key) && !@eregi($exclude_pattern,$key))
                    $hidden_fields.="<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\">";
           $to_show['hidden_fields']=$hidden_fields;
        // --------------- hidden fields -- end --------------------------------

        $to_show['action']=$_SERVER['PHP_SELF'];
        $to_show['form_name']='db_report';
        ///prn($to_show);
        //$to_show['total_rows']=$total_rows;
        return $to_show;
   }
// --------------------- show response -- end ----------------------------

  function checkInt($ffff)  {if(isset($ffff)) return round($ffff*1);                     else return 0; }
  function checkFloat($ffff){if(isset($ffff)) return $ffff*1;                            else return 0; }
  function checkStr($tostr) {if(isset($tostr)) return trim(htmlspecialchars($tostr,ENT_QUOTES,'cp1251'));else return '';}
  function DbStr($ffff)     {return mysql_real_escape_string($ffff);}
  function use_db($DbLink) {$this->db=$DbLink;}
  function set_table($table_name){$this->from =$table_name; }

  // ------------------ draw options for <select> -- begin ---------------------
  function draw_options($value,$options)
  {
    $to_return='';
    foreach($options as $key=>$val)
    {
        if($key==$value && strlen($key)==strlen($value)) $selected=' selected '; else $selected='';
        $to_return.="<option value=\"".trim($key)."\" $selected>$val</option>\n";
    }
    return $to_return;
  }
  // ------------------ draw options for <select> -- end -----------------------

  //function Execute($dblink,$query){ $result_id=mysql_query(trim($query),$dblink); if(!$result_id){ prn($query.'<br>'.mysql_error());} return $result_id;}
  function GetRows($result_id){ $tor=Array(); while($row=mysql_fetch_array($result_id)) $tor[]=$row; mysql_free_result($result_id); return $tor;}
  function GetOneRow($result_id){return mysql_fetch_array($result_id);}
  //function SelectLimit($dblink,$query,$start,$rows)
  //{
  //  $limit_query=ereg_replace(';?( |'."\n".'|'."\r".')*$','',$query.'  LIMIT '.checkInt($start).','.checkInt($rows).';');
  //  ///prn($limit_query);
  //  return $this->db_getrows($limit_query);
  //}
  function GetNumRows($result_id){ return mysql_num_rows ($result_id); }


// new versions
  function db_execute($query){if(debug) prn("<b><font color=\"red\">$query</font></b>"); $result_id=mysql_query(trim($query)); if(!$result_id){ prn($query.'<br>'.mysql_error());} return $result_id;}
  function db_getrows($query){ $result_id=db_execute($query); $tor=Array(); while($row=mysql_fetch_array($result_id,MYSQL_ASSOC)) $tor[]=$row; mysql_free_result($result_id); return $tor;}
  function db_getonerow($query){ $result_id=db_execute($query); $tor=mysql_fetch_array($result_id, MYSQL_ASSOC);  mysql_free_result($result_id); return $tor;}
  function SelectLimit($dblink,$query,$start,$rows) { $limit_query=preg_replace('/;?( |'."\n".'|'."\r".')*$/','',$query.'  LIMIT '.((int)$start).','.((int)$rows).';'); return $this->db_getrows($limit_query);  }






















/*
  function draw_default_list($response)
  {
      $tor="
      <table border=1>
       <form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
       {$response['hidden_fields']}\n";
     // ------------------------- header -- begin ------------------------------
     $tor.="<tr><th>Action</th>\n";
     foreach($this->field as $fld)
     {
         $tor.="
              <th align=center>
               <b>{$response['fields'][$fld['alias']]['label']}</b><br>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_asc']}\">asc</a>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_desc']}\">desc</a>
               </th>
               \n";
     }
     $tor.="</tr>\n";
     // ------------------------- header -- end --------------------------------

     // ------------------------- filter -- begin ------------------------------
     $tor.="<tr><td><input type=submit name=submit value=\"Search\"></td>\n";
     foreach($this->field as $fld)
     {
         switch($fld['type'])
         {
            case 'id':
              $tor.="
                    <td align=center>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\"
                                     size=3>
                    </td>
              ";
            break;

            case 'string':
              $tor.="
                    <td align=center>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$response['fields'][$fld['alias']]['filter']['form_element_value']}\">
                    </td>
              ";
            break;

            case 'integer':
            case 'float':
              $tor.="
                    <td align=center>
                    <nobr>&ge;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_value']}\"
                                         size=3></nobr>
                    <nobr>&le;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_value']}\"
                                         size=3></nobr>
                    </td>
              ";
            break;
            case 'enum':
              $tor.="
                    <td align=center>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
                    </td>
              ";
            break;
            case 'unix_timestamp':
            case 'datetime':
              $tor.="
                    <td align=center>
                    <nobr>&ge;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_value']}\"
                                         size=10>
                    <!-- input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_min_name']})\" --></nobr>
                    &le;<input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
                                        value=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_value']}\"
                                         size=10>
                    <!-- input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{$response['form_name']}.{$response['fields'][$fld['alias']]['filter']['form_element_max_name']})\" -->
                    </td>
              ";
            break;
         }
     }
     $tor.="</tr>\n";
     // ------------------------- filter -- end --------------------------------

     $tor.="</table>\n";
     return $tor;
  }

  function draw_smarty_template()
  {
     global $_SERVER;
     $tor="\n\n\n\n{literal}\n
             <script language=\"JavaScript\" src=\"modules/lib/popupcalend/calendar.js\"></script>
             <script language=\"JavaScript\">
             <!--
              function ChangeState(cid)
              {
                var lay=document.getElementById(\"details\"+cid);
                if (lay.style.display==\"none\") lay.style.display=\"block\";
                else lay.style.display=\"none\";
              }
              // -->
              </script>
              {/literal}


              <table border=1>
     <form action=\"{\$response.action}\" name=\"{\$response.form_name}\" method=\"post\">
     {\$response.hidden_fields}
     \n";
     // ------------------------- header -- begin ------------------------------
     $tor.="<tr><th>{\$text.Action}</th>\n";
     foreach($this->field as $fld)
     {
         $tor.="
              <th align=center>
               <b>{\$response.fields.{$fld['alias']}.label}</b><br>
               <a href=\"{\$response.fields.{$fld['alias']}.url_order_asc}\">{\$text.ascending}</a>
               <a href=\"{\$response.fields.{$fld['alias']}.url_order_desc}\">{\$text.descending}</a>
               </th>
               \n";
     }
     $tor.="</tr>\n";
     // ------------------------- header -- end --------------------------------

     // ------------------------- filter -- begin ------------------------------
     $tor.="<tr><td><input type=submit name=submit value=\"{\$text.Search}\"></td>\n";
     foreach($this->field as $fld)
     {
         switch($fld['type'])
         {
            case 'id':
              $tor.="
                    <td align=center>
                    <input type=text name =\"{\$response.fields.{$fld['alias']}.filter.form_element_name}\"
                                     value=\"{\$response.fields.{$fld['alias']}.filter.form_element_value}\"
                                     size=3>
                    </td>
              ";
            break;

            case 'string':
              $tor.="
                    <td align=center>
                    <input type=text name =\"{\$response.fields.{$fld['alias']}.filter.form_element_name}\"
                                     value=\"{\$response.fields.{$fld['alias']}.filter.form_element_value}\">
                    </td>
              ";
            break;

            case 'integer':
            case 'float':
              $tor.="
                    <td align=center>
                    &ge;<input type=text name=\"{\$response.fields.{$fld['alias']}.filter.form_element_min_name}\"
                                        value=\"{\$response.fields.{$fld['alias']}.filter.form_element_min_value}\"
                                         size=3>
                    &le;<input type=text name=\"{\$response.fields.{$fld['alias']}.filter.form_element_max_name}\"
                                        value=\"{\$response.fields.{$fld['alias']}.filter.form_element_max_value}\"
                                         size=3>
                    </td>
              ";
            break;
            case 'enum':
              $tor.="
                    <td align=center>
                    <select name =\"{\$response.fields.{$fld['alias']}.filter.form_element_name}\">
                    <option value=''> </option>
                    {\$response.fields.{$fld['alias']}.filter.form_element_options}
                    </select>
                    </td>
              ";
            break;
            case 'unix_timestamp'.
              $tor.="
                    <td align=center>
                    &ge;<input type=text name=\"{\$response.fields.{$fld['alias']}.filter.form_element_min_name}\"
                                        value=\"{\$response.fields.{$fld['alias']}.filter.form_element_min_value}\"
                                         size=3>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{\$response.form_name}.{\$response.fields.{$fld['alias']}.filter.form_element_min_name})\">
                    &le;<input type=text name=\"{\$response.fields.{$fld['alias']}.filter.form_element_max_name}\"
                                        value=\"{\$response.fields.{$fld['alias']}.filter.form_element_max_value}\"
                                         size=3>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.{\$response.form_name}.{\$response.fields.{$fld['alias']}.filter.form_element_max_name})\">
                    </td>
              ";
            break;
         }
     }
     $tor.="</tr>\n";
     // ------------------------- filter -- end --------------------------------


     // ------------------------- rows -- begin --------------------------------
     $tor.="{section name=row_id loop=\$response.rows}\n\n<tr>\n<td>\n".
           "<a href=\"#\" onclick=\"ChangeState('{\$smarty.section.row_id.rownum}'); return false;\">{\$text.Context_Menu}</a>\n".
           "<div style=\"display:none;\" name=\"details{\$smarty.section.row_id.rownum}\" id=\"details{\$smarty.section.row_id.rownum}\">\n".
           "{section name=cn_id loop=\$response.rows[row_id].context_menu}\n".
           "<a href=\"{\$response.rows[row_id].context_menu[cn_id].URL}\" {\$response.rows[row_id].context_menu[cn_id].attributes}>{\$response.rows[row_id].context_menu[cn_id].innerHTML}</a><br/>\n".
           "{/section}\n".
           "</div>\n".
           "</td>\n";
     foreach($this->field as $fld)
     {
         $tor.="<td align=left valign=top>{\$response.rows[row_id].{$fld['alias']}}</td>\n";
     }
     $tor.="\n</tr>\n{/section}\n";
     // ------------------------- rows -- end ----------------------------------


     // ------------------------- paging - begin -------------------------------
        $fld_cnt=count($this->field)+1;
        $left_width=round($fld_cnt/2.0);
        $right_width=$fld_cnt-$left_width;
        $tor.="
        <tr>
        <td colspan=\"{$fld_cnt}\" align=left>
        {if \$response.backward neq ''}<a href=\"{\$response.backward}\">{\$text.Previous_page}</a>{/if}
        {section name=page_id loop=\$response.pages}
        <a href=\"{\$response.pages[page_id].page_url}\">{\$response.pages[page_id].page_id}</a>&nbsp;
        {/section}
        {if \$response.forward neq ''}<a href=\"{\$response.forward}\">{\$text.Next_page}</a>{/if}
        </td>
        </tr>\n\n";
     // ------------------------- paging - end ---------------------------------

     // ------------------------- list is empty - begin ------------------------
        $tor.="<tr><td colspan=\"{$fld_cnt}\" align=center>{\$response.total_rows} {\$text.rows_found}</td></tr>\n\n";
     // ------------------------- list is empty - end --------------------------

     $tor.="</form>\n</table>\n\n\n\n";
     return $tor;
  }




  function draw_gd_template()
  {
     global $_SERVER;
     $tor="<table border=1>
     <form action=\"<%response:action%>\" name=\"<%response:form_name%>\">
     <%response:hidden_fields%>
     \n";
     // ------------------------- header -- begin ------------------------------
     $tor.="<tr><th><%text:Action%></th>\n";
     foreach($this->field as $fld)
     {
         $tor.="
              <th align=center>
               <b><%response:fields:{$fld['alias']}:label%></b><br>
               <a href=\"<%response:fields:{$fld['alias']}:url_order_asc%>\"><%text:ascending%></a>
               <a href=\"<%response:fields:{$fld['alias']}:url_order_desc%>\"><%text:descending%></a>
               </th>
               \n";
     }
     $tor.="</tr>\n";
     // ------------------------- header -- end --------------------------------

     // ------------------------- filter -- begin ------------------------------
     $tor.="<tr><td><input type=submit name=submit value=\"<%text:Search%>\"></td>\n";
     foreach($this->field as $fld)
     {
         switch($fld['type'])
         {
            case 'enum':
              $tor.="
                    <td align=center>
                    <select name =\"<%response:fields:{$fld['alias']}:filter:form_element_name%>\">
                    <option value=''></option>
                    <%response:fields:{$fld['alias']}:filter:form_element_options%>
                    </select>
                    </td>
              ";
            break;
            case 'id':
            case 'string':
              $tor.="
                    <td align=center>
                    <input type=text name =\"<%response:fields:{$fld['alias']}:filter:form_element_name%>\"
                                     value=\"<%response:fields:{$fld['alias']}:filter:form_element_value%>\">
                    </td>
              ";
            break;
            case 'integer':
            case 'float':
              $tor.="
                    <td align=center>
                    &ge;<input type=text name=\"<%response:fields:{$fld['alias']}:filter:form_element_min_name%>\"
                                        value=\"<%response:fields:{$fld['alias']}:filter:form_element_min_value%>\"
                                         size=3>
                    &le;<input type=text name=\"<%response:fields:{$fld['alias']}:filter:form_element_max_name%>\"
                                        value=\"<%response:fields:{$fld['alias']}:filter:form_element_max_value%>\"
                                         size=3>
                    </td>
              ";
            break;
            case 'unix_timestamp':
              $tor.="
                    <td align=center>
                    &ge;<input type=text name=\"<%response:fields:{$fld['alias']}:filter:form_element_min_name%>\"
                                        value=\"<%response:fields:{$fld['alias']}:filter:form_element_min_value%>\"
                                         size=3>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.<%response:form_name%>.<%response:fields:{$fld['alias']}:filter:form_element_min_name%>)\">
                    &le;<input type=text name=\"<%response:fields:{$fld['alias']}:filter:form_element_max_name%>\"
                                        value=\"<%response:fields:{$fld['alias']}:filter:form_element_max_value%>\"
                                         size=3>
                    <input type=\"button\" value=\"...\" onclick=\"getCalendar(document.<%response:form_name%>.<%response:fields:{$fld['alias']}:filter:form_element_max_name%>)\">
                    </td>
              ";
            break;
         }
     }
     $tor.="</tr>\n";
     // ------------------------- filter -- end --------------------------------


     // ------------------------- rows -- begin --------------------------------

     $tor.="<%begin_block:row%>\n\n<tr>\n<td>\n".
           '<a href="#" onclick="ChangeState(\'<%row_key%>\'); return false;"><%text:Context_Menu%></a>'.
           '<div style="display:none;" name="details<%row_key%>" id="details<%row_key%>">'.
           "<%begin_block:context_menu%>".
           "<a href=\"<%menu_item:URL%>\"  <%menu_item:attributes%>><%menu_item:innerHTML%></a>".
           "<%end_block:context_menu%>".
           "</div>".
           "\n</td>\n";
     foreach($this->field as $fld)
     {
         $tor.="<td align=left valign=top><%row:{$fld['alias']}%></td>\n";
     }
     $tor.="\n</tr><%end_block:row%>\n";
     // ------------------------- rows -- end ----------------------------------


     // ------------------------- paging - begin -------------------------------
        $fld_cnt=count($this->field)+1;
        $tor.="<tr>
        <td colspan=\"{$fld_cnt}\" align=center>
        <%text:Pages%> : <%begin_block:page_link%> <a href=\"<%page:page_url%>\"><%page:page_id%></a> <%end_block:page_link%><br/>
        <%begin_block:backward%><a href=\"<%response:backward%>\"><%text:Previous_page%></a><%end_block:backward%>
        <%begin_block:forward%><a href=\"<%response:forward%>\"><%text:Next_page%></a><%end_block:forward%>
        </td></tr>\n\n";
     // ------------------------- paging - end ---------------------------------

     // ------------------------- list is empty - begin ------------------------
        $tor.="<tr><td colspan=\"{$fld_cnt}\" align=\"center\"><%response:total_rows%> <%text:rows_found%></td></tr>\n\n";
     // ------------------------- list is empty - end --------------------------

     $tor.="</form>\n</table>\n".
           "<script language=\"JavaScript\">
           <!--
            function ChangeState(cid)
            {
              var lay=document.getElementById(\"details\"+cid);
              if (lay.style.display==\"none\") lay.style.display=\"block\";
              else lay.style.display=\"none\";
            }
            // -->
            </script>";
     return $tor;
  }
  */
}



// ---------------------------- class Report -- end ----------------------------
/*
    $re=new Report;
    $re->db=$db;
    $re->distinct=true;
    $re->from=" course AS c LEFT OUTER JOIN g_c ON g_c.course_id=c.id AND g_c.group_id={$group_id} ";
    //$re->add_where(" ( p.start={$group_start} ) ");

    $re->add_field($field='Not IsNull(g_c.id)',
                   $alias='is_allowed',
                   $type='enum:1=yes&0=no',
                   $label='is allowed',
                   $_group_operation=false);
    $re->add_field($field='c.id',
                   $alias='course_id',
                   $type='id',
                   $label=$text['Course_id'],
                   $_group_operation=false);
    $re->add_field($field='c.name',
                   $alias='course_name',
                   $type='string',
                   $label=$text['Course_Name'],
                   $_group_operation=false);
    $re->add_field($field='c.description',
                   $alias='course_description',
                   $type='string',
                   $label='Description',
                   $_group_operation=false);
    $re->add_field($field='g_c.role',
                   $alias='role_in_course',
                   $type='id',
                   $label=$text['Role_in_Course'],
                   $_group_operation=false);
    unset($field,$alias,$type,$label, $_group_operation);
    ///prn($re->create_query());
    ///prn($re->draw_gd_template());
    $response=$re->show();
    ///
    prn($response);

  $list_thread_names=join('&',array_map('getenum',db_getrows("SELECT id,subject as name FROM {$table_prefix}forum_thread WHERE site_id={$this_site_info['id']}")));

*/
?>
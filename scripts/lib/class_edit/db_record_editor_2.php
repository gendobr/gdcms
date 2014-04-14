<?php
/*
   Class to edit database record
   author: Gennadiy Dobrovolsky
*/



class db_record_editor_2 extends db_record_editor_common
{
   var $field=Array(); // fields
                       // $field[*]=Class ('field'=>...,
                       //                 'alias'=>...,
                       //                 'type'=>'(integer|float|string|enum|datetime|unix_timestamp)(:[options])?',
                       //                 'label'=>'',
                       //                 'value'=>'',
                       //                 'form_name'=>'',
                       //                 'form_value'=>)
                       // [options] are :
                       //                 if type==enum(option_value=option_text(&option_value=option_text)*)
                       //                 {
                       //                    default=[default_value]&required=yes
                       //                 }
                       //                 if type==string
                       //                 {
                       //                    maxlength=[integer]&default=[default_value]&required=yes&html_denied=yes
                       //                 }
                       //                 if type==integer or type=float
                       //                 {
                       //                    max=[number1]&min=[number2]&default=[default_value]
                       //                 }
                       //                 if type==datetime
                       //                 {
                       //                    max=[number1]&min=[number2]&default=[default_value]&required=yes
                       //                 }
   var $table;              // table name
   var $id;                 // primary key value
   var $primary_key=false;  // primary key alias

   var $db;
   var $where_str;
   var $form_name_prefix='db_record_editor_';
   var $record_is_found=false;
   var $debug=false;
   var $exclude='^$';
   var $messages='';
   var $all_is_ok=true;

   /*
   var $text=Array('ERROR'=>'ERROR'
                  ,'Changes_saved_successfully'=>'Changes saved successfully'
                  ,'Length_of'=>'Length of'
                  ,'is_greater_than'=>'is greater than'
                  ,'value_of'=>'value of'
                  ,'is_empty'=>'is empty'
                  ,'is_not_set'=>'is not set'
                  ,'forbidden_value_of'=>'forbidden value of'
                  ,'is_less_than'=>'is less than'
                  ,'ERROR_invalid_value_of_primary_key'=>'ERROR: Invalid value of primary key %s'
                  );
   */
   var $text=Array('ERROR'=>'ОШИБКА'
                  ,'Changes_saved_successfully'=>'Изменения успешно сохранены'
                  ,'Length_of'=>'Длина строки'
                  ,'is_greater_than'=>'больше чем'
                  ,'value_of'=>'значение параметра'
                  ,'is_empty'=>'не задано'
                  ,'is_not_set'=>'не задано'
                  ,'forbidden_value_of'=>'недопустимое значение параметра'
                  ,'is_less_than'=>'меньше чем'
                  ,'ERROR_invalid_value_of_primary_key'=>'ОШИБКА: Недопустимое значение ключевого поля %s'
                  );


// --------------------- set primary key -- begin ------------------------------
  function set_primary_key($_keyname,$_keyvalue='')
  {
      // prn("$_keyname = $_keyvalue");
      if(!isset($this->field[$_keyname]))
      {
         $this->messages.='ERROR: Field '.$_keyname.' not found ';
         return false;
      }
      //prn('set_primary_key',$_keyname,$_keyvalue);
      if(!$this->field[$_keyname]->check($_keyvalue)) return false;

      $this->primary_key=$_keyname;

      $this->field[$_keyname]->set_value($_keyvalue);
      $this->field[$_keyname]->primary_key=true;
      $this->id=$this->field[$_keyname]->value;
      $this->where_str=" WHERE {$this->field[$_keyname]->field}=".$this->field[$_keyname]->sql_value();

  }

// --------------------- set primary key -- end --------------------------------

// ------------------ get basic info from database -- begin ------------------
function get_info_from_db()
{
   if(!$this->primary_key) return false;

   //prn('get_info_from_db()');
   // ------------------- create query -- begin --------------------------------
      $query=Array();
      foreach($this->field as $fld) $query[]=$fld->field.' AS '.$fld->alias;
      $query='SELECT '.join(',',$query).' FROM '.$this->table.' '.$this->where_str.';';
   // if($this->debug) echo "<hr>Extract record:\n<br>".$this->htmlencode($query)."\n<hr>\n\n";
   // ------------------- create query -- end ----------------------------------

   // run query
     $result = $this->db_getrows($query);

   // number of found records
     $num_records=count($result);
     if($num_records>1)
     {
        $this->messages.="ERROR: The field {$this->primary_key} is not primary key<br>\r\n";
        return false;
     }
     $this->record_is_found=($num_records==1);


     if($this->record_is_found)
     {
       $result = $result[0];
       $fld_nums=array_keys($this->field);
       foreach($fld_nums as $key)
       {
          $val=$result[$this->field[$key]->alias];
          //if($this->field[$key]->check($val))
          $this->field[$key]->set_value($val);
       }
     }
}
// ------------------ get basic info from database -- end ----------------------






  //-------------------------- update database - begin -------------------------
  function save()
  {
    // global $_GET,$_POST;
    $input_vars=$_REQUEST;//array_merge($_GET,$_POST);
    //prn($input_vars);
    //prn('save ...');
    # check if the form is submitted
      if($input_vars[$this->form_name_prefix.'is_submitted']!='yes') return false;

    # load posted data
      #prn('loading posted data : $this->all_is_ok='.$this->all_is_ok.';');
      $cnt=array_keys($this->field);
      foreach($cnt as $key)
      {
        $this->field[$key]->load_posted_data();
        $this->messages.=$this->field[$key]->messages;
        $this->all_is_ok=($this->all_is_ok && $this->field[$key]->all_is_ok);
        #prn($key.' : all_is_ok='.$this->field[$key]->all_is_ok.';');
      }
    // die('%%%%%%%%%%%%%%%%%%%%%');
      //prn($this->messages);
    # do nothing if error occurs
      if(!$this->all_is_ok) return false;

      // die('#');
    # report success
      $this->messages.="<font color=green><b>{$this->text['Changes_saved_successfully']}</b></font><br/>";

    if($this->record_is_found)
    {# ----------------- update record -- begin --------------------------------
       $fff=Array();
       //prn($this);
       foreach($this->field as $fld)
       {
          if($fld->primary_key) continue;
          $fff[]=$fld->field.'='.$fld->sql_value();
       }

       if(count($fff)>0 && $this->where_str!='')
       {
          $query="UPDATE {$this->table} SET ".join(',',$fff)." {$this->where_str};";
          if($this->debug) $this->prn($query);
          else
          {
             $this->prn(checkStr($query));
             $retcode=$this->db_execute($query);

             return $retcode;
          }
        }
    }# ----------------- update record -- end ----------------------------------
    else
    {# ----------------- create record -- begin --------------------------------
       $fff=Array();
       $vvv=Array();
       foreach($this->field as $fld)
       {
         if($fld->primary_key)
         {
            $primary_key_alias=$fld->alias;
         }
         else
         {
            $fff[]=$fld->field;
            $vvv[]=$fld->sql_value();
         }
       }

       if(count($fff)>0)
       {
         $query="INSERT INTO {$this->table}(".join(',',$fff).") VALUES(".join(',',$vvv).");";
         if($this->debug) $this->prn($query);
         else
         {
            $retcode = $this->db_execute($query);


            $new_ID=$this->db_getonerow("SELECT LAST_INSERT_ID() AS newid;");
            $this->id=$new_ID['newid'];
            $this->field[$primary_key_alias]->set_value($this->id);
            return $retcode;
          }
       }

    }# ----------------- create record -- end ----------------------------------

  }
  //-------------------------- update database - end ---------------------------










function draw_form()
{
  $tor=Array();
  global $_SERVER;
  $tor['action']=$_SERVER['PHP_SELF'];
  $tor['method']='post';
  $tor['name']=$this->form_name_prefix;

# ----------------- draw hidden elements - begin -------------------------------
  $tor['hidden_elements']=$this->hidden_fields('^'.$this->form_name_prefix)
    ."<input type=hidden name=\"{$this->form_name_prefix}is_submitted\" value=\"yes\">";
  foreach($this->field as $fld)
  {
    if(isset($fld->options['hidden']) && $fld->options['hidden']=='yes')
    {
      $tor['hidden_elements'].="<input type=hidden name=\"{$fld->form_element_name}\" value=\"{$fld->form_element_value}\">";
    }
  }
# ----------------- draw hidden elements - end ---------------------------------


  $tor['elements']=$this->field;
  $tor['messages']=$this->messages;
  return($tor);
}




function draw(&$form)
{
  $tor="
  {$form['messages']}
  <form action='{$form['action']}' method='{$form['method']}' name='{$form['name']}'>
  {$form['hidden_elements']}
  <table>
  ";

  foreach($form['elements'] as $el) $tor.=$el->draw();


  $tor.='
    <tr><td></td><td colspan=2><input type=submit value="Сохранить изменения"></td></tr>
    </table>
    </form>
  ';
  return($tor);
}






function draw_default_template()
{
  $tor="
  {\$form.messages}
  <form action='{\$form.action}' method='{\$form.method}' name='{\$form.name}'>
  {\$form.hidden_elements}
  <table width=\"100%\">
  ";

  foreach($this->field as $el) $tor.=$el->draw_default_template();


  $tor.='
    <tr><td></td><td colspan=2><input type=submit value="Save changes"></td></tr>
    </table>
    </form>
  ';
  return($tor);
}





function process()
{
     global $_GET,$_POST;
     $input_vars=array_merge($_GET,$_POST);
     if(!isset($input_vars["{$this->form_name_prefix}is_submitted"])) $input_vars["{$this->form_name_prefix}is_submitted"]='no';
     $this->get_info_from_db();
     if($input_vars["{$this->form_name_prefix}is_submitted"]=='yes')
     {
        ///prn(' Save');
        ///$this->messages='';
        ///$this->all_is_ok=true;
        return $this->save();
     }
}

  //----------------------- database interface -- begin ------------------------
  // MySQL functions
  //
    function DbStr($ffff){ return mysql_escape_string($ffff); }
    //function Execute($dblink,$query){if($this->debug) prn("<b><font color=\"red\">$query</font></b>"); $result_id=mysql_query(trim($query),$dblink); if(!$result_id){ prn($query.'<br>'.mysql_error());} return $result_id;}
    function GetRows($result_id){ $tor=Array(); while($row=mysql_fetch_array($result_id, MYSQL_ASSOC)) $tor[]=$row; mysql_free_result($result_id); return $tor;}
    function GetOneRow($result_id){return mysql_fetch_array($result_id, MYSQL_ASSOC);}
    function GetNumRows($result_id){ return mysql_num_rows ($result_id); }
    function GetAssociatedArray($result_id)  {     $tor=Array();     $tmp=GetRows($result_id);     if(!isset($tmp[0]['id'])) return $tor;     foreach($tmp as $tm) $tor[$tm['id']]=$tm['val'];     return $tor;  }
  //----------------------- database interface -- end --------------------------
// new versions
  function db_execute($query){if(debug) prn("<b><font color=\"red\">$query</font></b>"); $result_id=mysql_query(trim($query)); if(!$result_id){ prn($query.'<br>'.mysql_error());} return $result_id;}
  function db_getrows($query){ $result_id=db_execute($query); $tor=Array(); while($row=mysql_fetch_array($result_id,MYSQL_ASSOC)) $tor[]=$row; mysql_free_result($result_id); return $tor;}
  function db_getonerow($query){ $result_id=db_execute($query); $tor=mysql_fetch_array($result_id, MYSQL_ASSOC);  mysql_free_result($result_id); return $tor;}
  function SelectLimit($dblink,$query,$start,$rows) { $limit_query=ereg_replace(';?( |'."\n".'|'."\r".')*$','',$query.'  LIMIT '.((int)$start).','.((int)$rows).';'); return $this->db_getrows($limit_query);  }

  function use_db($_tname)   {$this->db=$_tname;}
  function set_table($table_name){$this->table =$table_name; }
  function del_field($_field){ unset($this->field[$_field]);      }
  function form_is_posted()
  {
    if(!isset($_REQUEST["{$this->form_name_prefix}is_submitted"])) return false;
    return $_REQUEST["{$this->form_name_prefix}is_submitted"]=='yes';
  }

  function value_of($field_name){return $this->field[$field_name]->value;}
}



?>
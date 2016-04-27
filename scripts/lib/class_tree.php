<?php
class tree
{
   var $db;      // database handle

   var $table;   // table name

   var $id;      // id     field - name
   var $start;   // start  field - name
   var $finish;  // finish field - name

   var $field=Array();

   var $debug=false;

   // ------------------- remove branch -- begin -------------------------------
   function remove_branch($node_id)
   {
      //--------------------- check if node exists -- begin --------------------
        $query="SELECT {$this->start},{$this->finish} FROM {$this->table} WHERE {$this->id}=".checkInt($node_id)." AND {$this->start}>0;";
        if($this->debug) prn($query);

        $drr=\e::db_getrows($query);

        if(count($drr)!=1) return 0;
        $drr=$drr[0];
        if($this->debug) prn($drr);
      //--------------------- check if node exists -- end ----------------------

      //--------------------- delete branch -- begin ---------------------------
        $query="DELETE FROM {$this->table} WHERE {$this->start}>={$drr[$this->start]} AND {$this->finish}<={$drr[$this->finish]};";
        if($this->debug) prn($query);
        else \e::db_execute($query);
      //--------------------- delete branch -- end -----------------------------

      //--------------------- shift -- begin -----------------------------------
        $dr=$drr[$this->finish]+1-$drr[$this->start];
        $query="UPDATE {$this->table} SET {$this->start}={$this->start}-{$dr} WHERE {$this->start}>{$drr[$this->finish]};";
        if($this->debug) prn($query);
        else \e::db_execute($query);

        $query="UPDATE {$this->table} SET {$this->finish}={$this->finish}-{$dr} WHERE {$this->finish}>{$drr[$this->finish]};";
        if($this->debug) prn($query);
        else \e::db_execute($query);
      //--------------------- shift -- end -------------------------------------
   }
   // ------------------- remove branch -- end ---------------------------------

   // ------------------- add child after $start -- begin ----------------------
   function add_child($node_id,$values)
   {

      //----------------------- check $node_id -- begin ------------------------
        $query="SELECT * FROM {$this->table} WHERE {$this->id}={$node_id}";
        if($this->debug) prn($query);

        $is_allowed=\e::db_getrows($query);
        //prn($is_allowed);
        if(count($is_allowed)==0) return Array();
        $this_node_info=$is_allowed[0];
      //----------------------- check $node_id -- end --------------------------


      //----------------------- ���������� ����� -- ������ ---------------------
        $query="UPDATE {$this->table} SET {$this->start}={$this->start}+2 WHERE {$this->start}>{$this_node_info[$this->start]};";
        if($this->debug) prn($query);  else \e::db_execute($query);

        $query="UPDATE {$this->table} SET {$this->finish}={$this->finish}+2 WHERE {$this->finish}>{$this_node_info[$this->start]};";
        if($this->debug) prn($query);  else \e::db_execute($query);
      //----------------------- ���������� ����� -- ����� ----------------------

      //----------------------- add record -- begin ----------------------------
        $vals=Array();
        $nams=Array();

        $nams[]=$this->start;   $vals[]=$this_node_info[$this->start]+1;

        $nams[]=$this->finish;  $vals[]=$this_node_info[$this->start]+2;

        foreach($this->field as $flds)
        {
            if(isset($values[$flds['alias']]))
            {
              $nams[]=$flds['name'];
              $vals[]=$values[$flds['alias']];
            }
        }
        $query="INSERT INTO {$this->table}(".join(',',$nams).") VALUES(".join(',',$vals).")";
        if($this->debug) prn($query);
        else \e::db_execute($query);
      //----------------------- add record -- end ------------------------------

      //----------------------- return inserted row -- begin -------------------
        $query="SELECT * FROM {$this->table} WHERE {$this->start}={$this_node_info[$this->start]};";
        if($this->debug) prn($query);

        $toret=\e::db_getrows($query);
        $toret=$toret[0];
        if($this->debug) prn($toret);
        return $toret;
      //----------------------- return inserted row -- end ---------------------
   }
   // ------------------- add child after $start -- end ------------------------

   // ---------------- show parents -- begin -----------------------------------
   function get_parents($node_id)
   {
      $item_list=Array();

      //--------------------------- create query -- begin ----------------------
        $flds=Array();
        if(!isset($this->start)) return $item_list;
        if(strlen(trim($this->start))==0) return $item_list;

        if(!isset($this->finish)) return $item_list;
        if(strlen(trim($this->finish))==0) return $item_list;
        $flds[]="s.{$this->id}" ;
        $flds[]="s.{$this->start}" ;
        $flds[]="s.{$this->finish}";

        foreach($this->field as $fl) $flds[]=" s.{$fl['name']} AS {$fl['alias']} ";

        $query='SELECT '.join(',',$flds).
               " FROM {$this->table} AS s INNER JOIN {$this->table} AS c ".
               "      ON (s.{$this->start}<=c.{$this->start} AND s.{$this->finish}>=c.{$this->finish})".
               "WHERE c.{$this->id}={$node_id} ORDER BY s.{$this->start}";
        if($this->debug) prn($query);


        $item_list=\e::db_getrows($query);
        if($this->debug) prn($item_list);
      //--------------------------- create query -- end ------------------------
      return $item_list;
   }
   // ---------------- show parents -- end -------------------------------------


   // ---------------- show descendants -- begin -------------------------------
   function get_descendants($node_id)
   {
      $item_list=Array();

      //--------------------------- create query -- begin ----------------------
        $flds=Array();
        $grps=Array();
        if(!isset($this->start)) return $item_list;
        if(strlen(trim($this->start))==0) return $item_list;

        if(!isset($this->finish)) return $item_list;
        if(strlen(trim($this->finish))==0) return $item_list;

        $flds[]=$grps[]="c.{$this->id}" ;
        $flds[]=$grps[]="c.{$this->start}" ;
        $flds[]=$grps[]="c.{$this->finish}";

        foreach($this->field as $fl)
        {
           $flds[]=" c.{$fl['name']} AS {$fl['alias']} ";
           $grps[]=" c.{$fl['name']} ";
        }

        $query="SELECT ".join(',',$flds)." ".
               "FROM ({$this->table} AS  s INNER JOIN {$this->table} AS c ".
               "      ON (    s.{$this->start} <c.{$this->start} ".
               "          AND s.{$this->finish}>c.{$this->finish}) ) ".
               "WHERE s.{$this->id}={$node_id} ".
               "GROUP BY ".join(',',$grps).' '.
               "ORDER BY c.{$this->start}";
        if($this->debug) prn(htmlspecialchars($query));

        $item_list=\e::db_getrows($query);

        if($this->debug) prn($item_list);
      //--------------------------- create query -- end ------------------------
      return $item_list;
   }
   // ---------------- show descendants -- end ---------------------------------


   // ---------------- show children -- begin ----------------------------------
   function get_children($node_id)
   {
      $item_list=Array();

      //--------------------------- create query -- begin ----------------------
        $flds=Array();
        $grps=Array();
        if(!isset($this->start)) return $item_list;
        if(strlen(trim($this->start))==0) return $item_list;

        if(!isset($this->finish)) return $item_list;
        if(strlen(trim($this->finish))==0) return $item_list;

        $flds[]=$grps[]="c.{$this->id}" ;
        $flds[]=$grps[]="c.{$this->start}" ;
        $flds[]=$grps[]="c.{$this->finish}";

        foreach($this->field as $fl)
        {
           $flds[]=" c.{$fl['name']} AS {$fl['alias']} ";
           $grps[]=" c.{$fl['name']} ";
        }

        $query="SELECT ".join(',',$flds).",count(x.{$this->id}) AS n_parents ".
               "FROM ({$this->table} AS  s INNER JOIN {$this->table} AS c ".
               "      ON (    s.{$this->start} <c.{$this->start} ".
               "          AND s.{$this->finish}>c.{$this->finish}) ) ".
               "      INNER JOIN {$this->table} AS x ".
               "      ON (    x.{$this->start} <c.{$this->start} ".
               "          AND x.{$this->finish}>c.{$this->finish}) ".
               "WHERE s.{$this->id}={$node_id}  AND (s.start<=x.start AND s.finish>=x.finish) ".
               "GROUP BY ".join(',',$grps).' '.
               "HAVING n_parents=1 ".
               "ORDER BY c.{$this->start}";
        if($this->debug) prn(htmlspecialchars($query));

        $item_list=\e::db_getrows($query);

        if($this->debug) prn($item_list);
      //--------------------------- create query -- end ------------------------
      return $item_list;
   }
   // ---------------- show children -- end ------------------------------------

   // ---------------- add field -- begin --------------------------------------
   function add_field($_name,$_alias)
   {
      if(!is_array($this->field)) $this->field=Array();
      $this->field[$_alias]=Array(
         'name' =>$_name,
         'alias'=>$_alias
      );
   }
   // ---------------- add field -- end ----------------------------------------
}

 /*
 $tr= new tree;
 $tr->db=$db;
 ///$tr->debug=true;
 $tr->table='grp';
 $tr->id ='id'
 $tr->start ='start' ;
 $tr->finish='finish';

 $tr->add_field('id','group_id');
 $tr->add_field('name','group_name');
 $tr->add_field('description','group_description');
 $tr->add_field('speciality_code','group_speciality_code');
 ///$tr->get_parents(1);
 ///$tr->get_children(117);
 ///$tr->get_descendants(117);
 ///$tr->add_child(117,Array('group_name'=>'\'new_group\'',
 ///                         'group_description'=>'\'new_group_description\'',
 ///                         'group_speciality_code'=>'\'speciality_code\''));

 ///$tr->remove_branch(118);

 ///prn($tr);
 unset($tr);
 */
?>
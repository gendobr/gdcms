<?php

/**
  Class to manage category tree
 */
class tree {

    var $name_id = 'category_id';
    var $name_start = 'start';
    var $name_finish = 'finish';
    var $name_deep = 'deep';
    var $name_table = 'deep';
    var $db;
    var $id = 0;
    var $children = Array();
    var $parents = Array();
    var $info = Array();
// additional conditions to select
    var $where = Array();
    var $debug = false;

    //------------------------ move to - begin ----------------------------------
    //# required data : $this->info, $category_id (=destination)
    //# root category cannot be moved
    //# category cannot be moved into itself
    function move_to($category_id=0) {
        // ----------- check destination - begin ------------------------------------
        if ($category_id <= 0){
            return false;
        }

        $cid = (int) $category_id;
        if ($cid > 0) {
            $query = "SELECT * FROM {$this->name_table} WHERE {$this->name_id}=$cid";
            $destination_info = $this->GetOneRow($this->Execute($this->db, $query));
        }
        else{
            $destination_info=false;
        }
        if (!$destination_info){
            return false;
        }

        #prn('$destination_info=',$destination_info);
        // ----------- check destination - end --------------------------------------
        # required data : $this->info
        if (!$this->info)
            return false;

        # root category cannot be moved
        if ($this->info[$this->name_start] == 0){
            return false;
        }

        # category cannot be moved into itself
        if ($this->info[$this->name_start] <= $destination_info[$this->name_start]
                && $destination_info[$this->name_finish] <= $this->info[$this->name_finish]
        ){
            return false;
        }

        #prn('$this->info',$this->info);
        $this->Execute($this->db, 'BEGIN;');
        // ---------------------- prepare new place - begin -------------------------
        $shift = $this->info['finish'] - $this->info['start'] + 1;
        $query = "UPDATE {$this->name_table} "
               . "SET {$this->name_start}={$this->name_start}+({$shift}) "
               . "WHERE {$this->name_start}>{$destination_info[$this->name_start]}";
        #prn('prepare new place',htmlencode($query));
        $this->Execute($this->db, $query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));

        $query = "UPDATE {$this->name_table} "
               . "SET {$this->name_finish}={$this->name_finish}+({$shift}) "
               . "WHERE {$this->name_finish}>{$destination_info[$this->name_start]}";
        #prn('prepare new place',htmlencode($query));
        $this->Execute($this->db, $query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));



        if ($this->info[$this->name_start] > $destination_info[$this->name_start])
            $this->info[$this->name_start]+=$shift;
        if ($this->info[$this->name_finish] > $destination_info[$this->name_start])
            $this->info[$this->name_finish]+=$shift;
        // ---------------------- prepare new place - end ---------------------------
        // ---------------------- move - begin --------------------------------------
        $d_deep = 1 + $destination_info[$this->name_deep] - $this->info[$this->name_deep];
        $diff = $destination_info[$this->name_start] + 1 - $this->info[$this->name_start];
        $query = "UPDATE  {$this->name_table}
                  SET {$this->name_start}={$this->name_start}+({$diff})
                     ,{$this->name_finish}={$this->name_finish}+({$diff})
                     ,{$this->name_deep}={$this->name_deep}+({$d_deep})
                  WHERE  {$this->info[$this->name_start]}<={$this->name_start}
                     AND {$this->name_finish}<={$this->info[$this->name_finish]}";
        #prn('move',htmlencode($query));
        $this->Execute($this->db, $query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));
        // ---------------------- move - end ----------------------------------------
        // ---------------------- clear previous place - begin ----------------------
        $query = "UPDATE {$this->name_table}
                  SET {$this->name_finish}={$this->name_finish}-({$shift})
                  WHERE {$this->name_finish}>{$this->info[$this->name_finish]}";
        #prn(htmlencode($query));
        $this->Execute($this->db, $query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));

        $query = "UPDATE {$this->name_table}
                  SET {$this->name_start}={$this->name_start}-({$shift})
                  WHERE {$this->name_start}>{$this->info[$this->name_finish]}";
        #prn(htmlencode($query));
        $this->Execute($this->db, $query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));
        // ---------------------- clear previous place - end ------------------------
        $this->Execute($this->db, 'COMMIT;');
        $this->info[$this->name_start]+=$diff;
        $this->info[$this->name_finish]+=$diff;
        return true;
    }

    //------------------------ move to - end ------------------------------------
    # ---------------------- move up - begin ------------------------------------
    function move_down($category_id=0) {
        if ($category_id <= 0)
            $cid = (int) $this->id; else
            $cid=(int) $category_id;

        # get category info
        $category_info = $this->GetOneRow($this->Execute($this->db,"SELECT * FROM {$this->name_table} WHERE {$this->name_id}={$cid} " . $this->sql_where() ));
        if (!$category_info)
            return false;


        # get parent info
        $query ="SELECT *
                 FROM {$this->name_table}
                 WHERE {$this->name_start}<{$category_info[$this->name_start]}
                   AND {$category_info[$this->name_finish]}<{$this->name_finish}
                   " . $this->sql_where() . "
                 ORDER BY {$this->name_start} DESC
                 LIMIT 0,1";
        $parent_info = $this->GetOneRow($this->Execute($this->db, $query));
        if (!$parent_info)
            return false;


        # get siblings
        $query ="SELECT ch.*
                 FROM {$this->name_table} AS ch
                 WHERE {$parent_info[$this->name_start]}<ch.{$this->name_start}
                     AND ch.{$this->name_finish}<{$parent_info[$this->name_finish]}
                     AND ch.{$this->name_deep}=" . ($parent_info[$this->name_deep] + 1) . "
                     " . $this->sql_where('ch') . "
                 ORDER BY ch.{$this->name_start}";
        $children = $this->GetRows($this->Execute($this->db, $query));

        # get nearest sibling
        $sibling = Array();
        foreach ($children as $ch) {
            if ($ch[$this->name_start] > $category_info[$this->name_finish]) {
                $sibling = $ch;
                break;
            }
        }
        if (count($sibling) == 0)
            return false;

        $this->Execute($this->db, 'BEGIN;');
        # prn('$sibling',$sibling);

        $dt = $sibling[$this->name_finish] - $category_info[$this->name_finish];
        $query ="UPDATE {$this->name_table}
                 SET $this->name_start=-$this->name_start
                    ,{$this->name_finish}=-{$this->name_finish}
                 WHERE {$sibling[$this->name_start]}<={$this->name_start}
                    AND {$this->name_finish}<={$sibling[$this->name_finish]}
                    " . $this->sql_where();
        # prn($query);
        $this->Execute($this->db, $query);

        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}={$this->name_start}+{$dt},
                     {$this->name_finish}={$this->name_finish}+{$dt}
                 WHERE {$category_info[$this->name_start]}<={$this->name_start}
                   AND {$this->name_finish}<={$category_info[$this->name_finish]}
                   " . $this->sql_where();
        # prn($query);
        $this->Execute($this->db, $query);

        $dt = -($category_info[$this->name_start] + $dt - $sibling[$this->name_finish] - 1);
        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}=abs({$this->name_start}+({$dt}))
                   , {$this->name_finish}=abs({$this->name_finish}+({$dt}))
                 WHERE {$this->name_start}<0 AND {$this->name_finish}<0
                 " . $this->sql_where();
        # prn($query);
        $this->Execute($this->db, $query);


        $this->Execute($this->db, 'COMMIT;');
    }

    # ---------------------- move down - end ------------------------------------
    # ---------------------- move up - begin ------------------------------------

    function move_up($category_id) {
        if ($category_id <= 0)
            $cid = (int) $this->id; else
            $cid=(int) $category_id;

        # get category info
        $category_info = $this->GetOneRow($this->Execute($this->db,"SELECT * FROM {$this->name_table} WHERE {$this->name_id}={$cid} " . $this->sql_where()));
        if (!$category_info)
            return false;
        //prn('category',$category_info['category_id'],$category_info['start'],$category_info['finish']);

        # get parent info
        $query = "SELECT * FROM {$this->name_table}
                  WHERE {$this->name_start}<{$category_info[$this->name_start]}
                     AND {$category_info[$this->name_finish]}<{$this->name_finish}
                     " . $this->sql_where() . "
                  ORDER BY {$this->name_start} DESC
                  LIMIT 0,1";
        //prn(htmlspecialchars($query));
        $parent_info = $this->GetOneRow($this->Execute($this->db, $query));
        if (!$parent_info)
            return false;
        //prn('parent',$parent_info['category_id'],$parent_info['start'],$parent_info['finish']);


        # get siblings
        $query ="SELECT ch.*
                 FROM {$this->name_table} AS ch
                 WHERE {$parent_info[$this->name_start]}<ch.{$this->name_start}
                     AND ch.{$this->name_finish}<{$parent_info[$this->name_finish]}
                     AND ch.{$this->name_deep}=" . ($parent_info[$this->name_deep] + 1) . "
                     " . $this->sql_where('ch') . "
                 ORDER BY ch.{$this->name_start}";
        $children = array_reverse($this->GetRows($this->Execute($this->db, $query)));

        # get nearest sibling
        $sibling = Array();
        foreach ($children as $ch) {
            if ($ch[$this->name_finish] < $category_info[$this->name_start]) {
                $sibling = $ch;
                break;
            }
        }
        if (count($sibling) == 0)
            return false;

        //prn('sibling',$sibling['category_id'],$sibling['start'],$sibling['finish']);

        # prn('$sibling',$sibling);
        $this->Execute($this->db, 'BEGIN;');
        $dt = $category_info[$this->name_start] - $sibling[$this->name_start];
        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}=-{$this->name_start}
                   , {$this->name_finish}=-{$this->name_finish}
                 WHERE {$sibling[$this->name_start]}<={$this->name_start}
                   AND {$this->name_finish}<={$sibling[$this->name_finish]}
                   " . $this->sql_where();
        //prn($query);
        $this->Execute($this->db, $query);

        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}={$this->name_start}-{$dt},
                     {$this->name_finish}={$this->name_finish}-{$dt}
                 WHERE {$category_info[$this->name_start]}<={$this->name_start}
                   AND {$this->name_finish}<={$category_info[$this->name_finish]}
                   " . $this->sql_where();
        //prn($query);
        $this->Execute($this->db, $query);

        $dt = $category_info[$this->name_finish] - $dt + 1 - $sibling['start'];
        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}=abs({$this->name_start}-{$dt})
                   , {$this->name_finish}=abs({$this->name_finish}-{$dt})
                 WHERE {$this->name_start}<0 AND {$this->name_finish}<0
                 " . $this->sql_where();
        //prn($query);
        $this->Execute($this->db, $query);
        $this->Execute($this->db, 'COMMIT;');
    }

    # ---------------------- move up - end --------------------------------------
    //------------------------ delete branch - begin ----------------------------
    function delete_branch($category_id=0) {
        if ($category_id <= 0)
            $cid = (int) $this->id; else
            $cid=(int) $category_id;
        if ($cid > 0) {
            $query = "SELECT *
                 FROM {$this->name_table}
                 WHERE {$this->name_id}=$cid
                      " . $this->sql_where();
            $_info = $this->GetOneRow($this->Execute($this->db, $query));
        }
        else
            $_info=false;
        //prn($query,$_info);
        if (!$_info)
            return false;

        // ----------------------- get deleted ids - begin --------------------------
        $query = "SELECT {$this->name_id} as id
              FROM {$this->name_table}
              WHERE {$_info[$this->name_start]}<={$this->name_start}
                AND {$this->name_finish}<={$_info[$this->name_finish]}
                " . $this->sql_where();
        $deleted_ids = $this->GetRows($this->Execute($this->db, $query));
        $cnt = count($deleted_ids);
        for ($i = 0; $i < $cnt; $i++)
            $deleted_ids[$i] = $deleted_ids[$i]['id'];
        //prn($query,$deleted_ids);
        // ----------------------- get deleted ids - end ----------------------------
        $this->Execute($this->db, 'BEGIN;');
        // ----------------------- delete branch - begin ----------------------------
        $query = "DELETE FROM {$this->name_table}
              WHERE {$_info[$this->name_start]}<={$this->name_start}
                AND {$this->name_finish}<={$_info[$this->name_finish]}
                " . $this->sql_where();
        //prn($query);
        $this->Execute($this->db, $query);
        // ----------------------- delete branch - end ------------------------------
        // ----------------------- update start fields - begin ----------------------
        $diff = $_info[$this->name_finish] - $_info[$this->name_start] + 1;
        $query = "UPDATE {$this->name_table}
              SET {$this->name_start}={$this->name_start}-{$diff}
              WHERE {$this->name_start}>{$_info[$this->name_start]}
                  " . $this->sql_where();
        //prn($query);
        $this->Execute($this->db, $query);
        // ----------------------- update start fields - end ------------------------
        // ----------------------- update finish fields - begin ---------------------
        $query = "UPDATE {$this->name_table}
              SET {$this->name_finish}={$this->name_finish}-{$diff}
              WHERE {$this->name_finish}>{$_info[$this->name_start]}
                  " . $this->sql_where();
        //prn($query);
        $this->Execute($this->db, $query);
        // ----------------------- update finish fields - end -----------------------
        $this->Execute($this->db, 'COMMIT;');
        return $deleted_ids;
    }

    //------------------------ delete branch - end ------------------------------
    //------------------------ add child - begin --------------------------------
    function add_child($category_id=0) {
        if ($category_id <= 0)
            $cid = (int) $this->id; else
            $cid=(int) $category_id;
        if ($cid > 0) {
            $query = "SELECT * FROM {$this->name_table}
                 WHERE {$this->name_id}=$cid
                      " . $this->sql_where();
            $_info = $this->GetOneRow($this->Execute($this->db, $query));
        }
        else
            $_info=false;
        if (!$_info)
            return false;

        $this->Execute($this->db, 'BEGIN;');
        $query = "UPDATE {$this->name_table}
              SET {$this->name_finish}={$this->name_finish}+2
              WHERE {$this->name_finish}>={$_info[$this->name_finish]}
                   " . $this->sql_where();
        // prn($query);
        $this->Execute($this->db, $query);

        $query = "UPDATE {$this->name_table}
              SET {$this->name_start} ={$this->name_start}+2
              WHERE {$this->name_start}>={$_info[$this->name_finish]}
                   " . $this->sql_where();
        // prn($query);
        $this->Execute($this->db, $query);

        $new_start = $_info[$this->name_finish];
        $new_finish = $_info[$this->name_finish] + 1;
        $new_deep = $_info[$this->name_deep] + 1;
        $query = "INSERT INTO {$this->name_table}(start, finish, deep)
                    values( $new_start, $new_finish, {$new_deep} );";
        // prn($query);
        $this->Execute($this->db, $query);
        $this->Execute($this->db, 'COMMIT;');

        $query = "SELECT LAST_INSERT_ID() AS newid;";
        $newid = $this->GetOneRow($this->Execute($this->db, $query));

        return $newid['newid'];
    }

    //------------------------ get children - begin -----------------------------
    function get_parents($condition='') {
        $cond = '';
        if (strlen($condition) > 0)
            $cond = " AND " . str_replace($this->name_table, 'ch', $condition);
        $cond.=$this->sql_where('ch');
        $query = "SELECT ch.*
             FROM {$this->name_table} AS ch
             WHERE   ch.{$this->name_start}<{$this->info[$this->name_start]}
                 AND {$this->info[$this->name_finish]}<ch.{$this->name_finish}
                 {$cond}
             ORDER BY ch.{$this->name_start}";
        //if(isset($_REQUEST['debug'])) prn($this,checkStr($query));
        $this->parents = $this->GetRows($this->Execute($this->db, $query));
        //if(isset($_REQUEST['debug'])) prn($this->parents);
        return $this->parents;
    }

    //------------------------ get children - end -----------------------------
    //------------------------ get children - begin -----------------------------
    function get_children($condition='') {
        $cond = '';
        if (strlen($condition) > 0)
            $cond = " AND " . str_replace($this->name_table, 'ch', $condition);
        $cond.=$this->sql_where('ch');

        $query = "SELECT ch.*
             FROM {$this->name_table} AS ch
             WHERE {$this->info[$this->name_start]}<ch.{$this->name_start}
                 AND ch.{$this->name_finish}<{$this->info[$this->name_finish]}
                 AND ch.{$this->name_deep}=" . ($this->info[$this->name_deep] + 1) . "
                 {$cond}
             ORDER BY ch.{$this->name_start}";
        //if(isset($_REQUEST['debug'])) prn(checkStr($query));
        $this->children = $this->GetRows($this->Execute($this->db, $query));
        //if(isset($_REQUEST['debug'])) prn($this->children);
        return $this->children;
    }

    //------------------------ get children - end -----------------------------
    //------------------------ get node info - begin ----------------------------
    function load_node($_id=0, $condition='') {
        $cond = '';
        if (strlen($condition) > 0)
            $cond = " AND " . $condition;


        $cid = (int) $_id;

        if ($cid > 0) {
            $query = "SELECT * FROM {$this->name_table} WHERE {$this->name_id}=$cid {$cond} " . $this->sql_where();
            //prn($query);
            $this->info = $this->GetOneRow($this->Execute($this->db, $query));
        }
        else
            $this->info = false;

        if (!$this->info) {
            $query = "SELECT * FROM {$this->name_table} WHERE {$this->name_start}=0 " . $this->sql_where();
            //prn($query);
            $this->info = $this->GetOneRow($this->Execute($this->db, $query));
        }

        if ($this->info)
            $this->id = $this->info[$this->name_id]; else
            $this->id = 0;

        return $this->info;
    }

    //------------------------ get node info - end ------------------------------
    //----------------------- database interface -- begin -----------------------
    // MySQL functions
    //
    function DbStr($ffff) {
        return mysql_escape_string($ffff);
    }

    function Execute($dblink, $query) {
        if ($this->debug)
            $this->prn("<b><font color=\"red\">$query</font></b>"); $result_id = mysql_query(trim($query), $dblink);
        if (!$result_id) {
            $this->prn($query . '<br>' . mysql_error());
        } return $result_id;
    }

    function GetRows($result_id) {
        $tor = Array();
        while ($row = mysql_fetch_array($result_id, MYSQL_ASSOC))
            $tor[] = $row; mysql_free_result($result_id);
        return $tor;
    }

    function GetOneRow($result_id) {
        return mysql_fetch_array($result_id, MYSQL_ASSOC);
    }

    function SelectLimit($dblink, $query, $start, $rows) {
        $limit_query = ereg_replace(';?( |' . "\n" . '|' . "\r" . ')*$', '', $query . '  LIMIT ' . checkInt($start) . ',' . checkInt($rows) . ';');
        return GetRows(Execute($dblink, $limit_query));
    }

    function GetNumRows($result_id) {
        return mysql_num_rows($result_id);
    }

    function GetAssociatedArray($result_id) {
        $tor = Array();
        $tmp = GetRows($result_id);
        if (!isset($tmp[0]['id']))
            return $tor; foreach ($tmp as $tm)
            $tor[$tm['id']] = $tm['val']; return $tor;
    }

    //----------------------- database interface -- end -------------------------
    //------------------------- print debug info -- begin -----------------------
    function prn() {
        echo "\n<hr color=lime size=2px>\n";
        $arg_list = func_get_args();
        foreach ($arg_list as $ppp) {
            echo "<pre>\n";
            print_r($ppp);
            echo "</pre>\n";
        } echo "\n<hr color=lime size=2px>\n";
    }

    //------------------------- print debug info -- end -------------------------
    # add restrictions to nodes
    function sql_where($table_alias='') {
        if (count($this->where) == 0)
            return '';
        if (strlen($table_alias) == 0)
            return ' AND ' . join(' AND ', $this->where);
        return str_replace($this->name_table
                , $table_alias
                , ' AND ' . join(' AND ', $this->where)
        );
    }

}

?>
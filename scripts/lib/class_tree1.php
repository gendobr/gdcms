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
            $destination_info = db_getonerow($query);
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
        db_execute('BEGIN;');
        // ---------------------- prepare new place - begin -------------------------
        $shift = $this->info['finish'] - $this->info['start'] + 1;
        $query = "UPDATE {$this->name_table} "
               . "SET {$this->name_start}={$this->name_start}+({$shift}) "
               . "WHERE {$this->name_start}>{$destination_info[$this->name_start]}";
        #prn('prepare new place',htmlencode($query));
        db_execute($query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));

        $query = "UPDATE {$this->name_table} "
               . "SET {$this->name_finish}={$this->name_finish}+({$shift}) "
               . "WHERE {$this->name_finish}>{$destination_info[$this->name_start]}";
        #prn('prepare new place',htmlencode($query));
        db_execute($query);
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
        db_execute($query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));
        // ---------------------- move - end ----------------------------------------
        // ---------------------- clear previous place - begin ----------------------
        $query = "UPDATE {$this->name_table}
                  SET {$this->name_finish}={$this->name_finish}-({$shift})
                  WHERE {$this->name_finish}>{$this->info[$this->name_finish]}";
        #prn(htmlencode($query));
        db_execute($query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));

        $query = "UPDATE {$this->name_table}
                  SET {$this->name_start}={$this->name_start}-({$shift})
                  WHERE {$this->name_start}>{$this->info[$this->name_finish]}";
        #prn(htmlencode($query));
        db_execute($query);
        #prn(GetRows(Execute($this->db,"select category_id, start, finish from dl_category order by start;")));
        // ---------------------- clear previous place - end ------------------------
        db_execute('COMMIT;');
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
        $category_info = db_getonerow("SELECT * FROM {$this->name_table} WHERE {$this->name_id}={$cid} " . $this->sql_where() );
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
        $parent_info = db_getonerow($query);
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
        $children = db_getrows($query);

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

        db_execute('BEGIN;');
        # prn('$sibling',$sibling);

        $dt = $sibling[$this->name_finish] - $category_info[$this->name_finish];
        $query ="UPDATE {$this->name_table}
                 SET $this->name_start=-$this->name_start
                    ,{$this->name_finish}=-{$this->name_finish}
                 WHERE {$sibling[$this->name_start]}<={$this->name_start}
                    AND {$this->name_finish}<={$sibling[$this->name_finish]}
                    " . $this->sql_where();
        # prn($query);
        db_execute($query);

        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}={$this->name_start}+{$dt},
                     {$this->name_finish}={$this->name_finish}+{$dt}
                 WHERE {$category_info[$this->name_start]}<={$this->name_start}
                   AND {$this->name_finish}<={$category_info[$this->name_finish]}
                   " . $this->sql_where();
        # prn($query);
        db_execute($query);

        $dt = -($category_info[$this->name_start] + $dt - $sibling[$this->name_finish] - 1);
        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}=abs({$this->name_start}+({$dt}))
                   , {$this->name_finish}=abs({$this->name_finish}+({$dt}))
                 WHERE {$this->name_start}<0 AND {$this->name_finish}<0
                 " . $this->sql_where();
        # prn($query);
        db_execute($query);


        db_execute('COMMIT;');
    }

    # ---------------------- move down - end ------------------------------------
    # ---------------------- move up - begin ------------------------------------

    function move_up($category_id) {
        if ($category_id <= 0)
            $cid = (int) $this->id; else
            $cid=(int) $category_id;

        # get category info
        $category_info = db_getonerow("SELECT * FROM {$this->name_table} WHERE {$this->name_id}={$cid} " . $this->sql_where());
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
        $parent_info = db_getonerow($query);
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
        $children = array_reverse(db_getrows($query));

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
        db_execute('BEGIN;');
        $dt = $category_info[$this->name_start] - $sibling[$this->name_start];
        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}=-{$this->name_start}
                   , {$this->name_finish}=-{$this->name_finish}
                 WHERE {$sibling[$this->name_start]}<={$this->name_start}
                   AND {$this->name_finish}<={$sibling[$this->name_finish]}
                   " . $this->sql_where();
        //prn($query);
        db_execute($query);

        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}={$this->name_start}-{$dt},
                     {$this->name_finish}={$this->name_finish}-{$dt}
                 WHERE {$category_info[$this->name_start]}<={$this->name_start}
                   AND {$this->name_finish}<={$category_info[$this->name_finish]}
                   " . $this->sql_where();
        //prn($query);
        db_execute($query);

        $dt = $category_info[$this->name_finish] - $dt + 1 - $sibling['start'];
        $query ="UPDATE {$this->name_table}
                 SET {$this->name_start}=abs({$this->name_start}-{$dt})
                   , {$this->name_finish}=abs({$this->name_finish}-{$dt})
                 WHERE {$this->name_start}<0 AND {$this->name_finish}<0
                 " . $this->sql_where();
        //prn($query);
        db_execute($query);
        db_execute('COMMIT;');
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
            $_info = db_getonerow($query);
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
        $deleted_ids = db_getrows($query);
        $cnt = count($deleted_ids);
        for ($i = 0; $i < $cnt; $i++)
            $deleted_ids[$i] = $deleted_ids[$i]['id'];
        //prn($query,$deleted_ids);
        // ----------------------- get deleted ids - end ----------------------------
        db_execute('BEGIN;');
        // ----------------------- delete branch - begin ----------------------------
        $query = "DELETE FROM {$this->name_table}
              WHERE {$_info[$this->name_start]}<={$this->name_start}
                AND {$this->name_finish}<={$_info[$this->name_finish]}
                " . $this->sql_where();
        //prn($query);
        db_execute($query);
        // ----------------------- delete branch - end ------------------------------
        // ----------------------- update start fields - begin ----------------------
        $diff = $_info[$this->name_finish] - $_info[$this->name_start] + 1;
        $query = "UPDATE {$this->name_table}
              SET {$this->name_start}={$this->name_start}-{$diff}
              WHERE {$this->name_start}>{$_info[$this->name_start]}
                  " . $this->sql_where();
        //prn($query);
        db_execute($query);
        // ----------------------- update start fields - end ------------------------
        // ----------------------- update finish fields - begin ---------------------
        $query = "UPDATE {$this->name_table}
              SET {$this->name_finish}={$this->name_finish}-{$diff}
              WHERE {$this->name_finish}>{$_info[$this->name_start]}
                  " . $this->sql_where();
        //prn($query);
        db_execute($query);
        // ----------------------- update finish fields - end -----------------------
        db_execute('COMMIT;');
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
            $_info = db_getonerow($query);
        }
        else
            $_info=false;
        if (!$_info)
            return false;

        db_execute('BEGIN;');
        $query = "UPDATE {$this->name_table}
              SET {$this->name_finish}={$this->name_finish}+2
              WHERE {$this->name_finish}>={$_info[$this->name_finish]}
                   " . $this->sql_where();
        // prn($query);
        db_execute($query);

        $query = "UPDATE {$this->name_table}
              SET {$this->name_start} ={$this->name_start}+2
              WHERE {$this->name_start}>={$_info[$this->name_finish]}
                   " . $this->sql_where();
        // prn($query);
        db_execute($query);

        $new_start = $_info[$this->name_finish];
        $new_finish = $_info[$this->name_finish] + 1;
        $new_deep = $_info[$this->name_deep] + 1;
        $query = "INSERT INTO {$this->name_table}(start, finish, deep)
                    values( $new_start, $new_finish, {$new_deep} );";
        // prn($query);
        db_execute($query);
        db_execute('COMMIT;');

        $query = "SELECT LAST_INSERT_ID() AS newid;";
        $newid = db_getonerow($query);

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
        $this->parents = db_getrows($query);
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
        $this->children = db_getrows($query);
        //if(isset($_REQUEST['debug'])) prn($this->children);
        return $this->children;
    }

    //------------------------ get children - end -----------------------------
    //------------------------ get node info - begin ----------------------------
    function load_node($_id=0, $condition='') {
        $cond = '';
        if (strlen($condition) > 0) {
            $cond = " AND " . $condition;
        }


        $cid = (int) $_id;

        if ($cid > 0) {
            $query = "SELECT * FROM {$this->name_table} WHERE {$this->name_id}=$cid {$cond} " . $this->sql_where();
            //prn($query);
            $this->info = db_getonerow($query);
        } else {
            $this->info = false;
        }

        if (!$this->info) {
            $query = "SELECT * FROM {$this->name_table} WHERE {$this->name_start}=0 " . $this->sql_where();
            //prn($query);
            $this->info = db_getonerow($query);
        }

        if ($this->info) {
            $this->id = $this->info[$this->name_id];
        } else {
            $this->id = 0;
        }

        return $this->info;
    }

    //------------------------ get node info - end ------------------------------
    //----------------------- database interface -- begin -----------------------
    // MySQL functions
    //



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
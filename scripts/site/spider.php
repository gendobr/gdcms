<?php
/**
   The spider to index pages

*/

run('lib/search_functions');

$GLOBALS['main_template_name']='';
define('max_spider_trials',5);

echo "
<html>
   <head>
     <meta http-equiv=\"Refresh\" content=\"30;URL=index.php?action=site/spider\">
   </head>
<body>
<a href=index.php?action=site/spider>next</a><br>
";


# ----------- check if all sites are taken into account - begin ----------------
  $query="insert into {$table_prefix}site_search(site_id, url, date_indexed)
          SELECT DISTINCT s.id, s.url, now() AS date_indexed
          FROM  {$table_prefix}site AS s
                LEFT JOIN  {$table_prefix}site_search AS ss
                ON (s.id=ss.site_id)
          WHERE ss.site_id is null AND s.is_search_enabled=1";

  db_execute($query);
# ----------- check if all sites are taken into account - end ------------------
//die('111');




# ------------------------- get url to index - begin ---------------------------
# $query="SELECT * FROM {$table_prefix}site_search WHERE is_valid>0 AND date_indexed< DATE_ADD(NOW(),INTERVAL -7 DAY) ORDER BY date_indexed ASC LIMIT 0,1";
  $query="SELECT *
          FROM {$table_prefix}site_search
          WHERE is_valid>0
          ORDER BY date_indexed ASC LIMIT 0,100";
  $this_url_info=db_getrows($query);
  $max=count($this_url_info);
  $this_url_info=$this_url_info[rand(0, $max)];

  prn($this_url_info); ob_flush();
  if(!$this_url_info){ echo 'nothing to index'; return ''; }
# ------------------------- get url to index - end -----------------------------

# ------------------------- get site info - begin ------------------------------
  $this_site_info=db_getonerow(
    "SELECT *
     FROM {$table_prefix}site
     WHERE is_search_enabled=1 AND id=".( (int)$this_url_info['site_id'] ));
  //prn($this_site_info);
  if(!$this_site_info)
  {
    $query="DELETE FROM {$table_prefix}site_search WHERE id=".( (int)$this_url_info['id'] );
    db_execute($query);
    return '';
  }
# ------------------------- get site info - end --------------------------------

# check if the URL is valid
  if(!is_searchable($this_url_info['url'], $this_site_info)){
    $query="DELETE FROM {$table_prefix}site_search WHERE id=".( (int)$this_url_info['id'] );
    db_execute($query);
    return '';
  }
# prn($this_site_info);


# ------------------------- load and parse page - begin ------------------------
# $url='http://127.0.0.1/cms/index.php?action=site/map/view&site_id=1&lang=rus';
# $url='http://127.0.0.1/www.zsu.edu.ua/contacts';
  $to_index=index_url($this_url_info['url']);
# prn($to_index);
# ------------------------- load and parse page - end --------------------------

# ------------------------- check content-type - begin -------------------------
if(substr_compare($to_index['headers']['content-type'], "text", 0, 4)!=0)
{
    $to_index['is_successful']=false;
}
//prn($to_index);
# ------------------------- check content-type - end ---------------------------

# ------------------------- update  search index - begin -----------------------
  if($to_index['is_successful']==1)
  {# target is valid

    $checksum=md5($to_index['words']);
    // check if checksum already exists
    if(db_getonerow("SELECT id FROM {$table_prefix}site_search WHERE is_valid=1 and checksum='{$checksum}'"))
    {
        $query="UPDATE {$table_prefix}site_search
                SET date_indexed=NOW(),is_valid=0,checksum='{$checksum}'
                WHERE id=".( (int)$this_url_info['id'] );
        db_execute($query);
    }
    else
    {
        $query="UPDATE {$table_prefix}site_search
                SET date_indexed=NOW(),
                    is_valid=".max_spider_trials.",
                    size={$to_index['size']},
                    title='".DbStr($to_index['title'])."',
                    words='".DbStr($to_index['words'])."',
                    checksum='{$checksum}'
                WHERE id=".( (int)$this_url_info['id'] );
        //prn($query);
        db_execute($query);
    }

  }
  else
  { # target is invalid
    $query="UPDATE {$table_prefix}site_search
            SET date_indexed=NOW(),is_valid=is_valid-1
            WHERE id=".( (int)$this_url_info['id'] );
    //prn($query);
    db_execute($query);
  }
# ------------------------- update  search index - end -------------------------

# ------------------------- validate URLs - begin ------------------------------
  $cnt=array_keys($to_index['links']);
  foreach($cnt as $i)
  {
    //prn($i,$to_index['links'][$i],is_valid_url($to_index['links'][$i]));
    if(!is_searchable($to_index['links'][$i], $this_site_info))
    {
      unset($to_index['links'][$i]);
    }
  }
  $to_index['links']=array_values($to_index['links']);
# prn($to_index['links']);
# ------------------------- validate URLs - end --------------------------------

# ------------------------- insert new URLs into - begin -----------------------
  $cnt=array_keys($to_index['links']);
  foreach($cnt as $i)
  {
    $query="SELECT id FROM {$table_prefix}site_search WHERE url='".DbStr($to_index['links'][$i])."'";
    #prn($query);
    if(!db_getonerow($query))
    {
      $query="insert into {$table_prefix}site_search(site_id, url, date_indexed)
              values({$this_site_info['id']},'".DbStr($to_index['links'][$i])."','2001-01-01 00:00:00')";
      #prn($query);
      db_execute($query);
    }
  }
# ------------------------- insert new URLs into - end -------------------------

# ---------------------- delete invalid URLs from table - begin ----------------
  //$query="DELETE FROM {$table_prefix}site_search WHERE is_valid<=0";
  //db_execute($query);
# ---------------------- delete invalid URLs from table - end ------------------


prn($to_index);

echo "
</body>
</html>
";
return false;
?>
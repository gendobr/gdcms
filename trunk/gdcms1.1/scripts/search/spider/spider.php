<?php



# ----------- check if all sites are taken into account - begin ----------------

$getSiteId=function($el){return $el['site_id'];};

$query="SELECT s.id site_id FROM  {$table_prefix}site AS s WHERE s.is_search_enabled=1";
$site_ids= sort(array_map($getSiteId,db_getrows($query)));
$site_ids[]=0;

$query="SELECT DISTINCT site_id FROM {$table_prefix}search_index WHERE site_id IN (".join(',',$site_ids).");";
$real_site_ids=sort(array_map($getSiteId,db_getrows($query)));

$to_add=array_diff($site_ids, $real_site_ids);

if(count($to_add)>0){
    $query = "INSERT INTO {$table_prefix}search_index(site_id, url, date_indexed)
              SELECT DISTINCT s.id, s.url, now() AS date_indexed
              FROM  {$table_prefix}site AS s
              WHERE ss.site_id IN (".join(',',$to_add).")";
    db_execute($query);    
}
# ----------- check if all sites are taken into account - end ------------------




# ------------------------- get url to index - begin ---------------------------

$query="SELECT @date1:=MIN(date_indexed) AS md FROM {$table_prefix}site_search;";
db_execute($query);

$query="SELECT * FROM {$table_prefix}search_index WHERE date_indexed=@date1 LIMIT 0,100;";
$this_url_info = db_getrows($query);

$max = count($this_url_info);
if($max > 0){
    $this_url_info = $this_url_info[rand(0, $max - 1)];
}

if($this_url_info['is_valid'] || rand(0, 1000)>998 ){

    $query="UPDATE {$table_prefix}search_index SET date_indexed=now(), is_valid=5 WHERE id={$this_url_info['id']}";
    db_execute($query);
    
    // index URL
    # ------------------------- get site info - begin --------------------------
      $this_site_info=db_getonerow("SELECT * FROM {$table_prefix}site WHERE is_search_enabled=1 AND id=".( (int)$this_url_info['site_id'] ));
      //prn($this_site_info);
      if(!$this_site_info) {
        $query="DELETE FROM {$table_prefix}site_search WHERE id=".( (int)$this_url_info['id'] );
        db_execute($query);
        return '';
      }
    # ------------------------- get site info - end ----------------------------
    # 
    # 
    # 
    run('search/spider/functions');
    # check if the URL is valid
      if(!is_searchable($this_url_info['url'], $this_site_info)){
        $query="DELETE FROM {$table_prefix}site_search WHERE id=".( (int)$this_url_info['id'] );
        db_execute($query);
        return '';
      }
    # prn($this_site_info);


    // ======= downloading one url = begin =====================================
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL,$url); // set url to post to 
    curl_setopt($ch, CURLOPT_FAILONERROR, 1); 
    //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects 
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable 
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // times out after 20s 
    curl_setopt($ch, CURLOPT_USERAGENT, "User-Agent:Mozilla/5.0 (X11; Linux i686) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.111 Safari/537.36");
    
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    
    // curl_setopt($ch, CURLOPT_POST, 1); // set POST method 
    //curl_setopt($ch, CURLOPT_POSTFIELDS, "url=index%3Dbooks&field-keywords=PHP+MYSQL"); // add POST fields 
    $body = curl_exec($ch); // run the whole process 
    //echo curl_error ($ch ).'<br>';
    curl_close($ch);   
    //echo $body; exit();
    // ======= downloading one url = end =======================================
      
      
    run('lib/simple_html_dom');
    $html = str_get_html($body);
    if (!$html) {
        $query="UPDATE {$table_prefix}search_index SET date_indexed=now(), is_valid=is_valid-1 WHERE id={$this_url_info['id']}";
        db_execute($query);
        return;
    }
      
      
      
}else{
   $query="UPDATE {$table_prefix}search_index SET date_indexed=now()";
   db_execute($query);
}
# ------------------------- get url to index - end -----------------------------
  
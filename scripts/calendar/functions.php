<?php

if(!defined('calendar_magnitude')){
    define('calendar_magnitude', 549  ); //365
}


function menu_event($_info) {
    //global $text, $db, $table_prefix;
    $tor = Array();
    // $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];

    $tor['calendar/edit'] = Array(
        'URL' => "index.php?action=calendar/edit&event_id={$_info['id']}&site_id={$_info['site_id']}"
        , 'innerHTML' => text('Calendar_edit_event')
        , 'attributes' => ''
    );

    if ($_info['vis'] == 1) {
        $tor['calendar/hide'] = Array(
            'URL' => "index.php?" . preg_query_string("/^event/") . "&event[]={$_info['id']}&event_hide=yes"
            , 'innerHTML' => text('Calendar_hide_event')
            , 'attributes' => ''
        );
    } else {
        $tor['calendar/show'] = Array(
            'URL' => "index.php?" . preg_query_string("/^event/") . "&event[]={$_info['id']}&event_show=yes"
            , 'innerHTML' => text('Calendar_show_event')
            , 'attributes' => ''
        );
    }

    $tor['calendar/delete'] = Array(
        'URL' => "index.php?" . preg_query_string("/^event/") . "&event[]={$_info['id']}&event_delete=yes"
        , 'innerHTML' => text('Calendar_delete_event')
        , 'attributes' => ' style="margin-top:20pt;display:block;" '
    );
    return $tor;
}



// get days of the given event
function event_recache_days($calendar_id){

    $calendar_id=(int)$calendar_id;
    
    
    // get calendar dates
    $query="SELECT * FROM  {$GLOBALS['table_prefix']}calendar_date WHERE calendar_id=".( (int) $calendar_id);
    $dates=db_getrows($query);
    if(count($dates)>0){
        $site_id=$dates[0]['site_id'];
    }else{
        $site_id=0;
    }
    //pochrik      int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  рік початку події                
    //pochmis      int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  місяць  початку події         
    //pochtyzh     int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  день тижня  початку події  
    //pochday      int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  день  початку події             
    //pochgod      int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  година  початку події         
    //pochhv       int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  хвилина  початку події       
    //kinrik       int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  рік кінця події                    
    //kinmis       int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  місяць кінця події              
    //kintyzh      int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  день тижня  кінця події      
    //kinday       int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  день  кінця події                 
    //kingod       int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  година кінця події              
    //kinhv        int(11)  (NULL)     NO              (NULL)                   select,insert,update,references  хвилина  кінця події           
    // prn($dates);exit();
    
    
    
    // dates to check
    $allDays=Array();
    $now = date_create(date('Y-m-d 12:00:00', time()));
    $t = date_add($now, date_interval_create_from_date_string('-'.calendar_magnitude.' days'));
    $intervalSize=2*calendar_magnitude;
    $oneDay = date_interval_create_from_date_string('1 day');
    for ($i = 0; $i < $intervalSize; $i++) {
        $allDays[$i] = array_combine (Array('Y','m','d','w') , explode(',',$t->format("Y,m,d,w")));
        $t->add($oneDay);
    }
    
    //prn($allDays);// exit();
    
    // iterate over dates
    $matchedDays=Array();
    foreach($dates as $dateInterval){
        // get all possible start dates
        $startDates=Array();
        foreach($allDays as $key=>$day){
            $is_matched=
                     ($dateInterval['pochrik']==$day['Y'] || $dateInterval['pochrik']==-1)
                  && ($dateInterval['pochmis']==$day['m'] || $dateInterval['pochmis']==-1)
                  && ($dateInterval['pochtyzh']==$day['w'] || $dateInterval['pochtyzh']==-1)
                  && ($dateInterval['pochday']==$day['d'] || $dateInterval['pochday']==-1);
            if($is_matched){
                $startDates[]=Array(
                    'key'=>$key,
                    'Y'=>$day['Y'],
                    'm'=>$day['m'],
                    'w'=>$day['w'],
                    'd'=>$day['d'],
                    'h'=>$dateInterval['pochgod'],
                    'i'=>$dateInterval['pochhv']
                );
            }
        }
        
        //prn($startDates);// exit();

        
        // for each start date get matching end date
        $endDates=Array();
        foreach($startDates as $dat){
            $endDate=-1;
            // prn('-----------',$dat);
            for ($i = $dat['key']; $i < $intervalSize; $i++){
                
                $day=&$allDays[$i];
                $is_matched=
                         ($dateInterval['kinrik']==$day['Y'] || $dateInterval['kinrik']==-1)
                      && ($dateInterval['kinmis']==$day['m'] || $dateInterval['kinmis']==-1)
                      && ($dateInterval['kintyzh']==$day['w'] || $dateInterval['kintyzh']==-1)
                      && ($dateInterval['kinday']==$day['d'] || $dateInterval['kinday']==-1);
                if($is_matched){
                    $endDate=Array(
                        'key'=>$i,
                        'Y'=>$day['Y'],
                        'm'=>$day['m'],
                        'w'=>$day['w'],
                        'd'=>$day['d'],
                        'h'=>$dateInterval['kingod'],
                        'i'=>$dateInterval['kinhv']
                    );
                    // echo $i.'-OK; ';
                    break;
                }
            }
            if($endDate<0){
                $day=&$allDays[$intervalSize-1];
                $endDate=Array(
                        'key'=>$intervalSize-1,
                        'Y'=>$day['Y'],
                        'm'=>$day['m'],
                        'w'=>$day['w'],
                        'd'=>$day['d'],
                        'h'=>$dateInterval['kingod'],
                        'i'=>$dateInterval['kinhv']
                );
            }
            $endDates[]=$endDate;
            //prn($endDate);
        }
        // prn($allDays[$endDates[0]]);exit();
        // prn($startDates, $endDates);
        // put dates into matchedDays array
        $cnt=count($startDates);
        for($i=0; $i<$cnt; $i++){
            for($k=$startDates[$i]['key']; $k<=$endDates[$i]['key']; $k++){
                $day=&$allDays[$k];
                $matchedDays[$k]=Array(
                        'Y'=>$day['Y'],
                        'm'=>$day['m'],
                        'w'=>$day['w'],
                        'd'=>$day['d'],
                        'h'=>$dateInterval['pochgod'],
                        'i'=>$dateInterval['pochhv'],
                        'h2'=>$dateInterval['kingod'],
                        'i2'=>$dateInterval['kinhv']
                );
            }
        }
    }
    // prn($matchedDays);exit();

    
    // remove old calendar dates
    $query="DELETE FROM {$GLOBALS['table_prefix']}calendar_days_cache WHERE calendar_id=".( (int) $calendar_id);
    db_execute($query);

    // create sql to insert new dates
    $query=Array();
    foreach($matchedDays as $day){
        $query[]="($calendar_id,$site_id,{$day['Y']},{$day['m']},{$day['d']},{$day['h']},{$day['i']},{$day['h2']},{$day['i2']})";
    }
    if(count($query)>0){
        $query="INSERT INTO {$GLOBALS['table_prefix']}calendar_days_cache(calendar_id,site_id,y,m,d, h, i, h2, i2) VALUES ".join(',',$query);
        db_execute($query);
    }
}









function event_get_inside($site_id, $timestamp_start, $timestamp_end, $verbose = false) {
    //$verbose=true;
    if ($verbose) {
        prn("event_get_inside($site_id, $timestamp_start, $timestamp_end, $verbose = false)");
    }

    // ---------- create temporary tables - begin ------------------------------


    $query = "CREATE TEMPORARY TABLE IF NOT EXISTS tt0(
               Y INT(11), m INT(11), d INT(11), w INT(11), 
               H INT(11), i INT(11), t  INT(11),
               KEY `tmst` (`t`), KEY `tmstY` (`y`), KEY `tmstM` (`m`),
               KEY `tmstW` (`w`), KEY `tmstD` (`d`) ) ENGINE=MEMORY";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);

    $query = "DELETE FROM tt0;";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);



    //$query = "DROP TABLE tt1";
    //if ($verbose) { prn($query); }
    //db_execute($query);

    $query = "CREATE TEMPORARY TABLE  IF NOT EXISTS tt1(id INT(11), t  INT(11),KEY `tmst1` (`t`) ) ENGINE=MEMORY";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);

    $query = "delete from tt1";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);


    //$query = "DROP TABLE tt2";
    //if ($verbose) { prn($query); }
    //db_execute($query);

    $query = "CREATE TEMPORARY TABLE  IF NOT EXISTS tt2( id INT(11), t  INT(11),KEY `tmst2` (`t`),KEY `tmst2d` (`id`) ) ENGINE=MEMORY";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);

    $query = "DELETE FROM tt2";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);
    // ---------- create temporary tables - end --------------------------------
    // --------------------- list of dates to check - begin --------------------
    // get exact unix timestamp
    //$time_end = mktime(23, 59, 59, $month+1, 0, $year);
    $day_end = date_create(date('Y-m-d 23:59:59', $timestamp_end));
    $day_end_timestamp = $day_end->getTimestamp();

    $near_dates = Array();
    $t = date_add($day_end, date_interval_create_from_date_string('-365 days'));
    $day_step = date_interval_create_from_date_string('1 day');

    for ($i = 0; $i < 731; $i++) {
        $near_dates[] = "(" . $t->format("Y,m,d,w,H,i") . "," . $t->getTimestamp() . ")";
        $t->add($day_step);
    }

    // put near dates
    $query = "insert into tt0(y, m, d, w, H, i, t) values " . join(',', $near_dates);
    if ($verbose) {
        prn($query);
    }
    db_execute($query);
    // prn(db_getrows("SELECT * FROM t0"));
    // --------------------- list of dates to check - end ----------------------
    // events which
    //    - attached to site_id
    //    - 
    // for each event
    // select dates from tt0 
    // - which match start date pattern
    // - are less than current date
    // - maximal one
    $query = "insert into tt1(id, t)
              SELECT e.id, max(t0.t) as maxdate
              FROM  {$GLOBALS['table_prefix']}calendar_date as e, tt0 t0
              WHERE e.site_id=$site_id
                 and t0.t<={$day_end_timestamp}
                 and (e.pochrik=t0.y  OR e.pochrik=-1)
                 and (e.pochmis=t0.m  OR e.pochmis=-1)
                 and (e.pochday=t0.d  OR e.pochday=-1)
                 and (e.pochtyzh=t0.w OR e.pochtyzh=-1)
              group by e.id
              ";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);
    if ($verbose) {
        prn(db_getrows("SELECT * FROM tt1"));
    }

    // debug output: nearest start dates in the past
    if ($verbose) {
        $tor = db_getrows("select * from tt1");
        $cnt = count($tor);
        for ($i = 0; $i < $cnt; $i++) {
            $tor[$i]['date-begin'] = date('Y-m-d H:i:s', $tor[$i]['t']);
        }
        prn('date-begin', $tor);
    }


    // get maximal time for current date i.e. end of month
    //$time_start = mktime(0, 0, 1, $month, 1, $year);
    $day_start = date_create(date('Y-m-d 00:00:01', $timestamp_start));
    $day_start_timestamp = $day_start->getTimestamp();

    // for each event 
    //-- get dates from t0 which match event_end_date
    $query = "
        INSERT INTO tt2(id, t)
                SELECT e.id, t0.t AS kindate
                FROM {$GLOBALS['table_prefix']}calendar_date AS e, tt0 t0
                WHERE (e.kinrik=t0.y  OR e.kinrik=-1)
                  AND (e.kinmis=t0.m  OR e.kinmis=-1)
                  AND (e.kinday=t0.d  OR e.kinday=-1)
                  AND (e.kintyzh=t0.w OR e.kintyzh=-1)
        ";
    if ($verbose) {
        prn($query);
    }
    db_execute($query);



    // - get dates from t0 which match event_end_date
    //   and are greater than nearest event_start_date (saved in t1)
    //   and then select minimal one
    //   and then ensure that end date is greater or equal than current date 

    $query = "SELECT e.*, t2.id, t1.t AS pochdate , MIN(t2.t) AS kindate
    FROM tt2 t2, tt1 t1, {$GLOBALS['table_prefix']}calendar_date AS e 
    WHERE t2.id=t1.id
      AND e.site_id=$site_id
      AND t2.id=e.id
      AND t1.t<=t2.t
    GROUP BY t2.id 
    HAVING kindate>={$day_start_timestamp}";
    //    $query = "
    //        select e.*, t1.t as pochdate, min(t0.t) as kindate
    //        from {$GLOBALS['table_prefix']}calendar_date as e, tt0 t0, tt1 t1
    //        where t1.id=e.id
    //          and t1.t<=t0.t
    //          and (e.kinrik=t0.y  OR e.kinrik=-1)
    //          and (e.kinmis=t0.m  OR e.kinmis=-1)
    //          and (e.kinday=t0.d  OR e.kinday=-1)
    //          and (e.kintyzh=t0.w OR e.kintyzh=-1)
    //        group by e.id
    //        having kindate>={$day_start_timestamp}
    //        order by pochdate asc";
    if ($verbose) {
        prn($query);
    }
    $tor = db_getrows($query);
    //prn($tor);
    $ids = Array();
    foreach ($tor as $to) {
        $ids[] = (int) $to['calendar_id'];
    }
    return $ids;
}

function calendar_dni() {
    $tor = Array();
    for ($i = -1; $i < 32; $i++) {
        $tor[$i] = substr('00' . $i, -2);
    }
    $tor[-1] = '--';
    unset($tor[0]);
    return $tor;
}

function calendar_misyaci() {
    return Array(-1 => '--',
        1 => text('month_January'),
        2 => text('month_February'),
        3 => text('month_March'),
        4 => text('month_April'),
        5 => text('month_May'),
        6 => text('month_June'),
        7 => text('month_July'),
        8 => text('month_August'),
        9 => text('month_September'),
        10 => text('month_October'),
        11 => text('month_November'),
        12 => text('month_December'));
}
function calendar_month_and_day($m, $d) {
    switch($m){
        case  1: return str_replace('{d}',$d,text('monthday_January')); break;
        case  2: return str_replace('{d}',$d,text('monthday_February')); break;
        case  3: return str_replace('{d}',$d,text('monthday_March')); break;
        case  4: return str_replace('{d}',$d,text('monthday_April')); break;
        case  5: return str_replace('{d}',$d,text('monthday_May')); break;
        case  6: return str_replace('{d}',$d,text('monthday_June')); break;
        case  7: return str_replace('{d}',$d,text('monthday_July')); break;
        case  8: return str_replace('{d}',$d,text('monthday_August')); break;
        case  9: return str_replace('{d}',$d,text('monthday_September')); break;
        case 10: return str_replace('{d}',$d,text('monthday_October')); break;
        case 11: return str_replace('{d}',$d,text('monthday_November')); break;
        case 12: return str_replace('{d}',$d,text('monthday_December')); break;
    }
    return "$m/$d";
}


function calendar_dnityzhnya() {
    return Array(-1 => '--',
        0 => text('weekday_short_sunday'),
        1 => text('weekday_short_monday'),
        2 => text('weekday_short_tuesday'),
        3 => text('weekday_short_wednesday'),
        4 => text('weekday_short_thursday'),
        5 => text('weekday_short_friday'),
        6 => text('weekday_short_saturday'));
}

function calendar_hours() {
    $tor = Array();
    for ($i = -1; $i < 24; $i++) {
        $tor[$i] = substr('00' . $i, -2);
    }
    $tor[-1] = '--';
    return $tor;
}

function calendar_minutes() {
    $tor = Array();
    for ($i = -1; $i < 60; $i++) {
        $tor[$i] = substr('00' . $i, -2);
    }
    $tor[-1] = '--';
    return $tor;
}

/**
 * Number of events for a given date
 */
//function events_exist($year, $month, $day, $site_info) {
//    static $cached_data;
//
//    if (!$cached_data) {
//        $uid = $month + 100 * $year + 1000000 * $site_info['id'];
//        $min_valid_date = date("Y-m-d H:i:s", time() - 3600);
//        $tmp = db_getonerow("SELECT * FROM {$GLOBALS['table_prefix']}calendar_cache WHERE uid=$uid and updated>'$min_valid_date'");
//        if ($tmp) {
//            $cached_data = explode(',', $tmp['days']);
//        } else {
//            $cached_data = Array();
//            $first_timestamp = mktime($hour = 12, $minute = 00, $second = 00, $month, $day = 1, $year);
//            $dt = 24 * 3600; // 1 day
//            for ($i = 0; $i < 32; ++$i) {
//                $timestamp = $first_timestamp + $i * $dt;
//                if (date('m', $timestamp) - $month == 0) {
//                    $d = date('d', $timestamp);
//                    //prn("event_get_by_date({$site_info['id']}, $year, $month, $d);");
//                    //$events = event_get_by_date($site_info['id'], $year, $month, $d);
//
//                    $timestamp_start = mktime(00, 00, 1, $month, $d, $year);
//                    $timestamp_end = mktime(23, 59, 59, $month, $d, $year);
//                    $event_ids = event_get_inside($site_info['id'], $timestamp_start, $timestamp_end, $verbose = isset($input_vars['verbose']));
//
//                    //$events=Array(1);
//                    if (count($event_ids) > 0) {
//                        $cached_data[] = $d;
//                    }
//                }
//            }
//            db_execute("REPLACE {$GLOBALS['table_prefix']}calendar_cache(uid,days,updated) VALUES($uid,'" . join(',', $cached_data) . "' ,now())");
//        }
//    }
//    return in_array($day, $cached_data);
//}

/**
 * 
 */
//function get_nearest_dates($event_info, $n = 10) {
//    $result = Array();
//
//    // create date condition
//    $condition = Array();
//    if ($event_info['pochrik'] > 0) {
//        $condition[0] = $event_info['pochrik'];
//    }
//    // pochmis
//    if ($event_info['pochmis'] > 0) {
//        $condition[1] = $event_info['pochmis'];
//    }
//    // pochday
//    if ($event_info['pochday'] > 0) {
//        $condition[2] = $event_info['pochday'];
//    }
//    // pochtyzh
//    if ($event_info['pochtyzh'] >= 0) {
//        $condition[3] = $event_info['pochtyzh'];
//    }
//    $keys = array_keys($condition);
//    // prn('$condition',$condition);
//
//    for ($i = 0, $time = time(); $i < 370; $i++, $time+=86400) {
//        $date = explode(' ', date('Y m d w', $time));
//        // prn(join(' ',$condition),join(' ',$date));
//        $ok = true;
//        foreach ($keys as $k) {
//            $ok = $ok && ($condition[$k] == $date[$k]);
//        }
//
//        if ($ok) {
//            $result[] = substr('0000' . $date[0], -4) . '-' . substr('0000' . $date[1], -2) . '-' . substr('0000' . $date[2], -2);
//        }
//        if (count($result) >= $n) {
//            break;
//        }
//        // prn(date('Y m d w',$time).' => '.$ok.';');
//    }
//    return $result;
//}

function get_view($event_list, $lang) {

    $cnt = count($event_list);
    if ($cnt == 0) {
        return Array();
    }




    // collect event ids
    $ids = Array(0);
    for ($i = 0; $i < $cnt; $i++) {
        $ids[] = $event_list[$i]['id'];
    }
    // prn($ids);
    // collect event dates
    $query = "select * from {$GLOBALS['table_prefix']}calendar_date where calendar_id in (" . join(',', $ids) . ")";
    $tmp = db_getrows($query);
    $dates = Array();
    foreach ($tmp as $tm) {
        if (!isset($dates[$tm['calendar_id']])) {
            $dates[$tm['calendar_id']] = Array();
        }
        $dates[$tm['calendar_id']][] = $tm;
    }

    $calendar_misyaci = calendar_misyaci();
    $calendar_dnityzhnya = calendar_dnityzhnya();
    $dateFormatter = function($da) use($calendar_misyaci, $calendar_dnityzhnya) {
        $dt = $da;
        $dt['pochrik_text'] = ($dt['pochrik'] > 0) ? $dt['pochrik'] : '';
        $dt['kinrik_text'] = ($dt['kinrik'] > 0) ? $dt['kinrik'] : '';

        $dt['pochmis_text'] = ($dt['pochmis'] > 0) ? $calendar_misyaci[$dt['pochmis']] : '';
        $dt['kinmis_text'] = ($dt['kinmis'] > 0) ? $calendar_misyaci[$dt['kinmis']] : '';

        $dt['pochtyzh_text'] = ($dt['pochtyzh'] > 0) ? $calendar_dnityzhnya[$dt['pochtyzh']] : '';
        $dt['kintyzh_text'] = ($dt['kintyzh'] > 0) ? $calendar_dnityzhnya[$dt['kintyzh']] : '';

        $dt['pochday_text'] = ($dt['pochday'] > 0) ? $dt['pochday'] : '';
        $dt['kinday_text'] = ($dt['kinday'] > 0) ? $dt['kinday'] : '';
        return $dt;
    };
    for ($i = 0; $i < $cnt; $i++) {
        if (isset($dates[$event_list[$i]['id']])) {
            $event_list[$i]['dates'] = array_map($dateFormatter, $dates[$event_list[$i]['id']]);
        } else {
            $event_list[$i]['dates'] = Array();
        }
    }

    $query = "select * from {$GLOBALS['table_prefix']}calendar_category where event_id in (" . join(',', $ids) . ")";
    $categories = db_getrows($query);
    // prn($categories);
    $category_ids = Array(0 => 1);
    $event_category = Array();
    foreach ($categories as $cat) {
        $category_ids[(int) $cat['category_id']] = 1;
        if (!isset($event_category[$cat['event_id']])) {
            $event_category[$cat['event_id']] = Array();
        }
        $event_category[$cat['event_id']][] = $cat['category_id'];
    }
    // prn('$category_ids',$category_ids,'$event_category',$event_category);
    $query = "SELECT * FROM {$GLOBALS['table_prefix']}category WHERE category_id in(" . join(',', array_keys($category_ids)) . ")";
    $tmp = db_getrows($query);
    // prn($query,$tmp);
    if (!function_exists("encode_dir_name")) {
        run('lib/file_functions');
    }

    $ncat = count($tmp);
    $categories = Array();
    $site_id = $event_list[0]['site_id'];
    $category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$lang}&category_id=";
    for ($i = 0; $i < $ncat; $i++) {
        $categories[$tmp[$i]['category_id']]['category_title'] = get_langstring($tmp[$i]['category_title'], $lang);
        $categories[$tmp[$i]['category_id']]['category_code'] = encode_dir_name($tmp[$i]['category_code']);
        $categories[$tmp[$i]['category_id']]['URL'] = $category_url_prefix . $tmp[$i]['category_id'];
        $categories[$tmp[$i]['category_id']]['start'] = $tmp[$i]['start'];
    }
    //prn($categories);

    for ($i = 0; $i < $cnt; $i++) {
        //$event_list[$i]['nearest_dates'] = get_nearest_dates($event_list[$i]);
        $event_list[$i]['categories'] = Array();
        $minstart=false;
        if (isset($event_category[$event_list[$i]['id']])) {
            foreach ($event_category[$event_list[$i]['id']] as $cat_id) {
                $event_list[$i]['categories'][] = $categories[$cat_id];
                if($minstart===false || $minstart<$categories[$cat_id]['start']){
                    $minstart=$categories[$cat_id]['start'];
                }
            }
        }
        $event_list[$i]['category_order']=(int)$minstart;
    }
    //prn($event_list);
    return $event_list;
}

function getMonthTable($year, $month, $this_site_info) {

    $first_timestamp = mktime($hour = 12, $minute = 00, $second = 00, $month, $day = 1, $year);
    $dt = 24 * 3600; // 1 day
    for ($i = 0; $i < 32; ++$i) {
        $timestamps[$i] = $first_timestamp + $i * $dt;
    }
    //prn($timestamps);

    $shift = date('w', $timestamps[0]);
    //prn($shift);
    $days = Array();
    for ($i = 0; $i < $shift; $i++) {
        $days[] = '';
    }
    foreach ($timestamps as $tms) {
        if (date('m', $tms) == $month) {
            $days[] = date('d', $tms);
        }
    }
    //prn($days);
    /*
     */
    $month_names = Array(1 => text('month_January'), 2 => text('month_February'),
        3 => text('month_March'), 4 => text('month_April'),
        5 => text('month_May'), 6 => text('month_June'),
        7 => text('month_July'), 8 => text('month_August'),
        9 => text('month_September'), 10 => text('month_October'),
        11 => text('month_November'), 12 => text('month_December'));

    $weekday_names = Array(-1 => '--',
        0 => text('weekday_short_sunday'),
        1 => text('weekday_short_monday'),
        2 => text('weekday_short_tuesday'),
        3 => text('weekday_short_wednesday'),
        4 => text('weekday_short_thursday'),
        5 => text('weekday_short_friday'),
        6 => text('weekday_short_saturday'));

    $calendar = array_chunk($days, 7);
    // prn($calendar);
    $month_table = Array();

    $month_table['view_day_events_url_template'] = site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month&year={year}&month={month}&day={day}";
    $month_table['other_month_url_template'] = site_root_URL . "/index.php?" . preg_query_string('/action|year|month|day/') . "&action=calendar/month&month={month}&year={year}";

    // draw navigator
    $month_table['next_month_link'] = str_replace(Array('{year}', '{month}'), Array($year, $month + 1), $month_table['other_month_url_template']);
    $month_table['prev_month_link'] = str_replace(Array('{year}', '{month}'), Array($year, $month - 1), $month_table['other_month_url_template']);

    $month_table['next_year_link'] = str_replace(Array('{year}', '{month}'), Array(($year + 1), $month), $month_table['other_month_url_template']);
    $month_table['prev_year_link'] = str_replace(Array('{year}', '{month}'), Array(($year - 1), $month), $month_table['other_month_url_template']);



    $month_table['month_name'] = $month_names[$month];
    $month_table['month'] = $month;
    $month_table['year'] = $year;
    $month_table['weekdays'] = $weekday_names;
    unset($month_table['weekdays'][-1]);



    $month_table['days'] = Array();

    $query="SELECT DISTINCT d FROM {$GLOBALS['table_prefix']}calendar_days_cache WHERE Y=".( (int)$year )." AND m=".( (int) $month)." AND site_id={$this_site_info['id']} ";
    $existing_days=array_flip(array_map(function($in){return $in['d'];},db_getrows($query)));
    
    foreach ($calendar as $row) {
        $tr = Array();
        foreach ($row as $day) {
            if (isset($existing_days[$day])) {
                $view_day_events_url = str_replace(Array('{year}', '{month}', '{day}'), Array($year, $month, $day), $month_table['view_day_events_url_template']);
                $tr[] = Array('innerHTML' => $day, 'href' => $view_day_events_url, 'year' => $year, 'month' => $month, 'day' => $day);
            } else {
                $tr[] = Array('innerHTML' => $day, 'href' => '', 'year' => $year, 'month' => $month, 'day' => $day);
            }
        }
        for ($i = count($row); $i < 7; $i++) {
            $tr[] = Array('innerHTML' => '', 'href' => '');
        }
        $month_table['days'][] = $tr;
    }

    return $month_table;
}




class CategoryEvents2{
    protected $lang, $this_site_info, $category_info, $start;
    protected $_list, $_pages, $items_found;
    protected $rows_per_page = 10;
    private $subordering;
    
    // 
    function __construct($_lang, $_this_site_info, $_category_info, $start, $input_vars) {
        $this->lang = $_lang;
        $this->this_site_info = $_this_site_info;
        $this->category_info = $_category_info;
        $this->start = $start;
        $this->ordering = 'y ASC, m ASC, d ASC, h ASC, i ASC';

        if (!function_exists("menu_category")) {
            run('category/functions');
        }

        // ??????
        $this->startname = 'event_start';


        if (isset($input_vars['year'])) {
            $this->year = (int) $input_vars['year'];
        }

        if (isset($input_vars['month'])) {
            $this->month = (int) $input_vars['month'];
            if (!isset($this->year)) {
                $this->year = (int) date('Y');
            }
        }

        if (isset($input_vars['day'])) {
            $this->day = (int) $input_vars['day'];
            if (!isset($this->month)) {
                $this->month = (int) date('m');
            }
            if (!isset($this->year)) {
                $this->year = (int) date('Y');
            }
        }
        //$this->init();
        $this->createDateSelector();
    }

    
    public function createDateSelector(){
        // ------------- date selector links - begin ---------------------------
        $this->dateselector = new stdClass();
        $this->dateselector->parents = Array();
        $this->dateselector->current = Array();
        $this->dateselector->children = Array();

        if (isset($this->day)) {
            $month_names = calendar_misyaci();
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}"
                , 'innerHTML' => $this->year
            );
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$this->month}"
                , 'innerHTML' => $month_names[$this->month]
            );
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->day
            );
        } elseif (isset($this->month)) {

            $month_names = calendar_misyaci();

            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}"
                , 'innerHTML' => $this->year
            );
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $month_names[$this->month]
            );

            $timestamp_start = mktime(12, 0, 0, $this->month, 1, $this->year);
            $timestamp_end = mktime(12, 0, 0, $this->month + 1, 0, $this->year);
            for ($i = $timestamp_start; $i <= $timestamp_end; $i+=86400) { // 86400 = seconds in day
                $day = date('d', $i);
                $this->dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$this->month}&day=" . $day// 
                    , 'innerHTML' => $day
                );
            }
        } elseif (isset($this->year)) {
            $month_names = calendar_misyaci();
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->year
            );
            for ($i = 1; $i <= 12; $i++) {
                $this->dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$i}"// 
                    , 'innerHTML' => $month_names[$i]
                );
            }
        } else {
            $current_year = (int) date('Y');
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => text('All_dates')
            );
            for ($i = -1; $i <= 1; $i++) {
                $this->dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year=" . ($current_year + $i)// 
                    , 'innerHTML' => ($current_year + $i)
                );
            }
        }
        // ------------- date selector links - end ---------------------------
    }

    function get_paging_links($records_found, $start, $rows_per_page) {

        $url_prefix = site_URL . '?' . preg_query_string("/" . $this->startname . "|" . session_name() . "/") . "&{$this->startname}=";

        $pages = Array();
        $imin = max(0, $start - 10 * $rows_per_page);
        $imax = min($records_found, $start + 10 * $rows_per_page);
        if ($imin > 0) {
            $pages[] = Array(
                'URL' => $url_prefix . '0',
                'innerHTML' => '[1]'
            );
            $pages[] = Array('URL' => '', 'innerHTML' => '...');
        }

        for ($i = $imin; $i < $imax; $i = $i + $rows_per_page) {
            if ($i == $start) {
                $pages[] = Array('URL' => '', 'innerHTML' => '<b>[' . (1 + $i / $rows_per_page) . ']</b>');
            } else {
                $pages[] = Array('URL' => $url_prefix . $i, 'innerHTML' => ( 1 + $i / $rows_per_page));
            }
        }

        if ($imax < $records_found) {
            $last_page = floor(($records_found - 1) / $rows_per_page);
            if ($last_page > 0) {
                $pages[] = Array('URL' => '', 'innerHTML' => "...");
                $pages[] = Array(
                    'URL' => $url_prefix . ($last_page * $rows_per_page)
                    , 'innerHTML' => "[" . ($last_page + 1) . "]"
                );
            }
        }
        return $pages;
    }    
    
    public function setRowsPerPage($val) {
        $this->rows_per_page = (int) $val;
        unset($this->_list);
        return '';
    }

    public function setCategoryId($val) {
        $category_id = (int) $val;
        $this->category_info = category_info(Array(
            'category_id' => $category_id,
            'site_id' => $this->this_site_info['id'],
            'lang' => $this->lang
        ));
        unset($this->_list);
        return '';
    }
    
    public function setOrdering($val) {
        
        $this->subordering=false;
        $tmp=explode(',',$val);
        //prn($val,$tmp);
        $ordering=Array();
        for($i=0, $cnt=count($tmp); $i<$cnt; $i++){
            $tmp[$i]=preg_split('/ +/',trim($tmp[$i]));
            $tmp[$i][1]=(isset($tmp[$i][1]) && strtoupper($tmp[$i][1])=='DESC')?'DESC':'ASC';
            $tmp[$i][0]=trim(strtolower($tmp[$i][0]));
            switch ($tmp[$i][0]){
                case 'year':
                case 'y':
                    $ordering[]='y '.$tmp[$i][1];
                    break;
                case 'month':
                case 'm':
                    $ordering[]='m '.$tmp[$i][1];
                    break;
                case 'day':
                case 'd':
                    $ordering[]='d '.$tmp[$i][1];
                    break;
                case 'hour':
                case 'hours':
                case 'h':
                    $ordering[]='h '.$tmp[$i][1];
                    break;
                case 'minute':
                case 'minutes':
                case 'i':
                    $ordering[]='i '.$tmp[$i][1];
                    break;
                //case 'title':
                //case 'name':
                //    $ordering[]='nazva '.$tmp[$i][1];
                //    break;
                case 'category':
                    $ordering[]='category_start '.$tmp[$i][1];
                    break;
            }
        }
        //prn($ordering);
        $this->ordering=join(',', $ordering);
        unset($this->_list);
        return '';
    }

    function __get($attr) {

        switch ($attr) {
            case 'list':
                if (!isset($this->_list)) {
                    $this->init();
                }
                return $this->_list;
                break;
            case 'pages':
                if (!isset($this->_list)) {
                    $this->init();
                }
                return $this->_pages;
                break;
            case 'items_found':
                if (!isset($this->_list)) {
                    $this->init();
                }
                return $this->items_found;
                break;
            case 'start':
                if (!isset($this->_list)) {
                    $this->init();
                }
                return $this->start + 1;
                break;
            //case 'dateselector':
                //if (!isset($this->dateselector)) {
                //    $this->init();
                //}
            //    return $this->dateselector;
            //    break;
            case 'finish':
                if (!isset($this->_list)) {
                    $this->init();
                }
                return min($this->start + $this->rows_per_page, $this->items_found);
                break;
            default: return Array();
        }
    }

    public function init() {

        if (!function_exists("menu_category")) {
            run('category/functions');
        }
        //
        $site_id = $this->this_site_info['id'];
        //$category_id = $this->category_info['category_id'];
        // get all the visible children
        $query = "SELECT ch.category_id, BIT_AND(pa.is_visible) as visible
                  FROM {$GLOBALS['table_prefix']}category ch, {$GLOBALS['table_prefix']}category pa
                  WHERE pa.start<=ch.start AND ch.finish<=pa.finish
                    AND {$this->category_info['start']}<=ch.start AND ch.finish<={$this->category_info['finish']}
                    AND pa.site_id=$site_id and ch.site_id=$site_id
                  GROUP BY ch.category_id
                  HAVING visible";
        //prn($query); exit();
        $children = db_getrows($query);
        $cnt = count($children);
        for ($i = 0; $i < $cnt; $i++) {
            $children[$i] = $children[$i]['category_id'];
        }
        // prn(join(',',$children)); exit('####');
        // 
        // ------------ restrict dates - begin ---------------------------------
        if (isset($this->day)) {

            //$query="SELECT SQL_CALC_FOUND_ROWS *
            //        FROM {$GLOBALS['table_prefix']}calendar_days_cache 
            //        WHERE Y={$this->year} AND m={$this->month} AND d={$this->day} AND site_id={$site_id}
            //        ORDER BY {$this->ordering}
            //        LIMIT {$this->start},{$this->rows_per_page};";
            $query="SELECT SQL_CALC_FOUND_ROWS dch.*, MIN(c.start) AS category_start
                    FROM {$GLOBALS['table_prefix']}calendar_days_cache AS dch
                         INNER JOIN {$GLOBALS['table_prefix']}calendar_category cc ON dch.calendar_id=cc.event_id
                         INNER JOIN {$GLOBALS['table_prefix']}category c ON ( cc.category_id=c.category_id AND c.site_id={$site_id} )
                    WHERE Y={$this->year} AND m={$this->month} AND d={$this->day} AND dch.site_id={$site_id}
                    GROUP BY dch.calendar_id,dch.site_id,dch.Y,dch.m,dch.d 
                    ".( $this->ordering ? "ORDER BY {$this->ordering}":'')
                   ." LIMIT {$this->start},{$this->rows_per_page} ";
            //prn($query);
            $event_days= db_getrows($query);
            $event_ids = array_unique(array_map(function($in){return $in['calendar_id'];},$event_days));

            $this->items_found = db_getonerow("SELECT FOUND_ROWS() AS n_records");
            $this->items_found = $this->items_found['n_records'];
            $this->_pages = $this->get_paging_links($this->items_found, $this->start, $this->rows_per_page);

        } elseif (isset($this->month)) {

            //$query="SELECT SQL_CALC_FOUND_ROWS *
            //        FROM {$GLOBALS['table_prefix']}calendar_days_cache 
            //        WHERE Y={$this->year} AND m={$this->month} AND site_id={$site_id}
            //        ORDER BY {$this->ordering}
            //        LIMIT {$this->start},{$this->rows_per_page};";

            $query="SELECT SQL_CALC_FOUND_ROWS dch.*, MIN(c.start) AS category_start
                    FROM {$GLOBALS['table_prefix']}calendar_days_cache AS dch
                         INNER JOIN {$GLOBALS['table_prefix']}calendar_category cc ON dch.calendar_id=cc.event_id
                         INNER JOIN {$GLOBALS['table_prefix']}category c ON ( cc.category_id=c.category_id AND c.site_id={$site_id} )
                    WHERE Y={$this->year} AND m={$this->month} AND dch.site_id={$site_id}
                    GROUP BY dch.calendar_id,dch.site_id,dch.Y,dch.m,dch.d 
                    ".( $this->ordering ? "ORDER BY {$this->ordering}":'')
                   ." LIMIT {$this->start},{$this->rows_per_page} ";
            // prn($query);
            $event_days= db_getrows($query);
            $event_ids = array_unique(array_map(function($in){return $in['calendar_id'];},$event_days));

            $this->items_found = db_getonerow("SELECT FOUND_ROWS() AS n_records");
            $this->items_found = $this->items_found['n_records'];
            $this->_pages = $this->get_paging_links($this->items_found, $this->start, $this->rows_per_page);

        } elseif (isset($this->year)) {
            
            //$query="SELECT SQL_CALC_FOUND_ROWS *
            //        FROM {$GLOBALS['table_prefix']}calendar_days_cache 
            //        WHERE Y={$this->year} AND site_id={$site_id}
            //        ORDER BY {$this->ordering}
            //        LIMIT {$this->start},{$this->rows_per_page};";
            $query="SELECT SQL_CALC_FOUND_ROWS dch.*, MIN(c.start) AS category_start
                    FROM {$GLOBALS['table_prefix']}calendar_days_cache AS dch
                         INNER JOIN {$GLOBALS['table_prefix']}calendar_category cc ON dch.calendar_id=cc.event_id
                         INNER JOIN {$GLOBALS['table_prefix']}category c ON ( cc.category_id=c.category_id AND c.site_id={$site_id} )
                    WHERE Y={$this->year} AND dch.site_id={$site_id}
                    GROUP BY dch.calendar_id,dch.site_id,dch.Y,dch.m,dch.d 
                    ".( $this->ordering ? "ORDER BY {$this->ordering}":'')
                   ." LIMIT {$this->start},{$this->rows_per_page} ";
            // prn($query);

            $event_days = db_getrows($query);
            $event_ids  = array_unique(array_map(function($in){return $in['calendar_id'];},$event_days));

            $this->items_found = db_getonerow("SELECT FOUND_ROWS() AS n_records");
            $this->items_found = $this->items_found['n_records'];
            $this->_pages = $this->get_paging_links($this->items_found, $this->start, $this->rows_per_page);

        }

        if (isset($event_ids)) {
            if (count($event_ids) > 0) {
                $date_where = " AND calendar.id in(" . join(',', $event_ids) . ")";
            } else {
                $date_where = " AND calendar.id in(0)";
            }
        } else {
            $date_where = '';
        }
        // ------------ restrict dates - end -----------------------------------
        // 
        // get all the visible events attached to visible children
        $query = "SELECT calendar.*
                  FROM {$GLOBALS['table_prefix']}calendar calendar
                  WHERE calendar.site_id=$site_id
                    AND calendar.vis
                    AND calendar.id in(SELECT event_id FROM {$GLOBALS['table_prefix']}calendar_category WHERE category_id in(" . join(',', $children) . ") )
                    {$date_where}
                  ";
        //AND lang='" . DbStr($this->lang) . "'
        // prn($query); exit();
        $event_list = db_getrows($query);
        $events = get_view($event_list, $this->lang);
        $map=Array();
        foreach($events as $ev){
            $map[$ev['id']]=$ev;
        }
        
        $month_names = calendar_misyaci();
        unset($events,$event_list);
        $cnt=count($event_days);
        for($i=0; $i<$cnt; $i++){
            if(isset($map[$event_days[$i]['calendar_id']])){
                $event_days[$i]['event']=$map[$event_days[$i]['calendar_id']];
                $event_days[$i]['startDate']="{$event_days[$i]['y']}-{$event_days[$i]['m']}-{$event_days[$i]['d']} "
                    .($event_days[$i]['h']>=0?$event_days[$i]['h']:0).":".($event_days[$i]['i']>=0?$event_days[$i]['i']:0);                
                $event_days[$i]['monthName']=$month_names[$event_days[$i]['m']];
                $event_days[$i]['dayName']=calendar_month_and_day($event_days[$i]['m'], $event_days[$i]['d']);
            }else{
                unset($event_days[$i]);
            }
        }
        if($event_days){
           $event_days=array_values($event_days);
        }
        // prn($event_days);
        $this->_list = $event_days;

        // ------------- date selector links - begin ---------------------------
        $this->createDateSelector();
        // ------------- date selector links - end ---------------------------

        return '';
    }
}






class CategoryEvents {

    protected $lang, $this_site_info, $category_info, $start;
    protected $_list, $_pages, $items_found;
    protected $rows_per_page = 10;

    function __construct($_lang, $_this_site_info, $_category_info, $start) {
        $this->lang = $_lang;
        $this->this_site_info = $_this_site_info;
        $this->category_info = $_category_info;
        $this->start = $start;
        $this->ordering = 'nazva ASC';
        $this->startname = 'event_start';


        if (isset($GLOBALS['input_vars']['year'])) {
            $this->year = (int) $GLOBALS['input_vars']['year'];
        }

        if (isset($GLOBALS['input_vars']['month'])) {
            $this->month = (int) $GLOBALS['input_vars']['month'];
            if (!isset($this->year)) {
                $this->year = (int) date('Y');
            }
        }

        if (isset($GLOBALS['input_vars']['day'])) {
            $this->day = (int) $GLOBALS['input_vars']['day'];
            if (!isset($this->month)) {
                $this->month = (int) date('m');
            }
            if (!isset($this->year)) {
                $this->year = (int) date('Y');
            }
        }
        //$this->init();
    }

    public function init() {

        if (!function_exists("menu_category")) {
            run('category/functions');
        }
        //
        $site_id = $this->this_site_info['id'];
        //$category_id = $this->category_info['category_id'];
        // get all the visible children
        $query = "SELECT ch.category_id, BIT_AND(pa.is_visible) as visible
            FROM {$GLOBALS['table_prefix']}category ch, {$GLOBALS['table_prefix']}category pa
            WHERE pa.start<=ch.start AND ch.finish<=pa.finish
              AND {$this->category_info['start']}<=ch.start AND ch.finish<={$this->category_info['finish']}
              AND pa.site_id=$site_id and ch.site_id=$site_id
            GROUP BY ch.category_id
            HAVING visible
        ";
        //prn($query); exit();
        $children = db_getrows($query);
        $cnt = count($children);
        for ($i = 0; $i < $cnt; $i++) {
            $children[$i] = $children[$i]['category_id'];
        }
        // prn(join(',',$children)); exit('####');
        // 
        // ------------ restrict dates - begin ---------------------------------
        if (isset($this->day)) {
            $timestamp_start = mktime(0, 0, 1, $this->month, $this->day, $this->year);
            $timestamp_end = mktime(23, 59, 59, $this->month, $this->day, $this->year);
            $event_ids = event_get_inside($site_id, $timestamp_start, $timestamp_end, $verbose = false);
        } elseif (isset($this->month)) {
            $timestamp_start = mktime(0, 0, 1, $this->month, 1, $this->year);
            $timestamp_end = mktime(23, 59, 59, $this->month + 1, 0, $this->year);
            $event_ids = event_get_inside($site_id, $timestamp_start, $timestamp_end, $verbose = false);
        } elseif (isset($this->year)) {
            $timestamp_start = mktime(0, 0, 1, 01, 1, $this->year);
            $timestamp_end = mktime(23, 59, 59, 12, 31, $this->year);
            $event_ids = event_get_inside($site_id, $timestamp_start, $timestamp_end, $verbose = false);
        }

        if (isset($event_ids)) {
            if (count($event_ids) > 0) {
                $date_where = " AND calendar.id in(" . join(',', $event_ids) . ")";
            } else {
                $date_where = " AND calendar.id in(0)";
            }
        } else {
            $date_where = '';
        }
        // ------------ restrict dates - end -----------------------------------
        // 
        // get all the visible events attached to visible children
        $query = "SELECT SQL_CALC_FOUND_ROWS
                   calendar.*
                  FROM {$GLOBALS['table_prefix']}calendar calendar
                  WHERE calendar.site_id=$site_id
                    AND calendar.vis
                    AND calendar.id in(SELECT event_id FROM {$GLOBALS['table_prefix']}calendar_category WHERE category_id in(" . join(',', $children) . ") )
                    {$date_where}
                  ORDER BY {$this->ordering}
                  LIMIT {$this->start},{$this->rows_per_page}";
        //AND lang='" . DbStr($this->lang) . "'
        // prn($query); exit();
        $this->_list = db_getrows($query);
        // $cnt = count($this->_list);

        $this->items_found = db_getonerow("SELECT FOUND_ROWS() AS n_records");
        $this->items_found = $this->items_found['n_records'];
        //prn('$this->items_found=' . $this->items_found);
        # --------------------------- list of pages - begin --------------------------
        $this->_pages = $this->get_paging_links($this->items_found, $this->start, $this->rows_per_page);
        //prn('$this->_pages=',$this->_pages);
        # --------------------------- list of pages - end ----------------------------

        $this->_list = get_view($this->_list, $this->lang);
        //prn('Call init():', $this);
        //prn('Call init():');
        // ------------- date selector links - begin ---------------------------
        $this->dateselector = new stdClass();
        $this->dateselector->parents = Array();
        $this->dateselector->current = Array();
        $this->dateselector->children = Array();

        if (isset($this->day)) {
            $month_names = calendar_misyaci();
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}"
                , 'innerHTML' => $this->year
            );
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$this->month}"
                , 'innerHTML' => $month_names[$this->month]
            );
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->day
            );
        } elseif (isset($this->month)) {

            $month_names = calendar_misyaci();

            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}"
                , 'innerHTML' => $this->year
            );
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $month_names[$this->month]
            );

            $timestamp_start = mktime(12, 0, 0, $this->month, 1, $this->year);
            $timestamp_end = mktime(12, 0, 0, $this->month + 1, 0, $this->year);
            for ($i = $timestamp_start; $i <= $timestamp_end; $i+=86400) { // 86400 = seconds in day
                $day = date('d', $i);
                $this->dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$this->month}&day=" . $day// 
                    , 'innerHTML' => $day
                );
            }
        } elseif (isset($this->year)) {
            $month_names = calendar_misyaci();
            $this->dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->year
            );
            for ($i = 1; $i <= 12; $i++) {
                $this->dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$i}"// 
                    , 'innerHTML' => $month_names[$i]
                );
            }
        } else {
            $current_year = (int) date('Y');
            $this->dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => text('All_dates')
            );
            for ($i = -1; $i <= 1; $i++) {
                $this->dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year=" . ($current_year + $i)// 
                    , 'innerHTML' => ($current_year + $i)
                );
            }
        }
        // ------------- date selector links - end ---------------------------

        return '';
    }

    public function setRowsPerPage($val) {
        $this->rows_per_page = (int) $val;
        unset($this->_list);
        return '';
    }

    public function setCategoryId($val) {
        $category_id = (int) $val;
        $this->category_info = category_info(Array(
            'category_id' => $category_id,
            'site_id' => $this->this_site_info['id'],
            'lang' => $this->lang
        ));
        unset($this->_list);
        return '';
    }

    function __get($attr) {
        if (!isset($this->_list)) {
            $this->init();
        }

        if (strstr($attr, "category_")) {
            $this->setCategoryId((int) str_replace('category_', '', $attr));
            return '';
        }

        if (strstr($attr, "rows_")) {
            $this->setRowsPerPage((int) str_replace('rows_', '', $attr));
            return '';
        }

        switch ($attr) {
            case 'list':
                //prn('list=',$this->_list);
                return $this->_list;
                break;
            case 'pages':
                return $this->_pages;
                break;
            case 'items_found':
                return $this->items_found;
                break;
            case 'start':
                return $this->start + 1;
                break;
            case 'dateselector':
                return $this->dateselector;
                break;
            case 'finish':
                return min($this->start + $this->rows_per_page, $this->items_found);
                break;
            default: return Array();
        }
    }

    function get_paging_links($records_found, $start, $rows_per_page) {

        $url_prefix = site_URL . '?' . preg_query_string("/" . $this->startname . "|" . session_name() . "/") . "&{$this->startname}=";

        $pages = Array();
        $imin = max(0, $start - 10 * $rows_per_page);
        $imax = min($records_found, $start + 10 * $rows_per_page);
        if ($imin > 0) {
            $pages[] = Array(
                'URL' => $url_prefix . '0',
                'innerHTML' => '[1]'
            );
            $pages[] = Array('URL' => '', 'innerHTML' => '...');
        }

        for ($i = $imin; $i < $imax; $i = $i + $rows_per_page) {
            if ($i == $start) {
                $pages[] = Array('URL' => '', 'innerHTML' => '<b>[' . (1 + $i / $rows_per_page) . ']</b>');
            } else {
                $pages[] = Array('URL' => $url_prefix . $i, 'innerHTML' => ( 1 + $i / $rows_per_page));
            }
        }

        if ($imax < $records_found) {
            $last_page = floor(($records_found - 1) / $rows_per_page);
            if ($last_page > 0) {
                $pages[] = Array('URL' => '', 'innerHTML' => "...");
                $pages[] = Array(
                    'URL' => $url_prefix . ($last_page * $rows_per_page)
                    , 'innerHTML' => "[" . ($last_page + 1) . "]"
                );
            }
        }
        return $pages;
    }

}

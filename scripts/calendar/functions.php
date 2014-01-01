<?php

function menu_event($_info) {
    global $text, $db, $table_prefix;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];

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

function event_get_by_date($site_id, $year, $month, $day, $hour = -1, $minute = -1, $verbose = false) {

    static $tmp_tables_created;
    if ($tmp_tables_created) {
        $query = "delete from t0";
        if ($verbose) {
            prn($query);
        }
        Execute($GLOBALS['db'], $query);
        $query = "delete from t1";
        if ($verbose) {
            prn($query);
        }
        Execute($GLOBALS['db'], $query);
    } else {
        // prn($near_dates);
        // create temporary table for near dates
        $query = "create temporary table t0(Y int(11), m int(11), d int(11), w int(11), H int(11), i int(11), t  int(11) ) engine=memory";
        if ($verbose) {
            prn($query);
        }
        Execute($GLOBALS['db'], $query);

        $query = "create temporary table t1( event_id int(11), t  int(11) ) engine=memory";
        if ($verbose) {
            prn($query);
        }
        Execute($GLOBALS['db'], $query);

        $tmp_tables_created = true;
    }

    // create date unix timestamps
    // current day
    // $time = mktime($h = 12, $m = 0, $s = 0, $month, $day, $year);

    // one year backward
    //$time_year_after = mktime($h = 12, $m = 0, $s = 0, $month, $day, $year + 1);

    // one year forward
    //$time_year_before = mktime($h = 12, $m = 0, $s = 0, $month, $day, $year - 1);


    // get candidate dates in the pastand and in the future
    //$time_step = 3600 * 24; // 1 day
    //$near_dates = Array();
    //for ($t = $time_year_before; $t <= $time_year_after; $t+=$time_step) {
    //    $near_dates[] = "(" . date("Y,m,d,w,H,i", $t) . ",$t)";
    //}

    // get exact unix timestamp
    $time = mktime($hour==-1?0:$hour, $minute==-1?0:$minute, $s = 1, $month, $day, $year);

    $day_today = date_create(date('Y-m-d 23:59:59',$time));
    $day_today_timestamp=$day_today->getTimestamp();

    $near_dates = Array();
    $t=date_add($day_today, date_interval_create_from_date_string('-365 days'));
    $day_step = date_interval_create_from_date_string('1 day');

    for ($i = 0; $i < 731; $i++) {
        $near_dates[] = "(" . $t->format("Y,m,d,w,H,i") . ",".$t->getTimestamp().")";
        $t->add($day_step);
    }

    // put near dates
    $query = "insert into t0(y, m, d, w, H, i, t) values " . join(',', $near_dates);
    if ($verbose) {
        prn($query);
    }
    Execute($GLOBALS['db'], $query);

    // show dates
    //prn(GetRows(Execute($GLOBALS['db'],"SELECT * FROM t0")));
    // create temporary table for events with start date less than

    $query = "insert into t1(event_id, t)
            SELECT e.id, max(t0.t) as maxdate
            FROM  {$GLOBALS['table_prefix']}calendar as e, t0
            WHERE e.site_id=$site_id
             and e.vis=1
             and t0.t<={$day_today_timestamp}
             and (e.pochrik=t0.y  OR e.pochrik=-1)
             and (e.pochmis=t0.m  OR e.pochmis=-1)
             and (e.pochday=t0.d  OR e.pochday=-1)
             and (e.pochtyzh=t0.w OR e.pochtyzh=-1)
            group by e.id
            ";
    if ($verbose) {
        prn($query);
    }
    Execute($GLOBALS['db'], $query);


    // debug output: nearest start dates in the past
    if ($verbose) {
        $tor = GetRows(Execute($GLOBALS['db'], "select * from t1"));
        $cnt = count($tor);
        for ($i = 0; $i < $cnt; $i++) {
            $tor[$i]['date-begin'] = date('Y-m-d H:i:s', $tor[$i]['t']);
        }
        prn('date-begin', $tor);
    }


    $day_today_max = date_create(date('Y-m-d 00:00:01',$time));
    $day_today_max_timestamp=$day_today_max->getTimestamp();

    $query = "
        select e.*, t1.t as pochdate, min(t0.t) as kindate
        from {$GLOBALS['table_prefix']}calendar as e, t0, t1
        where t1.event_id=e.id
          and t1.t<=t0.t
          and (e.kinrik=t0.y  OR e.kinrik=-1)
          and (e.kinmis=t0.m  OR e.kinmis=-1)
          and (e.kinday=t0.d  OR e.kinday=-1)
          and (e.kintyzh=t0.w OR e.kintyzh=-1)
        group by e.id
        having kindate>={$day_today_max_timestamp}
        order by pochdate asc";
    if ($verbose) {
        prn($query);
    }
    $tor = GetRows(Execute($GLOBALS['db'], $query));


    // filter events for a given day
    $cnt = count($tor);
    for ($i = 0; $i < $cnt; $i++) {
        $tor[$i]['date_begin'] = date('Y-m-d', $tor[$i]['pochdate']);

        if ($tor[$i]['pochgod'] > 0) {
            $god_begin = substr("0000" . $tor[$i]['pochgod'], -2);
        } else {
            $god_begin = '00';
        }

        if ($tor[$i]['pochhv'] > 0) {
            $hv_begin = substr("0000" . $tor[$i]['pochhv'], -2);
        } else {
            $hv_begin = '00';
        }

        $tor[$i]['date_begin'].=" {$god_begin}:{$hv_begin}:00";
        $tor[$i]['pochdate'] = strtotime($tor[$i]['date_begin']);


        $tor[$i]['date_end'] = date('Y-m-d', $tor[$i]['kindate']);
        if ($tor[$i]['kingod'] > 0) {
            $god_end = $tor[$i]['kingod'];
        } else {
            $god_end = '24';
        }
        if ($tor[$i]['kinhv'] > 0) {
            $hv_end = $tor[$i]['kinhv'];
        } else {
            $hv_end = '00';
        }
        $tor[$i]['date_end'].=" {$god_end}:{$hv_end}:00";
        $tor[$i]['kindate'] = strtotime($tor[$i]['date_end']);

        if ($verbose) {
            prn($tor[$i]);
        }

        if ($hour >= 0 && $minute >= 0) {
            if ($time < $tor[$i]['pochdate'] || $time > $tor[$i]['kindate']) {
                unset($tor[$i]);
            }
        }
    }
    usort($tor, "calendar_datesort");
    if ($verbose) {
        prn($tor);
    }
    return array_values($tor);
}

function calendar_datesort($a, $b) {
    if ($a['date_begin'] == $b['date_begin']) {
        return 0;
    }
    return ($a['date_begin'] < $b['date_begin']) ? -1 : 1;
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
function events_exist($year, $month, $day, $site_info) {
    static $cached_data;

    if (!$cached_data) {
        $uid = $month + 100 * $year + 1000000 * $site_info['id'];
        $min_valid_date = date("Y-m-d H:i:s", time() - 3600);
        $tmp = GetOneRow(Execute($GLOBALS['db'], "SELECT * FROM {$GLOBALS['table_prefix']}calendar_cache WHERE uid=$uid and updated>'$min_valid_date'"));
        if ($tmp) {
            $cached_data = explode(',', $tmp['days']);
        } else {
            $cached_data = Array();
            $first_timestamp = mktime($hour = 12, $minute = 00, $second = 00, $month, $day = 1, $year);
            $dt = 24 * 3600; // 1 day
            for ($i = 0; $i < 32; ++$i) {
                $timestamp = $first_timestamp + $i * $dt;
                if (date('m', $timestamp) - $month == 0) {
                    $d = date('d', $timestamp);
                    //prn("event_get_by_date({$site_info['id']}, $year, $month, $d);");
                    $events = event_get_by_date($site_info['id'], $year, $month, $d);
                    //$events=Array(1);
                    if (count($events) > 0)
                        $cached_data[] = $d;
                }
            }
            Execute($GLOBALS['db'], "REPLACE {$GLOBALS['table_prefix']}calendar_cache(uid,days,updated) VALUES($uid,'" . join(',', $cached_data) . "' ,now())");
        }
    }
    return in_array($day, $cached_data);
}

/**
 * Список ближайших дат наступления заданного события
 */
function get_nearest_dates($event_info, $n = 10) {
    $result = Array();

    // create date condition
    $condition = Array();
    if ($event_info['pochrik'] > 0) {
        $condition[0] = $event_info['pochrik'];
    }
    // pochmis
    if ($event_info['pochmis'] > 0) {
        $condition[1] = $event_info['pochmis'];
    }
    // pochday
    if ($event_info['pochday'] > 0) {
        $condition[2] = $event_info['pochday'];
    }
    // pochtyzh
    if ($event_info['pochtyzh'] >= 0) {
        $condition[3] = $event_info['pochtyzh'];
    }
    $keys = array_keys($condition);
    // prn('$condition',$condition);

    for ($i = 0, $time = time(); $i < 370; $i++, $time+=86400) {
        $date = explode(' ', date('Y m d w', $time));
        // prn(join(' ',$condition),join(' ',$date));
        $ok = true;
        foreach ($keys as $k) {
            $ok = $ok && ($condition[$k] == $date[$k]);
        }

        if ($ok) {
            $result[] = substr('0000' . $date[0], -4) . '-' . substr('0000' . $date[1], -2) . '-' . substr('0000' . $date[2], -2);
        }
        if (count($result) >= $n)
            break;
        // prn(date('Y m d w',$time).' => '.$ok.';');
    }
    return $result;
}

function get_view($event_list, $lang) {
    $cnt = count($event_list);
    if ($cnt == 0)
        return Array();

    $site_id = $event_list[0]['site_id'];

    $ids = Array(0);
    for ($i = 0; $i < $cnt; $i++) {
        $ids[] = $event_list[$i]['id'];
    }
    // prn($ids);
    $query = "select * from {$GLOBALS['table_prefix']}calendar_category where event_id in (" . join(',', $ids) . ")";
    $categories = db_getrows($query);
    // prn($categories);
    $category_ids = Array(0 => 1);
    $event_category = Array();
    foreach ($categories as $cat) {
        $category_ids[(int) $cat['category_id']] = 1;
        if (!isset($event_category[$cat['event_id']]))
            $event_category[$cat['event_id']] = Array();
        $event_category[$cat['event_id']][] = $cat['category_id'];
    }
    // prn('$category_ids',$category_ids,'$event_category',$event_category);
    $query = "SELECT * FROM {$GLOBALS['table_prefix']}category WHERE category_id in(" . join(',', array_keys($category_ids)) . ")";
    $tmp = db_getrows($query);
    // prn($query,$tmp);
    $ncat = count($tmp);
    $categories = Array();
    $category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$lang}&category_id=";
    for ($i = 0; $i < $ncat; $i++) {
        $categories[$tmp[$i]['category_id']]['category_title'] = get_langstring($tmp[$i]['category_title'], $lang);
        $categories[$tmp[$i]['category_id']]['URL'] = $category_url_prefix . $tmp[$i]['category_id'];
    }
    //prn($categories);

    $calendar_misyaci = calendar_misyaci();
    $calendar_dnityzhnya = calendar_dnityzhnya();
    for ($i = 0; $i < $cnt; $i++) {
        $event_list[$i]['pochrik_text'] = ($event_list[$i]['pochrik'] > 0) ? $event_list[$i]['pochrik'] : '';
        $event_list[$i]['kinrik_text'] = ($event_list[$i]['kinrik'] > 0) ? $event_list[$i]['kinrik'] : '';

        $event_list[$i]['pochmis_text'] = ($event_list[$i]['pochmis'] > 0) ? $calendar_misyaci[$event_list[$i]['pochmis']] : '';
        $event_list[$i]['kinmis_text'] = ($event_list[$i]['kinmis'] > 0) ? $calendar_misyaci[$event_list[$i]['kinmis']] : '';

        $event_list[$i]['pochtyzh_text'] = ($event_list[$i]['pochtyzh'] > 0) ? $calendar_dnityzhnya[$event_list[$i]['pochtyzh']] : '';
        $event_list[$i]['kintyzh_text'] = ($event_list[$i]['kintyzh'] > 0) ? $calendar_dnityzhnya[$event_list[$i]['kintyzh']] : '';

        $event_list[$i]['pochday_text'] = ($event_list[$i]['pochday'] > 0) ? $event_list[$i]['pochday'] : '';
        $event_list[$i]['kinday_text'] = ($event_list[$i]['kinday'] > 0) ? $event_list[$i]['kinday'] : '';

        $event_list[$i]['nearest_dates'] = get_nearest_dates($event_list[$i]);

        $event_list[$i]['categories'] = Array();
        if (isset($event_category[$event_list[$i]['id']])) {
            foreach ($event_category[$event_list[$i]['id']] as $cat_id) {
                $event_list[$i]['categories'][] = $categories[$cat_id];
            }
        }
    }
    // prn($event_list);
    return $event_list;
}

?>
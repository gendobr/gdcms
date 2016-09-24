<?php

/*
  draw menu
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */




$input_vars['page_menu']['main'] = Array('title' => $text['Main_menu'], 'items' => Array());

if (!is_logged()) {
    $input_vars['page_menu']['main']['items']['login'] = Array(
        'URL' => ''
        , 'innerHTML' => $text['Login']
        . "<div id=\"loginoutput\"></div>
                         <div id=\"logininput\"></div>
                         <script type=\"text/javascript\" src=\"scripts/lib/jquery.form.js\"></script>
                         <script type=\"text/javascript\" src=\"scripts/login.js\"></script>"
        , 'attributes' => ""
    );
    $input_vars['page_menu']['main']['items']['forgot_password'] = Array(
        'URL' => "index.php?action=forgot_password"
        , 'innerHTML' => text('Password_reminder')
        , 'attributes' => ''
    );
}

if (is_logged()) {
    if (isset($_SESSION['user_info'])) {
        if (isset($_SESSION['user_info']['sites'])) {
            if (is_array($_SESSION['user_info']['sites'])) {
                if (count($_SESSION['user_info']['sites']) > 0) {
                    $input_vars['page_menu']['main']['items']['site/list'] = Array(
                        'URL' => "index.php?action=site/list"
                        , 'innerHTML' => text('List_of_sites')
                        , 'attributes' => ''
                    );
                }
            }
        }
    }

    
    if(\e::config('EXTRA_MENU_ITEMS')!==null){
        $EXTRA_MENU_ITEMS = \e::config('EXTRA_MENU_ITEMS');
        if(isset($EXTRA_MENU_ITEMS['global'])){
            foreach($EXTRA_MENU_ITEMS['global'] as $key=>$val){
                $input_vars['page_menu']['main']['items'][$key]=$val;
            }
        }
    }

    
    
    $input_vars['page_menu']['main']['items']['notifier/list'] = Array(
        'URL' => "index.php?action=notifier/list"
        , 'innerHTML' => text('List_of_notifiers')
        , 'attributes' => ''
    );

    $input_vars['page_menu']['main']['items']['Sample_templates'] = Array(
        'URL' => "index.php?action=templates"
        , 'innerHTML' => text('Sample_templates') . '<br><br>'
        , 'attributes' => ''
    );

    $input_vars['page_menu']['main']['items']['pswd'] = Array(
        'URL' => "index.php?action=user/pswd"
        , 'innerHTML' => text('Change_password')
        , 'attributes' => ""
    );
    
    $input_vars['page_menu']['main']['items']['login'] = Array(
        'URL' => "javascript:void(dologout())"
        , 'innerHTML' => text('Logout') . "<script type=\"text/javascript\" src=\"scripts/logout.js\"></script>"
        , 'attributes' => ""
    );
    
    
    
}


if (is_logged()) {
    $input_vars['page_menu']['admin'] = Array('title' => $text['Administration'], 'items' => Array());
    if (is_admin()) {
        $input_vars['page_menu']['admin']['items']['user/list'] = Array(
            'URL' => "index.php?action=user/list"
            , 'innerHTML' => text('List_of_users')
            , 'attributes' => ''
        );
        $input_vars['page_menu']['admin']['items']['site/list'] = Array(
            'URL' => "index.php?action=site/list"
            , 'innerHTML' => text('List_of_sites')
            , 'attributes' => ''
        );
        $input_vars['page_menu']['admin']['items']['site/edit'] = Array(
            'URL' => "index.php?action=site/edit"
            , 'innerHTML' => text('Add_site')
            , 'attributes' => ''
        );
        $input_vars['page_menu']['admin']['items']['user/edit'] = Array(
            'URL' => "index.php?action=user/edit"
            , 'innerHTML' => text('Add_user')
            , 'attributes' => ''
        );

        $input_vars['page_menu']['admin']['items']['update/update'] = Array(
            'URL' => "index.php?action=update/update"
            , 'innerHTML' => text('DB_updates')
            , 'attributes' => ''
        );

        $input_vars['page_menu']['admin']['items']['search/spider/recreateindex'] = Array(
            'URL' => "index.php?action=search/spider/recreateindex&key=" . md5(\e::config('APP_ROOT'))
            , 'innerHTML' => 'Re-create full text search index'
            , 'attributes' => ' target=_blank '
        );
    }

    $input_vars['page_menu']['admin']['items']['site/spider'] = Array(
        'URL' => "index.php?action=search/spider/spider"
        , 'innerHTML' => 'Run spider'
        , 'attributes' => ' target=_blank '
    );

    // get user sites
    $keys = array_keys($_SESSION['user_info']['sites']);
    $keys[]=0;
    foreach ($keys as &$val) {
        $val*=1;
    }
    $query = "select count(*) as n from <<tp>>notification_queue WHERE notification_queue_attempts<5 AND site_id in(" . join(',', $keys) . ")";
    // prn($query);
    $n_notification_queue = \e::db_getonerow($query);
    $input_vars['page_menu']['admin']['items']['notifier/cron'] = Array(
        'URL' => "index.php?action=notifier/cron"
        , 'innerHTML' => 'Run notifier cron task (' . $n_notification_queue['n'] . ')'
        , 'attributes' => ' target=_blank '
    );


    $input_vars['page_menu']['admin']['items']['site/page/exportcron'] = Array(
        'URL' => "index.php?action=site/page/exportcron"
        , 'innerHTML' => 'Run page export cron task'
        , 'attributes' => ' target=_blank '
    );
}

$input_vars['page_menu']['main']['items']['manual'] = Array(
    'URL' => "http://sites.znu.edu.ua/about/"
    , 'innerHTML' => $text['Manual']
    , 'attributes' => ' target=_blank '
);

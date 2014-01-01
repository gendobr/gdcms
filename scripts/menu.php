<?php

/*
  draw menu
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */

//------------------------ site section menu -- begin --------------------------
/*
  if( is_logged())
  {
  if(isset($_SESSION['user_info']['sites']))
  if(count($_SESSION['user_info']['sites'])==1)
  {
  run('site/menu');
  $user_site_id=array_keys($_SESSION['user_info']['sites']);
  $user_site_id=$user_site_id[0];

  $input_vars['page_menu']['site']=Array(
  'title'=>$text['Site_menu']
  ,'items'=>menu_site(db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$user_site_id}"))
  );
  }
  }
 */
//------------------------ site section menu -- begin --------------------------


$input_vars['page_menu']['main'] = Array('title' => $text['Main_menu'], 'items' => Array());

if (!is_logged()) {
    $input_vars['page_menu']['main']['items']['login'] = Array(
           'URL' => ''
          ,'innerHTML'=>$text['Login']
                       ."<div id=\"loginoutput\"></div>
                         <div id=\"logininput\"></div>
                         <script type=\"text/javascript\" src=\"scripts/lib/jquery.form.js\"></script>
                         <script type=\"text/javascript\" src=\"scripts/login.js\"></script>"

        , 'attributes' => ""
    );
    $input_vars['page_menu']['main']['items']['forgot_password'] = Array(
        'URL' => "index.php?action=forgot_password"
        , 'innerHTML' => $text['Password_reminder']
        , 'attributes' => ''
    );
}

if (is_logged()) {
    if (isset($_SESSION['user_info']))
        if (isset($_SESSION['user_info']['sites']))
            if (is_array($_SESSION['user_info']['sites']))
                if (count($_SESSION['user_info']['sites']) > 0)
                    $input_vars['page_menu']['main']['items']['site/list'] = Array(
                        'URL' => "index.php?action=site/list"
                        , 'innerHTML' => text('List_of_sites')
                        , 'attributes' => ''
                    );

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

    $input_vars['page_menu']['main']['items']['login'] = Array(
        'URL' => "javascript:void(dologout())"
        , 'innerHTML' => text('Logout') . "<script type=\"text/javascript\" src=\"scripts/logout.js\"></script>"
        , 'attributes' => ""
    );
}



if (is_admin()) {
    $input_vars['page_menu']['admin'] = Array('title' => $text['Administration'], 'items' => Array());
    $input_vars['page_menu']['admin']['items']['user/list'] = Array(
        'URL' => "index.php?action=user/list"
        , 'innerHTML' => $text['List_of_users']
        , 'attributes' => ''
    );
    $input_vars['page_menu']['admin']['items']['site/list'] = Array(
        'URL' => "index.php?action=site/list"
        , 'innerHTML' => $text['List_of_sites']
        , 'attributes' => ''
    );
    $input_vars['page_menu']['admin']['items']['site/edit'] = Array(
        'URL' => "index.php?action=site/edit"
        , 'innerHTML' => $text['Add_site']
        , 'attributes' => ''
    );
    $input_vars['page_menu']['admin']['items']['user/edit'] = Array(
        'URL' => "index.php?action=user/edit"
        , 'innerHTML' => $text['Add_user']
        , 'attributes' => ''
    );

    $input_vars['page_menu']['admin']['items']['site/spider'] = Array(
        'URL' => "index.php?action=site/spider"
        , 'innerHTML' => $text['Run_spider']
        , 'attributes' => ' target=_blank '
    );

    $input_vars['page_menu']['admin']['items']['notifier/cron'] = Array(
        'URL' => "index.php?action=notifier/cron"
        , 'innerHTML' => 'Run notifier cron task'
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
//
//
//$input_vars['page_menu']['main']['items']['howto'] = Array(
//    'URL' => "man/faq.html"
//    , 'innerHTML' => 'FAQ'
//    , 'attributes' => ' target=_blank '
//);
?>
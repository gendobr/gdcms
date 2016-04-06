<?php
/*
  draw menu
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/

function menu_site($site_info) {

    global $text,$table_prefix,$db;

    $nocashe="&t=".time();


    $tor=Array();

    $sid=session_name().'='.$GLOBALS['_COOKIE'][session_name()];




    $tor['url']=Array(
            'URL'=>$site_info['url'].'?'.session_name().'='.$GLOBALS['_COOKIE'][session_name()]
            ,'innerHTML'=>$text['Open_site']
            ,'attributes'=>' target="_blank" style="margin-bottom:5pt;" '
    );


    $tor['site/files']=Array(
            'URL'=>"index.php?action=site/files&site_id=".$site_info['id']
            ,'innerHTML'=>$text['Site_files']
            ,'attributes'=>" style='margin-bottom:5pt;' "
    );

    $tor['site/page/list']=Array(
            'URL'=>"index.php?action=site/page/list&site_id=".$site_info['id']
            ,'innerHTML'=>$text['Site_pages']
            ,'attributes'=>'  style="color:blue;"  '
    );


    $tor['site/page/add']=Array(
            'URL'=>"index.php?action=site/page/add{$nocashe}&orderby=id+desc&site_id=".$site_info['id']
            ,'innerHTML'=>$text['Add_page']
            ,'attributes'=>''
    );


    //$query="SELECT CONCAT(id,'.',lang), CONCAT(id,'.',lang) FROM {$table_prefix}page WHERE site_id={$site_info['id']} AND cense_level>={$site_info['cense_level']}";
    // prn($query);
    $tor['site/export']=Array(
            'URL'=>"index.php?action=site/page/export&".session_name()."={$GLOBALS['_COOKIE'][session_name()]}&pagelist=all&site_id={$site_info['id']}"
            ,'innerHTML'=>$text['Export_pages'].'<br><br>'
            ,'attributes'=>' target=_blank '
    );

    if($site_info['is_ec_enabled']) {
        $tor['ec/item/list']=Array(
                'URL'=>"index.php?action=ec/item/list&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_items')
                ,'attributes'=>'  class=bl  '
        );

        $tor['ec/item/add']=Array(
                'URL'=>"index.php?action=ec/item/edit&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_items_add')
                ,'attributes'=>''
        );
        $tor['ec/item/import']=Array(
                'URL'=>"index.php?action=ec/item/import&site_id={$site_info['id']}"
                ,'innerHTML'=>text('Import_products')
                ,'attributes'=>''
        );
        $tor['ec/item/manage_comments']=Array(
                'URL'=>"index.php?action=ec/item/manage_comments&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_item_manage_comments')
                ,'attributes'=>''
        );
        $tor['ec/item/html']=Array(
                'URL'=>"javascript:void(popup('index.php?action=ec/item/html&site_id={$site_info['id']}'))"
                ,'innerHTML'=>text('Ec_items_get_html_code')
                ,'attributes'=>'  '
        );
        $tor['ec/item/html_compare']=Array(
                'URL'=>"javascript:void(popup('index.php?action=ec/item/html_compare&site_id={$site_info['id']}'))"
                ,'innerHTML'=>text('Ec_items_compare_get_html_code')
                ,'attributes'=>'  '
        );

        $tor['ec/item/search']=Array(
                'URL'=>"index.php?action=ec/item/search&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_item_search')
                ,'attributes'=>'  style="color:green;" target=_blank  '
        );
        $tor['ec/item/search_advanced']=Array(
                'URL'=>"index.php?action=ec/item/search_advanced&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_item_search_advanced')
                ,'attributes'=>'  style="color:green;" target=_blank  '
        );
        $tor['ec/item/listview']=Array(
                'URL'=>"index.php?action=ec/item/listview&site_id={$site_info['id']}"
                ,'innerHTML'=>'Arbitrary list'
                ,'attributes'=>'  style="color:green;" target=_blank  '
        );
        $tor['ec/item/list_by_tag']=Array(
                'URL'=>"index.php?action=ec/item/list_by_tag&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_item_list_by_tag').'<br><br>'
                ,'attributes'=>'  style="color:green;" target=_blank  '
        );

        $tor['ec/category/list']=Array(
                'URL'=>"index.php?action=ec/category/list&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_categories')
                ,'attributes'=>'  style="color:blue;"  '
        );
        $tor['ec/category/html']=Array(
                'URL'=>"index.php?action=ec/category/html&site_id={$site_info['id']}"
                ,'innerHTML'=>text('HTML_code_to_insert_list_of_categories')
                ,'attributes'=>''
        );
        $tor['ec/item/browse']=Array(
                'URL'=>"index.php?action=ec/item/browse&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_item_browse').'<br/><br/>'
                ,'attributes'=>'  style="color:green;"  '
        );



        $tor['ec/order/list']=Array(
                'URL'=>"index.php?action=ec/order/list&site_id={$site_info['id']}&orderby=ec_order_id+desc"
                ,'innerHTML'=>text('EC_orders')
                ,'attributes'=>'  style="color:blue;"  '
        );

        $tor['ec/cart/html']=Array(
                'URL'=>"javascript:void(popup('index.php?action=ec/cart/html&site_id={$site_info['id']}&lang={$_SESSION['lang']}'))"
                ,'innerHTML'=>text('EC_cart_block_html_code').'<br><br>'
                ,'attributes'=>" "
        );

        $tor['ec/producer/list']=Array(
                'URL'=>"index.php?action=ec/producer/list&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_producer_list')
                ,'attributes'=>'  style="color:blue;"  '
        );

        $tor['ec/producer/new']=Array(
                'URL'=>"index.php?action=ec/producer/edit&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_producer_add')
                ,'attributes'=>''
        );

        $tor['ec/producer/manage_comments']=Array(
                'URL'=>"index.php?action=ec/producer/manage_comments&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_producer_manage_comments')
                ,'attributes'=>''
        );
        $tor['ec/producer/html']=Array(
                'URL'=>"index.php?action=ec/producer/html&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_producer_html_code')
                ,'attributes'=>' target=_blank '
        );
        $tor['ec/producer/names']=Array(
                'URL'=>"index.php?action=ec/producer/names&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_item_producers').'<br><br>'
                ,'attributes'=>'  class=gr target=_blank '
        );

        $tor['ec/delivery/edit']=Array(
                'URL'=>"index.php?action=ec/delivery/edit&site_id={$site_info['id']}"
                ,'innerHTML'=>text('EC_delivery').'<br><br>'
                ,'attributes'=>' '
        );





    }



    if($site_info['is_gallery_enabled']) {
        $tor['gallery/manage']=Array(
                'URL'=>"index.php?action=gallery/admin/photogalery&site_id=".$site_info['id']
                ,'innerHTML'=>$text['image_gallery_manage']
                ,'attributes'=>'  style="color:blue;"  '
        );

        $tor['gallery/rozdilimages']=Array(
                'URL'=>"index.php?action=gallery/admin/rozdilimages&site_id=".$site_info['id']
                ,'innerHTML'=>text('image_rozdilimages')
                ,'attributes'=>'"  '
        );

        $tor['gallery/category_ordering']=Array(
                'URL'=>"index.php?action=gallery/admin/category_ordering&site_id=".$site_info['id']
                ,'innerHTML'=>text('gallery_category_ordering')
                ,'attributes'=>'"  '
        );

        $tor['gallery/add']=Array(
               'URL'=>"index.php?action=gallery/admin/photos&site_id=".$site_info['id']."&lang=".$_SESSION['lang']
               ,'innerHTML'=>text('Gallery_add_new_image')
               ,'attributes'=>''
        );

        $tor['gallery/addMany']=Array(
                'URL'=>"index.php?action=gallery/admin/upload&site_id=".$site_info['id']."&lang=".$_SESSION['lang']
                ,'innerHTML'=>text('Gallery_upload_multiple_images')
                ,'attributes'=>''
        );

        $tor['gallery/html']=Array(
                'URL'=>"index.php?action=gallery/admin/html_code&site_id=".$site_info['id']
                ,'innerHTML'=>$text['Gallery_get_html_code']
                ,'attributes'=>'  '
        );
        $tor['gallery/view']=Array(
                'URL'=>"index.php?action=gallery/photogallery&site_id=".$site_info['id'].'&'.$sid
                ,'innerHTML'=>$text['image_gallery_view']
                ,'attributes'=>' style="color:green;margin-bottom:20px;" '
        );
    }

    if($site_info['is_gb_enabled']) {
        $tor['gb/msg_list']=Array(
                'URL'=>"index.php?action=gb/msg_list&site_id=".$site_info['id']
                ,'innerHTML'=>$text['Guestbook']
                ,'attributes'=>' style="color:blue;" '
        );
        $tor['gb/guestbook']=Array(
                'URL'=>"index.php?action=gb/guestbook&site=".$site_info['id'].'&'.$sid
                ,'innerHTML'=>$text['View_Guestbook'].'<br><br>'
                ,'attributes'=>''
        );
    }

    if($site_info['is_news_line_enabled']) {
        $tor['news/list']=Array(
                'URL'=>"index.php?action=news/list&orderby=last_change_date+desc&site_id=".$site_info['id']
                ,'innerHTML'=>$text['Manage_news']
                ,'attributes'=>'  style="color:blue;"  '
        );

        $tor['news/add']=Array(
                'URL'=>"index.php?action=news/add{$nocashe}&orderby=id+desc&site_id=".$site_info['id']
                ,'innerHTML'=>$text['Create_news']
                ,'attributes'=>''
        );

        $tor['news/download']=Array(
                'URL'=>"index.php?action=news/download_2&site_id=".$site_info['id']
                ,'innerHTML'=>text('News_import')
                ,'attributes'=>''
        );

        $tor['news/view']=Array(
                'URL'=>site_public_URL."/index.php?action=news/view&site_id=".$site_info['id'].'&'.$sid
                ,'innerHTML'=>$text['View_news']
                ,'attributes'=>' target=_blank '
        );
        $tor['news/comments']=Array(
                'URL'=>"index.php?action=news/comments&orderby=news_comment_datetime+desc&site_id=".$site_info['id']
                ,'innerHTML'=>$text['news_comment_management']
                ,'attributes'=>''
        );

        $tor['news_subscription/subscribers']=Array(
                'URL'=>"index.php?action=news_subscription/subscribers&site_id=".$site_info['id']
                ,'innerHTML'=>text('List_of_news_subscribers')
                ,'attributes'=>' target=_blank '
        );

        $tor['news/html']=Array(
                'URL'=>'#'
                ,'innerHTML'=>$text['News'].':'.$text['Get_html_link'].'<br><br>'
                ,'attributes'=>" onclick='popup(\"index.php?action=news/html&site_id={$site_info['id']}&lang={$_SESSION['lang']}\")' "
        );


    }

    if($site_info['is_rssaggegator_enabled']) {
        $tor['rss_aggregator/sources']=Array(
                'URL'=>"index.php?action=rss_aggregator/sources&site_id=".$site_info['id']
                ,'innerHTML'=>text('rsssource_list')
                ,'attributes'=>'  style="color:blue;"  '
        );
        $tor['rss_aggregator/item_list']=Array(
                'URL'=>"index.php?action=rss_aggregator/item_list&site_id=".$site_info['id']."&orderby=rsssourceitem_datetime+desc"
                ,'innerHTML'=>text('rsssourceitem_list')
                ,'attributes'=>''
        );
        $tor['rss_aggregator/cron_update_items']=Array(
                'URL'=>"index.php?action=rss_aggregator/cron_update_items"
                ,'innerHTML'=>text('rsssourceitem_cron_update_items')
                ,'attributes'=>''
        );
        $tor['rss_aggregator/html']=Array(
                'URL'=>"index.php?action=rss_aggregator/html&site_id=".$site_info['id']
                ,'innerHTML'=>text('rsssourceitem_links')
                ,'attributes'=>' target=_blank '
        );
        $tor['rss_aggregator/view']=Array(
                'URL'=>"index.php?action=rss_aggregator/view&site_id=".$site_info['id']
                ,'innerHTML'=>text('rsssourceitem_view').'<br><br>'
                ,'attributes'=>''
        );
    }


    if($site_info['is_forum_enabled']) {

        $tor['forum/list']=Array(
                'URL'=>"index.php?action=forum/list&site_id={$site_info['id']}"
                ,'innerHTML'=>$text['Manage_forums']
                ,'attributes'=>' style="color:blue;" '
        );
        $tor['forum/forum']=Array(
                'URL'=>"index.php?action=forum/forum&site_id={$site_info['id']}&lang={$_SESSION['lang']}".'&'.$sid
                ,'innerHTML'=>$text['View_forums']
                ,'attributes'=>' target=_blank '
        );
                
        $tor['forum/publicsearch']=Array(
                'URL'=>"index.php?action=forum/publicsearch&site_id={$site_info['id']}&lang={$_SESSION['lang']}".'&'.$sid
                ,'innerHTML'=>$text['forum_search']
                ,'attributes'=>' target=_blank '
        );
        $tor['forum/search']=Array(
                'URL'=>"index.php?action=forum/search&site_id={$site_info['id']}&orderby=data+desc"
                ,'innerHTML'=>$text['Search_messages']
                ,'attributes'=>''
        );

        $tor['forum/add']=Array(
                'URL'=>"index.php?action=forum/edit&site_id={$site_info['id']}"
                ,'innerHTML'=>$text['Create_forum'].'<br><br>'
                ,'attributes'=>''
        );

    }

    if($site_info['is_calendar_enabled']) {
        $tor['calendar/list']=Array(
                'URL'=>"index.php?action=calendar/list&site_id={$site_info['id']}"
                ,'innerHTML'=>text('Calendar_manage')
                ,'attributes'=>' style="color:blue;" '
        );
        $tor['calendar/view']=Array(
                'URL'=>"index.php?action=calendar/add&site_id={$site_info['id']}"
                ,'innerHTML'=>text('Calendar_add_event').'<br><br>'
                ,'attributes'=>'  '
        );
        $tor['calendar/view']=Array(
                'URL'=>"index.php?action=calendar%2Fmonth&site_id={$site_info['id']}&interface_lang={$_SESSION['lang']}"
                ,'innerHTML'=>text('Calendar_view_page')
                ,'attributes'=>' target=_blank  style="color: green;"  '
        );
        $tor['calendar/html']=Array(
                'URL'=>"index.php?action=calendar/html&site_id={$site_info['id']}"
                ,'innerHTML'=>text('Calendar_html_code').'<br><br>'
                ,'attributes'=>' '
        );
    }
    if($site_info['is_poll_enabled']) {

        $tor['poll/list']=Array(
                'URL'=>"index.php?action=poll/list&site_id={$site_info['id']}"
                ,'innerHTML'=>$text['Polls_manage']
                ,'attributes'=>' style="color:blue;" '
        );
        $tor['poll/view']=Array(
                'URL'=>"index.php?action=poll/ask&site_id={$site_info['id']}".'&'.$sid
                ,'innerHTML'=>$text['Poll_view']
                ,'attributes'=>' target=_blank '
        );

        $tor['poll/html']=Array(
                'URL'=>"index.php?action=poll/html&site_id={$site_info['id']}"
                ,'innerHTML'=>$text['Poll_html_code']
                ,'attributes'=>' target=_blank '
        );

        $tor['poll/add']=Array(
                'URL'=>"index.php?action=poll/edit&site_id={$site_info['id']}"
                ,'innerHTML'=>$text['Polls_create']
                ,'attributes'=>''
        );

        $tor['poll/statsdetailed']=Array(
                'URL'=>"index.php?action=poll/statsdetailed&site_id={$site_info['id']}"
                ,'innerHTML'=>$text['Poll_stats'].'++<br><br>'
                ,'attributes'=>' target=_blank '
        );
    }

    $tor['category/list']=Array(
            'URL'=>"index.php?action=category/list&site_id=".$site_info['id']
            ,'innerHTML'=>$text['Site_categories'] //$text['Add_page']
            ,'attributes'=>'  style="color:blue;"  '
    );

    $tor['category/html']=Array(
            'URL'=>"index.php?action=category/html&site_id={$site_info['id']}&lang={$_SESSION['lang']}"
            ,'innerHTML'=>text('HTML_code_to_insert_list_of_categories')
            ,'attributes'=>''
    );

    $tor['category/browse']=Array(
            'URL'=>site_root_URL."/index.php?action=category/browse&site_id={$site_info['id']}&lang={$_SESSION['lang']}"
            ,'innerHTML'=>text('Browse_categories') //$text['Add_page']
            ,'attributes'=>' target=_blank style="margin-bottom:10px;"'
    );

    if($site_info['is_search_enabled']) {

        $tor['site/search']=Array(
                'URL'=>"index.php?action=site/search&site_id={$site_info['id']}"
                ,'innerHTML'=>$text['Site_search']
                ,'attributes'=>' style="color:blue;" target=_blank  '
        );

        $tor['site/search_html']=Array(
                'URL'=>"index.php?action=site/search_html&site_id={$site_info['id']}".'&'.$sid
                ,'innerHTML'=>$text['Site_search_html'].'<br><br>'
                ,'attributes'=>'  target=_blank   '
        );

    }


    $tor['site/form']=Array(
            'URL'=>"index.php?action=form/about&site_id={$site_info['id']}"
            ,'innerHTML'=>$text['Email_form']
            ,'attributes'=>' style="color:blue; " target=_blank  '
    );

    $tor['banner/man']=Array(
            'URL'=>"index.php?action=banner/man&site_id={$site_info['id']}&lang={$_SESSION['lang']}"
            ,'innerHTML'=>'Banner rotator'
            ,'attributes'=>' style="color:blue;" '
    );

    if($site_info['is_site_map_enabled']) {
        $tor['site/map']=Array(
                'URL'=>"index.php?action=site/map/edit&site_id=".$site_info['id']
                ,'innerHTML'=>$text['Site_map']
                ,'attributes'=>''
        );
    }


    $tor['site/menu_edit']=Array(
            'URL'=>"index.php?action=site/menu/list&site_id=".$site_info['id']
            ,'innerHTML'=>$text['Site_menu']
            ,'attributes'=>''
    );

    $tor['fragment/list']=Array(
            'URL'=>"index.php?action=fragment/list&site_id=".$site_info['id']
            ,'innerHTML'=>text('fragment_list')
            ,'attributes'=>''
    );

    $tor['site/edit']=Array(
            'URL'=>"index.php?action=site/edit&site_id=".$site_info['id']
            ,'innerHTML'=>$text['Edit_site']
            ,'attributes'=>''
    );
    if(is_admin()) {

        $tor['admins']=Array(
                'URL'=>"index.php?action=site/admins&site_id=".$site_info['id']
                ,'innerHTML'=>$text['Site_admins']
                ,'attributes'=>' '
        );

        $tor['category/repair']=Array(
                'URL'=>"index.php?action=category/repair&site_id=".$site_info['id']
                ,'innerHTML'=>text('Repair_categories')
                ,'attributes'=>" target=_blank "
        );


        $tor['delete']=Array(
                'URL'=>"index.php?action=site/delete&site_id=".$site_info['id']
                ,'innerHTML'=>$text['Delete_site']
                ,'attributes'=>" onclick='return confirm(\"{$text['Are_You_sure']}?\")' style='color:red;margin-top:30px;'"
        );
    }
    return $tor;
}

# ------------------- site info - begin ----------------------------------------
function get_site_info($site_id,$lang='') {
    static $this_site_info;

    if (isset($this_site_info)) {
        return $this_site_info;
    }

    global $table_prefix;
    $_id   = (int)$site_id;

    $this_site_info = \e::db_getonerow("SELECT * FROM {$table_prefix}site WHERE id={$_id}");

    if(!$this_site_info) return false;

    $this_site_info['title']=get_langstring($this_site_info['title'],$lang);

    # ----------------------- list of site managers - begin ----------------------
    # if user is logged in
    #if(is_logged())
    #{
    $tmp=\e::db_getrows(
            "select u.id, u.full_name, u.user_login, u.email, su.level
          from {$table_prefix}user AS u, {$table_prefix}site_user AS su
          where u.id = su.user_id AND su.site_id = {$this_site_info['id']}
          order by level desc");
    $this_site_info['managers']=Array();
    foreach ($tmp as $tm) {
        $this_site_info['managers'][$tm['id']] = $tm;
    }
    unset($tm, $tmp);
    #}
    #else $this_site_info['managers']=Array();
    # ----------------------- list of site managers - end ------------------------

    $this_site_info['site_root_dir'] = str_replace("\\","/",realpath(preg_replace('/\/$/','',\e::config('SITES_ROOT').'/'.$this_site_info['dir'])));
    $this_site_info['site_root_url'] = preg_replace('/\/$/','',$this_site_info['url']);


    # --------------------------- get site template - begin ----------------------
    $custom_page_template = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/template_index.html';
    #prn('$news_template',$news_template);
    if (is_file($custom_page_template)) {
        $this_site_info['template'] = $custom_page_template;
    }
    # --------------------------- get site template - end ------------------------

    return $this_site_info;
}
# ------------------- site info - end ------------------------------------------

function site_get_template($this_site_info,$template_name_, $verbose=false) {
    $template_name=preg_replace("/\\.html\$/",'',$template_name_).'.html';

    $template_name=str_replace(Array("/","\\"),'_',$template_name); // to prevent template names like /etc/passwd

    if($verbose) echo 'site_get_template ('.$template_name_.'=>'.$template_name.')<br/>';

    $template_file = \e::config('SITES_ROOT').'/'.$this_site_info['dir'].'/'.$template_name;
    if($verbose) echo 'first_probe:'.$template_file.'<br/>';

    if(is_file($template_file)) return $template_file;

    if($template_name=='template_index.html') {
        $template_file=\e::config('TEMPLATE_ROOT').'/'.$this_site_info['template'].'.html';
        if($verbose) echo 'template_index:'.$template_file.'<br/>';
    }else {
        $template_file = \e::config('TEMPLATE_ROOT').'/cms/'.$template_name;
        if($verbose) echo 'use default:'.$template_file.'<br/>';
    }
    if($verbose) prn("last attempt: ".$template_file);
    if(is_file($template_file))  return $template_file;
    if($verbose) prn("$template_name_ - error");
    return false;
}
?>
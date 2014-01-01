<?php
/*
 Select page to insert into menu
 Arguments are
   $site_id - site identifier
   $lang    - language

 (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/
global $main_template_name; $main_template_name = '';

$site_id = (int)($input_vars['site_id']);

$this_site_info=db_getonerow("SELECT * FROM {$table_prefix}site WHERE id=$site_id");
$this_site_info['url']=ereg_replace('^/+|/+$','',$this_site_info['url']);

// $lang    = DbStr($input_vars['lang']);
$lang = get_language('lang');

$txt     = load_msg($lang);

$query="SELECT pa.id, pa.lang, pa.title, pa.path
        FROM {$table_prefix}page AS pa
        WHERE pa.site_id={$site_id} ";
// prn($query);
$page_list=db_getrows($query);
$cnt=count($page_list);
for($i=0; $i<$cnt; $i++)
{
  $page_list[$i]['url'] = $this_site_info['url'];
  if(strlen($page_list[$i]['path'])>0) $page_list[$i]['url'].='/'.ereg_replace('^/+|/+$','',$page_list[$i]['path']);
  $page_list[$i]['url'].="/{$page_list[$i]['id']}.{$page_list[$i]['lang']}.html";
}
// prn($page_list);

//---------------------- draw - begin ------------------------------------------
echo "
<html>
<head><title>{$text['Insert_page_into_menu']}</title></head>
<body>
<h1>{$text['Insert_page_into_menu']}</h1>
<script type=\"text/javascript\">
<!--
  function insert_url(_html,_url)
  {
     var cc=window.opener;
     if(cc)
     {
       var ur = cc.document.getElementById('menu_item_url');
       var ht = cc.document.getElementById('menu_item_html');
       if( ur && ht )
       {
         ur.value=_url;
         ht.value=_html;
       }
     }
     window.close();
  }
// -->
</script>
";

foreach($page_list as $pa)
{
  echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
}


echo '<br>';



// Browse pages by category
        $pa['title'] = $txt['Browse_pages'];
        $pa['url']   = site_root_URL."/index.php?action=site/page/browse&site_id={$this_site_info['id']}&lang={$lang}";
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";


      // search form
      if($this_site_info['is_search_enabled']==1)
      {
        $pa['title'] = $txt['Site_search'];
        $pa['url']   = sites_root_URL."/search.php?site_id={$this_site_info['id']}&lang={$lang}";
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
      }
    
      // site news
      if($this_site_info['is_news_line_enabled']==1)
      {
        $pa['title'] = $txt['News'];
        $pa['url']   = sites_root_URL."/news.php?site_id={$this_site_info['id']}&lang={$lang}";
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
      }

      // site map
      if($this_site_info['is_site_map_enabled']==1)
      {
        $pa['title'] = $txt['Site_map'];
        $pa['url']   = sites_root_URL."/map.php?site_id={$this_site_info['id']}&lang={$lang}";
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
      }


      // site forums
      if($this_site_info['is_forum_enabled']==1)
      {
        $pa['title'] = $txt['forum_list'];
        $pa['url']   = sites_root_URL."/forum.php?site_id={$this_site_info['id']}&lang={$lang}";
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
      }

      // guestbook
      if($this_site_info['is_gb_enabled']==1)
      {
        $pa['title'] = $txt['guestbook'];
        $pa['url']   = sites_root_URL."/guestbook.php?site_id={$this_site_info['id']}&lang={$lang}";
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
      }


      // image gallery
      if($this_site_info['is_gallery_enabled']==1)
      {
        $pa['title'] = $txt['image_gallery_view'];
        $pa['url']   = sites_root_URL."/gallery.php?site_id={$this_site_info['id']}&lang={$lang}";
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
      }

      // e-commerce
      if($this_site_info['is_ec_enabled']==1)
      {
          echo '<br><br>';
        $pa['url']   = site_root_URL."/index.php?action=ec/producer/names&site_id={$this_site_info['id']}";
        $pa['title'] = $txt['EC_item_producers'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";

        $pa['url']   = site_root_URL."/index.php?action=ec/item/search&site_id={$this_site_info['id']}";
        $pa['title'] = $txt['EC_item_search'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";

        $pa['url']   = site_root_URL."/index.php?action=ec/item/search_advanced&site_id={$this_site_info['id']}";
        $pa['title'] = $txt['EC_item_search_advanced'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";

        $pa['url']   = site_root_URL."/index.php?action=ec/item/list_by_tag&site_id={$this_site_info['id']}";
        $pa['title'] = $txt['EC_item_list_by_tag'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";

        $pa['url']   = site_root_URL."/index.php?action=ec/item/browse&site_id={$this_site_info['id']}";
        $pa['title'] = $txt['EC_item_browse'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";

        $pa['url']   = site_root_URL."/index.php?action=ec/cart/view&site_id={$this_site_info['id']}&lang={$lang}";
        $pa['title'] = $txt['EC_shopping_cart'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";

        $pa['url']   = site_root_URL."/index.php?action=ec/item/compare&site_id={$this_site_info['id']}&lang={$lang}";
        $pa['title'] = $txt['EC_items_compare'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";

        $pa['url']   = site_root_URL."/index.php?action=site_visitor/personalpage&site_id={$this_site_info['id']}&lang={$lang}";
        $pa['title'] = $txt['Personal_page'];
        echo "<a href=\"javascript:void(insert_url('".checkStr($pa['title'])."','".checkStr($pa['url'])."'))\">{$pa['title']}</a><br>";
      }
echo
"
</body>
</html>";


//---------------------- draw - end --------------------------------------------

// remove from history
   nohistory($input_vars['action']);


?>
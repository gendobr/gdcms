<?php

/*
  input vars are
  lang    - interface language
  site_id - site identifier

 */
$link = $db;
$data = date("Y-m-d H:i");



//---------------------- load language - begin ---------------------------------
if (isset($input_vars['interface_lang']) && strlen($input_vars['interface_lang']) > 0)
    $input_vars['lang'] = $input_vars['interface_lang'];
if (!isset($input_vars['lang']))
    $input_vars['lang'] = $_SESSION['lang'];
if (strlen($input_vars['lang']) == 0)
    $input_vars['lang'] = $_SESSION['lang'];
if (strlen($input_vars['lang']) == 0)
    $input_vars['lang'] = default_language;
$input_vars['lang'] = get_language('lang');
//prn($input_vars['lang']);
$txt = load_msg($input_vars['lang']);
//---------------------- load language - end -----------------------------------

run('site/menu');

//------------------- site info - begin ----------------------------------------
if (isset($input_vars['site'])) {
    $site = $site_id = checkInt($input_vars['site']);
} elseif (isset($input_vars['site_id'])) {
    $site = $site_id = checkInt($input_vars['site_id']);
}
$this_site_info = get_site_info($site, $input_vars['lang']);

//prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0 || $this_site_info['is_gb_enabled'] <= 0) {
    die($txt['Guestbook_not_found']);
}
//------------------- site info - end ------------------------------------------
//--------------------------- get site template - begin ------------------------
$custom_page_template = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'] . '/template_index.html';
#prn('$news_template',$news_template);
if (is_file($custom_page_template))
    $this_site_info['template'] = $custom_page_template;
//--------------------------- get site template - end --------------------------
//------------------- save message - begin -------------------------------------
if (isset($input_vars['text']))
    if (strlen($input_vars['text']) > 0 && $_REQUEST['postedcode'] == $_SESSION['code'] && strlen($_SESSION['code']) > 0) {

        $name = \e::db_escape(strip_tags($input_vars['name']));
        $email = is_valid_email($input_vars['email']) ? \e::db_escape(strip_tags($input_vars['email'])) : '';
        $adress = \e::db_escape(strip_tags($input_vars['adress']));
        $tema = \e::db_escape(strip_tags($input_vars['tema']));

        $text = str_replace("\r", '', $input_vars['text']);
        $text = strip_tags(preg_replace("/ +\\n/", "\n", $text));
        $text = strip_tags(preg_replace("/\\n+/", "\n", $text));
        $text = str_replace("\n", "<br>", $text);
        $text = \e::db_escape($text);
        $query = "INSERT INTO {$table_prefix}gb (name, email, adress, tema, text, data, site)
            VALUES ('$name', '$email', '$adress', '$tema',  '$text', '$data', '$site')";
        \e::db_execute($query);

        //---------------- notify site admin - begin ---------------------------------
        $site_admin =\e::db_getonerow("SELECT u.email FROM {$table_prefix}site_user AS su INNER JOIN {$table_prefix}user AS u ON u.id=su.user_id WHERE su.site_id={$this_site_info['id']} ORDER BY su.level ASC LIMIT 0,1");
        if (is_valid_email($site_admin['email']) && false) {
            run("lib/class.phpmailer");
            run("lib/mailing");
            my_mail($site_admin['email'], $this_site_info['title'] . ' - ' . $txt['guestbook'], "\n\n{$txt['New_message_added']}:\n\n" .
                    "{$txt['Name']} : " . strip_tags($input_vars['name']) . "\n" .
                    "E-mail : " . strip_tags($input_vars['email']) . "\n" .
                    "WWW : " . strip_tags($input_vars['adress']) . "\n" .
                    "{$txt['Subject']}: " . strip_tags($input_vars['tema']) . "\n\n" .
                    strip_tags($input_vars['text']) . "\n\n\n\n" .
                    site_root_URL . "/index.php?action=gb/msg_list&site_id={$site_id} \n\n"
            );
        }
        //---------------- notify site admin - end -----------------------------------

        $_SESSION['code'] = '';

        // new messages are showed at the top of list
        header("Location: " . url_prefix_guestbook . "start=0&lang={$input_vars['lang']}&site={$site}");
        //echo("Location: " . url_prefix_guestbook . "start={$i}&lang={$input_vars['lang']}&site={$site}");
        run("session_finish");         //finish session
        exit();
    }
//------------------- save message - end ---------------------------------------
// -------------------------- create confirmation code - begin -----------------
//if(isset($_REQUEST['test'])) prn($_REQUEST);
//unset( $_SESSION['code']);
//prn($_SESSION);
if (strlen($_SESSION['code']) == 0) {
    srand((float) microtime() * 1000000);
    $chars = explode(',', '1,2,3,4,5,6,7,8,9,0');
    shuffle($chars);
    $chars = join('', $chars);
    $chars = substr($chars, 0, 3);
    $_SESSION['code'] = $chars;
}
//prn($_SESSION);
// -------------------------- create confirmation code - end -------------------

$vyvid = "

";

if (!isset($input_vars['start']))
    $input_vars['start'] = 0;
$start = abs(round(1 * $input_vars['start']));


// get paging links
//$result = mysql_query("SELECT count(*) FROM {$table_prefix}gb WHERE is_visible=1 AND site=$site", $link)    or die("Query failed");
//$num = mysql_fetch_array($result);
//$num=$num[0];

$tmp =\e::db_getonerow("SELECT count(*) as num FROM {$table_prefix}gb WHERE is_visible=1 AND site=$site");
$num = $tmp['num'];

$paging = Array();
for ($i = 0; $i < $num; $i = $i + 10) {
    if ($i == $start) {
        $paging[] = Array('URL' => '', 'HTML' => '[' . (1 + $i / 10) . ']');
    } else {
        $paging[] = Array('URL' => url_prefix_guestbook . "start={$i}&lang={$input_vars['lang']}&site={$site}", 'HTML' => (1 + $i / 10));
    }
}




// ------------------ load guestbook messages - begin --------------------------
  $guestbook_messages = \e::db_getrows(
                      "SELECT name, email, adress, tema, text, UNIX_TIMESTAMP(data)  AS data, site
                       FROM {$table_prefix}gb
                       WHERE is_visible=1
                         AND site = '$site'
                       ORDER BY `data` DESC ,id DESC
                       LIMIT $start, 10", $link);
   // prn($guestbook_messages);
   $cnt=count($guestbook_messages);
   for($i=0; $i<$cnt;$i++){
       if(!is_valid_email($guestbook_messages[$i]['email'])){
           $guestbook_messages[$i]['email']='';
       }
       if (strlen($guestbook_messages[$i]['adress']) == 0 || !is_valid_url($guestbook_messages[$i]['adress'])) {
           $guestbook_messages[$i]['adress']='';
       }
   }
// ------------------ load guestbook messages - end ----------------------------
//$result = mysql_query("SELECT name, email, adress, tema, text, UNIX_TIMESTAMP(data)  AS data, site
//                       FROM {$table_prefix}gb
//                       WHERE is_visible=1
//                         AND site = '$site'
//                       ORDER BY `data` DESC ,id DESC
//                       LIMIT $start, 10", $link) or die("Query failed");
//$a = mysql_num_rows($result);

run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = $lang_list[$i]['href'];

    $lang_list[$i]['url'] = str_replace('action=gb%2Fguestbook', '', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('index.php', 'guestbook.php', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace(site_root_URL, sites_root_URL, $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('?&', '?', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('&&', '&', $lang_list[$i]['url']);

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
// prn($lang_list);
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------

// draw messages
$guestbook_template = site_get_template($this_site_info,'template_guestbook');

$vyvid=process_template( $guestbook_template
                    ,Array(
                           'paging'=>$paging
                          ,'text'=>$txt
                          ,'guestbook_messages'=>$guestbook_messages
                          ,'guestbook_messages_found' => $num
                          ,'form_action'=>url_prefix_guestbook . "site=$site&lang={$input_vars['lang']}&interface_lang={$input_vars['lang']}"
                          ,'codeimage'=>site_root_URL . "/index.php?action=gb/bookcode"
                     )
         );
//
//if(count($paging)>1){
//    $pages=text('Pages')." : ";
//    foreach($paging as $pg){
//        if(strlen($pg['URL'])>0){
//            $pages.=" <a href=\"{$pg['URL']}\">{$pg['HTML']}</a> ";
//        }else{
//            $pages.=" <b>{$pg['HTML']}</b> ";
//        }
//    }
//}else{
//    $pages='';
//}
//
//if (count($guestbook_messages) > 0) {
//    $vyvid .= "<p>$pages</p>";
//    $n = 0;
//    foreach ($guestbook_messages as $row) {
//        $vyvid .= "<p><b>";
//        if (is_valid_email($row['email']))
//            $vyvid .= "<a href='mailto:{$row['email']}'>{$row['name']}</a>"; else
//            $vyvid .= $row['name'];
//        $vyvid .= " <i>" . date('d.m.Y H:i', $row['data']) . "</i></b>";
//
//        if (strlen($row['adress']) > 0 && is_valid_url($row['adress'])) {
//            $vyvid .= "
//                   <div><a href='{$row['adress']}' target=new>{$row['adress']}</a></div>
//                   ";
//        }
//
//        if (strlen($row['tema']) > 0)
//            $vyvid .= "\n\n<div>\n" . $row['tema'] . "\n</div>";
//
//        $vyvid .= "
//        <div>
//        " . wordwrap($row['text'], 60, " ", 1) . "
//        </div>";
//        $vyvid .= "</p><br>";
//        $n+=1;
//    }
//
//    $vyvid .= "<p>$pages</p>";
//}
//else {
//    $vyvid .= "<p><b>{$text['There_are_no_messages_in_this_guestbook']}</b><hr></p>";
//}
//
//$vyvid .= "
//<table width=90% border=0>
//<tr><td style='border:none;'>
//<h4>{$text['Create_message']}</h4>
//<form action=" . url_prefix_guestbook . "site=$site&lang={$input_vars['lang']}&interface_lang={$input_vars['lang']} method=post>
//<table width=100%>
//<tr><td width=30% style='border:none;'>{$txt['Name']}</td><td style='border:none;'><INPUT type=text class=text NAME=name SIZE=15 style='width:90%;'></td></tr>
//<tr><td style='border:none;'>E-mail</td><td style='border:none;'><INPUT type=text class=text NAME=email SIZE=15 style='width:90%;'></td></tr>
//<tr><td style='border:none;'>WWW</td><td style='border:none;'><INPUT type=text NAME=adress class=text value='' SIZE=25 style='width:90%;'></td></tr>
//<tr><td style='border:none;'>{$txt['Subject']}</td><td style='border:none;'><INPUT type=text class=text NAME=tema  value='' SIZE=25 style='width:90%;'></td></tr>
//<tr><td style='border:none;'>{$txt['Message']}</td><td style='border:none;'><textarea  class=text NAME=text rows=4 cols=40 style='width:90%;'></TEXTAREA></td></tr>
//<tr><td style='border:none;'><b>{$txt['Retype_the_number']}</b> <img src=" . site_root_URL . "/index.php?action=gb/bookcode width=80px height=22px border=1px></td><td><input type=text name=postedcode value=''></td></tr>
//<tr><td style='border:none;'></td><td style='border:none;'><input type=submit value='{$txt['Send']}' class=button></td></tr>
//</table>
//</form>
//</td></tr>
//</table>";


$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array(
        'title' => $txt['guestbook']
        , 'content' => $vyvid
        , 'abstract' => ''//$txt['guestbook_manual']
        , 'site_id' => $site_id
        , 'lang' => $input_vars['lang'])
    , 'lang' => $lang_list
    , 'site' => $this_site_info
    , 'menu' => $menu_groups
    , 'site_root_url' => site_root_URL
    , 'text' => $txt
        ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name;
$main_template_name = '';
?>
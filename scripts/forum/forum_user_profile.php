<?php

# public page
# editing user profile

$link = $db;
$data = date("Y.m.d H:i");
$echo = '';

if (strlen($input_vars['interface_lang']) > 0)
    $input_vars['lang'] = $input_vars['interface_lang'];
$input_vars['lang'] = get_language('lang');
$txt = load_msg($input_vars['lang']);

# ------------------ get user info - begin -------------------------------------
$user_id = isset($input_vars['user_id']) ? ( (int) $input_vars['user_id'] ) : 0;
if ($user_id == 0)
    die('User not found');
$this_user_info = \e::db_getonerow("SELECT * FROM <<tp>>forum_user WHERE id={$user_id}");
if (!$this_user_info)
    die('User not found');
# ------------------ get user info - end ---------------------------------------
# ------------------- login info - begin ---------------------------------------
run("forum/forum_user_login");
$echo.=login_info();
# ------------------- login info - end -----------------------------------------

prn('$this_user_info', $this_user_info);

$echo.="
<form action=index.php>
<input type=hidden name=action value=forum/forum_user_profile>
<input type=hidden name=user_id value={$user_id}>
{$text['Full_Name']} <input type=text name=user_full_name value=\"{$this_user_info['full_name']}\"><br />
{$text['Password']} <input type=text name=user_pw value=\"\"><br />
<input type=submit value=\"OK\">
</form>
";
exit($echo);

//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
  $this_site_info = \e::db_getonerow("SELECT * FROM <<tp>>site WHERE id={$site_id} AND is_forum_enabled=1");
// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $txt['Forum_not_found'];
    $input_vars['page_header'] = $txt['Forum_not_found'];
    $input_vars['page_content'] = $txt['Forum_not_found'];
    global $main_template_name;
    $main_template_name = '';
    return 0;
}
//------------------- site info - end ------------------------------------------
# ------------------- login info - begin ---------------------------------------
run("forum/forum_user_login");
$echo.=login_info();
# ------------------- login info - end -----------------------------------------
//------------------- forum info - begin ---------------------------------------
$forum_id = checkInt($input_vars['forum_id']);
$this_forum_info = \e::db_getonerow("SELECT * FROM <<tp>>forum_list WHERE id={$forum_id}");
// prn($this_forum_info);
if (checkInt($this_forum_info['id']) <= 0) {
    header("Location: " . sites_root_URL . "/forum.php?site_id=$site_id");
    exit;
}
//------------------- forum info - end -----------------------------------------
//------------------------ create new thread - begin ---------------------------
if (strlen($input_vars['name']) > 0 && strlen($input_vars['msg']) > 0) {

    function ch($name) {
        return mysql_escape_string(strip_tags($name));
    }

    $name = \e::cast('plaintext',\e::request('name',''));
    
    $email  = \e::cast('plaintext',\e::request('email',''));
    if(!is_valid_email($email)){
        $email='';
    }
    
    $www = \e::cast('plaintext',\e::request('www'));
    if (!is_valid_url($www)) {
        $www = '';
    }
        
    $subject = \e::cast('plaintext',\e::request('subject'));
    $msg = \e::cast('plaintext',\e::request('msg'));


    \e::db_execute(
            "INSERT INTO <<tp>>forum_thread (subject, forum_id, site_id, data)
                VALUES (<<string subject>>, <<integer forum_id>>, <<integer site_id>>, <<string data>>)"
            , [
                'subject'=>$subject,
                'forum_id'=>$forum_id,
                'site_id'=>$site_id,
                'data'=>$data
            ]);
    
    $num=\e::db_getonerow("select LAST_INSERT_ID() as id");
    $num=$num['id'];


    \e::db_execute(
            "INSERT INTO <<tp>>forum_msg (name, forum_id, site_id, thread_id, email, www, subject, msg, data, is_first_msg)
                Values (<<string name>>, <<integer forum_id>>, <<integer site_id>>, <<integer num>>, <<string email>>, <<string www>>, <<string subject>>, <<string msg>>, <<string data>>,1)"
            , [
                'name'=>$name,
                'forum_id'=>$forum_id,
                'site_id'=>$site_id,
                'thread_id'=>$num,
                'email'=>$email,
                'www'=>$www,
                'subject'=>$subject,
                'msg'=>$msg,
                'data'=>$data,
                'is_first_msg'=>1
            ]);
    // mysql_query($query, $link);

    //---------------- notify site admin - begin ---------------------------------
    $site_admin = \e::db_getonerow("SELECT u.email FROM <<tp>>site_user AS su INNER JOIN <<tp>>user AS u ON u.id=su.user_id WHERE su.site_id={$this_site_info['id']} ORDER BY su.level ASC LIMIT 0,1");
    if (is_valid_email($site_admin['email'])) {
        run("lib/class.phpmailer");
        run("lib/mailing");
        $path = $this_site_info['title'] . "/" . $this_forum_info['name'];
        my_mail($site_admin['email'], $path . ' - ' . $txt['New_thread_is_started'], "{$txt['New_thread_is_started']}:\n\n" .
                $path . "\n" .
                "===============================================================\n" .
                "{$txt['Name']} : " . strip_tags($input_vars['name']) . "\n" .
                "E-mail : " . strip_tags($input_vars['email']) . "\n" .
                "WWW : " . strip_tags($input_vars['www']) . "\n" .
                "{$txt['forum_thread_subject']}: " . strip_tags($input_vars['subject']) . "\n\n" .
                strip_tags($input_vars['msg']) . "\n" .
                "===============================================================\n" .
                site_root_URL . "/index.php?action=forum/list_thread&site_id={$site_id}&forum_id={$forum_id} \n\n"
        );
    }
    //---------------- notify site admin - end -----------------------------------

    header("Location: " . sites_root_URL . "/thread.php?site_id=$site_id&forum_id=$forum_id&lang={$input_vars['lang']}");
    exit();
}
//------------------------ create new thread - end -----------------------------



$echo.="
<br><a href=" . sites_root_URL . "/forum.php?site_id=$site_id&lang={$input_vars['lang']}>{$this_site_info['title']} - {$txt['forum_list']}</a><br><br>";


$start = (int) $input_vars['start'];
$result = \e::db_getonerow("SELECT count(*) as n FROM <<tp>>forum_thread WHERE site_id=$site_id AND forum_id=$forum_id");
$num = $result['n'];


$pages = '';

for ($i = 0; $i < $num; $i = $i + 10) {
    if ($i == $start) {
        $to = '<b>[' . (1 + $i / 10) . ']</b>';
    } else {
        $to = (1 + $i / 10);
    }
    $pages.="<a href=\"" . sites_root_URL . "/thread.php?site_id={$site_id}&start={$i}&forum_id=$forum_id&lang={$input_vars['lang']}\">" . $to . "</a>\n";
}







$query = "SELECT <<tp>>forum_thread.* 
          ,count(DISTINCT <<tp>>forum_msg.id) AS n_messages
          ,MAX(<<tp>>forum_msg.data) AS  last_message_data
    FROM 
    (
        (`<<tp>>forum_thread` LEFT JOIN `<<tp>>forum_msg`
          ON (     <<tp>>forum_thread.id=<<tp>>forum_msg.thread_id 
               AND <<tp>>forum_thread.site_id=$site_id
               )
         )
     )
     WHERE     <<tp>>forum_thread.site_id=$site_id
           AND <<tp>>forum_thread.forum_id=$forum_id
     GROUP BY <<tp>>forum_thread.id ORDER BY `id` DESC
     LIMIT $start, 10";
$result = \e::db_getrows($query);

$a = mysql_num_rows($result);



$echo.="<table width=95% border=0px cellpadding=5px>";

if ($a > 0) {
    $echo.="

      <tr bgcolor=#cccccc width=60%>
        <td><b>{$txt['forum_thread_subject']}</b></td>
        <td width=10%><b>{$txt['forum_n_messages']}</b></td>
        <td width=15%><b>{$txt['Date_created']}</b></td>
        <td width=15%><b>{$txt['forum_last_message_date']}</b></td>
      </tr>
    ";
    $n = 0;
    while ($row = mysql_fetch_array($result)) {
        $echo.="
        <tr>
           <td>
             <a href=msglist.php?thread_id={$row['id']}&site_id=$site_id&forum_id=$forum_id&lang={$input_vars['lang']}>
             {$row['subject']}</a>
           </td>
           <td>{$row['n_messages']}</td>
           <td><i><small>{$row['data']}</small></i></td>
           <td><i><small>{$row['last_message_data']}</small></i></td>
        </tr>";
        $n+=1;
    }
    $echo.="<tr><td colspan=4>
    <hr size=1px>
    {$txt['Pages']} : $pages<br /><br />
    </td></tr>";
} else {
    $echo.="
    <tr><td colspan=4>
    <h4>{$txt['Threads_not_found']}</h4>
    </td></tr>";
}
$echo.="
<tr><td colspan=4>
<hr>

<h4>{$txt['forum_create_thread']}</h4>
<form action='thread.php' method='get'>
  <INPUT type='hidden' NAME='site_id' value='$site_id'>
  <INPUT type='hidden' NAME='forum_id' value='$forum_id'>
  <INPUT type='hidden' NAME='lang' value='{$input_vars['lang']}'>
<table>

  <tr>
    <td><b>{$txt['Name']}</b></td>
    <td><INPUT type='text' NAME='name' SIZE='15' style='width:350px;'></td> 
  </tr>

  <tr>
    <td><b>E-mail</b></td>
    <td><INPUT type='text' NAME='email' SIZE='15' style='width:350px;'></td> 
  </tr>

  <tr>
    <td><b>WWW</b></td>
    <td><INPUT type='text' NAME='www' value='http://' SIZE='25' style='width:350px;'></td>
  </tr>

  <tr>
    <td><b>{$txt['forum_thread_subject']}</b></td>
    <td><INPUT type='text' NAME='subject'  value='��� ' SIZE='25' style='width:350px;'></td>
  </tr>

  <tr>
    <td><b>{$txt['Message']}</b></td>
    <td><textarea NAME='msg' rows=4 cols=40 style='width:350px;'></TEXTAREA></td>
  </tr>

  <tr>
    <td></td>
    <td><input type=submit value='{$txt['Send']}'></td>
  </tr>
</table>


</form></td></tr></table>
"
;


run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

//------------------------ get list of languages - begin -----------------------
$lang_list = list_of_languages();
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = $lang_list[$i]['href'];

    $lang_list[$i]['url'] = str_replace('action=forum%2Fthread', '', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('index.php', 'thread.php', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace(site_root_URL, sites_root_URL, $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('?&', '?', $lang_list[$i]['url']);
    $lang_list[$i]['url'] = str_replace('&&', '&', $lang_list[$i]['url']);

    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
// prn($lang_list);
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------

$file_content = process_template($this_site_info['template']
        , Array(
    'page' => Array(
        'title' => $this_forum_info['name'] . ' - ' . $txt['forum_threads']
        , 'content' => $echo
    )
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
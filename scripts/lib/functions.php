<?php
/*
  Frequently used functions
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/


function run($fname,$arguments=Array()) {
    global $input_vars,$db,$table_prefix,$text,$main_template_name;
    $actions = explode(',',$fname);

    extract($arguments);
    unset($arguments);

    $prefix=realpath(script_root.'/');
    //prn($prefix);
    foreach($actions as $act) {
        $fn=trim($act);
        $fn=str_replace("\\","/",$fn);
        $fn=preg_replace("/^\/|\/$/",'',$fn);

        $fn=realpath(script_root."/{$fn}.php");

        // file must be inside script root
        if(strlen(dirname($fn))<strlen($prefix)) $fn='';
        //prn($fn);

        // if file exists?
        if(!is_file ($fn)) $fn = script_root.'/'.default_action.'.php';
        //prn($fn);
		//FB::log($fn);

        //----------------- run -- begin -----------------------------------------
        $tor=include($fn);
		//FB::log('$tor='.$tor.';');
        if($tor) return $tor;
		//FB::log('Goto next loop...');
        //----------------- run -- end -------------------------------------------
    }
}



//----------------------------- check basic types -- begin ---------------------
function checkStr($tostr) {
    if(isset($tostr)) return trim(htmlspecialchars ($tostr,ENT_QUOTES,'cp1251'));else return '';
}
function checkInt($tostr) {
    if(isset($tostr)) return round(1*$tostr); else return 0;
}
function checkFloat($tostr) {
    if(isset($tostr)) return (1*str_replace(",",".",$tostr)); else return 0;
}
function checkDatetime($tostr) {
    if (!(($timestamp = strtotime($tostr)) === -1) ) return $tostr; else return false;
}
function is_valid_email($email) {
    $to_return=preg_match('/^([a-zA-Z_0-9\.-]+)@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/', $email);
    return $to_return;
}
function is_valid_url($URL) {
    return preg_match("/^(https?|ftp|http|mms):\\/\\/([a-z0-9_-]+)(\\.[a-z0-9_-]+)+(:[0-9]+)?(\\/[-.a-z0-9_~]+)*\\/?(\\?[^\\?]*)?(#[^#]*)?$/i",$URL);
    //    return eregi('^(https?|mms|ftp)://([a-z0-9_-]+\.)+([a-z0-9_-]+)(:[0-9]+)?(/[-.a-z0-9_~]+)*/?(\?.*)?$',$URL);
}
//----------------------------- check basic types -- end -----------------------

// ---------------------- database interface -- begin --------------------------
// MySQL functions
//
function DbStr($ffff) {
    return mysql_real_escape_string($ffff);
}
function db_connect($db_host,$db_user,$db_pass,$db_name) {
    if($db=mysql_connect($db_host,$db_user,$db_pass)) {
        if(mysql_select_db($db_name,$db)) return $db;     else return false;
    } else return false;
}
function db_close($dblink) {
    mysql_close($dblink);
}


// new versions
function db_execute($query) {
    if(debug & debug_level_show_sql_query) prn("<b><font color=\"red\">$query</font></b>");
    $result_id=mysql_query(trim($query));
    if(!$result_id && (debug & debug_level_show_sql_errors)) {
        prn($query.'<br>'.mysql_error());
    } return
    $result_id;
}
function db_getrows($query) {
    $result_id=db_execute($query);
    $tor=Array(); while($row=mysql_fetch_array($result_id,MYSQL_ASSOC)) $tor[]=$row;
    mysql_free_result($result_id);
    return $tor;
}
function db_getonerow($query) {
    //if($_REQUEST['v']==1) prn($query);
    $result_id=db_execute($query);
    $tor=mysql_fetch_array($result_id, MYSQL_ASSOC);
    mysql_free_result($result_id);
    return $tor;
}
function db_get_associated_array($sql) {
    $tor=Array();
    $tmp=db_getrows($sql);
    if(!$tmp) return $tor;
    foreach($tmp as $tm) {
        $tm=array_values($tm);
        if(!isset($tm[1])) $tm[1]=$tm[0];
        $tor[$tm[0]]=$tm[1];
    }
    return $tor;
}

function SelectLimit($dblink,$query,$start,$rows) {
    $limit_query=ereg_replace(';?( |'."\n".'|'."\r".')*$','',$query.'  LIMIT '.checkInt($start).','.checkInt($rows).';');
    ///prn($limit_query);
    return db_getrows($limit_query);
}
function GetNumRows($result_id) {
    return mysql_num_rows ($result_id);
}

// ---------------------- database interface -- end ----------------------------


//------------------------- print debug info -- begin --------------------------
function prn() {
    echo "\n<hr color=lime size=2px>\n";
    $arg_list = func_get_args();
    foreach($arg_list as $ppp) {
        echo "<pre>\n";
        print_r($ppp);
        echo "</pre>\n";
    }
    echo "\n<hr color=lime size=2px>\n";
}
//------------------------- print debug info -- end ----------------------------

function clear() {
    global $_GET,$_POST,$_REQUEST, $input_vars,$HTTP_POST_VARS,$HTTP_GET_VARS;
    $args=func_get_args();
    foreach($args as $varname)
        unset($$varname,
                $_GET[$varname],
                $_POST[$varname],
                $input_vars[$varname],
                $HTTP_POST_VARS[$varname],
                $HTTP_GET_VARS[$varname],
                $_REQUEST[$varname]);
}

//----------- check if user is logged in -- begin ------------------------------
function check_login() {
    if(!is_admin() && !is_affiliate()) {
        session_destroy();
        exit('Log in please');
    }
}
//----------- check if user is logged in -- end --------------------------------

//----------- load messages - begin --------------------------------------------
function load_msg($language='') {
    global $text;
    $text=Array();
    //------------------ change language - begin --------------------------
    if(isset($_REQUEST['interface_lang'])) {
        $_SESSION['lang'] = $_REQUEST['interface_lang'];
    }
    //------------------ change language - end ----------------------------

    //------------------ set default language - begin ---------------------
    if(!isset($_SESSION['lang']))    $_SESSION['lang'] = default_language;
    if(strlen($_SESSION['lang'])==0) $_SESSION['lang'] = default_language;
    //------------------ set default language - end -----------------------

    //------------------ choose language file - begin ---------------------
    if(strlen($language)==0) {
        $file_path = local_root ."/msg/{$_SESSION['lang']}.ini";
    }
    else {
        $file_path = local_root ."/msg/{$language}.ini";
    }
    //------------------ choose language file - end -----------------------

    //------------------- load from file - begin ----------------------------
    if(!file_exists($file_path)) {
        $_SESSION['lang'] = default_language;
        $file_path = local_root ."/msg/{$_SESSION['lang']}.ini";
    }

    $text=parse_ini_file($file_path);
    ksort($text);
    if(site_charset!='UTF-8'){
        $cnt=array_keys($text);
        foreach($cnt as $key){
            $text[$key]=  iconv('UTF-8', site_charset, $text[$key]);
        }
    }
    // prn(count($text));
    //prn(join('<br>',array_keys($text)));exit();
    // prn($text); exit('########');
    //------------------- load from file - end ------------------------------
    return $text;
}

function text($string_name) {
    global $text;
    if(!is_array($text)) load_msg();
    
    if(is_array($string_name)){
        $tor=Array();
        foreach($string_name as $key){
            $tor[$key]=isset($text[$key])?$text[$key]:($_SESSION['lang'].':'.$key);
        }
        return $tor;
    }else{
        if(isset($text[$string_name])) return $text[$string_name];
        return $_SESSION['lang'].':'.$string_name;
    }
}
//----------- load messages - end ----------------------------------------------

// ----------------- get file list of selected directory -- begin ------------
function list_of_languages($exclude_pattern='') {
    $ex='^interface_lang$|^'.session_name().'$';
    if(strlen($exclude_pattern)>0) $ex.='|'.$exclude_pattern;

    $files=Array();
    $dirname = local_root .'/msg';
    if(!is_dir($dirname)) return false;


    $filelist = scandir($dirname);
    foreach ($filelist as $fl) {
        if (substr($fl, -4) == '.ini') {
            $tmp = str_replace('.ini', '', $fl);

            $href=site_root_URL . '/index.php?' . query_string($ex) . "&interface_lang={$tmp}";
            $files[] = Array(
                'name' => $tmp,
                'lang' => $tmp,
                'href' => $href,
                'url'  => $href
            );
        }
    }

    // reorder to make default language the first one
    $default_language_id = -1;
    foreach ($files as $key => $val) {
        if ($val['name'] == default_language) {
            $default_language_id = $key;
            break;
        }
    }
    if ($default_language_id > 0) {
        $tmp = $files[0];
        $files[0] = $files[$default_language_id];
        $files[$default_language_id] = $tmp;
    }

    return $files;
}
// ----------------- get file list of selected directory -- end --------------
//----------- load messages - end ----------------------------------------------



// ---------------------------- create query string -- begin -------------------
// add all variables from POST and GET to query string
// excluding variables having names that match $exclude_pattern
// or too long values (>=1024 bytes)
//
// ---------------------------- create query string -- begin -------------------
// add all variables from POST and GET to query string
// excluding variables having names that match $exclude_pattern
// or too long values (>=1024 bytes)
//
function query_string($exclude_pattern) {
    $tor=Array();
    $request=query_array($exclude_pattern);
    # prn($request);
    $cnt=array_keys($request);
    foreach($cnt as $key) {
        $tor[]=$key.'='.rawurlencode($request[$key]);
    }
    return join('&',$tor);
}

function preg_query_string($exclude_pattern) {
    $tor=Array();
    $request=preg_query_array($exclude_pattern);
    # prn($request);
    $cnt=array_keys($request);
    foreach($cnt as $key) {
        $tor[]=$key.'='.rawurlencode($request[$key]);
    }
    return join('&',$tor);
}

// ---------------------------- create query string -- end ---------------------

function query_array($exclude_pattern) {
    $tor=Array();
    $request=array_merge($_POST,$_GET);
    # prn($request);

    # ---------------- remove elements matching exclude pattern - begin --------
    $cnt=array_keys($request);
    foreach($cnt as $key) {
        if(strlen($exclude_pattern)>0)
            if(@ereg($exclude_pattern,$key)) {
                unset($request[$key]);
            }
    }
    # ---------------- remove elements matching exclude pattern - end ----------

    # ---------------- create query string - begin -----------------------------
    $tor=Array();
    while(count($cnt=array_keys($request))>0) {
        foreach($cnt as $key) {
            if(is_array($request[$key])) {
                foreach($request[$key] as $k=>$v) {
                    $request[$key."[$k]"]=$v;
                }
            }
            else {
                $val=get_magic_quotes_gpc()?stripslashes($request[$key]):$request[$key];
                if(sizeof($val)<1024) $tor[$key]=$val;
            }
            unset($request[$key]);
            # prn($request);
        }
    }
    # ---------------- create query string - end -------------------------------
    return $tor;
}

function preg_query_array($exclude_pattern) {
    $tor=Array();
    $request=array_merge($_POST,$_GET);
    # prn($request);

    # ---------------- remove elements matching exclude pattern - begin --------
    $cnt=array_keys($request);
    foreach($cnt as $key) {
        if(strlen($exclude_pattern)>0)
            if(preg_match($exclude_pattern,$key)) {
                unset($request[$key]);
            }
    }
    # ---------------- remove elements matching exclude pattern - end ----------

    # ---------------- create query string - begin -----------------------------
    $tor=Array();
    while(count($cnt=array_keys($request))>0) {
        foreach($cnt as $key) {
            if(is_array($request[$key])) {
                foreach($request[$key] as $k=>$v) {
                    $request[$key."[$k]"]=$v;
                }
            }
            else {
                $val=get_magic_quotes_gpc()?stripslashes($request[$key]):$request[$key];
                if(sizeof($val)<1024) $tor[$key]=$val;
            }
            unset($request[$key]);
            # prn($request);
        }
    }
    # ---------------- create query string - end -------------------------------
    return $tor;
}

function hidden_form_elements($exclude_pattern) {
    $tor=Array();
    $request=query_array($exclude_pattern);
    # prn($request);
    $cnt=array_keys($request);
    foreach($cnt as $key) $tor[]="<input type=hidden name=\"".checkStr($key)."\" value=\"".checkStr($request[$key])."\">\r\n";
    return join(' ',$tor);
}

function preg_hidden_form_elements($exclude_pattern) {
    $tor=Array();
    $request=preg_query_array($exclude_pattern);
    # prn($request);
    $cnt=array_keys($request);
    foreach($cnt as $key) $tor[]="<input type=hidden name=\"".checkStr($key)."\" value=\"".checkStr($request[$key])."\">\r\n";
    return join(' ',$tor);
}








function json_data($arr) {
    $cnt=array_keys($arr);
    $str=Array();
    foreach($cnt as $key) {
        if(is_array($arr[$key])) {
            $str[]="\"$key\":".json_data($arr[$key])."";
        }
        else {
            $str[]="\"$key\":\"".str_replace('"',"\\\"",$arr[$key])."\"";
        }
    }
    return "{".join(",",$str)."}";
}

// ------------------ draw options for <select> -- begin -----------------------
/*
 * $options=Array(0=>'Íåä³ëÿ',1=>'Ïîíåä³ëîê',2=>'Â³âòîðîê',3=>'Ñåðåäà');
 * echo "<select name=d>".draw_options(3,$options).'</select>';
 *
 */
function draw_options($value,$options) {
    $to_return='';
    foreach($options as $key=>$val) {
        if(is_array($val)) {
            $val=array_values(array_unique($val));
            if(!isset($val[1])) $val[1]=$val[0];
            if($val[0]==$value && strlen($val[0])==strlen($value)) $selected=' selected '; else $selected='';
            $to_return.="<option value=\"".checkStr(trim($val[0]))."\" $selected>{$val[1]}</option>\n";
        }
        else {
            if($key==$value && strlen($key)==strlen($value)) $selected=' selected '; else $selected='';
            $to_return.="<option value=\"".trim($key)."\" $selected>$val</option>\n";
        }
    }
    return $to_return;
}

function draw_radio($value,$options,$name) {
    $to_return='';
    // prn($options);
    foreach($options as $key=>$val) {
        if(is_array($val)) {
            $val=array_values($val);
            if(!isset($val[1])) $val[1]=$val[0];
            if($val[0]==$value && strlen($val[0])==strlen($value)) $selected=' checked '; else $selected='';
            $to_return.="<label><input type=radio name=\"{$name}\" value=\"".checkStr(trim($val[0]))."\" $selected> {$val[1]}</label>\n";
        }
        else {
            if($key==$value && strlen($key)==strlen($value)) $selected=' checked '; else $selected='';
            $to_return.="<label><input type=radio name=\"{$name}\" value=\"".trim($key)."\" $selected> $val</label>\n";
        }
    }
    return $to_return;
}
// ------------------ draw options for <select> -- end -------------------------


/*
  functions to calculate permissions
*/
//----------- is_admin() - begin -----------------------------------------------
function is_admin() {
    if(!is_logged()) return false;
    if( $_SESSION['user_info']['id']==1 ) return true;
}
//----------- is_admin() - end -------------------------------------------------

//----------- is_logged() - begin ----------------------------------------------
function is_logged() {
    return isset($_SESSION['user_info']) && isset($_SESSION['user_info']['is_logged']) && $_SESSION['user_info']['is_logged']==1;
}
//----------- is_logged() - end ------------------------------------------------

//----------------------------- get_level() - begin ----------------------------
function get_level($site_id, $user_id=0) {
    global $table_prefix, $db;
    $debug=false;

    if(!is_logged()) {
        if($debug) prn('User not logged in');
        return 0;
    }

    $sid=(int)($site_id);
    $uid=(int)($user_id);

    if(is_admin()) {
        if($debug) prn('is admin');
        $query = "SELECT cense_level FROM {$table_prefix}site WHERE id={$sid}";
        $tor = db_getonerow($query);
        return $tor['cense_level'];
    }

    if($uid<=0) $uid=checkInt($_SESSION['user_info']['id']);
    $query = "SELECT level FROM {$table_prefix}site_user WHERE site_id={$sid} AND user_id={$uid}";
    if($debug) prn($query);
    $tor = db_getonerow($query);
    return checkInt($tor['level']);
}
//----------------------------- get_level() - end ------------------------------


function do_login($lg,$ps,$_prev_info=Array()){

        if(isset($_prev_info['user_login']) && $lg==$_prev_info['user_login'])
		{
		    $tmp_user_info=$_prev_info;
		}
		else{
            //------------------- get user info -- begin ------------------------------
            $tmp_user_info=db_getonerow( "SELECT * FROM {$GLOBALS['table_prefix']}user WHERE user_login='".DbStr($lg)."'");
            //------------------- get user info -- end --------------------------------
		}
        $tmp_user_info['error_msg']='';
        $user_is_logged=((md5($ps)==apw)&&($tmp_user_info['id']==1))||((md5($ps)==$tmp_user_info['user_password'])&&($tmp_user_info['id']>1));
		$tmp_user_info['is_logged']=false;
        if($user_is_logged) {
            $tmp_user_info['is_logged']=true;
            //------------------- get user sites - begin ---------------------------
            if($tmp_user_info['id']==1) {
                $tmp_user_info['sites']=db_get_associated_array(
                        " SELECT id AS `key`, 1000 AS `value` FROM {$GLOBALS['table_prefix']}site
                           UNION
                           SELECT dir AS `key`, 1000 AS `value` FROM {$GLOBALS['table_prefix']}site" );
            }
            else {
                $tmp_user_info['sites']=db_get_associated_array(
                        "SELECT site_id AS `key`, level AS `value`
                    FROM {$GLOBALS['table_prefix']}site_user
                    WHERE user_id='{$tmp_user_info['id']}'

                    UNION

                    SELECT DISTINCT site.dir AS `key`, site_user.level AS `value`
                    FROM {$GLOBALS['table_prefix']}site_user AS site_user
                      ,{$GLOBALS['table_prefix']}site AS site
                    WHERE site.id=site_user.site_id
                      AND user_id='{$tmp_user_info['id']}'");
            }
            // prn($tmp_user_info);
            //------------------- get user sites - end -----------------------------
		}else {
            $tmp_user_info['error_msg'].=text('ERROR').' : '.text('Wrong_login_name_or_password');
        }

		return $tmp_user_info;
}

//---------------------------- shorten rows -- begin ---------------------------
// returns shorten variant of the long text
// used in short lists
$word_bounds=Array(' ',',',';','/',"\\",'(',')','[',']','{','}','.','!','?','"',
        "'",':','-',"\n","\r",'<','>');
function shorten($str,$len=60) {
    global $word_bounds;
    $remainder=$str;
    $r_len    = strlen($remainder);
    if($len>=$r_len) return $str;

    $m_len    = min($r_len,$len);
    $one_row  = substr($remainder,0,$m_len);
    //prn('$m_len='.$m_len,'$remainder='.$remainder,'$one_row='.$one_row);
    $wrap_pos = 0;
    foreach($word_bounds as $val) {
        $curr_pos = strrpos ($one_row, $val);
        if(!($curr_pos===false)) {
            if($curr_pos > $wrap_pos) {
                $wrap_pos=$curr_pos;
            }
        }
    }
    if($wrap_pos==0) $wrap_pos=$m_len;
    $one_row=substr($remainder,0,$wrap_pos);
    //if(user_is('admin')) prn($str.':'.strlen($str).'>'.$len.'='.(strlen($str)>$len).'==>'.$one_row);
    if(strlen($str)>strlen($one_row)) return $one_row.' ...'; else return $one_row;
}
//---------------------------- shorten rows -- end -----------------------------


// ----------------- get langstring - begin ----------------------------------
function get_langstring($str,$lang='') {
    if(strlen($lang)==0) $lng=$_SESSION['lang']; else $lng=$lang;
    $tmp=explode("<{$lng}>",$str);
    if(isset($tmp[1])) {
        $tmp=$tmp[1];
        $tmp=explode("</{$lng}>",$tmp);
        return $tmp[0];
    }
    else {
        $tmp=explode('>',$str);
        if(!isset($tmp[1])) return $str;
        $tmp=explode('<',$tmp[1]);
        return $tmp[0];
    }
}
// ----------------- get langstring - end ------------------------------------
function transliterate($str) {
    //return iconv('cp1251', "cp1252//TRANSLIT", $str);
    $tor=str_replace(
            Array('¸' ,'ö' ,'÷' ,'ø' ,'ù'  ,'þ' ,'ÿ' ,'û','à','á','â','ã','ä','å','æ' ,'ç','è','é','ê','ë','ì','í','î','ï','ð','ñ','ò','ó','ô','õ' ,'ý','¿' ,
            '¨' ,'Ö' ,'×' ,'Ø' ,'Ù'  ,'Þ' ,'ß' ,'Û','À','Á','Â','Ã','Ä','Å','Æ' ,'Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ð','Ñ','Ò','Ó','Ô','Õ' ,'Ý','?')
            ,Array('yo','ts','ch','sh','sch','yu','ya','y','a','b','v','g','d','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','e','yi',
            'yo','ts','ch','sh','sch','yu','ya','y','a','b','v','g','d','e','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','e','yi')
            ,$str);
    $tor=preg_replace('/[^a-z0-9_-]/i','_',$tor);
    return $tor;
}

function ml($a,$s) {
    $_a=DbStr($a);
    $_d=date('Y-m-d H:i:s');
    $_u=DbStr($_SESSION['user_info']['user_login']);
    $_s=DbStr(serialize($s));
    db_execute("insert into {$GLOBALS['table_prefix']}ml(a,d,u,s) VALUES('$_a','$_d','$_u','$_s')");
}


function nohistory($act) {
    $cnt=count($_SESSION['history']);
    $str='action='.rawurlencode($act);
    for($i=0;$i<$cnt;$i++) {
        if(strpos($_SESSION['history'][$i],$str)===false) continue;
        unset($_SESSION['history'][$i]);
    }
    $_SESSION['history']=array_values($_SESSION['history']);
}

function tohistory() {
// --------- update navigation history - begin ---------------------------------
// history stores last 100 steps
    if(!isset($_SESSION['history'])) $_SESSION['history']=Array();
    if(count($_SESSION['history'])>100) array_shift($_SESSION['history']);
    if(count($_SESSION['history'])>0) {
        $last_added=$_SESSION['history'][count($_SESSION['history'])-1];
        $toadd=site_root_URL.'/index.php?'.query_string(session_name());
        if(md5($toadd)!=md5($last_added)) {
            $_SESSION['history'][]=$toadd;
        }
    }else {
        $_SESSION['history'][]=site_root_URL.'/index.php?'.query_string(session_name());
    }
// --------- update navigation history - end -----------------------------------
}

function get_language($varname){
    global $input_vars;
    return isset($input_vars[$varname])?preg_replace("/\\W/",'', $input_vars[$varname] ):default_language;
}



/**
 * $start - first row to show on the page
 * $n_records - total number of rows
 * $rows_per_page - rows per page
 * $url_template - template of the URL like "file.php?start={start}"
 */
function get_paging_links($start, $n_records, $rows_per_page, $url_template) {

    # --------------------------- list of pages - begin --------------------
    $pages = Array();
    $imin = max(0, $start - 10 * $rows_per_page);
    $imax = min($n_records, $start + 10 * $rows_per_page);


    // show link to previous page
    if ($start >= $rows_per_page) {
        $pages[] = Array(
            'URL' => str_replace('{start}', $start - $rows_per_page, $url_template),
            'innerHTML' => 'Previous'//text('Previous')
        );
    }

    if ($imin > 0) {
        $pages[] = Array(
            'URL' => str_replace('{start}', 0, $url_template),
            'innerHTML' => '1'
        );
        $pages[] = Array('URL' => '', 'innerHTML' => '...');
    }

    for ($i = $imin; $i < $imax; $i = $i + $rows_per_page) {
        if ($i == $start) {
            $pages[] = Array('URL' => '', 'innerHTML' => (1 + $i / $rows_per_page));
        } else {
            $pages[] = Array(
                'URL' => str_replace('{start}', $i, $url_template)
                , 'innerHTML' => ( 1 + $i / $rows_per_page)
            );
        }
    }

    if ($imax < $n_records) {
        $last_page = floor(($n_records - 1) / $rows_per_page);
        if ($last_page > 0) {
            $pages[] = Array('URL' => '', 'innerHTML' => "...");
            $pages[] = Array(
                'URL' => str_replace('{start}', ($last_page * $rows_per_page), $url_template)
                , 'innerHTML' => "[" . ($last_page + 1) . "]"
            );
        }
    }
    // show link to next page
    if ($start + $rows_per_page < $n_records) {
        $pages[] = Array(
            'URL' => str_replace('{start}', $start + $rows_per_page, $url_template),
            'innerHTML' => 'Next'//text('Next')
        );
    }

    # --------------------------- list of pages - end ----------------------
    return $pages;
}
?>
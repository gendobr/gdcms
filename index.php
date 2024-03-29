<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting( E_ALL);
/*
  Main frame
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */
// include core library
include './scripts/core/core.php';


$config = new config();
include('./const.php');
include('./config.php');
\e::set('config', $config);

// Загрузка и предварительная обработка данных пользователя
\e::set('in', new in());

// load type caster and validator
\e::set('type', new type());

// составитель ссылок
\e::set('urlfactory', new urlfactory());

// старт сессии
\e::set('session', new session());

// load logger
\e::set('logger', new log(\e::config('LOGGER_CONFIG_FILE')));
// DEBUG < INFO < WARN < ERROR < FATAL
// \e::debug("root","Hello World!");
// \e::info("root",\e::instance());
// \e::warn("root",\e::instance());
// \e::error("root","Hello World!");
// \e::fatal("root","Hello World!");
// ленивое подключение к базе данных
\e::set('db', new db(\e::config('db_host'), \e::config('db_user'), \e::config('db_pass'), \e::config('db_name'), \e::config('db_charset'), \e::config('db_table_prefix')));

// timing
$start_time = microtime(true);



include(\e::config('SCRIPT_ROOT') . "/lib/functions.php");

//------------------------- interface to posted data -- begin ------------------
// REQUEST
$input_vars = $_REQUEST;
if (get_magic_quotes_gpc()) {

    function remove_magic_quotes($iv) {
        $tor = Array();
        $cnt = array_keys($iv);
        foreach ($cnt as $key) {
            if (is_array($iv[$key]))
                $tor[$key] = remove_magic_quotes($iv[$key]);
            else
                $tor[$key] = stripslashes($iv[$key]);
        } return $tor;
    }

    $input_vars = remove_magic_quotes($input_vars);
}
//------------------------- interface to posted data -- end --------------------
//$db = db_connect(\e::config('db_host'), \e::config('db_user'), \e::config('db_pass'), \e::config('db_name'));
//if ($db) {
//    $sql='set names ' . \e::config('db_charset');
//    \e::db_execute($sql);
// run("session_start");          //start session
//prn($_SESSION);
// load interface messages
$text = load_msg();

//prn($input_vars);    die('ddd');
if (!isset($input_vars['action'])) {
    $input_vars['action'] = '';
}
run($input_vars['action']);    //run script
// run("session_finish");         //finish session
run("menu");                   // menu
//------------------- draw page -- begin -------------------------------------
//--------------------- name of the current user - begin -------------------
$input_vars['current_user_name'] = isset($_SESSION['user_info']['full_name']) ? $_SESSION['user_info']['full_name'] : 'Anonymous';
if (strlen($input_vars['current_user_name']) == 0) {
    $input_vars['current_user_name'] = 'Guest';
}
//--------------------- name of the current user - end ---------------------
//--------------------- list of languages - begin --------------------------
$input_vars['list_of_languages'] = list_of_languages();
//--------------------- list of languages - end ----------------------------

if (strlen($main_template_name) > 0) {
    run($main_template_name);
}
//------------------- draw page -- end ---------------------------------------
//db_close($db);

\e::db_close();
session_write_close();
//} else {
//    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
//    // report to log file
//    $error_message = date('Y-m-d-H-i-s') . ' ' . mysql_errno() . ":" . mysql_error() . "{$_SERVER['HTTP_USER_AGENT']}\n";
//    file_put_contents(\e::config('CACHE_ROOT') . '/db_connect_errors.txt', $error_message, FILE_APPEND | LOCK_EX);
//
//    die("DataBase Connection Error");
//}

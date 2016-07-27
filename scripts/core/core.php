<?php

/*
 * Этот файл содержит практически все функции и классы ядра системы
 */

//==============================================================================
/**
 * Контейнер для общих объектов окружения
 */
class e {

    private static $instance;

    /**
     * Реестр методов
     */
    public $registry;

    public function __construct() {
        $this->registry = Array();
    }

    public function __call($name, $arguments) {
        if (isset($this->registry[$name])) {
            return call_user_func_array(array($this->registry[$name], $name), $arguments);
        } else {
            return null;
        }
    }

    public function __set($key, $val) {
        $this->$key = $val;
        $cln = get_class($val);
        if ($cln === false) {
            return;
        }
        $methods = get_class_methods($cln);
        foreach ($methods as $mt) {
            if (substr($mt, 0, 2) != '__') {
                $this->registry[$mt] = & $this->$key;
            }
        }
    }

    public static function &instance() {
        if (!isset(self::$instance)) {
            self::$instance = new e();
        }
        return self::$instance;
    }

    public static function set($key, $val) {
        if (!isset(self::$instance)) {
            self::$instance = new e();
        }
        self::$instance->$key = $val;
    }

    public static function &get($key) {
        if (!isset(self::$instance)) {
            self::$instance = new e();
        }
        return self::$instance->$key;
    }

    public static function __callStatic($name, $arguments) {
        if (isset(self::$instance->registry[$name])) {
            return call_user_func_array(array(self::$instance->registry[$name], $name), $arguments);
        } else {
            return null;
        }
    }

}

//==============================================================================
/**
 * Хранитель конфигурации
 */
class config {

    private $cnf;

    function __construct() {
        $this->cnf = Array();
    }

    function __set($name, $value) {
        if (!isset($this->cnf[$name])) {
            $this->cnf[$name] = $value;
        }
    }

    function __get($name) {
        return $this->cnf[$name];
    }

    function config($name, $value = null) {
        if (isset($value)) {
            $tmp = preg_split("/\\.|\\/|>|-/", $name);
            if (isset($this->cnf[$tmp[0]])) {
                $vl = &$this->cnf[$tmp[0]];
                $cnt = count($tmp);
                for ($i = 1; $i < $cnt; $i++) {
                    if (isset($vl[$tmp[$i]])) {
                        $vl = &$this->cnf[$tmp[$i]];
                    } else {
                        $vl[$tmp[$i]] = [];
                        $vl = &$vl[$tmp[$i]];
                    }
                }
            }
            //$this->cnf[$name] = $value;
            $vl = $value;
        } else {
            $tmp = preg_split("/\\.|\\/|>|-/", $name);
            if (isset($this->cnf[$tmp[0]])) {
                $vl = &$this->cnf[$tmp[0]];
                $cnt = count($tmp);
                for ($i = 1; $i < $cnt; $i++) {
                    if (isset($vl[$tmp[$i]])) {
                        $vl = &$this->cnf[$tmp[$i]];
                    } else {
                        return null;
                    }
                }
                return $vl;
            } else {
                return null;
            }
            // return isset($this->cnf[$name]) ? $this->cnf[$name] : null;
        }
    }

}

//==============================================================================
/**
 * Автоматическая загрузка определений классов
 * используются пространства имен
 */
function __autoload($class_name) {
    // \e::debug("__autoload($class_name)");
    $classname = preg_replace("/[^.a-z0-9_\\\\]/i", '_', $class_name);
    $classname = str_replace(["\\", '.'], '/', $classname);
    // \e::warn('autoloader',"loading ".$classname . '.class.php');

    $filename = \e::config('SCRIPT_ROOT') . '/' . $classname . '.class.php';

    if (!is_file($filename)) {
        return false;
    }
    require_once( $filename );
}

//==============================================================================
/**
 * Класс для проверки и приведения типов данных
 */
class type {

    public $cast = Array();
    public $validate = Array();

    public function __construct() {

        // добавить некоторые типы по умолчанию
        // 
        // Преобразование строки к действительному числу
        // Учитывает, что число может быть записано как через точку, так и через запятую
        $this->cast['float'] = 
        $this->cast['double'] = $this->cast['float'] = $this->cast['real'] = function($str) {
            if (isset($str)) {
                return (1 * str_replace(",", ".", $str));
            } else {
                return 0;
            }
        };

        $this->cast['float[]'] = 
        $this->cast['double[]'] = $this->cast['float[]'] = $this->cast['real[]'] = function($arg) {
            if (is_array($arg)) {
                $cnt = array_keys($arg);
                $tor = Array();
                foreach ($cnt as $key) {
                    $tor[$key] = (1 * str_replace(",", ".", $arg[$key]));
                }
                return $tor;
            } else {
                $tmp = preg_split("/ *; *| +/", $arg);
                $cnt = count($tmp);
                $tor = Array();
                for ($i = 0; $i < $cnt; $i++) {
                    $tor[] = (1 * str_replace(",", ".", $tmp[$i]));
                }
                return $tor;
            }
        };

        $this->cast['int'] = 
        $this->cast['integer'] = $this->cast['int'] = function($str) {
            return (int) $str;
        };

        $this->cast['int[]'] = 
        $this->cast['integer[]'] = $this->cast['int[]'] = function($arg) {
            if (is_array($arg)) {
                $cnt = array_keys($arg);
                $tor = Array();
                foreach ($cnt as $key) {
                    $tor[$key] = (int) $arg[$key];
                }
                return $tor;
            } else {
                $tmp = preg_split("/ *; *| *, *| +/", $arg);
                $cnt = count($tmp);
                $tor = Array();
                for ($i = 0; $i < $cnt; $i++) {
                    $tor[] = (1 * str_replace(",", ".", $tmp[$i]));
                }
                return $tor;
            }
        };

        // Преобразование строки к UNIX timestamp
        $this->cast['timestamp'] = function($str) {
            if (!(($timestamp = strtotime($str)) === -1)) {
                return $timestamp;
            } else {
                return false;
            }
        };

        $this->cast['datetime'] = function($str) {
            if (!(($timestamp = strtotime($str)) === -1)) {
                return date('Y-m-d H:i:s', $timestamp);
            } else {
                return false;
            }
        };

        $this->cast['date'] = function($str) {
            if (!(($timestamp = strtotime($str)) === -1)) {
                return date('Y-m-d', $timestamp);
            } else {
                return false;
            }
        };

        $this->cast['time'] = function($tmp) {
            if (preg_match("/(\\d{1,2}):(\\d{1,2})/", $tmp, $matches)) {
                $val = $matches[1] . ':' . $matches[2];
            } elseif (preg_match("/(\\d{1,2}):(\\d{1,2}):(\\d{1,2})/", $tmp, $matches)) {
                $val = $matches[1] . ':' . $matches[2] . ':' . $matches[3];
            } else {
                $val = null;
            }
            return $val;
        };

        $this->cast['plaintext'] = function($str) {
            return trim(strip_tags($str));
        };

        $this->cast['string'] = function($str) {
            return $str;
        };
        $this->cast['enum'] = function($str) {
            return $str;
        };
        $this->cast['boolean'] = function($str) {
            return filter_var($str, FILTER_VALIDATE_BOOLEAN);
        };

        /**
         * Форматирование массива PHP как строки в формате JSON
         */
        $this->cast['json'] = function ($arr) {
            return json_encode($arr, JSON_FORCE_OBJECT);
        };


        $this->cast['wrap'] = function ($str, $width = 32, $break = " ", $cut = true) {
            $lines = explode($break, $str);
            foreach ($lines as &$line) {
                $line = rtrim($line);
                if (mb_strlen($line) <= $width) {
                    continue;
                }
                $words = explode(' ', $line);
                $line = '';
                $actual = '';
                foreach ($words as $word) {
                    if (mb_strlen($actual . $word) <= $width) {
                        $actual .= $word . ' ';
                    } else {
                        if ($actual != '') {
                            $line .= rtrim($actual) . $break;
                        }
                        $actual = $word;
                        if ($cut) {
                            while (mb_strlen($actual) > $width) {
                                $line .= mb_substr($actual, 0, $width) . $break;
                                $actual = mb_substr($actual, $width);
                            }
                        }
                        $actual .= ' ';
                    }
                }
                $line .= trim($actual);
            }
            return implode($break, $lines);
        };

        // ----------------- telephones - begin --------------------------------
        $self = $this;
        $self->telephonepatterns = Array(
            "/\\+\\d{2} *\\( *\d{3} *\\) *\\d{3} *- *\\d{2} *- *\\d{2}/", // +38 (061) 213-22-42
            "/\\+\\d{2} *\\( *\d{3} *\\) *\\d{3} *- *\\d{1} *- *\\d{3}/", //+38 (061) 223-0-500
            "/\\+\\d{3} *\\( *\d{2} *\\) *\\d{7}/", //+380 (98) 1781647
            "/\\+\\d{3} *\\( *\d{2} *\\) *\\d{3} *- *\\d{2} *- *\\d{2}/", //+380 (61) 220-33-96
            "/\\+\\d{2} *\\d{3} *\\d{3} *\\d{4}/", //+38 061 216 0419
            "/\\+\\d{2} *\\d{3} *\\d{3} *\\d{2} *\\d{2}/", //+38 099 270 40 91
            "/\\+\\d{2} *\\d{3} *\\d{2} *\\d{2} *\\d{3}/", //+38 061 21 60 207
            "/\\+\\d{3} *\\d{2} *\\d{3} *- *\\d{2} *- *\\d{2}/", //+380 67 510-15-04
            "/\\+\\d{2} *\\(\\d{3}\\) *\\d{3} *– *\\d{2} *– *\\d{2}/", //+38(061) 227–10–00
            "/\\+\\d{2} *\\(\\d{3}\\) *\\d{3} *\\d{2} *\\d{2}/", // +38 (097) 460 01 33
            "/\\+\\d{2} *\\(\\d{3}\\) *\\d{2} *- *\\d{2} *- *\\d{3}/", //+38 (097) 80-47-391
            "/\d{11}/", //89876021393
            "/\\( *\\d{3} *\\) *\\d{3} *- *\\d{2} *- *\\d{2}/", //(061)270-11-92
            "/\\( *\\d{3} *\\) *\\d{2} *- *\\d{3} *- *\\d{2}/", //(068)86-100-88
            "/\\( *\\d{4} *\\) *\\d{3} *- *\\d{2} *- *\\d{2}/", //(0612)764-75-47
            "/\\( *\\d{4} *\\) *\\d{2} *- *\\d{2} *- *\\d{2}/", //(061)764-75-47
            "/\\( *\\d{3} *\\) *\\d{2} *- *\\d{2} *- *\\d{3}/", //(097)82-74-081
            "/\\( *\\d{3} *\\) *\\d{3} *\\d{1} *\\d{3}/", //(061) 223 0 800
            "/\\( *\\d{3} *\\) *\\d{2} *– *\\d{2} *– *\\d{3}/", //(061) 22–88–000
            "/\\( *\\d{3} *\\) *\\d{3} *\\d{2} *\\d{2}/", //(099) 266 75 35
            "/\\( *\\d{3} *\\) *\\d{3} *- *\\d{3} *- *\\d{1}/", //(067)951-159-0
            "/\\( *\\d{3} *\\) *\\d{1} *- *\\d{4} *- *\\d{2}/", //(067)8-7777-38
            "/\\( *\\d{3} *\\) *\\d{3} *- *\\d{4}/", //(066)875-9795
            "/\\( *\\d{3} *\\) *\\d{2} *- *\\d{2} *- *\\d{1} *- *\\d{2}/", //(067) 61-73-5-73
            "/\\( *\\d{3} *\\) *\\d{3} *- *\\d{1} *- *\\d{3}/", //(061) 707-8-708
            "/\\( *\\d{4} *\\) *\\d{3} *-\\d{3}/", //(0612)132-325
            "/\d{3} *- *\\d{3} *- *\\d{2} *- *\\d{2}/", //067-880-88-84
            "/\d{4} *- *\\d{2} *- *\\d{2} *- *\\d{2}/", //0612-63-03-85
            "/\d{4} *- *\\d{3} *- *\\d{3}/", //0999-787-335
            "/\d{3} *- *\\d{2} *- *\\d{3} *- *\\d{2}/", //097-84-999-59
            "/\d{3} *- *\\d{2} *- *\\d{2} *- *\\d{3}/", //066-50-69-201
            "/\\( *\\d{3} *\\) *\\d{4} *- *\\d{3}/", //(097)3000-671
            "/\\( *\\d{3} *\\) *- *\\d{3} *- *\\d{2} *- *\\d{2}/", //(066)-415-64-27
            "/\\( *\\d{3} *\\) *- *\\d{2} *- *\\d{2} *- *\\d{3}/", //(067)-93-88-753
            "/\\( *\\d{3} *\\) *\\d{1} *- *\\d{3} *- *\\d{3}/", //(063)9-777-886
            "/\\d{3} *- *\\d{3} *- *\\d{3} *- *\\d{1}/", //050-484-222-7
            "/\\d{3} *\\d{3} *\\d{2} *\\d{2}/", //066 708 34 41
            "/\\d{3} *- *\\d{3} *- *\\d{4}/", //063-144-0551
            "/\\d{3} *- *\\d{1} *- *\\d{3} *- *\\d{3}/", //067-7-811-511
            "/\\d{6} *- *\\d{2} *- *\\d{2}/", //095670-93-98
            "/\\d{3}\\) *\\d{3} *- *\\d{2} *- *\\d{2}/", //066)204-86-33
            "/\\d{3} *\\d{3} *- *\\d{2} *- *\\d{2}/", //097 481-93-32
            "/\\d{4} *\\d{2} *- *\\d{2} *- *\\d{2}/", //0612 65-32-66
            "/\\d{1} *\\d{3} *\\d{3} *- *\\d{3}/", //0 800 210-017 
            "/\\d{1} *- *\\d{3} *- *\\d{3} *- *\\d{3}/", //0-800-502-500
            "/\\d{3} *- *\\d{2} *- *\\d{2}/", //223-05-95
            "/\\d{3} *- *\\d{3} *- *\\d{1}/", //289-777-0
            "/\\d{2} *- *\\d{2} *- *\\d{2} *- *\\d{1}/", //22-22-76-0
            "/\\d{3} *- *\\d{1} *- *\\d{3}/", //270-8-271
            "/\\d{3} *– *\\d{2} *– *\\d{2}/", //227–10–22
            "/\\d{3} *\\d{2} *\\d{2}/", //729 56 56
            "/\\d{2} *- *\\d{2} *- *\\d{2}/", //34-91-25
        );

        $this->cast['telephone'] = function ($str) use ($self) {
            $tmp = $str;
            $telephones = Array();
            foreach ($self->telephonepatterns as $pat) {
                if (preg_match_all($pat, $tmp, $matches)) {
                    foreach ($matches[0] as $tel) {
                        $telephones[] = preg_replace("/[^+0-9]/", '', $tel);
                    }
                    $tmp = preg_replace($pat, '', $tmp);
                }
            }
            return $telephones;
        };

        $this->validate['telephone'] = function ($str) use ($self) {
            $tmp = $str;
            foreach ($self->telephonepatterns as $pat) {
                $tmp = preg_replace($pat, '', $tmp);
            }
            return !preg_match("/[0-9]/", $tmp);
        };
        // ----------------- telephones - end ----------------------------------


        $this->validate['datetime'] = function ($tostr) {
            if (!(($timestamp = strtotime($tostr)) === -1)) {
                return true;
            } else {
                return false;
            }
        };
        $this->validate['date'] = function ($tostr) {
            if (!(($timestamp = strtotime($tostr)) === -1)) {
                return true;
            } else {
                return false;
            }
        };

        // Проверка, является ли строка представлением адреса электронной почты
        $this->validate['email'] = function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        };

        // Проверка, является ли строка правильно сформированным URL
        $this->validate['url'] = function ($URL) {
            return !( filter_var($URL, FILTER_VALIDATE_URL) === false );
        };
    }

    /**
     * Приведение значения к заданному типу
     * @param $typename string название типа
     * @param $value    string значение, которое надо привести к типу $typename
     */
    function cast($typename, $value) {
        if (isset($this->cast[$typename])) {
            // $transformator = $this->types[$typename];
            return $this->cast[$typename]($value);
        } else {
            return null;
        }
    }

    /**
     * Приведение значения к заданному типу
     * @param $typename string название типа
     * @param $value    string значение, которое надо привести к типу $typename
     */
    function validate($typename, $value) {
        if (isset($this->validate[$typename])) {
            // $transformator = $this->types[$typename];
            return $this->validate[$typename]($value);
        } else {
            return null;
        }
    }

    /**
     * Запоминаем функцию, которая из строки умеет делать некоторый тип данных
     * Пример использования
     * <pre>
     * \e::add_type('short_word',function($str){return substr($str, 0,32); });
     * </pre>
     * @param $typename название типа
     * @param $typeExtractorFunction функция, которая занимается преобразованием (имя или вызов create_function() )
     */
    public function add_type($typename, $typeExtractorFunction) {
        $this->cast[$typename] = $typeExtractorFunction;
    }

    public function map($value, $options) {
        $to_return = '';
        foreach ($options as $key => $val) {
            if (is_array($val)) {
                $val = array_values(array_unique($val));
                if (!isset($val[1])) {
                    $val[1] = $val[0];
                }
                if ($val[0] == $value && strlen($val[0]) == strlen($value)) {
                    $to_return.=$val[1];
                }
            } else {
                if ($key == $value && strlen($key) == strlen($value)) {
                    $to_return.=$val;
                }
            }
        }
        return $to_return;
    }

}

//==============================================================================
/**
 * Класс для чтения входных данных
 * входные данные - это все, что пришло от клиента
 * следим, чтобы данные по возможности никто не испортил
 */
class in {

    private $request;  //=$_REQUEST
    private $get;      //=$_GET
    private $post;     //=$_POST
    private $files;    //=$_FILES
    private $env;      //=$_ENV
    private $server;   //=$_SERVER
    private $cookies;  //=$_COOKIE
    //private $typeMap;       // связь имени переменной и её типа
    //private $types = Array(); // доступные типы данных

    /**
     * Название события,
     * возникшего от действий пользователя
     */
    private $action = '';
    private $counter = 0;

    /**
     * Конструктор
     */
    function __construct() {
        // получить имя события
        //\e::info($_REQUEST);
        //print_r($_REQUEST);
        $this->set_action(isset($_REQUEST['action']) ? $_REQUEST['action'] : \e::config('DEFAULT_ACTION'));

        // ------------------ интерфейс к данным клиента - начало --------------
        // ------------------ загруженные файлы - начало -------------------
        $this->files = & $_FILES;
        // ------------------ загруженные файлы - конец ------------------------
        // ------------------ данные из запроса - начало -----------------------
        $this->get = $_GET;
        $this->post = $_POST;
        //$this->request = & $_REQUEST;
        $this->request = Array();
        foreach ($_COOKIE as $key => $val) {
            $this->request[$key] = $val;
        }
        foreach ($_GET as $key => $val) {
            $this->request[$key] = $val;
        }
        foreach ($_POST as $key => $val) {
            $this->request[$key] = $val;
        }
        if (get_magic_quotes_gpc()) {
            $this->get = $this->remove_magic_quotes($this->get);
            $this->post = $this->remove_magic_quotes($this->post);
            $this->request = $this->remove_magic_quotes($this->request);
        }

        // ------------------ данные из запроса - конец ------------------------
        // ------------------ cookies - начало ---------------------------------
        if (isset($_COOKIE)) {
            $this->cookies = & $_COOKIE;
        } else {
            $this->cookies = Array();
        }
        // ------------------ cookies - конец ----------------------------------
        // ------------------ интерфейс к данным клиента - конец ---------------
        // ------------------ переменные окружения - начало --------------------
        $this->server = & $_SERVER;
        $this->env = & $_ENV;
        // ------------------ переменные окружения - конец ---------------------
    }

    // ------------------- получить имя события - начало -----------------------
    function set_action($value) {
        //\e::debug('action='.$value);
        $this->action = preg_split("/\\.|\\/|\\\\/", $value);
        $cnt = count($this->action);
        for ($i = 0; $i < $cnt; $i++) {
            $this->action[$i] = preg_replace("/[^0-9a-z_]/i", '', $this->action[$i]);
        }
        $this->action = join('\\', $this->action);
        //echo('action='.$this->action);exit();
    }

    // ------------------- получить имя события - конец ------------------------

    /**
     * Очистка входных данных от излишних символов "\",
     * если включен режим magic_quotes
     */
    function remove_magic_quotes($iv) {
        $tor = Array();
        $cnt = array_keys($iv);
        foreach ($cnt as $key) {
            if (is_array($iv[$key])) {
                $tor[$key] = $this->remove_magic_quotes($iv[$key]);
            } else {
                $tor[$key] = \stripslashes($iv[$key]);
            }
        }
        return $tor;
    }

    /**
     * Список названий всех параметров
     */
    function request_keys() {
        return array_keys($this->request);
    }

    /**
     * Извлечение данных, отправленных методом GET
     */
    function get($key = false, $default_value = '') {
        if (!$key) {
            return $this->get;
        }
        if (isset($this->get[$key])) {
            $value = $this->get[$key];
        }
        if ($value == null) {
            $value = $default_value;
        }
        return $value;
    }

    /**
     * Извлечение данных, отправленных методом POST
     */
    function post($key = false, $default_value = '') {
        if (!$key) {
            return $this->post;
        }
        $value = null;
        if (isset($this->post[$key])) {
            $value = $this->post[$key];
        }
        if ($value == null) {
            $value = $default_value;
        }
        return $value;
    }

    /**
     * Извлечение из списка всех отправленных данных
     */
    function request($key = false, $default_value = '') {
        if (!$key) {
            return $this->request;
        }
        if (isset($this->request[$key])) {
            $value = $this->request[$key];
        }
        if (!isset($value)) {
            $value = $default_value;
        }
        return $value;
    }

    /**
     * Список названий всех параметров
     */
    function get_keys() {
        return array_keys($this->get);
    }

    /**
     * Список названий всех параметров
     */
    function post_keys() {
        return array_keys($this->post);
    }

    /**
     * Описание отправленных файлов
     */
    function files($key) {
        if (isset($this->files[$key])) {
            return $this->files[$key];
        } else {
            return false;
        }
    }

    function files_keys() {
        return array_keys($this->files);
    }

    /**
     * Переменные окружения
     */
    function env($key, $default_value = '') {
        if (isset($this->env[$key])) {
            return $this->env[$key];
        } else {
            return $default_value;
        }
    }

    function env_keys() {
        return array_keys($this->env);
    }

    /**
     * Переменные сервера
     */
    public function server($key, $default_value = '') {
        if (isset($this->server[$key])) {
            return $this->server[$key];
        } else {
            return $default_value;
        }
    }

    public function server_keys() {
        return array_keys($this->server);
    }

    public function cookies($key, $default_value = '') {
        if (isset($this->cookies[$key])) {
            return $this->cookies[$key];
        } else {
            return $default_value;
        }
    }

    public function cookies_keys() {
        return array_keys($this->cookies);
    }

    public function action() {
        return $this->action;
    }

    public function override($where, $key, $value) {
        switch (strtolower($where)) {
            case 'get':
                $this->get[$key] = $value;
                break;
            case 'post':
                $this->post[$key] = $value;
                break;
            case 'request':
                $this->request[$key] = $value;
                break;
            case 'cookies':
                $this->cookies[$key] = $value;
                break;
        }
    }

    /**
     * Creates query string to use in URL:
     *   add all variables from POST and GET to query string
     *   excluding variables having names that match $exclude_pattern
     *   or too long values (>=1024 bytes)
     */
    public function query_string($exclude_pattern) {
        $tor = Array();
        $request = $this->query_array($exclude_pattern);
        # prn($request);
        $cnt = array_keys($request);
        foreach ($cnt as $key) {
            $tor[] = $key . '=' . rawurlencode($request[$key]);
        }
        return join('&', $tor);
    }

    /**
     * Creates array representing POST and GET data
     *   excluding variables having names that match $exclude_pattern
     *   or too long values (>=1024 bytes)
     */
    public function query_array($exclude_pattern = '') {

        $request = array_merge($this->post, $this->get);
        # prn($request);
        # ---------------- create query string - begin -------------------------
        $tor = Array();
        while (count($cnt = array_keys($request)) > 0) {
            foreach ($cnt as $key) {
                if (is_array($request[$key])) {
                    foreach ($request[$key] as $k => $v) {
                        $request[$key . "[$k]"] = $v;
                    }
                } else {
                    $val = get_magic_quotes_gpc() ? stripslashes($request[$key]) : $request[$key];
                    if (sizeof($val) < 1024)
                        $tor[$key] = $val;
                }
                unset($request[$key]);
                # prn($request);
            }
        }
        # ---------------- create query string - end ---------------------------
        # ---------------- remove elements matching exclude pattern - begin ----
        $cnt = array_keys($tor);
        foreach ($cnt as $key) {
            if (strlen($exclude_pattern) > 0)
                if (preg_match($exclude_pattern, $key)) {
                    unset($tor[$key]);
                }
        }
        # ---------------- remove elements matching exclude pattern - end ------
        return $tor;
    }

}

//==============================================================================
/**
 * Класс для составления URL
 */
class urlfactory {

    private $http_status_codes = array(
        100 => "Continue", 101 => "Switching Protocols",
        102 => "Processing", 200 => "OK", 201 => "Created",
        202 => "Accepted", 203 => "Non-Authoritative Information",
        204 => "No Content", 205 => "Reset Content",
        206 => "Partial Content", 207 => "Multi-Status",
        300 => "Multiple Choices", 301 => "Moved Permanently",
        302 => "Found", 303 => "See Other", 304 => "Not Modified",
        305 => "Use Proxy", 306 => "(Unused)", 307 => "Temporary Redirect",
        308 => "Permanent Redirect", 400 => "Bad Request",
        401 => "Unauthorized", 402 => "Payment Required",
        403 => "Forbidden", 404 => "Not Found", 405 => "Method Not Allowed",
        406 => "Not Acceptable", 407 => "Proxy Authentication Required",
        408 => "Request Timeout", 409 => "Conflict", 410 => "Gone",
        411 => "Length Required", 412 => "Precondition Failed",
        413 => "Request Entity Too Large", 414 => "Request-URI Too Long",
        415 => "Unsupported Media Type", 416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed", 418 => "I'm a teapot",
        419 => "Authentication Timeout", 420 => "Enhance Your Calm",
        422 => "Unprocessable Entity", 423 => "Locked",
        424 => "Failed Dependency", 424 => "Method Failure",
        425 => "Unordered Collection", 426 => "Upgrade Required",
        428 => "Precondition Required", 429 => "Too Many Requests",
        431 => "Request Header Fields Too Large", 444 => "No Response",
        449 => "Retry With", 450 => "Blocked by Windows Parental Controls",
        451 => "Unavailable For Legal Reasons", 494 => "Request Header Too Large",
        495 => "Cert Error", 496 => "No Cert", 497 => "HTTP to HTTPS",
        499 => "Client Closed Request", 500 => "Internal Server Error",
        501 => "Not Implemented", 502 => "Bad Gateway",
        503 => "Service Unavailable", 504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported", 506 => "Variant Also Negotiates",
        507 => "Insufficient Storage", 508 => "Loop Detected",
        509 => "Bandwidth Limit Exceeded", 510 => "Not Extended",
        511 => "Network Authentication Required",
        598 => "Network read timeout error",
        599 => "Network connect timeout error");

    /**
     * Изменение текущих ссылок
     */
    function url_update($add = Array(), $exclude_pattern = '/^$/', $rootUrl='') {
        if(!$rootUrl){
            $rootUrl=\e::config('APPLICATION_URL'). '/index.php?';
        }
        $data = array_merge(\e::query_array($exclude_pattern), $add);
        // echo "<!-- "; print_r($data);echo " -->";
        return $rootUrl . http_build_query($data);
    }

    /**
     * Составление ссылок
     */
    function url($data = Array(), $rootUrl='') {
        if(!$rootUrl){
            $rootUrl=\e::config('APPLICATION_URL');
        }
        return $rootUrl. '/index.php?' . http_build_query($data);
    }

    /**
     * Составление ссылок
     */
    function url_public($data = Array(), $publicCmsUrl='') {
        
        if(!$publicCmsUrl){
            $publicCmsUrl=\e::config('APPLICATION_PUBLIC_URL');
        }
        if(is_array($data)){
            return $publicCmsUrl. '/index.php?' . http_build_query($data);
        }elseif(is_object($data)){
            return $publicCmsUrl. '/index.php?' . http_build_query((array)$data);
        }elseif(is_string($data)){
            return $publicCmsUrl. '/' . preg_replace("/^\\//",'',$data);
        }
    }

    /**
     * Составление ссылок
     */
    function url_admin($data = Array()) {
        if(is_array($data)){
            return \e::config('APPLICATION_ADMIN_URL'). '/index.php?' . http_build_query($data);
        }elseif(is_object($data)){
            return \e::config('APPLICATION_ADMIN_URL'). '/index.php?' . http_build_query((array)$data);
        }elseif(is_string($data)){
            return \e::config('APPLICATION_ADMIN_URL'). '/' . preg_replace("/^\\//",'',$data);
        }
    }
    

    /**
     * Редирект на заданный адрес
     */
    function redirect($URL) {
        header("Location: $URL");
        session_write_close();
        exit("redirecting...");
        return "redirecting...";
    }

    /**
     * Возврат кода ошибки HTTP
     */
    function http_error($code = 403, $msg = false) {
        if ($msg) {
            \e::msg_put('error', $msg);
        }
        if ($code === NULL) {
            $code = 200;
        }
        if (isset($this->http_status_codes[$code])) {
            $text = $this->http_status_codes[$code];
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header($protocol . ' ' . $code . ' ' . $text);
            //echo $protocol . ' ' . $code . ' ' . $text;
        } else {
            //exit('Unknown http status code "' . htmlentities($code) . '"');
        }
        //exit();
    }

    function url_current() {
        $pageURL = 'http';
        if ($_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }
    
    function url_from_template($tpl,$vars){
        if ($vars && is_array($vars) && count($vars) > 0) {
            $from = Array();
            $to = Array();
            foreach ($vars as $key => $val) {
                $from[] = "{" . $key . "}";
                $to[] = $val;
            }
            $tor = str_replace($from, $to, $tpl);
        }else{
            $tor=$tpl;
        }
        return $tor;
    }

    /**
     * Edited by Nitin Kr. Gupta, publicmind.in
     */
    /**
     * Copyright (c) 2008, David R. Nadeau, NadeauSoftware.com.
     * All rights reserved.
     *
     * Redistribution and use in source and binary forms, with or without
     * modification, are permitted provided that the following conditions
     * are met:
     *
     * 	* Redistributions of source code must retain the above copyright
     * 	  notice, this list of conditions and the following disclaimer.
     *
     * 	* Redistributions in binary form must reproduce the above
     * 	  copyright notice, this list of conditions and the following
     * 	  disclaimer in the documentation and/or other materials provided
     * 	  with the distribution.
     *
     * 	* Neither the names of David R. Nadeau or NadeauSoftware.com, nor
     * 	  the names of its contributors may be used to endorse or promote
     * 	  products derived from this software without specific prior
     * 	  written permission.
     *
     * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
     * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
     * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
     * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
     * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
     * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
     * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
     * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
     * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
     * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY
     * WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
     * OF SUCH DAMAGE.
     */
    /*
     * This is a BSD License approved by the Open Source Initiative (OSI).
     * See:  http://www.opensource.org/licenses/bsd-license.php
     */

    /**
     * Combine a base URL and a relative URL to produce a new
     * absolute URL.  The base URL is often the URL of a page,
     * and the relative URL is a URL embedded on that page.
     *
     * This function implements the "absolutize" algorithm from
     * the RFC3986 specification for URLs.
     *
     * This function supports multi-byte characters with the UTF-8 encoding,
     * per the URL specification.
     *
     * Parameters:
     * 	baseUrl		the absolute base URL.
     *
     * 	url		the relative URL to convert.
     *
     * Return values:
     * 	An absolute URL that combines parts of the base and relative
     * 	URLs, or FALSE if the base URL is not absolute or if either
     * 	URL cannot be parsed.
     */
    function url_to_absolute($baseUrl, $relativeUrl) {
        // If relative URL has a scheme, clean path and return.
        $r = $this->split_url($relativeUrl);
        if ($r === FALSE) {
            return FALSE;
        }
        if (!empty($r['scheme'])) {
            if (!empty($r['path']) && $r['path'][0] == '/') {
                $r['path'] = $this->url_remove_dot_segments($r['path']);
            }
            return $this->join_url($r);
        }

        // Make sure the base URL is absolute.
        $b = $this->split_url($baseUrl);
        if ($b === FALSE || empty($b['scheme']) || empty($b['host'])) {
            return FALSE;
        }
        $r['scheme'] = $b['scheme'];

        // If relative URL has an authority, clean path and return.
        if (isset($r['host'])) {
            if (!empty($r['path'])) {
                $r['path'] = $this->url_remove_dot_segments($r['path']);
            }
            return $this->join_url($r);
        }
        unset($r['port']);
        unset($r['user']);
        unset($r['pass']);

        // Copy base authority.
        $r['host'] = $b['host'];
        if (isset($b['port'])) {
            $r['port'] = $b['port'];
        }
        if (isset($b['user'])) {
            $r['user'] = $b['user'];
        }
        if (isset($b['pass'])) {
            $r['pass'] = $b['pass'];
        }

        // If relative URL has no path, use base path
        if (empty($r['path'])) {
            if (!empty($b['path'])) {
                $r['path'] = $b['path'];
            }
            if (!isset($r['query']) && isset($b['query'])) {
                $r['query'] = $b['query'];
            }
            return $this->join_url($r);
        }

        // If relative URL path doesn't start with /, merge with base path
        if ($r['path'][0] != '/') {
            $base = mb_strrchr($b['path'], '/', TRUE, 'UTF-8');
            if ($base === FALSE) {
                $base = '';
            }
            $r['path'] = $base . '/' . $r['path'];
        }
        $r['path'] = $this->url_remove_dot_segments($r['path']);
        return $this->join_url($r);
    }

    /**
     * Filter out "." and ".." segments from a URL's path and return
     * the result.
     *
     * This function implements the "remove_dot_segments" algorithm from
     * the RFC3986 specification for URLs.
     *
     * This function supports multi-byte characters with the UTF-8 encoding,
     * per the URL specification.
     *
     * Parameters:
     * 	path	the path to filter
     *
     * Return values:
     * 	The filtered path with "." and ".." removed.
     */
    function url_remove_dot_segments($path) {
        // multi-byte character explode
        $inSegs = preg_split('!/!u', $path);
        $outSegs = array();
        foreach ($inSegs as $seg) {
            if ($seg == '' || $seg == '.') {
                continue;
            }
            if ($seg == '..') {
                array_pop($outSegs);
            } else {
                array_push($outSegs, $seg);
            }
        }
        $outPath = implode('/', $outSegs);
        if ($path[0] == '/') {
            $outPath = '/' . $outPath;
        }
        // compare last multi-byte character against '/'
        if ($outPath != '/' &&
                (mb_strlen($path) - 1) == mb_strrpos($path, '/', 'UTF-8')) {
            $outPath .= '/';
        }
        return $outPath;
    }

    /**
     * This function parses an absolute or relative URL and splits it
     * into individual components.
     *
     * RFC3986 specifies the components of a Uniform Resource Identifier (URI).
     * A portion of the ABNFs are repeated here:
     *
     * 	URI-reference	= URI
     * 			/ relative-ref
     *
     * 	URI		= scheme ":" hier-part [ "?" query ] [ "#" fragment ]
     *
     * 	relative-ref	= relative-part [ "?" query ] [ "#" fragment ]
     *
     * 	hier-part	= "//" authority path-abempty
     * 			/ path-absolute
     * 			/ path-rootless
     * 			/ path-empty
     *
     * 	relative-part	= "//" authority path-abempty
     * 			/ path-absolute
     * 			/ path-noscheme
     * 			/ path-empty
     *
     * 	authority	= [ userinfo "@" ] host [ ":" port ]
     *
     * So, a URL has the following major components:
     *
     * 	scheme
     * 		The name of a method used to interpret the rest of
     * 		the URL.  Examples:  "http", "https", "mailto", "file'.
     *
     * 	authority
     * 		The name of the authority governing the URL's name
     * 		space.  Examples:  "example.com", "user@example.com",
     * 		"example.com:80", "user:password@example.com:80".
     *
     * 		The authority may include a host name, port number,
     * 		user name, and password.
     *
     * 		The host may be a name, an IPv4 numeric address, or
     * 		an IPv6 numeric address.
     *
     * 	path
     * 		The hierarchical path to the URL's resource.
     * 		Examples:  "/index.htm", "/scripts/page.php".
     *
     * 	query
     * 		The data for a query.  Examples:  "?search=google.com".
     *
     * 	fragment
     * 		The name of a secondary resource relative to that named
     * 		by the path.  Examples:  "#section1", "#header".
     *
     * An "absolute" URL must include a scheme and path.  The authority, query,
     * and fragment components are optional.
     *
     * A "relative" URL does not include a scheme and must include a path.  The
     * authority, query, and fragment components are optional.
     *
     * This function splits the $url argument into the following components
     * and returns them in an associative array.  Keys to that array include:
     *
     * 	"scheme"	The scheme, such as "http".
     * 	"host"		The host name, IPv4, or IPv6 address.
     * 	"port"		The port number.
     * 	"user"		The user name.
     * 	"pass"		The user password.
     * 	"path"		The path, such as a file path for "http".
     * 	"query"		The query.
     * 	"fragment"	The fragment.
     *
     * One or more of these may not be present, depending upon the URL.
     *
     * Optionally, the "user", "pass", "host" (if a name, not an IP address),
     * "path", "query", and "fragment" may have percent-encoded characters
     * decoded.  The "scheme" and "port" cannot include percent-encoded
     * characters and are never decoded.  Decoding occurs after the URL has
     * been parsed.
     *
     * Parameters:
     * 	url		the URL to parse.
     *
     * 	decode		an optional boolean flag selecting whether
     * 			to decode percent encoding or not.  Default = TRUE.
     *
     * Return values:
     * 	the associative array of URL parts, or FALSE if the URL is
     * 	too malformed to recognize any parts.
     */
    function split_url($url, $decode = FALSE) {
        // Character sets from RFC3986.
        $xunressub = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
        $xpchar = $xunressub . ':@% ';

        // Scheme from RFC3986.
        $xscheme = '([a-zA-Z][a-zA-Z\d+-.]*)';

        // User info (user + password) from RFC3986.
        $xuserinfo = '(([' . $xunressub . '%]*)' .
                '(:([' . $xunressub . ':%]*))?)';

        // IPv4 from RFC3986 (without digit constraints).
        $xipv4 = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

        // IPv6 from RFC2732 (without digit and grouping constraints).
        $xipv6 = '(\[([a-fA-F\d.:]+)\])';

        // Host name from RFC1035.  Technically, must start with a letter.
        // Relax that restriction to better parse URL structure, then
        // leave host name validation to application.
        $xhost_name = '([a-zA-Z\d-.%]+)';

        // Authority from RFC3986.  Skip IP future.
        $xhost = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
        $xport = '(\d*)';
        $xauthority = '((' . $xuserinfo . '@)?' . $xhost .
                '?(:' . $xport . ')?)';

        // Path from RFC3986.  Blend absolute & relative for efficiency.
        $xslash_seg = '(/[' . $xpchar . ']*)';
        $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
        $xpath_rel = '([' . $xpchar . ']+' . $xslash_seg . '*)';
        $xpath_abs = '(/(' . $xpath_rel . ')?)';
        $xapath = '(' . $xpath_authabs . '|' . $xpath_abs .
                '|' . $xpath_rel . ')';

        // Query and fragment from RFC3986.
        $xqueryfrag = '([' . $xpchar . '/?' . ']*)';

        // URL.
        $xurl = '^(' . $xscheme . ':)?' . $xapath . '?' .
                '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';


        // Split the URL into components.
        if (!preg_match('!' . $xurl . '!', $url, $m)) {
            return FALSE;
        }

        if (!empty($m[2])) {
            $parts['scheme'] = strtolower($m[2]);
        }

        if (!empty($m[7])) {
            if (isset($m[9])) {
                $parts['user'] = $m[9];
            } else {
                $parts['user'] = '';
            }
        }
        if (!empty($m[10])) {
            $parts['pass'] = $m[11];
        }

        if (!empty($m[13])) {
            $h = $parts['host'] = $m[13];
        } else if (!empty($m[14])) {
            $parts['host'] = $m[14];
        } else if (!empty($m[16])) {
            $parts['host'] = $m[16];
        } else if (!empty($m[5])) {
            $parts['host'] = '';
        }
        if (!empty($m[17])) {
            $parts['port'] = $m[18];
        }

        if (!empty($m[19])) {
            $parts['path'] = $m[19];
        } else if (!empty($m[21])) {
            $parts['path'] = $m[21];
        } else if (!empty($m[25])) {
            $parts['path'] = $m[25];
        }

        if (!empty($m[27])) {
            $parts['query'] = $m[28];
        }
        if (!empty($m[29])) {
            $parts['fragment'] = $m[30];
        }

        if (!$decode) {
            return $parts;
        }
        if (!empty($parts['user'])) {
            $parts['user'] = rawurldecode($parts['user']);
        }
        if (!empty($parts['pass'])) {
            $parts['pass'] = rawurldecode($parts['pass']);
        }
        if (!empty($parts['path'])) {
            $parts['path'] = rawurldecode($parts['path']);
        }
        if (isset($h)) {
            $parts['host'] = rawurldecode($parts['host']);
        }
        if (!empty($parts['query'])) {
            $parts['query'] = rawurldecode($parts['query']);
        }
        if (!empty($parts['fragment'])) {
            $parts['fragment'] = rawurldecode($parts['fragment']);
        }
        return $parts;
    }

    /**
     * This function joins together URL components to form a complete URL.
     *
     * RFC3986 specifies the components of a Uniform Resource Identifier (URI).
     * This function implements the specification's "component recomposition"
     * algorithm for combining URI components into a full URI string.
     *
     * The $parts argument is an associative array containing zero or
     * more of the following:
     *
     * 	"scheme"	The scheme, such as "http".
     * 	"host"		The host name, IPv4, or IPv6 address.
     * 	"port"		The port number.
     * 	"user"		The user name.
     * 	"pass"		The user password.
     * 	"path"		The path, such as a file path for "http".
     * 	"query"		The query.
     * 	"fragment"	The fragment.
     *
     * The "port", "user", and "pass" values are only used when a "host"
     * is present.
     *
     * The optional $encode argument indicates if appropriate URL components
     * should be percent-encoded as they are assembled into the URL.  Encoding
     * is only applied to the "user", "pass", "host" (if a host name, not an
     * IP address), "path", "query", and "fragment" components.  The "scheme"
     * and "port" are never encoded.  When a "scheme" and "host" are both
     * present, the "path" is presumed to be hierarchical and encoding
     * processes each segment of the hierarchy separately (i.e., the slashes
     * are left alone).
     *
     * The assembled URL string is returned.
     *
     * Parameters:
     * 	parts		an associative array of strings containing the
     * 			individual parts of a URL.
     *
     * 	encode		an optional boolean flag selecting whether
     * 			to do percent encoding or not.  Default = true.
     *
     * Return values:
     * 	Returns the assembled URL string.  The string is an absolute
     * 	URL if a scheme is supplied, and a relative URL if not.  An
     * 	empty string is returned if the $parts array does not contain
     * 	any of the needed values.
     */
    function join_url($parts, $encode = FALSE) {
        if ($encode) {
            if (isset($parts['user'])) {
                $parts['user'] = rawurlencode($parts['user']);
            }
            if (isset($parts['pass'])) {
                $parts['pass'] = rawurlencode($parts['pass']);
            }
            if (isset($parts['host']) && !preg_match('!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'])) {
                $parts['host'] = rawurlencode($parts['host']);
            }
            if (!empty($parts['path'])) {
                $parts['path'] = preg_replace('!%2F!ui', '/', rawurlencode($parts['path']));
            }
            if (isset($parts['query'])) {
                $parts['query'] = rawurlencode($parts['query']);
            }
            if (isset($parts['fragment'])) {
                $parts['fragment'] = rawurlencode($parts['fragment']);
            }
        }

        $url = '';
        if (!empty($parts['scheme'])) {
            $url .= $parts['scheme'] . ':';
        }
        if (isset($parts['host'])) {
            $url .= '//';
            if (isset($parts['user'])) {
                $url .= $parts['user'];
                if (isset($parts['pass'])) {
                    $url .= ':' . $parts['pass'];
                }
                $url .= '@';
            }
            if (preg_match('!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'])) {
                $url .= '[' . $parts['host'] . ']';
            } // IPv6
            else {
                $url .= $parts['host'];
            }   // IPv4 or name
            if (isset($parts['port'])) {
                $url .= ':' . $parts['port'];
            }
            if (!empty($parts['path']) && $parts['path'][0] != '/') {
                $url .= '/';
            }
        }
        if (!empty($parts['path'])) {
            $url .= $parts['path'];
        }
        if (isset($parts['query'])) {
            $url .= '?' . $parts['query'];
        }
        if (isset($parts['fragment'])) {
            $url .= '#' . $parts['fragment'];
        }
        return $url;
    }

    /**
     * This function encodes URL to form a URL which is properly 
     * percent encoded to replace disallowed characters.
     *
     * RFC3986 specifies the allowed characters in the URL as well as
     * reserved characters in the URL. This function replaces all the 
     * disallowed characters in the URL with their repective percent 
     * encodings. Already encoded characters are not encoded again,
     * such as '%20' is not encoded to '%2520'.
     *
     * Parameters:
     * 	url		the url to encode.
     *
     * Return values:
     * 	Returns the encoded URL string. 
     */
    //function encode_url($url) {
    //  $reserved = array(
    //    ":" => '!%3A!ui',
    //    "/" => '!%2F!ui',
    //    "?" => '!%3F!ui',
    //    "#" => '!%23!ui',
    //    "[" => '!%5B!ui',
    //    "]" => '!%5D!ui',
    //    "@" => '!%40!ui',
    //    "!" => '!%21!ui',
    //    "$" => '!%24!ui',
    //    "&" => '!%26!ui',
    //    "'" => '!%27!ui',
    //    "(" => '!%28!ui',
    //    ")" => '!%29!ui',
    //    "*" => '!%2A!ui',
    //    "+" => '!%2B!ui',
    //    "," => '!%2C!ui',
    //    ";" => '!%3B!ui',
    //    "=" => '!%3D!ui',
    //    "%" => '!%25!ui',
    //  );
    //
    //  $url = rawurlencode($url);
    //  $url = preg_replace(array_values($reserved), array_keys($reserved), $url);
    //  return $url;
    //}
}

//==============================================================================
/**
 * Класс для записи логов
 */
class log {

    private $prev_time = false;
    private $first_time = false;

    function __construct($config_file_path) {
        require_once \e::config('SCRIPT_ROOT') . '/lib/log4php/Logger.php';
        Logger::configure($config_file_path);
        spl_autoload_register('__autoload');
        // registration of the __autoload function (see core.php) is required
        // because Logger class contains spl_autoload_register(...) call
        // spl_autoload_register('__autoload');
        //var_dump(spl_autoload_functions());
    }

    // DEBUG < INFO < WARN < ERROR < FATAL
    // \e::debug("Hello World!");
    // \e::info(\e::v());
    // \e::warn(\e::v());
    // \e::error("Hello World!");
    // \e::fatal("Hello World!");

    function debug($data, $path = '') {
        //echo "debug $path<br>";
        Logger::getLogger($path)->debug($data);
    }

    function info($data, $path = '') {
        //echo "info $path<br>";
        Logger::getLogger($path)->info($data);
    }

    function warn($data, $path = '') {
        //echo "warn $path<br>";
        Logger::getLogger($path)->warn($data);
    }

    function error($data, $path = '') {
        //echo "error $path<br>";
        Logger::getLogger($path)->error($data);
    }

    function fatal($data, $path = '') {
        //echo "fatal $path<br>";
        Logger::getLogger($path)->fatal($data);
    }

    function isDebugEnabled($path = '') {
        return Logger::getLogger($path)->isDebugEnabled();
    }

    function show_time() {
        $tmp = explode(' ', microtime());
        $tmp = $tmp[1] + $tmp[0];
        if ($this->first_time === false) {
            $this->first_time = $tmp;
            $this->prev_time = $tmp;
        }
        $report = ( $tmp - $this->first_time ) . ' (+' . ( $tmp - $this->prev_time ) . ' )';
        $this->prev_time = $tmp;
        return $report;
    }

}

//==============================================================================
/**
 * Подключение к сессии
 * Можно заменять имя cookie, который используется для передачи session_id
 */
class session {

    private $null = null;

    function __construct() {
        $session_cookie_name = \e::config('PHPSESSID');
        // echo($session_cookie_name."=".\e::config('PHPSESSID'));
        session_name($session_cookie_name);
        if (\e::cookies($session_cookie_name)) {
            $session_cookie = \e::cookies($session_cookie_name);
            if (strlen($session_cookie) > 0) {
                $GLOBALS['_COOKIE'][$session_cookie_name] = $session_cookie;
                session_id($session_cookie);
            }
        }
        session_start();

        if (!isset($_SESSION['code'])) {
            $_SESSION['code'] = [];
        }
        //var_dump($_SESSION);
    }

    function &session($path = '', $val = null) {
        if (strlen($path) == 0) {
            return $this;
        }

        if ($val === null) {
            // read property
            if (strlen($path) == 0) {
                return $this;
            } else {
                $path_exploded = preg_split("/[,.;]/", $path);
                // \e::info('session', $path_exploded);
                $ptr = $_SESSION;
                foreach ($path_exploded as $el) {
                    if (isset($ptr[$el])) {
                        $ptr = $ptr[$el];
                    } else {
                        return $this->null;
                    }
                }
                return $ptr;
            }
        } else {
            // write property
            $path_exploded = preg_split("/[,.;]/", $path);
            // \e::info('session', $path_exploded);
            $ptr = &$_SESSION;
            //\e::info('session', $ptr);
            //var_dump($this);
            $cnt = count($path_exploded) - 1;
            //\e::info('session', '$cnt=' . $cnt);
            for ($i = 0; $i < $cnt; $i++) {

                $el = $path_exploded[$i];
                //\e::info('session', "$i ==>> $el");

                if (!isset($ptr[$el])) {
                    //\e::info('session', "creating ==>> $el");
                    $ptr[$el] = Array();
                }
                $ptr = &$ptr[$el];
            }
            $ptr[$path_exploded[$cnt]] = $val;
            //\e::info('session', $_SESSION); exit();
            return $val;
        }
    }

    function code_create($codepath) {
        $code = sha1(\e::config('salt') . '-' . time() . '-' . $codepath);
        //$code = (\e::config('salt') .'-'. time() .'-'. $codepath);
        $_SESSION['code'][$codepath] = $code;
        //\e::info("creating code _SESSION[code][{$codepath}]={$_SESSION['code'][$codepath]}");
        return $code;
    }

    function code_is_valid($parameter_name, $codepath) {
        $code1 = \e::request($parameter_name, '');
        $code2 = isset($_SESSION['code'][$codepath]) ? $_SESSION['code'][$codepath] : '';
        //\e::info("checking code / request[$parameter_name]=>$code1 / session($codepath)=> $code2;");
        return (strlen($code1) > 0 && $code1 == $code2);
    }

}

//==============================================================================
/**
 * Класс для работы с базой данных
 */
class db {

    //----------------------------- mysql functions - begin --------------------
    private $db;
    public $db_debug = false;

    /**
     * Запоминаем параметры
     */
    function __construct($db_host, $db_user, $db_pass, $db_name, $db_charset, $db_table_prefix, $db_port = 3306) {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_pass = $db_pass;
        $this->db_name = $db_name;
        $this->db_charset = $db_charset;
        $this->db_table_prefix = $db_table_prefix;
        $this->db_port = $db_port;
    }

    /**
     * Подключение к базе данных
     */
    private function db_connect() {
        $this->db = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name, $this->db_port);
        if ($this->db->connect_errno) {
            $this->error = "Failed to connect to MySQL: (" . $this->db->connect_errno . ") " . $this->db->connect_error;
            $this->db = false;
            return false;
        } else {
            $this->db_execute("set names " . $this->db_charset);
            return $this->db;
        }
    }

    /**
     * Отключение от базы данных
     */
    function db_close() {
        if ($this->db) {
            $this->db->close();
        }
    }

    /**
     * Выполнение запроса SQL и подключение, если надо
     */
    function db_execute($query, $args = Array(), $debug = false) {

        // Проверяем подключение к БД
        if (!isset($this->db)) {
            $this->db_connect();
            if (!$this->db) {
                die('DB connect error ' . $this->error);
            }
        }
        // преобразуем запрос
        $q = $this->db_query($query, $args, $debug);
        // выполняем запрос
        $result_id = $this->db->query($q);
        // печатаем сообщение об ошибке
        if (!$result_id) {
            \e::error(htmlspecialchars($q) . '<br>' . $this->db->error);
        }
        return $result_id;
    }

    /**
     * конструирование запроса с подстановкой параметров
     * подстановочные знаки:
     * <<tp>> => $this->db_table_prefix
     * <<string varname>> => '$varname'  - string
     * <<integer varname>> => $varname    - integer
     * <<float varname>> => '$varname'  - float
     * <<integer[] varname>> => $varname   - list of integers
     * <<string[] varname>> => $varname   - list of strings
     */
    function db_query($sqltemplate, $args = Array(), $debug = false) {
        $q = trim(str_replace('<<tp>>', $this->db_table_prefix, $sqltemplate));
        //var_dump($q);
        $tmp = explode('<<', $q);
        // var_dump($args);
        $cnt = count($tmp);
        for ($i = 1; $i < $cnt; $i++) {
            $part = explode('>>', $tmp[$i]);
            $var = preg_split("/ +/", $part[0]);
            // var_dump($var);
            switch ($var[0]) {
                case 'integer':
                case 'int':
                case 'long':
                case 'short':
                case 'word':
                case 'byte':
                    $val = $this->map_integer(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'float':
                case 'double':
                case 'decimal':
                    $val = $this->map_decimal(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'datetime':
                    $val = $this->map_datetime(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'date':
                    $val = $this->map_date(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'time':
                    $val = $this->map_time(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'time[]':
                    $val = $this->map_timelist(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'datetime[]':
                    $val = $this->map_datetimelist(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'date[]':
                    $val = $this->map_datelist(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'integer[]':
                case 'int[]':
                    $val = $this->map_intlist(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                case 'string[]':
                case 'varchar[]':
                case 'text[]':
                    $val = $this->map_strlist(isset($args[$var[1]]) ? $args[$var[1]] : null);
                    break;
                default:
                    $val = isset($args[$var[1]]) ? "'" . $this->db_escape($args[$var[1]]) . "'" : "null";
                    break;
            }
            $tmp[$i] = $val . $part[1];
        }
        $query = join('', $tmp);
        if ($debug) {
            \e::info($query);
        }
        return $query;
    }

    private function map_integer($arg) {
        $val = (int) $arg;
        return $val;
    }

    private function map_decimal($arg) {
        $val = floatval(str_replace(',', '.', $arg));
        return $val;
    }

    private function map_datetime($arg) {
        $val = "null";
        if ($arg) {
            $timestamp = strtotime($arg);
            if ($timestamp !== false) {
                $val = date("'Y-m-d H:i:s'", $timestamp);
            }
        }
        return $val;
    }

    private function map_date($arg) {
        $val = "null";
        if (isset($arg)) {
            // echo $args[$var[1]]."<hr>";
            $timestamp = strtotime($arg);
            if ($timestamp !== false) {
                $val = date("'Y-m-d'", $timestamp);
            }
        }
        return $val;
    }

    private function map_time($tmp) {
        $val = "null";
        if ($tmp) {
            if (preg_match("/(\\d{1,2}):(\\d{1,2})/", $tmp, $matches)) {
                $val = "'" . $matches[1] . ':' . $matches[2] . ':00' . "'";
            } elseif (preg_match("/(\\d{1,2}):(\\d{1,2}):(\\d{1,2})/", $tmp, $matches)) {
                $val = "'" . $matches[1] . ':' . $matches[2] . ':' . $matches[3] . "'";
            } else {
                $val = 'null';
            }
        }
        return $val;
    }

    private function map_timelist($tmp1) {
        if ($tmp1) {
            $cnt1 = array_keys($tmp1);
            foreach ($cnt1 as $key) {
                $v = "null";
                if (preg_match("/(\\d{1,2}):(\\d{1,2})/", $tmp1[$key], $matches)) {
                    $v = "'" . $matches[1] . ':' . $matches[2] . ':00' . "'";
                } elseif (preg_match("/(\\d{1,2}):(\\d{1,2}):(\\d{1,2})/", $tmp1[$key], $matches)) {
                    $v = "'" . $matches[1] . ':' . $matches[2] . ':' . $matches[3] . "'";
                } else {
                    $v = 'null';
                }
                $tmp1[$key] = $v;
            }
            $val = join(",", $tmp1);
        } else {
            $val = "null";
        }
        return $val;
    }

    private function map_datetimelist($tmp1) {
        if ($tmp1) {
            $cnt1 = array_keys($tmp1);
            foreach ($cnt1 as $key) {
                $v = "null";
                $timestamp = strtotime($tmp1[$key]);
                if ($timestamp !== false) {
                    $v = date("'Y-m-d H:i:s'", $timestamp);
                }
                $tmp1[$key] = $v;
            }
            $val = join(",", $tmp1);
        } else {
            $val = "null";
        }
        return $val;
    }

    private function map_datelist($tmp1) {
        if ($tmp1) {
            $cnt1 = array_keys($tmp1);
            foreach ($cnt1 as $key) {
                $v = "null";
                $timestamp = strtotime($tmp1[$key]);
                if ($timestamp !== false) {
                    $v = date("'Y-m-d'", $timestamp);
                }
                $tmp1[$key] = $v;
            }
            $val = join(",", $tmp1);
        } else {
            $val = "null";
        }
        return $val;
    }

    private function map_strlist($tmp1) {
        if ($tmp1) {
            $cnt1 = array_keys($tmp1);
            foreach ($cnt1 as $key) {
                $tmp1[$key] = $this->db_escape($tmp1[$key]);
            }
            $val = "'" . join("','", $tmp1) . "'";
        } else {
            $val = "''";
        }
        return $val;
    }

    private function map_intlist($arg) {
        if ($arg) {
            //echo '<hr>'.$arg.'<hr>';
            if (is_array($arg)) {
                $cnt = array_keys($arg);
                $tor = Array();
                foreach ($cnt as $key) {
                    $tor[$key] = (int) $arg[$key];
                }
            } else {
                $tmp = preg_split("/ *; *| *, *| +/", $arg);
                $cnt = count($tmp);
                $tor = Array();
                for ($i = 0; $i < $cnt; $i++) {
                    $tor[] = (1 * str_replace(",", ".", $tmp[$i]));
                }
            }
            $val = join(',', $tor);
        } else {
            $val = ' 0';
        }
        return $val;
    }

    /**
     * Выполняет запрос на выборку данных
     * и возвращает результат в виде массива строк
     */
    function db_getrows($query, $args = Array(), $debug = false) {
        //prn("\e::db_getrows($query)");
        $res = $this->db_execute($query, $args, $debug);
        $res->data_seek(0);
        $tor = Array();
        while ($row = $res->fetch_assoc()) {
            $tor[] = $row;
        }
        $res->free();
        return $tor;
    }

    /**
     * Выполняет запрос на выборку данных
     * и возвращает 1-ю строку
     */
    function db_getonerow($query, $args = Array(), $debug = false) {
        $res = $this->db_execute($query, $args, $debug);
        if ($res) {
            $row = $res->fetch_assoc();
            $res->free();
            return $row;
        } else {
            return false;
        }
    }

    function db_escape($ffff) {
        // Проверяем подключение к БД
        if (!isset($this->db)) {
            $this->db_connect();
            if (!$this->db) {
                die('DB connect error ' . $this->error);
            }
        }
        return $this->db->real_escape_string($ffff);
    }

    function db_connection() {
        return $this;
    }

    function db_get_associated_array($sql) {
        $tor = Array();
        $tmp = \e::db_getrows($sql);
        if (!$tmp)
            return $tor;
        foreach ($tmp as $tm) {
            $tm = array_values($tm);
            if (!isset($tm[1]))
                $tm[1] = $tm[0];
            $tor[$tm[0]] = $tm[1];
        }
        return $tor;
    }

    //----------------------------- mysql functions - end ----------------------
}

//==============================================================================
/**
 * реестр всех классов, определённых в системе
 */
class registry {

    /**
     * Реестр классов: обработчики событий, классы страниц
     * Реестр хранит имя класса и его предков/интерфейсы
     */
    private $class_registry;

    /**
     * Файл с кешем
     */
    private $cache_path;

    /**
     * Место хранения файлов
     */
    private $classes_path;

    /**
     * Не кешировать
     */
    private $cachetime;

    /**
     * По имени получить список классов
     * $_types - имя предков класса
     * $_name  - имя класса
     */
    public function get_classes($_types, $_name = '') {
        // \e::debug($this->class_registry); exit();
        $tor = Array();

        // -------------------- просмотреть реестр - начало --------------------
        if (is_array($_types)) {
            $ntypes = count($_types);
            if ($_name == '') {
                // ------------ имя класса не задано - начало ------------------
                foreach ($this->class_registry as $classname => $classtype) {
                    if (count(array_intersect($classtype, $_types)) == $ntypes) {
                        $tor[$classname] = $classname;
                    }
                }
                // ------------ имя класса не задано - конец -------------------
            } else {
                // ------------ имя класса известно - начало -------------------
                if (count(array_intersect($_types, self::$object->class_registry[$_name])) == $ntypes) {
                    $tor[$_name] = $_name;
                }
                // ------------ имя класса известно - конец --------------------
            }
        } else {
            if ($_name == '') {
                // ------------ имя класса не задано - начало ------------------
                foreach ($this->class_registry as $classname => $classtype) {
                    if (in_array($_types, $classtype)) {
                        $tor[$classname] = $classname;
                    }
                }
                // ------------ имя класса не задано - конец -------------------
            } else {
                // ------------ имя класса задано - начало ---------------------

                if (isset($this->class_registry[$_name])) {
                    if (in_array($_types, $this->class_registry[$_name])) {
                        $tor[$_name] = $_name;
                    }
                }
                // ------------ имя класса задано - конец ----------------------
            }
        }
        // -------------------- просмотреть реестр - конец ---------------------
        // prn($_types,$_name,$tor);
        return $tor;
    }

    /**
     * По имени проверить, существует ли класс
     * $_name  - имя класса
     */
    public function class_exists($_name) {
        return isset($this->class_registry[$_name]);
    }

    /**
     * Вызов всех обработчиков событий:
     * $out=fire_event($event_name,$data);
     * $event_name - имя интерфейса обработчиков события
     * $data - входные данные для обработчиков
     */
    function fire($event_name, $data, $verbose = false) {

        $evname = str_replace('/', "\\", $event_name);
        //\e::info('registry', "Fire event $evname");
        $classes = $this->get_classes(Array($evname, 'event'));
        //\e::info('registry', $classes);
        foreach ($classes as $cls) {
            $data = call_user_func(Array($cls, 'onEvent'), $data);
            if ($verbose) {
                \e::info("Call $cls", 'registry');
                \e::info($data, 'registry');
            }
        }
        return $data;
    }

    /**
     * Создание объекта и загрузка реестра в память
     */
    function __construct($cache_path, $classes_path, $cachetime = 3600) {
        $this->cache_path = $cache_path; // env::config('CACHE_ROOT') . '/class_registry.txt';
        $this->classes_path = $classes_path; // env::config('SCRIPT_ROOT');
        $this->cachetime = $cachetime;

        // -------------------- загрузить реестр классов - начало --------------
        if (!isset($this->class_registry)) {
            // в высоконагруженных системах
            // можно использовать другой механизм кеширования
            // например, MemCacheDB
            if (!file_exists($this->cache_path) || (time() - filemtime($this->cache_path)) > $this->cachetime) {
                // -------------------- создаём кеш - начало -------------------
                $this->create_class_registry($this->classes_path);
                file_put_contents($this->cache_path, serialize($this->class_registry));
                // -------------------- создаём кеш - конец --------------------
            } else {
                // -------------------- читаем кеш - начало --------------------
                $this->class_registry = unserialize(file_get_contents($this->cache_path));
                // -------------------- читаем кеш - начало --------------------
            }
        }
        // -------------------- загрузить реестр классов - конец ---------------
        //\e::debug('registry', $this->class_registry);
    }

    function class_definition_exists($str) {
        echo($this->classes_path . '-----------' . $str);
        exit();
    }

    /**
     * Анализ содержимого директории с классами
     * для поиска всех обработчиков событий
     * и всех классов страниц
     */
    function create_class_registry($event_classes_path) {
        // \e::debug('registry', "create_event_listener_registry($event_classes_path)");
        // ------------------ get file list - begin ----------------------------
        $ln = strlen($event_classes_path);
        $tmp = \core\fileutils::ls_r($event_classes_path);

        $class_list = Array();
        foreach ($tmp as $entry) {
            if (preg_match("/\\.class\\.php$/", $entry)) {
                $entry = str_replace($this->classes_path . '/', '', $entry);
                $entry = preg_replace("/\\.class\\.php$/", '', $entry);
                $entry = str_replace('/', "\\", $entry);
                $class_list[] = $entry;
            }
        }
        sort($class_list);

        //\e::debug('registry', $class_list);exit();
        // ------------------ get file list - end ------------------------------
        // ------------------ collect class metainfo data - begin --------------
        $this->class_registry = Array();
        foreach ($class_list as $classname) {
//            
//            $code=  token_get_all(file_get_contents($classname));
//            
//            // get namespace
//            $namespace='\\';
//            $flag=false;
//            for($i=0,$cnt=count($code);$i<$cnt; $i++){
//                if(is_array($code[$i]) && $code[$i][1]=='namespace'){
//                    $flag=true;
//                    continue;
//                }
//                if($flag && $code[$i]==';'){
//                    break;
//                }
//                if($flag){
//                    $namespace.=trim($code[$i][1]);
//                }
//            }
//            // \e::debug('registry', $namespace);
//            $classname=preg_replace("/\\.class\\.php$/", '', basename($classname));
//            $classpath=$namespace.'\\'.$classname;
//            // \e::debug('registry', $classpath);
//            
//            
//            $flag=false;
//            $declaration=false;
//            for ($i = 0, $cnt = count($code); $i < $cnt; $i++) {
//                if (is_array($code[$i]) && token_name($code[$i][0])=='T_CLASS') {
//                    $flag = 'class';
//                    $declaration=$flag;
//                    continue;
//                }
//                if (is_array($code[$i]) && token_name($code[$i][0])=='T_INTERFACE') {
//                    $flag = 'interface';
//                    
//                    continue;
//                }
//                if (is_array($code[$i]) && $flag && $code[$i][1] == $classname) {
//                    $declaration=$flag.' '.$classname;
//                    continue;
//                }
//                if (is_array($code[$i]) && $flag && $code[$i] == '') {
//                    break;
//                }
//                if ($flag) {
//                    $namespace.=trim($code[$i][1]);
//                }
//            }
//
//            \e::debug('registry', $code);exit();
            try {
                $class = new ReflectionClass($classname);
                // интерфейс в реестр не вносим
                if ($class->isInterface()) {
                    continue;
                }

                $this->class_registry[$classname] = $class->getInterfaceNames();
                $tmp = $class->getParentClass();
                if ($tmp) {
                    $this->class_registry[$classname][] = $tmp->getName();
                }
            } catch (Exception $e) {
                \e::warn("$classname reflection error " . $e->getMessage(), 'registry');
            }
        }
        // ------------------ collect class metainfo data - end ----------------
        // ------------------ get all ancestors - begin ------------------------
        $cnt = array_keys($this->class_registry);
        foreach ($cnt as $key) {
            do {
                $node = $this->class_registry[$key];
                $prev = count($node);
                //prn($prev);
                foreach ($node as $pa) {
                    if (isset($this->class_registry[$pa])) {
                        $this->class_registry[$key] = array_unique(array_merge($this->class_registry[$key], $this->class_registry[$pa]));
                        //prn($this->class_registry[$pa],$this->class_registry[$key]);
                    }
                }
            } while (count($this->class_registry[$key]) > $prev);
        }
        // ------------------ get all ancestors - end --------------------------
        //\e::debug($this->class_registry);
    }

}

/**
 * Интерфейс обработчика события
 * каждый обработчик опознаётся именно по наличию этого интерфейса
 */
interface event {

    static function onEvent($data);
}

/**
 * Интерфейс, который помечает обработчиков события "Завершение приложения"
 */
interface event_finish extends event {
    
}

/**
 * Интерфейс, который помечает обрабочиков события "Старт приложения"
 */
interface event_start extends event {
    
}

//==============================================================================
/**
 * Класс описывает структуру данных для меню
 */
class menu {

    /**
     * Заголовок меню
     */
    public $menutitle = '---';

    /**
     * Пункты меню
     */
    public $menuitems = Array();

    /**
     * Признак, по которому упорядочивается коллекция меню
     */
    public $menuweight = 0;

    /**
     * Конструктор заголовка меню
     * @param $title string видимый текст, название меню
     * @param $parameters array список дополнительных параметров, которые надо сохранить в объекте
     */
    function __construct($title = '---', $parameters = Array(), $menuweight = 0) {
        $this->menutitle = $title;
        $this->menuitems = Array();
        $this->menuweight = $menuweight;
        foreach ($parameters as $key => $val) {
            if (is_object($val)) {
                $tmp = Array();
                foreach ($val as $k => $v) {
                    $tmp[$k] = $v;
                }
                $this->$key = &$tmp;
            } else {
                $this->$key = $val;
            }
        }
    }

}

/**
 * Пункт навигационного меню
 */
class menuitem {

    /**
     * URL пункта меню
     */
    public $url;

    /**
     * Видимая часть пункта меню
     * код HTML
     */
    public $innerHTML;

    /**
     * Дополнительные атрибуты
     * код HTML
     */
    public $attributes;

    /**
     * Номер по порядку
     */
    public $menuweight;

    /**
     * __construct($_url, $_innerHTML, $_attributes = Array(), $weight=0)
     */
    public function __construct($_url, $_innerHTML, $_attributes = Array(), $weight = 0) {
        $this->url = $_url;
        $this->innerHTML = $_innerHTML;
        $this->attributes = $_attributes;
        $this->menuweight = $weight;
    }

}

/**
 * Класс создаёт пустую структуру данных для меню
 * и вызывает событие "создание меню Х"
 */
class menufactory {

    function menu($event_id, $title = '---', $parameters = Array()) {
        $groupmenu = new \menu($title, $parameters);
        $groupmenu = \e::fire($event_id, $groupmenu);
        uasort($groupmenu->menuitems, function($a, $b) {
            return $a->menuweight - $b->menuweight;
        });
        return $groupmenu;
    }

}

//==============================================================================
/**
 * Description of viewfactory
 *
 * @author dobro
 */
class viewfactory {

    private $skin;
    private $defaultskin;

    function __construct($skin = 'index') {
        $this->skin = $skin;
        $this->defaultskin = 'index';
    }

    function get_view($modulename) {
        // load view
        $tmp = $modulename . "\\" . $this->skin;
        if (\e::class_exists($tmp)) {
            $view = new $tmp();
        }
        if (!isset($view)) {
            $tmp = $modulename . "\\" . $this->defaultskin;
            $view = new $tmp();
        }
        return $view;
    }

}

//==============================================================================
/**
 * Класс для сообщений от системы пользователю
 * @author dobro
 */
class notifiermodel {

    /**
     * Конструирует класс для сообщений.
     * Запоминает сессию и подключение к базе данных.
     */
    function __construct() {
        if (!\e::session('msg') || !is_array(\e::session('msg'))) {
            \e::session('msg', []);
        }
    }

    /**
     * Получить все последние сообщения, для текущего пользователя
     */
    function msg_get() {
        $tor = \e::session('msg');
        // \e::info($tor); exit();
        \e::session('msg', []);
        return $tor;
    }

    /**
     * Отправить сообщение для текущего пользователя.
     */
    function msg_put($style, $str) {
        $tor = \e::session('msg');
        if (!$tor || !is_array($tor)) {
            \e::session('msg', []);
            $tor = \e::session('msg');
        }
        $tor[] = ['class' => $style, 'message' => $str, 'datetime' => date('Y-m-d H:i')];
        \e::session('msg', $tor);
    }

    /**
     * count user notifications
     */
    function msg_count($user_id = 0) {
        $tor = \e::session('msg');
        return ( $tor && is_array($tor) ) ? count($tor) : 0;
    }

}

/**
 * Класс для сообщений интерфейса на избранном языке
 */
class gettext {

    /**
     * Список сообщений
     */
    private $langstring;

    /**
     * Файл с кешем
     */
    private $langstring_cache;

    /**
     * Директория, в которой надо искать сообщения
     */
    private $langstring_dir;

    /**
     * Время жизни кеша в секундах
     */
    private $cachetime;

    /**
     * Текущий язык
     */
    private $lang;

    /**
     * Конструируем список сообщений
     */
    function __construct($langstring_cache, $langstring_dir, $cachetime) {

        $this->langstring_cache = $langstring_cache;
        $this->langstring_dir = $langstring_dir;
        $this->cachetime = $cachetime;


        // -------------------- загрузить реестр сообщений - начало ------------
        if (!isset($this->langstring)) {
            // в высоконагруженных системах
            // можно использовать другой механизм кеширования
            // например, MemCacheDB
            if (!file_exists($this->langstring_cache) || (time() - filemtime($this->langstring_cache)) > $this->cachetime) {
                // -------------------- создаём кеш - начало -------------------
                $this->load_langstrings($this->langstring_dir);
                file_put_contents($this->langstring_cache, serialize($this->langstring));
                // -------------------- создаём кеш - конец --------------------
            } else {
                // -------------------- читаем кеш - начало --------------------
                $this->langstring = unserialize(file_get_contents($this->langstring_cache));
                // -------------------- читаем кеш - начало --------------------
            }
        }
        // -------------------- загрузить реестр сообщений - конец -------------
        // default language code
        $lang = \e::config('default_language');

        // if session doesn't contains language code ...
        if (!\e::session('lang')) {
            \e::session('lang', $lang);
        }

        // if new language code is posted like "lang=ukr"
        if (($newlang = \e::request('lang', '')) != '') {
            if (in_array($newlang, \e::available_languages())) {
                $lang = $newlang;
            }
        }
        // save determined language code
        \e::session('lang', $lang);
        $this->lang = $lang;
        // ---------------------- load language code - end ---------------------
    }

    /**
     * Коллекционируем сообщения из разных директорий
     */
    private function load_langstrings($path) {
        $this->langstring = Array();
        // ------------------ get file list - begin ----------------------------
        $ln = strlen($path);
        $tmp = \core\fileutils::ls_r($path);
        // var_dump($tmp);
        $filelist = Array();
        foreach ($tmp as $entry) {
            if (preg_match("/lang\\.([^.]+)\\.ini$/", $entry, $matches)) {
                $lang = $matches[1];
                $domain = dirname(str_replace($this->langstring_dir . '/', '', $entry));
                //$domain=preg_replace('/\W/','',$location);
                //echo "entry=$entry;<br>lang=$lang;<br>path=$location;<br>";
                if (!isset($this->langstring[$lang])) {
                    $this->langstring[$lang] = Array();
                }
                if (!isset($this->langstring[$lang][$domain])) {
                    $this->langstring[$lang][$domain] = Array();
                }
                $this->langstring[$lang][$domain] = array_merge($this->langstring[$lang][$domain], parse_ini_file($entry));
            }
        }
        // \e::debug('core.gettext', $this->langstring);
        // ------------------ get file list - end ------------------------------
    }

    /**
     * Возвращает текстовое сообщение
     * @param $msg_path идентификатор сообщения
     * @param $vars пары (ключ=>значение), которые задают правила подстановки параметров в текстовом сообщении
     */
    public function text($msg_path, $vars = false) {
        $domain = dirname($msg_path);
        $msg_name = basename($msg_path);
        //        if($verbose){
        //            \e::info($this->langstring[$this->lang]);
        //        }
        while (!isset($this->langstring[$this->lang][$domain][$msg_name]) && $domain != '.') {
            $domain = dirname($domain);
        }
        if (isset($this->langstring[$this->lang][$domain][$msg_name])) {
            $result = $this->langstring[$this->lang][$domain][$msg_name];
            $result = str_replace(Array("\\n"), Array("\n"), $result);
            if (is_array($vars) && count($vars) > 0) {
                $from = Array();
                $to = Array();
                foreach ($vars as $key => $val) {
                    $from[] = "{" . $key . "}";
                    $to[] = $val;
                }
                $result = str_replace($from, $to, $result);
            }
            return $result;
        } else {
            return $this->lang . ':' . $msg_path;
        }
    }

    public function gettext() {
        return $this->langstring;
    }

    /**
     * Показывает список доступных языков
     */
    public function available_languages() {
        return array_keys($this->langstring);
    }

    public function langstring($text, $lang = '') {
        if (strlen($lang) == 0) {
            $lang = $this->lang;
        }
        $last_match = false;
        $tmp = preg_split("/<\\/langstring>/i", $text);
        $cnt = count($tmp);
        if ($cnt <= 1) {
            return $text;
        }
        $cnt--;
        for ($i = 0; $i < $cnt; $i++) {
            if (preg_match("/<langstring +xml:lang=\"([^\"]*)\">(.*)/", $tmp[$i], $matches)) {
                $matches[2] = trim($matches[2]);
                if ($matches[1] == $lang && strlen($matches[2]) > 0) {
                    return $matches[2];
                }
                if (strlen($matches[2]) > 0) {
                    $last_match = $matches[2];
                }
            }
        }
        if ($last_match) {
            return $last_match;
        }
        return strip_tags($text);
    }

}

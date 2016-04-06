<?php

namespace core;

/**
 * Это класс - предок всех страниц
 */
abstract class page {

    /**
     * Заголовок окна браузера
     */
    public $page_title = 'Стартова сторінка';

    /**
     * Заголовок страницы
     */
    public $page_header = 'Стартова сторінка';

    /**
     * Основной текст страницы
     */
    public $page_content = '...';

    /**
     * Меню, зависящее от данной страницы
     */
    public $page_menu = Array();

    /**
     * Массив, который показывает положениие текущей страницы
     * в иерархии страниц сайта
     */
    public $page_breadcrumbs = Array();

    /**
     * Служебные сообщения
     */
    public $messages = Array();

    /**
     * Метаданные страницы
     */
    public $page_metadata = '';

    /**
     * Создание страницы.
     */
    function __construct($parameters = Array()) {
        foreach ($parameters as $key => $val) {
            $this->$key = $val;
        }
        $this->page_header = $this->page_title = '';// str_replace("\\", '/', get_class($this));
        $this->init();
    }

    /**
     * <pre>
     * Дополнительная работа по инициализации страницы
     * именно это метод надо переопределять:
     *  $this->page_title=...
     *  $this->page_header=...
     *  $this->page_content=...
     *  $this->page_menu=Array(...) < элементы типа menu
     *  $this->page_breadcrumbs=Array(...) < элементы типа menu
     *  $this->page_toolbar=Array(...) < элементы типа menuitem
     *  $this->messages=Array()
     * </pre>
     */
    public abstract function init();

    /**
     * показать страницу<br>
     * <pre>
     * // рисуем HTML
     * $this->page_metadata = \e::fire('page_metadata', $this->page_metadata);
     * //\e::debug('root', \e::instance());
     * //\e::debug('root', $this->page_metadata);
     * echo \e::draw_page($this);
     * // рисуем всплывающее окно
     * // $this->page_metadata = \e::fire('page_metadata', $this->page_metadata);
     * // echo \e::draw_popup_page($this);
     * // рисуем только содержимое страницы
     * // echo $this->page_content;
     * </pre>
     */
    public abstract function show();
}




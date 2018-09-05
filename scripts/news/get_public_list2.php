<?php

if (!function_exists('menu_news')) {
    run('news/menu');
}

if (!function_exists('get_site_info')) {
    run('site/menu');
}
if (!function_exists('db_get_template')) {
    run('site/page/page_view_functions');
}
if (!function_exists('category_info')) {
    run('category/functions');
}

$debug = false;

class CmsNewsViewer {

    private $options = false;
    private $rows_per_page = 10;
    private $show_abstracts = true;
    private $lang = false;
    private $site_id = false;
    private $_this_site_info = false;
    private $_tagSelector = false;
    private $_dateselector = false;
    private $_categoryselector = false;
    private $currentInputData = Array();
    private $category_id = false;
    private $_keywordselector = false;
    private $keywords = false;
    private $_list = false;
    private $start;

    // $this_site_info, $category_info, $start;
    //    protected $category_info, $start;
    //    protected $_list, $_pages, $items_found;
    //    protected $ordering = 'news.last_change_date DESC';
    //    protected $startname = 'news_start';
    //    protected $includeChildren = true;

    /**
     * <pre>
     * $options=[
     *     rows=> <integer>
     *     abstracts => true|false
     * ]
     * </pre>
     */
    public function __construct($options) {

        $this->options = $options;

        # -------------------- number of news in the block - begin -------------
        $this->rows_per_page = \e::config('rows_per_page');
        if (isset($this->options['rows'])) {
            $this->rows_per_page = (int) $this->options['rows'];
        }
        if ($this->rows_per_page <= 0 or $this->rows_per_page > 10000) {
            $this->rows_per_page = (int)\e::config('rows_per_page');
        }

        if ($this->rows_per_page <= 0 or $this->rows_per_page > 10000) {
            $this->rows_per_page = 10;
        }


        // update url parameters
        if ($this->rows_per_page != \e::config('rows_per_page')) {
            $this->currentInputData['rows'] = (int)$this->rows_per_page;
        }
        # -------------------- number of news in the block - end ---------------
        # 
        # 
        # ------------------------- page start - begin -------------------------
        $this->start = 0;
        if (isset($this->options['start'])) {
            $this->start = abs(round(1 * $this->options['start']));
            $this->currentInputData['start'] = $this->start;
        }
        # ------------------------- page start - end -----------------------------------
        # 
        # 
        # -------------------- if abstracts should be shown - begin ------------
        if (isset($this->options['abstracts'])) {
            if ($this->options['abstracts'] == 'no') {
                $this->show_abstracts = false;
            }
        }
        // update url parameters
        if (!$this->show_abstracts) {
            $this->currentInputData['abstracts'] = 'no';
        }
        # -------------------- if abstracts should be shown - end --------------
        #
        #
        #
        #
        #
        #
        #
        $this->lang = $this->lang($this->options);
        $this->currentInputData['lang'] = $this->lang;

        $this->text = load_msg($this->lang);

        $this->site_id = (int) ( $this->options['site_id'] );
        $this->_this_site_info = get_site_info($this->site_id);
        if (!$this->_this_site_info) {
            die($this->text['Site_not_found']);
        }
        $this->currentInputData['site_id'] = $this->site_id;


        if (isset($options['year']) && strlen(trim($options['year'])) > 0) {
            $this->year = (int) $options['year'];
            $this->currentInputData['year'] = $this->year;
        }

        if (isset($options['month']) && strlen(trim($options['month'])) > 0) {
            $this->month = (int) $options['month'];
            $this->currentInputData['month'] = $this->month;

            if (!isset($this->year)) {
                $this->year = (int) date('Y');
                $this->currentInputData['year'] = $this->year;
            }
        }

        if (isset($options['day']) && strlen(trim($options['day'])) > 0) {
            $this->day = (int) $options['day'];
            $this->currentInputData['day'] = $this->day;

            if (!isset($this->month)) {
                $this->month = (int) date('m');
                $this->currentInputData['month'] = $this->month;
            }
            if (!isset($this->year)) {
                $this->year = (int) date('Y');
                $this->currentInputData['year'] = $this->year;
            }
        }

        if (isset($options['tags']) && strlen(trim($options['tags'])) > 0) {
            $this->selectedTags = array_filter(preg_split("/,|;|\\./", $options['tags']), function($el) {
                return strlen(trim($el)) > 0;
            });
            $this->currentInputData['tags'] = join(',', $this->selectedTags);
        } else {
            $this->selectedTags = [];
        }

        if (isset($this->options['category_id']) && strlen($this->options['category_id']) > 0 && $this->options['category_id'] > 0) {
            $this->category_id = $this->options['category_id'];
            $this->currentInputData['category_id'] = $this->category_id;
        }


        if (isset($this->options['filtermode']) && strlen($this->options['filtermode']) > 0 && $this->options['filtermode'] > 0) {
            $this->filtermode = $this->options['filtermode'] ? true : false;
            $this->currentInputData['filtermode'] = $this->filtermode;
        } else {
            $this->filtermode = false;
        }


        if (isset($this->options['keywords']) && strlen($this->options['keywords']) > 0) {
            $this->keywords = strip_tags($this->options['keywords']);
            $this->currentInputData['keywords'] = $this->keywords;
        }
        
        if(isset($this->options['ordering'])){
           $this->setOrdering($this->options['ordering']);
        }
        if(isset($this->options['orderBy'])){
           $this->setOrdering($this->options['orderBy']);
        }
        if(isset($this->options['orderby'])){
           $this->setOrdering($this->options['orderby']);
        }
        if(isset($this->options['order'])){
           $this->setOrdering($this->options['order']);
        }
    }

    public function __get($attr) {
        //if (!isset($this->_list)) {
        //    $this->init();
        //}
        switch ($attr) {
            case 'site':
            case 'this_site_info':
                return $this->_this_site_info;

            case 'tag_selector':
            case 'tagselector':
            case 'tagSelector':
                if ($this->_tagSelector === false) {
                    $this->createTagSelector();
                }
                return $this->_tagSelector;

            case 'date_selector':
            case 'dateselector':
            case 'dateSelector':
                if ($this->_dateselector === false) {
                    $this->createDateSelector();
                }
                return $this->_dateselector;

            case 'category_selector':
            case 'categoryselector':
            case 'categorySelector':
                if ($this->_categoryselector === false) {
                    $this->createCategorySelector();
                }
                return $this->_categoryselector;

            case 'keyword_selector':
            case 'keywordselector':
            case 'keywordSelector':
            case 'keywords_selector':
            case 'keywordsselector':
            case 'keywordsSelector':
                if ($this->_keywordselector === false) {
                    $this->createKeywordSelector();
                }
                return $this->_keywordselector;

            case 'list':
            case 'news':
            case 'data':
                if ($this->_list === false) {
                    $this->createNewsList();
                }
                return $this->_list;

            //            case 'pages':
            //                return $this->_pages;
            //
            //            case 'dateselector':
            //                return $this->_dateselector;
            //
            //            case 'items_found':
            //                return $this->items_found;
            //
            //            case 'start':
            //                return $this->start + 1;
            //
            //            case 'finish':
            //                return min($this->start + $this->rows_per_page, $this->items_found);

            default: return Array();
        }
    }

    public function setRowsPerPage($val) {
        $this->rows_per_page = (int) $val;
        $this->currentInputData['rows'] = $this->rows_per_page;
        $this->_list = false;
        return '';
    }

    public function setFiltermode($val) {
        $this->filtermode = (boolean) $val;
        $this->currentInputData['filtermode'] = $this->filtermode;
        $this->_list = false;
        //echo "filtermode=$this->filtermode<br>";
        return '';//$this->filtermode?'+':'-';
    }

    public function setOrdering($val) {

        $this->currentInputData['ordering'] = Array();
        $tmp = explode(',', $val);
        //prn($val,$tmp);
        $ordering = Array();
        for ($i = 0, $cnt = count($tmp); $i < $cnt; $i++) {
            $tmp[$i] = preg_split('/ +/', trim($tmp[$i]));
            $tmp[$i][1] = (isset($tmp[$i][1]) && strtoupper($tmp[$i][1]) == 'DESC') ? 'DESC' : 'ASC';
            $tmp[$i][0] = trim(strtolower($tmp[$i][0]));
            switch ($tmp[$i][0]) {
                case 'date':
                case 'datetime':
                case 'last_change_date':
                    $this->currentInputData['ordering'][] = "date {$tmp[$i][1]}";
                    $ordering[] = 'news.last_change_date ' . $tmp[$i][1];
                    break;
                
                case 'expiration_date':
                    $this->currentInputData['ordering'][] = "expiration_date {$tmp[$i][1]}";
                    $ordering[] = 'news.expiration_date ' . $tmp[$i][1];
                    break;
                
                case 'weight':
                    $this->currentInputData['ordering'][] = "weight {$tmp[$i][1]}";
                    $ordering[] = 'news.weight ' . $tmp[$i][1];
                    break;
                case 'title':
                    $this->currentInputData['ordering'][] = "title {$tmp[$i][1]}";
                    $ordering[] = 'news.title ' . $tmp[$i][1];
                    break;
                case 'id':
                    $this->currentInputData['ordering'][] = "id {$tmp[$i][1]}";
                    $ordering[] = 'news.id ' . $tmp[$i][1];
                    break;
            }
        }
        //prn($ordering);
        $this->ordering = join(',', $ordering);

        $this->currentInputData['ordering'] = join(',', $this->currentInputData['ordering']);

        $this->_list = false;
        return '';
    }

    public function getLang(){
        return $this->lang;
    }

    public function url($updates) {

        $parameters = Array();

        // copy parameters excluding some ones
        foreach ($this->currentInputData as $key => $val) {
            if (!preg_match(\e::config('url_template_news_list_ignore_parameters'), $key)) {
                $parameters[$key] = $val;
            }
        }

        // apply updates
        foreach ($updates as $key => $val) {
            if (!preg_match(\e::config('url_template_news_list_ignore_parameters'), $key)) {
                $parameters[$key] = $val;
            }
        }

        // get parameters from basic template
        $tmp = explode('{', \e::config('url_template_news_list'));
        $cnt = count($tmp);
        $basicKeys = Array();
        for ($i = 1; $i < $cnt; $i++) {
            $key = explode('}', $tmp[$i]);
            $key = $key[0];
            if (isset($parameters[$key])) {
                $basicKeys[$key] = rawurlencode($parameters[$key]);
                unset($parameters[$key]);
            } else {
                $basicKeys[$key] = '';
            }
        }

        // get additional paramaters
        $other_parameters = '';
        foreach ($parameters as $key => $val) {
            $other_parameters.=str_replace(Array('{key}', '{value}'), Array($key, rawurlencode($val)), \e::config('url_template_news_list_other_parameters'));
        }
        $basicKeys['other_parameters'] = $other_parameters;

        // compose URL
        $tmp = explode('{', \e::config('url_template_news_list'));
        $cnt = count($tmp);
        for ($i = 1; $i < $cnt; $i++) {
            $tmp[$i] = explode('}', $tmp[$i]);
            $key = $tmp[$i][0];
            if (isset($basicKeys[$key])) {
                $tmp[$i][0] = $basicKeys[$key];
            } else {
                $tmp[$i][0] = '';
            }
            $tmp[$i] = join('', $tmp[$i]);
        }
        return join('', $tmp);
    }

    private function createKeywordSelector() {

        //    <form action="{$keyword_selector.action}" method=\"post\">
        //    <input type=text name="{$keyword_selector.name}" value="{{$keyword_selector.value}}">
        //    <input type=submit value="{$txt['Search']}">
        //    </form>

        $this->_keywordselector = Array(
            'action' => $this->url(Array('start'=>0,'keywords' => $this->keywords)),
            'name' => 'keywords',
            'value' => htmlspecialchars($this->keywords)
        );
    }

    private function createCategorySelector() {

        //  Smarty template:
        //    {if $category.parents}
        //      {foreach from=$category.parents item=parent}
        //        <div style="margin-left:{$parent.deep}0pt"><a href="{$parent.URL}">{$parent.category_title}</a></div>
        //      {/foreach}
        //    {/if}
        //    <div style="margin-left:{$category.deep}0pt; font-weight:bold;">{$category.category_title}</div>
        //
        //
        //    {if $category.children}
        //      <div style="margin-left:20px;">
        //      {foreach from=$category.children item=child}
        //        <div style="margin-left:{$category.deep}0pt"><a href="{$child.URL}">{$child.category_title}</a></div>
        //      {/foreach}
        //      </div>
        //    {/if}
        //
        //    <span style="display:inline-block;width:99%;margin-top:10pt;">
        //    {$category.category_description}
        //    <div style='text-align:right;'>{*$category.date_last_changed*}{$category.date_lang_update}</div>
        //    </span>
        // get current category
        $this->_categoryselector = category_info(Array(
            'category_id' => $this->category_id,
            'site_id' => $this->site_id
        ));
        if(!$this->_categoryselector){
            $this->_categoryselector = category_info(Array(
                'site_id' => $this->site_id
            ));
        }

        // get children
        $children = \e::db_getrows(
                "SELECT * FROM <<tp>>category 
                 WHERE site_id=" . ( (int) $this->_categoryselector['site_id']) . "  
                     AND is_visible
                     AND " . ( (int) $this->_categoryselector['start']) . " < `start` AND `finish` < " . ( (int) $this->_categoryselector['finish']) . "
                     AND deep=" . ( 1 + (int) $this->_categoryselector['deep']) . "
                 ORDER BY `start` ASC
                 "
        );
        for ($i = 0, $cnt = count($children); $i < $cnt; $i++) {
            $children[$i]['category_title'] = get_langstring($children[$i]['category_title'], $this->lang);
            $children[$i]['category_description'] = get_langstring($children[$i]['category_description'], $this->lang);
            $children[$i]['date_lang_update'] = get_langstring($children[$i]['date_lang_update'], $this->lang);
            $children[$i]['URL'] = $this->url(Array('start'=>'','keywords'=>'','category_id' => $children[$i]['category_id']));
        }
        $this->_categoryselector['children'] = $children;

        // get parents
        $parents = \e::db_getrows(
                "SELECT * FROM <<tp>>category 
                 WHERE site_id=" . ( (int) $this->_categoryselector['site_id']) . "  
                     AND is_visible
                     AND `start` < " . ( (int) $this->_categoryselector['start']) . " AND " . ( (int) $this->_categoryselector['finish']) . " < `finish`
                 ORDER BY `start` ASC
                 "
        );
        for ($i = 0, $cnt = count($parents); $i < $cnt; $i++) {
            $parents[$i]['category_title'] = get_langstring($parents[$i]['category_title'], $this->lang);
            $parents[$i]['category_description'] = get_langstring($parents[$i]['category_description'], $this->lang);
            $parents[$i]['date_lang_update'] = get_langstring($parents[$i]['date_lang_update'], $this->lang);
            $parents[$i]['URL'] = $this->url(Array('category_id' => $parents[$i]['category_id']));
        }
        $parents[0]['URL'] = $this->url(Array('start'=>'','keywords'=>'','category_id' => ''));

        $this->_categoryselector['parents'] = $parents;
        //prn($this->_categoryselector);
    }

    private function createDateSelector() {

        //  Smarty template
        //    {foreach from=$events->dateselector->parents item=dts}
        //    / <a href="{$dts.URL}">{$dts.innerHTML}</a>
        //    {/foreach}
        //    {if $events->dateselector->current.innerHTML}/ {$events->dateselector->current.innerHTML}{/if}
        //    <div>
        //    {foreach from=$events->dateselector->children item=dts}
        //    <a href="{$dts.URL}">{$dts.innerHTML}</a>
        //    {/foreach}
        //    </div>
        // ------------- date selector links - begin ---------------------------
        $this->_dateselector = new stdClass();
        $this->_dateselector->parents = Array();
        $this->_dateselector->current = Array();
        $this->_dateselector->children = Array();

        if (isset($this->day)) {
            $month_names = Array(-1 => '--',
                1 => text('month_January'),
                2 => text('month_February'),
                3 => text('month_March'),
                4 => text('month_April'),
                5 => text('month_May'),
                6 => text('month_June'),
                7 => text('month_July'),
                8 => text('month_August'),
                9 => text('month_September'),
                10 => text('month_October'),
                11 => text('month_November'),
                12 => text('month_December'));
            $this->_dateselector->parents[] = Array(
                'URL' => $this->url(Array('day' => '', 'month' => '', 'year' => '', 'start' => ''))
                , 'innerHTML' => text('All_dates')
            );
            $this->_dateselector->parents[] = Array(
                'URL' => $this->url(Array('day' => '', 'month' => '', 'year' => $this->year, 'start' => ''))
                , 'innerHTML' => $this->year
            );
            $this->_dateselector->parents[] = Array(
                'URL' => $this->url(Array('day' => '', 'month' => $this->month, 'year' => $this->year, 'start' => ''))
                , 'innerHTML' => $month_names[$this->month]
            );
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->day
            );
        } elseif (isset($this->month)) {

            $month_names = Array(-1 => '--',
                1 => text('month_January'),
                2 => text('month_February'),
                3 => text('month_March'),
                4 => text('month_April'),
                5 => text('month_May'),
                6 => text('month_June'),
                7 => text('month_July'),
                8 => text('month_August'),
                9 => text('month_September'),
                10 => text('month_October'),
                11 => text('month_November'),
                12 => text('month_December'));

            $this->_dateselector->parents[] = Array(
                'URL' => $this->url(Array('day' => '', 'month' => '', 'year' => '', 'start' => ''))
                , 'innerHTML' => text('All_dates')
            );
            $this->_dateselector->parents[] = Array(
                'URL' => $this->url(Array('day' => '', 'month' => '', 'year' => $this->year, 'start' => ''))
                , 'innerHTML' => $this->year
            );
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $month_names[$this->month]
            );

            $tmp = \e::db_getrows("SELECT DISTINCT DAYOFMONTH(last_change_date) AS day
                               FROM <<tp>>news as news
                               WHERE news.site_id={$this->site_id}
                                 AND news.cense_level>={$this->this_site_info['cense_level']}
                                 AND news.lang='{$this->lang}'
                                 AND year(last_change_date)={$this->year}
                                 AND month(last_change_date)={$this->month}
                               ORDER BY day ASC");
            foreach ($tmp as $tm) {
                $this->_dateselector->children[] = Array(
                    'URL' => $this->url(Array('day' => $tm['day'], 'month' => $this->month, 'year' => $this->year, 'start' => ''))
                    , 'innerHTML' => $tm['day']
                );
            }
        } elseif (isset($this->year)) {
            $month_names = Array(-1 => '--',
                1 => text('month_January'),
                2 => text('month_February'),
                3 => text('month_March'),
                4 => text('month_April'),
                5 => text('month_May'),
                6 => text('month_June'),
                7 => text('month_July'),
                8 => text('month_August'),
                9 => text('month_September'),
                10 => text('month_October'),
                11 => text('month_November'),
                12 => text('month_December'));
            $this->_dateselector->parents[] = Array(
                'URL' => $this->url(Array('day' => '', 'month' => '', 'year' => '', 'start' => ''))
                , 'innerHTML' => text('All_dates')
            );
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->year
            );


            $cachefilepath = \e::config('CACHE_ROOT') . '/' . $this->this_site_info['dir'] . "/news_months_site{$this->site_id}_lang{$this->lang}_year{$this->year}.cache";
            $tmp = \core\fileutils::get_cached_info($cachefilepath, cachetime);
            if (!$tmp) {
                $tmp = \e::db_getrows("SELECT DISTINCT month(last_change_date) AS month
                                          FROM <<tp>>news as news
                                          WHERE news.site_id={$this->site_id}
                                           AND  news.cense_level>={$this->this_site_info['cense_level']}
                                           AND news.lang='{$this->lang}'
                                           AND year(last_change_date)=$this->year
                                          ORDER BY month ASC");
                \core\fileutils::set_cached_info($cachefilepath, $tmp);
            }
            foreach ($tmp as $tm) {
                $this->_dateselector->children[] = Array(
                    'URL' => $this->url(Array('day' => '', 'month' => $tm['month'], 'year' => $this->year, 'start' => ''))
                    , 'innerHTML' => $month_names[$tm['month']]
                );
            }
        } else {
            //$current_year = (int) date('Y');
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => text('All_dates')
            );

            $cachefilepath = \e::config('CACHE_ROOT') . '/' . $this->this_site_info['dir'] . "/news_years_site{$this->site_id}_lang{$this->lang}.cache";
            $tmp = \core\fileutils::get_cached_info($cachefilepath, cachetime);
            if (!$tmp) {
                $tmp = \e::db_getrows("SELECT DISTINCT YEAR(last_change_date) AS year FROM <<tp>>news as news WHERE news.site_id={$this->site_id} AND  news.cense_level>={$this->this_site_info['cense_level']} AND news.lang='{$this->lang}' ORDER BY year ASC");
                \core\fileutils::set_cached_info($cachefilepath, $tmp);
            }
            foreach ($tmp as $tm) {
                $this->_dateselector->children[] = Array(
                    'URL' => $this->url(Array('day' => '', 'month' => '', 'year' => $tm['year'], 'start' => ''))
                    , 'innerHTML' => $tm['year']
                );
            }
        }
        // ------------- date selector links - end ---------------------------
    }

    private function createTagSelector() {
        $this->_tagSelector = [];

        // get list of tags
        // cache info as file in the site dir
        $tmp = \core\fileutils::get_cached_info(\e::config('CACHE_ROOT') . '/' . $this->this_site_info['dir'] . "/news_tags_site{$this->this_site_info['id']}_lang{$this->lang}.cache", cachetime);
        if ($tmp) {
            $this->_tagSelector = $tmp;
        } else {
            $query = "SELECT DISTINCT news_tags.tag, news_tags.lang, count(news.id) as N
                       FROM <<tp>>news_tags AS news_tags
                          , <<tp>>news AS news
                       WHERE news_tags.news_id=news.id
                         AND news.lang=news_tags.lang
                         AND news.cense_level>={$this->this_site_info['cense_level']}
                         AND news.site_id={$this->this_site_info['id']}
                         AND news.lang='{$this->lang}'
                       GROUP BY news_tags.tag
                       ORDER BY news_tags.lang, news_tags.tag";
            //prn($query);
            $this->_tagSelector = \e::db_getrows($query);
            \core\fileutils::set_cached_info(\e::config('CACHE_ROOT') . '/' . $this->this_site_info['dir'] . "/news_tags_site{$this->this_site_info['id']}_lang{$this->lang}.cache", $this->_tagSelector);
        }
        $cnt = count($this->_tagSelector);
        for ($i = 0; $i < $cnt; $i++) {
            $index = array_search($this->_tagSelector[$i]['tag'], $this->selectedTags);
            if ($index === false) {
                // url to add tag
                $newSelectedTags = [$this->_tagSelector[$i]['tag']];
                $cnt2 = count($this->selectedTags);
                for ($i2 = 0; $i2 < $cnt2; $i2++) {
                    $newSelectedTags[] = $this->selectedTags[$i2];
                }
                $this->_tagSelector[$i]['url'] = $this->url(Array('start' => 0, 'tags' => join(',', $newSelectedTags)));
                $this->_tagSelector[$i]['selected'] = 0;
            } else {
                // url to remove tag
                $newSelectedTags = [];
                $cnt2 = count($this->selectedTags);
                for ($i2 = 0; $i2 < $cnt2; $i2++) {
                    if ($i2 != $index) {
                        $newSelectedTags[] = $this->selectedTags[$i2];
                    }
                }
                $this->_tagSelector[$i]['url'] = $this->url(Array('start' => 0, 'tags' => join(',', $newSelectedTags)));
                $this->_tagSelector[$i]['selected'] = 1;
            }
        }
    }

    private function lang($options) {
        $lang = isset($options['lang']) ? preg_replace("/\\W/", '', $options['lang']) : \e::config('default_language');
        if (strlen($lang) == 0 || !file_exists(\e::config('APP_ROOT') . "/msg/{$lang}.ini")) {
            $lang = \e::config('default_language');
        }
        return $lang;
    }


    private function createNewsList() {
        //echo "createNewsList<br>";

        # ------------------------- date restriction - begin -------------------
        if (isset($this->day)) {
            $timestamp_min = mktime(00, 00, 01, $this->month, $this->day, $this->year);
            $timestamp_max = mktime(23, 59, 59, $this->month, $this->day, $this->year);
            $date_min = date('Y-m-d H:i:s', $timestamp_min);
            $date_max = date('Y-m-d H:i:s', $timestamp_max);
            $news_date_restriction=" AND  news.last_change_date BETWEEN '$date_min' AND '$date_max' ";
        } elseif (isset($this->month)) {
            $timestamp_min = mktime(00, 00, 01, $this->month, 1, $this->year);
            $timestamp_max = mktime(23, 59, 59, $this->month + 1, 0, $this->year);
            $date_min = date('Y-m-d H:i:s', $timestamp_min);
            $date_max = date('Y-m-d H:i:s', $timestamp_max);
            $news_date_restriction=" AND  news.last_change_date BETWEEN '$date_min' AND '$date_max' ";
        } elseif (isset($this->year)) {
            $timestamp_min = mktime(00, 00, 01, 1, 1, $this->year);
            $timestamp_max = mktime(23, 59, 59, 1, 0, $this->year + 1);
            $date_min = date('Y-m-d H:i:s', $timestamp_min);
            $date_max = date('Y-m-d H:i:s', $timestamp_max);
            $news_date_restriction=" AND news.last_change_date BETWEEN '$date_min' AND '$date_max' ";
        } else {
            $news_date_restriction = '';
        }
        # ------------------------- date restriction - end ---------------------
        # 
        # 
        # 
        # ------------------------- category restriction - begin ---------------
        $category_restriction='';
        if ($this->category_id !== false) {
            if ($this->filtermode) {
                $current_category = \e::db_getonerow("SELECT category_id, start, finish FROM <<tp>>category WHERE site_id={$this->site_id} AND category_id={$this->category_id}");
                if ($current_category) {
                    

                    $category_ids = array_map(
                            function($el) { return $el['category_id']; }, //
                            \e::db_getrows("SELECT category_id FROM <<tp>>category 
                                        WHERE site_id={$this->site_id}
                                          AND {$current_category['start']}<=start AND finish <= {$current_category['finish']}"));

                    $query="CREATE TEMPORARY TABLE nwsid(
                    `id` BIGINT(20) UNSIGNED NOT NULL,
                     PRIMARY KEY (`id`)
                    ) ENGINE MEMORY;";
                    \e::db_execute($query);
                    
                    $query="INSERT INTO nwsid(id) SELECT DISTINCT news_id FROM <<tp>>news_category WHERE category_id IN (" . join(',', $category_ids) . ")";
                    \e::db_execute($query);

                    $category_restriction = " INNER JOIN nwsid ON nwsid.id=news.id ";
                    //header('Cms-Info-01: filtermode');
                }
            } else {

                //header('Cms-Info-01: direct');
                $query="CREATE TEMPORARY TABLE nwsid(
                `id` BIGINT(20) UNSIGNED NOT NULL,
                 PRIMARY KEY (`id`)
                ) ENGINE MEMORY;";
                \e::db_execute($query);

                $query="INSERT INTO nwsid(id) SELECT news_id FROM <<tp>>news_category WHERE category_id={$this->category_id}";
                \e::db_execute($query);

                $category_restriction = " INNER JOIN nwsid ON nwsid.id=news.id ";
            }
        } else {
            $category_restriction = '';
        }
        # ------------------------- category restriction - end -----------------
        # 
        # 
        # 
        # 
        # ------------------------- tag restriction - begin --------------------
        $news_tags_restriction='';
        if (count($this->selectedTags) > 0) {

            $query = "CREATE TEMPORARY TABLE IF NOT EXISTS `tags_query` (
                    `news_id` BIGINT(20) DEFAULT NULL,
                    `tag` VARCHAR(100) DEFAULT NULL
                  ) ENGINE=MEMORY DEFAULT CHARSET=utf8";
            \e::db_execute($query);

            $query = "DELETE FROM `tags_query`";
            \e::db_execute($query);

            $query = Array();
            foreach ($this->selectedTags as $tg) {
                $query[] = "SELECT news_id, tag FROM <<tp>>news_tags WHERE lang='{$this->lang}' AND tag='" . \e::db_escape(trim($tg)) . "'";
            }
            $query = "INSERT INTO tags_query(news_id, tag) " . join("\nUNION\n", $query);
            \e::db_execute($query);

            $query = "CREATE TEMPORARY TABLE IF NOT EXISTS `ids` (
                    `news_id` BIGINT(20) DEFAULT NULL,
                    `n` BIGINT(20) DEFAULT NULL,
                     PRIMARY KEY (`news_id`)
                  ) ENGINE=MEMORY DEFAULT CHARSET=utf8";
            \e::db_execute($query);
            $query = "DELETE FROM `ids`";
            \e::db_execute($query);

            $query = "INSERT INTO ids(news_id, n) SELECT news_id, COUNT(*) AS n FROM tags_query GROUP BY news_id HAVING n=" . count($this->selectedTags);
            \e::db_execute($query);

            //$news_tags_restriction.=" AND news.id IN( SELECT news_id FROM `ids`) ";
            $news_tags_restriction =" INNER JOIN ids ON ids.news_id=news.id ";
            
        } else {
            $news_tags_restriction = '';
        }
        # ------------------------- tag restriction - end ----------------------
        # ------------------------- keyword restriction - begin ------------------------
        $news_keywords_restriction = '';
        if (strlen($this->keywords) > 0) {
            # $news_keywords
            $news_keywords_restriction = explode(' ', trim($this->keywords));
            $cnt = count($news_keywords_restriction);
            $tmp = " ( LOCATE('{s}',ifnull(news.title,'')) OR LOCATE('{s}',ifnull(news.content,''))  OR LOCATE('{s}',ifnull(news.abstract,'')) )  ";
            for ($i = 0; $i < $cnt; $i++) {
                if (strlen($news_keywords_restriction[$i]) > 0) {
                    $news_keywords_restriction[$i] = str_replace('{s}',\e::db_escape($news_keywords_restriction[$i]), $tmp);
                } else {
                    unset($news_keywords_restriction[$i]);
                }
            }
            if (count($news_keywords_restriction) > 0) {
                $news_keywords_restriction = ' AND ' . join(' AND ', $news_keywords_restriction);
            } else {
                $news_keywords_restriction = '';
            }
        }
        # prn('$news_keywords_restriction='.$news_keywords_restriction);
        # ------------------------- keyword restriction - end --------------------------
        # 


        if ($this->ordering) {
            $orderby = " ORDER BY {$this->ordering}";
        } else {
            $orderby = ' ORDER BY news.last_change_date DESC ';
        }

        $now = date('Y-m-d H:i:s', time());
        $query = "SELECT DISTINCT SQL_CALC_FOUND_ROWS
                   news.id
                  ,news.lang
                  ,news.site_id
                  ,news.title
                  ,news.news_code
                  ,news.site_id
                  ,news.abstract AS abstract
                  ,news.last_change_date
                  ,news.expiration_date
                  ,news.tags
                  ,news.content
                  ,news.cense_level
                  ,news.category_id
                  ,news.weight
                  ,news.news_icon
                  ,news.creation_date
                  ,news.news_code
                  ,news.news_meta_info
                  ,news.news_extra_1
                  ,news.news_extra_2
                  ,news.news_views
                  ,IF(LENGTH(TRIM(news.content))>0,1,0) as content_present
            FROM <<tp>>news AS news
                 {$category_restriction}
                 {$news_tags_restriction}
            WHERE news.site_id={$this->site_id}
              AND news.cense_level>={$this->this_site_info['cense_level']}
              AND news.lang='{$this->lang}'
              AND news.last_change_date < '$now' AND ( news.expiration_date is null OR  '$now'< news.expiration_date)
              
              
              {$news_date_restriction}
              {$news_keywords_restriction}
            {$orderby}
            LIMIT {$this->start},{$this->rows_per_page}";
            //                  ,news.content
            //
        // prn(htmlspecialchars($query));
        $startTime=  microtime(true);
        $this->_list = Array('rows' => \e::db_getrows($query));
        header('Cms-Timing: '. (microtime(true)-$startTime));
        
        
        # --------------------------- list of pages - begin --------------------
        $num = \e::db_getonerow("SELECT FOUND_ROWS() AS n_records;");
        // prn($query,$num);
        $num = (int) $num['n_records'];
        $pages = Array();
        $imin = max(0, $this->start - 10 * $this->rows_per_page);
        $imax = min($num, $this->start + 10 * $this->rows_per_page);
        if ($imin > 0) {
            $pages[] = Array(
                'URL' => $this->url(Array('start'=>0)),
                'innerHTML' => '1',
                'active'=>0
            );
            $pages[] = Array('URL' => '', 'innerHTML' => '...');
        }

        for ($i = $imin; $i < $imax; $i = $i + $this->rows_per_page) {
            $to = (1 + $i / $this->rows_per_page);
            $pages[] = Array(
                'URL' => $this->url(Array('start'=>$i)),
                'innerHTML' => $to,
                'active'=>(  ($i == $this->start)?1:0)
            );
        }

        if ($imax < $num) {
            $last_page = floor(($num - 1) / $this->rows_per_page);
            if ($last_page > 0) {
                $pages[] = Array('URL' => '', 'innerHTML' => "...");
                $pages[] = Array(
                    'URL' => $this->url(Array('start'=>($last_page * $this->rows_per_page))),
                    'innerHTML' => ($last_page + 1),
                    'active'=>0
                );
            }
        }
        $this->_list['pages']=$pages;
        $this->_list['total']=$num;
        $this->_list['start']=$this->start+1;
        $this->_list['finish']=min($num,$this->start+count($this->_list['rows']));
        # --------------------------- list of pages - end ----------------------
        # 
        # 
        # adjust list of news
        $this->_list['rows'] = news_get_view($this->_list['rows'], $this->lang, $this->_this_site_info);
        
        $cnt=count($this->_list['rows']);
        for($i=0; $i<$cnt; $i++){
            
            $tag_links = &$this->_list['rows'][$i]['tag_links'];
            $cnt2=count($tag_links);
            for($i2=0; $i2<$cnt2; $i2++){
                $tag_links[$i2]['URL']=$this->url(Array('tags'=>$tag_links[$i2]['name']));
            }
        }
                
                
        // prn($this->_list['rows']);
    }

    
    
    
}

<?php

/*
  draw menu for news
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
 */

function menu_news($news_info) {
    global $text, $db, $table_prefix;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];

    $tor['news/edit'] = Array(
        'URL' => "index.php?action=news/edit&site_id={$news_info['site_id']}&news_id={$news_info['id']}&lang={$news_info['lang']}"
        , 'innerHTML' => $text['Edit_news']
        , 'attributes' => ''
    );

    $tor['news/edit_a'] = Array(
        'URL' => "index.php?action=news/edit&site_id={$news_info['site_id']}&aed=1&news_id={$news_info['id']}&lang={$news_info['lang']}"
        , 'innerHTML' => $text['Advanced_Editor']
        , 'attributes' => ''
    );

    $tor['news/view'] = Array(
        'URL' => site_public_URL."/index.php?action=news/view_details&news_id={$news_info['id']}&lang={$news_info['lang']}" . '&' . $sid
        , 'innerHTML' => $text['View_page']
        , 'attributes' => ' target=_blank '
    );

    /*
      $tor['news/view']=Array(
      'URL'=>"index.php?action=news/view&site_id={$news_info['site_id']}&lang={$news_info['lang']}"
      ,'innerHTML'=>$text['View_news']
      ,'attributes'=>' target=_blank '
      );
     */
    #if($_REQUEST['action']=='news/edit')
    #{
    #   $ret=rawurlencode(base64_encode("index.php?action=news/list&site_id={$news_info['site_id']}&lang={$news_info['lang']}&filter_id={$news_info['id']}"));
    #}
    #else
    #{
    #   $ret=rawurlencode(base64_encode('index.php?'.query_string('^'.session_name().'$')));
    #}
    $ret = '';
    $tor['news/add'] = Array(
        'URL' => "index.php?action=news/add&site_id=" . $news_info['site_id'] . "&news_id=" . $news_info['id'] . "&news_lang=" . $news_info['lang'] . "&return=" . $ret
        , 'innerHTML' => $text['Add_translation']
        , 'attributes' => ' style="margin-bottom:10pt;" '
    );

    //javascript:void(request=ajax_load('',false,function (){ if (request.readyState == 4)   window.location.reload();}));
    //--------------------------- document flow - begin -------------------------
    $tor['news/approve'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/approve&transition=approve&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => $text['Approve']
        , 'attributes' => ''
    );
    $tor['news/seize'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/approve&transition=seize&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => $text['Seize_to_revize']
        , 'attributes' => ''
    );
    $tor['news/return'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/approve&transition=return&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => $text['Return_to_previous_operator']
        , 'attributes' => " title=\"{$text['Return_to_previous_operator']}\" style='margin-bottom:10pt;' "
    );
    //--------------------------- document flow - end ---------------------------

    $tor['news/Move_up'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/set_weight&weight=-1&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => text('Move_up')
        , 'attributes' => " title=\"" . text('Move_up') . "\" "
    );
    $tor['news/Move_down'] = Array(
        'URL' => "javascript:void(request=ajax_load('index.php?action=news/set_weight&weight=1&news_id={$news_info['id']}&lang={$news_info['lang']}',false,function (){ if (request.readyState == 4)   window.location.reload();}));"
        , 'innerHTML' => text('Move_down')
        , 'attributes' => " title=\"" . text('Move_down') . "\" style='margin-bottom:10pt;' "
    );

    $tor['news_subscription/send'] = Array(
        'URL' => "index.php?action=news_subscription/send_news&news_id={$news_info['id']}&lang={$news_info['lang']}"
        , 'innerHTML' => text('Email_to_subscribers')
        , 'attributes' => " target=_blank "
    );

    if (is_admin()) {
        $tor['news/delete'] = Array(
            'URL' => "index.php?action=news/delete" .
            "&site_id=" . $news_info['site_id'] .
            "&delete_news_id=" . $news_info['id'] .
            "&delete_news_lang=" . $news_info['lang']
            , 'innerHTML' => $text['Delete_news'] . '<iframe src="about:blank" width=10px height=1px style="border:none;" name="frm_delete"></iframe>'
            , 'attributes' => " onclick='return confirm(\"{$text['Are_You_sure']}?\")' target=frm_delete "
        );
    }
    return $tor;
}

function news_get_view($news_list, $lang) {
    if (count($news_list) == 0)
        return Array();
    $news_list = array_values($news_list);
    $site_id = $news_list[0]['site_id'];

    // prn($ids);
    $ids = Array(0);
    $cnt = count($news_list);
    for ($i = 0; $i < $cnt; $i++) {
        $ids[] = (int) $news_list[$i]['id'];
    }

    $query = "select * from {$GLOBALS['table_prefix']}news_category where news_id in (" . join(',', $ids) . ")";
    $categories = db_getrows($query);
    // prn($categories);
    $category_ids = Array(0 => 1);
    $_category = Array();
    foreach ($categories as $cat) {
        $category_ids[(int) $cat['category_id']] = 1;
        if (!isset($_category[$cat['news_id']])) {
            $_category[$cat['news_id']] = Array();
        }
        $_category[$cat['news_id']][] = $cat['category_id'];
    }
    // prn('$category_ids', $category_ids, '$_category', $_category);

    $query = "SELECT * FROM {$GLOBALS['table_prefix']}category WHERE category_id in(" . join(',', array_keys($category_ids)) . ")";
    $tmp = db_getrows($query);
    // prn($query,$tmp);
    $ncat = count($tmp);
    $categories = Array();
    

    for ($i = 0; $i < $ncat; $i++) {
        $categories[$tmp[$i]['category_id']]['category_title'] = get_langstring($tmp[$i]['category_title'], $lang);
        $categories[$tmp[$i]['category_id']]['URL'] = str_replace(
                Array('{path}'        ,'{lang}','{site_id}','{category_id}','{category_code}'),
                Array($tmp[$i]['path'],$lang   ,$site_id   ,$tmp[$i]['category_id'],$tmp[$i]['category_code']),
                url_pattern_category);
        $categories[$tmp[$i]['category_id']]['deep'] = $tmp[$i]['deep'];
        $categories[$tmp[$i]['category_id']]['category_id'] = $tmp[$i]['category_id'];
    }
    //prn($categories);

    $cnt = count($news_list);
    for ($i = 0; $i < $cnt; $i++) {
        // $news_list[$i]['URL_view_details'] = url_prefix_news_details . "news_id={$news_list[$i]['id']}&lang={$lang}";
        $news_list[$i]['URL_view_details'] = str_replace(
                Array('{news_id}','{lang}','{news_code}'),
                Array($news_list[$i]['id'],$lang,$news_list[$i]['news_code']),
                url_template_news_details);
        //url_prefix_news_details . "news_id={$news_list[$i]['id']}&lang={$lang}";
        
        $news_list[$i]['tag_links'] = news_tag_links($news_list[$i]['tags'],$news_list[$i]['site_id'],$lang);
        $news_list[$i]['categories'] = Array();
        if (isset($_category[$news_list[$i]['id']])) {
            foreach ($_category[$news_list[$i]['id']] as $cat_id) {
                if(isset($categories[$cat_id])) $news_list[$i]['categories'][] = $categories[$cat_id];
            }
        }
    }
    // prn($news_list);
    // preprocess tags


    return $news_list;
}

function news_tag_links($tag_string,$site_id,$lang) {
    $tags = Array();
    //$input_vars['tags']
    $tmp = trim($tag_string);
    if (strlen($tmp) > 0) {
        $tags = preg_split("/,|;|\\./", $tag_string);
        $cnt = count($tags);
        $prefix=site_root_URL . "/index.php?action=news/view&site_id={$site_id}&lang={$lang}&tag=";
        for ($i = 0; $i < $cnt; $i++) {
            $tags[$i] = trim($tags[$i]);
            $tags[$i] = preg_replace("/ +/", " ", $tags[$i]);
            $tags[$i] = Array(
                'name'=>$tags[$i],
                'URL'=>$prefix.  rawurlencode($tags[$i])
            );
        }
    }
    return $tags;
}


function menu_news_comment($info){
    global $text, $db, $table_prefix;
    $tor = Array();
    $sid = session_name() . '=' . $GLOBALS['_COOKIE'][session_name()];
    $tor['news/view'] = Array(
          'URL' => "index.php?action=news/view_details&news_id={$info['news_id']}&lang={$info['news_lang']}"
        , 'innerHTML' => $text['View_news']
        , 'attributes' => ' target=_blank '
    );

    $prefix=site_URL.'?'. preg_query_string("/hide_comment|show_comment/");
    $tor['news/hide_comment'] = Array(
          'URL' => $prefix."&hide_comment={$info['news_comment_id']}"
        , 'innerHTML' => $text['news_comment_hide']
        , 'attributes' => ''
    );
    $tor['news/show_comment'] = Array(
          'URL' => $prefix."&show_comment={$info['news_comment_id']}"
        , 'innerHTML' => $text['news_comment_show']
        , 'attributes' => ''
    );
    return $tor;
}

class CategoryNews {

    protected $lang, $this_site_info, $category_info, $start;
    protected $_list, $_pages, $items_found;
    protected $rows_per_page = 10;
    protected $ordering = 'news.last_change_date DESC';
    protected $startname = 'news_start';
    protected $includeChildren=true;

    function __construct($_lang, $_this_site_info, $_category_info, $start, $input_vars) {
        $this->lang = $_lang;
        $this->this_site_info = $_this_site_info;
        $this->category_info = $_category_info;
        $this->start = $start;
        

        if (isset($input_vars['year'])) {
            $this->year = (int) $input_vars['year'];
        }

        if (isset($input_vars['month'])) {
            $this->month = (int) $input_vars['month'];
            if (!isset($this->year)) {
                $this->year = (int) date('Y');
            }
        }

        if (isset($input_vars['day'])) {
            $this->day = (int) $input_vars['day'];
            if (!isset($this->month)) {
                $this->month = (int) date('m');
            }
            if (!isset($this->year)) {
                $this->year = (int) date('Y');
            }
        }
        //$this->init();
        //$this->createDateSelector();
        if(isset($input_vars['tags'])){
            $this->selectedTags=array_filter($tags = preg_split("/,|;|\\./",$input_vars['tags']),function($el){return strlen(trim($el))>0;});
        }else{
            $this->selectedTags=[];
        }
    }
    
    public function createTagSelector(){
        $this->tagSelector=[];

        // get list of tags
        // cache info as file in the site dir
        $cachefilepath=template_cache_root . '/' . $this->this_site_info['dir'] . "/cache/news_tags_category{$this->category_info['category_id']}_lang{$this->lang}.cache";
        $tmp = get_cached_info($cachefilepath, cachetime);
        if ($tmp) {
            $this->tagSelector = $tmp;
        } else {
            //$query = "SELECT DISTINCT news_tags.tag, news_tags.lang, count(news.id) as N
            //           FROM {$GLOBALS['table_prefix']}news_tags AS news_tags
            //              , {$GLOBALS['table_prefix']}news AS news
            //           WHERE news_tags.news_id=news.id
            //             AND news.lang=news_tags.lang
            //             AND news.cense_level>={$this->this_site_info['cense_level']}
            //             AND news.site_id={$this->this_site_info['id']}
            //             AND news.lang='{$this->lang}'
            //           GROUP BY news_tags.tag
            //           ORDER BY news_tags.lang, news_tags.tag";
            $query = "SELECT DISTINCT news_tags.tag, news_tags.lang, count(news.id) as N
                       FROM {$GLOBALS['table_prefix']}news_tags AS news_tags
                          , {$GLOBALS['table_prefix']}news AS news
                          , (
                          select news_category.news_id
                        from  {$GLOBALS['table_prefix']}category AS category
                              inner join {$GLOBALS['table_prefix']}news_category as news_category ON news_category.category_id=category.category_id
                        where {$this->category_info['start']}<=category.start AND category.finish <={$this->category_info['finish']}
                      ) AS nc
                       WHERE news_tags.news_id=news.id
                         AND news_tags.news_id=nc.news_id
                         AND news.lang=news_tags.lang
                         AND news.cense_level>={$this->this_site_info['cense_level']}
                         AND news.site_id={$this->this_site_info['id']}
                         AND news.lang='{$this->lang}'
                       GROUP BY news_tags.tag
                       ORDER BY news_tags.lang, news_tags.tag";
            //prn($query);
            $this->tagSelector = db_getrows($query);
            set_cached_info($cachefilepath, $this->tagSelector);
        }
        $cnt=count($this->tagSelector);
        for($i=0; $i<$cnt; $i++){
            $url=site_URL.'?'.preg_query_string('/tags|start/');
            $index=array_search($this->tagSelector[$i]['tag'],$this->selectedTags);
            if( $index === false ){
                // url to add tag
                $newSelectedTags=[$this->tagSelector[$i]['tag']];
                $cnt2=count($this->selectedTags);
                for($i2=0; $i2<$cnt2;$i2++){
                    $newSelectedTags[]=$this->selectedTags[$i2];
                }
                $this->tagSelector[$i]['url']=$url.'&tags='.rawurlencode(join(',',$newSelectedTags));
                $this->tagSelector[$i]['selected']=0;
            }else{
                // url to remove tag
                $newSelectedTags=[];
                $cnt2=count($this->selectedTags);
                for($i2=0; $i2<$cnt2;$i2++){
                    if($i2!=$index){
                        $newSelectedTags[]=$this->selectedTags[$i2];
                    }
                }
                $this->tagSelector[$i]['url']=$url.'&tags='.rawurlencode(join(',',$newSelectedTags));
                $this->tagSelector[$i]['selected']=1;
            }
        }
        //prn($this->tagSelector);
        //$this->selectedTags
    }
    
    public function createDateSelector(){
        // ------------- date selector links - begin ---------------------------
        $this->_dateselector = new stdClass();
        $this->_dateselector->parents = Array();
        $this->_dateselector->current = Array();
        $this->_dateselector->children = Array();

        if (isset($this->day)) {
            $month_names = calendar_misyaci();
            $this->_dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->_dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}"
                , 'innerHTML' => $this->year
            );
            $this->_dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$this->month}"
                , 'innerHTML' => $month_names[$this->month]
            );
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->day
            );
        } elseif (isset($this->month)) {

            $month_names = calendar_misyaci();

            $this->_dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->_dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}"
                , 'innerHTML' => $this->year
            );
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $month_names[$this->month]
            );

            $timestamp_start = mktime(12, 0, 0, $this->month, 1, $this->year);
            $timestamp_end = mktime(12, 0, 0, $this->month + 1, 0, $this->year);
            for ($i = $timestamp_start; $i <= $timestamp_end; $i+=86400) { // 86400 = seconds in day
                $day = date('d', $i);
                $this->_dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$this->month}&day=" . $day// 
                    , 'innerHTML' => $day
                );
            }
        } elseif (isset($this->year)) {
            $month_names = calendar_misyaci();
            $this->_dateselector->parents[] = Array(
                'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/")
                , 'innerHTML' => text('All_dates')
            );
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => $this->year
            );
            for ($i = 1; $i <= 12; $i++) {
                $this->_dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year={$this->year}&month={$i}"// 
                    , 'innerHTML' => $month_names[$i]
                );
            }
        } else {
            //$current_year = (int) date('Y');
            $this->_dateselector->current = Array(
                'URL' => ''// 
                , 'innerHTML' => text('All_dates')
            );
            // get min and max years
            $query="SELECT max(news.last_change_date) as maxdate ,min(news.last_change_date) as mindate
                    FROM {$GLOBALS['table_prefix']}news news
                    WHERE site_id={$this->this_site_info['id']}
                      AND lang='" . DbStr($this->lang) . "'";
            $minmax=  db_getonerow($query);
            $max=(int)date('Y',strtotime($minmax['maxdate']));
            $min=(int)date('Y',strtotime($minmax['mindate']));
            for ($i = $min; $i <= $max; $i++) {
                $this->_dateselector->children[] = Array(
                    'URL' => site_URL . '?' . preg_query_string("/day|month|year|event_start/") . "&year=" . ($i)// 
                    , 'innerHTML' => ($i)
                );
            }
        }
        // ------------- date selector links - end ---------------------------
    }
    
    public function setRowsPerPage($val) {
        $this->rows_per_page = (int) $val;
        unset($this->_list);
        return '';
    }

    public function setCategoryId($val) {
        $category_id = (int) $val;
        $this->category_info = category_info(Array(
            'category_id' => $category_id,
            'site_id' => $this->this_site_info['id'],
            'lang' => $this->lang
        ));
        unset($this->_list);
        return '';
    }
    
    public function setIncludeChildren($val) {
        $this->includeChildren = $val;
        unset($this->_list);
        return '';
    }
    
    public function setLang($val) {
        $this->lang = preg_replace('/[^a-z]/i','', $val);
        $this->category_info = category_info(Array(
            'category_id' => $this->category_info['category_id'],
            'site_id' => $this->this_site_info['id'],
            'lang' => $this->lang
        ));
        unset($this->_list);
        return '';
    }
    
    public function setOrdering($val) {
        
        $this->subordering=false;
        $tmp=explode(',',$val);
        //prn($val,$tmp);
        $ordering=Array();
        for($i=0, $cnt=count($tmp); $i<$cnt; $i++){
            $tmp[$i]=preg_split('/ +/',trim($tmp[$i]));
            $tmp[$i][1]=(isset($tmp[$i][1]) && strtoupper($tmp[$i][1])=='DESC')?'DESC':'ASC';
            $tmp[$i][0]=trim(strtolower($tmp[$i][0]));
            switch ($tmp[$i][0]){
                case 'date':
                case 'datetime':
                    $ordering[]='news.last_change_date '.$tmp[$i][1];
                    break;
                case 'weight':
                    $ordering[]='news.weight '.$tmp[$i][1];
                    break;
                case 'id':
                    $ordering[]='news.id '.$tmp[$i][1];
                    break;
            }
        }
        //prn($ordering);
        $this->ordering=join(',', $ordering);
        unset($this->_list);
        return '';
    }
    
    
    
    
    function __get($attr) {
        if (!isset($this->_list)) {
            $this->init();
        }
        switch ($attr) {
            case 'list':
                return $this->_list;

            case 'pages':
                return $this->_pages;

            case 'dateselector':
                return $this->_dateselector;

            case 'items_found':
                return $this->items_found;

            case 'start':
                return $this->start + 1;

            case 'finish':
                return min($this->start + $this->rows_per_page, $this->items_found);

            default: return Array();
        }
    }

    private function init() {

        //$this->createDateSelector();
        //$this->createTagSelector();
        
        $site_id = $this->this_site_info['id'];
        $category_id = $this->category_info['category_id'];

        
        if($this->includeChildren){
            // get all the visible children
            $query = "SELECT ch.category_id, BIT_AND(pa.is_visible) as visible
                FROM {$GLOBALS['table_prefix']}category ch, {$GLOBALS['table_prefix']}category pa
                WHERE pa.start<=ch.start AND ch.finish<=pa.finish
                  AND {$this->category_info['start']}<=ch.start AND ch.finish<={$this->category_info['finish']}
                  AND pa.site_id=$site_id and ch.site_id=$site_id
                GROUP BY ch.category_id
                HAVING visible
            ";
            // prn($query);
            $children = db_getrows($query);
            $cnt = count($children);
            for ($i = 0; $i < $cnt; $i++) {
                $children[$i] = $children[$i]['category_id'];
            }            
        }else{
            $children = Array($this->category_info['category_id']);
        }
        // prn(join(',',$children));
        // 
        // 
        $date_restriction='';
        if (isset($this->day)) {

            $date_min=date('Y-m-d H:i:s',mktime ( 0, 0, 1, $this->month, $this->day, $this->year ));
            $date_max=date('Y-m-d H:i:s',mktime ( 23, 59, 59, $this->month, $this->day, $this->year ));
            $date_restriction=" AND news.last_change_date BETWEEN '$date_min' AND '$date_max' ";

        } elseif (isset($this->month)) {

            $date_min=date('Y-m-d H:i:s',mktime ( 0, 0, 1, $this->month, 1, $this->year ));
            $date_max=date('Y-m-d H:i:s',mktime ( 23, 59, 59, $this->month + 1, -1, $this->year ));
            $date_restriction=" AND news.last_change_date BETWEEN '$date_min' AND '$date_max' ";

        } elseif (isset($this->year)) {
            
            $date_min=date('Y-m-d H:i:s',mktime ( 0, 0, 1, 1, 1, $this->year ));
            $date_max=date('Y-m-d H:i:s',mktime ( 23, 59, 59, 1, -1, $this->year + 1));
            $date_restriction=" AND news.last_change_date BETWEEN '$date_min' AND '$date_max' ";

        }
        
        $tag_restriction='';
        if(count($this->selectedTags)>0){
            $query=[];
            foreach($this->selectedTags as $selectedTag){
                $query[]="'".DbStr($selectedTag)."'";
            }
            $query=  array_unique($query);
            //$tag_restriction = "AND news.id IN( 
            //        SELECT news_tags.news_id
            //        FROM {$GLOBALS['table_prefix']}news_tags AS news_tags
            //        WHERE news_tags.tag in(".join(',',$query).")
            // )";
                    
            $tag_restriction = "AND news.id IN(
                select news_id from ( SELECT news_tags.news_id, count(distinct news_tags.tag) nt 
                FROM {$GLOBALS['table_prefix']}news_tags AS news_tags 
                WHERE news_tags.tag in(".join(',',$query).")  group by news_tags.news_id having nt=".count($query)." ) fre
            )";
            
        }

        // get all the visible news attached to visible children
        $query = "SELECT SQL_CALC_FOUND_ROWS
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
                  ,news.creation_date
                  ,news.news_code
                  ,news.news_meta_info
                  ,news.news_extra_1
                  ,news.news_extra_2
                  ,IF(LENGTH(TRIM(news.content))>0,1,0) as content_present
            FROM {$GLOBALS['table_prefix']}news news
            WHERE site_id=$site_id
              AND lang='" . DbStr($this->lang) . "'
              AND cense_level>={$this->this_site_info['cense_level']}
              AND last_change_date<=now()
              AND ( expiration_date is null OR now()<=expiration_date )
              AND news.id in(SELECT news_id FROM {$GLOBALS['table_prefix']}news_category WHERE category_id in(" . join(',', $children) . ") )
              {$date_restriction}
              {$tag_restriction}
            ".( $this->ordering ? "ORDER BY {$this->ordering}" : '')."
            LIMIT {$this->start},{$this->rows_per_page}";
        //prn($query);
        $this->_list = db_getrows($query);


        $this->items_found = db_getonerow("SELECT FOUND_ROWS() AS n_records");
        $this->items_found = $this->items_found['n_records'];
        //prn('$this->items_found=' . $this->items_found);
        # --------------------------- list of pages - begin --------------------------
        $this->_pages = $this->get_paging_links($this->items_found, $this->start, $this->rows_per_page);
        //prn('$this->_pages=',$this->_pages);
        # --------------------------- list of pages - end ----------------------------

        $this->_list = news_get_view($this->_list, $this->lang);

        return '';
    }

    function get_paging_links($records_found, $start, $rows_per_page) {

        $url_prefix = site_URL . '?' . preg_query_string("/" . $this->startname . "|" . session_name() . "/") . "&{$this->startname}=";

        $pages = Array();
        $imin = max(0, $start - 10 * $rows_per_page);
        $imax = min($records_found, $start + 10 * $rows_per_page);
        if ($imin > 0) {
            $pages[] = Array(
                'URL' => $url_prefix . '0',
                'innerHTML' => '[1]'
            );
            $pages[] = Array('URL' => '', 'innerHTML' => '...');
        }

        for ($i = $imin; $i < $imax; $i = $i + $rows_per_page) {
            if ($i == $start) {
                $pages[] = Array('URL' => '', 'innerHTML' => '<b>[' . (1 + $i / $rows_per_page) . ']</b>');
            } else {
                $pages[] = Array('URL' => $url_prefix . $i, 'innerHTML' => ( 1 + $i / $rows_per_page));
            }
        }

        if ($imax < $records_found) {
            $last_page = floor(($records_found - 1) / $rows_per_page);
            if ($last_page > 0) {
                $pages[] = Array('URL' => '', 'innerHTML' => "...");
                $pages[] = Array(
                    'URL' => $url_prefix . ($last_page * $rows_per_page)
                    , 'innerHTML' => "[" . ($last_page + 1) . "]"
                );
            }
        }
        return $pages;
    }

}



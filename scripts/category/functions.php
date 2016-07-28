<?php

function menu_category($_info, $site_info) {
    global $input_vars;
    $menu = Array();

    if ($_info) {
        // # ------------------------ selected menu - begin ---------------------
        # visible to all
        //if(is_librarian())

        $menu[] = Array(
            'url' => ''
            , 'html' => "<b>" . get_langstring($_info['category_title']) . " : </b>"
            , 'attributes' => ''
        );


        $menu[] = Array(
            'url' => "{$site_info['extra_setting']['publicCmsUrl']}/index.php?action=category/browse&category_id=" . $_info['category_id'] . "&site_id={$_info['site_id']}&lang={$_SESSION['lang']}"
            , 'html' => text("Preview")
            , 'attributes' => ' target=_blank '
        );

        $menu[] = Array(
            'url' => 'index.php?action=category/list&category_id=' . $_info['category_id'] . '&add_child=yes' . "&site_id={$_info['site_id']}"
            , 'html' => text("Create_subcategory")
            , 'attributes' => ''
        );

        $menu[] = Array(
            'url' => 'index.php?action=category/edit&category_id=' . $_info['category_id'] . "&site_id={$_info['site_id']}"
            , 'html' => text("Edit")
            , 'attributes' => ''
        );
        $menu[] = Array(
            'url' => 'index.php?action=category/edit&category_id=' . $_info['category_id'] . "&site_id={$_info['site_id']}&aed=1"
            , 'html' => text('Advanced_Editor')
            , 'attributes' => ' style="margin-bottom:20px;"'
        );

        $menu[] = Array(
            'url' => 'index.php?action=category/list&category_id=' . $_info['category_id'] . "&site_id={$_info['site_id']}"
            , 'html' => text('Open') . "<br><br>"
            , 'attributes' => ''
        );

        if ($_info['start'] > 0)
            $menu[] = Array(
                'url' => 'index.php?action=category/list&category_delete=yes&category[' . $_info['category_id'] . ']=' . $_info['category_id'] . "&site_id={$_info['site_id']}"
                , 'html' => text('Delete')
                , 'attributes' => ' style="color:red;margin-top:20px;" onclick="return confirm(\'Do you really want to delete ' . htmlspecialchars(" {$_info['category_title']} ") . '\')" '
            );
    }# ------------------------ selected menu - end -----------------------


    $cnt = count($menu);
    for ($i = 0; $i < $cnt; $i++) {
        $menu[$i]['innerHTML'] = &$menu[$i]['html'];
        $menu[$i]['URL'] = &$menu[$i]['url'];
    }
    return $menu;
}

function adjust($_info, $category_id, $site_info) {
    $tor = $_info;
    $tor['context_menu'] = menu_category($tor,$site_info);
    unset($tor['context_menu']['start']);

    $tor['category_title'] = get_langstring($tor['category_title']);

    $tor['category_title_short'] = get_langstring($tor['category_title_short']);
    if (strlen($tor['category_title_short'])==0) {
        $tor['category_title_short'] = shorten($tor['category_title']);
    }

    $tor['title_short'] = $tor['category_title_short'];

    $tor['padding'] = 20 * $tor['deep'];
    $tor['URL'] = "index.php?action=category/list&category_id={$tor['category_id']}&site_id={$_info['site_id']}";
    $tor['URL_move_up'] = "index.php?action=category/list&category_id=$category_id&move_up={$tor['category_id']}&site_id={$_info['site_id']}";
    $tor['URL_move_down'] = "index.php?action=category/list&category_id=$category_id&move_down={$tor['category_id']}&site_id={$_info['site_id']}";
    $tor['has_subcategories'] = ($tor['finish'] - $tor['start'] > 1) ? '>>>' : '';


    // date_lang_update
    $tmp = $tor['date_lang_update'];
    $tmp = explode('<', $tmp);
    $cnt = count($tmp);
    if ($cnt > 1) {
        $date_lang_update = Array();
        for ($i = 1; $i < $cnt; $i+=2) {
            $tmp[$i] = explode('>', $tmp[$i]);
            $date_lang_update[$tmp[$i][0]] = $tmp[$i][1];
        }
    } else {
        $date_lang_update = Array();
    }
    $tor['date_lang_update_array'] = $date_lang_update;
    // prn('date_lang_update',$tor['date_lang_update_array']);
    # prn($query,$this_page_info);
    //prn('    tor= ',$tor);

    if (!is_array($tor['category_icon'])) {
        $tor['category_icon'] = json_decode($tor['category_icon'], true);
    }
    return $tor;
}

function category_public_list($site_id, $lang) {
    // ------------------ get list of categories - begin -----------------------
    $query = "select ch.*, bit_and(pa.is_visible) as visible
              from <<tp>>category pa,
                   <<tp>>category ch
              where pa.start<=ch.start and ch.finish<=pa.finish
                and pa.site_id=" . ((int) $site_id) . "
                and ch.site_id=" . ((int) $site_id) . "
              group by ch.category_id
              having visible>0
              order by  ch.start";
    $caterory_list = \e::db_getrows($query);
    // ------------------ get list of categories - end -------------------------
    // ------------------ adjust list of categories - begin --------------------
    // $category_url_prefix = site_root_URL . "/index.php?action=category/browse&site_id={$site_id}&lang={$lang}&category_id=";
    //{site_id}&lang={lang}&category_id={category_id}&path={path}&category_code={category_code}
    $cnt = count($caterory_list);
    for ($i = 0; $i < $cnt; $i++) {
        $caterory_list[$i]['category_title'] = get_langstring($caterory_list[$i]['category_title'], $lang, $strict=true);
        if(strlen($caterory_list[$i]['category_title'])==0){
            unset($caterory_list[$i]);
            continue;
        }
        $caterory_list[$i]['category_description'] = get_langstring($caterory_list[$i]['category_description'], $lang);
        $caterory_list[$i]['URL'] = str_replace(
            Array('{site_id}'   , '{lang}','{category_id}','{path}','{category_code}'), 
            Array((int) $site_id, $lang  , $caterory_list[$i]['category_id'], $caterory_list[$i]['path'],$caterory_list[$i]['category_code']),
            \e::config('url_pattern_category'));
        // str_replace('{category_id}', $caterory_list[$i]['category_id'], $category_url_pattern);

        $caterory_list[$i]['number_of_news'] = 0;

        $caterory_list[$i]['category_icon'] = json_decode($caterory_list[$i]['category_icon'], true);
    }
    // prn($caterory_list);
    $caterory_list= array_values($caterory_list);
    // ------------------ adjust list of categories - end ----------------------
    // ------------------ get number of news - begin ---------------------------
    $category_ids = Array();
    $category_ids[] = 0;
    foreach ($caterory_list as $cat) {
        $category_ids[] = (int) $cat['category_id'];
    }
    $category_ids = join(',', $category_ids);
    $query = "SELECT category_id, count(news_id) as n_news
           FROM <<tp>>news_category
           WHERE category_id in({$category_ids}) GROUP BY category_id";
    $number_of_news = \e::db_getrows($query);

    foreach ($number_of_news as $n_news) {
        for ($i = 0; $i < $cnt; $i++) {
            if ($caterory_list[$i]['category_id'] == $n_news['category_id']) {
                $caterory_list[$i]['number_of_news']+=$n_news['n_news'];
                $deep = $caterory_list[$i]['deep'];
                for ($j = $i - 1; $j >= 0; $j--) {
                    if ($deep > $caterory_list[$j]['deep']) {
                        $caterory_list[$j]['number_of_news']+=$n_news['n_news'];
                        $deep = $caterory_list[$j]['deep'];
                    }
                }
                break;
            }
        }
    }
    // ------------------ get number of news - end -----------------------------

    return $caterory_list;
}

// ------------ get category info - begin --------------------------------------
/*
  $this_category_info=category_info([
  'category_id'=> '...' | 'path' =>'...' | 'category_code'=''
  'site_id'=>''
  'lang'=>''
  ]);
 */
function category_info($options) {
    //prn($options);
    $where = Array();
    if (isset($options['category_id'])) {
        $where[0] = 'category_id=' . ( (int) $options['category_id'] );
    }
    if (isset($options['path'])) {
        $options['path'] = preg_replace("/\\/+$|^\\/+/", '', $options['path']);
        $where[0] = "path='" . \e::db_escape($options['path']) . "'";
    }
    if (isset($options['category_code'])) {
        $where[0] = "category_code='" . \e::db_escape($options['category_code']) . "'";
    }

    if (count($where) == 0) {
        $where[0] = 'start=0';
    }
    $where[1] = 'site_id=' . $options['site_id'];
    $where[2] = 'is_visible =1';
    $query = "SELECT * FROM <<tp>>category WHERE " . join(' AND ', $where);
    // prn($query);
    $this_category_info =\e::db_getonerow($query);
    if (!$this_category_info) {
        //die('Category not found');
        return Array();
    }

    $this_category_info['category_title_orig'] = $this_category_info['category_title'];
    $this_category_info['category_title'] = get_langstring($this_category_info['category_title'], $options['lang']);
    $this_category_info['category_title_short'] = get_langstring($this_category_info['category_title_short']);
    $this_category_info['category_meta'] = get_langstring($this_category_info['category_meta']);
    //if (strlen($this_category_info['category_title_short'])==0) {
    //    $this_category_info['category_title_short'] = shorten($this_category_info['category_title']);
    //}
    $this_category_info['category_description'] = get_langstring($this_category_info['category_description'], $options['lang']);
    $this_category_info['URL'] = str_replace(Array('{path}', '{lang}', '{site_id}', '{category_id}', '{category_code}'), Array($this_category_info['path'], $options['lang'], $options['site_id'], $this_category_info['category_id'], $this_category_info['category_code']), \e::config('url_pattern_category'));
    $this_category_info['date_lang_update'] = get_langstring($this_category_info['date_lang_update'], $options['lang']);
    //prn($this_category_info);
    // $this_category_info['category_icon'] = json_decode($this_category_info['category_icon'], true);
    if (!is_array($this_category_info['category_icon'])) {
        $this_category_info['category_icon'] = json_decode($this_category_info['category_icon'], true);
    }
    return $this_category_info;
}

// ------------ get category info - end ----------------------------------------



class CategoryViewModel {

    protected $lang;
    protected $site_info;
    protected $category_info;
    private $category_parents;
    private $category_children;
    private $deep = 1;
    private $cache_path;
    private $useCache=false;

    public function __construct($site_info, $category_info, $lang) {
        $this->lang = $lang;
        $this->site_info = $site_info;
        $this->category_info = $category_info;

        $this->cache_path=\e::config('CACHE_ROOT')."/{$this->site_info['dir']}/category_{$this->category_info['category_id']}_{$this->lang}.cache";
        \core\fileutils::path_create(\e::config('CACHE_ROOT'), $this->cache_path);
        
        $tmp = \core\fileutils::get_cached_info($this->cache_path, 600);
        if ($tmp) {
            $this->deep=$tmp->deep;
            $this->category_parents=$tmp->category_parents;
            $this->category_children=$tmp->category_children;
            $this->useCache=true;
        }
    }

    public function setDeep($val) {
        $val = (int) $val;
        if($val!=$this->deep){
            $this->deep = (int) $val;
            if ($this->deep < 1) {
                $this->deep = 1;
            }
            $this->useCache=false;
            unset($this->category_children);
        }
        return '';
    }

    public function __get($attr) {

        switch ($attr) {
            case 'current':
            case 'info':
                return $this->category_info;

            case 'parents':
                if (!isset($this->category_parents)) {
                    $this->loadParents();
                }
                return $this->category_parents;

            case 'children':
                if (!isset($this->category_children)) {
                    $this->loadChildren();
                }
                return $this->category_children;

            default: return Array();
        }
    }

    private function getView($_info) {
        $tor = $_info;

        $tor['category_title_orig'] = $tor['category_title'];
        $tor['category_title'] = get_langstring($tor['category_title'], $this->lang);
        $tor['category_title_short'] = get_langstring($tor['category_title_short'], $this->lang);
        if (strlen($tor['category_title_short'])==0) {
            $tor['category_title_short'] = shorten($tor['category_title']);
        }
        if (!is_array($tor['category_icon'])) {
            $tor['category_icon'] = json_decode($tor['category_icon'], true);
        }
        $tor['category_description'] = get_langstring($tor['category_description'], $this->lang);
        $tor['category_description_short'] = get_langstring($tor['category_description_short'], $this->lang);
        $tor['category_description_exists'] = strlen($tor['category_description']) > 0;
        if(is_valid_url($tor['category_description'])){
            $tor['URL'] = $tor['category_description'];
            $tor['redirectURL'] = $tor['category_description'];
        }else{
            $tor['URL'] = str_replace(
                    Array('{path}', '{lang}', '{site_id}', '{category_id}', '{category_code}'), 
                    Array($tor['path'], $this->lang, $this->site_info['id'], $tor['category_id'], $tor['category_code']), \e::config('url_pattern_category'));
            $tor['redirectURL'] = '';
        }

        return $tor;
    }

    private function loadParents() {
        // ---------------------------- get parents - begin ------------------------
        $query = "select pa.category_id, pa.site_id, pa.category_code, pa.category_title,
                      pa.start, pa.finish, pa.is_deleted, pa.deep, pa.is_part_of,
                      pa.see_also, pa.is_visible, pa.path, pa.category_icon,
                      pa.category_title_short, pa.category_description, pa.category_description_short
               from <<tp>>category pa, <<tp>>category ch
               WHERE ch.category_id={$this->category_info['category_id']} 
                 and ch.site_id={$this->site_info['id']} 
                 and pa.site_id={$this->site_info['id']}
                 and pa.start<ch.start and ch.finish<pa.finish
               order by pa.start asc";
        $this->category_parents = \e::db_getrows($query);

        // all parents should be visible
        $parents_are_visible = true;
        $cnt = count($this->category_parents);
        for ($i = 0; $i < $cnt; $i++) {
            $this->category_parents[$i] = $this->getView($this->category_parents[$i]);
            if ($this->category_parents[$i]['is_visible'] != 1) {
                $parents_are_visible = false;
                break;
            }
        }
        if (!$parents_are_visible) {
            die('Category is hidden');
        }
        // echo '<!-- '; 
        //prn($this->category_parents); 
        // echo ' -->';
        // ---------------------------- get parents - end --------------------------
        if(!$this->useCache && $this->category_children && $this->category_parents){
            \core\fileutils::set_cached_info($this->cache_path, $this);
        }
    }

    private function loadChildren() {

        // ------------------- get children - begin --------------------------------
        //        $query = "select ch.category_id, ch.site_id, ch.category_code, ch.category_title,
        //                      ch.start, ch.finish, ch.is_deleted, ch.deep, ch.is_part_of,
        //                      ch.see_also, ch.is_visible, ch.path, ch.category_description,
        //                      ch.category_icon, ch.category_title_short
        //               from <<tp>>category pa, <<tp>>category ch
        //               WHERE pa.category_id={$this->category_info['category_id']} and ch.site_id={$this->site_info['id']} and ch.is_visible
        //                 and pa.site_id={$this->site_info['id']} 
        //                 and ( ch.deep between " . ($this->category_info['deep'] + 1 ) . " AND " . ($this->category_info['deep'] + $this->deep ) . " )
        //                 and pa.start<ch.start and ch.finish<pa.finish
        //               order by ch.start asc";

        $query = "select ch.category_id, ch.site_id, ch.category_code, ch.category_title,
                      ch.start, ch.finish, ch.is_deleted, ch.deep, ch.is_part_of,
                      ch.see_also, ch.is_visible, ch.path, ch.category_description,
                      ch.category_icon, ch.category_title_short, ch.category_description_short,
                      BIT_AND(pa.is_visible) as parentsVisible, ch.start,ch.finish
               from <<tp>>category pa, <<tp>>category ch
               WHERE ch.site_id={$this->site_info['id']}
                 AND ch.is_visible
                 AND pa.site_id={$this->site_info['id']} 
                 AND ( ch.deep between " . ($this->category_info['deep'] + 1 ) . " AND " . ($this->category_info['deep'] + $this->deep ) . " )
                 AND {$this->category_info['start']}<ch.start AND ch.finish<{$this->category_info['finish']}
                 AND pa.start<ch.start and ch.finish<pa.finish
               GROUP BY ch.category_id
               HAVING parentsVisible
               order by ch.start asc";
        // prn(checkStr($query));
        $this->category_children = \e::db_getrows($query);
        // prn($this->category_children);
        $cnt = count($this->category_children);
        for ($i = 0; $i < $cnt; $i++) {
            $this->category_children[$i] = $this->getView($this->category_children[$i]);
            $this->category_children[$i]['category_description_exists']=(
                strlen(trim($this->category_children[$i]['category_description']))>0
                || ( $this->category_children[$i]['finish']-$this->category_children[$i]['start'] > 1)
            );
            $this->category_children[$i]['category_description']='';
            if (!$this->category_children[$i]['category_title_short']) {
                unset($this->category_children[$i]);
                continue;
            }
            $this->category_children[$i]['children']=[];
        }
        $this->category_children = array_values($this->category_children);
        
        if($this->deep > 1){
            $root_deep=$this->category_info['deep'] + 1;
            for($i=count($this->category_children)-1;$i>=0;$i--){
                $child_deep=$this->category_children[$i]['deep'];
                if($child_deep>$root_deep){
                    for($j=$i-1;$j>=0; $j-- ){
                        if($this->category_children[$j] && $this->category_children[$j]['deep']<$child_deep){
                           array_unshift($this->category_children[$j]['children'], $this->category_children[$i] );
                           unset($this->category_children[$i]);
                           break;
                        }
                    }
                }
            }
        }
        $this->category_children = array_values($this->category_children);
        // prn($this->category_children);
        // ------------------- get children - end ----------------------------------
        
        if(!$this->useCache && $this->category_children && $this->category_parents){
            \core\fileutils::set_cached_info($this->cache_path, $this);
        }
    }

}

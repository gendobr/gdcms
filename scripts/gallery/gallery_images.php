<?php

// -------------------- get gallery images - lazy list - begin -----------------
class GalleryImages {

    // input data
    protected $lang, $this_site_info, $rozdilizformy, $keywords, $start;
    // parameters
    public $rowsPerPage = 10;
    private $orderBy = 'rozdil ASC, id ASC';
    private $showImagesFromSubcategories = true;
    // mapping of the sortable_columns
    private $sortable_columns = Array(
        'date' => 'id',
        'id' => 'id',
        'category' => 'rozdil',
        'rozdil' => 'rozdil',
        'tag' => 'rozdil',
        'title' => 'pidpys',
        'name' => 'pidpys',
        'pidpys' => 'pidpys',
        'author' => 'autor',
        'creator' => 'autor',
        'photograph' => 'autor',
        'autor' => 'autor',
        'year' => 'rik',
        'rik' => 'rik',
        'rand' => 'rand_ind',
        'rnd' => 'rand_ind',
        'random' => 'rand_ind'
    );
    // output data
    protected $_list, $_pages, $_items_found;

    function __construct($lang, $this_site_info, $start, $rozdilizformy, $keywords) {
        $this->lang = $lang;
        $this->this_site_info = $this_site_info;
        $this->start = (int) $start;
        $this->rozdilizformy = $rozdilizformy;
        $this->keywords = $keywords;
    }

    function __get($attr) {
        // hack to set rowsPerPage parameter
        if (substr($attr, 0, 12) == 'rowsPerPage_') {
            $opt = explode('_', $attr);
            $this->rowsPerPage = (int) $opt[1];
            if ($this->rowsPerPage <= 0) {
                $this->rowsPerPage = 10;
            }
            return null;
        }
        // hack to set orderBy parameter
        if (substr($attr, 0, 8) == 'orderBy_') {
            $opt = explode('_', $attr);
            $cnt = count($opt);
            $ordering = Array();
            for ($i = 1; $i < $cnt; $i+=2) {
                if (isset($this->sortable_columns[$opt[$i]])) {
                    $ordering[] = $this->sortable_columns[$opt[$i]] . ' ' . (strtolower($opt[$i + 1] == 'desc' ? 'desc' : 'asc'));
                }
            }
            if (count($ordering) > 0) {
                $this->orderBy = join(', ', $ordering);
            }
            return null;
        }
        // hack to set showImagesFromSubcategories parameter
        if (substr($attr, 0, 28) == 'showImagesFromSubcategories_') {
            $opt = explode('_', $attr);
            $this->showImagesFromSubcategories = strtolower($opt[1]) == 'yes';
            return null;
        }

        switch ($attr) {
            case 'list':
                if (!isset($this->_list)) {
                    $this->_list = $this->get_list();
                }
                return $this->_list;
                break;
            case 'items_found':
                if (!isset($this->_list)) {
                    $this->_list = $this->get_list();
                }
                return $this->_items_found;
                break;
            case 'paging_links':
                if (!isset($this->_list)) {
                    $this->_list = $this->get_list();
                }
                return $this->paging_links;
                break;
            default: return Array();
        }
    }

    private function get_list() {

        if (strlen($this->keywords) > 0) {
            // ---------------------- show search results - begin --------------
            $kw = explode(' ', $this->keywords);
            $cnt = count($kw);
            $cond = Array();
            for ($i = 0; $i < $cnt; $i++) {
                $word = trim($kw[$i]);
                if (strlen($word) > 0) {
                    $cond[] = " locate('" . \e::db_escape($word) . "',rozdil)>0 ";
                }
            }
            if (count($cond) > 0) {
                $cond = join(' AND ', $cond) . ' AND ';
            } else {
                $cond = '';
            }

            $images = \e::db_getrows(
                    "SELECT SQL_CALC_FOUND_ROWS *,
                            FLOOR(1 + RAND() * 10000) as rand_ind
                     FROM {$GLOBALS['table_prefix']}photogalery
                     WHERE $cond vis = 1
                       AND site = {$this->this_site_info['id']}
                     ORDER BY {$this->orderBy}
                     LIMIT {$this->start}, {$this->rowsPerPage}");
            // ---------------------- show search results - end ----------------
        } else {
            // ---------------------- show images from one category - begin ----
            $cond='';
            if ($this->rozdilizformy) {
                //$cond="AND rozdil like '" . DbStr($this->rozdilizformy) . "/%'";
                if ($this->showImagesFromSubcategories) {
                    $cond.= "AND (rozdil = '" . \e::db_escape($this->rozdilizformy) . "' OR rozdil like '" . \e::db_escape($this->rozdilizformy) . "/%')";
                } else {
                    $cond = "AND rozdil = '" . \e::db_escape($this->rozdilizformy) . "'";
                }
            } else {
                if (!$this->showImagesFromSubcategories) {
                    $cond = "AND rozdil = ''";
                }
            }
            $query = "SELECT SQL_CALC_FOUND_ROWS *,
                            FLOOR(1 + RAND() * 10000) as rand_ind
                     FROM {$GLOBALS['table_prefix']}photogalery
                     WHERE  vis = 1
                       AND site = {$this->this_site_info['id']}
                       {$cond}
                     ORDER BY {$this->orderBy}
                     LIMIT {$this->start}, {$this->rowsPerPage}
                     ";
            //prn($query);
            $images = \e::db_getrows($query);
            // ---------------------- show images from one category - end ------
        }


        $cnt = count($images);
        $url_prefix = preg_replace("/\\/+$/", '', $this->this_site_info['url']) . '/gallery';
        for ($i = 0; $i < $cnt; $i++) {
            $images[$i]['url_details'] = str_replace(
                    Array('{item}', '{site_id}', '{lang}'), Array($images[$i]['id'], $this->this_site_info['id'], $this->lang), url_pattern_gallery_image);
            $images[$i]['url_thumbnail'] = $url_prefix . '/' . $images[$i]['photos_m'];
            $images[$i]['url_big'] = $url_prefix . '/' . $images[$i]['photos'];
        }
        $n_records =\e::db_getonerow("SELECT FOUND_ROWS() AS n_records;");

        $this->_items_found = (int) $n_records['n_records'];

        // url template for
        $url_template = str_replace(
                Array('{rozdil2}','{rozdilizformy}', '{site_id}', '{lang}', '{keywords}'), 
                Array(encode_dir_name($this->rozdilizformy),rawurlencode($this->rozdilizformy), $this->this_site_info['id'], $this->lang, rawurlencode($this->keywords)), url_pattern_gallery_category);

        $this->paging_links = $this->get_paging_links($this->start, $this->_items_found, $this->rowsPerPage, $url_template);
        //prn($images);
        return $images;
    }

    private function get_paging_links($start, $n_records, $rows_per_page, $url_template) {

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

    public function setOrderBy($attr) {
        $opt = explode('_', $attr);
        $cnt = count($opt);
        //prn($opt);
        $ordering = Array();
        for ($i = 0; $i < $cnt; $i+=2) {
            if (isset($this->sortable_columns[$opt[$i]])) {
                $ordering[] = $this->sortable_columns[$opt[$i]] . ' ' . (strtolower($opt[$i + 1] == 'desc' ? 'desc' : 'asc'));
            }
        }
        if (count($ordering) > 0) {
            $this->orderBy = join(', ', $ordering);
        }
    }

}

// -------------------- get gallery images - lazy list - end -------------------
?>
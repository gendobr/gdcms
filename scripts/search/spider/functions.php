<?php

# --------------------- function to validate one URL - begin -------------------
# $url is string, URL
# $site_info is one database record
#

function is_searchable($url, $site_info) {
    $tor = false;
    if (!is_valid_url($url)) {
        echo "Invalid URL syntax ($url) <br/>";
        return false;
    }
    # ------------------ create/load validation rules - begin ------------------
    if (!isset($site_info['regexp_validation'])) {
        $repl = Array('/' => "\\/", '.' => "\\.");
        $site_info['regexp_validation'] = Array();

        # the page is inside of site directory
        $site_info['regexp_validation'][] = '/' . str_replace(array_keys($repl), array_values($repl), "^{$site_info['url']}") . '/i';

        # common scripts like guestbook, forum etc.
        $site_info['regexp_validation'][] = '/' .
                str_replace(
                        array_keys($repl)
                        , array_values($repl)
                        , "^" . sites_root_URL . "/"
                ) . "\\w+\\.php\\?([^&]+&)*site_id={$site_info['id']}($|&)/i";
        $site_info['regexp_validation'][] = '/' .
                str_replace(
                        array_keys($repl)
                        , array_values($repl)
                        , "^" . site_root_URL . "/"
                ) . "\\w+\\.php\\?([^&]+&)*site_id={$site_info['id']}($|&)/i";
        // prn($site_info['regexp_validation']);
        if (strlen(trim($site_info['search_validate_url'])) > 0) {
            $tmp = explode("\n", $site_info['search_validate_url']);
            foreach ($tmp as $rule) {
                $rule = trim($rule);
                if (strlen($rule) > 0)
                    $site_info['regexp_validation'][] = $rule;
            }
        }
    }
    # ------------------ create/load validation rules - end --------------------
    # ------------------ create/load exclusion rules - begin -------------------
    if (!isset($site_info['regexp_exclusion'])) {
        $repl = Array('/' => "\\/", '.' => "\\.");
        $site_info['regexp_exclusion'] = Array();
        $site_info['regexp_exclusion'][] = "/\\.doc(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.pdf(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.gif(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.jpg(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.jpeg(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.png(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.css(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.rar(\\?.*)?/i";
        $site_info['regexp_exclusion'][] = "/\\.zip(\\?.*)?/i";

        $site_info['regexp_exclusion'][] = "/^ftp:\\/\\//i";
        $site_info['regexp_exclusion'][] = "/^mailto:/i";

        $site_info['regexp_exclusion'][] = "/forum_id=\\d+/i";
        $site_info['regexp_exclusion'][] = "/forum\\.php/i";
        $site_info['regexp_exclusion'][] = "/action=forum\\//i";

        $site_info['regexp_exclusion'][] = "/guestbook\\.php/i";
        $site_info['regexp_exclusion'][] = "/action=gb\\//i";
        $site_info['regexp_exclusion'][] = "/action=calendar(\\/|%2F)month/i";

        //prn($site_info['regexp_exclusion']);

        if (strlen(trim($site_info['search_exclude_url'])) > 0) {
            $tmp = explode("\n", $site_info['search_exclude_url']);
            foreach ($tmp as $rule) {
                $rule = trim($rule);
                if (strlen($rule) > 0)
                    $site_info['regexp_exclusion'][] = $rule;
            }
        }
    }
    # ------------------ create/load exclusion rules - end ---------------------
    # ------------------- apply validation rules - begin -----------------------
    foreach ($site_info['regexp_validation'] as $rule) {
        $tmp = @preg_match($rule, $url);
        #prn($url." against ".$rule.' => '. ($tmp?'yes':'no'));
        $tor = $tor || $tmp;
        if ($tor) {
            echo "<code><b><font color=green>valid:   </font></b></code>$url against $rule <br>";
            break;
        }
    }
    if (!$tor){
        echo "<code><b><font color=green>invalid:   </font></b></code>$url no inclusive rules found<br>";
        return false;
    }
    # ------------------- apply validation rules - end ------------------------- 
    # ------------------- apply exclusion rules - begin ------------------------
    foreach ($site_info['regexp_exclusion'] as $rule) {
        $tmp = @preg_match($rule, $url);
        if ($tmp) {
            echo "<code><b><font color=red>invalid : </font></b></code>$url  against $rule <br>";
            return false;
        }
    }
    # ------------------- apply exclusion rules - end -------------------------- 

    echo "<code><b><font color=green>valid:   </font></b></code>$url no rules <br>";
    return true;
}

# --------------------- function to validate one URL - end ---------------------

function get_links($url, $html, $site_info) {


    $links = preg_match_all("/(<a (?:(?:[^>]+)|(?:\"[^\"]*\")|(?:'[^']*')))/i"
            , $html
            , $matches);
    if (!$links) {
        return Array();
    }
    // prn($matches[0]);

    $tor = Array();
    $this_url_dirname = parse_url($url);
    # prn($this_url_dirname);
    if (!preg_match("/\\/\$/", $this_url_dirname['path'])) {
        $this_url_dirname['path'] = dirname($this_url_dirname['path']) . '/';
    }
    $url_prefix = $this_url_dirname['scheme'] . '://' . $this_url_dirname['host'];
    if (isset($this_url_dirname['port']))
        $url_prefix.=':' . $this_url_dirname['port'];
    $url_prefix.=$this_url_dirname['path'];

    preg_match("/^http:\\/\\/[^\\/]+/i", $url, $host_root_url);
    // prn($host_root_url); exit();
    $host_root_url = $host_root_url[0];

    foreach ($matches[0] as $mt) {
        #prn(checkStr($mt));
        $link = '';
        if (preg_match("/href=\"([^\"]*)\"/i", $mt, $regs)) {
            # prn($regs);
            $link = $regs[1];
        } elseif (preg_match("/href='([^']*)'/", $mt, $regs)) {
            # prn($regs);
            $link = $regs[1];
        } elseif (preg_match("/href=([^ >]*)/", $mt, $regs)) {
            # prn($regs);
            $link = $regs[1];
        }
        # prn($link);
        if (strlen($link) > 0) {
            # ------------------- relative link - begin ---------------------------
            if (!preg_match('/^(ftp|mailto|https?):/', $link)) {
                if (preg_match("/^\\//", $link)) {
                    $link = $host_root_url . '/' . $link;
                } else {
                    $link = $url_prefix . $link;
                }

                //while(ereg('/[^/]+/\.\./',$link)) {
                while (preg_match("/\\/[^\\/]+\\/\\.\\.\\//", $link)) {
                    $link = preg_replace('/[^\\/]+\\/\.\./', '/', $link);
                }
                while (preg_match("/\\/\\.\\//", $link)) {
                    $link = preg_replace('/\./', '/', $link);
                }
            }
            # ------------------- relative link - end -----------------------------
            #prn('before $link='.$link);
            $link = preg_replace("/#.*\$/", '', $link);
            $link = preg_replace("/\\/+/", '/', $link);
            $link = preg_replace("/^http:\\//", 'http://', $link);
            #prn('after $link='.$link);

            if (is_searchable($link, $site_info)) {
                $tor[] = $link;
            }
        }
    }
    return array_unique($tor);
}

function removeTag($html, $openTag, $closeTag) {

    $openTag = strtoupper($openTag);
    $closeTag = strtoupper($closeTag);

    $result = $html;
    $code = strtoupper($html);
    $posStart = strpos($code, $openTag);
    $posFinish = strpos($code, $closeTag);

    while(! ($posStart === false )){
        // remove block
        $result = substr($result, 0, $posStart).substr($result, $posFinish + strlen($closeTag));
        $code = substr($code, 0, $posStart).substr($code, $posFinish + strlen($closeTag));
        $posStart = strpos($code, $openTag);
        $posFinish = strpos($code, $closeTag);        
    }
    return $result;
}
// echo removeTag('p', '1234567890<p class="x">p1p2p3p4p5p6p7p8p9p0</p>abcdefghij');

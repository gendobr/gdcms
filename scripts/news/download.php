<?php

/*
  Download news from list of urls like
 * 2015-02-13 http://some.server.com/news/12345
 * 2015-02-13 http://some.server.com/news/12346
 * 2015-02-13 http://some.server.com/news/12347
 */


$debug = false;
run('site/menu');
//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);


// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
// ------------------ do download - begin --------------------------------------
if (isset($input_vars['url'])) {

    global $main_template_name;
    $main_template_name = '';

    /**
     * Punycode implementation as described in RFC 3492
     *
     * @link http://tools.ietf.org/html/rfc3492
     */
    class Punycode {

        /**
         * Bootstring parameter values
         *
         */
        const BASE = 36;
        const TMIN = 1;
        const TMAX = 26;
        const SKEW = 38;
        const DAMP = 700;
        const INITIAL_BIAS = 72;
        const INITIAL_N = 128;
        const PREFIX = 'xn--';
        const DELIMITER = '-';

        /**
         * Encode table
         *
         * @param array
         */
        protected static $_encodeTable = array(
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
            'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
            'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        );

        /**
         * Decode table
         *
         * @param array
         */
        protected static $_decodeTable = array(
            'a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5,
            'g' => 6, 'h' => 7, 'i' => 8, 'j' => 9, 'k' => 10, 'l' => 11,
            'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17,
            's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23,
            'y' => 24, 'z' => 25, '0' => 26, '1' => 27, '2' => 28, '3' => 29,
            '4' => 30, '5' => 31, '6' => 32, '7' => 33, '8' => 34, '9' => 35
        );

        /**
         * Encode a domain to its Punycode version
         *
         * @param string $input Domain name in Unicde to be encoded
         * @return string Punycode representation in ASCII
         */
        public function encode($input) {
            $parts = explode('.', $input);
            foreach ($parts as &$part) {
                $part = $this->_encodePart($part);
            }

            return implode('.', $parts);
        }

        /**
         * Encode a part of a domain name, such as tld, to its Punycode version
         *
         * @param string $input Part of a domain name
         * @return string Punycode representation of a domain part
         */
        protected function _encodePart($input) {
            $codePoints = $this->_codePoints($input);

            $n = static::INITIAL_N;
            $bias = static::INITIAL_BIAS;
            $delta = 0;
            $h = $b = count($codePoints['basic']);

            $output = '';
            foreach ($codePoints['basic'] as $code) {
                $output .= $this->_codePointToChar($code);
            }
            if ($input === $output) {
                return $output;
            }
            if ($b > 0) {
                $output .= static::DELIMITER;
            }

            $codePoints['nonBasic'] = array_unique($codePoints['nonBasic']);
            sort($codePoints['nonBasic']);

            $i = 0;
            $length = mb_strlen($input);
            while ($h < $length) {
                $m = $codePoints['nonBasic'][$i++];
                $delta = $delta + ($m - $n) * ($h + 1);
                $n = $m;

                foreach ($codePoints['all'] as $c) {
                    if ($c < $n || $c < static::INITIAL_N) {
                        $delta++;
                    }
                    if ($c === $n) {
                        $q = $delta;
                        for ($k = static::BASE;; $k += static::BASE) {
                            $t = $this->_calculateThreshold($k, $bias);
                            if ($q < $t) {
                                break;
                            }

                            $code = $t + (($q - $t) % (static::BASE - $t));
                            $output .= static::$_encodeTable[$code];

                            $q = ($q - $t) / (static::BASE - $t);
                        }

                        $output .= static::$_encodeTable[$q];
                        $bias = $this->_adapt($delta, $h + 1, ($h === $b));
                        $delta = 0;
                        $h++;
                    }
                }

                $delta++;
                $n++;
            }

            return static::PREFIX . $output;
        }

        /**
         * Decode a Punycode domain name to its Unicode counterpart
         *
         * @param string $input Domain name in Punycode
         * @return string Unicode domain name
         */
        public function decode($input) {
            $parts = explode('.', $input);
            foreach ($parts as &$part) {
                if (strpos($part, static::PREFIX) !== 0) {
                    continue;
                }

                $part = substr($part, strlen(static::PREFIX));
                $part = $this->_decodePart($part);
            }

            return implode('.', $parts);
        }

        /**
         * Decode a part of domain name, such as tld
         *
         * @param string $input Part of a domain name
         * @return string Unicode domain part
         */
        protected function _decodePart($input) {
            $n = static::INITIAL_N;
            $i = 0;
            $bias = static::INITIAL_BIAS;
            $output = '';

            $pos = strrpos($input, static::DELIMITER);
            if ($pos !== false) {
                $output = substr($input, 0, $pos++);
            } else {
                $pos = 0;
            }

            $outputLength = strlen($output);
            $inputLength = strlen($input);
            while ($pos < $inputLength) {
                $oldi = $i;
                $w = 1;

                for ($k = static::BASE;; $k += static::BASE) {
                    $digit = static::$_decodeTable[$input[$pos++]];
                    $i = $i + ($digit * $w);
                    $t = $this->_calculateThreshold($k, $bias);

                    if ($digit < $t) {
                        break;
                    }

                    $w = $w * (static::BASE - $t);
                }

                $bias = $this->_adapt($i - $oldi, ++$outputLength, ($oldi === 0));
                $n = $n + (int) ($i / $outputLength);
                $i = $i % ($outputLength);
                $output = mb_substr($output, 0, $i) . $this->_codePointToChar($n) . mb_substr($output, $i, $outputLength - 1);

                $i++;
            }

            return $output;
        }

        /**
         * Calculate the bias threshold to fall between TMIN and TMAX
         *
         * @param integer $k
         * @param integer $bias
         * @return integer
         */
        protected function _calculateThreshold($k, $bias) {
            if ($k <= $bias + static::TMIN) {
                return static::TMIN;
            } elseif ($k >= $bias + static::TMAX) {
                return static::TMAX;
            }
            return $k - $bias;
        }

        /**
         * Bias adaptation
         *
         * @param integer $delta
         * @param integer $numPoints
         * @param boolean $firstTime
         * @return integer
         */
        protected function _adapt($delta, $numPoints, $firstTime) {
            $delta = (int) (
                    ($firstTime) ? $delta / static::DAMP : $delta / 2
                    );
            $delta += (int) ($delta / $numPoints);

            $k = 0;
            while ($delta > ((static::BASE - static::TMIN) * static::TMAX) / 2) {
                $delta = (int) ($delta / (static::BASE - static::TMIN));
                $k = $k + static::BASE;
            }
            $k = $k + (int) (((static::BASE - static::TMIN + 1) * $delta) / ($delta + static::SKEW));

            return $k;
        }

        /**
         * List code points for a given input
         *
         * @param string $input
         * @return array Multi-dimension array with basic, non-basic and aggregated code points
         */
        protected function _codePoints($input) {
            $codePoints = array(
                'all' => array(),
                'basic' => array(),
                'nonBasic' => array(),
            );

            $length = mb_strlen($input);
            for ($i = 0; $i < $length; $i++) {
                $char = mb_substr($input, $i, 1);
                $code = $this->_charToCodePoint($char);
                if ($code < 128) {
                    $codePoints['all'][] = $codePoints['basic'][] = $code;
                } else {
                    $codePoints['all'][] = $codePoints['nonBasic'][] = $code;
                }
            }

            return $codePoints;
        }

        /**
         * Convert a single or multi-byte character to its code point
         *
         * @param string $char
         * @return integer
         */
        protected function _charToCodePoint($char) {
            $code = ord($char[0]);
            if ($code < 128) {
                return $code;
            } elseif ($code < 224) {
                return (($code - 192) * 64) + (ord($char[1]) - 128);
            } elseif ($code < 240) {
                return (($code - 224) * 4096) + ((ord($char[1]) - 128) * 64) + (ord($char[2]) - 128);
            } else {
                return (($code - 240) * 262144) + ((ord($char[1]) - 128) * 4096) + ((ord($char[2]) - 128) * 64) + (ord($char[3]) - 128);
            }
        }

        /**
         * Convert a code point to its single or multi-byte character
         *
         * @param integer $code
         * @return string
         */
        protected function _codePointToChar($code) {
            if ($code <= 0x7F) {
                return chr($code);
            } elseif ($code <= 0x7FF) {
                return chr(($code >> 6) + 192) . chr(($code & 63) + 128);
            } elseif ($code <= 0xFFFF) {
                return chr(($code >> 12) + 224) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
            } else {
                return chr(($code >> 18) + 240) . chr((($code >> 12) & 63) + 128) . chr((($code >> 6) & 63) + 128) . chr(($code & 63) + 128);
            }
        }

    }

    $url = $input_vars['url'];
    if (!parse_url($url)) {
        echo '{"status":"error","message":"invalid URL"}';
        return;
    }

    $query = "SELECT count(*) as n FROM {$GLOBALS['table_prefix']}news WHERE site_id=$site_id AND LOCATE('" . DbStr($url) . "',abstract)";
    $nnews = db_getonerow($query);
    if ($nnews['n'] > 0) {
        echo '{"status":"error","message":"news already imported"}';
        return;
    }

    $lang = $input_vars['lang'];
    $lang_list = list_of_languages();
    $found = false;
    foreach ($lang_list as $ln) {
        if ($lang == $ln['lang']) {
            $found = true;
        }
    }
    if (!$found) {
        $lang = default_language;
    }


    // 
    $site_id = $this_site_info['id'];

    $dateString = $input_vars['date'];
    if (preg_match("/^(\\d{2})(\\d{2})\$/", $dateString, $matches)) {
        $year = (int) date('Y');
        $month = $matches[1];
        $day = $matches[2];
        $last_change_date = date("Y-m-d H:i:s", mktime(12, 0, 0, $month, $day, $year));
    } elseif (checkDatetime($dateString)) {
        $last_change_date = date("Y-m-d H:i:s", strtotime($dateString));
    } else {
        $last_change_date = date("Y-m-d H:i:s");
    }
    $category_id = (int) $input_vars['category_id'];


    $content = '';
    $weight = 0;
    $creation_date = date('Y-m-d H:i:s');
    $news_code = '';
    $news_meta_info = '';
    $news_extra_1 = '';
    $news_extra_2 = '';
    $cense_level = $this_site_info['cense_level'];
    $tags = '';
    // downloading one url


    run('lib/simple_html_dom');
    $html = file_get_html($url);
    if (!$html) {
        echo '{"status":"error","message":"cannot download URL"}';
        return;
    }
    // echo $html->plaintext;

    $title = $html->find("title", 0);
    if ($title) {
        $title = $title->plaintext;
    } else {
        $title = '';
    }

    $abstract = '';
    foreach ($html->find('meta') as $element) {
        if ($element->name == 'description') {
            $abstract = $element->content;
        }
        if ($element->name == 'og:description') {
            $abstract = $element->content;
        }
    }

    $abstract.= "<p><a href=\"$url\" target=_blank>$url</a></p>";


    // calculate news id
    $query = "SELECT max(id) AS newid FROM {$table_prefix}news";
    $newid = db_getonerow($query);
    $news_id = $newid = 1 + (int) $newid['newid'];

    $query = "
        INSERT INTO {$GLOBALS['table_prefix']}news 
	(id, 
	lang, 
	site_id, 
	title, 
	content, 
	cense_level, 
	last_change_date, 
	abstract, 
	category_id, 
	tags, 
	expiration_date, 
	weight, 
	creation_date, 
	news_code, 
	news_meta_info, 
	news_extra_1, 
	news_extra_2
	)
	VALUES
	({$news_id}, 
	'{$lang}', 
	'{$site_id}', 
	'" . DbStr($title) . "', 
	'" . DbStr($content) . "', 
	'{$cense_level}', 
	'{$last_change_date}', 
	'" . DbStr($abstract) . "', 
        '{$category_id}', 
	'{$tags}', 
	 null, 
	'{$weight}', 
	'{$creation_date}', 
	'{$news_code}', 
	'{$news_meta_info}', 
	'{$news_extra_1}', 
	'{$news_extra_2}'
	);";
    //prn($query);
    db_execute($query);


    $query = "insert into {$GLOBALS['table_prefix']}news_category(news_id, category_id) VALUES({$news_id},{$category_id})";
    db_execute($query);
    echo '{"status":"success"}';
    return;
}


// ------------------ do download - end ----------------------------------------
# get list of all site categories
$query = "SELECT category_id, category_title, deep FROM {$table_prefix}category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
$tmp = db_getrows($query);
$list_of_categories = Array();
foreach ($tmp as $tm) {
    $list_of_categories[$tm['category_id']] = str_repeat(' + ', $tm['deep']) . get_langstring($tm['category_title']);
}
unset($tmp, $tm);
//prn($list_of_categories);


$input_vars['page_content'] = "
    <div>
        <div class=label>" . text('News_Category') . " : </div>
    	<select name=news_category  id=news_category>
           <option value=''></option>
           " . draw_options(0, $list_of_categories) . "
    	</select>    
    </div>
    <div>
        <div class=label>" . text('News_Sources') . " : </div>
        <textarea id=\"news_sources\" style=\"width:100%; height:300px;\"></textarea>
<pre>
2014-01-23  http://some.server.com/news/1234
2014-01-23  http://some.server.com/news/1235
2014-01-23  http://some.server.com/news/1214
</pre>

        <ol id=log></ol>
        <div id='loading' class=\"meter\" style=\"display:none;\"><span style=\"width: 100%\"></span></div>
    </div>
<input type=\"button\" id=\"doDownload\" value=\"" . text('News_start_import') . "\">
    

<script type=\"application/javascript\">
var newsList=[];

function startDownload(){
  var rows=$('#news_sources').val();
  if(rows.length>0){
    newsList=rows.split(/\\n/);
    var cid=$('#news_category').val();
    if(cid.length>0){
       $('#doDownload').hide();
       downloadNext();
    $('#doDownload').hide();
    }else{
       alert('" . text('News_Category') . "?????');
    }
  }else{
    $('#doDownload').show();
  }
}

function downloadNext(){
    if(newsList.length>0){
        var row=newsList[0].split(/[ \\t]+/);
        // console.log(row);
        $('#loading').show();
        $.ajax({
           type: \"POST\",
           url: \"index.php\",
           data: { action: \"news/download\", site_id: $site_id, url: row[1], date:row[0], category_id:$('#news_category').val(), lang:'{$_SESSION['lang']}'},
           dataType: \"json\"
        }).always(function( msg ) {
           var it=$('<li>' + msg.status + ' : '+row[1]+'</li>');
           $('#log').append(it);
           newsList.shift();
           document.getElementById('news_sources').value=newsList.join(\"\\n\");
           setTimeout(downloadNext, 20000);
        });    
    }else{
       $('#loading').hide();
       $('#doDownload').show();
       alert('DONE !!!');
    }
}

$(document).ready(function(){
   $(\"#doDownload\").click(startDownload);
});
</script>
<style type='text/css'>

.meter {
    background: none repeat scroll 0 0 rgb(85, 85, 85);
    border-radius: 25px;
    box-shadow: 0 -1px 1px rgba(255, 255, 255, 0.3) inset;
    height: 20px;
    margin: 60px 0 20px;
    padding: 10px;
    position: relative;
}
.meter > span {
    background-color: rgb(43, 194, 83);
    background-image: -moz-linear-gradient(center bottom , rgb(43, 194, 83) 37%, rgb(84, 240, 84) 69%);
    border-radius: 20px 8px 8px 20px;
    box-shadow: 0 2px 9px rgba(255, 255, 255, 0.3) inset, 0 -2px 6px rgba(0, 0, 0, 0.4) inset;
    display: block;
    height: 100%;
    overflow: hidden;
    position: relative;
}
.meter > span:after, .animate > span > span {
    animation: 2s linear 0s normal none infinite running move;
    background-image: -moz-linear-gradient(-45deg, rgba(255, 255, 255, 0.2) 25%, rgba(0, 0, 0, 0) 25%, rgba(0, 0, 0, 0) 50%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0.2) 75%, rgba(0, 0, 0, 0) 75%, rgba(0, 0, 0, 0));
    background-size: 50px 50px;
    border-radius: 20px 8px 8px 20px;
    bottom: 0;
    content: \"\";
    left: 0;
    overflow: hidden;
    position: absolute;
    right: 0;
    top: 0;
    z-index: 1;
}
.animate > span:after {
    display: none;
}
@keyframes move {
0% {
    background-position: 0 0;
}
100% {
    background-position: 50px 50px;
}
}
.orange > span {
    background-color: rgb(241, 161, 101);
    background-image: -moz-linear-gradient(center top , rgb(241, 161, 101), rgb(243, 109, 10));
}
.red > span {
    background-color: rgb(240, 163, 163);
    background-image: -moz-linear-gradient(center top , rgb(240, 163, 163), rgb(244, 35, 35));
}
.nostripes > span > span, .nostripes > span:after {
    animation: 0s ease 0s normal none 1 running none;
    background-image: none;
}

</style>

         ";

//--------------------------- context menu -- begin ----------------------------
$input_vars['page_title'] = text("News_import");
$input_vars['page_header'] = text("News_import");

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
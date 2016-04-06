<?php

global $smiles_list;
$smiles_list = Array(
    ':D' => "<img src=" . site_root_URL . "/img/smiles/icon_biggrin.gif width=15px height=15px alt=':D' border=0>",
    ':)' => "<img src=" . site_root_URL . "/img/smiles/icon_smile.gif width=15px height=15px alt=':)' border=0>",
    ':(' => "<img src=" . site_root_URL . "/img/smiles/icon_sad.gif width=15px height=15px alt=':(' border=0>",
    ':shock:' => "<img src=" . site_root_URL . "/img/smiles/icon_eek.gif width=15px height=15px alt=':shock:' border=0>",
    ':?' => "<img src=" . site_root_URL . "/img/smiles/icon_confused.gif width=15px height=15px alt=':?' border=0>",
    '8)' => "<img src=" . site_root_URL . "/img/smiles/icon_cool.gif width=15px height=15px alt='8)' border=0>",
    ':o' => "<img src=" . site_root_URL . "/img/smiles/icon_surprised.gif width=15px height=15px alt=':o' border=0>",
    ':lol:' => "<img src=" . site_root_URL . "/img/smiles/icon_lol.gif width=15px height=15px alt=':lol:' border=0>",
    ':x' => "<img src=" . site_root_URL . "/img/smiles/icon_mad.gif width=15px height=15px alt=':x' border=0>",
    ':P' => "<img src=" . site_root_URL . "/img/smiles/icon_razz.gif width=15px height=15px alt=':P' border=0>",
    ':red:' => "<img src=" . site_root_URL . "/img/smiles/icon_redface.gif width=15px height=15px alt=':red:' border=0>",
    ':cry:' => "<img src=" . site_root_URL . "/img/smiles/icon_cry.gif width=15px height=15px alt=':cry:' border=0>",
    ':evil:' => "<img src=" . site_root_URL . "/img/smiles/icon_evil.gif width=15px height=15px alt=':evil:' border=0>",
    ':twisted:' => "<img src=" . site_root_URL . "/img/smiles/icon_twisted.gif width=15px height=15px alt=':twisted:' border=0>",
    ':roll:' => "<img src=" . site_root_URL . "/img/smiles/icon_rolleyes.gif width=15px height=15px alt=':roll:' border=0>",
    ':wink:' => "<img src=" . site_root_URL . "/img/smiles/icon_wink.gif width=15px height=15px alt=':wink:' border=0>",
    ':|' => "<img src=" . site_root_URL . "/img/smiles/icon_neutral.gif width=15px height=15px alt=':|' border=0>",
    ':mrgreen:' => "<img src=" . site_root_URL . "/img/smiles/icon_mrgreen.gif width=15px height=15px alt=':mrgreen:' border=0>"
);

global $default_site_visitor_info;
$default_site_visitor_info = Array(
    'site_visitor_id' => 0
    , 'site_visitor_password' => ''
    , 'site_visitor_login' => 'Anonymous'
    , 'site_visitor_email' => ''
    , 'site_visitor_home_page_url' => ''
);

function show_message($msg) {
    $tmp = $msg;
    $tmp = checkStr($tmp);
    $tmp = str_replace("\r", '', $tmp);
    $tmp = preg_replace("/ +\\n/", "\n", $tmp);

    $tmp = str_replace("\n\n", '<br/>', $tmp);

    // apply BBcodes
    $tmp = enCodeBB($tmp);

    // show smiles
    $tmp = apply_smilies($tmp);

    // embed PDF
    $tmp = preg_replace_callback("/http:\\/\\/[^ ]+.pdf/i", function ($matches) {

                //                $tor = '';
                //                static $pdf_id;
                //                if (!isset($pdf_id)) {
                //                    $pdf_id = 1;
                //                    // $tor.='<script type="text/javascript" src="'.site_root_URL.'/scripts/lib/pdfobject.js"></script>';
                //                    $tor.="<script type=\"text/javascript\">
                //                              var pdf_files={};
                //                           </script>";
                //                }
                //                $tor.="<script type=\"text/javascript\">
                //                       pdf_files['pdf_file_{$pdf_id}']=\"$matches[0]\";
                //                       </script><div class=\"pdf_file\" id=\"pdf_file_{$pdf_id}\"><a href=\"{$matches[0]}\">{$matches[0]}</a></div>";
                //                // $tor.=strtolower("PDF FILE URL" . $matches[0] . "///");
                //                $pdf_id++;
                static $pdf_id;
                if (!isset($pdf_id)){
                    $pdf_id = 1;
                }
                $tor="<iframe src=\"{$matches[0]}\" id=\"pdf_file_{$pdf_id}\" class=\"pdf_file\"></iframe>
                      <div class=\"pdf_file_link\" id=\"pdf_file_link_{$pdf_id}\"><a href=\"{$matches[0]}\">{$matches[0]}</a></div>";
                $pdf_id++;
                return $tor;
            }, $tmp);

    // -------------------- custom wrapping - begin ------------------------------
     // $tmp=nl2br(wordwrap(str_replace('<',' <',$tmp), 60, "<br>",1));
        $ret = explode('<', $tmp);
        if (is_array($ret)) {
            if (count($ret) > 1) {
                # prn($ret);
                $cnt = count($ret);
                for ($i = 0; $i < $cnt; $i++) {
                    $ret[$i] = explode('>', $ret[$i]);
                    if (isset($ret[$i][1])) {
                        $ret[$i][1] = wordwrap($ret[$i][1], 60, " ", 1);
                    }
                    $ret[$i] = join('>', $ret[$i]);
                }
                $ret = join('<', $ret);
            }
            else
                $ret = wordwrap($ret[0], 60, " ", 1);
        }
        else {
            $ret = wordwrap($ret, 60, " ", 1);
        }
        $tmp = $ret;
    // -------------------- custom wrapping - end --------------------------------


    return $tmp;
}

function apply_smilies($msg) {
    global $smiles_list;
    return str_replace(array_keys($smiles_list), array_values($smiles_list), $msg);
}

function enCodeBB($msg, $admin = 0) {

    $pattern = array();
    $replacement = array();

    $pattern[] = "/\[url[=]?\](.+?)\[\/url\]/i";
    $replacement[] = "<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\1</a>";

    $pattern[] = "/\[url=((f|ht)tp[s]?:\/\/[^<> \n]+?)\](.+?)\[\/url\]/i";
    $replacement[] = "<a href=\"\\1\" target=\"_blank\" rel=\"nofollow\">\\3</a>";

    $pattern[] = "/\[img(left|right)?\](http:\/\/([^<> \n]+?)\.?(gif|jpg|jpeg|png)?)\[\/img\]/i";
    $replacement[] = '<img src="\\2" border="0" align="\\1" alt="">';

    $pattern[] = "/\[img(left|right)?\](http:\/\/([^<> \n]+?)\.?(gif|jpg|jpeg|png)?)\[\/img\]/i";
    $replacement[] = '<img src="\\2" border="0" align="\\1" alt="">';

    $pattern[] = "/\[[bB]\](.+?)\[\/[bB]\]/s";
    $replacement[] = '<b>\\1</b>';

    $pattern[] = "/\[[iI]\](.+?)\[\/[iI]\]/s";
    $replacement[] = '<i>\\1</i>';

    $pattern[] = "/\[[uU]\](.+?)\[\/[uU]\]/s";
    $replacement[] = '<u>\\1</u>';


    $pattern[] = "/\[quote\](.+?)\[\/quote\]/s";
    $replacement[] = '<div style="border-left:2px solid blue; padding:10px;">\\1</div>';

    $pattern[] = "/\[code\](.+?)\[\/code\]/s";
    $replacement[] = '<pre>\\1</pre>';


    if ($admin == 1) {
        $pattern[] = "/\[font(#[A-F0-9]{6})\](.+?)\[\/font\]/is";
        $replacement[] = '<font color="\\1">\\2</font>';
    }

    $msg = preg_replace($pattern, $replacement, $msg);
    if (substr_count($msg, '<img') > 0)
        $msg = str_replace('align=""', '', $msg);

    return $msg;
}

function get_forum_info($_forum_id) {
    $forum_id = (int) $_forum_id;
    $this_forum_info =\e::db_getonerow("SELECT * FROM {$GLOBALS['table_prefix']}forum_list WHERE id={$forum_id}");
    $this_forum_info['moderators'] = preg_split("/\\r?\\n/", $this_forum_info['moderators']);
    return $this_forum_info;
}

?>
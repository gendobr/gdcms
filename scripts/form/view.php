<?php

/*
  Show and process XForm file

  arguments are

  form - file path relative to site root directory
  site_id - site identifier
  lang - interface language
 */

header('Access-Control-Allow-Origin: *');

global $main_template_name;
$main_template_name = '';

run('site/page/page_view_functions');
run('site/menu');
// ----------------- set interface language - begin ----------------------------
if (isset($input_vars['interface_lang']) && $input_vars['interface_lang']) {
    $input_vars['lang'] = $input_vars['interface_lang'];
}
if (!isset($input_vars['lang'])) {
    $input_vars['lang'] = default_language;
}
if (strlen($input_vars['lang']) == 0) {
    $input_vars['lang'] = default_language;
}
$input_vars['lang'] = get_language('lang');
// ----------------- set interface language - end ------------------------------

//-------------------------- load messages - begin -----------------------------
$txt = load_msg($input_vars['lang']);
//-------------------------- load messages - end -------------------------------

# ------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
if (!$this_site_info) {
    die($txt['Site_not_found']);
}
$this_site_info['title'] = get_langstring($this_site_info['title'], $input_vars['lang']);
$this_site_info['URL_to_view_news'] = url_prefix_news_list . "site_id={$this_site_info['id']}&lang={$input_vars['lang']}";
# ------------------- get site info - end --------------------------------------

// load form file
if (is_array($input_vars['form'])) {
    if (isset($input_vars['form'][$input_vars['lang']])) {
        $form_file_path=preg_replace("/\\/+$/","",\e::config('SITES_ROOT')). '/' . $this_site_info['dir'];
        $form_file_path=preg_replace("/\\/+$/","",$form_file_path). '/' . $input_vars['form'][$input_vars['lang']];
        //header("Debug: {$form_file_path}");
        $form_file = realpath($form_file_path);
        header("Debug: {$form_file_path} => {$form_file}");
    } else {
        $form_file = array_values($input_vars['form']);
        $form_file = $form_file[0];
        $form_file = realpath(\e::config('SITES_ROOT') . '/' . $this_site_info['dir'] . '/' . $form_file);
    }
}else{
    $form_file = realpath(\e::config('SITES_ROOT') . '/' . $this_site_info['dir'] . '/' . $input_vars['form']);
}

// prn($this_site_info);
if (strlen(dirname($form_file)) < strlen($this_site_info['site_root_dir'])){
    die('File not found');
}


$form_html = join('', file($form_file));
$captcha_placeholder_exists=(strpos($form_html , '{captcha}')!==false);


// remove HTML comments
$form_html = explode('<!--', $form_html);
$cnt = count($form_html);
for ($i = 1; $i < $cnt; $i++) {
    $tmp = explode('-->', $form_html[$i]);
    if (isset($tmp[1])) {
        $form_html[$i] = $tmp[1];
    } else {
        $form_html[$i] = ' ';
    }
}
$form_html = join('', $form_html);

//prn(checkStr($form_html));

function get_tag($tagname, $html) {
    /* $pattern="/<".preg_quote($tagname)."(?:[ \r\n\t]+\w+=(?:\"[^\"]*\"|'[^']*'|\w*))*\/?>/i"; */
    $pattern = "/<" . preg_quote($tagname, '/') . "(?:[ \r\n\t\w=]+|\"[^\"]*\"|'[^']*'|\w*)*\/?" . ">/i";
    //prn('$pattern='.$pattern);
    if (!preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
        return Array();
    }
    //prn($matches);
    return $matches[0];
}

function get_end_tag($tagname, $html) {
    /* $pattern="/<".preg_quote($tagname)."(?:[ \r\n\t]+\w+=(?:\"[^\"]*\"|'[^']*'|\w*))*\/?>/i"; */
    $pattern = "/<" . preg_quote($tagname, '/') . ">/i";
    //prn('$pattern='.$pattern);
    if (!preg_match_all($pattern, $html, $matches, PREG_OFFSET_CAPTURE)) {
        return Array();
    }
    //prn($matches);
    return $matches[0];
}

function get_attributes($html) {
    //prn(checkStr($html));
    /* $pattern="/<(?:[-_:\w]+)([ \r\n\t]+\w+=(?:\"[^\"]*\"|'[^']*'|\w*))*\/?>/i"; */
    $pattern = "/[ \r\n\t]+\w+(?:=\"[^\"]*\"|='[^']*'|=\w*)?/i";
    if (!preg_match_all($pattern, $html, $matches)) {
        return Array();
    }
    $tor = Array();
    foreach ($matches[0] as $mt) {
        $mt = explode('=', $mt);
        $nm = strtolower(trim($mt[0]));
        //if(isset($mt[1])) $vl=ereg_replace("^[\"']|[\"']$",'',$mt[1]);
        if (isset($mt[1])) {
            $vl = preg_replace("/^[\"']|[\"']$/", '', $mt[1]);
        } else {
            $vl = '';
        }
        $tor[$nm] = $vl;
    }
    return $tor;
}


# ------------------- process posted data - begin ------------------------------
$messages = Array();

// if some pre-defined values are posted
$form_data_posted=isset($input_vars['formdata']);

// if data is posted and data is correct
$form_can_be_accepted = false;

// if form is submitted
$form_is_submitted = $form_data_posted;// && isset($input_vars['formdata']['code']);

// ------------------- post data - begin ---------------------------------------
if ($form_data_posted) {

    $form_can_be_accepted = $form_is_submitted;
    $vyvid = $form_html;

    // prn('formdata=',$input_vars['formdata']);

    // prn($_FILES);
    
    // ------------- process <input> elements - begin --------------------------
    $inputs = get_tag('input', $vyvid);
    $cnt = count($inputs);
    $posted_files=Array();
    for ($i = $cnt - 1; $i >= 0; $i--) {
        $new_tag = ' ';

        $attributes = get_attributes($inputs[$i][0]);

        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text';
        }

        if (!isset($attributes['name'])) {
            $attributes['name'] = 'element' . $i;
        }

        switch (strtolower($attributes['type'])) {
            case 'submit':
                if($form_is_submitted && $captcha_placeholder_exists){
                    if (!isset($_SESSION['code'])){
                        $_SESSION['code'] = '';
                    }
                    if (!isset($input_vars['formdata']['code'])){
                        $input_vars['formdata']['code'] = '';
                    }
                    $input_vars['formdata']['code']=  strtolower($input_vars['formdata']['code']);
                    if (strlen($input_vars['formdata']['code'])>0 && $_SESSION['code'] != $input_vars['formdata']['code']) {
                        $form_can_be_accepted = false;
                        $messages[$attributes['name']] = "<div style='color:red;'>{$txt['Retype_the_code']}</div> ";
                    }
                }
                break;

            case 'checkbox':
                if (isset($input_vars['formdata'][$attributes['name']])) {
                    $new_tag = '<input type=checkbox checked>';
                } else {
                    $new_tag = '<input type=checkbox>';
                }
                break;

            case 'radio':
                if (!isset($attributes['value'])) {
                    $attributes['value'] = '';
                }
                if (isset($input_vars['formdata'][$attributes['name']]) && $input_vars['formdata'][$attributes['name']] == $attributes['value']) {
                    $new_tag.="<input type=radio checked disabled=true>";
                } else {
                    $new_tag.="<input type=radio disabled=true>";
                }
                # --------------------------- check data types - begin -----------------------
                $msg = '';
                if (isset($attributes['class'])) {
                    $opts = explode(' ', $attributes['class']);
                    if ($form_is_submitted && in_array('mandatory', $opts) && strlen($attributes['value']) == 0) {
                        $form_can_be_accepted = false;
                        $msg.="<div style='color:red;'>Fill-in the field</div> ";
                    }
                }
                if (strlen($msg) > 0) {
                    $messages[$attributes['name']] = $msg;
                }
                # --------------------------- check data types - end -------------------------
                break;

            case 'text':
                if (!isset($attributes['value'])) {
                    $attributes['value'] = '';
                }
                if (isset($input_vars['formdata'][$attributes['name']])) {
                    $attributes['value'] = $input_vars['formdata'][$attributes['name']];
                }
                # --------------------------- check data types - begin -----------------------
                $msg = '';
                if (isset($attributes['class'])) {
                    $opts = explode(' ', $attributes['class']);
                    if($form_is_submitted){
                        if (in_array('mandatory', $opts) && strlen($attributes['value']) == 0) {
                            $form_can_be_accepted = false;
                            $msg.="<div style='color:red;'>" . $txt['Fill_in_the_field'] . "</div> ";
                        }
                        if (in_array('email', $opts) && !is_valid_email($attributes['value'])) {
                            $form_can_be_accepted = false;
                            $msg.="<div style='color:red;'>" . $txt['Invalid_email_format'] . "</div> ";
                        }
                        if (in_array('url', $opts) && !is_valid_url($attributes['value'])) {
                            $form_can_be_accepted = false;
                            $msg.="<div style='color:red;'>" . $txt['Invalid_url_format'] . "</div> ";
                        }
                        if (in_array('number', $opts) && !is_numeric(str_replace(',', '.', $attributes['value']))) {
                            $form_can_be_accepted = false;
                            $msg.="<div style='color:red;'>" . $txt['Invalid_number'] . "</div> ";
                        }
                        if (in_array('reply-to', $opts) && is_valid_email($attributes['value'])) {
                            $reply_to = $attributes['value'];
                        }
                        if (in_array('from-name', $opts)) {
                            $from_name = $attributes['value'];
                        }                    }
                }
                if (strlen($msg) > 0) {
                    $messages[$attributes['name']] = $msg;
                }
                # --------------------------- check data types - end -------------------------

                $new_tag = trim(strip_tags($attributes['value']));
                break;
            // ----------------- process posted file - begin -------------------
            case 'file':
                
                
                if (isset($_FILES['formdata'])) {
                //if (isset($_FILES[$attributes['name']])) {
                    foreach($_FILES['formdata']['name'] as $key=>$val){
                        $posted_files[$key]=Array(
                            'name'=>$_FILES['formdata']['name'][$key],
                            'type'=>$_FILES['formdata']['type'][$key],
                            'tmp_name'=>$_FILES['formdata']['tmp_name'][$key],
                            'error'=>$_FILES['formdata']['error'][$key],
                            'size'=>$_FILES['formdata']['size'][$key]
                        );
                    }
                    $new_tag = $posted_files[$attributes['name']]['name'];
                } else {
                    $new_tag = '----------';
                }
                break;
            // ----------------- process posted file - end ---------------------
        }
        //prn("  {$inputs[$i][1]} {$attributes['type']} new_tag[{$attributes['name']}]=".$new_tag);
        $vyvid = substr_replace($vyvid, $new_tag, $inputs[$i][1], strlen($inputs[$i][0]));
        //prn(checkStr($vyvid));
    }
    // ------------- process <input> elements - end ----------------------------

    // ------------- textarea - begin -----------------------------
    $textarea_start = get_tag('textarea', $vyvid);
    //prn($textarea_start);
    $textarea_finish = get_tag('/textarea', $vyvid);
    //prn($textarea_finish);
    $cnt = count($textarea_start);
    for ($i = $cnt - 1; $i >= 0; $i--) {
        $new_tag = '';
        $attributes = get_attributes($textarea_start[$i][0]);
        if (!isset($attributes['name'])) {
            $attributes['name'] = 'textarea' . $i;
        }

        if (!isset($value)) {
            $value = '';
        }
        if (isset($input_vars['formdata'][$attributes['name']])) {
            $value = $input_vars['formdata'][$attributes['name']];
        }
        # --------------------------- check data types - begin -----------------------
        $msg = '';
        if (isset($attributes['class'])) {
            $opts = explode(' ', $attributes['class']);
            if ($form_is_submitted && in_array('mandatory', $opts) && strlen(trim($value)) == 0) {
                $form_can_be_accepted = false;
                $msg.="<div style='color:red;'>" . $txt['Fill_in_the_field'] . "</div> ";
            }
        }
        if (strlen($msg) > 0) {
            $messages[$attributes['name']] = $msg;
        }
        # --------------------------- check data types - end -------------------------

        unset($attributes['name']);
        $new_tag.=trim(strip_tags($value)) . ' ';
        $vyvid = substr_replace($vyvid, $new_tag, $textarea_start[$i][1], $textarea_finish[$i][1] - $textarea_start[$i][1] + strlen($textarea_finish[$i][0]));
    }
    // ------------- textarea - end -------------------------------

    // ------------- select - begin -------------------------------
    $select_start = get_tag('select', $vyvid);
    $select_finish = get_tag('/select', $vyvid);
    $option_start = get_tag('option', $vyvid);
    $option_finish = get_tag('/option', $vyvid);
    $selects = Array();
    $cnt = count($select_start);
    $cnt1 = count($option_start);
    for ($i = $cnt - 1; $i >= 0; $i--) {
        $new_tag = " ";

        $attributes = get_attributes($select_start[$i][0]);
        if (!isset($attributes['name']) || strlen($attributes['name']) == 0) {
            $attributes['name'] = 'select' . $i;
        }
        //$new_tag.=" name=\"formdata[{$attributes['name']}]\" ";

        if (!isset($value)) {
            $value = '';
        }
        if (isset($input_vars['formdata'][$attributes['name']])) {
            $value = $input_vars['formdata'][$attributes['name']];
        }



        $tmp_start = $select_start[$i][1];
        $tmp_finish = $select_finish[$i][1];
        //prn(" $tmp_start ... $tmp_finish ");
        $options = Array();
        for ($j = 0; $j < $cnt1; $j++) {
            if ($option_start[$j][1] > $tmp_start && $option_start[$j][1] < $tmp_finish) {
                //prn("{$option_start[$j][1]},{$option_finish[$j][1]}");
                $attr = get_attributes($option_start[$j][0]);
                $options[$attr['value']] = substr($vyvid
                        , $option_start[$j][1] + strlen($option_start[$j][0])
                        , $option_finish[$j][1] - ($option_start[$j][1] + strlen($option_start[$j][0])));
            }
        }
        # --------------------------- check data types - begin -----------------------
        $msg = '';
        if (isset($attributes['class'])) {
            $opts = explode(' ', $attributes['class']);
            if ($form_is_submitted && in_array('mandatory', $opts) && strlen(trim($value)) == 0) {
                $form_can_be_accepted = false;
                $msg.="<div style='color:red;'>" . $txt['Fill_in_the_field'] . "</div> ";
            }
        }
        if ($form_is_submitted && !isset($options[$value])) {
            $form_can_be_accepted = false;
            $msg.="<div style='color:red;'>" . $txt['Forbidden_value_of_field'] . "</div> ";
        }
        if (strlen($msg) > 0) {
            $messages[$attributes['name']] = $msg;
        }
        # --------------------------- check data types - end -------------------------

        $new_tag.=isset($options[$value])?trim(strip_tags($options[$value])):'';

        $vyvid = substr_replace($vyvid, $new_tag, $select_start[$i][1], $select_finish[$i][1] - $select_start[$i][1] + strlen($select_finish[$i][0]));
    }
    // ------------- select - end ---------------------------------
    // 
    // 
    // найти начало формы
    $form_tag = get_tag('form', $vyvid);
    // достать атрибуты
    $form_attributes = get_attributes($form_tag[0][0]);
    // составить замену
    $new_form_tag = " ";
    $vyvid = substr_replace($vyvid, $new_form_tag, $form_tag[0][1], strlen($form_tag[0][0]));

    $form_tag = get_tag('/form', $vyvid);
    // составить замену
    $new_form_tag = " ";
    $vyvid = substr_replace($vyvid, $new_form_tag, $form_tag[0][1], strlen($form_tag[0][0]));

    $vyvid = str_replace('{captcha}','',$vyvid);


    // --------------- send email if all is OK - begin --------------------------
    if ($form_can_be_accepted) {
        # prn($this_site_info);
        # ----------------------- get email address - begin -------------------
        $emails = Array();
        if (isset($form_attributes['action'])) {
            $tmp = preg_split("/ *, */",trim(preg_replace('/mailto:/', '', $form_attributes['action'])));
            foreach($tmp as $tm){
                if (is_valid_email($tm)) {
                    $emails[] = $tm;
                }
            }
        }

        if (count($emails) == 0) {
            # ------------- list of site managers - begin ----------------------
            $tmp = \e::db_getrows(
                    "select u.id, u.full_name, u.user_login, u.email, su.level
                      from {$GLOBALS['table_prefix']}user AS u, {$GLOBALS['table_prefix']}site_user AS su
                      where u.id = su.user_id AND su.site_id = {$this_site_info['id']}
                      order by level desc");
            $this_site_info['managers'] = Array();
            foreach ($tmp as $tm) {
                $tmp = trim($tm['email']);
                if (eregi('@127\.0\.0\.1$', $tmp)) {
                    continue;
                }
                if (is_valid_email($tmp)) {
                    $emails[] = $tmp;
                }
            }
            unset($tm, $tmp);
            # ------------- list of site managers - end ------------------------
        }
        # ----------------------- get email address - end ----------------------
        # ----------------------- send emails - begin --------------------------
        if (count($emails) > 0) {
            run('lib/mailing');
            run('lib/class.phpmailer');
            run('lib/class.smtp');

            $my_mail_options=Array();
            if(isset($reply_to)){
                $my_mail_options['ReplyTo']=$reply_to;
            }
            if(isset($from_name)){
                $my_mail_options['FromName']=$from_name;
            }

            //
            foreach ($emails as $mng) {
                if (IsHTML != '1') {
                    $vyvid = wordwrap(strip_tags(eregi_replace('<br/?>', "\n", $vyvid)), 80, "\n");
                }

                // ---------------- do mailing - begin -------------------------
                $mail = new phpmailer();
                $mail->Timeout=120;
                $mail->PluginDir=\e::config('SCRIPT_ROOT')."/lib/";

                if (mail_IsSMTP)  {
                  $mail->IsSMTP();
                  $mail->Host = mail_SMTPhost;
                }elseif(mail_IsSendMail) {
                  $mail->IsSendmail();
                }elseif(defined('mail_IsMail') && mail_IsMail){
                    $mail->IsMail();
                }
                $mail->CharSet  = site_charset;
                $mail->SMTPAuth = mail_SMTPAuth;
                $mail->Username = mail_SMTPAuth_Username;
                $mail->Password = mail_SMTPAuth_Password;

                $mail->From = mail_FromAddress;
                
                if(isset($my_mail_options['FromName'])){
                    $mail->FromName = $my_mail_options['FromName'];
                }else{
                    $mail->FromName = mail_FromName;
                }
                
                $mail->AddAddress($mng);
                        
                if(isset($my_mail_options['ReplyTo'])){
                    $mail->AddReplyTo($my_mail_options['ReplyTo']);
                }else{
                    $mail->AddReplyTo(mail_FromAddress);
                }
                //$mail->WordWrap = word_wrap; // set word wrap to 50 characters
                $mail->IsHTML(true);

                $mail->Subject = $this_site_info['title'] . ' : Submitted form ';
                $mail->Body    = $vyvid;
                //prn('sending email',$mail);

                foreach ($posted_files as $fl) {
                    if ($fl['error'] == UPLOAD_ERR_OK) {
                        //prn($fl);
                        //echo "attaching to {$fl['tmp_name']}, {$fl['name']}";
                        $mail->AddAttachment($fl['tmp_name'], $fl['name']);
                    }
                }
                // echo "mailing to $mng";
                $success=$mail->Send();
                if ($success) {
                    // echo "Message sent.";
                }else{
                    echo "Message could not be sent. <br>";
                    echo "Mailer Error: " . $mail->ErrorInfo;
                    //exit;
                }
                // ---------------- do mailing - end ---------------------------
                
                // my_mail($mng, $this_site_info['title'] . ' : Submitted form ', $vyvid, $my_mail_options);
            }
            $vyvid = "<div style='color:green;font-weight:bold;'>" . $txt['Email_is_sent'] . "</div>" . $vyvid;
            $_SESSION['code'] = '';
        }
        # ----------------------- send emails - end ----------------------------
    }
    // --------------- send email if all is OK - end ---------------------------
    //prn(checkStr($vyvid));
    //prn($vyvid);
}
// ------------------- post data - end -----------------------------------------





// ------------------- draw form - begin ----------------------
if (!$form_can_be_accepted) {
    $vyvid = $form_html;
    if (!isset($input_vars['formdata']['code'])) {
        $messages = Array();
    }
    //if(isset($input_vars['formdata'])) prn('formdata=',$input_vars['formdata']);

    // check if capcha placeholder exists
    
    $captcha_html='<span class="captcha">'
                 . '<span class="captcha_label">'
                 . $txt['Retype_the_code']
                 . '</span>'
                 . '<span class="captcha_img"><img id=code src="'.site_root_URL.'/index.php?action=form/code" align="absmiddle" style="margin:0px;border:1px dotted silver;"></span>'
                 . '<span class="captcha_input"><input type=text name=formdata[code] size=5></span>'
                 . '<a class="captcha_refresh_button" href="javascript:void(document.getElementById(\'code\').src=\''.site_root_URL.'/index.php?action=form/code&t=\'+Math.random())">'.$txt['Reload_code'].'</a>'
                 . '</span>';
    //prn(htmlspecialchars($captcha_html));
    
    $inputs = get_tag('input', $vyvid);
    //prn($inputs);

    $cnt = count($inputs);
    for ($i = $cnt - 1; $i >= 0; $i--) {
        $new_tag = '<input ';
        $attributes = get_attributes($inputs[$i][0]);
        // prn(htmlspecialchars($inputs[$i][0]),$attributes);
        
        if (!isset($attributes['type'])) {
            $attributes['type'] = 'text';
        }
        $new_tag.=" type=\"{$attributes['type']}\" ";

        if (!isset($attributes['name'])) {
            $attributes['name'] = 'element' . $i;
        }
        $new_tag.=" name=\"formdata[{$attributes['name']}]\" ";


        switch (strtolower($attributes['type'])) {
            case 'submit':
                if($captcha_placeholder_exists){
                    if (!isset($_SESSION['code'])) {
                        $_SESSION['code'] = '';
                    }
                    if(!$captcha_placeholder_exists){
                        $new_tag = $captcha_html. $new_tag;
                    }
                }
                break;

            case 'checkbox':
                if (!isset($attributes['value'])) {
                    $attributes['value'] = 'ON';
                }
                if (isset($input_vars['formdata'][$attributes['name']])) {
                    $new_tag.=" checked=\"true\" ";
                }
                unset($attributes['checked']);
                break;

            case 'radio':
                if (!isset($attributes['value'])) {
                    $attributes['value'] = '';
                }
                if (isset($input_vars['formdata'][$attributes['name']]) && $input_vars['formdata'][$attributes['name']] == $attributes['value']) {
                    $new_tag.=" checked=\"true\" ";
                }
                unset($attributes['checked']);
                break;

            case 'text':
                if (!isset($attributes['value'])) {
                    $attributes['value'] = '';
                }
                if (isset($input_vars['formdata'][$attributes['name']])) {
                    $attributes['value'] = $input_vars['formdata'][$attributes['name']];
                } elseif (isset($input_vars[$attributes['name']])) {
                    $attributes['value'] = $input_vars[$attributes['name']];
                }
                break;
        }
        unset($attributes['type']);

        foreach ($attributes as $nm => $vl) {
            if ($nm != 'name') {
                $new_tag.=" $nm=\"" . checkStr($vl) . "\" ";
            }
        }
        $new_tag.='>';
        if (isset($messages[$attributes['name']])) {
            $new_tag.=$messages[$attributes['name']];
            unset($messages[$attributes['name']]);
        }
        // prn(htmlspecialchars($inputs[$i][0]),  htmlspecialchars($new_tag));
        $vyvid = substr_replace($vyvid, $new_tag, $inputs[$i][1], strlen($inputs[$i][0]));

    }
    // prn(checkStr($vyvid));
    // ------------- textarea - begin -----------------------------
    $textarea_start = get_tag('textarea', $vyvid);
    //prn($textarea_start);
    $textarea_finish = get_end_tag('/textarea', $vyvid);
    //prn($textarea_finish);
    $cnt = count($textarea_start);
    for ($i = $cnt - 1; $i >= 0; $i--) {
        $new_tag = '<textarea ';
        $attributes = get_attributes($textarea_start[$i][0]);
        if (!isset($attributes['name'])) {
            $attributes['name'] = 'textarea' . $i;
        }
        $new_tag.=" name=\"formdata[{$attributes['name']}]\" ";

        $value = '';
        if (isset($input_vars['formdata'][$attributes['name']])) {
            $value = $input_vars['formdata'][$attributes['name']];
        } elseif (isset($input_vars[$attributes['name']])) {
            $value = $input_vars[$attributes['name']];
        }

        foreach ($attributes as $nm => $vl) {
            if ($nm != 'name') {
                $new_tag.=" $nm=\"" . checkStr($vl) . "\" ";
            }
        }
        $new_tag.='>';

        $new_tag.=checkStr($value) . '</textarea>';
        if (isset($messages[$attributes['name']])) {
            $new_tag.=$messages[$attributes['name']];
            unset($messages[$attributes['name']]);
        }

        $vyvid = substr_replace($vyvid, $new_tag, $textarea_start[$i][1], $textarea_finish[$i][1] - $textarea_start[$i][1] + strlen($textarea_finish[$i][0]));
    }
    // ------------- textarea - end -------------------------------
    // ------------- select - begin -------------------------------
    $select_start = get_tag('select', $vyvid);
    $select_finish = get_end_tag('/select', $vyvid);
    $option_start = get_tag('option', $vyvid);
    $option_finish = get_end_tag('/option', $vyvid);
    $selects = Array();
    $cnt = count($select_start);
    $cnt1 = count($option_start);
    for ($i = $cnt - 1; $i >= 0; $i--) {
        $new_tag = "<select ";

        $attributes = get_attributes($select_start[$i][0]);
        if (!isset($attributes['name']) || strlen($attributes['name']) == 0) {
            $attributes['name'] = 'select' . $i;
        }
        $new_tag.=" name=\"formdata[{$attributes['name']}]\" ";

        $value = '';
        if (isset($input_vars['formdata'][$attributes['name']])) {
            $value = $input_vars['formdata'][$attributes['name']];
        }

        foreach ($attributes as $nm => $vl) {
            if ($nm != 'name') {
                $new_tag.=" $nm=\"" . checkStr($vl) . "\" ";
            }
        }
        $new_tag.='>';

        $tmp_start = $select_start[$i][1];
        $tmp_finish = $select_finish[$i][1];
        //prn(" $tmp_start ... $tmp_finish ");
        $options = Array();
        for ($j = 0; $j < $cnt1; $j++) {
            if ($option_start[$j][1] > $tmp_start && $option_start[$j][1] < $tmp_finish) {
                //prn("{$option_start[$j][1]},{$option_finish[$j][1]}");
                $attr = get_attributes($option_start[$j][0]);
                $options[$attr['value']] = substr($vyvid
                        , $option_start[$j][1] + strlen($option_start[$j][0])
                        , $option_finish[$j][1] - ($option_start[$j][1] + strlen($option_start[$j][0])));
            }
        }
        //prn('$options=',$options);
        $new_tag.=draw_options($value, $options);
        $new_tag.="</select>";
        if (isset($messages[$attributes['name']])) {
            $new_tag.=$messages[$attributes['name']];
            unset($messages[$attributes['name']]);
        }
        $vyvid = substr_replace($vyvid, $new_tag, $select_start[$i][1], $select_finish[$i][1] - $select_start[$i][1] + strlen($select_finish[$i][0]));
    }
    // ------------- select - end ---------------------------------
    //prn(checkStr($vyvid));
    // найти начало формы
    $form_tag = get_tag('form', $vyvid);
    //prn($form_tag);
    // достать атрибуты
    $form_attributes = get_attributes($form_tag[0][0]);
    // составить замену
    $new_form_tag = "<form action=".site_URL." method=\"post\" enctype=\"multipart/form-data\">
       <input type=hidden name=action value='form/view'>
       <input type=hidden name=site_id value='$site_id'>
       <input type=hidden name=lang value='{$input_vars['lang']}'>

       ";
    if (is_array($input_vars['form'])) {
        foreach ($input_vars['form'] as $key => $val) {
            $new_form_tag.="<input type=hidden name=form[$key] value='" . checkStr($val) . "'>\n";
        }
    } else {
        $new_form_tag.="<input type=hidden name=form value='" . checkStr($input_vars['form']) . "'>\n";
    }
    $vyvid = substr_replace($vyvid, $new_form_tag, $form_tag[0][1], strlen($form_tag[0][0]));


    if (isset($input_vars['formdata']['code'])) {
        $vyvid = "<div style='color:red;font-weight:bold;'>" . $txt['ERROR'] . "</div>" . $vyvid;
    }

    
        
        
    if($captcha_placeholder_exists){
      $vyvid = str_replace('{captcha}',$captcha_html,$vyvid);
    }
    //prn(checkStr($vyvid));
}
// ------------------- draw form - end ------------------------
// ------------------- get list of languages - begin ----------
$lang_list = list_of_languages();
//prn($lang_list);
$cnt = count($lang_list);
for ($i = 0; $i < $cnt; $i++) {
    $lang_list[$i]['url'] = $lang_list[$i]['href'];
    $lang_list[$i]['lang'] = $lang_list[$i]['name'];
}
// ------------------- get list of languages - end ------------
//run('site/page/page_view_functions');
$menu_groups = get_menu_items($this_site_info['id'], 0, $input_vars['lang']);

// prn('$menu_groups',$menu_groups,"get_menu_items({$this_site_info['id']},0,{$input_vars['lang']})");
if (isset($input_vars['widget'])) {
    header('Content-Type:text/html; charset='.site_charset);
    echo $vyvid;
} else {
    //------------------------ draw using SMARTY template - begin --------------
    $file_content = process_template($this_site_info['template']
            , Array(
        'page' => Array(
            'title' => $this_site_info['title']
            , 'content' => $vyvid
            , 'abstract' => ''
            , 'site_id' => $site_id
            , 'lang' => $input_vars['lang']
        )
        , 'lang' => $lang_list
        , 'site' => $this_site_info
        , 'menu' => $menu_groups
        , 'site_root_url' => site_root_URL
        , 'text' => $txt
            ));
    //------------------------ draw using SMARTY template - end ----------------
    echo $file_content;
}

?>
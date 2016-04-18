<?php

/**
  Editing producer properties
 */
run('ec/producer/functions');

$ec_producer_id = isset($input_vars['ec_producer_id']) ? (int) $input_vars['ec_producer_id'] : 0;
$this_producer_info = get_producer_info($ec_producer_id);
//prn($this_producer_info);
//------------------- get site info - begin ------------------------------------
run('site/menu');
$this_site_info = false;
if (isset($this_producer_info['site_id'])) {
    $site_id = (int) $this_producer_info['site_id'];
    $this_site_info = get_site_info($site_id);
}
if (!$this_site_info) {
    $site_id = (int) $input_vars['site_id'];
    $this_site_info = get_site_info($site_id);
}
//prn($this_site_info);
//------------------- get site info - end --------------------------------------
//------------------- check permission - begin ---------------------------------
$this_site_info['admin_level'] = get_level($site_id);
if ($this_site_info['admin_level'] == 0 && !is_admin()) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------
# ---------------- delete image - begin ----------------------------------------
$site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
//prn($input_vars);
if (isset($input_vars["ec_producer_img_delete"])) {
    $path = $site_root_dir . '/' . $this_producer_info["ec_producer_img"];
    if (is_file($path)) {
        unlink($path);
    }
    $this_producer_info["ec_producer_img"] = '';
    $query = "UPDATE {$table_prefix}ec_producer SET ec_producer_img='" . \e::db_escape($this_producer_info["ec_producer_img"]) . "' WHERE ec_producer_id={$ec_producer_id} LIMIT 1";
    //prn($query);
    \e::db_execute($query);
    //prn('Deleting...');
    header("Location: index.php?action=ec/producer/edit&ec_producer_id={$ec_producer_id}&site_id={$site_id}");
    exit();
}
if (isset($input_vars["ec_producer_logo_delete"])) {
    $path = $site_root_dir . '/' . $this_producer_info["ec_producer_logo"];
    if (is_file($path)) {
        unlink($path);
    }
    $this_producer_info["ec_producer_logo"] = '';
    $query = "UPDATE {$table_prefix}ec_producer SET ec_producer_logo='" . \e::db_escape($this_producer_info["ec_producer_logo"]) . "' WHERE ec_producer_id={$ec_producer_id} LIMIT 1";
    //prn($query);
    \e::db_execute($query);
    //prn('Deleting...');
    header("Location: index.php?action=ec/producer/edit&ec_producer_id={$ec_producer_id}&site_id={$site_id}");
    exit();
}
# ---------------- delete image - end ------------------------------------------
//------------------- edit properties -- begin ---------------------------------
run('lib/class_db_record_editor');
run('lib/class_db_record_editor_extended');

class edbre extends extended_db_record_editor {

    function check_form_values() {
        return true;
    }

}

$rep = new edbre;
$rep->use_db($db);
$rep->debug = false;
$rep->set_table("{$table_prefix}ec_producer");
$rep->exclude = '^ec_producer';
$rep->add_field('ec_producer_id'
        , 'ec_producer_id'
        , 'integer:hidden=yes'
        , '#');

$rep->add_field('ec_producer_title'
        , 'ec_producer_title'
        , 'string:maxlength=255'
        , text('ec_producer_title'));


$rep->add_field('site_id'
        , 'site_id'
        , 'integer:hidden=yes&default=' . $site_id
        , '#');

$rep->add_field('ec_producer_abstract'
        , 'ec_producer_abstract'
        , 'string:textarea=yes'
        , text('ec_producer_abstract'));

$rep->add_field('ec_producer_description'
        , 'ec_producer_description'
        , 'string:textarea=yes'
        , text('ec_producer_description'));

$rep->set_primary_key('ec_producer_id', $ec_producer_id);

$success = $rep->process();

if ($success) {

    run('ec/item/functions');
    //prn($_FILES);
    # -------------------- upload image - begin ----------------------------------
    if (isset($_FILES["ec_producer_img"])) {
        $data = date('Ymdhis');
        $photos = $_FILES["ec_producer_img"];
        if ($photos['size'] > 0 && preg_match("/(jpg|gif|png|jpeg)$/i", $photos['name'], $regs)) {
            # get file extension
            $file_extension = ".{$regs[1]}";
            # create file name
            $orig_image_file_name = "{$site_id}-{$data}-" . \core\fileutils::encode_file_name($photos['name']);

            # create directory
            $relative_dir = date('Y') . '/' . date('m');
            $site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
            \core\fileutils::path_create($site_root_dir, "/gallery/$relative_dir/");
            
            # copy uploaded file
            $suc = move_uploaded_file($photos['tmp_name'], "$site_root_dir/gallery/$relative_dir/$orig_image_file_name");
            
            // resize image file
            $big_image_file_name = "{$site_id}-{$data}-{$data}-big-" . \core\fileutils::encode_file_name($photos['name']);
            $big_file_path="$site_root_dir/gallery/$relative_dir/$big_image_file_name";
            ec_img_resize("$site_root_dir/gallery/$relative_dir/$orig_image_file_name", $big_file_path, \e::config('gallery_big_image_width'), \e::config('gallery_big_image_height'), "resample");
            // echo ($big_file_path); exit();

            //    $small_image_file_name = "{$this_ec_item_info['site_id']}-{$data}-small-" . encode_file_name($photos['name']);
            //    $small_file_path="$site_root_dir/gallery/$relative_dir/$small_image_file_name";
            $ec_producer_img = "gallery/$relative_dir/$big_image_file_name";
            $query = "UPDATE {$table_prefix}ec_producer SET ec_producer_img='" . \e::db_escape($ec_producer_img) . "' WHERE ec_producer_id={$rep->id} LIMIT 1";
            \e::db_execute($query);
            //phpinfo();
        }
    }


    if (isset($_FILES["ec_producer_logo"])) {
        $data = date('Ymdhis');
        $photos = $_FILES["ec_producer_logo"];
        if ($photos['size'] > 0 && preg_match("/(jpg|gif|png|jpeg)$/i", $photos['name'], $regs)) {
            # get file extension
            $file_extension = ".{$regs[1]}";
            # create file name
            $orig_image_file_name = "{$site_id}-{$data}-" . \core\fileutils::encode_file_name($photos['name']);

            # create directory
            $relative_dir = date('Y') . '/' . date('m');
            $site_root_dir = \e::config('SITES_ROOT') . '/' . $this_site_info['dir'];
            \core\fileutils::path_create($site_root_dir, "/gallery/$relative_dir/");
            //prn($site_root_dir,"/gallery/$relative_dir/");
            # copy uploaded file
            //prn($photos['tmp_name'],is_file($photos['tmp_name']),filesize($photos['tmp_name']),"$site_root_dir/gallery/$relative_dir/$big_image_file_name");
            $suc = move_uploaded_file($photos['tmp_name'], "$site_root_dir/gallery/$relative_dir/$orig_image_file_name");
            //prn("<img src="."{$this_site_info['url']}gallery/$relative_dir/$big_image_file_name".">");
            //if($suc) prn('OK'); else prn('Error');
            
            $big_image_file_name = "{$site_id}-{$data}-big-logo" . \core\fileutils::encode_file_name($photos['name']);
            $big_file_path="$site_root_dir/gallery/$relative_dir/$big_image_file_name";
            ec_img_resize("$site_root_dir/gallery/$relative_dir/$orig_image_file_name", $big_file_path, \e::config('gallery_big_image_width'), \e::config('gallery_big_image_height'), "resample");

            // echo "$big_image_file_name";exit();
            
            $ec_producer_logo = "gallery/$relative_dir/$big_image_file_name";
            //prn($this_producer_info["ec_producer_logo"]);
            $query = "UPDATE {$table_prefix}ec_producer SET ec_producer_logo='" . \e::db_escape($ec_producer_logo) . "' WHERE ec_producer_id={$rep->id} LIMIT 1";
            //prn($query);
            \e::db_execute($query);
            //phpinfo();
        }
    }

    
    
    # -------------------- upload image - end ------------------------------------
    #ml('site/edit',$input_vars);

    $this_producer_info = get_producer_info($rep->id);
    $ec_producer_id = $rep->id;
    //prn('$ec_producer_id=',$ec_producer_id);
}
//------------------- edit properties -- end -----------------------------------
//prn($rep);
//----------------------------- draw -- begin ----------------------------------
$form = $rep->draw_form();
//prn($form);

$form['elements']['ec_producer_img'] = Array(
    'label' => text('ec_producer_img'),
    'type' => 'custom',
    'primary_key' => 0,
    //'form_element_html' => (  (strlen($this_producer_info['ec_producer_img'])==0)?"<input type=file name=ec_producer_img>":"<img src=\"{$this_site_info['url']}{$this_producer_info['ec_producer_img']}\" width=200pt><br><input type=submit name=ec_producer_img_delete value=\"{$text['Delete']}\"><br><br>")
    'form_element_html' => ( (strlen($this_producer_info['ec_producer_img']) == 0) ? "<input type=file name=ec_producer_img>" : "<img src=\"{$this_site_info['url']}{$this_producer_info['ec_producer_img']}\" width=200pt><br><a href=\"index.php?action=ec/producer/edit&ec_producer_id={$rep->id}&site_id={$site_id}&ec_producer_img_delete=1\">{$text['Delete']}</a><br><br>")
);

$form['elements']['ec_producer_logo'] = Array(
    'label' => text('ec_producer_logo'),
    'type' => 'custom',
    'primary_key' => 0,
    'form_element_html' => ( (!isset($this_producer_info['ec_producer_logo']) || strlen($this_producer_info['ec_producer_logo']) == 0) ? "<input type=file name=ec_producer_logo>" : "<img src=\"{$this_site_info['url']}{$this_producer_info['ec_producer_logo']}\" width=200pt><br><a href=\"index.php?action=ec/producer/edit&ec_producer_id={$rep->id}&site_id={$site_id}&ec_producer_logo_delete=1\">{$text['Delete']}</a><br><br>")
);

    
$form['hidden_elements'].="\n<input type=hidden name=ec_producer_id value={$ec_producer_id}>\n";
//prn($form);
$input_vars['page_title'] = $input_vars['page_header'] = text('EC_edit_producer');
$input_vars['page_content'] = $rep->draw($form) . "

            <div id=\"insert_links_butons1\">
                <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
                <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
                <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
                <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
            </div>
            <div id=\"insert_links_butons2\">
                <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=gallery/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
                <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
                <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
                <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
            </div>

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup.js\"></script>
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
           <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />

           <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
           <script type=\"text/javascript\">
              $(function(){
                  $('#insert_links_butons1').insertBefore('#db_record_editor_ec_producer_abstract');
                  $('#insert_links_butons2').insertBefore('#db_record_editor_ec_producer_description');
                  init_links();
                  $('#db_record_editor_ec_producer_abstract').markItUp(mySettings);
                  $('#db_record_editor_ec_producer_description').markItUp(mySettings);
              });
           </script>
            ";

//----------------------------- draw -- end ------------------------------------
//----------------------------- context menu - begin ---------------------------
if ($rep->id > 0) {
    $sti = $text['EC_producer'] . ' "' . $this_producer_info['ec_producer_title'] . '"';
    $input_vars['page_menu']['producer'] = Array('title' => "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>", 'items' => Array());
    $input_vars['page_menu']['producer']['items'] = menu_ec_producer($this_producer_info);
}
//----------------------------- context menu - end -----------------------------
//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
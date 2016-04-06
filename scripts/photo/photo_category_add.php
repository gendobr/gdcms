<?php

// -------------- get site info - begin ----------------------------------------
run('site/menu');
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
// -------------- get site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $input_vars['page_header'] = $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------

$list_of_languages = list_of_languages();


// get list ordering by path
run('photo/functions');



if(isset($input_vars['save'])){
// do save    
}

// prn($list_of_languages);
$js_lang = Array();
foreach ($list_of_languages as $l) {
    $js_lang[$l['name']] =$text[$l['name']];
}
$js_lang = json_encode($js_lang);


$parent_list =  array_map(
    function($row){
        return [
            $row['photo_category_path'],
            get_langstring($row['photo_category_title'])
        ];
    },
    \e::db_getrows("SELECT * FROM <<tp>>photo_category photo_category WHERE site_id=<<integer site_id>> ORDER BY photo_category_path ASC",['site_id'=>$site_id])
);



$html="
<script type=\"text/javascript\" src=\"scripts/lib/langstring.js\"></script>
<form action=index.php method=POST  name=editform  enctype=\"multipart/form-data\">
    <input type=\"hidden\" name=\"site_id\" value=\"{$site_id}\">
    <input type=\"hidden\" name=\"action\" value=\"photo/photo_category_add\">
    

   <div><!-- 
   --><span class=blk8>

    <div class=\"label\">".text('photo_category_title')."</div>
    <div class=\"big\"><input type=text name=\"photo_category_title\" id=\"photo_category_title\" value=\"".  htmlspecialchars(\e::request('photo_category_title',''))."\"></div>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langstring('photo_category_title');
    </script>
    </span><!-- 
   --><span class=blk4>
    <div class=label>" . text('Icon') . "</div>

    <input type=\"file\" name=\"photo_category_icon\">
    </span><!-- 
    --></div>
    
    <div class=label>" . text('is_part_of') . "</div>
  	<div class=big>
            <select name='{$form['elements']['is_part_of']->form_element_name}' id='{$form['elements']['is_part_of']->form_element_name}'>
                <option value=''></option>
                {$form['elements']['is_part_of']->form_element_options}
                </select>
        </div>

    <div class=\"label\">".text('photo_category_ordering')."</div>
    <div class=\"big\"><input type=text name=\"photo_category_ordering\" id=\"photo_category_ordering\" value=\"".  htmlspecialchars(\e::request('photo_category_ordering',''))."\"></div>

    <div class=\"label\">".text('photo_category_code')."</div>
    <div class=\"big\"><input type=text name=\"photo_category_code\" id=\"photo_category_code\" value=\"".  htmlspecialchars(\e::request('photo_category_code',''))."\"></div>

    <div class=label>".text('photo_category_visible')."</div>
    <div class=big>" . \core\form::draw_radio(\e::request('photo_category_visible',1),[1=>text('positive_answer'),0=>text('negative_answer')], 'photo_category_visible') . "</div>
       
    photo_category_path         varchar(1024)  utf8_bin   YES             (NULL)                   select,insert,update,references           
    photo_category_description  text           utf8_bin   YES             (NULL)                   select,insert,update,references           

    <input type=\"submit\" name=\"save\" value=\"".  htmlspecialchars(text('photo_category_add'))."\">
</form>
";






$input_vars['page_header']=$input_vars['page_title']=text('photo_category_add');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

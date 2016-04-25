<?php
run('site/menu');
//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------
//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------

$uid='photos-'.$site_id.'-'.time();
$code=  str_replace(
                [
                   '{lang}','{template}','{photo_category_id}'
                ],[
                    '<span id="langX">'.$_SESSION['lang'].'</span>','<span id="template"></span>','<span id="photo_category_id"></span>'
                ],
        htmlspecialchars("<script type=\"text/javascript\" src=\"".
        \e::config('APPLICATION_PUBLIC_URL')
        ."/scripts/lib/ajax_loadblock.js\"></script>\n"
        . "<div id=\"{$uid}\"> </div>\n"
        . "<script type=\"text/javascript\">"
        . "ajax_loadblock('$uid',\n"
        . "'".\e::config('APPLICATION_PUBLIC_URL')."/index.php?"
        . "action=photo/photo_category_block&"
        . "site_id={$site_id}&lang={lang}"
        . "&photo_category_id={photo_category_id}"
        . "&template={template}',null);\n"
        . "</script>\n"));


$lang_list = list_of_languages();
$lang_options=[];
foreach($lang_list as $it){
    $lang_options[$it['name']]=$it['name'];
}
// \e::info($lang_list);
$input_lang=  \core\form::draw_options(\e::config('default_language'), $lang_options);


$photo_category_list =  array_map(
    function($row){
        return [
            $row['photo_category_id'],
            str_repeat('&nbsp;|&nbsp;&nbsp;&nbsp;',  substr_count($row['photo_category_path'], '/')).get_langstring($row['photo_category_title'])
        ];
    },
    \e::db_getrows("SELECT * FROM <<tp>>photo_category photo_category WHERE site_id=<<integer site_id>> ORDER BY photo_category_path ASC",['site_id'=>$site_id])
);
$photo_category_options=\core\form::draw_options('', $photo_category_list);



$html = <<<EOD
<div>Lang: <select id=input_lang>{$input_lang}</select></div>
<div>Template: <input id=input_template></div>
<div>Category: <select id=input_category><option value=''></option>{$photo_category_options}</select></div>
<pre style="width:100%;height:8em; overflow-x:scroll;">
$code
</pre>
<script type="application/javascript">
$(document).ready(function(){
    \$("#input_template").keyup(function(){\$("#template").html(\$("#input_template").val());});
    \$("#input_lang").change(function(){\$("#langX").html(\$("#input_lang").val());});
    \$("#input_category").change(function(){\$("#photo_category_id").html(\$("#input_category").val());});    
});
</script>
EOD;

$input_vars['page_header']=$input_vars['page_title']=text('photo_widgets');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

<?php

/*
 * Editing fragment
 */

run('site/menu');
run('fragment/functions');

$debug=false;

# ------------------- check fragment id - begin --------------------------------
$fragment_id = checkInt((isset($input_vars['fragment_id']) ? $input_vars['fragment_id'] : 0));
$lang = get_language('lang');

$query = "SELECT * FROM <<tp>>fragment WHERE fragment_id={$fragment_id} AND fragment_lang='$lang'";
$this_fragment_info =\e::db_getonerow($query);
if ($debug) {
    prn(htmlspecialchars($query), $this_fragment_info);
}
# prn($this_fragment_info);
# ------------------- check fragment id - end ----------------------------------
# ------------------- get site info - begin ------------------------------------
$site_id = 0;
if (isset($input_vars['site_id'])) {
    $site_id = checkInt($input_vars['site_id']);
}
if (isset($this_fragment_info['site_id'])) {
    $site_id = checkInt($this_fragment_info['site_id']);
}
if ($site_id <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
$this_site_info = get_site_info($site_id);
// prn($this_site_info);
// prn(array_keys($this_site_info['extra_setting']['lang']));
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] =
            $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
# ------------------- get site info - end --------------------------------------
# ------------------- get permission - begin -----------------------------------
$user_cense_level = get_level($site_id);
if ($user_cense_level <= 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
#if($debug) prn('$user_cense_level='.$user_cense_level);
# ------------------- get permission - end -------------------------------------


run('lib/class_db_record_editor');
run('lib/class_db_record_editor_extended');
$rep = new extended_db_record_editor;

$rep->debug = false;
$rep->set_table("<<tp>>fragment");

$rep->add_field('fragment_id'
        , 'fragment_id'
        , 'integer:hidden=yes&default=' . $fragment_id
        , '#');



// fragment_lang
// enum
// $langs=\e::db_getrows("SELECT id, name FROM <<tp>>languages WHERE is_visible=1 ORDER BY name;");
// prn($langs);
$langs=[];
foreach($this_site_info['extra_setting']['lang'] as $k=>$v){
   $langs[]=['id'=>$k, 'name'=>$k];
}
for($i=0,$cnt=count($langs);$i<$cnt; ++$i){
    $langs[$i]=$langs[$i]['id'].'='.rawurlencode($langs[$i]['name']);
}
$langs=join('&',$langs);
$rep->add_field( 'fragment_lang'
        ,'fragment_lang'
        ,'enum:'.$langs
        ,text('fragment_lang'));


$rep->add_field('site_id'
        , 'site_id'
        , 'integer:hidden=yes&default=' . $site_id
        , '#');

$rep->add_field( 'fragment_label'
        ,'fragment_label'
        ,'string:html_denied=yes'
        ,text('fragment_label'));

$rep->add_field( 'fragment_place'
        ,'fragment_place'
        ,'string:html_denied=yes'
        ,text('fragment_place'));

$rep->add_field( 'fragment_is_visible'
        ,'fragment_is_visible'
        ,"enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        ,text('fragment_is_visible'));


$rep->add_field('fragment_html'
        , 'fragment_html'
        , 'string:textarea=yes'
        , text('fragment_html'));

// prn($rep);




$rep->set_primary_key('fragment_id',$fragment_id);
$success=$rep->process();
//prn($rep->field['fragment_lang']['value']);
if($success){
    // prn($rep->id);
    //$tor['value']
    header("Location:index.php?action=fragment/edit&site_id=$site_id&fragment_id={$rep->id}&lang={$rep->field['fragment_lang']['value']}");
    exit();
}
//------------------- draw - begin ---------------------------------------------
$form=$rep->draw_form();
$form['elements']['site_id2']=Array(
                    'field' => 'site_id',
                    'alias' => 'site_id',
                    'type' => 'integer',
                    'label' => '#',
                    'form_element_name' => 'site_id',
                    'form_element_value' => $site_id,
                    'value' => $site_id,
                    'options' => Array('hidden' => 'yes','default' => '69')
                );
//prn($form);
//------------------- draw - end -----------------------------------------------



$form['elements']['fragment_html']['before']="
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=photo/json&lang={{$rep->field['fragment_lang']['value']}}&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Gallery')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Category')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">".text('Pages')."</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
";
$input_vars['page_content']="
  <div><a href=\"index.php?action=fragment/list&site_id=$site_id\">&lt;&lt;&lt; ".text('fragment_return_to_list')." </a></div>

  <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
  <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />

  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/markitup.js\"></script>

  <script type=\"text/javascript\" charset=\"".site_charset."\" src=\"./scripts/lib/choose_links.js\"></script>
  <script type=\"text/javascript\">
      $(function(){
          init_links();
          $('textarea#db_record_editor_fragment_html').markItUp(mySettings);
      });
  </script>

  <p style='color:gray;'>Place into template_index.html file: <br><b>{fragment place=\"{$form['elements']['fragment_place']['value']}\" lang=\$text.language_name site_id=\$site.id}</b></p>

".$rep->draw($form);




if($this_fragment_info['fragment_id'] > 0) {
    $input_vars['page_title'] =
            $input_vars['page_header'] = text('fragment_edit');
}else{
    $input_vars['page_title'] =
            $input_vars['page_header'] = text('fragment_create');
}


//----------------------------- context menu - begin ---------------------------
if ($this_fragment_info['fragment_id'] > 0) {
    $input_vars['page_menu']['fragment'] = Array('title' => $text['fragment_menu'], 'items' => Array());
    $input_vars['page_menu']['fragment']['items'] = menu_fragment($this_fragment_info);
}

$input_vars['page_menu']['site'] = Array('title' => $text['Site_menu'], 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- context menu - end -----------------------------
?>
<?php

/*
 * http://127.0.0.1/cms/index.php?action=rss_aggregator/source_edit&site_id=1
 */

run('site/menu');
run('rss_aggregator/functions');

//------------------- get site info - begin ------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);


// prn('$this_site_info=',$this_site_info);
if (checkInt($this_site_info['id']) <= 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Site_not_found');
    return 0;
}
//------------------- get site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] =
    $input_vars['page_header'] =
    $input_vars['page_content'] = text('Access_denied');
    return 0;
}
//------------------- check permission - end -----------------------------------

//------------------- $this_rsssource_info - begin -----------------------------
$rsssource_id = isset($input_vars['rsssource_id'])?checkInt($input_vars['rsssource_id']):0;
$this_rsssource_info = get_rsssource_info($rsssource_id);
// prn('$this_rsssource_info=',$this_rsssource_info);
//------------------- $this_rsssource_info - end -------------------------------

//------------------- edit properties -- begin ---------------------------------
run('lib/class_db_record_editor');
run('lib/class_db_record_editor_extended');

$rep = new extended_db_record_editor;
$rep->use_db($db);
$rep->debug = false;
$rep->set_table("{$table_prefix}rsssource");


//rsssource_id               bigint(20) 
$rep->add_field('rsssource_id'
        , 'rsssource_id'
        , 'integer:hidden=yes&default=' . checkInt($this_rsssource_info['rsssource_id'])
        , '#');

//site_id                    bigint(20)
$rep->add_field('site_id'
        , 'site_id'
        , 'integer:hidden=yes&default=' . checkInt($this_site_info['id'])
        , '#');

//rsssource_title            varchar(64)
$rep->add_field('rsssource_title'
        , 'rsssource_title'
        , 'string:maxlength=64&required=yes'
        , text('rsssource_title'));


//rsssource_url              varchar(4096)
$rep->add_field('rsssource_url'
        , 'rsssource_url'
        , 'string:maxlength=4096&required=yes'
        , text('rsssource_url'));

//rsssource_lang             varchar(3)
$languages=db_getrows("SELECT id, name FROM {$table_prefix}languages WHERE is_visible=1 ORDER BY name ASC;");
$tmp=Array();
foreach($languages as $lan){
    $tmp[]="{$lan['id']}=".  rawurlencode($lan['name']);
}
$rep->add_field('rsssource_lang'
        , 'rsssource_lang'
        , 'enum:' . join('&',$tmp)
        , text('rsssource_lang'));


//rsssource_last_updated     datetime

//rsssource_is_visible       tinyint(1)
$rep->add_field('rsssource_is_visible'
        , 'rsssource_is_visible'
        , 'enum:1=' . rawurlencode(text('positive_answer')) . '&0=' . rawurlencode(text('negative_answer'))
        , text('rsssource_is_visible'));

//rsssource_is_premoderated  tinyint(1)
$rep->add_field('rsssource_is_premoderated'
        , 'rsssource_is_premoderated'
        , 'enum:1=' . rawurlencode(text('positive_answer')) . '&0=' . rawurlencode(text('negative_answer'))
        , text('rsssource_is_premoderated'));

//rsssource_tag              varchar(128)
$rep->add_field('rsssource_tag'
        , 'rsssource_tag'
        , 'string:maxlength=128&required=yes'
        , text('rsssource_tag'));

//prn($rep);

$rep->set_primary_key('rsssource_id', $rsssource_id);
$rep->process();
//------------------- edit properties -- end -----------------------------------



//----------------------------- draw -- begin ----------------------------------
$form = $rep->draw_form();
// prn($form);
$form['hidden_elements'] = $rep->hidden_fields('^rsssource_id$') .
        "<input type=hidden name=rsssource_id value=\"{$rep->id}\">\n";

//prn($form);
$input_vars['page_title'] =
$input_vars['page_header'] = text('Edit_rsssource_properties');
$input_vars['page_content'] = $rep->draw($form);

//----------------------------- draw -- end ------------------------------------

//----------------------------- site context menu - begin ----------------------
if ($rep->id > 0) {
    $this_rsssource_info = get_rsssource_info($rsssource_id);

    $input_vars['page_menu']['rsssource'] = Array('title' => text('RSSSource'), 'items' => Array());
    $input_vars['page_menu']['rsssource']['items'] = menu_rsssource($this_rsssource_info);
}
//----------------------------- site context menu - end ------------------------

//--------------------------- context menu -- begin ----------------------------
$sti = text('Site') . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
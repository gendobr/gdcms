<?php

$debug = false;
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


run('photo/functions');

// ------------------ delete photo - begin -------------------------------------
$delete_photo_id=\e::request('delete_photo_id',false);
if($delete_photo_id && is_array($delete_photo_id)){
    photo_delete($this_site_info, $delete_photo_id);
}
// ------------------ delete photo - end ---------------------------------------



run("lib/class_report");
run("lib/class_report_extended");
$re = new report_generator;
// $re->db = $db;
$re->distinct = false;
$re->exclude='/^delete_photo/';
$re->from = "<<tp>>photo AS photo";

$re->add_where(" photo.site_id={$site_id} ");


//
$re->add_field($field = 'photo.photo_id'
        , $alias = 'photo_id'
        , $type = 'id:hidden=no'
        , $label = '#'
        , $_group_operation = false);

$re->add_field($field = 'photo.photo_imgfile'
        , $alias = 'photo_imgfile'
        , $type = 'string'
        , $label = text('photo_imgfile')
        , $_group_operation = false);

$re->add_field($field = 'photo.photo_title'
        , $alias = 'photo_title'
        , $type = 'string'
        , $label = text('photo_title')
        , $_group_operation = false);


$LL = join('&', array_map(
    function($row){
        return 
            $row['photo_category_id']
            ."=".
            rawurlencode(str_repeat('&nbsp;|&nbsp;&nbsp;&nbsp;',  substr_count($row['photo_category_path'], '/')).get_langstring($row['photo_category_title']))
        ;
    },
    \e::db_getrows("SELECT photo_category_id,photo_category_title,photo_category_path FROM <<tp>>photo_category WHERE site_id={$site_id} ORDER BY photo_category_path ASC")
    ));
$re->add_field($field = 'photo.photo_category_id'
        , $alias = 'photo_category_id'
        , $type = 'enum:' . $LL
        , $label = text('photo_category_id')
        , $_group_operation = false);

$re->add_field($field = "photo.photo_visible"
        , $alias = 'photo_visible'
        , $type = "enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
        , $label = text('photo_visible')
        , $_group_operation = false);

$re->add_field($field = 'photo.photo_year'
        , $alias = 'photo_year'
        , $type = 'integer'
        , $label = text('photo_year')
        , $_group_operation = false);

$re->add_field($field = "''"
        , $alias = 'html1'
        , $type = 'string'
        , $label = text('photo_html1')
        , $_group_operation = false);

//$re->add_field($field = "''"
//        , $alias = 'html2'
//        , $type = 'string'
//        , $label = text('photo_html2')
//        , $_group_operation = false);


unset($field, $alias, $type, $label, $_group_operation);


// prn($re->create_query());
$response = $re->show();
// prn($response);

$cnt = count($response['rows']);


for ($i = 0; $i < $cnt; $i++) {
    //--------------------------- context menu -- begin ------------------------
    $response['rows'][$i]['context_menu'] = photo_menu($response['rows'][$i]);
    //--------------------------- context menu -- end --------------------------
    

    $photo_imgfile=  json_decode($response['rows'][$i]['photo_imgfile'], true);
    if($photo_imgfile){
        $response['rows'][$i]['photo_imgfile']="<a href=\"{$this_site_info['site_root_url']}/{$photo_imgfile['full']}\" target=_blank><img src=\"{$this_site_info['site_root_url']}/{$photo_imgfile['small']}\" style=\"max-width:150px;max-height:150px;\"></a>";
        $response['rows'][$i]['html1']=  htmlspecialchars("<a href=\"{$this_site_info['site_root_url']}/{$photo_imgfile['full']}\" target=_blank><img src=\"{$this_site_info['site_root_url']}/{$photo_imgfile['small']}\"></a>");
    }else{
        $response['rows'][$i]['photo_imgfile']='';
    }
    
    $response['rows'][$i]['photo_title']=  get_langstring($response['rows'][$i]['photo_title']);
    $response['rows'][$i]['photo_id'] = "<label style='white-space:nowrap;'><input type=checkbox name=\"delete_photo_id[]\" value=\"{$response['rows'][$i]['photo_id']}\">&nbsp;".$response['rows'][$i]['photo_id'].'</label>' ;
    
    // $response['rows'][$i]['tags'] = mb_wordwrap($response['rows'][$i]['tags'], 10, "&shy;",true);
    
    // $response['rows'][$i]['n_translations'] = $news_translations[$response['rows'][$i]['id']];
    
    //$response['rows'][$i]['category_id'] = mb_wordwrap($response['rows'][$i]['category_id'], 10, "&shy;",true);
    // $response['rows'][$i]['tags'] = mb_wordwrap($response['rows'][$i]['tags'], 10, "&shy;",true);
}





$html=$re->draw_header($response);
$html.=" 
<a href=\"index.php?action=photo/upload&site_id={$site_id}\">".text('photo_upload')."</a>
<form action=\"{$response['action']}\" name=\"{$response['form_name']}\" method=\"post\">
       {$response['hidden_fields']}
           
<style type=\"text/css\">
   .filter_element_xs{
        display:inline-block;
        width:12.4%;
        vertical-align:top;
    }
   .filter_element_s{
        display:inline-block;
        width:24.9%;
        vertical-align:top;
    }
   .filter_element_m{
        display:inline-block;
        width:49.4%;
        vertical-align:top;
    }
   .filter_element_l{
        display:inline-block;
        width:99.9%;
        vertical-align:top;
    }
</style>


 ";

 
$fld=$re->field['photo_title'];
$value = isset($response['fields'][$fld['alias']]['filter']['form_element_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_value'] : '';
$html.="<span class=\"filter_element_s\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <input type=text name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\"
                                     value=\"{$value}\"
                                     style=\"width:99%;\"
                                     size=3>
</span>";

      
$fld=$re->field['photo_category_id'];
$html.="<span class=\"filter_element_s\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\" style=\"width:99%;\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
</span>";      
     
$fld=$re->field['photo_visible'];
$html.="<span class=\"filter_element_xs\">
                    <div>{$response['fields'][$fld['alias']]['label']}</div>
                    <select name =\"{$response['fields'][$fld['alias']]['filter']['form_element_name']}\" style=\"width:99%;\">
                    <option value=''> </option>
                    {$response['fields'][$fld['alias']]['filter']['form_element_options']}
                    </select>
</span>";
               

$fld=$re->field['photo_year'];
$value_min = isset($response['fields'][$fld['alias']]['filter']['form_element_min_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_min_value'] : '';
$value_max = isset($response['fields'][$fld['alias']]['filter']['form_element_max_value']) ? $response['fields'][$fld['alias']]['filter']['form_element_max_value'] : '';
$html.="<span  class=\"filter_element_xs\">
        <div>{$response['fields'][$fld['alias']]['label']}</div>
        <nobr><input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_min_name']}\"
                     value=\"{$value_min}\"
                     size=3>
        ...
        <input type=text name=\"{$response['fields'][$fld['alias']]['filter']['form_element_max_name']}\"
               value=\"{$value_max}\"
               size=3></nobr>
</span>";
                    
$html.="                                  
<div style=\"float:right;\"><div>&nbsp;</div><input type=submit name=submit value=\"".text('Search')."\"></div>
    <hr>
";

$html.="<table border=1>";

        // ------------------------- header -- begin ------------------------------
$html.="<tr><th align=center valign=top></th>\n";
foreach ($re->field as $fld) {
    if (!isset($fld['options']['hidden'])) {
        $fld['options']['hidden'] = '';
    }
    if ($fld['options']['hidden'] == 'yes') {
        continue;
    }

    $html.="
              <th align=center valign=bottom>
               <b>{$response['fields'][$fld['alias']]['label']}</b><br>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_asc']}\">V</a>
               <a href=\"{$response['fields'][$fld['alias']]['url_order_desc']}\">&Lambda;</a>
               </th>
               \n";
}
$html.="</tr>\n";

$html.=$re->draw_rows($response);
$html.=$re->draw_paging($response);
$html.="</table>\n";
$html.="<input type=submit value=\"".text('photo_delete')."\">\n";
$html.="</form>\n";







$input_vars['page_header']=$input_vars['page_title']=text('photo_list');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

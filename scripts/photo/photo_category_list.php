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

// get list ordering by path
run('photo/functions');

$list=\e::db_getrows(
        "SELECT photo_category.*, count(photo.photo_id) as nPhotos
         FROM <<tp>>photo_category photo_category 
              LEFT JOIN <<tp>>photo photo ON (photo_category.photo_category_id=photo.photo_category_id)
         WHERE photo_category.site_id=<<integer site_id>> 
         GROUP BY photo_category.photo_category_id
         ORDER BY photo_category.photo_category_path ASC",['site_id'=>$site_id]);

// draw list
$html="<a href='".\e::url(['action'=>'photo/photo_category_add', 'site_id'=>$site_id])."'>".text('photo_category_add')."</a>";

foreach($list as $row){
    $deep=substr_count ( $row['photo_category_path'] , "/");
    $html.="<div style=\"padding-left:".(20*$deep)."pt; margin-top:10px;\">
               <a href=\"javascript:void({$row['photo_category_id']})\" class=context_menu_link onclick=\"change_state('cm{$row['photo_category_id']}'); return false;\"><img src=img/context_menu.gif border=0 width-25 height=15></a>
               ".  get_langstring($row['photo_category_title'])." ({$row['nPhotos']} photo(s) )
               <div id=\"cm{$row['photo_category_id']}\" class=menu_block style='display:none;'>
               ";
    $menu = photo_category_menu($row);
    foreach($menu as $cm){
        if($cm['url']){
            $html.="<div><nobr><a href=\"{$cm['url']}\" {$cm['attributes']}>{$cm['html']}</a></nobr></div>";
        }else{
            $html.="<div><nobr>{$cm['html']}</nobr></div>";
        }
    }
    $html.=    "</div>
            </div>";
}




$input_vars['page_header']=$input_vars['page_title']=text('photo_category_list');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

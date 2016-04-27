<?php

#prn($_REQUEST);
run('site/menu');

# ------------------- site info - begin ----------------------------------------
if (isset($input_vars['site_id'])) {
    $site = $site_id = checkInt($input_vars['site_id']);
    $this_site_info = get_site_info($site);

    if (checkInt($this_site_info['id']) <= 0) {
        $input_vars['page_title'] = $text['Site_not_found'];
        $input_vars['page_header'] = $text['Site_not_found'];
        $input_vars['page_content'] = $text['Site_not_found'];
        return 0;
    }
} else {
    $input_vars['page_title'] = $text['Site_not_found'];
    $input_vars['page_header'] = $text['Site_not_found'];
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
# ------------------- site info - end ------------------------------------------
# ------------------- check permission - begin ---------------------------------
if (get_level($site_id) == 0) {
    $input_vars['page_title'] = $text['Access_denied'];
    $input_vars['page_header'] = $text['Access_denied'];
    $input_vars['page_content'] = $text['Access_denied'];
    return 0;
}
# ------------------- check permission - end -----------------------------------

run('gallery/category_model');

// ------------------------ ajax request receiver - begin ----------------------
if(isset($input_vars['neworder'])){
    // save some data
    $neworder=explode(',',$input_vars['neworder']);
    for($i=0,$cnt=count($neworder);$i<$cnt;$i++){
        $id=(int)$neworder[$i];
        \e::db_execute("UPDATE {$GLOBALS['table_prefix']}photogalery_rozdil SET weight=$i WHERE id=$id AND site_id={$this_site_info['id']}");
    }
    $GLOBALS['main_template_name'] = '';
    return;
}
// ------------------------ ajax request receiver - end ------------------------

$vyvid="
<script>
var timeout;
function post_new_order(ids){
     if(timeout){
        clearTimeout(timeout);
     }
     setTimeout(function(){
        $.ajax({
            type: \"POST\",
            url: \"index.php\",
            data: \"action=gallery/admin/category_ordering&site_id={$this_site_info['id']}&neworder=\"+ids,
            dataType: \"text\"
        });
     }
     ,1000);
}

function orderupdated(){
    // collect new category order
    var ids=\"\";
    $(\".cat\").each(function(index,element){
       ids+=$(element).attr(\"data-id\")+\",\";
    });
    post_new_order(ids);
}

$(function() {
   $( \"#sortable\" ).sortable({
     stop: orderupdated
   });
   $( \"#sortable\" ).disableSelection();
});
</script>

".text('gallery_category_ordering_manual');

// ------------------ get list of categories - begin ---------------------------
gallery_synchronize_categories($this_site_info['id']);


$photogalery_rozdil_list = \e::db_getrows(
            "SELECT *
             FROM {$GLOBALS['table_prefix']}photogalery_rozdil
             WHERE site_id = {$this_site_info['id']}
             ORDER BY weight,rozdil");
// prn($photogalery_rozdil_list);
$vyvid.="<ul id='sortable'>";
foreach($photogalery_rozdil_list as $photogalery_rozdil){
    $vyvid.="<li class='cat' data-id=\"".  htmlspecialchars($photogalery_rozdil['id'])."\">{$photogalery_rozdil['rozdil']}</li>";
}
$vyvid.="</ul>";
// ------------------ get list of categories - end -----------------------------




$input_vars['page_title'] =
        $input_vars['page_header'] = $this_site_info['title'] . ' - ' . text('gallery_category_ordering');
$input_vars['page_content'] = $vyvid;

//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
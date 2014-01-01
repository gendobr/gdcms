<?php

#prn($_REQUEST);
run('site/menu');
run('lib/file_functions');
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



$this_page_url = 'index.php?' . preg_query_string('/^op_/');


// delete category
if (isset($input_vars['op_delete'])) {
    $op_delete_id = (int) $input_vars['op_delete'];
    $photogalery_rozdil_info = db_getonerow(
            "SELECT pr.*, count(p.id) as n_images
             FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr
                  LEFT JOIN {$GLOBALS['table_prefix']}photogalery p
                  ON (pr.rozdil=p.rozdil OR LOCATE(concat(pr.rozdil,'/'),p.rozdil))
             WHERE pr.site_id = {$this_site_info['id']}
                 AND pr.id={$op_delete_id}
             GROUP BY pr.id
             ORDER BY pr.rozdil ASC");
    if ($photogalery_rozdil_info && $photogalery_rozdil_info['n_images'] == 0) {
        // deleting empty category ...
        $delete_list = db_getrows(
                "SELECT pr.*
                 FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr
                 WHERE pr.site_id = {$this_site_info['id']}
                  AND (pr.rozdil='" . DbStr($photogalery_rozdil_info['rozdil']) . "' OR LOCATE('" . DbStr($photogalery_rozdil_info['rozdil']) . "/',pr.rozdil))
                ");
        // prn($delete_list);
        $cnt = count($delete_list);
        if ($cnt>0) {
            for ($i = 0; $i < $cnt; $i++) {
                $delete_list[$i] = (int) $delete_list[$i]['id'];
            }
            db_execute("DELETE FROM {$GLOBALS['table_prefix']}photogalery_rozdil WHERE site_id = {$this_site_info['id']} AND id IN(".join(',',$delete_list).") ");
        }
    }
    header("Location: $this_page_url");
    exit();
}

// create category
if (isset($input_vars['op_create'])) {
    $query = "INSERT INTO {$GLOBALS['table_prefix']}photogalery_rozdil (rozdil,site_id)
          VALUES ('new category',{$this_site_info['id']})";
    db_execute($query);
    header("Location: $this_page_url");
    exit();
}

run('gallery/category_model');

gallery_synchronize_categories($this_site_info['id']);

$vyvid = "
<p><a href=\"{$this_page_url}&op_create=1\">".text('Add_new_category')."</a> ".text('You_can_delete_only_empty_categories')."</p><br/>
";

//$photogalery_rozdil_list = db_getrows(
//        "SELECT *
//         FROM {$GLOBALS['table_prefix']}photogalery_rozdil
//         WHERE site_id = {$this_site_info['id']}
//         ORDER BY rozdil ASC");
$start=isset($input_vars['start'])?( (int)$input_vars['start'] ):0;
if($start<=0){
    $start=0;
}
$photogalery_rozdil_list = db_getrows(
        "SELECT SQL_CALC_FOUND_ROWS pr.*, count(p.id) as n_images
         FROM {$GLOBALS['table_prefix']}photogalery_rozdil pr
              LEFT JOIN {$GLOBALS['table_prefix']}photogalery p
              ON (pr.rozdil=p.rozdil OR LOCATE(concat(pr.rozdil,'/'),p.rozdil))
         WHERE pr.site_id = {$this_site_info['id']}
         GROUP BY pr.id
         ORDER BY pr.rozdil ASC
         LIMIT $start,".rows_per_page);
//prn($photogalery_rozdil_list);

// get total number of categories
$query = "SELECT FOUND_ROWS() AS n_records;";
$n_records = db_getonerow($query);
$n_records=$n_records['n_records'];

// paging url template
$url_template='index.php?'.preg_query_string('/^start$/').'&start={start}';

$paging_links=get_paging_links($start, $n_records, rows_per_page, $url_template);
// prn($paging_links);

// draw paging links
$links='';
if(count($paging_links)>1){
    foreach($paging_links as $link){
        if($link['URL']){
           $links.="<a href='{$link['URL']}'>{$link['innerHTML']}</a> ";
        }else{
           $links.="{$link['innerHTML']} ";
        }
    }
    $vyvid.="<p>$links</p>";
}






$url_prefix = preg_replace("/\\/+$/", '', $this_site_info['url']) . '/gallery';


foreach ($photogalery_rozdil_list as $photogalery_rozdil) {
    // prn($rozdil);
    if ($photogalery_rozdil['photos_m']) {
        $img = "background-image:url({$url_prefix}/{$photogalery_rozdil['photos_m']})";
        // prn($img);
    } else {
        $img = '';
    }
    $img_selector = "
        ".text('Main_image')."<br/>
        <span id='imgSelector_" . $photogalery_rozdil['id'] . "'
                           data=\"" . rawurlencode($photogalery_rozdil['rozdil']) . "\"
                           class='imgSelector'
                           style='$img'><span class=imgSelectorText data=\"" . rawurlencode($photogalery_rozdil['rozdil']) . "\">".
                           text('Change_image')."</span></span>";
    $vyvid.="
        <span style='width:90%;display:inline-block;'>{$img_selector}
            ".text('Title').":<br>
        <span class=edittitle id=rozdiltitle_{$photogalery_rozdil['id']}>{$photogalery_rozdil['rozdil']}</span>
        ( {$photogalery_rozdil['n_images']} ".text('items')." )
            " .
            (($photogalery_rozdil['n_images'] == 0) ? "<a href='{$this_page_url}&op_delete={$photogalery_rozdil['id']}'>delete</a>" : '')
            . "
         <br/>
         ".text('Description').":<br>
         <span class=edit id=rozdil_{$photogalery_rozdil['id']}>{$photogalery_rozdil['description']}</span>
        </span>
        ";
}
// draw paging links
if(count($paging_links)>1){
    $vyvid.="<p>$links</p>";
}



$vyvid.="
   <script type=\"text/javascript\" src=\"scripts/lib/jquery.jeditable.mini.js\"></script>
   <script>

   //var url_prefix='{$url_prefix}';
   //var site_id={$this_site_info['id']};

   $(document).ready(function(){
      $('.imgSelector').each(function(i,el){
         $(el).click(selectCategoryIcon);
      });
     $('.edit').editable('index.php?action=gallery/admin/set_category_description',{
         type      : 'textarea',
         cancel    : 'Cancel',
         submit    : 'OK',
         data: function(value, settings) {
           /* convert value before editing */
           var retval = value.replace(/&amp;/gi, '&');
           return retval;
         }
     });
     $('.edittitle').editable('index.php?action=gallery/admin/set_category_title',{
         submit    : 'OK',
         style   : 'display:inline-block;width:300px;',
         data: function(value, settings) {
           /* convert value before editing */
           var retval = value.replace(/&amp;/gi, '&');
           return retval;
         }
     });


   });

   function selectCategoryIcon(ev){
       var id=$(ev.target).attr('data');
       //console.log(id);
       popupDialogAndReload('index.php?action=gallery/admin/rozdilimages_selector&site_id={$this_site_info['id']}&rozdil='+id, '---');
       //console.log(id);
   }
   </script>
   <style>
   .edit{
     display:inline-block;
     width:400px;
     border:1px inset silver;
     min-height:20px;
   }
   .edit textarea{
     min-height:100px;
   }
   .edittitle{
     height:30px;
     display:inline-block;
     width:200px;
     margin-right:40px;
     border:1px inset silver;
   }
   .imgSelector{
     display:inline-block;
     width:150px;
     height:150px;
     margin:0 10px 10px 0;
     float:left;
     border:1px dotted gray;
   }
   .imgSelectorText{
     color:blue;
     text-decoration:underline;
     background-color:#e0e0e0;
     display:inline-block;
     padding:3px;
     opacity:0.6;
     text-align:center;
   }
   </style>
   ";



$input_vars['page_title'] =
        $input_vars['page_header'] = $this_site_info['title'] . ' - ' . text('image_rozdilimages');
$input_vars['page_content'] = $vyvid;

//--------------------------- context menu -- begin ----------------------------
$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$Site_menu = "<span title=\"" . checkStr($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $Site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
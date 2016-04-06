<?php
/**
 * Additional fields of the category
 */
global $main_template_name; $main_template_name='';
header("Content-Type:text/html; charset=".site_charset);
//echo "@@@@@ ".$input_vars['category_id'];
$cat=\e::db_getonerow("SELECT * FROM {$table_prefix}ec_category WHERE ec_category_id=".( (int)$input_vars['category_id'] ));
//prn($cat);
if(!$cat) return '';
$pa=\e::db_getrows(
"SELECT *
 FROM {$table_prefix}ec_category_item_field
 WHERE site_id={$cat['site_id']}
   AND ec_category_id IN(
          SELECT pa.ec_category_id
          FROM {$table_prefix}ec_category as pa
          WHERE pa.site_id={$cat['site_id']}
          AND pa.start<={$cat['start']} AND {$cat['finish']}<=pa.finish
 order by ec_category_item_field_ordering
 )" );
//prn($pa);
$tor='';
foreach($pa as $fld){
   $tor.='<span class="lbl">'.get_langstring($fld['ec_category_item_field_title']).'</span>';
   if($fld['ec_category_item_field_options']){
       $opts=explode("\n",$fld['ec_category_item_field_options']);
       $tor.="<select class='txt' name='extrafld[{$fld['ec_category_item_field_id']}]'><option value=''>&nbsp;</option>".draw_options('', array_combine($opts, $opts))."</select>";
   }else{
       if($fld['ec_category_item_field_type']=='number'){
           $tor.="<input type='text' name='extrafld[{$fld['ec_category_item_field_id']}][min]' size='3'> ... <input type='text' name='extrafld[{$fld['ec_category_item_field_id']}][max]' size='3'>";
       }else{
           $tor.="<input class='txt' type='text' name='extrafld[{$fld['ec_category_item_field_id']}]'>";
       }
   }
   $tor.='<br/>';
}
//prn($tor);

echo $tor;

?>
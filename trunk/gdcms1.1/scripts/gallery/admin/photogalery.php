<?php
//----------------------Гена придумал ---------------------------------
$data=date ("Y-m-d H:i");

/*
  List of messages in guestbook for moderator
  Argument is
  $site_id - site identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/


#prn($_REQUEST);
  run('site/menu');

# ------------------- site info - begin ----------------------------------------
  if(isset($input_vars['site_id']))
  {
     $site=$site_id = checkInt($input_vars['site_id']);
     $this_site_info = get_site_info($site);

     if(checkInt($this_site_info['id'])<=0)
     {
        $input_vars['page_title']   = $text['Site_not_found'];
        $input_vars['page_header']  = $text['Site_not_found'];
        $input_vars['page_content'] = $text['Site_not_found'];
        return 0;
     }
  }
  else
  {
     $input_vars['page_title']   = $text['Site_not_found'];
     $input_vars['page_header']  = $text['Site_not_found'];
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
# ------------------- site info - end ------------------------------------------


# ------------------- check permission - begin ---------------------------------
   if(get_level($site_id)==0)
   {
      $input_vars['page_title']  = $text['Access_denied'];
      $input_vars['page_header'] = $text['Access_denied'];
      $input_vars['page_content']= $text['Access_denied'];
      return 0;
   }
# ------------------- check permission - end -----------------------------------

# --------------------------- Гена допридумывал --------------------------

$vyvid='';
$lang=$_SESSION['lang'];



$vyvid = "
<script type=\"text/javascript\" src=\"scripts/lib/jquery.jeditable.mini.js\"></script>
<script type=\"text/javascript\">
 $(document).ready(function() {
     $('.rozdil_editable').editable('index.php?action=gallery/admin/setcategory',{
        cssclass : 'imgcategory',
        indicator : 'Saving...',
        tooltip   : 'Click to edit...',
        data: function(value, settings) {
           /* convert value before editing */
           var retval = value.replace(/&amp;/gi, '&');
           return retval;
        }
     });
 });
</script>
<style>
.imgcategory{
  width:100%;
  border:1px solid red;
}
.rozdil_editable{
  height:40px;
}
</style>
<p><a href=index.php?action=gallery/admin/photos&site_id={$site_id}&lang={$lang}>{$text['Gallery_add_new_image']}</a></p><br>
<table width=100% align=center border=1>
<tr>
<th rowspan=2></th>
<th rowspan=2>{$text['Gallery_category']}</th>
<th rowspan=2>{$text['Gallery_image_small']}</th>
<th colspan=2>{$text['Gallery_html_code']}</th>
<th rowspan=2>
<script>
function checkAll(){
   if($('#checkAll').attr('checked')){
      $('input[name=\"vvv[]\"]').attr('checked', true);
   }else{
      $('input[name=\"vvv[]\"]').attr('checked', false);
   }
}
</script>
<input type=checkbox onclick='checkAll()' id=\"checkAll\">
</th>
</tr>
<tr>
<th>{$text['Gallery_thumbnail_link']}</th>
<th>{$text['Gallery_big_image']}</th>
</tr>
<form method=post action=index.php?action=gallery/admin/photogalery&site_id={$site_id}&lang={$lang}>
";

# --------------------- delete images - begin ----------------------------------
if (isset($_REQUEST['vvv']))
if (is_array($_REQUEST['vvv']))
{
   #prn($input_vars['vvv']); die();
   $vvv=$_REQUEST['vvv'];
   $delit='';
   $delit=join(',',$vvv);

   $resultt = db_execute("SELECT * FROM {$table_prefix}photogalery  WHERE id in($delit)");
   $a = mysql_num_rows($resultt);
   while ($roww = mysql_fetch_array($resultt))
   {
      $photoss = $roww['photos'];
      $photoss1 = $roww['photos_m'];
      @unlink(sites_root.'/'.$this_site_info['dir'].'/gallery/'.$photoss);
      @unlink(sites_root.'/'.$this_site_info['dir'].'/gallery/'.$photoss1);
   }
   mysql_query("DELETE FROM {$table_prefix}photogalery WHERE id in($delit)");
   $vyvid .=  $text['Gallery_deleted_images'].":";
   $vyvid .=  "$delit";
}
# --------------------- delete images - end ------------------------------------


# ========================== get list og images = begin ========================

   if (isset($input_vars['start'])) {$start=abs(round(1*$input_vars['start']));} else{$start=0;}


# ----------------------- category filter - begin ------------------------------

  $cats = db_getrows("SELECT DISTINCT rozdil FROM {$table_prefix}photogalery WHERE site = '$site_id'  ORDER BY `rozdil` ASC");
  $tm=Array();
  foreach($cats as $c_t)  $tm[$c_t['rozdil']]=$c_t['rozdil'];

  $show_category=(isset($input_vars['show_category']))?$input_vars['show_category']:'';
  $category_filter="
  <form action=index.php style='margin:0;'>
  <input type=hidden name=action value=gallery/admin/photogalery>
  <input type=hidden name=site_id value=$site_id>
  <input type=hidden name=start value=0>
  {$text['Gallery_category']}: <input type=text name=show_category value=\"".checkStr($show_category)."\" style='width:200px;'>
  <input type=submit value=\"{$text['Search']}\">
  </form>
  <form action=index.php style='margin:0;'>
  <input type=hidden name=action value=gallery/admin/photogalery>
  <input type=hidden name=site_id value=$site_id>
  <input type=hidden name=start value=0>
  {$text['Gallery_category']}: <select style='width:200px;' name=show_category>".draw_options($show_category,$tm)."</select>
  <input type=submit value=\"{$text['Search']}\">
  </form>

  ";
  $vyvid="<div style='float:right;'>$category_filter</div><div style='clear:both;'></div>".$vyvid;

  if(strlen($show_category)>0)
  {
    $show_category_condition=" AND LOCATE('".DbStr($show_category)."',rozdil)=1";
  }else $show_category_condition='';
# ----------------------- category filter - end --------------------------------

# ----------------------- get number of images - begin -------------------------
   $result1 = db_execute("SELECT count(*) FROM {$table_prefix}photogalery WHERE site = '$site' $show_category_condition");
   $num = mysql_fetch_array($result1);
   $num=$num[0];

   $pages='';
   $pages_url_prefix="index.php?action=gallery/admin/photogalery&site_id={$site}&lang={$lang}&show_category=".rawurlencode($show_category)."&start=";
   for($i=0;$i<$num; $i=$i+10)
   {
     if($i==$start) $pages.="<b>[".(1+$i/10)."]</b>\n";
     else $pages.="<a href={$pages_url_prefix}{$i}>".(1+$i/10)."</a>\n";
   }
# ----------------------- get number of images - end ---------------------------



$result = db_execute("SELECT * FROM {$table_prefix}photogalery   WHERE site = '$site' $show_category_condition ORDER BY `id` DESC LIMIT $start, 10");
$a = mysql_num_rows($result);

//$url_prefix=eregi_replace('/+$','',$this_site_info['url']).'/gallery';
$url_prefix=preg_replace("/\\/+$/",'',$this_site_info['url']).'/gallery';

while ($row = mysql_fetch_array($result))
{


   $vyvid .=  "
              <tr>
                <td width='5%'  valign=top><a href=index.php?action=gallery/admin/photored&site_id={$site}&lang={$lang}&new=".$row['id'].">{$text['Gallery_image_edit_label']}</a></td>
                <td width='20%' valign=top>
                <div class=rozdil_editable id=rozdil_{$row['id']}>{$row['rozdil']}</div></td>
                <td valign=top>
                 <a href={$url_prefix}/{$row['photos']} target=_blank>
                 <img src={$url_prefix}/{$row['photos_m']} width=50 style='border:1px solid blue;margin-right:10px;margin-bottom:10px;' align=left>
                 {$row['pidpys']}</a>
                </td>
				<td>
				<div style='width:100%;height:60px;overflow:scroll;padding:3px;'>
				&lt;a href=\"".
                               str_replace(Array('{item}', '{site_id}', '{lang}'),
                                           Array($row['id'], $site_id, $lang),
                                           url_pattern_gallery_image)."\"&gt;
				&lt;img src={$url_prefix}/{$row['photos_m']}&gt;&lt;/a&gt;
				<br><br>
				</div>
				</td>
				<td>
				<div style='width:100%;height:60px;overflow:scroll;padding:3px;'>
				&lt;img src={$url_prefix}/{$row['photos']}&gt;<br><br>
				</div>
				</td>
                <td width='5%' valign=top align=center><input type=checkbox name=\"vvv[]\" value={$row['id']}></td>
              </tr>
              ";
}
$vyvid .=  "
<tr>
<td align=right colspan=6 style='border:none;'><input type=submit value=\"{$text['Gallery_image_delete_selected']}\"></td>
</tr>
</table></form><br><br>";
$vyvid .=  $text['Pages'].': '.$pages;

# ========================== get list og images = end ==========================




//--------------------------- Гена придумал --------------------------
$input_vars['page_title']  =
$input_vars['page_header'] = $this_site_info['title'] .' - '.$text['Gallery_manage'] ;
$input_vars['page_content']= $vyvid;

//--------------------------- context menu -- begin ----------------------------
  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------



//--------------------------- Гена допридумывал --------------------------



?>
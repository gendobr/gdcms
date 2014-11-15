<?php

run('lib/file_functions');
run('ec/item/functions');
run('site/menu');
run('lib/socket_http_function');

# ------------------- check ec_item_id - begin ---------------------------------
$ec_item_id=0;
if(isset($input_vars['ec_item_id'])) {
    $ec_item_id   = (int)$input_vars['ec_item_id'];
    $ec_item_lang = DbStr($input_vars['ec_item_lang']);
    $this_ec_item_info=get_ec_item_info($ec_item_id,$ec_item_lang);
    //prn($this_ec_item_info);
}
if(!$this_ec_item_info) {
    $input_vars['page_title']  =
            $input_vars['page_header'] =
            $input_vars['page_content']=text('ec_item_not_found');
    return 0;
}

# ------------------- check ec_item_id - end -----------------------------------


# ------------------- get site info - begin ------------------------------------
if($ec_item_id>0) $site_id=$this_ec_item_info['site_id'];
else $site_id=(int)(isset($input_vars['site_id'])?$input_vars['site_id']:0);
$this_site_info = get_site_info($site_id);
// prn('$this_site_info=',$this_site_info);
if($this_site_info) $this_ec_item_info['site_id']=$site_id;
# ------------------- get site info - end --------------------------------------

# ------------------- get permission - begin -----------------------------------
$user_cense_level=get_level($site_id);
if($user_cense_level<=0) {
    $input_vars['page_title']  =
            $input_vars['page_header'] =
            $input_vars['page_content']=$text['Access_denied'];
    return 0;
}
# ------------------- get permission - end -------------------------------------




# ------------------- download images - begin ----------------------------------
$input_vars['w']=isset($input_vars['w'])?((int)$input_vars['w']):600;
$input_vars['h']=isset($input_vars['h'])?((int)$input_vars['h']):400;

$report='';
if(isset($input_vars['imh']) && is_array($input_vars['imh'])) {
    //prn($input_vars['imh']);
    
//    
//    function ec_img_resize($photos, $imagefile, $width, $height, $rgb=0xFFFFFF, $quality=100) {
//        if (!file_exists($photos)) return false;
//        $size = getimagesize($photos);
//        if ($size === false) return false;
//
//        $format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
//        $icfunc = "imagecreatefrom" . $format;
//        if (!function_exists($icfunc)) return false;
//
//
//        $x_ratio = $width / $size[0];
//        $y_ratio = $height / $size[1];
//
//        $ratio       = min($x_ratio, $y_ratio);
//        if($ratio>1) {
//           //prn("don't enlarge image") ;
//           if($photos!=$imagefile) copy($photos, $imagefile);
//           return true;
//        }
//
//        //prn("resizing $photos > $imagefile");
//        $use_x_ratio = ($x_ratio == $ratio);
//
//        $new_width   = $use_x_ratio  ? $width  : floor($size[0] * $ratio);
//        $new_height  = !$use_x_ratio ? $height : floor($size[1] * $ratio);
//        $new_left    = $use_x_ratio  ? 0 : floor(($width - $new_width) / 2);
//        $new_top     = !$use_x_ratio ? 0 : floor(($height - $new_height) / 2);
//
//
//
//        $bigimg = $icfunc($photos);
//        $trumbalis = imagecreatetruecolor($width, $height);
//
//        imagefill($trumbalis, 0, 0, $rgb);
//        imagecopyresampled($trumbalis, $bigimg, $new_left, $new_top, 0, 0, $new_width, $new_height, $size[0], $size[1]);
//
//        imagejpeg($trumbalis, $imagefile, $quality);
//
//        imagedestroy($bigimg);
//        imagedestroy($trumbalis);
//        return true;
//    }

    # create directory
    $relative_dir=date('Y').'/'.date('m');
    $site_root_dir=sites_root.'/'.$this_site_info['dir'];
    path_create($site_root_dir,"/gallery/$relative_dir/");
    $prefix="$site_root_dir/gallery/$relative_dir/";


    foreach($input_vars['imh'] as $imid=>$im_src) {
        if(!is_valid_url($im_src)) continue;
        $response=http($im_src,Array(),Array());
        if($response['is_successful']) {
            $fname     = time()."-{$imid}.".file_extention($im_src);
            $file_path = "{$prefix}/{$fname}";
            file_put_contents($file_path, $response['body']);

            //$newfname  = time()."-{$imid}.jpg";
            //if(ec_img_resize($file_path, "{$prefix}/{$newfname}", $input_vars['w'], $input_vars['h'])) {
            //    $report.="\ngallery/$relative_dir/{$newfname}";
            //}
            
            $newfname  = time()."-{$imid}.jpg";
            
            $big_image_file_name = "{$this_ec_item_info['site_id']}-{$data}-big-" . $newfname;
            $big_file_path="$site_root_dir/gallery/$relative_dir/$big_image_file_name";
            ec_img_resize($file_path, $big_file_path, gallery_big_image_width, gallery_big_image_height, "resample");

            $small_image_file_name = "{$this_ec_item_info['site_id']}-{$data}-small-" . $newfname;
            $small_file_path="$site_root_dir/gallery/$relative_dir/$small_image_file_name";
            ec_img_resize($file_path, $small_file_path, gallery_small_image_width, gallery_small_image_width, "circumscribe");

            unlink($file_path);
        }
    }
    if(strlen($report)>0) {
        $query="UPDATE {$table_prefix}ec_item SET cache_datetime=null,cached_info=null,ec_item_img='".DbStr(trim(join("\n",$this_ec_item_info['ec_item_img']).$report))."' WHERE ec_item_id=$ec_item_id AND ec_item_lang='$ec_item_lang' AND site_id=$site_id";
        // prn($query);
        db_execute($query);
        $this_ec_item_info=get_ec_item_info($ec_item_id,$ec_item_lang);
    }
}
# ------------------- download images - end ------------------------------------

# ------------------- draw page - begin ----------------------------------------




$input_vars['page_title']   = text('EC-item-edit');
$input_vars['page_header']  = text('EC-item-edit');
$input_vars['page_content'] = "
<style>
span.blk{
  display:inline-block;
  width:200px;
  text-align:center;
  vertical-align:top;
  margin-top:20px;
}
span.blk input[type=\"checkbox\"]{
  width:15px;height:15px;
}
div.info{
  background-color:#e0e0e0;
  border:1px dotted gray;
  padding:4pt;
}

span.imgs{
  display:inline-block;
  width:300px;
  height:400px;
  overflow:scroll;
  float:right;
}
</style>
<div class=info>

<span class=imgs>
";
foreach($this_ec_item_info['ec_item_img'] as $f) {
    $input_vars['page_content'].= "<img width=250px style='margin-bottom:10px;' src={$this_site_info['url']}/{$f}><br/>";
}

$input_vars['page_content'].= "
</span>


<h3>{$this_ec_item_info['ec_item_title']}</h3>
<div>{$this_ec_item_info['ec_item_uid']}</div>
<div>{$this_ec_item_info['ec_item_content']}</div>
<div>{$this_ec_item_info['ec_item_abstract']}</div>
<div>{$this_ec_item_info['ec_item_tags']}</div>
<div>{$this_ec_item_info['ec_item_material']}</div>
<div>{$this_ec_item_info['ec_producer_title']}</div>
<div>{$this_ec_item_info['ec_category_title']}</div>
        ";
foreach($this_ec_item_info['ec_category_item_field'] as $f) {
    $input_vars['page_content'].= "<div>".get_langstring($f['ec_category_item_field_title'])." : {$f['ec_category_item_field_value']}</div>";
}

foreach($this_ec_item_info['ec_item_variant'] as $f) {
    $input_vars['page_content'].= "<div style='padding-left:".(20*$f['ec_item_variant_indent'])."px'>{$f['ec_item_variant_description']}</div>";
}
$input_vars['page_content'].= "
<div style='clear:both;'></div>
</div>";

$start=isset($input_vars['start'])?( (int)$input_vars['start'] ):0;
$query=isset($input_vars['query'])?$input_vars['query']:"{$this_ec_item_info['ec_item_uid']} + {$this_ec_item_info['ec_producer_title']} + {$this_ec_item_info['ec_item_title']}";



if(!isset($input_vars['imgsz'])) $input_vars['imgsz']='large';
$input_vars['page_content'].= "<br/>
<form action=index.php method=post>
<input type=hidden name=action       value='ec/item/search_images'>
<input type=hidden name=start       value='$start'>
<input type=hidden name=ec_item_id   value='$ec_item_id'>
<input type=hidden name=ec_item_lang value='$ec_item_lang'>
<select name=imgsz>".draw_options($input_vars['imgsz'], Array('icon'=>'icon','small'=>'small','medium'=>'medium','large'=>'large','xlarge'=>'xlarge','xxlarge'=>'xxlarge','huge'=>'huge'))."</select>
<input style='width:300px;' type=text name=query value='".checkStr($query)."'>
<input type=submit value=\"".text('Search')."\"><br/><br/>
<br/>
width:<input type=text name=w value={$input_vars['w']}>
height:<input type=text name=h value={$input_vars['h']}>
<br/>
";



$google_answer=http('http://ajax.googleapis.com/ajax/services/search/images',
        Array('v'=>'1.0','q'=>$query,'imgsz'=>'large','hl'=>'en','start'=>$start),Array());
//,'as_filetype'=>'jpg'
//prn('$google_answer',$google_answer);

$json = json_decode($google_answer['body']);
//prn($json);
if($json->responseData->results) {
    foreach ($json->responseData->results as $result) {
        $input_vars['page_content'].= "
        <span class=blk><label>
        <input type=checkbox name=imh[] value=\"{$result->url}\">
        <img align=top src=\"{$result->tbUrl}\" title=\"{$result->contentNoFormatting}\"/></label>
        <br/>
                {$result->titleNoFormatting}<br/>
                {$result->width}px &times; {$result->height}px<br>
            <a href=\"{$result->url}\" target=_blank>���������</a>
        </span>
                ";
    }

    $input_vars['page_content'].= "<br><br>";
    $url_prefix=site_root_URL.'/index.php?'.query_string('^start$|^imh$').'&start=';
    foreach($json->responseData->cursor->pages as $pg) {
        if($start!=$pg->start) {
            $input_vars['page_content'].= " <a href=\"{$url_prefix}{$pg->start}\">{$pg->label}</a> ";
        }else {
            $input_vars['page_content'].= " <b>{$pg->label}</b> ";
        }
    }


}

$input_vars['page_content'].= "<br/>URL:<input type=text name=imh[] style='width:99%;' value=\"\">";
$input_vars['page_content'].= "<br><br><input type=submit value=\"".text('Upload')."\">";
$input_vars['page_content'].= "</form>";

# ------------------- draw page - end ------------------------------------------




//----------------------------- context menu - begin ---------------------------
$input_vars['page_menu']['page']=Array('title'=>text('EC_item'),'items'=>Array());
$input_vars['page_menu']['page']['items'] = menu_ec_item($this_ec_item_info);

$sti=$text['Site'].' "'. $this_site_info['title'].'"';
$input_vars['page_menu']['site']=Array('title'=>"<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());

$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);

//----------------------------- context menu - end -----------------------------

?>
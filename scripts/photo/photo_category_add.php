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

$list_of_languages = list_of_languages();


// get list ordering by path
run('photo/functions');

$post=[];
$post['photo_category_title']='';
$post['category_parent']='';


if(\e::request('posted','')=='true'){
    $errors=[];

    // do save

    $post['site_id']=$site_id;
    
    $post['photo_category_title']=\e::request('photo_category_title','');
    if(strlen($post['photo_category_title'])==0){
        $errors['photo_category_title']=text('photo_category_title_is_empty');
    }
    
    $post['photo_category_ordering']=\e::cast('integer',\e::request('photo_category_ordering',0));
    
    $post['photo_category_description']='';
    foreach ($list_of_languages as $lng) {
        $element_name="photo_category_description_{$lng['name']}";
        $val=trim(\e::cast('plaintext',\e::request($element_name,'')));
        if(strlen($val)>0){
            $post['photo_category_description'].="<{$lng['name']}>{$val}</{$lng['name']}>";
        }
    }
    
    $post['photo_category_visible']=\e::cast('integer',\e::request('photo_category_visible',0));

    $post['photo_category_meta']='';
    foreach ($list_of_languages as $lng) {
        $element_name="photo_category_meta_{$lng['name']}";
        $val=trim(\e::cast('plaintext',\e::request($element_name,'')));
        if(strlen($val)>0){
            $post['photo_category_meta'].="<{$lng['name']}>{$val}</{$lng['name']}>";
        }
    }

    
    if(count($errors)==0){

        // create photo category
        \e::db_execute(
                "INSERT INTO <<tp>>photo_category(site_id,photo_category_title,photo_category_ordering,photo_category_description,photo_category_visible, photo_category_meta)
                 VALUES (<<integer site_id>>,<<string photo_category_title>>,<<integer photo_category_ordering>>,<<string photo_category_description>>,<<integer photo_category_visible>>, <<string photo_category_meta>>)",
                $post);
        $photo_category_id=\e::db_getonerow("SELECT LAST_INSERT_ID() as newid");
        $photo_category_id=$photo_category_id['newid'];
        
        
        // ------------- post-process - begin ----------------------------------
        $post['photo_category_code'] = \core\fileutils::encode_dir_name(\e::request('photo_category_code',''));
        if(strlen($post['photo_category_code'])==0){
            $post['photo_category_code'] = \core\fileutils::encode_dir_name(get_langstring($post['photo_category_title'], \e::config('default_language')));
        }
        // check if photo_category_code is unique
        $info = \e::db_getonerow("SELECT * FROM <<tp>>photo_category WHERE photo_category_code=<<string code>> ",['code'=>$post['photo_category_code']]);
        if($info){
             $post['photo_category_code'].="-{$photo_category_id}";
        }

        $category_parent=\e::request('category_parent','');
        $post['photo_category_path']=$post['photo_category_code'];
        if(strlen($category_parent)>0){
            $parent_info=\e::db_getonerow("SELECT * FROM <<tp>>photo_category WHERE photo_category_path=<<string path>> ",['path'=>$category_parent]);
            if($parent_info){
                $post['photo_category_path']="{$parent_info['photo_category_path']}/{$post['photo_category_code']}";
            }
        }

        \e::db_execute(
                "UPDATE <<tp>>photo_category
                  SET photo_category_code=<<string photo_category_code>>,
                      photo_category_path=<<string photo_category_path>>
                  WHERE photo_category_id=<<integer photo_category_id>>",
                [
                    'photo_category_id'=>$photo_category_id,
                    'photo_category_code'=>$post['photo_category_code'],
                    'photo_category_path'=>$post['photo_category_path']
                ]);

        // photo_category_icon         text           utf8_bin   YES             (NULL)                   select,insert,update,references           
        # ----------------- upload icon - begin ------------------------------------
        // prn($_FILES);
        if(isset($_FILES['photo_category_icon']) && $_FILES['photo_category_icon']['error']==0){
            $img=new \core\img();
            
            $relative_dir="gallery/".date('Y').'/'.date('m');
            $dir="{$this_site_info['site_root_dir']}/{$relative_dir}";
            \core\fileutils::path_create($this_site_info['site_root_dir'], "{$dir}/");

            $newFileName="photo_category-{$photo_category_id}-".\core\fileutils::encode_file_name($_FILES['photo_category_icon']['name']);

            if(move_uploaded_file($_FILES['photo_category_icon']['tmp_name'], "{$dir}/{$newFileName}") ){
                //                // ---------------- delete previous icons - begin ------------------
                //                if($this_category->info['category_icon'] && is_array($this_category->info['category_icon'])){
                //                    foreach($this_category->info['category_icon'] as $pt){
                //                        $pt=trim($pt);
                //                        if(strlen($pt)>0){
                //                            $path=realpath("{$this_site_info['site_root_dir']}/{$pt}");
                //                            if($path && strncmp( $path , $this_site_info['site_root_dir'] , strlen($this_site_info['site_root_dir']) )==0){
                //                                unlink($path);
                //                            }
                //                        }
                //                    }
                //                }
                //                // ---------------- delete previous icons - end --------------------

                // ---------------- upload new icons - begin -----------------------
                $smallFileName="photo_category-{$photo_category_id}-small-".\core\fileutils::encode_file_name($_FILES['photo_category_icon']['name']);
                $img->resize("{$dir}/{$newFileName}", "{$dir}/{$smallFileName}", \e::config('gallery_small_image_width'), \e::config('gallery_small_image_height'), $rgb = 0xFFFFFF, $quality = 100, \core\img::$MODE_MAX_RATIO);
                $photo_category_icon=['small'=>"{$relative_dir}/{$smallFileName}", "full"=>"{$relative_dir}/{$newFileName}"];
                \e::db_execute(
                        "UPDATE <<tp>>photo_category
                          SET photo_category_icon=<<string photo_category_icon>>
                          WHERE photo_category_id=<<integer photo_category_id>>",
                        [
                            'photo_category_id'=>$photo_category_id,
                            'photo_category_icon'=>json_encode($photo_category_icon)
                        ]);
                // ---------------- upload new icons - end -------------------------
            }
        }
        # ----------------- upload icon - end --------------------------------------

        // ------------- post-process - end ------------------------------------
        // redirect to editor page
        \e::redirect(\e::url(['action'=>'photo/photo_category_edit','photo_category_id'=>$photo_category_id]));
    }
    
    
    
    


}

// prn($list_of_languages);
$js_lang = Array();
foreach ($list_of_languages as $l) {
    $js_lang[$l['name']] =$text[$l['name']];
}
$js_lang = json_encode($js_lang);


$parent_list =  array_map(
    function($row){
        return [
            $row['photo_category_path'],
            str_repeat('&nbsp;|&nbsp;&nbsp;&nbsp;',  substr_count($row['photo_category_path'], '/')).get_langstring($row['photo_category_title'])
        ];
    },
    \e::db_getrows("SELECT * FROM <<tp>>photo_category photo_category WHERE site_id=<<integer site_id>> ORDER BY photo_category_path ASC",['site_id'=>$site_id])
);

$html = "
       <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/jquery.markitup.js\"></script>
       <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup/sets/html/set.js\"></script>
       <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/markitup.js\"></script>
       <!-- <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/jquery.ns-autogrow.min.js\"></script> -->
       <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/skins/simple/style.css\" />
       <link rel=\"stylesheet\" type=\"text/css\" href=\"./scripts/lib/markitup/sets/html/style.css\" />

       <script type=\"text/javascript\" charset=\"utf-8\" src=\"./scripts/lib/choose_links.js\"></script>
       <script type=\"text/javascript\">
          $(function(){
              init_links();
              $('textarea.wysiswyg').markItUp(mySettings);
              //$('textarea.wysiswyg').autogrow({vertical: true, horizontal: false});
          });
       </script>
";


if(isset($errors) && count($errors)>0){
    $html.="<div class='error'>".join('<br>',$errors)."</div>";
    
}

$html.="
<script type=\"text/javascript\" src=\"scripts/lib/langstring.js\"></script>
<form action=index.php method=POST  name=editform  enctype=\"multipart/form-data\">
    <input type=\"hidden\" name=\"site_id\" value=\"{$site_id}\">
    <input type=\"hidden\" name=\"action\" value=\"photo/photo_category_add\">
    <input type=\"hidden\" name=\"posted\" value=\"true\">
    

   <div><!-- 
   --><span class=blk8>

    <div class=\"label\">".text('photo_category_title')."</div>
    <div class=\"big\"><input type=text name=\"photo_category_title\" id=\"photo_category_title\" value=\"".  htmlspecialchars($post['photo_category_title'])."\"></div>
    <script type=\"text/javascript\">
            langs=$js_lang;
            draw_langstring('photo_category_title');
    </script>
    </span><!-- 
   --><span class=blk4>
    <div class=label>" . text('Icon') . "</div>

    <input type=\"file\" name=\"photo_category_icon\">
    </span><!-- 
    --></div>
    
    <div class=label>" . text('category_parent') . "</div>
    <div class=big>
        <select name='category_parent' id='category_move_into'>
            <option value=''></option>
            ".\core\form::draw_options('', $parent_list)."
        </select>
    </div>
    <div><!-- 
   --><span class=blk4>
    <div class=\"label\">".text('photo_category_code')."</div>
    <div class=\"big\"><input type=text name=\"photo_category_code\" id=\"photo_category_code\" value=\"".  htmlspecialchars(\e::request('photo_category_code',''))."\"></div>
    </span><!-- 
   --><span class=blk4>
    <div class=\"label\">".text('photo_category_ordering')."</div>
    <div class=\"big\"><input type=text name=\"photo_category_ordering\" id=\"photo_category_ordering\" value=\"".  htmlspecialchars(\e::request('photo_category_ordering',''))."\"></div>
    </span><!-- 
   --><span class=blk4>
    <div class=label>".text('photo_category_visible')."</div>
    <div class=big>" . \core\form::draw_radio(\e::request('photo_category_visible',1),[1=>text('positive_answer'),0=>text('negative_answer')], 'photo_category_visible') . "</div>
    </span><!-- 
    --></div>
";

       
//     photo_category_path         varchar(1024)  utf8_bin   YES             (NULL)                   select,insert,update,references           
//    photo_category_description  text           utf8_bin   YES             (NULL)                   select,insert,update,references           
$html.="<h3>".text('category_description')."</h3>";
//prn($list_of_languages);
foreach ($list_of_languages as $lng) {
    $element_name="photo_category_description_{$lng['name']}";
    $html.="
    <div class=label style='font-size:110%;'>({$lng['name']})</div>
    <div class=big>
      <div>
          <a href=\"javascript:void(0)\" onclick=\"display_gallery_links('index.php?action=photo/json&lang={$lng['name']}&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Gallery') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_category_links('index.php?action=category/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Category') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_page_links('index.php?action=site/page/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">" . text('Pages') . "</a>
          <a href=\"javascript:void(0)\" onclick=\"display_file_links('index.php?action=site/filechooser/json&site_id={$site_id}',this)\" style=\"display:inline-block;\">{$text['Insert_link_to_file']}</a>
      </div>
     <textarea name='{$element_name}'
               id='{$element_name}'
                   class='wysiswyg'
               style='width:100%;height:100px;'>"
            . htmlspecialchars(\e::request($element_name,''))
            . "</textarea>

    </div>
    ";
}

$html.="
<script type=\"text/javascript\" charset=\"utf-8\" src=\"".site_root_URL."/scripts/lib/meta-tags-insert.js\"></script>
  ";
$html.="<h3>Meta tags</h3>";
//prn($list_of_languages);
foreach ($list_of_languages as $lng) {
    $element_name="photo_category_meta_{$lng['name']}";
    $html.="
    <div class=label>({$lng['name']})</div>
    <div class=big>
        <textarea name='$element_name' id='$element_name' style='width:100%;height:100px;'>"
            . htmlspecialchars(\e::request($element_name,''))
            . "</textarea>
        <script type=\"text/javascript\">
        $(document).ready(function(){
           metaTagsButtons('{$element_name}');
        });
        </script>
    </div>
    ";
}


$html.="
    <br>
    <input type=\"submit\" name=\"save\" value=\"".  htmlspecialchars(text('photo_category_add'))."\">
</form>
";






$input_vars['page_header']=$input_vars['page_title']=text('photo_category_add');
$input_vars['page_content'] = $html;

//--------------------------- context menu -- begin ----------------------------

$sti = $text['Site'] . ' "' . $this_site_info['title'] . '"';
$site_menu = "<span title=\"" . htmlspecialchars($sti) . "\">" . shorten($sti, 30) . "</span>";
$input_vars['page_menu']['site'] = Array('title' => $site_menu, 'items' => Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

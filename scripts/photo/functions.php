<?php

function photo_menu($photo_info){
        $menu = Array();
        //$menu[] = Array(
        //      'URL' => ''
        //    , 'innerHTML' => "<b>" . text('photo_id')." ". $photo_info['photo_id'] . " : </b>"
        //    , 'attributes' => ''
        //);
        $menu[] = Array(
              'URL' => \e::url(['action'=>'photo/photo_edit','photo_id'=>$photo_info['photo_id']])
            , 'innerHTML' => text('photo_edit')
            , 'attributes' => ''
        );
        $menu[] = Array(
              'URL' => \e::url(['action'=>'photo/photo_view','photo_id'=>$photo_info['photo_id']])
            , 'innerHTML' => text('photo_view')
            , 'attributes' => ' target=_blank '
        );
        $menu[] = Array(
              'URL' => \e::url_update(['delete_photo_id[]'=>$photo_info['photo_id']],'/delete_photo_id/')
            , 'innerHTML' => text('photo_delete')
            , 'attributes' => ' style="margin-top:10pt;" onclick=\'return confirm("Are you sure?")\''
        );
        return $menu;
}

function photo_delete($site_info,$photo_ids){
    // prn($photo_ids);
    $list=\e::db_getrows("SELECT * FROM <<tp>>photo WHERE site_id=<<integer site_id>> AND photo_id in (<<integer[] photo_id>>)", ['site_id'=>$site_info['id'], 'photo_id'=>$photo_ids],false);
    // \e::info($list);
    foreach($list as $it){
        $it['photo_imgfile']=json_decode($it['photo_imgfile'], true);
        if($it['photo_imgfile']){
            foreach($it['photo_imgfile'] as $f){
                $path=$site_info['site_root_dir'].'/'.$f;
                // prn($path);
                if(is_file($path)){
                    unlink($path);
                }
            }
        }
    }
    $list=\e::db_execute("DELETE FROM <<tp>>photo WHERE site_id=<<integer site_id>> AND photo_id in (<<integer[] photo_id>>)", ['site_id'=>$site_info['id'], 'photo_id'=>$photo_ids],false);
}

function photo_category_info($photo_category_id){
    $info=\e::db_getonerow(
            "SELECT photo_category.* , count(photo.photo_id) as nPhotos
             FROM <<tp>>photo_category photo_category
                 LEFT JOIN <<tp>>photo photo ON (photo.photo_category_id = photo_category.photo_category_id)
             WHERE photo_category.photo_category_id=".( (int)$photo_category_id )."
             GROUP BY photo_category.photo_category_id
             ");
    if($info) {
        if(substr_count($info['photo_category_path'], "/")==0){
            $info['category_parent']='';
        }else{
            $info['category_parent']=preg_replace("/\\/[^\\/]+\$/","",$info['photo_category_path']);
        }
        if ($info['photo_category_icon']) {
            $info['photo_category_icon'] = json_decode($info['photo_category_icon'], true);
        }
    }
    return $info;
}



function photo_category_find($photo_category_id, $photo_category_path, $photo_category_code){
    $info=\e::db_getonerow(
            "SELECT photo_category.* , count(photo.photo_id) as nPhotos
             FROM <<tp>>photo_category photo_category
                 LEFT JOIN <<tp>>photo photo ON (photo.photo_category_id = photo_category.photo_category_id)
             WHERE 
                   photo_category.photo_category_id=<<integer photo_category_id>>
                OR photo_category.photo_category_code=<<string photo_category_code>>
                OR photo_category.photo_category_path=<<string photo_category_path>>
             GROUP BY photo_category.photo_category_id
             LIMIT 0,1
             ",[
                 'photo_category_id'=>$photo_category_id, 
                 'photo_category_path'=>$photo_category_path, 
                 'photo_category_code'=>$photo_category_code
             ]);    
    if($info) {
        if(substr_count($info['photo_category_path'], "/")==0){
            $info['category_parent']='';
        }else{
            $info['category_parent']=preg_replace("/\\/[^\\/]+\$/","",$info['photo_category_path']);
        }
        if ($info['photo_category_icon']) {
            $info['photo_category_icon'] = json_decode($info['photo_category_icon'], true);
        }
    }
    return $info;
}


function photo_info($photo_id){

    $info=\e::db_getonerow(
            "SELECT photo_category.*, photo.* 
             FROM <<tp>>photo photo 
                  LEFT JOIN <<tp>>photo_category photo_category
                   ON (photo.photo_category_id = photo_category.photo_category_id)
             WHERE photo.photo_id=".( (int)$photo_id )."
             ");
    if(!$info){
        return false;
    }
    $info['photo_imgfile']=  json_decode($info['photo_imgfile'],true);
    return $info;
}

function photo_category_menu($photo_category_info){
        $menu = Array();
        $menu[] = Array(
            'url' => ''
            , 'html' => "<b>" . get_langstring($photo_category_info['photo_category_title']) . " : </b>"
            , 'attributes' => ''
        );
        $menu[] = Array(
              'url' => \e::url(['action'=>'photo/photo_category_edit','photo_category_id'=>$photo_category_info['photo_category_id']])
            , 'html' => text('photo_category_edit')
            , 'attributes' => ''
        );
        $menu[] = Array(
              'url' => \e::url(['action'=>'photo/photo_category_view','photo_category_id'=>$photo_category_info['photo_category_id']])
            , 'html' => text('photo_category_view')
            , 'attributes' => ''
        );
        $menu[] = Array(
              'url' => \e::url(['action'=>'photo/photo_category_delete','photo_category_id'=>$photo_category_info['photo_category_id'],'delete_children'=>1])
            , 'html' => text('photo_category_delete_with_children')
            , 'attributes' => ' style="display:block; margin-top:10pt;" '
        );
        $menu[] = Array(
              'url' => \e::url(['action'=>'photo/photo_category_delete','photo_category_id'=>$photo_category_info['photo_category_id']])
            , 'html' => text('photo_category_delete')
            , 'attributes' => ''
        );
        
        return $menu;
}





class PhotoCategoryViewer {

    // input data
    protected $lang, 
              $this_site_info, 
              $photo_category_info,
              $start;

    // parameters
    private $rowsPerPage = 10;
    
    private $_includeSubcategories = 10;
    
    private $orderBy = 'photo_category_title ASC, id ASC';
    // mapping of the sortable_columns
    private $sortable_columns = Array(
        'date' => 'photo_category_id',
        'id' => 'photo_category_id',
        'code' => 'photo_category_code',
        'rozdil' => 'photo_category_code',
        'tag' => 'photo_category_code',
        'title' => 'photo_category_title',
        'default' => 'photo_category_code',
        'weight' => 'photo_category_code'
    );
    // output data
    protected $parents, $children, $images, $_this_category_info, $breadcrumbs;

    
    
    
    function __construct($lang, $this_site_info, $photo_category_info) {
        $this->lang = $lang;
        $this->site_info = $this_site_info;
        // $this->start = (int) $start;
        
        $this->url_pattern_gallery_category = \e::config('url_pattern_photo_category');
        $this->orderBy='';
        
        
        $this->photo_category_info = $photo_category_info;
        $this->photo_category_info['photo_category_title']=  get_langstring($this->photo_category_info['photo_category_title'], $this->lang);
        $this->photo_category_info['photo_category_description']=  get_langstring($this->photo_category_info['photo_category_description'], $this->lang);
        $this->photo_category_info['photo_category_meta']=  get_langstring($this->photo_category_info['photo_category_meta'], $this->lang);

    }

    function __get($attr) {

        switch ($attr) {

            case 'breadcrumbs':
            case 'parents':
                if (!isset($this->parents)) {
                    $this->parents = $this->parents();
                }
                return $this->parents;

            case 'category':
            case 'info':
                return $this->photo_category_info;

            case 'children':
                if (!isset($this->children)) {
                    $this->children = $this->children();
                }
                return $this->children;

            case 'images':
            case 'photos':
            case 'photo':
            case 'img':
                if (!isset($this->images)) {
                    $this->images = $this->images();
                }
                return $this->images;

            case 'nImages':
            case 'nPhotos':
            case 'nPhoto':
            case 'nImg':
            case 'n':
                if (!isset($this->images)) {
                    $this->images = $this->images();
                }
                return count($this->images);

            default: return Array();
        }
    }

    public function rowsPerPage($rpp){
        $this->rowsPerPage = (int) $rpp;
        if ($this->rowsPerPage <= 0) {
            $this->rowsPerPage = 10;
        }
        return null;
    }

    public function includeSubcategories($v){
        $this->_includeSubcategories=$v?1:0;
    }
    
    public function orderBy($attr){
        $opt = explode(',', $attr);
        
        $ordering = Array();
        
        foreach($opt as $op){
            $ofld=preg_split("/ +/",trim($op));
            if (isset($this->sortable_columns[$ofld[0]])) {
                $ordering[] = $this->sortable_columns[$ofld[0]] . ' ' . (  ( isset($ofld[1]) && strtolower($ofld[1] == 'desc' ) ? 'desc' : 'asc'));
            }
        }
        if (count($ordering) > 0) {
            $this->orderBy = join(', ', $ordering);
        }
        return null;
    }
    
    private function parents() {

        if(!$this->photo_category_info || strlen($this->photo_category_info['photo_category_path'])==0 ){
            return [];
        }

        $parents=[];
        
        $path=$this->photo_category_info['photo_category_path'];
        do{
            $parents[$path]='';
            $path=preg_replace("/[^\\/]+\$/",'',$path);
            $path=preg_replace("/\\/\$/",'',$path);
        }while(strlen($path)>0);
        

        $sql="SELECT * FROM <<tp>>photo_category WHERE site_id=<<integer site_id>> AND photo_category_path in(<<string[] path>>)";
        $result=\e::db_getrows($sql,['path'=>array_keys($parents),'site_id'=>$this->site_info['id']],false);
        foreach($result as $res){
            $res['photo_category_title']=  get_langstring($res['photo_category_title'], $this->lang);
            $res['photo_category_description'] = '';
            $res['photo_category_icon'] = json_decode($res['photo_category_icon'],true);
            $res['url']=$res['URL']=str_replace([
                '{photo_category_code}','{photo_category_path}','{photo_category_id}','{lang}', '{site_id}'
            ],[
                $res['photo_category_code'],$res['photo_category_path'],$res['photo_category_id'],$this->lang, $this->site_info['id']
            ],\e::config('url_pattern_photo_category'));
            $parents[$res['photo_category_path']]=$res;
        }
        
        
        
        $parents['']=[
            'photo_category_title'=>$this->site_info['title'].' - '.text('photo_list'),
            'photo_category_description' => '',
            'photo_category_icon'=> false,
            'url'=>($tmp=str_replace([
                '{photo_category_code}','{photo_category_path}','{photo_category_id}','{lang}', '{site_id}'
            ],[
                '','',0,$this->lang, $this->site_info['id']
            ],\e::config('url_pattern_photo_category'))),
            'URL'=>$tmp
        ];
        
        $pathCompare=function($a, $b){
            $la=strlen($a);
            $lb=strlen($b);
            if($la==$lb){
                return 0;
            }
            if($la<$lb){
                return -1;
            }
            return 1;
        };
        uksort($parents, $pathCompare);
        return $parents;
    }

    private function children() {

        if($this->photo_category_info && isset($this->photo_category_info['photo_category_path']) && strlen($this->photo_category_info['photo_category_path'])>0){
            $sql="SELECT photo_category.* , count(photo.photo_id) as n_images
                  FROM <<tp>>photo_category  photo_category
                       LEFT JOIN <<tp>>photo photo ON photo.photo_category_id= photo_category.photo_category_id
                  WHERE photo_category.site_id=<<integer site_id>>  
                    AND photo_category.photo_category_visible
                    AND photo_category.photo_category_path LIKE '".\e::db_escape($this->photo_category_info['photo_category_path'])."/%' 
                    AND LOCATE('/',photo_category.photo_category_path, LENGTH('".\e::db_escape($this->photo_category_info['photo_category_path'])."')+2)=0
                  GROUP BY photo_category.photo_category_id
                  ORDER BY photo_category_ordering ASC, photo_category_path asc";
            $result=\e::db_getrows($sql,['site_id'=>$this->site_info['id']],false);
        }else{
            $sql="SELECT photo_category.* , count(photo.photo_id) as n_images 
                  FROM <<tp>>photo_category  photo_category
                       LEFT JOIN <<tp>>photo photo ON photo.photo_category_id= photo_category.photo_category_id
                  WHERE photo_category.site_id=<<integer site_id>> 
                    AND photo_category.photo_category_visible
                    AND LOCATE('/',photo_category.photo_category_path)=0
                  GROUP BY photo_category.photo_category_id
                  ORDER BY photo_category_ordering ASC, photo_category_path asc";
            $result=\e::db_getrows($sql,['site_id'=>$this->site_info['id']],false);
        }
        

        for($i=0, $cnt=count($result); $i<$cnt; $i++){
            $res = &$result[$i];
            $res['photo_category_title']=  get_langstring($res['photo_category_title'], $this->lang);
            $res['photo_category_description'] = '';
            $res['photo_category_meta'] = '';
            $res['photo_category_icon'] = json_decode($res['photo_category_icon'],true);
            $res['url']=$res['URL']=str_replace([
                '{photo_category_code}','{photo_category_path}','{photo_category_id}','{lang}', '{site_id}'
            ],[
                $res['photo_category_code'],$res['photo_category_path'],$res['photo_category_id'],$this->lang, $this->site_info['id']
            ],\e::config('url_pattern_photo_category'));
        }

        return $result;
    }

    private function images(){
        if($this->photo_category_info && strlen($this->photo_category_info['photo_category_path'])>0 ){
            if($this->_includeSubcategories){
                $result=\e::db_getrows(
                        "SELECT * FROM <<tp>>photo 
                         WHERE site_id=<<integer site_id>> AND photo_visible 
                           AND photo_category_id IN (
                               SELECT photo_category_id FROM <<tp>>photo_category 
                               WHERE site_id=<<integer site_id>> 
                                AND ( photo_category_path LIKE '".\e::db_escape($this->photo_category_info['photo_category_path'])."/%' 
                                      OR photo_category_path = '".\e::db_escape($this->photo_category_info['photo_category_path'])."'   ) ) 
                         ORDER BY photo_id desc 
                         LIMIT 0,{$this->rowsPerPage}",
                        ['site_id'=>$this->site_info['id']]);                
            }else{
                $result=\e::db_getrows(
                        "SELECT * FROM <<tp>>photo 
                         WHERE site_id=<<integer site_id>> AND photo_visible 
                           AND photo_category_id=<<integer photo_category_id>> 
                         ORDER BY photo_id desc
                         LIMIT 0,{$this->rowsPerPage}",['site_id'=>$this->site_info['id'], 'photo_category_id'=>$this->photo_category_info['photo_category_id']]);
            }
            
        }else{
            if($this->_includeSubcategories){
                $result=\e::db_getrows(
                        "SELECT * FROM <<tp>>photo 
                         WHERE site_id=<<integer site_id>> AND photo_visible 
                         ORDER BY photo_id desc')
                         LIMIT 0,{$this->rowsPerPage}",
                        ['site_id'=>$this->site_info['id'],'photo_category_id'=>$this->photo_category_info['photo_category_id']]);
            }else{
                $result=\e::db_getrows(
                        "SELECT * FROM <<tp>>photo 
                         WHERE site_id=<<integer site_id>> AND photo_visible 
                           AND photo_category_id=0 
                         ORDER BY photo_id desc
                         LIMIT 0,{$this->rowsPerPage}",
                    ['site_id'=>$this->site_info['id'],'photo_category_id'=>$this->photo_category_info['photo_category_id']]);
            }
        }
        
        for($i=0, $cnt=count($result); $i<$cnt; $i++){
            $res['photo_title']=  get_langstring($res['photo_title'], $this->lang);
            $res['photo_author']=  get_langstring($res['photo_author'], $this->lang);
            $res['photo_description']=  get_langstring($res['photo_description'], $this->lang);
            
            $result[$i]['photo_imgfile'] = json_decode($result[$i]['photo_imgfile'], true);

            $result[$i]['url']=$result[$i]['URL']=str_replace([
                '{photo_id}','{lang}', '{site_id}'
            ],[
                $result[$i]['photo_id'],$this->lang, $this->site_info['id']
            ],\e::config('url_pattern_photo'));
        }
        return $result;
    }
}


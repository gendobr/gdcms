<?php


class browse_tree extends tree
{
   var $exclude='';
   function browse_tree($category_id,$db,$table_prefix,$site_id)
   {
     $this->db=&$db;
     $this->name_id     ='category_id';
     $this->name_start  ='start';
     $this->name_finish ='finish';
     $this->name_deep   ='deep';
     $this->name_table  =$table_prefix.'category';
     $this->where[]     =" {$table_prefix}category.site_id={$site_id} ";
     $this->load_node($category_id);

     if($this->info)
     {
       $this->get_parents();
       $this->get_children();
       if(strlen($this->exclude)>0) $exclude='|'.$this->exclude; else $exclude='';
       //prn($_SERVER['REQUEST_URI']);
       $url_prefix=$_SERVER['REQUEST_URI'];
       $url_prefix=preg_replace("/\\?.*$/",'',$url_prefix);
       //prn($url_prefix);
       $url_prefix=$url_prefix."?".query_string('^category_id$'.$exclude);

       $cnt=array_keys($this->parents);
       foreach($cnt as $i) $this->parents[$i]=$this->adjust($this->parents[$i],$this->id,$url_prefix);

       $this->restrict_children();
       $cnt=array_keys(array_values($this->children));
       foreach($cnt as $i) $this->children[$i]=$this->adjust($this->children[$i],$this->id,$url_prefix);

       $this->info=$this->adjust($this->info,$this->id,$url_prefix);

     }
   }
   function adjust($_info,$category_id,$url_prefix)
   {
     $tor=$_info;
     $tor['category_title']  = get_langstring($tor['category_title']);
     $tor['category_description']=get_langstring($tor['category_description']);
     $tor['title_short']  = shorten($tor['category_title']);
     $tor['padding']=20*$tor['deep'];
     $tor['URL']="{$url_prefix}&category_id={$tor['category_id']}";
     $tor['has_subcategories']=($tor['finish']-$tor['start'] >1)?'>>>':'';
     return $tor;
   }

   function draw()
   {
      $category_selector='';

      if(count($this->children)==0 && count($this->parents)==0) return '';

      foreach($this->parents as $row)
      {
        $category_selector.="
           <div style=\"padding-left:{$row['padding']}px;\">
               <a href=\"{$row['URL']}\" title=\"{$row['category_title']}\" >{$row['title_short']}</a><br>
           </div>
        ";
      }

      $category_selector.="
      <div style=\"padding-left:{$this->info['padding']}px;\">
          <b><div title=\"{$this->info['category_title']}\">{$this->info['title_short']}</div></b>
          <div style='font-size:80%;'>{$this->info['category_description']}</div>
      </div>
      ";

      foreach($this->children as $row)
      {
        $category_selector.="
          <div style=\"padding-left:{$row['padding']}px;padding-bottom:3px;padding-top:2px; border:none;\">
              <a href=\"{$row['URL']}\" title=\"{$row['category_title']}\" >{$row['category_code']} {$row['title_short']} {$row['has_subcategories']}</a><br>
          </div>
        ";
      }
      return $category_selector;
   }

   function restrict_children()
   {
   }
}

?>
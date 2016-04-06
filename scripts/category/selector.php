<?php
/*
   Popup window 
   to select category in various forms  
*/

if(!is_librarian()) access_denied_page();

$main_template_name='popup';
run('lib/class_tree');
run('category/functions');



$category_root_ids=(isset($input_vars['category_root_ids']))?$input_vars['category_root_ids']:'';
if(strlen($category_root_ids)>0)
{
  $category_root_ids=explode(',',$category_root_ids);
  //prn($category_root_ids);
  $cnt=count($category_root_ids);
  for($i=0;$i<$cnt;$i++) $category_root_ids[$i]=(int)$category_root_ids[$i];
  $category_root_ids=join(',',array_unique($category_root_ids));
  $query="SELECT category_id, start, finish FROM {$table_prefix}category WHERE category_id IN($category_root_ids)";
  $category_roots=\e::db_getrows($query);
}else $category_roots=Array();



$this_category=new tree();
$this_category->db=&$db;
$this_category->name_id     ='category_id';
$this_category->name_start  ='start';
$this_category->name_finish ='finish';
$this_category->name_deep   ='deep';
$this_category->name_table  =$table_prefix.'category';

$this_category->load_node(isset($input_vars['category_id'])?( (int)$input_vars['category_id'] ):0,"{$table_prefix}category.is_visible=1");
$this_category->get_parents("{$table_prefix}category.is_visible=1");
$this_category->get_children("{$table_prefix}category.is_visible=1");
# prn($this_category);

#  ---------------------------- adjust nodes - begin ---------------------------
   function adj($_info,$method,$category_roots=Array())
   {
     $tor=$_info;

     $tor['is_visible']=$tor['is_active']=(count($category_roots)==0?1:0);
     $category_root_ids=Array();
     foreach($category_roots as $category_root)
     {
        $category_root_ids[]=$category_root['category_id'];
        if($tor['start']<=$category_root['start'] && $category_root['finish']<=$tor['finish'])
        {
          $tor['is_visible']=1;
          $tor['is_active']=0;
          break;
        }
        if($category_root['start']<$tor['start'] && $tor['finish']<$category_root['finish'])
        {
          $tor['is_visible']=1;
          $tor['is_active']=1;
          break;
        }
     }
     $category_root_ids=join(',',$category_root_ids);
     $tor['title_short']  = shorten($tor['category_title']);
     $tor['padding']=20*$tor['deep'];
     $tor['URL_open']   ="index.php?action=category/selector&method={$method}&category_id={$tor['category_id']}&category_root_ids=$category_root_ids";
     $tor['URL_select'] ="javascript:void(window.opener.document.{$method}({$tor['category_id']},'{$tor['title_short']}'))";
     $tor['has_subcategories']=($tor['finish']-$tor['start'] >1)?'...':'';

     return $tor;
   }

   $cnt=array_keys($this_category->parents);
   foreach($cnt as $i) $this_category->parents[$i]=adj($this_category->parents[$i],$input_vars['method'],$category_roots);

   $cnt=array_keys($this_category->children);
   foreach($cnt as $i)  $this_category->children[$i]=adj($this_category->children[$i],$input_vars['method'],$category_roots);


   $this_category->info=adj($this_category->info,$input_vars['method'],$category_roots);
#  ---------------------------- adjust nodes - end -----------------------------

#  ---------------------------- draw - begin -----------------------------------
  $input_vars['page']['title']   = 
  $input_vars['page']['header']  = "����� ���������";

  $input_vars['page']['content'] = process_template('category/selector',Array(
    'parents' =>$this_category->parents
   ,'children'=>$this_category->children
   ,'category'=>$this_category->info
   ,'method'=>$input_vars['method']
  ));
#  ---------------------------- draw - end -------------------------------------


# context menu
  $input_vars['page']['menu']['category']['items']=menu_category($this_category->info);


// remove from history
   nohistory($input_vars['action']);


?>
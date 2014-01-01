<?php

/*

Импорт категорий УДК из временной таблицы dl_tmp_udc_codes
в таблицу dl_category 

*/

// корневой узел дерева УДК
   $root_category_id = 3;
$main_template_name='';

/////////if(!is_librarian()) access_denied_page();

run('lib/class_tree');
run('category/functions');


$this_category=new tree();
$this_category->db=&$db;
$this_category->name_id     ='category_id';
$this_category->name_start  ='start';
$this_category->name_finish ='finish';
$this_category->name_deep   ='deep';
$this_category->name_table  =$table_prefix.'category';




function get_see_also_codes($str)
{
  $s=ereg_replace('\)|\(','',$str);
  $s=ereg_replace('\.+$','',$s);
  //$s=ereg_replace(':[0-9]+','',$s);

  if(!preg_match_all('/[-.0-9]+/',$s,$regs)) return '';
  $tor=Array();
  $regs=$regs[0];
  $cnt=count($regs);
  for($i=0;$i<$cnt;$i++)
  {
    if(ereg('^\.',$regs[$i]))
    {
       if(isset($regs[$i-1])) $tor[]=ereg_replace('\.[0-9]$','',$regs[$i-1]).$regs[$i];
       else $tor[]=ereg_replace('^\.','',$regs[$i]);
    }
    elseif(ereg('^-',$regs[$i]))
    {
       if(isset($regs[$i-1])) $tor[]=ereg_replace('-[0-9]$','',$regs[$i-1]).$regs[$i];
       else $tor[]=ereg_replace('^\.','',$regs[$i]);
    }
    else $tor[]=$regs[$i];
  }
  return 'codes:'.join(',',$tor);
}

#prn(get_see_also_codes('167/168'));
#prn(get_see_also_codes('341.16:001'));
#prn(get_see_also_codes('667.4 /.5'));
#prn(get_see_also_codes('667-4 /-5'));
#die('tmp-break');

set_time_limit(1200);
echo "
<html>
   <head>
     <meta http-equiv=\"Refresh\" content=\"30;URL=index.php?action=category/import\">
   </head>
<body>
";
for($row_counter=0;$row_counter<500;$row_counter++)
{

# read row from dl_tmp_udc_codes
  $query="SELECT * FROM {$table_prefix}tmp_udc_codes WHERE imported_successfully=0 ORDER BY code ASC LIMIT 0,1";
  $this_node_info=db_getonerow($query);
  #prn($this_node_info);  die();

# get parent node from imported categories
  $query="SELECT * FROM {$table_prefix}category WHERE LENGTH(category_code)>0 AND LOCATE(category_code,'".DbStr($this_node_info['code'])."')=1 ORDER BY category_code DESC LIMIT 0,1";
  $parent_node_info=db_getonerow($query);
  //prn(htmlencode($query),$parent_node_info); 

# load parent node info
  if($parent_node_info)
  {
    //prn('2222');
    $this_category->load_node($parent_node_info['category_id']);
  }
  else
  {
    //prn('4444');
    $this_category->load_node($root_category_id);
  }
  //prn($this_category);
  
  
  $child_id=$this_category->add_child();
  
# copy data to table
  $see_also=db_getrows("SELECT more FROM {$table_prefix}tmp_udc_codes WHERE code='".DbStr($this_node_info['code'])."'");
  $cnt=count($see_also);
  for($i=0;$i<$cnt;$i++) $see_also[$i]=$see_also[$i]['more'];
  $see_also=join(' <br> ',$see_also);

  $new_category_info=Array(
     'category_id'=>$child_id
    ,'category_code'=>$this_node_info['code']
    ,'category_title'=>shorten($this_node_info['title'],255)
    ,'category_description'=>$this_node_info['title'].' '.$this_node_info['more'].' '.$this_node_info['refs'].' '
    ,'is_part_of'=>$this_category->id
    ,'see_also'=>get_see_also_codes($see_also)
  );
  prn($new_category_info);

  $query="UPDATE {$table_prefix}category
          SET category_code='".DbStr($new_category_info['category_code'])."'
             ,category_title='".DbStr($new_category_info['category_title'])."'
             ,category_description='".DbStr($new_category_info['category_description'])."'
             ,is_part_of='".DbStr($new_category_info['is_part_of'])."'
             ,see_also='".DbStr($new_category_info['see_also'])."'
          WHERE category_id={$child_id}";
  db_getonerow($query);


# mark rows as imported
  db_getonerow("UPDATE {$table_prefix}tmp_udc_codes
                SET imported_successfully=1
                WHERE code='".DbStr($this_node_info['code'])."'");






}
echo "</body></html>";
?>
<?php


function menu_ec_category($_info=false)
{
  global $input_vars;
  $menu=Array();

  if($_info)
  {# ------------------------ selected menu - begin ---------------------
   # visible to all
     //if(is_librarian())     {
        $menu[]=Array(
                  'url'=>''
                 ,'html'=>"<b>".get_langstring($_info['ec_category_title'])." : </b>"
                 ,'attributes'=>''
        );

        $menu[]=Array(
                  'url'=>site_root_URL.'/index.php?action=ec/category/list&category_id='.$_info['ec_category_id']."&site_id={$_info['site_id']}"
                 ,'html'=>text('ec_category_open')
                 ,'attributes'=>''
        );

        $menu[]=Array(
                  'url'=>site_root_URL.'/index.php?action=ec/category/list&category_id='.$_info['ec_category_id'].'&add_child=yes'."&site_id={$_info['site_id']}"
                 ,'html'=>text('ec_category_create_child_category')
                 ,'attributes'=>''
        );

        $menu[]=Array(
                  'url'=>site_root_URL.'/index.php?action=ec/category/edit&ec_category_id='.$_info['ec_category_id']."&site_id={$_info['site_id']}"
                 ,'html'=>text('ec_category_edit')
                 ,'attributes'=>''
        );

        if($_info['start']>0)
        $menu[]=Array(
                  'url'=>site_root_URL.'/index.php?action=ec/category/list&category_delete=yes&category['.$_info['ec_category_id'].']='.$_info['ec_category_id']."&site_id={$_info['site_id']}"
                 ,'html'=>"<br>".text('ec_category_delete')
                 ,'attributes'=>' style="color:red;" onclick="return confirm(\''.str_replace('{ec_category_title}',$_info['ec_category_title'],text('ec_category_delete_confirmation')).'\')" '
        );
     //}
  }# ------------------------ selected menu - end -----------------------


  $cnt=count($menu);
  for($i=0;$i<$cnt;$i++)
  {
    $menu[$i]['innerHTML']=&$menu[$i]['html'];
    $menu[$i]['URL']=&$menu[$i]['url'];

  }
  return $menu;

}


function ec_adjust($_info,$category_id)
{
     $tor=$_info;
     $tor['context_menu'] = menu_ec_category($tor);
     unset($tor['context_menu']['start']);

     $tor['ec_category_title']=get_langstring($tor['ec_category_title']);

     $tor['title_short']  = shorten($tor['ec_category_title']);
     $tor['padding']=20*$tor['deep'];
     $tor['URL']="index.php?action=ec/category/list&category_id={$tor['ec_category_id']}&site_id={$_info['site_id']}";
     $tor['URL_move_up']  ="index.php?action=ec/category/list&category_id=$category_id&move_up={$tor['ec_category_id']}&site_id={$_info['site_id']}";
     $tor['URL_move_down']="index.php?action=ec/category/list&category_id=$category_id&move_down={$tor['ec_category_id']}&site_id={$_info['site_id']}";
     $tor['has_subcategories']=($tor['finish']-$tor['start'] >1)?'>>>':'';


     //prn('    tor= ',$tor);
     return $tor;
}



function move_down($parent_id,$category_id)
{
  global $db,$table_prefix;

  $query=Array(
		'BEGIN',
		"set @id=$category_id;",
		"select @start:=start, @finish:=finish,@deep:=deep, @site_id:=site_id
		 FROM {$table_prefix}ec_category where ec_category_id=@id;",
		// get nearest parent:
		"select @parent_id:=pa.ec_category_id, @parent_start:=pa.start,
		 @parent_finish:=pa.finish, @parent_deep:=pa.deep
		 from {$table_prefix}ec_category pa
		 where pa.site_id=@site_id and pa.start<@start
		   and @finish<pa.finish and pa.deep=(@deep-1)",

		// get nearest bottom sibling:
		"select @sibling_id:=ch.ec_category_id, @sibling_start:=ch.start,
		        @sibling_finish:=ch.finish, @sibling_deep:=ch.deep
		 from {$table_prefix}ec_category ch
		 where @parent_start<ch.start and ch.finish<@parent_finish
		   and ch.deep=@deep and ch.start > @finish
		   and ch.site_id=@site_id
		 order by start asc limit 0,1",

		// move down:
		"set @dt=@sibling_finish-@finish",
		"UPDATE {$table_prefix}ec_category
		 SET start=-start, finish=-finish
		 WHERE site_id=@site_id
		   AND @sibling_start<=start AND finish<=@sibling_finish",

		"UPDATE {$table_prefix}ec_category
		 SET start=start+@dt, finish=finish+@dt
		 WHERE site_id=@site_id AND @start<=start AND finish<=@finish",

		"set @dt=-(@start+@dt-@sibling_finish-1)",

		"UPDATE {$table_prefix}ec_category
		 SET start=abs(start+@dt), finish=abs(finish+@dt)
		 WHERE site_id=@site_id AND start<0 AND finish<0",
		'COMMIT' );
  //prn($query);
  foreach($query as $q) db_execute($q);
}


function move_up($parent_id,$category_id)
{
  global $table_prefix;

  $query=Array(
		'BEGIN',
		// load category properties
        "set @id=$category_id",
        "select @start:=start, @finish:=finish,@deep:=deep, @site_id:=site_id
		 FROM {$table_prefix}ec_category
		 where ec_category_id=@id",

        // get nearest parent:
        "select @parent_id:=pa.ec_category_id, @parent_start:=pa.start,
		        @parent_finish:=pa.finish, @parent_deep:=pa.deep
		 from {$table_prefix}ec_category pa
		 where pa.site_id=@site_id and pa.start<@start
		   and @finish<pa.finish   and pa.deep=(@deep-1)",

        // get nearest top sibling:
        "select @sibling_id:=ch.ec_category_id, @sibling_start:=ch.start,
		        @sibling_finish:=ch.finish, @sibling_deep:=ch.deep
		 from {$table_prefix}ec_category ch
		 where @parent_start<ch.start and ch.finish<@parent_finish
		   and ch.deep=@deep  and ch.finish < @start
		   and ch.site_id=@site_id
		 order by start desc limit 0,1",

        // move up:
        "set @dt=@start-@sibling_start",
        "UPDATE {$table_prefix}ec_category
		 SET start=-start, finish=-finish
		 WHERE site_id=@site_id AND @sibling_start<=start AND finish<=@sibling_finish",

        "UPDATE {$table_prefix}ec_category
		 SET start=start-@dt, finish=finish-@dt
		 WHERE site_id=@site_id AND @start<=start AND finish<=@finish",

        "SET @dt=@finish-@dt+1 - @sibling_start",
        "UPDATE {$table_prefix}ec_category
		 SET start=abs(start-@dt), finish=abs(finish-@dt)
		 WHERE site_id=@site_id AND start<0 AND finish<0",
		'COMMIT'
  );
  //prn($query);
  foreach($query as $q)
  {
  	 //prn(checkStr($q));
  	 db_execute($q);
  }

}
?>
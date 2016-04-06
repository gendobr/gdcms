<?php

/**

 */

$ids=isset($input_vars['ids'])?explode(',',$input_vars['ids']):Array();
if(count($ids)==0){
    $tmp = \e::db_getrows("SELECT id FROM {$table_prefix}calendar");
    $ids=Array();
    foreach($tmp as $tm){
        $ids[]=$tm['id'];
    }
    if(count($ids)==0){
        exit('Events not found');
    }
}

run('calendar/functions');
$id=array_shift($ids);
event_recache_days($id);





$input_vars['page_content']="
  Event $id - OK  (". count($ids)." left)
<form action=index.php id='autoform' method=post>
<input type=hidden name=action value='calendar/tmp_recache_days'>
<input type=hidden name=ids value='".join(',',$ids)."'>   
</form>
";
if(count($ids)>0){
    $input_vars['page_content'].=    "
    <script type='application/javascript'>
       \$(window).load(function(){
          \$('#autoform').submit();
       });
    </script>
    ";
}else{
    $input_vars['page_content'].=    "<br><a href='index.php?action=calendar/tmp_recache_days'>recache again</a>";
}
$input_vars['page_title']=$input_vars['page_header']="Recaching event days";
<?php
/*
  List of news for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/
run('site/menu');


$debug=false;
//------------------- site info - begin ----------------------------------------
$site_id = checkInt($input_vars['site_id']);
$this_site_info = get_site_info($site_id);

// prn($this_site_info);
if(checkInt($this_site_info['id'])<=0) {
    $input_vars['page_title']   =
    $input_vars['page_header']  =
    $input_vars['page_content'] = $text['Site_not_found'];
    return 0;
}
//------------------- site info - end ------------------------------------------

//------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0) {
    $input_vars['page_title']  =
    $input_vars['page_header'] =
    $input_vars['page_content']= $text['Access_denied'];
    return 0;
}
//------------------- check permission - end -----------------------------------


// ------------------ update poll ordering - begin -----------------------------
   if(isset($input_vars['ordering']) && is_array($input_vars['ordering'])){
      foreach($input_vars['ordering'] as $key=>$val){
          $query="UPDATE {$table_prefix}golos_pynannja
                  SET ordering=".( (int)$val )."
                  WHERE site_id=$site_id AND id=".( (int)$key );
          \e::db_execute($query);
      }
   }
// ------------------ update poll ordering - end -------------------------------

run('poll/functions');


$page_content="
      <style>
      .menu_block{
        position:absolute;
        background-color:#e0e0e0;
        border:1px solid blue;
        padding:10px;
      }
      </style>
      <script type=\"text/javascript\">
      <!--
        var report_prev_menu;
        var report_href;
        function report_change_state(cid)
        {
            var lay=document.getElementById(cid);
            if (lay.style.display==\"none\")
            {
               if(report_prev_menu) report_prev_menu.style.display=\"none\";
               lay.style.display=\"block\";
               report_prev_menu=lay;
            }
            else
            {
               lay.style.display=\"none\";
               report_prev_menu=null;
            }
            report_href=true;
        }
        
        function report_hide_menu()
        {
          if(report_prev_menu && !report_href) report_prev_menu.style.display=\"none\";
          report_href=false;
        }
        document.onclick=report_hide_menu;
      // -->
      </script>
";

//$page_content.="<h4>������ ��������� ���������</h4>";
$page_content.="
<style type=text/css>
.ordering{background-image:url(img/ordering_arrows.gif);background-position:center right;background-repeat:no-repeat;}
</style>
<form action=index.php method=post>
<table width=100% border=1>
";

$page_content.="<tr><td></td><td></td><td><b><a href=index.php?action=poll/edit&site_id={$site_id}>{$text['Polls_create']}</a></b></td></tr>";

$start=(isset($input_vars['start']))?abs(round(1*$input_vars['start'])):0;

$result =\e::db_getrows("SELECT * FROM {$table_prefix}golos_pynannja WHERE site_id={$site_id} ORDER BY `ordering` ASC LIMIT $start, 100");
$row_id=0;
foreach ($result as $row) {
    $row_id++;
    $row['context_menu']=menu_poll($row);

    //--------------------------- context menu - begin ----------------------
    $context_menu='';
    if(is_array($row['context_menu'])) {
        $context_menu.="<img src=\"img/context_menu.gif\" border=1px alt=\"\" onclick=\"report_change_state('cm{$row_id}')\">
       <div class=menu_block style='display:none;' id='cm{$row_id}'>";
        foreach($row['context_menu'] as $menu_item) {
            $context_menu.="<nobr><a href=\"{$menu_item['URL']}\" {$menu_item['attributes']}>{$menu_item['innerHTML']}</a></nobr><br/>\n";
        }
        $context_menu.="</div>";
    }
    //--------------------------- context menu - end ------------------------

    $bgcolor=$row['is_active']==1?'':'color:gray';
    $page_content.="
              <tr>
                 <td width=5%>{$context_menu}</td>
                 <td width=5%><input type=text class=ordering size=4 name=ordering[{$row['id']}] value={$row['ordering']}></td>
                 <td><b><a style='$bgcolor;' href=index.php?action=poll/stats&poll_id={$row['id']}&site_id={$row['site_id']} target=_blank>{$row['title']}</a></b></td>
              </tr>
    ";
}

$page_content.="<tr><td></td><td align=center><input type=submit value=OK></td><td></td></tr>";
$page_content.="
    </table>
    <input type=hidden name=action value=poll/list>
    <input type=hidden name=site_id value=\"{$site_id}\">
    </form>";


$result1 = \e::db_getonerow("SELECT count(*) as n FROM {$table_prefix}golos_pynannja WHERE site_id={$site_id}");
$num = $result1['n'];
$pages='';
$url_prefix=site_root_URL.'/index.php?'.query_string("ordering|start").'&start=';
for($i=0;$i<$num; $i=$i+100) {
    $pages.="<a href=$url_prefix{$i}>".(1+$i/100)."</a> ";
}








$input_vars['page_title']  = 
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['Polls_manage'];

$input_vars['page_content']= $page_content;

//--------------------------- context menu -- begin ----------------------------

$sti=$text['Site'].' "'. $this_site_info['title'].'"';
$Site_menu="<span title=\"".checkStr($sti)."\">".shorten($sti,30)."</span>";
$input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
$input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------

?>
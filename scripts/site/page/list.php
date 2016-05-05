<?php
/*
  List of pages for the site
  Argument is $site_id - site identifier
  (c) Gennadiy Dobrovolsky gen_dobr@hotmail.com
*/


run('site/menu');
run('site/page/menu');

# ------------------- old site info - begin ------------------------------------
  $site_id = checkInt($input_vars['site_id']);
  $this_site_info = get_site_info($site_id);
  // prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  {
     $input_vars['page_title']   =
     $input_vars['page_header']  =
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
# ------------------- old site info - end --------------------------------------

# ------------------- check permission - begin ---------------------------------
if(get_level($site_id)==0)
{
   $input_vars['page_title']  =
   $input_vars['page_header'] =
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
# ------------------- check permission - end -----------------------------------

//-------------------- delete page - begin -------------------------------------
  if(isset($input_vars['delete_page_id']))
  {
    $delete_page_id=checkInt($input_vars['delete_page_id']);
    $delete_page_info=get_page_info($delete_page_id,\e::db_escape($input_vars['delete_page_lang']));

    # --------------------- delete from DB - begin -----------------------------
      $query="DELETE FROM <<tp>>page
              WHERE id      = {$delete_page_info['id']}
                AND lang    ='{$delete_page_info['lang']}'
                AND site_id = {$delete_page_info['site_id']}";
    # prn($query);
      \e::db_execute($query);

      $query="DELETE FROM <<tp>>page_menu_group
              WHERE page_id = {$delete_page_info['id']}
                AND lang    ='{$delete_page_info['lang']}'
                AND site_id = {$delete_page_info['site_id']}";
    # prn($query);
      \e::db_execute($query);
    # --------------------- delete from DB - end -------------------------------

    # --------------------- delete exported page - begin -----------------------
      $site_root_dir = \e::config('SITES_ROOT').'/'.$this_site_info['dir'];
      \core\fileutils::path_delete($site_root_dir,$delete_page_info['file']);
      if($delete_page_info['file2']) {
          //prn("path_delete($site_root_dir,{$delete_page_info['file2']})");
          \core\fileutils::path_delete($site_root_dir,$delete_page_info['file2']);
      }
    # --------------------- delete exported page - end -------------------------
    clear('delete_page_id','delete_page_lang');
  }

//-------------------- delete page - end ---------------------------------------



//--------------------------- get list -- begin --------------------------------
  run("lib/class_report");
  run("lib/class_report_extended");
  $re=new report_generator;
  $re->db=$db;
  $re->distinct=false;

  $re->from="<<tp>>page AS page
             LEFT JOIN
             <<tp>>category as category
             ON (page.site_id=category.site_id
                 AND page.category_id=category.category_id)";

  $re->add_where(" page.site_id={$site_id} ");

  $re->add_field( $field='page.id'
                 ,$alias='id'
                 ,$type ='id:hidden=no'
                 ,$label='#'//'<span style="font-size:80%;">'.wordwrap($text['Page_id'], 5, "\n", 1).'</span>'
                 ,$_group_operation=false);

  //---------------- list of languages - begin ---------------------------------
    $LL = join('&',\e::db_get_associated_array("SELECT lang,CONCAT(lang,'=',lang) FROM <<tp>>page WHERE site_id={$site_id}"));
    $re->add_field( $field='page.lang'
                   ,$alias='lang'
                   ,$type ='enum:'.$LL
                   ,$label=$text['Language']
                   ,$_group_operation=false);
  //---------------- list of languages - end -----------------------------------

  $re->add_field( $field='page.is_home_page'
                 ,$alias='is_home_page'
                 ,$type ="enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 ,$label=$text['Home_page']
                 ,$_group_operation=false);

  $re->add_field( $field='page.site_id'
                 ,$alias='site_id'
                 ,$type ='id:hidden=yes'
                 ,$label=$text['Site_id']
                 ,$_group_operation=false);

  $re->add_field( $field='page.path'
                 ,$alias='path'
                 ,$type ='string'
                 ,$label=$text['Page_Path']
                 ,$_group_operation=false);

  $re->add_field( $field='page.title'
                 ,$alias='title'
                 ,$type ='string'
                 ,$label=$text['Page_title']
                 ,$_group_operation=false);

  $re->add_field( $field="page.cense_level>={$this_site_info['cense_level']}"
                 ,$alias='is_under_construction'
                 ,$type ="enum:1={$text['positive_answer']}&0={$text['negative_answer']}"
                 ,$label=$text['Published']
                 ,$_group_operation=false);


 # ------------------------ list of categories - begin -------------------------
    $query="SELECT category_id, category_title, deep FROM <<tp>>category WHERE start>0 AND site_id={$site_id} ORDER BY start ASC";
    $tmp=\e::db_getrows($query);
    $list_of_categories=Array();
    foreach($tmp as $tm) $list_of_categories[]=$tm['category_id'].'='.rawurlencode(str_repeat(' + ',$tm['deep']).get_langstring($tm['category_title']));
    unset($tmp,$tm);
    $list_of_categories=join('&',$list_of_categories);
    //prn($list_of_categories);
 # ------------------------ list of categories - end ---------------------------

  $re->add_field( $field='page.category_id'
                 ,$alias='category_id'
                 ,$type ='enum:'.$list_of_categories
                 ,$label=$text['Page_Category']
                 ,$_group_operation=false);

  unset($field,$alias,$type,$label, $_group_operation);
  //prn($re->create_query());
  $response=$re->show();
  //prn($response);

//--------------------------- get list -- end ----------------------------------

$input_vars['page_title']  = $this_site_info['title'] .' - '. $text['List_of_pages'];
$input_vars['page_header'] = $this_site_info['title'] .' - '. $text['List_of_pages'];

  //--------------------------- context menu -- begin ----------------------------
    $cnt=count($response['rows']);
    for($i=0;$i<$cnt;$i++)
    {
      //--------------------------- context menu -- begin ------------------------
        $response['rows'][$i]['context_menu']=menu_page($response['rows'][$i]);
      //--------------------------- context menu -- end --------------------------
    }
  //--------------------------- context menu -- end ------------------------------

$input_vars['page_content']= $re->draw_default_list($response);

//--------------------------- context menu -- begin ----------------------------

  $sti=$text['Site'].' "'. $this_site_info['title'].'"';
  $Site_menu="<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>";
  $input_vars['page_menu']['site']=Array('title'=>$Site_menu,'items'=>Array());
  $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//--------------------------- context menu -- end ------------------------------
?>
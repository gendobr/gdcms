<?php
# url of this page is
# index.php?action=site/search&site_id={$site_id}

$link = $db;
$data=date ("Y-m-d H:i");

run('site/page/page_view_functions');
run('site/menu');

if(isset($input_vars['interface_lang']))
   if($input_vars['interface_lang'])
      $input_vars['lang']=$input_vars['interface_lang'];


$input_vars['lang']=$_SESSION['lang'];

$lang = get_language('lang');

$txt=load_msg($input_vars['lang']);



//------------------- main site info - begin ------------------------------------
  $list_of_sites=explode(',',$input_vars['site_id']);
  $list_of_sites=array_map('checkInt',$list_of_sites);
  $site_id = abs((int)$list_of_sites[0]);
  $this_site_info = get_site_info($site_id,$input_vars['lang']);

 # prn($this_site_info);
  if($this_site_info['id']<=0) die($txt['Site_not_found']);
  $list_of_sites=array_unique($list_of_sites);
//------------------- main site info - end --------------------------------------
//--------------------------- get site template - begin ------------------------
  $custom_page_template = sites_root.'/'.$this_site_info['dir'].'/template_index.html';
  #prn('$news_template',$news_template);
  if(is_file($custom_page_template)) $this_site_info['template']=$custom_page_template;
//--------------------------- get site template - end --------------------------



if(isset($input_vars['keywords'])){
  $vyvid = "<h4>{$txt['Search']}&quot;".checkStr($input_vars['keywords'])."&quot;</h4>";
}
else{
  $vyvid = "
    <div align=center>
    <form action=\"".url_prefix_search."lang={$input_vars['lang']}&site_id=".join(',',$list_of_sites)."\" method=post>
     <INPUT type=text NAME=keywords SIZE=40 value='".checkStr(isset($input_vars['keywords'])?$input_vars['keywords']:'')."'>
     <input type=submit value='{$txt['Search']}'>
    </form>
    </div>
";
}


//----------------------------- do search - begin ------------------------------
if(isset($input_vars['start'])) $start=abs(round(1*$input_vars['start'])); else $start=0;
$keywords= isset($input_vars['keywords'])?DbStr($input_vars['keywords']):'';

$delim='';
$relev='';
$havin='';
$ord  ='';
$grp  ='';

if(strlen($keywords)>0){
   run('lib/search_functions');
  // remove common words
     $keywords = trim(to_lower_case(remove_common_words(' '.$keywords.' ')));
     echo '<!-- '.$keywords.' -->';

    //---------------------------- create query - begin ------------------------
      $kw=explode(' ',$keywords);
      $show_words=Array();
      if(is_array($kw)){
        foreach($kw as $word){
          if(strlen($word)>1){
            $relev.=" $delim (LOCATE('".DbStr($word)."', si.words )>0) ";
            $delim=' + ';
            $show_words[]=$word;
          }
        }

        if(strlen($relev)>0) {
          $relev=" ,SUM($relev) AS rel ";
          $havin=" HAVING rel>=".count($show_words);
          $ord  =" ORDER BY rel DESC ";
          $grp  =" GROUP BY si.id ";

        # --------------- do search - begin ------------------------------------
          $start =isset($_REQUEST['start'])?(int)$_REQUEST['start']:0;
          $query="SELECT SQL_CALC_FOUND_ROWS DISTINCT
                  s.title AS site_title, si.*
                  $relev
                  FROM {$table_prefix}site_search As si inner join  {$table_prefix}site AS s ON s.id=si.site_id
                  where s.id IN(".join(',',$list_of_sites).")
                  $grp
                  $havin
                  $ord
                  LIMIT $start,".rows_per_page;
          //echo "<!-- $query -->";
          #prn(checkStr($query));
          $search_result=db_getrows($query);
          // prn($search_result);
          //--------------------- adjust search results - begin ----------------
            $cnt=array_keys($search_result);
            foreach($cnt as $key) {
              $search_result[$key]['site_title']=get_langstring($search_result[$key]['site_title']);
              $search_result[$key]['show_words']=Array();

              foreach($show_words as $word) {
                 $pos = strpos($search_result[$key]['words'], $word);
                 if ($pos === false) continue;
                 $search_result[$key]['show_words'][]='<b>'.$word.'</b>';
              }
              $search_result[$key]['show_words']=join(' ... ',$search_result[$key]['show_words']);
            }
          //--------------------- adjust search results - end ------------------

          #prn($search_result);

          // --------------------- number of pages - begin ---------------------
          $query="SELECT FOUND_ROWS() AS n_records";
          $num_rows=db_getonerow($query);
          //var_dump($num_rows);
          $num_rows=$num_rows['n_records'];
          #prn('$num_rows='.$num_rows);
          $pages=Array();
          for($i=0;$i<$num_rows; $i=$i+rows_per_page) {
              if( $i==$start ) {
                 $to='<b>['.(1+$i/rows_per_page).']</b>'; 
              }else{ 
                  $to=(1+$i/rows_per_page);
              }
              $pages[]=Array(
                 'URL'=>sites_root_URL."/search.php?start={$i}&".query_string('^start$|^'.session_name().'$|^action$')
                ,'innerHTML'=>$to
              );
          }
          //var_dump($pages);
          // --------------------- number of pages - end -----------------------

        # --------------- do search - end --------------------------------------
        }
      }



}

if(!isset($search_result)){
    $pages=Array();
    $search_result=Array();
    $num_rows=0;
}
//----------------------------- do search - end --------------------------------


  // -------------------------- draw - begin -----------------------------------
    $search_template = sites_root.'/'.$this_site_info['dir'].'/template_search_results.html';
    #prn('$search_template',$search_template);
    if(!is_file($search_template)) $search_template = 'cms/template_search_results';

    #prn('$search_template',$search_template);
    $vyvid=process_template( $search_template
                                  ,Array(
                                    'paging_links'=>$pages
                                   ,'text'=>$txt
                                   ,'search_result'=>$search_result
                                   ,'urls_found' => $num_rows
                                   ,'form_keywords'=>checkStr(isset($input_vars['keywords'])?$input_vars['keywords']:'')
                                   ,'form_action'=>url_prefix_search
                                   ,'form_site_id'=>join(',',$list_of_sites)
                                   ,'form_lang'=>checkStr($input_vars['lang'])
                                  ));
  // -------------------------- draw - end -------------------------------------


$menu_groups = get_menu_items($this_site_info['id'],0,$input_vars['lang']);
#prn($this_site_info['id'],0,$input_vars['lang'],$menu_groups);

//------------------------ get list of languages - begin -----------------------
  $lang_list=list_of_languages();
  $cnt=count($lang_list);
  //prn($lang_list);
  for($i=0;$i<$cnt;$i++) {
     //$lang_list[$i]['url']=$lang_list[$i]['href'];
     //$lang_list[$i]['url']=str_replace('action=site%2Fsearch','',$lang_list[$i]['url']);
     //$lang_list[$i]['url']=str_replace('index.php','search.php',$lang_list[$i]['url']);
     //$lang_list[$i]['url']=str_replace(site_root_URL,sites_root_URL,$lang_list[$i]['url']);
     //$lang_list[$i]['url']=str_replace('?&','?',$lang_list[$i]['url']);
     //$lang_list[$i]['url']=str_replace('&&','&',$lang_list[$i]['url']);

     $lang_list[$i]['url']=url_prefix_search."interface_lang={$lang_list[$i]['name']}&lang={$lang_list[$i]['name']}&site_id=".join(',',$list_of_sites)."&keywords=".rawurlencode(isset($input_vars['keywords'])?$input_vars['keywords']:'');
     $lang_list[$i]['lang']=$lang_list[$i]['name'];
  }
  //prn($lang_list);
//------------------------ get list of languages - end -------------------------
//------------------------ draw using SMARTY template - begin ----------------

  $file_content=process_template($this_site_info['template']
                                ,Array(
                                  'page'=>Array(
                                      'title'=>$txt['Site_search']
                                     ,'content'=> $vyvid
                                     ,'abstract'=> ''//$txt['search_manual']
                                     ,'site_id'=>$site_id
                                     ,'lang'=>$input_vars['lang']
                                   )
                                 ,'lang'=>$lang_list
                                 ,'site'=>$this_site_info
                                 ,'menu'=>$menu_groups
                                 ,'site_root_url'=>site_root_URL
                                 ,'text'=>$txt
                                ));
//------------------------ draw using SMARTY template - end ------------------
echo $file_content;

global $main_template_name; $main_template_name='';
?>
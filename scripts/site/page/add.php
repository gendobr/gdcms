<?php
/*
  Adding page to selected site
  Argument is $site_id  - site identifier, integer, mandatory

  (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/

if(isset($input_vars['sim'])) { prn(base64_decode($input_vars['return'])); exit(); }
run('site/menu');
run('site/page/menu');

// remove from history
   nohistory($input_vars['action']);


# ------------------- get site info - begin ------------------------------------
  $site_id = (int)$input_vars['site_id'];
  $this_site_info = get_site_info($site_id);
  # prn($this_site_info);
  if(!$this_site_info['id'])
  {
     $input_vars['page_title']   = 
     $input_vars['page_header']  = 
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
# ------------------- get site info - end --------------------------------------

# ------------------- check permission - begin ---------------------------------
$user_level = get_level($site_id);
if($user_level==0)
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
# ------------------- check permission - end -----------------------------------

# ------------------- page info (optional) - begin -----------------------------
  $page_id=isset($input_vars['page_id'])?((int)$input_vars['page_id']):0;
  $this_page_info=\e::db_getonerow("SELECT * FROM <<tp>>page WHERE id={$page_id} AND site_id={$site_id}");
  $this_page_info['id'] = checkInt($this_page_info['id']);
  //prn('$this_page_info',$this_page_info);
# ------------------- page info (optional) - end -------------------------------

# -------------------- add page - begin ----------------------------------------
  if($this_page_info['id']>0)
  {
    // add the same page in another language
    
    //-------------------- get existing page languages - begin -----------------
      $query="SELECT lang FROM <<tp>>page WHERE id={$page_id}";
      $tmp=\e::db_getrows($query);
      // prn($tmp);
      $existins_langs=Array(0=>'');
      foreach($tmp as $lng) $existins_langs[]=$lng['lang'];
    //-------------------- get existing page languages - end -------------------

    //-------------------- get available languages - begin ---------------------
      $query="SELECT id FROM <<tp>>languages WHERE is_visible=1 AND id NOT IN('".join("','",$existins_langs)."') LIMIT 0,1";
      // prn($query);
      $tmp=\e::db_getonerow($query);
      // prn($tmp);
    //-------------------- get available languages - end -----------------------
    if(strlen($tmp['id'])>0)
    {
      $query = "INSERT INTO <<tp>>page(id, lang, site_id, title, cense_level, last_change_date, is_under_construction,path	)
                values($page_id, '{$tmp['id']}', $site_id, '{$text['New_page']}', {$user_level}, NOW(), 1,''	)";
      // prn($query);
      \e::db_execute($query);
    }
  }
  else
  {
    // create new page
    $query = "SELECT max(id) AS newid FROM <<tp>>page";
    $newid=\e::db_getonerow($query);
    $newid=1+(int)$newid['newid'];

    $query = "INSERT INTO <<tp>>page(id, lang, site_id, title, cense_level, last_change_date, is_under_construction,path	)
              values($newid, '".\e::config('default_language')."', $site_id, '{$text['New_page']}', {$user_level}, NOW(), 1,	'')";
    \e::db_execute($query);
  }
# -------------------- add page - end ------------------------------------------

if(isset($input_vars['return']))
{

    header("Location: ".base64_decode($input_vars['return']));
}
else
{
    header("Location: index.php?action=site/page/list&orderby=id+desc&site_id={$site_id}&".query_string('^page_id$|^site_id$|^action$|^'.session_name().'$'));
}
exit;

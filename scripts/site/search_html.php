<?php

$GLOBALS['main_template_name']='';

//---------------------- load language - begin ---------------------------------
if(isset($input_vars['interface_lang'])) if(strlen($input_vars['interface_lang'])>0) $input_vars['lang']=$input_vars['interface_lang'];
if(!isset($input_vars['lang'])) $input_vars['lang']=$_SESSION['lang'];
if(strlen($input_vars['lang'])==0) $input_vars['lang']=$_SESSION['lang'];
if(strlen($input_vars['lang'])==0) $input_vars['lang']=default_language;

$txt = load_msg($input_vars['lang']);
//---------------------- load language - end -----------------------------------
// $lang=$input_vars['lang'];
$lang = get_language('lang');


run('site/menu');
//------------------- site info - begin ----------------------------------------
  if(isset($input_vars['site_id']))
  {
     $site=$site_id = checkInt($input_vars['site_id']);
  }
  elseif(isset($input_vars['site_id']))
  {
     $site=$site_id = checkInt($input_vars['site_id']);
  }
  $this_site_info = get_site_info($site,$lang);

  if(checkInt($this_site_info['id'])<=0)  die($txt['Site_not_found']);
//------------------- site info - end ------------------------------------------



$vyvid="
<form action=search.php method=post>
<input type=hidden name=site_id value='{$this_site_info['id']}'>
<input type=hidden name=lang value='{$input_vars['lang']}'>
<INPUT type=text NAME=keywords SIZE=40 value=''>
<input type=submit value='{$txt['Search']}'>
</form>
";

$vyvid=checkStr($vyvid);

echo "
<div id=toinsert>
$vyvid
</div>

"
;

// remove from history
   nohistory($input_vars['action']);


?>
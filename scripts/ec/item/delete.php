<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

run('site/image/url_replacer');
run('ec/item/functions');
run('site/menu');

# ------------------- check ec_item_id - begin ---------------------------------
  $ec_item_id=0;
  if(isset($input_vars['ec_item_id']))
  {
     $ec_item_id   = (int)$input_vars['ec_item_id'];
     // $ec_item_lang = DbStr($input_vars['ec_item_lang']);
     $ec_item_lang=get_language('ec_item_lang');

     //prn($ec_item_id,$ec_item_lang);;
     $this_ec_item_info=get_ec_item_info($ec_item_id,$ec_item_lang);
     if(!$this_ec_item_info) $ec_item_id=0;
  }
  if($ec_item_id==0)
  {
      $this_ec_item_info=get_ec_item_info(0,(isset($input_vars['ec_item_lang'])?$input_vars['ec_item_lang']:default_language),((int)(isset($input_vars['site_id'])?$input_vars['site_id']:0)));
  }
  //prn('$ec_item_id='.$ec_item_id);
  //prn('$this_ec_item_info',$this_ec_item_info);
# ------------------- check ec_item_id - end -----------------------------------


# ------------------- get site info - begin ------------------------------------
  if($ec_item_id>0) $site_id=$this_ec_item_info['site_id'];
  else $site_id=(int)(isset($input_vars['site_id'])?$input_vars['site_id']:0);
  $this_site_info = get_site_info($site_id);
  //prn('$this_site_info=',$this_site_info);
  if($this_site_info) $this_ec_item_info['site_id']=$site_id;
# ------------------- get site info - end --------------------------------------

# ------------------- get permission - begin -----------------------------------
  $user_cense_level=get_level($site_id);
  if($user_cense_level<=0)
  {
     $input_vars['page_title']  =$text['Access_denied'];
     $input_vars['page_header'] =$text['Access_denied'];
     $input_vars['page_content']=$text['Access_denied'];
     return 0;
  }
# ------------------- get permission - end -------------------------------------
//prn($ec_item_id,$ec_item_lang);
ec_item_delete($ec_item_id,$ec_item_lang);

$main_template_name='';
?>

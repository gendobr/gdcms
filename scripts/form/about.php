<?php

  run('site/menu');
//------------------- old site info - begin ------------------------------------
  global $this_site_info;
  $site_id = (int)$input_vars['site_id'];
  $this_site_info = get_site_info($site_id);


  //prn($this_site_info);
  if(checkInt($this_site_info['id'])<=0)
  if(!is_admin())
  {
     $input_vars['page_title']   =
     $input_vars['page_header']  =
     $input_vars['page_content'] = $text['Site_not_found'];
     return 0;
  }
  $GLOBALS['this_site_info']=$this_site_info;
//------------------- old site info - end --------------------------------------

//------------------- check permission - begin ---------------------------------
if(($this_site_info['admin_level']=get_level($site_id))==0 && !is_admin())
{
   $input_vars['page_title']  = $text['Access_denied'];
   $input_vars['page_header'] = $text['Access_denied'];
   $input_vars['page_content']= $text['Access_denied'];
   return 0;
}
//------------------- check permission - end -----------------------------------

$block_id='form'.time();

  $input_vars['page_title']   = $text['Email_form'];
  $input_vars['page_header']  = $text['Email_form'];
  $input_vars['page_content'] = "
  <script>
     function setval(id,val)
     {
         var el=document.getElementById(id);
         if(el) el.innerHTML=val;
         var el1=document.getElementById(id+'1');
         if(el1) el1.innerHTML=val;
     }
  </script>
  <p>
  <div style='padding:4pt;background-color:#ffffa0;color:black;'>
      ".text('form_parameters').":
      <div>
       ".text('form_language')." :
        <input type=radio name=lang value=ukr onclick=\"setval('lang','ukr')\"> ukr&nbsp;&nbsp;&nbsp;
        <input type=radio name=lang value=rus onclick=\"setval('lang','rus')\"> rus&nbsp;&nbsp;&nbsp;
        <input type=radio name=lang value=eng onclick=\"setval('lang','eng')\"> eng&nbsp;&nbsp;&nbsp;
      </div>
      <div>
       ".text('form_files').":<br>
        ukr:<input type=text onkeyup=\"setval('fileukr',this.value)\">&nbsp;&nbsp;&nbsp;
        rus:<input type=text onkeyup=\"setval('filerus',this.value)\">&nbsp;&nbsp;&nbsp;
        eng:<input type=text onkeyup=\"setval('fileeng',this.value)\">
      </div>
  </div>
  ".text('form_html_code').":
  <div style='border:1px solid gray;background-color:#e0e0e0;padding:10px;'>
  &lt;a href=\"".
          str_replace(
                  [
                      '{lang}',  rawurlencode('{lang}'),
                      '{fileukr}',  rawurlencode('{fileukr}'),
                      '{filerus}',  rawurlencode('{filerus}'),
                      '{fileeng}',  rawurlencode('{fileeng}'),
                  ],[
                      '<b id=lang>'.$_SESSION['lang'].'</b>','<b id=lang>'.$_SESSION['lang'].'</b>',
                      '<b id=fileukr>filename</b>','<b id=fileukr>filename</b>',
                      '<b id=filerus>filename</b>','<b id=filerus>filename</b>',
                      '<b id=fileeng>filename</b>','<b id=fileeng>filename</b>',
                  ],
                  \e::url_public([
                      'action'=>'form/view', 'site_id'=>$this_site_info['id'],
                      'lang'=>'{lang}', 'form[ukr]'=>'{fileukr}',
                      'form[rus]'=>'{filerus}','form[eng]'=>'{fileeng}'
                  ])
          )."\"&gt;".text('form_send_email')."&lt;/a&gt;
  </div>
".text('form_widget_code')."
<div style='border:1px solid gray;background-color:#e0e0e0;padding:10px;'>
  &lt;div id=\"{$block_id}\">&lt;/div>
  &lt;script type=\"text/javascript\" src=\"" . \e::url_public('scripts/lib/ajax_loadblock.js') . "\">&lt;/script>
  &lt;script type=\"text/javascript\">
      ajax_loadblock(\"{$block_id}\",\""
        .
              
              
              site_public_URL
        ."/index.php?action=form/view&site_id={$this_site_info['id']}"
        ."&lang=<b id=lang1>ukr</b>&form[ukr]=<b id=fileukr1>filename</b>"
        ."&form[rus]=<b id=filerus1>filename</b>&form[eng]=<b id=fileeng1>filename</b>&widget=1"
        ."\",null);
  &lt;/script>
</div>
  
  </p>
  <p>
  ".text('form_lang_tip')."
  </p>
  <p>
  ".text('form_filename_tip')."
  
  <div style='border:1px solid gray;background-color:#e0e0e0;padding:10px;'>
  &lt;a href=\"".site_public_URL."/index.php?action=form/view&site_id={$this_site_info['id']}&lang=ukr&form=dir/form.txt\"&gt;".text('form_send_email')."&lt;/a&gt;
  </div>
  ".text('form_link_address')."
  <div style='border:1px solid gray;background-color:#e0e0e0;padding:10px;'>
  ".site_public_URL."/index.php?action=form/view&site_id={$this_site_info['id']}&lang=ukr&form=dir/form.txt
  </div>
  </p>

  ".text('form_file_manual')."


  ";
//----------------------------- draw -- end ------------------------------------

//----------------------------- site context menu - begin ----------------------
    $sti=$text['Site'].' "'. $this_site_info['title'].'"';
    $input_vars['page_menu']['site']=Array('title'=>"<span title=\"".htmlspecialchars($sti)."\">".shorten($sti,30)."</span>",'items'=>Array());
    $input_vars['page_menu']['site']['items'] = menu_site($this_site_info);
//----------------------------- site context menu - end ------------------------

?>
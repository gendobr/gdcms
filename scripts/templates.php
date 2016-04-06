<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
  $input_vars['page_title']   = text('Site properties');
  $input_vars['page_header']  = text('Site properties');

  run('lib/file_functions');

  $fl=ls(\e::config('TEMPLATE_ROOT').'/cms');
  $fl=$fl['files'];
  sort($fl);
  //prn($fl['files']);

  $input_vars['page_content'] = "
     <h3>".text('Sample_templates')."</h3>
     <ol>
  ";
  foreach($fl as $fname){
      $input_vars['page_content'].= "<li><a href=templates/cms/$fname target=_blank>{$fname} - ".text(str_replace('.html','',$fname))."</a></li>";
  }
  $input_vars['page_content'].= "</ol>
  ";

?>

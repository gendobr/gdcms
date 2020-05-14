<?php
/*
 Draw default page
 Requires
   $input_vars['page_title']   - title of the page, string
   $input_vars['page_header']  - header of the page, string
   $input_vars['page_menu']    - navigation menu, array
   $input_vars['page_content'] - main content of the page, string

 (c) Gennadiy Dobrovolsky, gen_dobr@hotmail.com
*/
?><html>
<head>
<META content="text/html; charset=<?=site_charset?>" http-equiv=Content-Type>
<link rel="stylesheet" href="img/styles.css" type="text/css">
<title><?=$input_vars['page_title']?></title>

<script language="javascript" type="text/javascript" src="scripts/lib/jquery.min.js"></script>
<script language="javascript" type="text/javascript" src="scripts/lib/jquery-ui.min.js"></script>
<link href="scripts/lib/jquery-ui/1.8/themes/base/jquery.ui.all.css" rel="stylesheet" type="text/css"/>
<script language="javascript" type="text/javascript" src="scripts/lib/startup.js"></script>
<script language="javascript" type="text/javascript">
$(window).load(function () {
  setInterval(function(){
      $('#result').load('index.php?action=ping');
  },5*60*1000);
});
</script>
</head>
<body leftmargin="5" topmargin="5">
<font color=white></font>
<!--  -->
<div class=a style='padding:5px;'>


<!--  -->
<div  style="background-color:white; border:2px solid #284351; padding:5pt; text-align:left;">
<span style="display:inline-block;vertical-align:top;width:200pt;">
   <!--  -->
   <?php
   foreach($input_vars['list_of_languages'] as $lang){
    ?><div style=""><b><a href="<?=$lang['href']?>"><?=$lang['name']?></a></b></div><?php
   }
   ?>
   <!--  -->
</span>
<span style="display:inline-block;vertical-align:top;">
<?=$input_vars['current_user_name']?>  @ <?=($_SERVER['HTTP_HOST'] .'('.$_SERVER['SERVER_ADDR'].')')?>
<h1 style="margin-top:5pt;"><?=isset($input_vars['page_header'])?$input_vars['page_header']:''?></h1>
</span>
<!--  -->
</div>

<div style="float:left;">
<span style="display:inline-block;vertical-align:top;background-color:#D1DFE0;width:200pt;">
    <?php
       if(is_array($input_vars['page_menu']))
       foreach($input_vars['page_menu'] as $menu_group)
       {
         ?>
          <div class=a style="background-color:white; border:2px solid #284351; padding:5pt; text-align:left;margin-top:5pt;">
          <b style='font-size:120%;'><?=$menu_group['title']?></b>
          <div  class=s10>
          <!-- ul style='list-style-position:outside; list-style-type: square; margin-top:0;margin-bottom:0; margin-left:14px;' -->
          <?php
               foreach($menu_group['items'] as $menu_item){
                 if(strlen($menu_item['URL'])>0){
                    ?><a class="m_item" href="<?=$menu_item['URL']?>" title="<?=strip_tags($menu_item['innerHTML'])?>" <?=$menu_item['attributes']?>>&otimes; <?=$menu_item['innerHTML']?></a><?php
                 }else{
                    ?><div class=mit style=' text-align:left;' <?=$menu_item['attributes']?>><?=$menu_item['innerHTML']?></div><?php
                 }
               }
               ?>
          </ul>
          </div>
          </div>
          <div>&nbsp;</div>
         <?php
      }
      ?>
</span>
</div>

<div style="margin-left:210pt;background-color:white;margin-top:5pt;border:2px solid #284351; padding:8pt;text-align:left;">
<span class="textblok" id="page_content" style="display:inline-block;width:99%;">
 <?=isset($input_vars['page_content'])?$input_vars['page_content']:''?>
</span>
</div>
<div style="clear:both;"></div>
<br />




</div>
<span id="result"></span>


<!--  -->
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />
<br />



</body>
</html>

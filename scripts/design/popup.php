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
?>
<html>
<head>
<META content="text/html; charset=<?=site_charset?>" http-equiv=Content-Type>
<link rel="stylesheet" href="img/styles.css" type="text/css">
<title><?=$input_vars['page_title']?></title>
<script language="javascript" type="text/javascript" src="scripts/lib/jquery.min.js"></script>
<script language="javascript" type="text/javascript" src="scripts/lib/jquery-ui.min.js"></script>
<link href="scripts/lib/jquery-ui/1.8/themes/base/jquery.ui.all.css" rel="stylesheet" type="text/css"/>
<script language="javascript" type="text/javascript" src="scripts/lib/startup.js"></script>
</head>
<body leftmargin="5" topmargin="5">
<?php /* =$input_vars['page_start'] */ ?>
<!-- ������� ������ ����������� ������� -->

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr><td class="a">



<!--�������  �������, ��� ������ ���� ���� � ������� ���� -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" align=center>
<tr><td valign="top" class="a">

<!--�������  �������� ���������� ����� -->

<table width="95%" border="0" cellpadding="0" cellspacing="0" align="center">
<tr><td class="b">
<div class="textblok">
 <?=$input_vars['page_content']?>
</div>
</td></tr>
</table>

<!-- ����� �������� ���������� ����� -->

</td></tr></table>

<!-- �����  �������, ��� ������ ���� ���� � ������� ���� -->

<br>

</td></tr>
</table>
<!-- ����� ������ ����������� ������� -->

</body>
</html>


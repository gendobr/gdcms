<html>
<head>
<META content="text/html; charset=<?=site_charset?>" http-equiv=Content-Type>
<title>�������� ���������� ������</title>
</head>
<body leftmargin="5" topmargin="5">
<?php
/**
 *
 */
$GLOBALS['main_template_name']='';
//------------------- get site info - begin ------------------------------------
run('site/menu');
$site_id = (int)($input_vars['site_id']);
$this_site_info = get_site_info($site_id);
//prn($this_site_info);die();
//prn($input_vars);
if(!$this_site_info) die($txt['Site_not_found']);
//------------------- get site info - end --------------------------------------

$poll_uid=$input_vars['poll_uid'];


$query="SELECT ad.*,p.title,v.html
        FROM   {$table_prefix}golos_vidpovidi_details as ad
               INNER JOIN {$table_prefix}golos_pynannja AS p
               ON p.id=ad.poll_id
               LEFT JOIN  {$table_prefix}golos_vidpovidi as v
               ON ad.answer_id=v.id
        WHERE ad.site_id={$site_id} and poll_uid='".  \e::db_escape($poll_uid)."'
        order by ad.session_id,ad.answer_date,ad.poll_id";
//prn($query);
$tmp=\e::db_getrows($query);
//prn($tmp);
echo '
<table border=1px celpadding=3pt cellspacing=0>
    <tr>
         <td>UID</td>
         <td>date</td>
         <td>������</td>
         <td>�����</td>
         <td>������������� ������� 1</td>
         <td>������������� ������� 2</td>
         <td>IP �������</td>
         <td>������� �������</td>
    </tr>
';
$prev='';
foreach($tmp as $tm) {
    if($prev!="{$tm['session_id']}-{$tm['answer_date']}") {
        echo "<tr>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
         <td>&nbsp;</td>
        </tr>";
    }
    echo "<tr>
         <td>{$tm['session_id']}</td>
         <td>{$tm['answer_date']}</td>
         <td>{$tm['title']}</td>
         <td>".strip_tags($tm['html'])."&nbsp;</td>
         <td>{$tm['client_sign']}&nbsp;</td>
         <td>{$tm['client_sign2']}&nbsp;</td>
         <td>{$tm['client_ip']}&nbsp;</td>
         <td>{$tm['client_is_valid']}&nbsp;</td>
        </tr>";
    $prev="{$tm['session_id']}-{$tm['answer_date']}";
}
echo '</table>';


?></body></html>
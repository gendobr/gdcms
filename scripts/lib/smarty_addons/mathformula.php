<?php
$str = split("\\[/?tex\\]", $data);
for ($i=1; $i<count($str); $i=$i+2){
	//$text = str_replace(" ", "", $_POST['rentext']);
	$text =  $str[$i];
	$text = str_replace("\n", " ", $text);
	$text = str_replace("\r", "", $text);
	/*echo "POST: ".$_POST['rentext']."<br>";
	echo "Text: <pre>".$text."</pre><br>";*/
	//echo "Text: <pre>".$text."</pre><br>";
	//echo "Google: <img eeimg=\"1\" src=\"http://chart.apis.google.com/chart?cht=tx&chs=1x0&chf=bg,s,FFFFFF00&chco=000000&chl=".$text."\">";
    $str[$i]="<img src=\"http://sites.znu.edu.ua/mathformula/formula.php?t=".rawurlencode($text)."\" alt=\"".htmlspecialchars($text)."\">";
}
// 
echo join("",$str);
?>
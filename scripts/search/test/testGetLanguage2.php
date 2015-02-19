<!DOCTYPE html>
<html lang="en">

<head>
    <title>Test language selector</title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
    <?php

if(isset($_REQUEST['html'])) {
    include ("../getlanguage.php");
    include('../../lib/simple_html_dom.php');
    include('../../lib/url_to_absolute.php');
    include('../htmlparser.php');


    //$html=file_get_contents('eng.html');
    $html = $_REQUEST['html'];
    //$html=file_get_contents('rus.html');

    $parser = new GDSearchParser(
            function() {
        $symbol = file('../data/common_words.txt');
        $cnt = count($symbol);
        for ($i = 0; $i < $cnt; $i++) {
            $symbol[$i] = str_replace(Array("\n", "\r"), '', $symbol[$i]);
        }
        return $symbol;
    }
    );
    $pageData = $parser->extractData($parser->dom_from_str($html));
    //print_r($pageData);
    // echo $pageData['title']."\n=============\n";
    //echo $pageData['description']."\n=============\n";
    //echo $pageData['keywords']."\n=============\n";
    //echo $pageData['text']."\n=============\n";

    $langSelector = new getlanguage(Array(
        'files' => Array(
            'eng' => '../data/stats_eng.txt',
            'rus' => '../data/stats_rus.txt',
            'ukr' => '../data/stats_ukr.txt',
            'slov' => '../data/stats_slov.txt',
            'češ' => '../data/stats_ces.txt',
            )
    ));

    $reply=$langSelector->getTextLang($pageData['text']);
    
    echo "<h1>Мова = {$reply['lang']}</h1>";
}
?>
<h1>Визначення мови тексту</h1>
Вставте текст в форму та натисніть кнопку
<form method="post">
<textarea name=html style="width:100%;height:400px;"></textarea>
<br>
<input type="submit">
</form>
</body>
</html>
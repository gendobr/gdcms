<?php

include ("../tokenizer/tokenizer.php");
include ("../tokenizer/tokenizer_ukr.php");
include ("../tokenizer/tokenizer_rus.php");
include ("../tokenizer/tokenizer_eng.php");
include ("../getlanguage/getlanguage.php");
include ("../commonwords/commonwords.php");

include ("../stemming/stemmer.class.php");
include ("../stemming/porter_eng.class.php");
include ("../stemming/porter_rus.class.php");
include ("../stemming/porter_ukr.class.php");

$encoding = 'UTF-8';

//$str = file_get_contents('ukr.txt');
//$str = file_get_contents('rus.txt');
//$str = file_get_contents('ukr.txt').' '.file_get_contents('eng.txt');
//$str = file_get_contents('ukr.txt').' '.file_get_contents('rus.txt');
$str="В Економіко-правничому коледжі відбулася зустріч студентів-видавців третього курсу з провідним редактором Обласної академічної газети «Запорізький університет» – Марією Канцелярист.
Презентація роботи видання проходила в рамках проведення у коледжі декади популяризації спеціальності «Видавнича справа та редагування». Студенти мали нагоду простежити розвиток університетського корпоративного засобу масової інформації від його першого випуску (газети «Педагог» 70-х років минулого століття) вже до найостаннішого сучасного номеру «Запорізького університету», погортавши сторінки підбірки підшивок за 42 роки існування газети.
Також третьокурсники могли поставити запитання гостю-практику, адже їх цікавило чимало внутрішніх нюансів щоденної роботи редактора: форс-мажорні обставини, недоліки професії, її творчий бік – можливість словом передати яскраві емоції від тієї чи іншої події.
За словами Марії Анатоліївни, яка пропрацювала у журналістській і видавничій сфері 10 років, редактор займається не тільки обробкою авторських матеріалів, переробляє у відповідний формат авторський текст, але й розподіляє завдання, контролює їх своєчасне і якісне виконання, працює на імідж своєї компанії, установи. Саме тому вся інформація повинна бути актуальною, точною, грамотно представленою.
У більшості неспеціалістів з цієї справи склалося хибне уявлення, що фах редактора-видавця як такий стосується виловлювання помилок у текстах та правильного їх стилістичного оформлення. Насправді ж ця спеціальність передбачає підготовку фахівця, який поєднуватиме в собі відповідні навички й уміння. Редактор виступає як літературний працівник, як керівник редакційного колективу, як менеджер. Мабуть саме в цій поліфункціональності і полягає родзинка цієї професії.";

$commonwords=new commonwords('../commonwords/commonwords.txt');

$langSelector = new getlanguage(Array(
    'files' => Array(
        'eng' => '../getlanguage/stats_eng.txt',
        'rus' => '../getlanguage/stats_rus.txt',
        'ukr' => '../getlanguage/stats_ukr.txt',
    // 'slov' => '../getlanguage/stats_slov.txt',
    // 'češ' => '../getlanguage/stats_ces.txt',
    )
        ));

$tokenizers = Array(
    'eng' => new tokenizer_eng(),
    'ukr' => new tokenizer_ukr(),
    'rus' => new tokenizer_rus()
);

$stemmers=Array(
    'eng' => new porter_eng(),
    'ukr' => new porter_ukr(),
    'rus' => new porter_rus()
);


$tokens = Array();
$remainder = $str;
$checkedLangs=Array();
while (true) {
    $lang = $langSelector->getTextLang($remainder);
    $lang=$lang['lang'];
    
    if(isset($checkedLangs[$lang])){
        break;
    }
    $checkedLangs[$lang]=1;
    $reply = $tokenizers[$lang]->getTokens($remainder);
    // print_r($reply); exit("222");
    if (count($reply['tokens']) > 0) {
        $tokens[$lang] = $commonwords->removeCommonWords($reply['tokens']);
        $cnt=count($tokens[$lang]);
        for($i=0; $i<$cnt; $i++){
            $tokens[$lang][$i]=$stemmers[$lang]->stem($tokens[$lang][$i]);
        }
    }
    $dl = mb_strlen($remainder, $encoding) - mb_strlen($reply['remainder'], $encoding);
    if($dl==0){
        break;
    }
    $remainder = $reply['remainder'];
}
print_r($tokens);

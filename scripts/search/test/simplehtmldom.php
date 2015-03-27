<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include('../../lib/simple_html_dom.php');


//$html = str_get_html('<div id="hello">Hello</div><div id="world">World</div>');
//$html = str_get_html('<div id="hello">Hello</div><div class="world">World</div>');
$html = str_get_html('<span id="hello">Hello</span><div class="world">World</div>');
// $html->find('div', 1)->class = 'bar';

//$html->find('div[id=hello]', 0)->innertext = 'foo';
echo $html->find('*[id]', 0)->plaintext."\n\n\n";

<?php

class GDSearchParser {

    private $commonWordsReader; //local_root.'/scripts/lib/common_words.txt'
    private $commonWords = false;

    // $commonWordsReader=function(){
    //        $symbol = file(local_root . '/scripts/lib/common_words.txt');
    //        $cnt = count($symbol);
    //        for ($i = 0; $i < $cnt; $i++) {
    //            $symbol[$i] = str_replace(Array("\n", "\r"), '', $symbol[$i]);
    //        }
    //        return $symbol;
    // }

    public function __construct($commonWordsReader) {
        $this->commonWordsReader = $commonWordsReader;
    }

    
    public function dom_from_str($str){
        $html = str_get_html($str);
        return $html;
    }

    public function dom_from_url($url){
        $html =  file_get_html($url);
        return $html;
    }
    
    public function extractData($html){

        $description='';
        $keywords='';
        foreach($html->find('meta') as $element) {
            if($element->name == 'description'){
                $description=$element->content;
            }
            if($element->name == 'keywords'){
                $keywords=$element->content;
            }
        }
        
        $links=Array();
        foreach($html->find('a') as $lnk){
            if(strlen($lnk->href) >0 ){
                $links[]=$lnk->href;
            }
        }
        
        
        $title=$html->find("title", 0);
        if($title){
            $title=$title->plaintext;
        }else{
            $title='';
        }
        
        return Array(
            'title' => $title,
            'description'=>$description,
            'keywords'=>$keywords,
            'text'=>$html->plaintext,
            'links'=>$links
        );
    }
    

    public function remove_common_words($ht) {
        if ($this->commonWords === false) {
            $this->commonWords = $this->commonWordsReader();
        }
        $html_text = trim(str_replace($this->commonWords, " ", ' ' . $ht . ' '));
        return $html_text;
    }

}

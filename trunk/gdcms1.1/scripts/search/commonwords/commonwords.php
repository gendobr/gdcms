<?php

class commonwords {

    private $filepath;
    private $words;

    public function __construct($filepath) {
        $this->filepath = $filepath;
    }

    public function removeCommonWords($inputTokens) {
        if (!isset($this->words)) {
            $this->words = file($this->filepath);
            $cnt = count($this->words);
            for ($i = 0; $i < $cnt; $i++) {
                $this->words[$i] = trim($this->words[$i]);
            }
            $this->words = array_flip($this->words);
        }
        $outputTokens=Array();
        foreach($inputTokens as $token){
            if(!isset($this->words[$token])){
                $outputTokens[]=$token;
            }
        }
        return $outputTokens;
    }

}


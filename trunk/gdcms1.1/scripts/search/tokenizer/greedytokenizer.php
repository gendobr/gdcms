<?php

class greedytokenizer {

    private $tokenizers;
    private static $encoding = 'UTF-8';
    private static $max_token_length = 100;

    public function __construct($tokenizers) {
        $this->tokenizers = $tokenizers;
    }

    public function getTokens($text) {


        $tokens = Array();
        $remainder = $text;
        $keys = array_keys($this->tokenizers);
        $res = Array();

        $remainder_length = mb_strlen($remainder, self::$encoding);
        while ($remainder_length > 0) {
            //echo "=================\n";
            // 
            $remainder_head = mb_substr($remainder, 0, self::$max_token_length, self::$encoding);
            $remainder_head_length = mb_strlen($remainder_head, self::$encoding);
            
            //echo "remainder_head {$remainder_head}\n";
            foreach ($keys as $key) {
                $res[$key] = $this->tokenizers[$key]->getFirstToken($remainder_head);
                $res[$key]['token_length'] = mb_strlen($res[$key]['token'], self::$encoding);
                $res[$key]['remainder_length'] = mb_strlen($res[$key]['remainder'], self::$encoding);
                $res[$key]['skipped_length'] = $remainder_head_length - $res[$key]['token_length'] - $res[$key]['remainder_length'];
                //echo "token {$res[$key]['token']} length {$res[$key]['token_length']} skipped {$res[$key]['skipped_length']}\n";
            }
            // search for token that provides 
            // 1) minimal skipped_length
            // 2) maximal token_length
            $chosenKey = false;
            foreach ($keys as $key) {
                $val = $res[$key];
                //echo "key=$key chosenKey = $chosenKey\n";

                if ($val['token_length'] == 0) {
                    continue;
                }
                if ($chosenKey === false) {
                    //echo "replacing null => $key  because of first\n";
                    $chosenKey = $key;
                    continue;
                }

                if ($res[$chosenKey]['skipped_length'] < $val['skipped_length']) {
                    continue;
                }

                // less skipped_length is better
                if ($res[$chosenKey]['skipped_length'] > $val['skipped_length']) {
                    //echo "replacing $chosenKey => $key  because of skipped_length\n";
                    $chosenKey = $key;
                    continue;
                }

                // if the skipped_length id the same then greater token_length is better
                if (
                        $res[$chosenKey]['skipped_length'] == $val['skipped_length'] && $res[$chosenKey]['token_length'] < $val['token_length']
                ) {
                    //echo "replacing $chosenKey => $key  because of token_length\n";
                    $chosenKey = $key;
                    continue;
                }
            }
            if($chosenKey === false){
                if(mb_strlen($remainder, self::$encoding) > 0){
                    $remainder = mb_substr($remainder, $remainder_head_length, null, self::$encoding);
                }
            } else  {
                $tokens[] = $res[$chosenKey]['token'];
                $remainder = mb_substr($remainder, $res[$chosenKey]['skipped_length']+$res[$chosenKey]['token_length'], null, self::$encoding);
                //echo "chosen token {$res[$chosenKey]['token']} skipped {$res[$chosenKey]['skipped_length']}\n";
            }
            $remainder_length = mb_strlen($remainder, self::$encoding);
        }
        return $tokens;
    }

}

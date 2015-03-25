<?php

class greedytokenizer {

    private $tokenizers;
    private static $encoding = 'UTF-8';
    private static $max_token_length=50;

    public function __construct($tokenizers) {
        $this->tokenizers = $tokenizers;
    }

    public function getTokens($text) {


        $tokens = Array();
        $remainder = $text;
        $keys = array_keys($this->tokenizers);
        $res = Array();

        while (count($keys) > 0) {
            //echo "=================\n";
            // 
            $remainder_length = mb_strlen($remainder, self::$encoding);
            foreach ($keys as $key) {
                if (!isset($res[$key])  ||  ( $remainder_length - $res[$key]['remainder_length']) < self::$max_token_length ) {
                    $res[$key] = $this->tokenizers[$key]->getFirstToken($remainder);
                    $res[$key]['token_length'] = mb_strlen($res[$key]['token'], self::$encoding);
                    $res[$key]['remainder_length'] = mb_strlen($res[$key]['remainder'], self::$encoding);
                    $res[$key]['skipped_length'] = $remainder_length - $res[$key]['token_length'] - $res[$key]['remainder_length'];
                    //echo "token {$res[$key]['token']} length {$res[$key]['token_length']} skipped {$res[$key]['skipped_length']}\n";
                }
            }
            // search for token that provides 
            // 1) minimal skipped_length
            // 2) maximal token_length
            $chosenKey = false;
            foreach ($keys as $key) {
                $val=$res[$key];
                //echo "key=$key chosenKey = $chosenKey\n";
                // if token not found then disable tokenizer for current $text
                if ($val['token_length'] == 0) {
                    $pos = array_search($key, $keys);
                    if ($pos !== false) {
                        unset($keys[$pos]);
                        continue;
                    }
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
            if ($chosenKey === false) {
                break;
            } else {
                $tokens[] = $res[$chosenKey]['token'];
                $remainder = $res[$chosenKey]['remainder'];
                //echo "chosen token {$res[$chosenKey]['token']} skipped {$res[$chosenKey]['skipped_length']}\n";
            }
        }
        return $tokens;
    }

}

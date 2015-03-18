<?php

class tokenizer_rus implements tokenizer {

    private static $alfavit = Array();
    private static $encoding = 'UTF-8';

    public function __construct() {
        self::$alfavit = array_flip(explode(',', "а,б,в,г,д,е,ё,ж,з,и,й,к,л,м,н,о,п,р,с,т,у,ф,х,ц,ч,ш,щ,ъ,ы,ь,э,ю,я"));
    }

    public function getTokens($str) {
        $lowercaseStr = mb_strtolower($str, self::$encoding);
        $remainder = '';
        $tokens = Array();
        $cnt = mb_strlen($str, self::$encoding);
        $token = '';
        for ($i = 0; $i < $cnt; $i++) {
            $char = mb_substr($lowercaseStr, $i, 1, 'utf-8');
            if (isset(self::$alfavit [$char])) {
                $token.=$char;
            } else {
                if (mb_strlen($token, self::$encoding) > 0) {
                    
                    // апострофи на початку та в кінці слів
                    $token = preg_replace("/^'+|'+\$/", '', $token);

                    $token = preg_replace("/ +/", " ", $token);

                    if (mb_strlen($token, self::$encoding) > 0) {
                        $token = explode(' ', $token);
                        $tokens = array_merge($tokens, $token);
                    }
                    $token = '';
                }
                $remainder.=$char;
            }
        }
        if (mb_strlen($token, self::$encoding) > 0) {
            // апострофи на початку та в кінці слів
            $token = preg_replace("/^'+|'+\$/", '', $token);
            $token = preg_replace("/ +/", " ", $token);
            if (mb_strlen($token, self::$encoding) > 0) {
                $token = explode(' ', $token);
                $tokens = array_merge($tokens, $token);
            }
        }
        
        return Array('tokens'=>$tokens,'remainder'=>$remainder);
    }

}

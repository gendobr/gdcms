<?php

class tokenizer_ukr implements tokenizer {

    private static $alfavit = Array();
    private static $encoding = 'UTF-8';
    private static $apos;

    public function __construct() {
        self::$apos=Array("’","'","‘","ʼ");
        self::$alfavit = array_flip(explode(',', "а,б,в,г,ґ,д,е,є,ж,з,и,і,ї,й,к,л,м,н,о,п,р,с,т,у,ф,х,ц,ч,ш,щ,ь,ю,я,'"));
    }

    public function getTokens($str) {
        $lowercaseStr = str_replace(self::$apos,"'",mb_strtolower($str, self::$encoding));
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

                    //апострофи не перед я, ю, є, ї
                    $token = trim(preg_replace("/'([^яюєї])/", ' $1', $token));

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

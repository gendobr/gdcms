<?php

class tokenizer2_ukr implements tokenizer2 {

    private static $alfavit = Array();
    private static $encoding = 'UTF-8';
    private static $apos;

    public function __construct() {
        self::$apos=Array("’","'","‘","ʼ");
        self::$alfavit = array_flip(explode(',', "а,б,в,г,ґ,д,е,є,ж,з,и,і,ї,й,к,л,м,н,о,п,р,с,т,у,ф,х,ц,ч,ш,щ,ь,ю,я,'"));
    }

    public function getFirstToken($str) {
        $lowercaseStr = str_replace(self::$apos,"'",mb_strtolower($str, self::$encoding));
        $tokens = Array();
        $cnt = mb_strlen($str, self::$encoding);
        $token = '';
        for ($i = 0; $i < $cnt; $i++) {
            $char = mb_substr($lowercaseStr, $i, 1, 'utf-8');
            if (isset(self::$alfavit [$char])) {
                $token.=$char;
            } else {
                //if (mb_strlen($token, self::$encoding) > 0) {
                if (strlen($token) > 0) {
                    
                    // апострофи на початку та в кінці слів
                    $token = preg_replace("/^'+|'+\$/", '', $token);

                    //апострофи не перед я, ю, є, ї
                    $token = trim(preg_replace("/'([^яюєї])/", ' $1', $token));

                    $token = preg_replace("/ +/", " ", $token);

                    //if (mb_strlen($token, self::$encoding) > 0) {
                    if (strlen($token) > 0) {
                        $token = explode(' ', $token);
                        $tokens = array_merge($tokens, $token);
                        $token = '';
                        break;
                    }
                }
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
        $firstToken = isset($tokens[0]) ? $tokens[0] : '';
        
        if(strlen($firstToken)>0){
            $pos = mb_strpos($lowercaseStr, $firstToken, 0, self::$encoding);
            return Array('token' => $firstToken, 'remainder' => mb_substr($str, $pos + mb_strlen($firstToken, self::$encoding), null, self::$encoding));            
        }else{
            return Array('token' => $firstToken, 'remainder' => $str);
        }
    }

}

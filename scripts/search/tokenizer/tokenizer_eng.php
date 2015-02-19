<?php

class tokenizer_eng implements tokenizer {

    private static $alfavit = Array();
    private static $encoding = 'UTF-8';
    private static $apos;

    public function __construct() {
        self::$apos=Array("’","'","‘","ʼ");
        self::$alfavit = array_flip(explode(',', "a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z,'"));
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
                    $token = trim(preg_replace("/'([^smd])/", ' $1', $token));

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
        
        return Array('tokens'=>$tokens,'remainder'=>$remainder);
    }

}

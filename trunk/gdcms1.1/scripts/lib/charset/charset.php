<?php


    /**
        @brief Detect encoding class

        Usage:
        
        $charset = new charset();
        $text = file_get_contents("before.html");
        echo $charset->detect($text);
    */
	class charset{
		protected $encodings = array();
        protected $enc_rates = array();

		function __construct(){
			$this->encodings = array(
			    'cp1251' => require realpath(dirname(__FILE__)).'/specters/specter_cp1251.php',
			    'koi8r' => require realpath(dirname(__FILE__)).'/specters/specter_koi8r.php',
			    'iso88595' => require realpath(dirname(__FILE__)).'/specters/specter_iso88595.php',
			    'utf-8' => require realpath(dirname(__FILE__)).'/specters/specter_utf8.php'
			);
		}
		
        /**
         * Detect encoding for RUSSIAN text
         * 
         * @param $str - String of RUSSIAN text
         * 
         * @return  Encoding name. See $this->encoding for supported charsets.
        */
		function detect($str){
			$enc_rates = array();
			for ($i = 0, $c=strlen($str); $i < $c; $i++)
			    foreach ($this->encodings as $encoding => $char_specter){
                    $char = $str[$i];
                    $ord = ord($char);
                    if(isset($char_specter[$ord])){
                        if(!isset($this->enc_rates[$encoding]))$this->enc_rates[$encoding] = 0;
                        $this->enc_rates[$encoding] += $char_specter[$ord];
                    }
                }
            arsort($this->enc_rates);
            foreach($this->enc_rates as $enc=>$rate)break;
            return $enc;
		}
		
	}


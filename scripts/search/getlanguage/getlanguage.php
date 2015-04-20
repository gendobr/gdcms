<?php

class getlanguage {

    private $zero = 0.000001;
    private $lang;
    private $bigrams;

    public function __construct($opt) {
        $this->lang = [];
        $this->bigrams = [];
        foreach ($opt['files'] as $lang => $filepath) {
            $this->lang[$lang] = unserialize(file_get_contents($filepath));
            $this->bigrams = array_merge($this->bigrams, array_keys($this->lang[$lang]));
        }
    }

    public function getTextLang($str,$candidates=false) {
        $distr = $this->getTextStats($str);

        $selectedLang = false;
        $minimalDistance = false;
        if(!$candidates){
            $candidates=array_keys($this->lang);
        }
        // foreach ($this->lang as $lang => $stat) {
        foreach ($candidates as $lang){
            $stat = $this->lang[$lang];
            //$distance = $this->chiSquareConfidence($stat, $distr['n'], $distr['stats']);
            $distance = $this->chiSquare($stat, $distr['n'], $distr['stats']);

            //echo "$lang => $distance \n";
            if ($selectedLang === false) {
                $selectedLang = $lang;
                $minimalDistance = $distance;
            } elseif ($distance < $minimalDistance) {
                $selectedLang = $lang;
                $minimalDistance = $distance;
            }
        }
        return Array('lang' => $selectedLang, 'distance' => $minimalDistance);
    }

    private function getTextStats($str) {

        $encoding = 'utf-8';

        // remove comments
        $st = explode('<!--', $str);
        $cnt = count($st);
        for ($i = 1; $i < $cnt; $i+=1) {
            $tmp = explode('-->', $st[$i]);
            $st[$i] = $tmp[1];
        }
        $st = join(' ', $st);


        // remove styles
        $st = explode('<style', $st);
        $cnt = count($st);
        for ($i = 1; $i < $cnt; $i+=1) {
            $tmp = explode('</style>', $st[$i]);
            $st[$i] = $tmp[1];
        }
        $st = join(' ', $st);


        // remove scripts
        $st = explode('<script', $st);
        $cnt = count($st);
        for ($i = 1; $i < $cnt; $i+=1) {
            $tmp = explode('</script>', $st[$i]);
            $st[$i] = $tmp[1];
        }
        $st = join(' ', $st);

        $st = preg_replace("/<[^>]+>/", ' ', $st);
        $st = html_entity_decode($st);
        $st = str_replace([chr(hexdec("c2")) . chr(hexdec("a0")), '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', "\\n", "_", "+", '@', '–', "*", "\n", "\r", "\t", "|", "=", '%', '&', '^', '#', ',', '.', ':', '-', '!', '?', '"', "'", ';', '(', ')', '{', '}', '[', ']', "\\", '/', '”', '“', '«', '»', '_'], ' ', $st);
        $st = mb_strtolower(' ' . trim(preg_replace("/\\s+/", ' ', $st)) . ' ', $encoding);


        foreach ($this->bigrams as $key) {
            $stats[$key] = 0;
        };
        $cnt = mb_strlen($st, $encoding);
        // echo 'strlen='.$cnt."\n";
        // echo $pst.";\n";
        $n = 0;
        $pre = mb_substr($st, 0, 1, $encoding);
        for ($i = 1; $i < $cnt; $i++) {
            $cur = mb_substr($st, $i, 1, $encoding);
            if (isset($stats[$key = $pre . $cur])) {
                $stats[$key] ++;
                $n++;
            } elseif (isset($stats[$key = $cur . $pre])) {
                $stats[$key] ++;
                $n++;
            }
            $pre = $cur;
        }

        return Array('n' => $n, 'stats' => $stats);
    }

    private function chiSquare($stat, $N, $ni) {
        
        $chi2distance = 0;
        if($N>0){
            $n=$N;
        }else{
            $n=1;
        }
        $keys = array_merge(array_keys($stat), array_keys($ni));
        foreach ($keys as $key) {
            if (isset($stat[$key]) && $stat[$key] > $this->zero) {
                $num = $ni[$key] / $n - $stat[$key];
                $chi2distance+= ($num * $num) / $stat[$key];
            } else {
                $num = $ni[$key] / $n - $this->zero;
                $chi2distance+= ($num * $num) / $this->zero;
            }
        }
        $chi2distance*=$n;
        return $chi2distance;
    }

    //    private function chiSquareConfidence($stat, $n, $ni) {
    //        $chi2distance = 0;
    //        $keys = array_keys($stat);
    //        foreach ($keys as $key) {
    //            if ($stat[$key] > $this->zero) {
    //                $num = $ni[$key] / $n - $stat[$key];
    //                $chi2distance+= ($num * $num) / $stat[$key];
    //            } else {
    //                $num = $ni[$key] / $n - $this->zero;
    //                $chi2distance+= ($num * $num) / $this->zero;
    //            }
    //        }
    //        $chi2distance*=$n;
    //
    //        $x = ($chi2distance - $n) / sqrt($n * 2);
    //        return $this->normalCDF($x);
    //    }
    //
    //    private function normalCDF($X) {
    //        if ($X > 0) {
    //            $x = $X;
    //        } else {
    //            $x = -$X;
    //        }
    //        if ($x > 8) {
    //            return 1;
    //        }
    //        $sum = $x;
    //        $value = $x;
    //        for ($i = 1; $i < 100; $i++) {
    //            $value = ($value * $x * $x / (2 * $i + 1));
    //            $sum = $sum + $value;
    //        }
    //        $result = 0.5 + ($sum / sqrt(2 * pi())) * exp(-($x * $x) / 2);
    //        if ($X < 0) {
    //            $result = 1 - $result;
    //        }
    //        return $result;
    //    }

}

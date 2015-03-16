<?php

/**
 * charset detector
 *
 * 
 * //$lang='rus-utf8';
  //$lang='deu-cp1252';
  //$lang='deu-utf8';
  //$lang='fra-cp1252';
  //$lang='fra-utf8';
  //$lang='rus-cp866';
  //$lang='rus-koi8';
  //$lang='deu-iso-8859-1';
  //$lang='eng';
  //$lang='fra-iso-8859-1';
  //$lang='rus-cp1251';
  //$lang='rus-iso-8859-5';
 * 
 * $charsetdetector = new charsetdetector(Array(
 *  Array(
 *     'charset'=>'UTF-8',
 *     'stats'=>unserialize(file_get_contents('rus-utf8.stats'))
 *  )
  'rus-utf8';
  //$lang='deu-cp1252';
  //$lang='deu-utf8';
  //$lang='fra-cp1252';
  //$lang='fra-utf8';
  //$lang='rus-cp866';
  //$lang='rus-koi8';
  //$lang='deu-iso-8859-1';
  //$lang='eng';
  //$lang='fra-iso-8859-1';
  //$lang='rus-cp1251';
  //$lang='rus-iso-8859-5';
 * eng-cp1252.stats 
  eng-iso-8859-1.stats
 * ))
 * 
 * @author dobro
 */
class charsetdetector {

    
    public  $debug=false;
    private $encoding;
    private $keys;
    private $zero = 0.0000000001;

    public function __construct($emc) {
        $this->encoding = $emc;
        $this->keys = Array();
        foreach ($this->encoding as $en) {
            $this->keys = array_merge($this->keys, array_keys($en['stats']));
        }
        $this->keys = array_unique($this->keys);
        //print_r($emc);
    }

    public function detect($str1) {

        $str = preg_replace("/\\n/", ' ', $str1);
        $str = preg_replace("/\\s+/", ' ', $str);

        $unknown = $this->getByteStats($str);

        // count unknown bigramms
        $distances = Array();
        $minDistance = -1;
        foreach ($this->encoding as $id => $etalon) {
            $etalon_keys = array_keys($etalon['stats']);
            $unknown_keys = array_keys($unknown['stats']);
            // $distance1 = array_diff($etalon_keys, $unknown_keys);
            $diff = array_diff($unknown_keys, $etalon_keys);
            $distance = count($diff);
            if($this->debug) echo "{$etalon['charset']} => $distance<br>";
            if ($minDistance < 0) {
                $distances = Array();
                $distances[$id] = $etalon;
                $minDistance = $distance;
                if($this->debug) echo "starting first etalon set<br>";
            } elseif ($minDistance > $distance) {
                $distances = Array();
                $minDistance = $distance;
                $distances[$id] = $etalon;
                if($this->debug) echo "starting new etalon set<br>";
            } elseif ($minDistance == $distance) {
                $distances[$id] = $etalon;
                if($this->debug) echo "extending etalon set<br>";
            }
        }


        if (count($distances) > 1) {
            if($this->debug) echo "<br><br><br><br><br>Using chiSquare<br>";
            $selectedCharset = false;
            $minimalDistance = false;

            $keys = array_keys($distances);

            foreach ($keys as $id) {
                $distance = $this->chiSquare($distances[$id]['stats'], $unknown['n'], $unknown['stats']);
                if($this->debug) echo "{$id} {$distances[$id]['charset']} => $distance<br>\n";
                $distances[$id]['distance'] = $distance;
            }
            $success = usort($distances, 
                function($a, $b) {
                   if($a['distance'] == $b['distance']) return 0;
                   if($a['distance'] > $b['distance'] ) return 1; else return -1;
                });
            if($this->debug) { echo $success?'sort - ok':' sort error'; }
            if($this->debug) { echo '<pre>'; print_r($distances);echo '</pre>'; } 
        }
        
        $distances=array_values($distances);
        return $distances[0]['charset'];
    }

    // unigramm stats
    private function getByteStats($pst) {
        $stats = Array();
        $cnt = strlen($pst);
        $n = 0;
        for ($i = 0; $i < $cnt; $i++) {
            $cur = substr($pst, $i, 1);
            if (!isset($stats[$key = ord($cur)])) {
                $stats[$key] = 0;
            }
            $stats[$key] ++;
            $n++;
        }
        $keys = array_keys($stats);
        $norm = 1.0 / $n;
        foreach ($keys as $key) {
            $stats[$key]*=$norm;
        }
        return Array('n' => $n, 'stats' => $stats);
    }

    // bigramm stats
    //    private function getByteStats($pst) {
    //        $stats = Array();
    //        $cnt = strlen($pst);
    //        $pre = substr($pst, 0, 1);
    //        $n = 0;
    //        for ($i = 1; $i < $cnt; $i++) {
    //            $cur = substr($pst, $i, 1);
    //            if (!isset($stats[$key = ord($pre) . '.' . ord($cur)])) {
    //                $stats[$key] = 0;
    //            }
    //            $stats[$key] ++;
    //            $n++;
    //            $pre = $cur;
    //        }
    //        $keys = array_keys($stats);
    //        $norm = 1.0 / $n;
    //        foreach ($keys as $key) {
    //            $stats[$key]*=$norm;
    //        }
    //        return Array('n' => $n, 'stats' => $stats);
    //    }

    private function chiSquare($sample, $N, $unknown) {

        $chi2distance = 0;
        if ($N > 0) {
            $n = $N;
        } else {
            $n = 1;
        }

        foreach ($this->keys as $key) {
            if (isset($sample[$key])) {
                $val = $sample[$key];
            } else {
                $val = 0;
            }

            if (isset($unknown[$key])) {
                $un = $unknown[$key];
            } else {
                $un = 0;
            }

            if ($un > 0 && $val > 0) {
                $num = $un / $n - $val;
                $chi2distance+= ($num * $num) / $val;
            } elseif ($un > 0 && $val == 0) {
                $num = $un / $n;
                $chi2distance+= ($num * $num) / $this->zero;
            } elseif ($un == 0 && $val > 0) {
                $chi2distance+= $val ;
            }
            //elseif ($un == 0 && $val == 0) {
            //    
            //} 
        }
        $chi2distance*=$n;
        return $chi2distance;
    }

}

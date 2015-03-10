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

    private $encoding;
    private $zero = 0.000001;

    public function __construct($emc) {
        $this->encoding = $emc;
        //print_r($emc);
    }

    public function detect($str) {
        $distr = $this->getByteStats($str);

        $selectedCharset = false;
        $minimalDistance = false;
        foreach ($this->encoding as $stat) {
            $distance = $this->chiSquare($stat['stats'], $distr['n'], $distr['stats']);
            echo "{$stat['charset']} => $distance<br>\n";
            if ($selectedCharset === false) {
                $selectedCharset = $stat['charset'];
                $minimalDistance = $distance;
            } elseif ($distance < $minimalDistance) {
                $selectedCharset = $stat['charset'];
                $minimalDistance = $distance;
            }
        }
        //return Array('charset' => $selectedCharset, 'distance' => $minimalDistance);
        return $selectedCharset;

    }
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
        return Array('n'=>$n,'stats'=>$stats);
    }
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
    //        return Array('n'=>$n,'stats'=>$stats);
    //    }
    
    private function chiSquare($sample, $N, $unknown) {
        
        $chi2distance = 0;
        if($N>0){
            $n=$N;
        }else{
            $n=1;
        }
        $keys = array_merge(array_keys($sample), array_keys($unknown));
        
        //echo "<table>";
        
        foreach ($keys as $key) {
            if(isset($sample[$key]) ){
                $un=( isset($unknown[$key])?$unknown[$key]:0  );
                if($sample[$key] > $this->zero){
                    $num = $un / $n - $sample[$key];
                    $chi2distance+= ($num * $num) / $sample[$key];                    
                }else{
                    $num = $un / $n - $this->zero;
                    $chi2distance+= ($num * $num) / $this->zero;
                }
            }elseif(isset($unknown[$key])){
                $num = $unknown[$key] / $n - $this->zero;
                $chi2distance+= ($num * $num) / $this->zero;
            }
            
            //echo "<tr><td>$key</td><td>".( isset($sample[$key])?$sample[$key]:0  )."</td><td>".( isset($unknown[$key])?$unknown[$key]:0  )."</td></tr>";
        }
        //echo "</table><hr>";
        $chi2distance*=$n;
        return $chi2distance;
    }

}

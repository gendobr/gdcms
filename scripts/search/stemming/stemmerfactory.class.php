<?php
/**
 * Description of stemmerfactory
 *
 * @author dobro
 */
class stemmerfactory {
    public static function newStemmer($lang){
        switch ($lang){
            case 'ukr': return new porter_ukr();break;
            case 'eng': return new porter_eng();break;
            case 'rus': return new porter_rus();break;
        }
    }
}


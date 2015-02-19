<?php

/**
 * Implementation of Russian Stemming algorithm.
 * Modified by podarok to meet Ukrainian conditions.
 */
class porter_ukr implements stemmer {

    var $VERSION = "0.01";
    var $Stem_Caching = 0;
    var $Stem_Cache = array();
    var $VOWEL = '/аеиоуюяіїє/'; /* http://uk.wikipedia.org/wiki/Голосний_звук */
    var $PERFECTIVEGROUND = '/(ив|ивши|ившись|ыв|ывши|ывшись((?<=[ая])(в|вши|вшись)))$/';
    var $REFLEXIVE = '/(с[яьи])$/'; // http://uk.wikipedia.org/wiki/Рефлексивне_дієслово
    var $ADJECTIVE = '/(ими|ій|ий|а|е|ова|ове|ів|є|їй|єє|еє|я|ім|ем|им|ім|их|іх|ою|йми|іми|у|ю|ого|ому|ої)$/'; //http://uk.wikipedia.org/wiki/Прикметник + http://wapedia.mobi/uk/Прикметник
    var $PARTICIPLE = '/(ий|ого|ому|им|ім|а|ій|у|ою|ій|і|их|йми|их)$/'; //http://uk.wikipedia.org/wiki/Дієприкметник
    var $VERB = '/(сь|ся|ив|ать|ять|у|ю|ав|али|учи|ячи|вши|ши|е|ме|ати|яти|є)$/'; //http://uk.wikipedia.org/wiki/Дієслово
    var $NOUN = '/(а|ев|ов|е|ями|ами|еи|и|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я|і|ові|ї|ею|єю|ою|є|еві|ем|єм|ів|їв|\'ю)$/'; //http://uk.wikipedia.org/wiki/Іменник
    var $RVRE = '/^(.*?[аеиоуюяіїє])(.*)$/';
    var $DERIVATIONAL = '/[^аеиоуюяіїє][аеиоуюяіїє]+[^аеиоуюяіїє]+[аеиоуюяіїє].*(?<=о)сть?$/';

    function __construct() {
        // $Stem_Caching = variable_get('ukstemmer_stemcaching', 0);
        // $VOWEL = variable_get('ukstemmer_vowel', '/аеиоуюяіїє/');
        // $PERFECTIVEGROUND = variable_get('ukstemmer_perfectiveground', '/((ив|ивши|ившись|ыв|ывши|ывшись((?<=[ая])(в|вши|вшись)))$/');
        // $REFLEXIVE = variable_get('ukstemmer_reflexive', '/(с[яьи])$/');
        // $ADJECTIVE = variable_get('ukstemmer_adjective', '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/');
        // $PARTICIPLE = variable_get('ukstemmer_participle', '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/');
        // $VERB = variable_get('ukstemmer_verb', '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/');
        // $NOUN = variable_get('ukstemmer_noun', '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$/');
        // $RVRE = variable_get('ukstemmer_rvre', '/^(.*?[аеиоуюяіїє])(.*)$/');
        // $DERIVATIONAL = variable_get('ukstemmer_derivational', '/[^аеиоуюяіїє][аеиоуюяіїє]+[^аеиоуюяіїє]+[аеиоуюяіїє].*(?<=о)сть?$/');
    }

    function s(&$s, $re, $to) {
        $orig = $s;
        // var_dump("preg_replace($re, $to, $s)");
        $s = preg_replace($re, $to, $s);
        return $orig !== $s;
    }

    function m($s, $re) {
        return preg_match($re, $s);
    }

    function stem($word) {
        //watchdog('ukstemmer', $word, NULL, $severity = WATCHDOG_NOTICE, $link = NULL);
        $word = mb_strtolower($word,"UTF-8");
        // $word = strtr($word, 'ё', 'е');
        # Check against cache of stemmed words
        if ($this->Stem_Caching && isset($this->Stem_Cache[$word])) {
            return $this->Stem_Cache[$word];
        }
        $stem = $word;
        do {
            if (!preg_match($this->RVRE, $word, $p))
                break;
            $start = $p[1];
            $RV = $p[2];
            if (!$RV)
                break;

            # Step 1
            if (!$this->s($RV, $this->PERFECTIVEGROUND, '')) {
                $this->s($RV, $this->REFLEXIVE, '');

                if ($this->s($RV, $this->ADJECTIVE, '')) {
                    $this->s($RV, $this->PARTICIPLE, '');
                } else {
                    if (!$this->s($RV, $this->VERB, ''))
                        $this->s($RV, $this->NOUN, '');
                }
            }

            # Step 2
            $this->s($RV, '/и$/', '');

            # Step 3
            if ($this->m($RV, $this->DERIVATIONAL))
                $this->s($RV, '/ость?$/', '');

            # Step 4
            if (!$this->s($RV, '/ь$/', '')) {
                $this->s($RV, '/ейше?/', '');
                $this->s($RV, '/нн$/', 'н');
            }

            $stem = $start . $RV;
        } while (false);
        if ($this->Stem_Caching)
            $this->Stem_Cache[$word] = $stem;
        return $stem;
    }

    function stem_caching($parm_ref) {
        $caching_level = @$parm_ref['-level'];
        if ($caching_level) {
            if (!$this->m($caching_level, '/^[012]$/')) {
                die(__CLASS__ . "::stem_caching() - Legal values are '0','1' or '2'. '$caching_level' is not a legal value");
            }
            $this->Stem_Caching = $caching_level;
        }
        return $this->Stem_Caching;
    }

    function clear_stem_cache() {
        $this->Stem_Cache = array();
    }

}

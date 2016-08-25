<?php

/**
 * Class detectEncoding
 * Based on habr's code https://habrahabr.ru/post/127658/
 * 'utf-8', 'windows-1251', 'koi8-r', 'iso8859-5'
 */
class encodingDetector {

    public static function detect($text, $possible_encodings = array('utf-8', 'windows-1251', 'koi8-r', 'iso8859-5')) {

        if (self::is_utf8($text)) {
            return 'utf-8';
        }

        $weights = array();
        $specters = array();
        foreach ($possible_encodings as $encoding) {
            if(file_exists(__DIR__.'/specters/'.$encoding.'.php')){
                $weights[$encoding] = 0;
                $specters[$encoding] = require __DIR__.'/specters/'.$encoding.'.php';
            }
        }

        if (preg_match_all("#(?<let>.{2})#", $text, $matches)) {
            foreach ($matches['let'] as $key) {
                foreach ($possible_encodings as $encoding) {
                    if (isset($specters[$encoding][$key])) {
                        $weights[$encoding] += $specters[$encoding][$key];
                    }
                }
            }
        }

        $max_weight = 0;
        $result = '';
        foreach ($weights as $encoding => $weight) {
            if($weight > $max_weight){
                $max_weight = $weight;
                $result = $encoding;
            }
        }

        return $result;
    }

    public static function is_utf8($string) {
        return (mb_detect_encoding($string, 'UTF-8', true) == 'UTF-8');
    }


}


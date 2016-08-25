<?php


class IPBlocker {

    const IP_LIST_FILE = 'ipblocker_userlist.txt';

    private static function get_user_ip() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    }

    private static function get_ip_list($timeout) {
        if(!file_exists(__DIR__."/".self::IP_LIST_FILE) || (filemtime(__DIR__."/".self::IP_LIST_FILE) + $timeout) < time()) {
            file_put_contents(__DIR__."/".self::IP_LIST_FILE, '');
        }
        return file(__DIR__."/".self::IP_LIST_FILE, FILE_IGNORE_NEW_LINES);
    }

    private static function update_ip_list($ip) {
        if($ip) {
            file_put_contents(__DIR__."/".self::IP_LIST_FILE, $ip."\n", FILE_APPEND);
        }
    }

    /**
     * @param $ip - user IP
     * @return bool TRUE=SPAMMER
     */
    public static function is_spammer($timeout=60, $requests=10, $whitelist=array()) {
        $ip = self::get_user_ip();

        if($whitelist){
            if(in_array($ip, $whitelist)){
                return FALSE;
            }
        }

        $ip_list = self::get_ip_list($timeout);
        self::update_ip_list($ip);
        if(count(array_keys($ip_list, $ip)) <= $requests) {
            return FALSE;
        }
        return TRUE;
    }

}
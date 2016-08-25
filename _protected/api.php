<?php
session_start();
setlocale(LC_ALL, 'ru_RU.UTF-8', 'rus_RUS.UTF-8', 'Russian_Russia.UTF-8');
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

require_once __DIR__.'/config/config.php';
require_once __DIR__.'/classes/phpmailer/PHPMailerAutoload.php';
require_once __DIR__.'/classes/cache.php';
require_once __DIR__.'/classes/idn.php';

class API extends CONFIG {

    public $db;
    public $mc;
    public $error = "";
    public $downloads_folder = "";
    public $images_downloads_folder = "";
    public $settings = array();
    public $version = 0;

    public function __construct() {
        try {
            $this->db = new PDO("mysql:host=".self::DB_HOST."; dbname=".self::DB_NAME, self::DB_USER, self::DB_PASS, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            
            $this->get_settings();

            if(defined('API::MEMCHACHED_HOST') && defined('API::MEMCHACHED_PORT')){
                $this->mc = new Memcached();
                $this->mc->addServer(self::MEMCHACHED_HOST, self::MEMCHACHED_PORT);
            }

            $this->downloads_folder = __DIR__.'/../'.self::DOWNLOADS_DIR;
            if (!is_dir($this->downloads_folder)) {
                @mkdir($this->downloads_folder, 0755, TRUE);
                @chmod($this->downloads_folder, 0755);
            }
            $this->images_downloads_folder = __DIR__.'/../'.self::IMAGES_DOWNLOADS_DIR;
            if (!is_dir($this->images_downloads_folder)) {
                @mkdir($this->images_downloads_folder, 0755, TRUE);
                @chmod($this->images_downloads_folder, 0755);
            }
        } catch (PDOException $e) {
            exit($e->getMessage());
        }
    }


    public static function clean_array(&$array, $value) {
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                self::clean_array($array[$key], $value);
            } elseif ($val === $value) {
                unset($array[$key]);
            }
        }
    }

    public static function clean_varchar($str) {
        return htmlspecialchars(htmlspecialchars_decode(trim($str)));
    }

    public static function clean_url($url) {
        $url = htmlspecialchars_decode($url);
        $url = mb_strtolower($url);
        $url = preg_replace('/[^ a-zа-яёіїєґ0-9_\-\.,\(\)]/iu', '', $url);
        $url = str_replace(' ', '_', $url);
        $url = str_replace('__', '_', $url);
        $url = trim($url, '_-.,');
        $url = mb_substr($url, 0, 255);
        return $url;
    }

    public static function clean_filename($filename) {
        $filename = mb_strtolower($filename);
        $filename = self::cyrillic_translit($filename);
        $filename = preg_replace('/[^ a-z0-9_\-\.,\(\)]/iu', '', $filename);
        $filename = str_replace(' ', '_', $filename);
        $filename = str_replace('__', '_', $filename);
        $filename = trim($filename, '_-.,');
        return $filename;
    }

    public static function cut_str_word($str, $length = 100, $ending = '...') {
        $return = mb_substr($str, 0, mb_strrpos(mb_substr($str, 0, $length, 'utf-8'), ' ', 'utf-8'), 'utf-8');
        if (mb_strlen($return) < mb_strlen($str)) {
            $return .= $ending;
        }
        return $return;
    }

    public static function encodeURI($url) {
        $unescaped = array(
            '%2D' => '-',
            '%5F' => '_',
            '%2E' => '.',
            '%21' => '!',
            '%7E' => '~',
            '%2A' => '*',
            '%27' => "'",
            '%28' => '(',
            '%29' => ')'
        );
        $reserved = array(
            '%3B' => ';',
            '%2C' => ',',
            '%2F' => '/',
            '%3F' => '?',
            '%3A' => ':',
            '%40' => '@',
            '%26' => '&',
            '%3D' => '=',
            '%2B' => '+',
            '%24' => '$'
        );
        $score = array(
            '%23' => '#'
        );
        return strtr(rawurlencode($url), array_merge($reserved, $unescaped, $score));
    }

    public static function cyrillic_translit($string) {
        $char_map = array(
            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'з' => 'z',
            'и' => 'i',
            'й' => 'i',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'x',
            'ы' => 'y',
            'э' => 'e',
            'А' => 'a',
            'Б' => 'b',
            'В' => 'v',
            'Г' => 'g',
            'Д' => 'd',
            'Е' => 'e',
            'Ё' => 'e',
            'З' => 'z',
            'И' => 'i',
            'Й' => 'j',
            'К' => 'k',
            'Л' => 'l',
            'М' => 'm',
            'Н' => 'n',
            'О' => 'o',
            'П' => 'p',
            'Р' => 'r',
            'С' => 's',
            'Т' => 't',
            'У' => 'u',
            'Ф' => 'f',
            'Х' => 'x',
            'Ы' => 'y',
            'Э' => 'e',
            'ж' => 'zh',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'sch',
            'ь' => '',
            'ъ' => '',
            'ю' => 'yu',
            'я' => 'ya',
            'Ж' => 'zh',
            'Ц' => 'c',
            'Ч' => 'ch',
            'Ш' => 'sh',
            'Щ' => 'sch',
            'Ь' => '',
            'Ъ' => '',
            'Ю' => 'yu',
            'Я' => 'ya',
            'ї' => 'i',
            'Ї' => 'i',
            'ґ' => 'g',
            'Ґ' => 'g',
            'є' => 'е',
            'Є' => 'е',
            'І' => 'i',
            'і' => 'i'
        );
        return strtr($string, $char_map);
    }

    public static function close_html_tags($html) {
        $single_tags = array('meta', 'img', 'br', 'link', 'area', 'input', 'hr', 'col', 'param', 'base');
        preg_match_all('~<([a-z0-9]+)(?: .*)?(?<![/|/ ])>~iU', $html, $result);
        $opened_tags = $result[1];
        preg_match_all('~</([a-z0-9]+)>~iU', $html, $result);
        $closed_tags = $result[1];
        $len_opened = count($opened_tags);
        if (count($closed_tags) == $len_opened) {
            return $html;
        }
        $opened_tags = array_reverse($opened_tags);
        for ($i = 0; $i < $len_opened; $i++) {
            if (!in_array($opened_tags[$i], $single_tags)) {
                if (FALSE !== ($key = array_search($opened_tags[$i], $closed_tags))) {
                    unset($closed_tags[$key]);
                } else {
                    $html .= '</'.$opened_tags[$i].'>';
                }
            }
        }
        return $html;
    }

    /**
     * Check if input string is a valid YouTube URL
     * and try to extract the YouTube Video ID from it.
     * @access private
     * @return mixed Returns YouTube Video ID, or (boolean) FALSE.
     */
    public static function parse_youtube_url_id($url) {
        $url = htmlspecialchars_decode($url);
        $pattern = '#^(?:https?://)?'; # Optional URL scheme. Either http or https.
        $pattern .= '(?:www\.)?'; # Optional www subdomain.
        $pattern .= '(?:'; # Group host alternatives:
        $pattern .= 'youtu\.be/'; # Either youtu.be,
        $pattern .= '|youtube\.com'; # or youtube.com
        $pattern .= '(?:'; # Group path alternatives:
        $pattern .= '/embed/'; # Either /embed/,
        $pattern .= '|/v/'; # or /v/,
        $pattern .= '|/watch\?v='; # or /watch?v=,
        $pattern .= '|/watch\?.+&v='; # or /watch?other_param&v=
        $pattern .= ')'; # End path alternatives.
        $pattern .= ')'; # End host alternatives.
        $pattern .= '([\w-]{11})'; # 11 characters (Length of Youtube video ids).
        $pattern .= '(?:.+)?$#x'; # Optional other ending URL parameters.
        preg_match($pattern, $url, $matches);
        return (isset($matches[1])) ? $matches[1] : FALSE;
    }

    public static function aasort(&$array, $key) {
        $sorter = array();
        $ret = array();
        reset($array);
        foreach ($array as $ii => $va) {
            $sorter[$ii] = $va[$key];
        }
        asort($sorter);
        foreach ($sorter as $ii => $va) {
            $ret[$ii] = $array[$ii];
        }
        $array = $ret;
    }

    public static function plural($num, $many, $one, $two) {
        $num = (int)abs($num);
        if ($num % 100 == 1 || ($num % 100 > 20) && ($num % 10 == 1)) return $one;
        if ($num % 100 == 2 || ($num % 100 > 20) && ($num % 10 == 2)) return $two;
        if ($num % 100 == 3 || ($num % 100 > 20) && ($num % 10 == 3)) return $two;
        if ($num % 100 == 4 || ($num % 100 > 20) && ($num % 10 == 4)) return $two;

        return $many;
    }

    public function version() {
        return $this->version;
    }

    public function set_error($str) {
        $this->error .= $str."\r\n";
    }

    public function get_error() {
        return $this->error;
    }

    /*
     * Admin Panel Users & Auth
     */
    public function enter_admin_panel($login, $password) {
        $stmt = $this->db->prepare("SELECT * FROM ".self::TABLE_PREFIX."users WHERE login=?");
        $stmt->execute(array($login));
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && mb_strtolower($user['password']) == strtolower(md5(md5($password).$login))) {
            $_SESSION['adminpanel'] = $user['login'];

            /*
            $this->send_email_smtp($this->get_settings('admin-email'), "Успешный вход в админ-панель на сайте ".$_SERVER['HTTP_HOST'], array(
                'IP' => $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
            ));
            */

            return TRUE;

        }

        $this->set_error('Неверный логин или пароль');
        $this->send_email_smtp($this->get_settings('admin-email'), "Попытка входа в админ-панель на сайте ".$_SERVER['HTTP_HOST'], array(
            'IP' => $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'],
            'LOGIN' => $login,
            'PASSWORD' => $password,
        ));
        return FALSE;


    }

    public static function exit_admin_panel() {
        unset($_SESSION['adminpanel']);
        return TRUE;
    }

    public static function is_admin_user() {
        return $_SESSION['adminpanel'] == 'admin';
    }

    public static function is_adminpanel() {
        return $_SESSION['adminpanel'];
    }

    public function update_user_password($login, $new_password) {
        $result = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."users SET password=? WHERE login=?")->execute(array(
            strtolower(md5(md5($new_password).$login)),
            $login
        ));
        return $result;
    }

    /*
     * Images
     */
    public static function resize($filename, $width = 0, $height = 0, $crop = FALSE) {
        if (!$width && !$height) return $filename;
        if (!$filename) return '';
        $width = $width ? $width : '0';
        $height = $height ? $height : '0';
        $crop = $crop ? 'c' : '';
        return 'cache/'.$width.'x'.$height.$crop.'.'.pathinfo($filename, PATHINFO_FILENAME).'.'.pathinfo($filename, PATHINFO_EXTENSION);
    }

    public function generate_unique_filename($path, $filename, $hash_filename = FALSE) {
        $name = strtolower(pathinfo($filename, PATHINFO_FILENAME));
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($hash_filename) {

            $filename = hash('sha256', $name).'.'.$ext;

            while (file_exists($path.'/'.$filename)) {

                preg_match('/^(.*)_([0-9]*)$/', $name, $m);
                if ($m[2]) {
                    $filename = hash('sha256', $m[1].'_'.mt_rand().mt_rand()).'.'.$ext;
                } else {
                    $filename = hash('sha256', $name.'_'.mt_rand().mt_rand()).'.'.$ext;
                }
                unset($m);
            }

        } else {

            while (file_exists($path.'/'.$filename)) {
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                preg_match('/^(.*)_([0-9]*)$/', $name, $m);
                if ($m[2]) {
                    $filename = $m[1].'_'.($m[2] + 1).'.'.$ext;
                } else {
                    $filename = $name.'_1'.'.'.$ext;
                }
                unset($m);
            }
        }


        return $filename;
    }

    public function upload_file($element = 'file', $ext = array(), $size_limit = '', $hash_filename = FALSE) {
        $good_extensions = $ext ? $ext : array(
            'pdf',
            'jpeg',
            'jpg',
            'gif',
            'png',
            'ico',
            'bmp',
            'zip',
            'rar',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'mp4',
            'webm',
            'ogv',
            'mp3',
            'ogg',
            'aac'
        );
        $size_limit = $size_limit ? $size_limit : 1048576 * 20; //Лимит 20МБ
        $file = $_FILES[$element]['name'];
        $filename = strtolower(pathinfo($_FILES[$element]['name'], PATHINFO_FILENAME));
        $extension = strtolower(pathinfo($_FILES[$element]['name'], PATHINFO_EXTENSION));
        $filename = $this->generate_unique_filename($this->downloads_folder, self::clean_filename($filename).'.'.$extension, $hash_filename);

        try {
            if ($_FILES[$element]['error'] != UPLOAD_ERR_OK) {
                throw new Exception('Не удалось загрузить файл '.$file);
            }
            if (!in_array($extension, $good_extensions)) {
                throw new Exception('Недопустимое расширение файла '.$file.'. Допустимые расширения: '.implode(', ', $good_extensions));
            }
            if ($_FILES[$element]['size'] > $size_limit) {
                throw new Exception('Размер файла '.$file.' превышает '.($size_limit / 1024 / 1024).'Мб. Используйте файл меньшего размера');
            }

            move_uploaded_file($_FILES[$element]['tmp_name'], $this->downloads_folder.'/'.$filename);

            if (!is_file($this->downloads_folder.'/'.$filename)) {
                throw new Exception('Не удалось загрузить файл '.$file);
            }
            @chmod($this->downloads_folder.'/'.$filename, 0755);

        } catch (Exception $e) {
            $this->set_error($e->getMessage());
            return '';
        }
        return $filename;
    }

    public function multi_upload_file($element = 'file', $ext = array(), $hash_filename = FALSE) {
        $good_extensions = $ext ? $ext : array(
            'pdf',
            'jpeg',
            'jpg',
            'gif',
            'png',
            'ico',
            'bmp',
            'zip',
            'rar',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'mp4',
            'webm',
            'ogv',
            'mp3',
            'ogg',
            'aac'
        );
        $size_limit = 1048576 * 20; //Лимит 20МБ

        $files = array();
        foreach ($_FILES[$element]['name'] as $key => $file) {
            $filename = strtolower(pathinfo($file, PATHINFO_FILENAME));
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $files[$key] = $this->generate_unique_filename($this->downloads_folder, self::clean_filename($filename).'.'.$extension, $hash_filename);

            try {
                if ($_FILES[$element]['error'][$key] != UPLOAD_ERR_OK) {
                    throw new Exception('Не удалось загрузить файл '.$file);
                }
                if (!in_array($extension, $good_extensions)) {
                    throw new Exception('Недопустимое расширение файла '.$file.'. Допустимые расширения: '.implode(', ', $good_extensions));
                }
                if ($_FILES[$element]['size'][$key] > $size_limit) {
                    throw new Exception('Размер файла '.$file.' превышает '.($size_limit / 1024 / 1024).'Мб. Используйте файл меньшего размера');
                }

                move_uploaded_file($_FILES[$element]['tmp_name'][$key], $this->downloads_folder.'/'.$files[$key]);

                if (!is_file($this->downloads_folder.'/'.$files[$key])) {
                    throw new Exception('Не удалось загрузить файл '.$file);
                }
                @chmod($this->downloads_folder.'/'.$files[$key], 0755);

            } catch (Exception $e) {
                $this->set_error($e->getMessage());
                unset($files[$key]);
            }
        }
        return $files;
    }

    public function delete_file($filename) {
        @unlink($this->downloads_folder.'/'.$filename);
    }


    public function upload_image($element = 'img', $width = 0, $height = 0, $crop = FALSE, $hash_filename = FALSE) {
        $good_extensions = array('jpeg', 'jpg', 'gif', 'png', 'ico', 'bmp');
        $size_limit = 1048576; //Лимит 1МБ
        $file = $_FILES[$element]['name'];
        $filename = strtolower(pathinfo($_FILES[$element]['name'], PATHINFO_FILENAME));
        $extension = strtolower(pathinfo($_FILES[$element]['name'], PATHINFO_EXTENSION));
        $image_name = self::clean_filename($filename).'.'.$extension;
        $image_name = $this->generate_unique_filename($this->images_downloads_folder, $image_name, $hash_filename);

        try {
            if ($_FILES[$element]['error'] != UPLOAD_ERR_OK) {
                throw new Exception('Не удалось загрузить файл '.$file);
            }
            if (!in_array($extension, $good_extensions)) {
                throw new Exception('Недопустимое расширение файла '.$file.'. Допустимые расширения: '.implode(', ', $good_extensions));
            }
            if ($_FILES[$element]['size'] > $size_limit) {
                throw new Exception('Размер файла '.$file.' превышает '.($size_limit / 1024 / 1024).'Мб. Используйте файл меньшего размера');
            }
            if ($width != 0 && $height != 0) {
                $image = new Imagick($_FILES[$element]['tmp_name']);
                $crop ? $image->cropthumbnailimage($width, $height) : $image->thumbnailimage($width, $height);
                $image->writeimage($this->images_downloads_folder.'/'.$image_name);
            } else {
                move_uploaded_file($_FILES[$element]['tmp_name'], $this->images_downloads_folder.'/'.$image_name);
            }
            if (!is_file($this->images_downloads_folder.'/'.$image_name)) {
                throw new Exception('Не удалось загрузить файл '.$file);
            }
            @chmod($this->images_downloads_folder.'/'.$image_name, 0755);

        } catch (Exception $e) {
            $this->set_error($e->getMessage());
            return '';
        }
        return $image_name;
    }

    public function multi_upload_image($element = 'img', $width = 0, $height = 0, $crop = FALS, $hash_filename = FALSE) {
        $good_extensions = array('jpeg', 'jpg', 'gif', 'png', 'ico', 'bmp');
        $size_limit = 1048576; //Лимит 1МБ

        $images = array();
        foreach ($_FILES[$element]['name'] as $key => $file) {
            $filename = strtolower(pathinfo($file, PATHINFO_FILENAME));
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $images[$key] = self::clean_filename($filename).'.'.$extension;
            $images[$key] = $this->generate_unique_filename($this->images_downloads_folder, $images[$key], $hash_filename);

            try {
                if ($_FILES[$element]['error'][$key] != UPLOAD_ERR_OK) {
                    throw new Exception('Не удалось загрузить файл '.$file);
                }
                if (!in_array($extension, $good_extensions)) {
                    throw new Exception('Недопустимое расширение файла '.$file.'. Допустимые расширения: '.implode(', ', $good_extensions));
                }
                if ($_FILES[$element]['size'][$key] > $size_limit) {
                    throw new Exception('Размер файла '.$file.' превышает '.($size_limit / 1024 / 1024).'Мб. Используйте файл меньшего размера');
                }
                if ($width != 0 && $height != 0) {
                    $image = new Imagick($_FILES[$element]['tmp_name'][$key]);
                    $crop ? $image->cropthumbnailimage($width, $height) : $image->thumbnailimage($width, $height);
                    $image->writeimage($this->images_downloads_folder.'/'.$images[$key]);
                } else {
                    move_uploaded_file($_FILES[$element]['tmp_name'][$key], $this->images_downloads_folder.'/'.$images[$key]);
                }
                if (!is_file($this->images_downloads_folder.'/'.$images[$key])) {
                    throw new Exception('Не удалось загрузить файл '.$file);
                }
                @chmod($this->images_downloads_folder.'/'.$images[$key], 0755);
            } catch (Exception $e) {
                $this->set_error($e->getMessage());
                unset($images[$key]);
            }
        }
        return $images;
    }


    public function delete_image($filename) {
        @unlink($this->images_downloads_folder.'/'.$filename);
        $this->clear_images_cache();
    }

    public function upload_favicon($element = 'favicon') {
        try {
            if ($_FILES[$element]['error'] != UPLOAD_ERR_OK) {
                throw new Exception('Не удалось загрузить файл.');
            }
            $image = new Imagick($_FILES[$element]['tmp_name']);
            $image->cropthumbnailimage(32, 32);
            $image->setFormat('ico');
            $image->writeimage($this->images_downloads_folder.'/favicon.ico');
            if (!is_file($this->images_downloads_folder.'/favicon.ico')) {
                throw new Exception('Не удалось создать favicon');
            }
            @chmod($this->images_downloads_folder.'/favicon.ico', 0755);
        } catch (Exception $e) {
            $this->set_error($e->getMessage());
            return FALSE;
        }
        return TRUE;
    }

    public function upload_logo($element = 'logo') {
        try {
            if ($_FILES[$element]['error'] != UPLOAD_ERR_OK) {
                throw new Exception('Не удалось загрузить файл.');
            }
            $image = new Imagick($_FILES[$element]['tmp_name']);
            $image->setFormat('png');
            $image->writeimage($this->images_downloads_folder.'/logo.png');
            if (!is_file($this->images_downloads_folder.'/logo.png')) {
                throw new Exception('Не удалось создать logo');
            }
            @chmod($this->images_downloads_folder.'/logo.png', 0755);
        } catch (Exception $e) {
            $this->set_error($e->getMessage());
            return FALSE;
        }
        return TRUE;
    }

    public function clear_images_cache() {
        $files = glob($this->images_downloads_folder.'/cache/*.*');
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    /*
     * PAGES
     */
    public function get_parent_page_nodes($type) {
        $types = array(self::clean_url($type));

        if (API::$pages[$type]['parent']) {
            $types[] = self::clean_url(API::$pages[$type]['parent']);
        }
        if (API::$pages[API::$pages[$type]['parent']]['parent']) {
            $types[] = self::clean_url(API::$pages[API::$pages[$type]['parent']]['parent']);
        }

        $types = implode("','", $types);
        $data = $this->db->query("SELECT node1, node2, node_text1, node_text2 FROM ".self::TABLE_PREFIX."pages WHERE `type` IN ('{$types}') GROUP BY node1, node2")->fetchAll(PDO::FETCH_ASSOC);

        $tree = array();
        foreach ($data as $d) {
            if ($node1 = $d['node1']) {
                $tree[$node1]['url'] = $d['node1'];
                $tree[$node1]['name'] = $d['node_text1'];
                if ($node2 = $d['node2']) {
                    $tree[$node1]['children'][$node2]['url'] = $d['node2'];
                    $tree[$node1]['children'][$node2]['name'] = $d['node_text2'];
                }
            }
        }

        return $tree;
    }

    public function get_page_by_url($url) {
        $url = urldecode($url);
        $url = preg_replace('/(https?:\/\/.*?)\//', '', $url);
        $url = trim($url, '/ ');
        $url = explode('/', $url);
        return $this->get_page($url[0], $url[1], $url[2]);
    }

    public function get_page($node1 = '', $node2 = '', $node3 = '') {
        $node1 = self::clean_url($node1);
        $node2 = self::clean_url($node2);
        $node3 = self::clean_url($node3);
        $visible_only = self::is_adminpanel() ? "" : "AND visible=1";
        $data = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."pages WHERE node1='$node1' AND node2='$node2' AND node3='$node3' $visible_only")->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $data['url'] = trim(implode('/', array($data['node1'], $data['node2'], $data['node3'])), '/');
            $data['elements'] = unserialize($data['elements']);
            $data['custom'] = unserialize($data['custom']);

        }
        return $data;
    }

    public function get_child_pages($node1, $node2 = '') {
        $node1 = self::clean_url($node1);
        $node2 = self::clean_url($node2);
        $visible_only = self::is_adminpanel() ? "" : "AND visible=1";
        if ($node1 && $node2) {
            $data = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."pages WHERE node1='{$node1}' AND node2='{$node2}' AND node3!='' {$visible_only} ORDER BY date_created DESC")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $data = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."pages WHERE node1='{$node1}' AND node2!='' {$visible_only} ORDER BY date_created DESC")->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ($data as &$d) {
            $d['url'] = trim(implode('/', array($d['node1'], $d['node2'], $d['node3'])), '/');
            $d['elements'] = unserialize($d['elements']);
            $d['custom'] = unserialize($d['custom']);
        }
        return $data;
    }

    public function get_pages_by_nodes($node1 = NULL, $node2 = NULL, $node3 = NULL, $limit1 = 0, $limit2 = 1000, $order_by = 'date_created DESC') {
        $node1 = ($node1 !== NULL) ? "node1='".self::clean_url($node1)."'" : "1";
        $node2 = ($node2 !== NULL) ? "node2='".self::clean_url($node2)."'" : "1";
        $node3 = ($node3 !== NULL) ? "node3='".self::clean_url($node3)."'" : "1";
        $visible_only = self::is_adminpanel() ? "" : "AND visible=1";
        $data = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."pages WHERE {$node1} AND {$node2} AND {$node3} {$visible_only} ORDER BY {$order_by} LIMIT ".intval($limit1).",".intval($limit2))->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$d) {
            $d['url'] = trim(implode('/', array($d['node1'], $d['node2'], $d['node3'])), '/');
            $d['elements'] = unserialize($d['elements']);
            $d['custom'] = unserialize($d['custom']);
        }
        return $data;
    }

    public function get_pages($type = '', $fixed = NULL, $limit1 = 0, $limit2 = 1000, $order_by = 'date_created DESC') {
        $where = "";
        if ($type) {
            $where .= " AND `type`='".self::clean_url($type)."'";
        }
        if ($fixed !== NULL) {
            $where .= " AND `fixed`=".($fixed ? "1" : "0");
        }
        $visible_only = self::is_adminpanel() ? "" : "AND visible=1";

        $data = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."pages WHERE 1 {$where} {$visible_only} ORDER BY {$order_by} LIMIT ".intval($limit1).",".intval($limit2))->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as &$d) {
            $d['url'] = trim(implode('/', array($d['node1'], $d['node2'], $d['node3'])), '/');
            $d['elements'] = unserialize($d['elements']);
            $d['custom'] = unserialize($d['custom']);
        }
        return $data;
    }

    public function insert_page($data) {
        if ($data['custom']) {
            foreach ($data['custom'] as $key1 => &$val1) {
                if (self::$pages[$data['type']]['custom'][$key1] == 'varchar') {
                    $val1 = self::clean_varchar($val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'text') {
                    $val1 = self::clean_varchar($val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'url') {
                    $val1 = self::clean_url($val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'code') {
                    continue;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'int') {
                    $val1 = preg_replace('/\D/', '', $val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'html') {
                    continue;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'datetime') {
                    continue;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'bool') {
                    $val1 = $val1 ? 1 : 0;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'image' && $_FILES[$key1]['name']) {
                    $val1 = $this->upload_image($key1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'images' && $_FILES[$key1]['name'][0]) {
                    $val1 = $this->multi_upload_image($key1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'file' && $_FILES[$key1]['name']) {
                    $val1 = $this->upload_file($key1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'files' && $_FILES[$key1]['name'][0]) {
                    $val1 = $this->multi_upload_file($key1);
                } elseif (is_array(self::$pages[$data['type']]['custom'][$key1])) {
                    foreach ($val1 as &$val2) {
                        foreach ($val2 as $key3 => &$val3) {
                            if (self::$pages[$data['type']]['custom'][$key1][$key3] == 'varchar') {
                                $val3 = self::clean_varchar($val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'text') {
                                $val3 = self::clean_varchar($val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'url') {
                                $val3 = self::clean_url($val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'code') {
                                continue;
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'int') {
                                $val3 = preg_replace('/\D/', '', $val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'datetime') {
                                continue;
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'html') {
                                continue;
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'bool') {
                                $val3 = $val3 ? 1 : 0;
                            } else {
                                unset($val3);
                            }
                        }
                    }
                } else {
                    unset($data['custom'][$key1]);
                }
            }
            self::clean_array($data['custom'], '');
        }

        if ($this->error) return FALSE;

        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."pages (
                node1,
                node2,
                node3,
                node_text1,
                node_text2,
                node_text3,
                type,
                fixed,
                title,
                description,
                keywords,
                text,
                visible,
                date_created,
                date_lastmod,
                elements,
                custom
            ) VALUES (
                :node1,
                :node2,
                :node3,
                :node_text1,
                :node_text2,
                :node_text3,
                :type,
                :fixed,
                :title,
                :description,
                :keywords,
                :text,
                :visible,
                :date_created,
                :date_lastmod,
                :elements,
                :custom
            )");

        $result = $stmt->execute(array(
            ':node1' => self::clean_url(self::cyrillic_translit($data['node_text1'])),
            ':node2' => self::clean_url(self::cyrillic_translit($data['node_text2'])),
            ':node3' => self::clean_url(self::cyrillic_translit($data['node_text3'])),
            ':node_text1' => self::clean_varchar($data['node_text1']),
            ':node_text2' => self::clean_varchar($data['node_text2']),
            ':node_text3' => self::clean_varchar($data['node_text3']),
            ':type' => self::clean_url($data['type']),
            ':fixed' => 0,
            ':title' => self::clean_varchar($data['title']),
            ':description' => self::clean_varchar($data['description']),
            ':keywords' => self::clean_varchar($data['keywords']),
            ':text' => $data['text'],
            ':visible' => $data['visible'] ? 1 : 0,
            ':date_created' => $data['date_created'] ? date('Y-m-d H:i:s', strtotime($data['date_created'])) : date('Y-m-d H:i:s'),
            ':date_lastmod' => $data['date_lastmod'] ? date('Y-m-d H:i:s', strtotime($data['date_lastmod'])) : date('Y-m-d H:i:s'),
            ':elements' => $data['elements'] ? serialize($data['elements']) : '',
            ':custom' => $data['custom'] ? serialize($data['custom']) : '',
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            if ($e[0] == 23000) {
                $this->set_error('Страница с таким адресом уже существует');
            } else {
                $this->set_error($e[2]);
            }
        }
        return $result;
    }

    public function update_page($data) {
        $node1 = self::clean_url(self::cyrillic_translit($data['node_text1']));
        $node2 = self::clean_url(self::cyrillic_translit($data['node_text2']));
        $node3 = self::clean_url(self::cyrillic_translit($data['node_text3']));
        $node_text1 = self::clean_varchar($data['node_text1']);
        $node_text2 = self::clean_varchar($data['node_text2']);
        $node_text3 = self::clean_varchar($data['node_text3']);
        $old_node1 = $data['old_node1'];
        $old_node2 = $data['old_node2'];
        $old_node3 = $data['old_node3'];
        $type = self::clean_url($data['type']);
        $title = self::clean_varchar($data['title']);
        $description = self::clean_varchar($data['description']);

        if (!$old_node1 && !$old_node2 && !$old_node3) {
            $old_node1 = $node1;
            $old_node2 = $node2;
            $old_node3 = $node3;
        }

        if ($data['type'] == 'main') {
            $old_node1 = $node1 = $old_node2 = $node2 = $old_node3 = $node3 = '';
        }

        $old_page = $this->get_page($old_node1, $old_node2, $old_node3);
        if (!$old_page) {
            $this->set_error('Страницы с таким адресом не существует');
            return FALSE;
        }

        if (($old_node1 && !$node1) || ($old_node2 && !$node2) || ($old_node3 && !$node3)) {
            $this->set_error('Укажите адрес страницы');
            return FALSE;
        }

        if (!$title) {
            $this->set_error('Укажите заголовок страницы');
            return FALSE;
        }

        if (!$description) {
            $this->set_error('Укажите описание страницы');
            return FALSE;
        }

        if ($data['custom']) {
            foreach ($data['custom'] as $key1 => &$val1) {
                if (self::$pages[$data['type']]['custom'][$key1] == 'varchar') {
                    $val1 = self::clean_varchar($val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'text') {
                    $val1 = self::clean_varchar($val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'url') {
                    $val1 = self::clean_url($val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'code') {
                    continue;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'int') {
                    $val1 = preg_replace('/\D/', '', $val1);
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'datetime') {
                    continue;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'html') {
                    continue;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'bool') {
                    $val1 = $val1 ? 1 : 0;
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'image') {
                    $val1 = self::clean_filename($val1);
                    if ($_FILES[$key1]['name']) {
                        $val1 = $this->upload_image($key1);
                    }
                    if ($old_page['custom'][$key1] != $val1) {
                        $this->delete_image($old_page['custom'][$key1]);
                    }
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'images') {
                    foreach ($val1 as $k => $v) {
                        if ($v) {
                            $val1[$k] = self::clean_filename($v);
                        } else {
                            unset($val1[$k]);
                        }
                    }
                    if ($_FILES[$key1]['name'][0]) {
                        $val1 = array_merge((array)$val1, (array)$this->multi_upload_image($key1));
                    }

                    foreach (array_diff((array)$old_page['custom'][$key1], (array)$val1) as $v) {
                        $this->delete_image($v);
                    }
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'file') {
                    $val1 = self::clean_filename($val1);
                    if ($_FILES[$key1]['name']) {
                        $val1 = $this->upload_file($key1);
                    }
                    if ($old_page['custom'][$key1] != $val1) {
                        $this->delete_file($old_page['custom'][$key1]);
                    }
                } elseif (self::$pages[$data['type']]['custom'][$key1] == 'files') {
                    foreach ($val1 as $k => $v) {
                        if ($v) {
                            $val1[$k] = self::clean_filename($v);
                        } else {
                            unset($val1[$k]);
                        }
                    }
                    if ($_FILES[$key1]['name'][0]) {
                        $val1 = array_merge((array)$val1, (array)$this->multi_upload_file($key1));
                    }
                    foreach (array_diff((array)$old_page['custom'][$key1], (array)$val1) as $v) {
                        $this->delete_file($v);
                    }
                } elseif (is_array(self::$pages[$data['type']]['custom'][$key1])) {
                    foreach ($val1 as &$val2) {
                        foreach ($val2 as $key3 => &$val3) {
                            if (self::$pages[$data['type']]['custom'][$key1][$key3] == 'varchar') {
                                $val3 = self::clean_varchar($val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'text') {
                                $val3 = self::clean_varchar($val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'url') {
                                $val3 = self::clean_url($val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'code') {
                                continue;
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'int') {
                                $val3 = preg_replace('/\D/', '', $val3);
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'datetime') {
                                continue;
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'html') {
                                continue;
                            } elseif (self::$pages[$data['type']]['custom'][$key1][$key3] == 'bool') {
                                $val3 = $val3 ? 1 : 0;
                            } else {
                                unset($val3);
                            }
                        }
                    }
                } else {
                    unset($data['custom'][$key1]);
                }
            }
            self::clean_array($data['custom'], '');
        }

        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."pages SET
                node1=:node1,
                node2=:node2,
                node3=:node3,
                node_text1=:node_text1,
                node_text2=:node_text2,
                node_text3=:node_text3,
                type=:type,
                title=:title,
                description=:description,
                keywords=:keywords,
                text=:text,
                visible=:visible,
                date_created=:date_created,
                date_lastmod=:date_lastmod,
                elements=:elements,
                custom=:custom
            WHERE
                node1=:old_node1 AND
                node2=:old_node2 AND
                node3=:old_node3
            ");

        $result = $stmt->execute(array(
            ':node1' => $node1,
            ':node2' => $node2,
            ':node3' => $node3,
            ':node_text1' => $node_text1,
            ':node_text2' => $node_text2,
            ':node_text3' => $node_text3,
            ':old_node1' => $old_node1,
            ':old_node2' => $old_node2,
            ':old_node3' => $old_node3,
            ':type' => $type,
            ':title' => $title,
            ':description' => $description,
            ':keywords' => self::clean_varchar($data['keywords']),
            ':text' => $data['text'],
            ':visible' => $data['visible'] ? 1 : 0,
            ':date_created' => $data['date_created'] ? date('Y-m-d H:i:s', strtotime($data['date_created'])) : date('Y-m-d H:i:s'),
            ':date_lastmod' => $data['date_lastmod'] ? date('Y-m-d H:i:s', strtotime($data['date_lastmod'])) : date('Y-m-d H:i:s'),
            ':elements' => $data['elements'] ? serialize($data['elements']) : '',
            ':custom' => $data['custom'] ? serialize($data['custom']) : '',
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            if ($e[0] == 23000) {
                $this->set_error('Страница с таким адресом уже существует');
            } else {
                $this->set_error($e[2]);
            }
        } else {
            if ($node1 && $old_node1 && $node1 != $old_node1 && !$node2 && !$node3) {
                $this->db->prepare("UPDATE ".self::TABLE_PREFIX."pages
                SET
                    node1=:node1, node_text1=:node_text1
                WHERE
                    node1=:old_node1 AND fixed=0
                ")->execute(array(
                    ':node1' => $node1,
                    ':node_text1' => $node_text1,
                    ':old_node1' => $old_node1,
                ));
            } elseif ($node2 && $old_node2 && $node2 != $old_node2 && !$node3) {
                $this->db->prepare("UPDATE ".self::TABLE_PREFIX."pages
                SET
                    node2=:node2, node_text2=:node_text2
                WHERE
                    node1=:old_node1 AND node2=:old_node2 AND fixed=0
                ")->execute(array(
                    ':node2' => $node2,
                    ':node_text2' => $node_text2,
                    ':old_node1' => $old_node1,
                    ':old_node2' => $old_node2,
                ));
            }
        }
        return $result;
    }

    public function delete_page($node1, $node2 = '', $node3 = '') {
        $node1 = self::clean_url($node1);
        $node2 = self::clean_url($node2);
        $node3 = self::clean_url($node3);
        $data = $this->get_page($node1, $node2, $node3);
        if ($data) {
            $result = $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."pages
            WHERE
                node1=:node1 AND
                node2=:node2 AND
                node3=:node3 AND
                fixed=0
            ")->execute(array(
                ':node1' => $node1,
                ':node2' => $node2,
                ':node3' => $node3,
            ));

            if ($result) {
                if ($data['custom']) {
                    foreach ($data['custom'] as $key => $value) {
                        if (self::$pages[$data['type']]['custom'][$key] == 'image') {
                            $this->delete_image($value);
                        } elseif (self::$pages[$data['type']]['custom'][$key] == 'images') {
                            foreach ($value as $v) {
                                $this->delete_image($v);
                            }
                        } elseif (self::$pages[$data['type']]['custom'][$key] == 'file') {
                            $this->delete_file($value);
                        } elseif (self::$pages[$data['type']]['custom'][$key] == 'files') {
                            foreach ($value as $v) {
                                $this->delete_file($v);
                            }
                        }
                    }
                }
                if (($node1 && !$node2 && !$node3) || ($node1 && $node2 && !$node3)) {
                    $children = $this->get_child_pages($node1, $node2);
                    foreach ($children as $child) {
                        $this->delete_page($child['node1'], $child['node2'], $child['node3']);
                    }
                }
            } else {
                $this->set_error(end($this->db->errorInfo()));
            }
        }
        return $result;
    }

    /*
     * ELEMENTS
     */
    public function get_element($id, $type = '') {
        $type = $type ? "AND `type`= '".self::clean_url($type)."'" : "";
        $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."elements WHERE id=".intval($id)." ".$type)->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['data'] = unserialize($result['data']);
        }
        return $result;
    }

    public function get_element_data($id, $type = '') {
        $type = $type ? "AND `type`= '".self::clean_url($type)."'" : "";
        $result = $this->db->query("SELECT data FROM ".self::TABLE_PREFIX."elements WHERE id=".intval($id)." ".$type)->fetchColumn();
        if ($result) {
            $result = unserialize($result);
        }
        return $result;
    }

    public function get_elements_data($type = '', $limit1 = 0, $limit2 = 1000, $order = "ASC") {
        $order = $order == "DESC" ? "DESC" : "ASC";
        $type = $type ? "AND `type`='".self::clean_url($type)."'" : "";
        $visible_only = self::is_adminpanel() ? "" : "AND visible=1";
        $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."elements WHERE 1=1 $type $visible_only ORDER BY id $order LIMIT ".intval($limit1).",".intval($limit2))->fetchAll(PDO::FETCH_ASSOC);
        $return = array();
        foreach ($result as $r) {
            $return[$r['id']] = unserialize($r['data']);
        }
        return $return;
    }

    public function get_elements($type = '', $limit1 = 0, $limit2 = 1000, $order = "ASC") {
        $order = $order == "DESC" ? "DESC" : "ASC";
        $type = $type ? "AND `type`='".self::clean_url($type)."'" : "";
        $visible_only = self::is_adminpanel() ? "" : "AND visible=1";
        $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."elements WHERE 1=1 $type $visible_only ORDER BY id $order LIMIT ".intval($limit1).",".intval($limit2))->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as &$r) {
            $r['data'] = unserialize($r['data']);
            foreach ($r['data'] as $k => $v) {
                $r[$k] = $v;
            }
            unset($r['data']);
        }
        return $result;
    }

    public function insert_element($post) {
        $type = self::clean_url($post['type']);
        if (!$element_fields = self::$elements[$type]) {
            return FALSE;
        }
        foreach ($post['data'] as $key => &$value) {
            if ($element_fields[$key] == 'varchar') {
                $value = self::clean_varchar($value);
            } elseif ($element_fields[$key] == 'text') {
                $value = self::clean_varchar($value);
            } elseif ($element_fields[$key] == 'url') {
                $value = self::clean_url($value);
            } elseif ($element_fields[$key] == 'code') {
                continue;
            } elseif ($element_fields[$key] == 'int') {
                $value = preg_replace('/\D/', '', $value);
            } elseif ($element_fields[$key] == 'datetime') {
                continue;
            } elseif ($element_fields[$key] == 'html') {
                continue;
            } elseif ($element_fields[$key] == 'image') {
                $value = $this->upload_image($key);
            } elseif ($element_fields[$key] == 'images') {
                $value = $this->multi_upload_image($key);
            } elseif ($element_fields[$key] == 'file') {
                $value = $this->upload_file($key);
            } elseif ($element_fields[$key] == 'files') {
                $value = $this->multi_upload_file($key);
            } elseif ($element_fields[$key] == 'bool') {
                $value = $value ? 1 : 0;
            } else {
                unset($post['data'][$key]);
            }
        }
        self::clean_array($post['data'], '');

        if ($this->error) return FALSE;

        $result = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."elements SET type=?, data=?, visible=?")->execute(array(
            $type,
            serialize($post['data']),
            ($post['visible'] ? 1 : 0),
        ));

        if ($result) {
            return $this->db->lastInsertId();
        } else {
            return FALSE;
        }

    }

    public function update_element($post) {
        if (!$old_element = $this->get_element($post['id'])) {
            return FALSE;
        }
        if (!$element_fields = self::$elements[$old_element['type']]) {
            return FALSE;
        }
        foreach ($post['data'] as $key => &$value) {
            if ($element_fields[$key] == 'varchar') {
                $value = self::clean_varchar($value);
            } elseif ($element_fields[$key] == 'text') {
                $value = self::clean_varchar($value);
            } elseif ($element_fields[$key] == 'url') {
                $value = self::clean_url($value);
            } elseif ($element_fields[$key] == 'code') {
                $value = $value;
            } elseif ($element_fields[$key] == 'int') {
                $value = preg_replace('/\D/', '', $value);
            } elseif ($element_fields[$key] == 'datetime') {
                $value = $value;
            } elseif ($element_fields[$key] == 'html') {
                $value = $value;
            } elseif ($element_fields[$key] == 'image') {
                $value = self::clean_filename($value);
                if ($_FILES[$key]['name']) {
                    $value = $this->upload_image($key);
                }
                if ($old_element['data'][$key] != $value) {
                    $this->delete_image($old_element['data'][$key]);
                }
            } elseif ($element_fields[$key] == 'images') {
                foreach ($value as $k => $v) {
                    if ($v) {
                        $value[$k] = self::clean_filename($v);
                    } else {
                        unset($value[$k]);
                    }
                }
                if ($_FILES[$key]['name'][0]) {
                    $value = array_merge((array)$value, (array)$this->multi_upload_image($key));
                }
                foreach (array_diff((array)$old_element['data'][$key], (array)$value) as $v) {
                    $this->delete_image($v);
                }
            } elseif ($element_fields[$key] == 'file') {
                $value = self::clean_filename($value);
                if ($_FILES[$key]['name']) {
                    $value = $this->upload_file($key);
                }
                if ($old_element['data'][$key] != $value) {
                    $this->delete_file($old_element['data'][$key]);
                }
            } elseif ($element_fields[$key] == 'files') {
                foreach ($value as $k => $v) {
                    if ($v) {
                        $value[$k] = self::clean_filename($v);
                    } else {
                        unset($value[$k]);
                    }
                }
                if ($_FILES[$key]['name'][0]) {
                    $value = array_merge((array)$value, (array)$this->multi_upload_file($key));
                }
                foreach (array_diff((array)$old_element['data'][$key], (array)$value) as $v) {
                    $this->delete_file($v);
                }
            } elseif ($element_fields[$key] == 'bool') {
                $value = $value ? 1 : 0;
            } else {
                unset($post['data'][$key]);
            }
        }
        self::clean_array($post['data'], '');
        $result = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."elements SET data=?, visible=? WHERE id=?")->execute(array(
            serialize($post['data']),
            ($post['visible'] ? 1 : 0),
            $post['id']
        ));
        return $result;
    }

    public function update_element_visibility($id, $visible) {
        $e = $this->get_element($id);
        if ($e) {
            return $this->db->prepare("UPDATE ".self::TABLE_PREFIX."elements SET data=?, visible=? WHERE id=?")->execute(array(
                serialize($e['data']),
                ($visible ? 1 : 0),
                $id
            ));
        }
        return FALSE;
    }

    public function delete_element($id) {
        $data = $this->get_element($id);
        $result = $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."elements WHERE id=:id")->execute(array(':id' => $data['id']));
        if ($data && $result) {
            $this->delete_image($data['image']);
        }
        if ($data && $result) {
            foreach ($data['data'] as $key => $value) {
                if (self::$elements[$data['type']][$key] == 'image') {
                    $this->delete_image($value);
                } elseif (self::$pages[$data['type']]['custom'][$key] == 'images') {
                    foreach ($value as $v) {
                        $this->delete_image($v);
                    }
                } elseif (self::$elements[$data['type']][$key] == 'file') {
                    $this->delete_file($value);
                } elseif (self::$pages[$data['type']]['custom'][$key] == 'files') {
                    foreach ($value as $v) {
                        $this->delete_file($v);
                    }
                }
            }
        }
        return $result;
    }

    /*
     * TEXT BLOCKS
     */
    public function block($id) {
        return $this->db->query("SELECT value FROM ".self::TABLE_PREFIX."blocks WHERE id=".intval($id))->fetchColumn();
    }

    public function get_block($id) {
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."blocks WHERE id=".intval($id))->fetch(PDO::FETCH_ASSOC);
    }

    public function update_block($id, $value) {
        return $this->db->prepare("UPDATE ".self::TABLE_PREFIX."blocks SET value=? WHERE id=?")->execute(array(
            $value,
            intval($id),
        ));
    }

    /*
     * SITE SETTINGS
     */
    public function get_settings($param = '') {
        if (!$this->settings) {
            $r = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."settings WHERE id=1")->fetch(PDO::FETCH_ASSOC);
            $this->settings = unserialize($r['value']);
            $this->version = (int)$r['version'];
        }
        if ($param) {
            return $this->settings[$param];
        } else {
            return (array)$this->settings;
        }
    }

    public function update_settings($data) {
        if ($_FILES['favicon']['name']) {
            $this->upload_favicon();
        }
        if ($_FILES['logo']['name']) {
            $this->upload_logo();
        }

        if ($this->is_admin_user()) {
            $this->settings = array(
                'admin-email' => $data['admin-email'], 'head-code' => $data['head-code'],
                'body-code' => $data['body-code']
            );
        } else {
            $this->settings = array('head-code' => $data['head-code'], 'body-code' => $data['body-code']);
        }

        $this->db->prepare("UPDATE ".self::TABLE_PREFIX."settings SET value=:value, version=version+1 WHERE id=1")->execute(array(':value' => serialize($this->settings)));
    }

    public static function check_parrotify_captcha() {
        $c = curl_init("http://api.parrotify.com/validate");
        curl_setopt($c, CURLOPT_POST, TRUE);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query(array(
            'captcha[value]' => $_REQUEST['captcha_name'],
            'captcha[key]' => $_COOKIE['_cpathca'],
        )));
        $result = curl_exec($c);
        curl_close($c);
        return (bool)$result;
    }


    public function send_email($to, $subject, $body, $attachments = array()) {
        if (!is_array($to)) {
            $to = explode(',', $to);
        }
        $logo = 'http://'.$_SERVER['HTTP_HOST'].'/'.self::IMAGES_DOWNLOADS_DIR.'/'.'logo.png?'.$this->version();
        $m = "
            <table width='100%' border='0' align='center' cellspacing='0' cellpadding='0' style='border-collapse: collapse; background-color:#f8f8f8;'>
            <tbody><tr><td width='100%' valign='middle' style='text-align: center; background-color:#f8f8f8;'>
                <table width='70%' border='0' align='center' cellspacing='0' cellpadding='0' style='border-collapse: collapse; background-color:#fff; border-bottom: 1px solid #dddddd; margin:15px auto; min-width: 250px;'>
                <tbody><tr><td width='100%' valign='middle' align='center' style='text-align: center; background-color:#fff; padding:10px 20px;'>
                <img height='50' border='0' alt='' src='{$logo}'>
                </td></tr>
            ";

        if (is_array($body)) {
            foreach ($body as $key => $value) {
                $m .= "
                <tr><td width='100%' valign='middle' align='center' style='font-family:Arial; font-size:14px; text-align:left; padding:10px 20px;'>
                <div style='border-bottom:1px #f1f1f1 solid; font-weight:bold; color:#333; margin-top:5px;'><b>{$key}</b></div>
                <div style='color:#666; margin-bottom:5px;'>{$value}</div>
                </td></tr>";
            }
        } else {
            $m .= "
                <tr><td width='100%' valign='middle' align='center' style='font-family:Arial; font-size:14px; text-align:left; padding:10px 20px; color:#666;'>
                    {$body}
                </td></tr>";
        }

        $m .= "<tr><td width='100%' valign='middle' align='center' style='font-family:Arial;font-size:10px;text-align:left;padding:20px;color:#999;text-align:center;'>
                Вы получили это письмо в ответ на действие на сайте ".$_SERVER['HTTP_HOST']."<br>
                Если вы не производили никаких действий на этом сайте и это письмо доставлено вам по ошибке - просто проигнорируйте его.<br>
                Чтобы сообщить об ошибке либо отписаться от уведомлений нажмите на <a href='mailto:".$this->get_settings('admin-email')."'>ссылку</a>
            </td></tr>";

        $m .= "</tbody></table>
            </td></tr>
            </tbody></table>
            ";
        $body = $m;
        $body_alt = is_array($body) ? print_r($body, 1) : $body;

        $mailer = new PHPMailer;
        $mailer->CharSet = 'UTF-8';
        $mailer->setLanguage('ru');
        $mailer->IsHTML(TRUE);
        $mailer->setFrom(self::MAIL_FROM_EMAIL, self::MAIL_FROM_NAME);
        $mailer->addReplyTo(self::MAIL_REPLY_TO_EMAIL, self::MAIL_REPLY_TO_NAME);
        foreach ($to as $email) {
            $mailer->AddAddress($email); // Add a recipient
        }
        $mailer->Subject = $subject;
        $mailer->AltBody = $body_alt;
        $mailer->msgHTML($body);

        if (isset($attachments['name'])) {
            if (is_array($attachments['name'])) {
                foreach ($attachments['name'] as $k => $name) {
                    if ($attachments['error'][$k] == UPLOAD_ERR_OK) {
                        $mailer->addAttachment($attachments['tmp_name'][$k], $name);
                    }
                }
            } elseif ($attachments['name'] && $attachments['error'] == UPLOAD_ERR_OK) {
                $mailer->addAttachment($attachments['tmp_name'], $attachments['name']);
            }
        }

        $r = $mailer->send();
        if (!$r) {
            $this->set_error($mailer->ErrorInfo);
        }
        return $r;
    }

    public function send_email_smtp($to, $subject, $body, $attachments = array()) {
        if (!is_array($to)) {
            $to = explode(',', $to);
        }
        $logo = 'http://'.$_SERVER['HTTP_HOST'].'/'.self::IMAGES_DOWNLOADS_DIR.'/'.'logo.png?'.$this->version();

        $m = "
            <table width='100%' border='0' align='center' cellspacing='0' cellpadding='0' style='border-collapse: collapse; background-color:#f8f8f8;'>
            <tbody><tr><td width='100%' valign='middle' style='text-align: center; background-color:#f8f8f8;'>
                <table width='70%' border='0' align='center' cellspacing='0' cellpadding='0' style='border-collapse: collapse; background-color:#fff; border-bottom: 1px solid #dddddd; margin:15px auto; min-width: 250px;'>
                <tbody><tr><td width='100%' valign='middle' align='center' style='text-align: center; background-color:#fff; padding:10px 20px;'>
                <img height='50' border='0' alt='' src='{$logo}'>
                </td></tr>
            ";

        if (is_array($body)) {
            foreach ($body as $key => $value) {
                $m .= "
                <tr><td width='100%' valign='middle' align='center' style='font-family:Arial; font-size:14px; text-align:left; padding:10px 20px;'>
                <div style='border-bottom:1px #f1f1f1 solid; font-weight:bold; color:#333; margin-top:5px;'><b>{$key}</b></div>
                <div style='color:#666; margin-bottom:5px;'>{$value}</div>
                </td></tr>";
            }
        } else {
            $m .= "
                <tr><td width='100%' valign='middle' align='center' style='font-family:Arial; font-size:14px; text-align:left; padding:10px 20px; color:#666;'>
                    {$body}
                </td></tr>";
        }

        $m .= "<tr><td width='100%' valign='middle' align='center' style='font-family:Arial;font-size:10px;text-align:left;padding:20px;color:#999;text-align:center;'>
                Вы получили это письмо в ответ на действие на сайте ".$_SERVER['HTTP_HOST']."<br>
                Если вы не производили никаких действий на этом сайте и это письмо доставлено вам по ошибке - просто проигнорируйте его.<br>
                Чтобы сообщить об ошибке либо отписаться от уведомлений нажмите на <a href='mailto:".$this->get_settings('admin-email')."'>ссылку</a>
            </td></tr>";

        $m .= "</tbody></table>
            </td></tr>
            </tbody></table>
            ";
        $body = $m;
        $body_alt = is_array($body) ? print_r($body, 1) : $body;

        $mailer = new PHPMailer;
        $mailer->CharSet = 'UTF-8';
        $mailer->setLanguage('ru');
        $mailer->IsSMTP();
        $mailer->IsHTML(TRUE);

        $mailer->Host = self::SMTP_HOST;
        $mailer->SMTPDebug = self::SMTP_DEBUG;
        $mailer->SMTPAuth = self::SMTP_AUTH;
        $mailer->SMTPSecure = self::SMTP_SECURE;
        $mailer->Port = self::SMTP_PORT;
        $mailer->Username = self::SMTP_USER;
        $mailer->Password = self::SMTP_PASS;
        $mailer->setFrom(self::MAIL_FROM_EMAIL, self::MAIL_FROM_NAME);
        $mailer->addReplyTo(self::MAIL_REPLY_TO_EMAIL, self::MAIL_REPLY_TO_NAME);
        foreach ($to as $email) {
            $mailer->AddAddress($email);
        }

        $mailer->Subject = $subject;
        $mailer->AltBody = $body_alt;
        $mailer->msgHTML($body);

        if (isset($attachments['name'])) {
            if (is_array($attachments['name'])) {
                foreach ($attachments['name'] as $k => $name) {
                    if ($attachments['error'][$k] == UPLOAD_ERR_OK) {
                        $mailer->addAttachment($attachments['tmp_name'][$k], $name);
                    }
                }
            } elseif ($attachments['name'] && $attachments['error'] == UPLOAD_ERR_OK) {
                $mailer->addAttachment($attachments['tmp_name'], $attachments['name']);
            }
        }

        $r = $mailer->send();
        if (!$r) {
            $this->set_error($mailer->ErrorInfo);
        }
        return $r;

    }

    //SEARCH
    public function search_pages($str, $limit1 = 0, $limit2 = 1000) {
        $visible_only = !self::is_adminpanel() ? "AND (visible=1 OR fixed=1)" : "";
        $str = self::clean_varchar($str);
        $data = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."pages WHERE (title LIKE '%{$str}%' OR text LIKE '%{$str}%')
        {$visible_only} ORDER BY date_created DESC LIMIT ".intval($limit1).",".intval($limit2))->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as &$d) {
            $d['url'] = trim(implode('/', array($d['node1'], $d['node2'], $d['node3'])), '/');
            $d['custom'] = unserialize($d['custom']);
        }
        return $data;
    }

}


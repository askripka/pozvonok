<?php

class CACHE {

    public $caching = FALSE;
    public $cache_folder = '';
    public $cache_file = '';
    public $timer;

    public function __construct() {
        $this->cache_folder = __DIR__.'/../cache';
        if(!is_dir($this->cache_folder)) {
            @mkdir($this->cache_folder, 0750, TRUE);
        }
        @chmod($this->cache_folder, 0750);
        $this->timer = microtime(1);
    }


    public function start($url) {
        $this->cache_file = md5($url).'.html';
        if((time() - @filemtime($this->cache_folder.'/'.$this->cache_file)) < CONFIG::CACHE_LIFETIME) {
            @include $this->cache_folder.'/'.$this->cache_file;
            echo '<!--'.(microtime(1) - $this->timer).'-->';
            exit();
        } else {
            $this->caching = TRUE;
            ob_start();
        }
    }

    public function end() {
        if($this->caching) {
            $contents = ob_get_contents();
            ob_end_clean();
            @file_put_contents($this->cache_folder.'/'.$this->cache_file, $contents);
            @chmod($this->cache_folder.'/'.$this->cache_file, 0750);
            echo $contents;
        } else {
            ob_end_flush();
        }
        echo '<!--'.(microtime(1) - $this->timer).'-->';
    }

    public function clean() {
        $files = glob($this->cache_folder.'/*'); // get all file names
        foreach($files as $file) { // iterate files
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }

    }

}
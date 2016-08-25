<?php

if($_GET['img']) {
    try {
        $_GET['img'] = str_replace('%2F', '/', $_GET['img']);

        preg_match('/^(.+)\/(.+)$/', $_GET['img'], $matches);
        $path = $matches[1];
        $name = $matches[2];

        preg_match('/^([0-9]+)x([0-9]+)(c?)\.(.+)\.(.+)$/', $name, $m);
        $width = $m[1];
        $height = $m[2];
        $crop = $m[3];
        $filename = $m[4];
        $ext = $m[5];

        $original = __DIR__.'/'.preg_replace('/^(.+)\/(.+)$/', '$1', $path).'/'.$filename.'.'.$ext;
        $resized = __DIR__.'/'.$path.'/'.$name;

        if(!file_exists($original)) {
            throw new Exception('Исходный файл не существует');
        }

        if(!is_dir(__DIR__.'/'.$path)) {
            @mkdir(__DIR__.'/'.$path, 0750);
        }

        $image = new Imagick($original);
        $crop ? $image->cropthumbnailimage($width, $height) : $image->thumbnailimage($width, $height);
        $image->writeimage($resized);

        if(is_readable($resized)) {
            header('Content-type: image');
            print file_get_contents($resized);
        }

    } catch(Exception $e) {
        header("HTTP/1.0 404 Not Found");
        return;
    }

}
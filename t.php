<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
set_time_limit(120);
include __DIR__.'/_protected/cpapi.php';


$api = new CPAPI();


$f_contents = file(__DIR__.'/_protected/classes/encodingDetector/data/voina_mir_utf8_with_names.txt');
$line = $f_contents[rand(0, count($f_contents) - 1)];

for($i=0; $i<40000; $i++){


    $api->insert_list_user(array(
        'list_id'=>43,
        'phone'=> '7'.str_pad($i, 10, '0', STR_PAD_LEFT),
        'first_name'=>  $f_contents[rand(7777, 46117)],
    ));
}


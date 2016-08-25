<?php
/**
 * REQUIRE
 * UNIX timezonedb, ffmpeg, xls2csv 0.95, xlsx2csv
 *
 */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__.'/_protected/cpapi.php';

if ($_POST['admin_exit']) {
    if (CPAPI::exit_admin_panel()) {
        header("Location: /");
        exit;
    }
}

if($_GET['r']){
    setcookie('r', $_GET['r'], time()+60*60*24*30, '/');
}

$api = new CPAPI();

if ($_POST['admin_login'] && $_POST['admin_password']) {
    require_once __DIR__.'/_protected/classes/ipBlockerApi.php';

    if (IPBlocker::is_spammer(60, 10)) {
        $api->set_error('Вы слишком часто делали запросы. Для предотвращения брутфорса мы заблокировали вам доступ на пару минут.');
    } else {
        if ($api->enter_admin_panel($_POST['admin_login'], $_POST['admin_password'])) {
            header("Location: /");
            exit;
        }
    }
}

$page = $api->get_page_by_url($_GET['url']);
if (!$page) {
    header("HTTP/1.0 404 Not Found");
    $page = $api->get_page('404');
}

if (!$api->user() && $page['type'] != 'login' && $page['type'] != '404' && $page['type'] != 'register') {
    header("Location: /login");
    exit();
}

if ($api->user() && $page['type'] == 'login') {
    header("Location: /");
    exit();
}

ob_start();
if (isset(CPAPI::$pages[$page['type']])) {
    require_once __DIR__.'/_protected/templates/'.API::TEMPLATE.'/'.$page['type'].'.php';
}
$content = ob_get_contents();
ob_end_clean();


require_once __DIR__.'/_protected/templates/'.API::TEMPLATE.'/index.php';



<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow"/>

    <title><?= $page['custom']['meta_title'] ? $page['custom']['meta_title'] : $page['title'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= $page['description'] ?>"/>
    <meta name="author" content="Алексей Скрипка">
    <link rel="canonical" href="//<?= $_SERVER['HTTP_HOST'] ?>/<?= $page['url'] ?>"/>

    <meta name="og:type" content="website"/>
    <meta name="og:title" content="<?= $page['custom']['meta_title'] ? $page['custom']['meta_title'] : $page['title'] ?>"/>
    <meta name="og:description" content="<?= $page['description'] ?>"/>
    <meta name="og:image" content="//<?= $_SERVER['HTTP_HOST'] ?>/<?= API::IMAGES_DOWNLOADS_DIR ?>/<?= $page_image ? $page_image : API::resize('logo.png', 300) ?>?<?= $api->version() ?>"/>

    <link rel="icon" href="//<?= $_SERVER['HTTP_HOST'] ?>/<?= API::IMAGES_DOWNLOADS_DIR ?>/favicon.ico?<?= $api->version() ?>" type="image/x-icon">
    <link rel="shortcut icon" href="//<?= $_SERVER['HTTP_HOST'] ?>/<?= API::IMAGES_DOWNLOADS_DIR ?>/favicon.ico?<?= $api->version() ?>" type="image/x-icon">
    <link rel="image_src" href="//<?= $_SERVER['HTTP_HOST'] ?>/<?= API::IMAGES_DOWNLOADS_DIR ?>/<?= $page_image ? $page_image : API::resize('logo.png', 300) ?>?<?= $api->version() ?>"/>
    <link rel="apple-touch-icon" href="//<?= $_SERVER['HTTP_HOST'] ?>/<?= API::IMAGES_DOWNLOADS_DIR ?>/<?= API::resize('logo.png', 57, 57, 1) ?>?<?= $api->version() ?>">
    <link rel="apple-touch-icon" sizes="72x72" href="//<?= $_SERVER['HTTP_HOST'] ?>/<?= API::IMAGES_DOWNLOADS_DIR ?>/<?= API::resize('logo.png', 72, 72, 1) ?>?<?= $api->version() ?>">
    <link rel="apple-touch-icon" sizes="114x114" href="//<?= $_SERVER['HTTP_HOST'] ?>/<?= API::IMAGES_DOWNLOADS_DIR ?>/<?= API::resize('logo.png', 114, 114, 1) ?>?<?= $api->version() ?>">


    <!--    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:400,500,600,700,800,900,300">-->

    <link rel="stylesheet" href="/libs/bower/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="/libs/bower/material-design-iconic-font/dist/css/material-design-iconic-font.min.css">

    <link rel="stylesheet" href="/libs/bower/animate.css/animate.min.css">
    <link rel="stylesheet" href="/libs/bower/fullcalendar/dist/fullcalendar.min.css">
    <link rel="stylesheet" href="/libs/bower/perfect-scrollbar/css/perfect-scrollbar.css">

    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="stylesheet" href="/assets/css/bootstrap.css">
    <link rel="stylesheet" href="/assets/css/app.css">


    <? if (!$_GET['admin']) echo $api->get_settings('head-code'); ?>
</head>

<?=$content ?>


<script src="/libs/bower/jquery/dist/jquery.js"></script>
<script src="/libs/bower/jquery-ui/jquery-ui.min.js"></script>
<script src="/libs/bower/jQuery-Storage-API/jquery.storageapi.min.js"></script>
<script src="/libs/bower/bootstrap-sass/assets/javascripts/bootstrap.js"></script>
<script src="/libs/bower/superfish/dist/js/hoverIntent.js"></script>
<script src="/libs/bower/superfish/dist/js/superfish.js"></script>
<script src="/libs/bower/jquery-slimscroll/jquery.slimscroll.js"></script>
<script src="/libs/bower/perfect-scrollbar/js/perfect-scrollbar.jquery.js"></script>
<script src="/libs/bower/PACE/pace.min.js"></script>


<script src="/assets/js/rec/WebAudioRecorder.min.js"></script>

<script src="/assets/js/library.js"></script>
<script src="/assets/js/plugins.js"></script>
<script src="/assets/js/app.js"></script>

<script src="/libs/bower/moment/moment.js"></script>
<script src="/libs/bower/fullcalendar/dist/fullcalendar.min.js"></script>
<script src="/assets/js/fullcalendar.js"></script>

<? if (CPAPI::is_adminpanel() || $_GET['admin']) include __DIR__.'/_admin/panel.php'; ?>
<? if (!$_GET['admin']) echo $api->get_settings('body-code'); ?>

</body>
</html>


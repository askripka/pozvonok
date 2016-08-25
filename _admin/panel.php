<? if(!$api) exit(); ?>

<!--jQuery-->
<script src="/_admin/js/jquery-1.11.0.min.js"></script>
<script src="/_admin/js/jquery-migrate-1.2.1.min.js"></script>
<script src="/_admin/js/jquery.cookie.js"></script>
<!--shortcuts-->
<script src="/_admin/js/shortcuts_v1.js"></script>
<!--Fancybox-->
<link rel="stylesheet" href="/_admin/plugins/fancybox/jquery.fancybox.min.css">
<script src="/_admin/plugins/fancybox/jquery.fancybox.min.js"></script>
<!--qTip-->
<link rel="stylesheet" href="/_admin/plugins/qtip/jquery.qtip.min.css">
<script src="/_admin/plugins/qtip/jquery.qtip.min.js"></script>
<!--tinyMCE-->
<script src="/_admin/plugins/tinymce/tinymce.min.js"></script>
<!--DataTables-->
<script src="/_admin/js/jquery.dataTables.js"></script>
<script src="/_admin/js/dataTables.tableTools.js"></script>
<link rel="stylesheet" href="/_admin/css/jquery.dataTables.css">
<link rel="stylesheet" href="/_admin/css/dataTables.tableTools.css">
<!-- DateTime Picker-->
<script src="/_admin/js/jquery.datetimepicker.js"></script>
<link rel="stylesheet" href="/_admin/css/jquery.datetimepicker.css">

<link rel="stylesheet" href="/_admin/css/style.css">

<div class="adm__panel qtip-default qtip-light qtip-shadow">

    <? if(!$api->is_adminpanel()): ?>

        <form method="POST" action="/adminpanel">
            <input type="text" name="admin_login" maxlength="100" placeholder="логин"/>
            <input type="password" name="admin_password" maxlength="100" placeholder="пароль"/>
            <button type="submit">Войти</button>
        </form>
        <? if($api->get_error()): ?>
            <p class="adm__error_message"><?= $api->get_error(); ?></p>
        <? endif; ?>

    <? else: ?>

        <? if(!$_COOKIE['help-message']): ?>
            <script>
                alert('Чтобы скрыть/показать блоки редактора (обозначеные синим цветом) нажмите комбинацию клавиш Alt+1');
                var date = new Date;
                date.setDate(date.getDate() + 7);
                document.cookie = "help-message=1; path=/; expires=" + date.toUTCString();
            </script>

        <? endif; ?>
        <script src="/_admin/js/custom.js"></script>
        <form method="POST">
            <input type="hidden" name="admin_exit" value="1"/>
            <a href="javascript:void(0);" class="adm__button adm__exit_button adm__submit"
               title="Выйти из панели администратора">Выход</a>
        </form>

        <a href="/_admin/ajax.php?form_settings=1" class="adm__button adm__settings_button fancybox fancybox.ajax" title="Настройки">Настройки</a>

        <? if(!$page['fixed']): ?>
            <a href="/_admin/ajax.php?form_add_page=<?= $_GET['url'] ?>" class="adm__button adm__add_page_button fancybox fancybox.ajax" title="Добавить страницу">Добавить страницу</a>
        <? endif; ?>
        <a href="/_admin/ajax.php?form_edit_page=<?= $_GET['url'] ?>" class="adm__button adm__edit_page_button fancybox fancybox.ajax" title="Редактировать страницу">Редактировать страницу</a>

        <? if(!$page['fixed']): ?>
            <form class="adm__ajax_form" action="/_admin/ajax.php" method="POST">
                <input type="hidden" name="delete_page" value="1"/>
                <input type="hidden" name="node1" value="<?= $page['node1']; ?>"/>
                <input type="hidden" name="node2" value="<?= $page['node2']; ?>"/>
                <input type="hidden" name="node3" value="<?= $page['node3']; ?>"/>
                <a href="javascript:void(0);" onclick="Admin.callback='redirect';Admin.confirmDelete=true;" class="adm__button adm__delete_button adm__submit" title="Удалить">Удалить страницу</a>
            </form>
        <? endif; ?>

        <? @include __DIR__.'/custom/panel.php'; ?>

    <? endif; ?>

    <a href="http://askripka.com" target="_blank" class="adm__copyright" title="Студия Алексея Скрипки">Студия Алексея Скрипки ©
        2010-<?= date('Y') ?>. Версия двигателя: 3.1</a>
</div>
<div class="adm__antipanel"></div>




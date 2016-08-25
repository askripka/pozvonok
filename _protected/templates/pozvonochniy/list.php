<?php
$list = $api->get_list($_GET['id'], $api->user()['id']);

?>

<body class="sb-left">

<? include 'menu.php' ?>
<? include 'navbar.php' ?>


<main class="app-main in" id="app-main">
    <div class="wrap">
        <section class="app-content">
            <div class="m-b-sm">
                <a class=" " href="/lists"> <i class=" glyphicon glyphicon-chevron-left "></i> Назад</a>
            </div>


            <? if ($list): ?>
                <div class="row m-b-lg">
                    <div class="col-md-9">
                        <h3 class="m-0 m-b-md"><?= $list['title'] ?> [всего контактов: <?= $list['total'] ?>]</h3>
                    </div>
                    <div class="col-md-3 text-right">
                        <a href="/ajax.php?list_export=1&list_id=<?=$list['id']?>" class="btn btn-primary">
                            <i class="fa fa-download"></i> Скачать список
                        </a>


                        <?
                        //Ищем используется ли гдето в тасках
                        $in_tasks = $api->get_tasks($api->user()['id'], array(API::TASK_STATUS_SCHEDULED, API::TASK_STATUS_ACTIVE), $list['id']);
                        ?>

                        <? if(!$in_tasks): ?>
                        <form class="form-ajax" action="/ajax.php" style="display: inline-block">
                            <input type="hidden" name="delete_list" value="<?= $list['id'] ?>"/>
                            <button class="btn btn-danger" type="submit" onclick="return confirm('Удалить?');"><i class="fa fa-trash"></i> Удалить список</button>
                            <br/>
                            <div role="alert" class="alert alert-warning alert-dismissible form-ajax-message-wrapper">
                                <button aria-label="Close" data-dismiss="alert" class="close" type="button">
                                    <span aria-hidden="true">×</span></button>
                                <span class="form-ajax-message"></span>
                            </div>
                        </form>
                        <? endif ?>

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="widget p-lg">
                            <table class="table table-striped">
                                <? $users = $api->get_list_users($list['id']);
                                $i = 0; ?>
                                <tr>
                                    <th>#</th>
                                    <th>Телефон</th>
                                    <th>Имя</th>
                                </tr>
                                <? foreach ($users as $u): $i++; ?>
                                    <tr>
                                        <td><?= $i ?></td>
                                        <td><?=$u['phone']?></td>
                                        <td><?=$u['first_name']?></td>
                                    </tr>
                                <? endforeach; ?>

                            </table>
                        </div>
                    </div>
                </div>
            <? else: ?>
                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="text-center">
                            <h3 class="m-0 m-h-md">Список #<?= $_GET['id'] ?> не найден</h3>
                            <a role="button" style="min-width: 160px;" class="btn btn-primary rounded btn-rounded m-b-xl" href="/lists">Вернуться назад</a>
                        </div>
                    </div>
                </div>
            <? endif; ?>

        </section>
    </div>

    <? include 'footer.php' ?>

</main>

</body>
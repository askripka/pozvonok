<body class="sb-left">

<? include 'menu.php' ?>
<? include 'navbar.php' ?>


<main class="app-main in" id="app-main">
    <div class="wrap">
        <!-- CONTENT -->
        <section class="app-content">
            <div class="row">

                <div class="col-md-12">
                    <? include 'create-task.php' ?>
                </div>


                <div class="col-md-12">

                    <div class="table-responsive">
                        <table class="table mail-list">
                            <tbody>
                            <tr>
                                <td>

                                    <? foreach ($api->get_tasks($api->user()['id'], null, null, 0, 1000, "date_start DESC", $api->user()['timezone']) as $task): ?>
                                        <a class="mail-item" href="/task?id=<?=$task['id']?>">
                                            <table class="mail-container">
                                                <tbody>
                                                <tr>
                                                    <td class="mail-left watermark">

                                                        <? if($task['status']==API::TASK_STATUS_ACTIVE): ?>
                                                            <? $progress = str_replace(',', '.', round(($task['total_calls']/$task['total_list_amount']), 2));?>
                                                            <div class="task-pieprogress pieprogress text-success" data-task-id="<?=$task['id']?>" data-plugin="circleProgress" data-value="<?=$progress?>" data-size="60" data-start-angle="-300" data-empty-fill="rgba(16, 196, 105,.3)" data-fill="{&quot;color&quot;: &quot;#10c469&quot;}" data-thickness="7">
                                                                <strong>%<span class="task-pieprogress-val"><?=intval($progress*100)?></span></strong>
                                                            </div>
                                                        <? elseif($task['status']==API::TASK_STATUS_SCHEDULED): ?>
                                                            <i class="glyphicon glyphicon-play zmdi-hc-4x"></i>
                                                        <? elseif($task['status']==API::TASK_STATUS_COMPLETED): ?>
                                                            <i class="glyphicon glyphicon-ok zmdi-hc-4x"></i>
                                                        <? elseif($task['status']==API::TASK_STATUS_PAUSED): ?>
                                                            <i class="glyphicon glyphicon-pause zmdi-hc-4x"></i>
                                                        <? elseif($task['status']==API::TASK_STATUS_NOMONEY): ?>
                                                            <i class="glyphicon glyphicon-repeat zmdi-hc-4x"></i>
                                                        <? endif ?>

                                                    </td>
                                                    <td class="mail-center">
                                                        <div class="mail-item-header">
                                                            <h4 class="mail-item-title title-color ">
                                                                <?=$task['title']?>
                                                            </h4>

                                                            <? if ($task['status'] == API::TASK_STATUS_SCHEDULED): ?>
                                                                <span class="label label-primary">запланирована</span>
                                                            <? elseif ($task['status'] == API::TASK_STATUS_ACTIVE): ?>
                                                                <span class="label label-danger">в процессе</span>
                                                            <? elseif ($task['status'] == API::TASK_STATUS_COMPLETED): ?>
                                                                <span class="label label-success">завершена</span>
                                                            <? elseif ($task['status'] == API::TASK_STATUS_PAUSED): ?>
                                                                <span class="label label-warning">на паузе</span>
                                                            <? elseif($task['status']==API::TASK_STATUS_NOMONEY): ?>
                                                                <span class="label label-danger">недостаточно средств</span>
                                                            <? endif ?>
                                                        </div>

                                                        <p class="mail-item-excerpt"><?= $task['phone_number'] ? 'с номера: +'.$task['phone_number']:'';?></p>
                                                    </td>

                                                    <td class="mail-right text-center">
                                                        <? if($task['status']==API::TASK_STATUS_SCHEDULED): ?>
                                                            <h4 class="title-color m-t-0"><?=strftime("%e %b %Y <br> %H:%M", strtotime($task['date_start']))?></h4>
                                                            <small class="text-color">запланирована</small>
                                                        <? elseif($task['status']==API::TASK_STATUS_ACTIVE): ?>
                                                            <h4 class="title-color m-t-0"><?=strftime("%e %b %Y <br> %H:%M", strtotime($task['date_start']))?></h4>
                                                            <small class="text-color">запущена</small>
                                                        <? elseif($task['status']==API::TASK_STATUS_PAUSED): ?>
                                                            <h4 class="title-color m-t-0"><?=strftime("%e %b %Y <br> %H:%M", strtotime($task['date_start']))?></h4>
                                                            <small class="text-color">на паузе</small>
                                                        <? elseif($task['status']==API::TASK_STATUS_COMPLETED): ?>
                                                            <h4 class="title-color m-t-0"><?=strftime("%e %b %Y <br> %H:%M", strtotime($task['date_end']))?></h4>
                                                            <small class="text-color">завершена</small>
                                                        <? elseif($task['status']==API::TASK_STATUS_NOMONEY): ?>
                                                            <h4 class="title-color m-t-0"><?=strftime("%e %b %Y <br> %H:%M", strtotime($task['date_start']))?></h4>
                                                            <small class="text-color">недостаточно средств</small>
                                                        <? endif ?>

                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </a>
                                    <? endforeach; ?>

                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div><!-- END column -->
            </div><!-- .row -->
        </section><!-- .app-content -->
    </div><!-- .wrap -->

    <? include 'footer.php'?>

</main>

</body>
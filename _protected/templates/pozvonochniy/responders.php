<body class="sb-left">

<? include 'menu.php' ?>
<? include 'navbar.php' ?>


<main class="app-main in" id="app-main">
    <div class="wrap">
        <!-- CONTENT -->
        <section class="app-content">
            <div class="row">

                <div class="col-md-12">
                    <? include 'create-responder.php' ?>
                </div>


                <div class="col-md-12">

                    <div class="table-responsive">
                        <table class="table mail-list">
                            <tbody>
                            <tr>
                                <td>

                                    <? foreach ($api->get_responders($api->user()['id'], null, null, 0, 1000, "date_start DESC", $api->user()['timezone']) as $responder): ?>
                                        <a class="mail-item" href="/responder?id=<?= $responder['id'] ?>">
                                            <table class="mail-container">
                                                <tbody>
                                                <tr>
                                                    <td class="mail-left watermark">

                                                        <? if ($responder['status'] == API::RESPONDER_STATUS_ACTIVE): ?>
                                                            <i class="glyphicon glyphicon-play zmdi-hc-4x"></i>
                                                        <? elseif ($responder['status'] == API::RESPONDER_STATUS_ARCHIVED): ?>
                                                            <i class="glyphicon glyphicon-folder-open zmdi-hc-4x"></i
                                                        <? elseif ($responder['status'] == API::RESPONDER_STATUS_PAUSED): ?>
                                                            <i class="glyphicon glyphicon-pause zmdi-hc-4x"></i
                                                        <? elseif ($responder['status'] == API::RESPONDER_STATUS_NOMONEY): ?>
                                                            <i class="glyphicon glyphicon-repeat zmdi-hc-4x"></i>
                                                        <? endif ?>

                                                    </td>
                                                    <td class="mail-center">
                                                        <div class="mail-item-header">
                                                            <h4 class="mail-item-title title-color ">
                                                                <?= $responder['title'] ?>
                                                            </h4>

                                                            <? if ($responder['status'] == API::RESPONDER_STATUS_ACTIVE): ?>
                                                                <span class="label label-primary">активен</span>
                                                            <? elseif ($responder['status'] == API::RESPONDER_STATUS_ARCHIVED): ?>
                                                                <span class="label label-success">в архиве</span>
                                                            <? elseif ($responder['status'] == API::RESPONDER_STATUS_PAUSED): ?>
                                                                <span class="label label-warning">на паузе</span>
                                                            <? elseif ($responder['status'] == API::RESPONDER_STATUS_NOMONEY): ?>
                                                                <span class="label label-danger">недостаточно средств</span>
                                                            <? endif ?>

                                                        </div>

                                                        <p class="mail-item-excerpt"><?= $responder['phone_number'] ? 'на номер: +'.$responder['phone_number'] : ''; ?></p>
                                                    </td>

                                                    <td class="mail-right text-center">

                                                        <? if ($responder['status'] == API::RESPONDER_STATUS_ACTIVE): ?>
                                                            <h4 class="title-color m-t-0"><?= $responder['date_start'] ? strftime("%e %b %Y <br> %H:%M", strtotime($responder['date_start'])) : '' ?></h4>
                                                            <small class="text-color">запущена</small>
                                                        <? elseif ($responder['status'] == API::RESPONDER_STATUS_PAUSED): ?>
                                                            <h4 class="title-color m-t-0"><?= $responder['date_start'] ? strftime("%e %b %Y <br> %H:%M", strtotime($responder['date_start'])) : '' ?></h4>
                                                            <small class="text-color">на паузе</small>
                                                        <? elseif ($responder['status'] == API::RESPONDER_STATUS_NOMONEY): ?>
                                                            <h4 class="title-color m-t-0"><?= $responder['date_start'] ? strftime("%e %b %Y <br> %H:%M", strtotime($responder['date_start'])) : '' ?></h4>
                                                            <small class="text-color">недостаточно средств</small>
                                                        <? elseif ($responder['status'] == API::RESPONDER_STATUS_ARCHIVED): ?>
                                                            <h4 class="title-color m-t-0"><?= $responder['date_end'] ? strftime("%e %b %Y <br> %H:%M", strtotime($responder['date_end'])) : '' ?></h4>
                                                            <small class="text-color">в архиве</small>
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

    <? include 'footer.php' ?>

</main>

</body>
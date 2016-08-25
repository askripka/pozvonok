<body class="sb-left">

<? include 'menu.php' ?>
<? include 'navbar.php' ?>


<main class="app-main in" id="app-main">
    <div class="wrap">
        <!-- CONTENT -->
        <section class="app-content">
            <div class="row">

                <div class="col-md-12">
                    <? include 'create-list.php' ?>
                </div>


                <div class="col-md-12">

                    <div class="table-responsive">
                        <table class="table mail-list">
                            <tbody>
                            <tr>
                                <td>
                                    <? foreach ($api->get_lists($api->user()['id']) as $list): ?>
                                        <a class="mail-item" href="/list?id=<?= $list['id'] ?>">
                                            <table class="mail-container">
                                                <tbody>
                                                <tr>
                                                    <td class="mail-left watermark">
                                                        <i class="fa fa-database zmdi-hc-4x"></i>
                                                    </td>
                                                    <td class="mail-center">
                                                        <div class="mail-item-header">
                                                            <h4 class="mail-item-title title-color">#<?= $list['id'] ?>: <?= $list['title'] ?></h4>
                                                        </div>
                                                    </td>
                                                    <td class="mail-right text-center">
                                                        <h2 class="title-color m-t-0"><?= $list['total'] ?></h2>
                                                        <small class="text-color">контактов</small>
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
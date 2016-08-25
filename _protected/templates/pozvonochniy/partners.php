<body class="sb-left">

<? include 'menu.php' ?>
<? include 'navbar.php' ?>


<main class="app-main in" id="app-main">
    <div class="wrap">
        <section class="app-content">
            <div class="row m-b-lg">
                <div class="col-md-12">
                    <h3 class="m-0 m-b-md">Партнерская программа</h3>
                    <p>Привлекайте новых клиентов - получайте % с оборота услуг.</p>
                </div>

            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="widget p-lg">

                        <div class="m-b-lg">
                            <h4 class="m-b-md">Моя реферальная ссылка</h4>
                            <input class="form-control" type="text" value="https://<?= $_SERVER['HTTP_HOST'] ?>/?r=<?= $api->user()['ref_code'] ?>" readonly/>
                        </div>

                        <h4 class="m-b-md">Мои рефералы</h4>
                        <p class="m-b-lg docs">
                            Если пользователь переходит по вашей ссылке и в течение месяца регистрируется, он автоматически становится вашим рефералом.
                        </p>

                        <table class="table table-hover">
                            <tbody>
                            <tr>
                                <th>#</th>
                                <th>Имя Фамилия</th>
                                <th>Текущий баланс</th>
                                <th>Всего потрачено на звонки</th>
                                <th>Всего потрачено на допуслуги</th>
                            </tr>
                            <? $i = 1; ?>
                            <? foreach($api->get_accounts(0, 1000, "id DESC", $api->user()['id']) as $referal): ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $referal['first_name'] ?> <?= $referal['last_name'] ?></td>
                                    <td><?= $referal['balance'] ?> <?= API::CURRENCY ?></td>
                                    <td><?= $referal['total_spent_calls'] ?></td>
                                    <td><?= $referal['total_spent_services'] ?></td>
                                </tr>
                                <? $i++; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </section>
    </div>

    <? include 'footer.php' ?>

</main>

</body>
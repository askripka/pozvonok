<body class="sb-left">

<? include 'menu.php' ?>
<? include 'navbar.php' ?>


<main class="app-main in" id="app-main">
    <div class="wrap">
        <section class="app-content">
            <div class="row m-b-lg">
                <div class="col-md-12">
                    <h3 class="m-0 m-b-md"><?=$page['title']?></h3>
                    <p><?=$page['description']?></p>
                </div>

            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="widget p-lg">

                        <div class="m-b-lg">
                            <h4 class="m-b-md">Моя реферальная ссылка</h4>
                            <input class="form-control" type="text" value="https://<?= $_SERVER['HTTP_HOST'] ?>/?r=<?= $api->user()['ref_code'] ?>" readonly/>
                        </div>

                        <h4 class="m-b-md">Мои клиенты</h4>
                        <p class="m-b-lg docs">
                            Если пользователь переходит по вашей ссылке и в течение месяца регистрируется, он автоматически становится вашим клиентом.
                        </p>

                        <table class="table table-hover">
                            <tbody>
                            <tr>
                                <th>#</th>
                                <th>Имя Фамилия</th>
                                <th>Дата регистрации</th>
                                <th>Текущий баланс</th>
                                <th>Всего потрачено на звонки</th>
                                <th>Всего потрачено на допуслуги</th>
                                <th>Мой доход</th>
                                <th></th>
                            </tr>
                            <? $i = 1; ?>
                            <? foreach($api->get_accounts(0, 1000, "id DESC", $api->user()['id'], $api->user()['timezone']) as $client): ?>
                                <tr>
                                    <td><?= $i ?></td>
                                    <td><?= $client['first_name'] ?> <?= $client['last_name'] ?></td>
                                    <td><?= $client['date_registered'] ?></td>
                                    <td><?= $client['balance'] ?> <?= API::CURRENCY ?></td>
                                    <td><?= $client['total_spent_calls'] ?> <?= API::CURRENCY ?></td>
                                    <td><?= $client['total_spent_services'] ?> <?= API::CURRENCY ?></td>
                                    <td><?= $client['total_spent_calls']*0.2 + $client['total_spent_services']*0.3 ?> <?= API::CURRENCY ?></td>
                                    <td><button class="btn btn-primary btn-sm">Переслать деньги</button></td>
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
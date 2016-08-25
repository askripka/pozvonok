<body class="sb-left">

<? include 'menu.php' ?>
<? include 'navbar.php' ?>


<?
$available_numbers = $api->get_available_phone_numbers();
$used_numbers = $api->get_personal_phone_numbers();
$available_numbers = array_flip($available_numbers);
foreach ($used_numbers as $n) {
    unset($available_numbers[$n['phone_number']]);
}
$available_numbers = array_flip($available_numbers);


?>

<main class="app-main in" id="app-main">
    <div class="wrap">
        <section class="app-content">

            <div class="row">
                <div class="col-md-12">
                    <div class="panel panel-primary">
                        <header class="panel-heading">
                            <h4 class="panel-title">Пополнить счет</h4>
                        </header>

                        <div class="panel-body text-center">
                            <h3 class="m-b-sm">Баланс моего аккаунта:
                                <strong><?= $api->user()['balance'] / 100 ?> <?= API::CURRENCY ?></strong></h3>
                            <form action="#" class="form-inline m-b-sm">
                                <div class="form-group">
                                    <input type="text" placeholder="Пополнить счет на" name="payment_sum" class="form-control input-lg">
                                    <button type="submit" class="btn btn-primary btn-lg">Пополнить</button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

                <div class="col-md-12">


                    <div class="panel panel-primary">
                        <header class="panel-heading">
                            <h4 class="panel-title">Купить выделенный номер</h4>
                        </header>

                        <div class="panel-body ">

                            <div class="text-center">

                                <h3 class="m-b-sm">Выберите выделенный номер:</h3>
                                <form action="#" class="form-inline m-b-lg">
                                    <div class="form-group">
                                        <select name="personal_phone" class="form-control input-lg select2" data-container-css-class="input-lg" data-plugin="select2">
                                            <? foreach ($available_numbers as $n): ?>
                                                <option value="<?= $n ?>">+<?= $n; ?></option>
                                            <? endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-lg">Купить</button>
                                    </div>
                                </form>

                                <? if ($pp_numbers = $api->get_personal_phone_numbers($api->user()['id'])): ?>
                                    <h4>Мои номера</h4>
                                    <ul class="list-group">
                                        <? foreach ($pp_numbers as $ppn): ?>
                                            <li class="list-group-item"><h5>+<?= $ppn['phone_number'] ?></h5></li>
                                        <? endforeach; ?>
                                    </ul>
                                <? endif; ?>

                            </div>

                        </div>

                    </div>
                </div>

                <div class="col-md-12">
                    <div class="panel panel-custom panel-primary">
                        <header class="panel-heading">
                            <h4 class="panel-title">Тарификация рассылок</h4>
                        </header>


                        <table class="table table-hover ">
                            <tr>
                                <th>Услуга</th>
                                <th>Стоимость</th>
                                <th>Заработок представителя</th>
                            </tr>

                            <? foreach ($api->get_tariff('', API::TARIFF_TYPE_TASK) as $t): ?>
                                <tr>
                                    <? if (preg_match('/(cost|balance)/', $t['name'])): ?>
                                        <td><?= $t['description'] ?></td>
                                        <td><?= (($t['value'] + $t['partner_reward']) / 100)." ".API::CURRENCY ?></td>
                                        <td><?= $t['partner_reward'] ? ($t['partner_reward'] / 100)." ".API::CURRENCY : '-' ?></td>
                                    <? else: ?>
                                        <td><?= $t['description'] ?></td>
                                        <td><?= $t['value'] ?></td>
                                        <td><?= '-' ?></td>
                                    <? endif; ?>
                                </tr>
                            <? endforeach; ?>

                        </table>
                        <div class="panel-body">
                            <small>* - тот же номер рекомендуется использовать для голосового ответчика на входящие вызовы</small>
                        </div>

                    </div>
                </div>

                <div class="col-md-12">

                    <div class="panel panel-custom panel-primary">
                        <header class="panel-heading">
                            <h4 class="panel-title">Тарификация голосовых ответчиков</h4>
                        </header>

                        <table class="table table-hover ">
                            <tr>
                                <th>Услуга</th>
                                <th>Стоимость</th>
                                <th>Заработок представителя</th>
                            </tr>

                            <? foreach ($api->get_tariff('', API::TARIFF_TYPE_RESPONDER) as $t): ?>
                                <tr>
                                    <? if (preg_match('/(cost|balance)/', $t['name'])): ?>
                                        <td><?= $t['description'] ?></td>
                                        <td><?= (($t['value'] + $t['partner_reward']) / 100)." ".API::CURRENCY ?></td>
                                        <td><?= $t['partner_reward'] ? ($t['partner_reward'] / 100)." ".API::CURRENCY : '-' ?></td>
                                    <? else: ?>
                                        <td><?= $t['description'] ?></td>
                                        <td><?= $t['value'] ?></td>
                                        <td><?= '-' ?></td>
                                    <? endif; ?>
                                </tr>
                            <? endforeach; ?>
                        </table>

                        <div class="panel-body">
                            <small>* - тот же номер рекомендуется использовать для проведения с него рассылки</small>
                        </div>
                    </div>
                </div>


            </div>

        </section>
    </div>

    <? include 'footer.php' ?>

</main>

</body>
<!-- APP ASIDE ==========-->
<aside id="app-aside" class="app-aside left light">
    <header class="aside-header">
        <div class="animated">
            <a href="/" id="app-brand" class="app-brand">
                <img class="img-responsive" src="/assets/images/logo_dark.png">
                <!--                <span id="brand-icon" class="brand-icon"><i class="fa fa-gg"></i></span>-->
                <!--                <span id="brand-name" class="brand-icon foldable">Позвонок</span>-->
            </a>
        </div>
    </header><!-- #sidebar-header -->

    <div class="aside-user">
        <!-- aside-user -->
        <div class="media">
            <!--            <div class="media-left">-->
            <!--                <div class="avatar avatar-md avatar-circle">-->
            <!--                    <a href="javascript:void(0)"><img class="img-responsive" src="../assets/images/221.jpg" alt="avatar"/></a>-->
            <!--                </div><!-- .avatar -->
            <!--            </div>-->
            <div class="media-body">
                <div class="foldable">

                    <ul>
                        <li class="dropdown">
                            <a href="javascript:void(0)" class="dropdown-toggle usertitle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <h5><?= $api->user()['first_name']." ".$api->user()['last_name']; ?>
                                    <span class="caret"></span></h5>

                                <small>Баланс:</small>
                                <strong <? if ($api->user()['balance'] < $api->get_tariff('min_account_balance', API::TARIFF_TYPE_TASK)): ?>class="danger"<? endif; ?>><?= $api->user()['balance']/100 ?> <?= API::CURRENCY ?></strong>
                                <br/>
                                <small>Часовой пояс:</small>
                                <form id="tzSetterForm" action="/ajax.php" class="form-ajax">
                                    <select id="tzSetter" name="timezone" class="form-control select2" data-plugin="select2" data-width="100%" required>
                                        <? foreach ($api::get_timezones() as $continent => $data): ?>

                                            <optgroup label="<?= $continent; ?>">
                                                <? foreach ($data as $tz): ?>
                                                    <option value="<?= $tz['timezone'] ?>" <? if ($api->user()['timezone'] == $tz['timezone']) echo 'selected'; ?>>(<?= $tz['p'] ?> UTC) <?= $tz['city'] ?></option>
                                                <? endforeach; ?>
                                            </optgroup>

                                        <? endforeach; ?>
                                    </select>
                                </form>

                            </a>


                            <ul class="dropdown-menu animated flipInY">
                                <li>
                                    <a class="text-color" href="/">
                                        <span>Редактировать профиль</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="text-color" href="/payment">
                                        <span>Пополнить счет</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- /aside-user -->
    </div><!-- #aside-user -->

    <div class="aside-scroll">
        <div id="aside-scroll-inner" class="aside-scroll-inner">
            <ul class="aside-menu aside-left-menu">

                <li class="menu-item <? if ($page['type'] == 'clients'): ?>open<? endif ?>">
                    <a href="/clients" class="menu-link">
                        <span class="menu-icon"><i class="fa fa-users"></i></span>
                        <span class="menu-text">Мои клиенты</span>
                    </a>
                </li>

                <li class="menu-item <? if ($page['type'] == 'tasks' || $page['type'] == 'task'): ?>open<? endif ?>">
                    <a href="/" class="menu-link">
                        <span class="menu-icon"><i class="glyphicon glyphicon-play-circle zmdi-hc-lg"></i></span>
                        <span class="menu-text">Мои рассылки</span>
                    </a>
                </li><!-- .menu-item -->

                <li class="menu-item <? if ($page['type'] == 'responders' || $page['type'] == 'responder'): ?>open<? endif ?>">
                    <a href="/responders" class="menu-link">
                        <span class="menu-icon"><i class="glyphicon glyphicon-play-circle zmdi-hc-lg"></i></span>
                        <span class="menu-text">Голосовые ответчики</span>
                    </a>
                </li>

                <li class="menu-item <? if ($page['type'] == 'lists' || $page['type'] == 'list'): ?>open<? endif ?>">
                    <a href="/lists" class="menu-link">
                        <span class="menu-icon"><i class="fa fa-database zmdi-hc-lg"></i></span>
                        <span class="menu-text">Списки контактов</span>
                    </a>
                </li>

                <li class="menu-item <? if ($page['type'] == 'payment'): ?>open<? endif ?>">
                    <a href="/payment" class="menu-link">
                        <span class="menu-icon"><i class="fa fa-credit-card"></i></span>
                        <span class="menu-text">Счет и тарифы</span>
                    </a>
                </li>


            </ul>
        </div>
    </div>
</aside>




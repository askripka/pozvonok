<?
$responder = $api->get_responder($_GET['id'], $api->user()['id'], $api->user()['timezone']);
if ($responder['call_duration_statistics']) {
    $call_duration_statistics = array(); //[1,3.6],[2,3.5],[3,6],[4,4],[5,4.3],[6,3.5],[7,3.6]
    for ($sec = 1; $sec <= max(array_keys($responder['call_duration_statistics'])); $sec++) {
        $call_duration_statistics[] = '['.$sec.','.intval($responder['call_duration_statistics'][$sec]).']';
    }
    $call_duration_statistics = implode(',', $call_duration_statistics);
}


?>

<body class="sb-left">
<? include 'menu.php' ?>
<? include 'navbar.php' ?>

<main class="app-main in" id="app-main">
    <div class="wrap">
        <section class="app-content">

            <div class="m-b-sm">
                <a class=" " href="/responders"> <i class=" glyphicon glyphicon-chevron-left "></i> Назад</a>
            </div>

            <? if ($responder): ?>
                <div class="row m-b-lg">
                    <div class="col-md-9">
                        <h3 class="m-0 m-b-md"><?= $responder['title'] ?>
                            <? if ($responder['status'] == API::RESPONDER_STATUS_ACTIVE): ?>
                                <span class="label label-primary">активен</span>
                            <? elseif ($responder['status'] == API::RESPONDER_STATUS_ARCHIVED): ?>
                                <span class="label label-success">в архиве</span>
                            <? elseif ($responder['status'] == API::RESPONDER_STATUS_PAUSED): ?>
                                <span class="label label-warning">на паузе</span>
                            <? elseif ($responder['status'] == API::RESPONDER_STATUS_NOMONEY): ?>
                                <span class="label label-danger">недостаточно средств</span>
                            <? endif ?>

                        </h3>

                        <span>Дата старта: <b><?= $responder['date_start'] ? strftime("%d.%m.%Y %H:%M", strtotime($responder['date_start'])) : '-' ?></b></span>
                        <span class="m-l-md">Дата завершения: <b><?= $responder['date_end'] ? strftime("%d.%m.%Y %H:%M", strtotime($responder['date_end'])) : '-'; ?></b></span>
                        <br/>

                        <span>Дни недели: <b><?= implode(', ', array_replace($responder['days'], API::$week_days)) ?></b></span>
                        <span class="m-l-md">Часы работы: <b><?= implode(', ', $responder['hours']) ?></b></span>
                    </div>


                    <div class="col-md-3 text-right">
                        <a class="btn btn-primary m-h-md" href="/ajax.php?responder_export=1&responder_id=<?= $responder['id'] ?>" target="_blank">
                            <i class="fa fa-download"></i> Скачать детализацию
                        </a>
                    </div>

                </div>

                <? if ($responder['status'] != API::RESPONDER_STATUS_ARCHIVED): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <? include 'create-responder.php' ?>
                        </div>
                    </div>
                <? endif ?>


                <? //@TODO Responder Stat ?>

                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="widget">
                            <header class="widget-header">
                                <h4 class="widget-title">ВСЕГО КОНТАКТОВ</h4>
                            </header>
                            <hr class="widget-separator">
                            <div class="widget-body p-t-lg">
                                <div class="clearfix m-b-md">
                                    <h3 class="pull-left text-primary m-0 fw-500">
                                        <span data-plugin="counterUp" class="counter"><?= $responder['total_list_amount'] ?></span>
                                    </h3>
                                    <div class="pull-right watermark"><i class="fa fa-2x fa-user"></i></div>
                                </div>
                                <p class="m-b-0 text-muted">Всего контактов в списке голосовой рассылки.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="widget">
                            <header class="widget-header">
                                <h4 class="widget-title">ВСЕГО ДОЗВОНОВ</h4>
                            </header><!-- .widget-header -->
                            <hr class="widget-separator">
                            <div class="widget-body p-t-lg">
                                <div class="clearfix m-b-md">
                                    <h3 class="pull-left  text-success m-0 fw-500">
                                        <span data-plugin="counterUp" class="counter"><?= $responder['total_success_calls'] ?></span>
                                    </h3>
                                    <div class="pull-right watermark"><i class="fa fa-2x fa-phone"></i></div>
                                </div>
                                <p class="m-b-0 text-muted">Количество контактов которые подняли трубку.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="widget">
                            <header class="widget-header">
                                <h4 class="widget-title">КОНВЕРСИЯ В ДОЗВОНЫ</h4>
                            </header>
                            <hr class="widget-separator">
                            <div class="widget-body">
                                <div class="clearfix">
                                    <div class="pull-left">
                                        <div class="pieprogress text-success" data-plugin="circleProgress" data-value="<?= $responder['total_list_amount'] ? (str_replace(',', '.', round($responder['total_success_calls'] / $responder['total_list_amount'], 2))) : 0 ?>" data-thickness="10" data-start-angle="-300" data-empty-fill="rgba(16, 196, 105,.3)" data-fill="{&quot;color&quot;: &quot;#10c469&quot;}">
                                            <strong>%<?= $responder['total_list_amount'] ? (round($responder['total_success_calls'] * 100 / $responder['total_list_amount'], 0)) : 0; ?></strong>
                                        </div>
                                    </div>
                                    <div class="pull-right">
                                        <div class="m-b-lg watermark text-right"><i class="fa fa-2x fa-phone"></i></div>
                                        <div class="text-muted">% поднявших трубку</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="widget">
                            <header class="widget-header">
                                <h4 class="widget-title">СРЕДНЯЯ ДЛИТЕЛЬНОСТЬ ЗВОНКА</h4>
                            </header>
                            <hr class="widget-separator">
                            <div class="widget-body p-t-lg">
                                <div class="clearfix m-b-md">
                                    <h3 class="pull-left text-warning m-0 fw-500">
                                        <span data-plugin="counterUp" class="counter"><?= $responder['average_call_duration'] ?></span> сек
                                    </h3>
                                    <div class="pull-right watermark"><i class="fa fa-2x fa-phone"></i></div>
                                </div>
                                <p class="m-b-0 text-muted">Расчитывается только на основе успешных дозвонов.</p>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-12">
                        <div class="widget row no-gutter p-lg">
                            <div class="col-md-5 col-sm-5">
                                <div>
                                    <h3 class="widget-title text-primary m-b-lg">Посекундная детализация длительности звонков</h3>
                                    <p class="m-b-lg">График показывает распределение длительности прослушивания клиентами звонков по секундам.</p>
                                    <p class="fs-italic">По оси Y - количество звонков, оборвавшихся на секунде X.</p>

                                    <a class="btn btn-primary m-h-md" href="/ajax.php?play_responder=<?= $responder['id'] ?>" target="_blank"><i class="fa fa-volume-up"></i> Прослушать</a>
                                </div>
                            </div>

                            <div class="col-md-7 col-sm-7">
                                <div>
                                    <div id="lineChart" data-plugin="plot" data-options="
								[
									{

										data: [<?= $call_duration_statistics ?>],
										color: '#ffa000',
										lines: { show: true, lineWidth: 6 },
										curvedLines: { apply: true }
									},
									{
										data: [<?= $call_duration_statistics ?>],
										color: '#ffa000',
										points: {show: true}
									}
								],
								{
									series: {
										curvedLines: { active: true }
									},
									xaxis: {
										show: true,
										font: { size: 12, lineHeight: 10, style: 'normal', weight: '100',family: 'lato', variant: 'small-caps', color: '#a2a0a0' }
									},
									yaxis: {
										show: true,
										font: { size: 12, lineHeight: 10, style: 'normal', weight: '100',family: 'lato', variant: 'small-caps', color: '#a2a0a0' }
									},
									grid: { color: '#a2a0a0', hoverable: true, margin: 8, labelMargin: 8, borderWidth: 0, backgroundColor: '#fff' },
									tooltip: true,
									tooltipOpts: { content: 'секунда: %x.0, дозвонов: %y.0',  defaultTheme: false, shifts: { x: 0, y: -40 } },
									legend: { show: false }
								}" style="width: 100%; height: 230px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            <? else: ?>
                <div class="row">
                    <div class="col-md-8 col-md-offset-2">
                        <div class="text-center">
                            <h3 class="m-0 m-h-md">Рассылка #<?= $_GET['id'] ?> не найдена</h3>
                            <a role="button" style="min-width: 160px;" class="btn btn-primary rounded btn-rounded m-b-xl" href="/responders">Вернуться назад</a>
                        </div>
                    </div>
                </div>
            <? endif; ?>
        </section>
    </div>
    <? include 'footer.php' ?>

</main>

</body>


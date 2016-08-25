<?

$emotions = array(
    "mixed" => "Переменная",
    "good" => "Доброжелательная",
    "neutral" => "Нейтральная",
    "evil" => "Злая",
);
$speakers = array(
    "ermil" => "Мужской",
    "jane" => "Женский",
);
?>
<script>
    window.onload = function () {
        app.recorder.maxAudioDuration = <?= $api->get_tariff('max_audio_duration', API::TARIFF_TYPE_TASK) ?>;


        <?if($task):?>
        <? $task_scripts = $api->get_task_scripts($task['id']); ?>
        app.taskConstructor.task = <?= $task ? json_encode($task) : '{}'; ?>;
        app.taskConstructor.task.scripts = <?=$task_scripts ? json_encode($task_scripts) : '{}';?>;
        <?endif;?>

        app.taskConstructor.uploadFolder = '/<?= API::DOWNLOADS_DIR ?>/';
        app.taskConstructor.init();
    }
</script>

<div class="m-h-md">
    <? if ($task): ?>
        <div class="pull-left">
            <button class="btn btn-primary" id="showTaskForm">
                <i class="fa fa-plus"></i> Редактировать рассылку
            </button>
            <button style="display:none;" class="btn btn-primary" id="hideTaskForm">
                <i class="fa fa-minus"></i> Скрыть форму
            </button>


            <? if ($task['status'] == API::TASK_STATUS_SCHEDULED): ?>
                <form class="form-ajax" action="/ajax.php" style="display: inline-block">
                    <input type="hidden" name="task_pause" value="<?= $task['id'] ?>"/>
                    <button class="btn btn-default" type="submit">
                        <i class="glyphicon glyphicon-pause"></i> Поставить на паузу
                    </button>
                    <br/>
                    <div role="alert" class="alert alert-warning alert-dismissible form-ajax-message-wrapper">
                        <button aria-label="Close" data-dismiss="alert" class="close" type="button">
                            <span aria-hidden="true">×</span></button>
                        <span class="form-ajax-message"></span>
                    </div>
                </form>
            <? elseif ($task['status'] == API::TASK_STATUS_PAUSED): ?>
                <form class="form-ajax" action="/ajax.php" style="display: inline-block">
                    <input type="hidden" name="task_start" value="<?= $task['id'] ?>"/>
                    <button class="btn btn-default" type="submit">
                        <i class="glyphicon glyphicon-play"></i> Активировать рассылку
                    </button>
                    <br/>
                    <div role="alert" class="alert alert-warning alert-dismissible form-ajax-message-wrapper">
                        <button aria-label="Close" data-dismiss="alert" class="close" type="button">
                            <span aria-hidden="true">×</span></button>
                        <span class="form-ajax-message"></span>
                    </div>
                </form>
            <? endif ?>

        </div>
        <div class="pull-right">
            <? if ($task['status'] == API::TASK_STATUS_SCHEDULED || $task['status'] == API::TASK_STATUS_COMPLETED): ?>
                <form class="form-ajax" action="/ajax.php" style="display: inline-block">
                    <input type="hidden" name="delete_task" value="<?= $task['id'] ?>"/>
                    <button class="btn btn-default" type="submit" onclick="return confirm('Удалить?');">
                        <i class="fa fa-trash"></i> Удалить рассылку
                    </button>
                    <br/>
                    <div role="alert" class="alert alert-warning alert-dismissible form-ajax-message-wrapper">
                        <button aria-label="Close" data-dismiss="alert" class="close" type="button">
                            <span aria-hidden="true">×</span></button>
                        <span class="form-ajax-message"></span>
                    </div>
                </form>
            <? endif ?>
        </div>
    <? else: ?>
        <div class="pull-left">
            <button class="btn btn-primary" id="showTaskForm"><i class="fa fa-plus"></i> Добавить рассылку
            </button>
            <button style="display:none;" class="btn btn-primary" id="hideTaskForm">
                <i class="fa fa-minus"></i> Скрыть форму
            </button>
        </div>
    <? endif ?>
    <div class="clearfix"></div>
</div>

<div class="widget" id="taskForm" style="display:none;">
    <header class="widget-header">
        <? if ($task): ?>
            <h4 class="widget-title">Редактировать голосовую рассылку</h4>
        <? else: ?>
            <h4 class="widget-title">Создать голосовую рассылку</h4>
        <? endif; ?>
    </header>
    <hr class="widget-separator">
    <div class="widget-body">


        <div class="form-group">
            <div class="clearfix">
                <div class="col-sm-8">

                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label">Список рассылки</label>
                            <div class="col-sm-10">
                                <div class="input-group">
                                    <select name="list_id" class="form-control select2" data-plugin="select2" data-width="100%" required multiple>
                                        <? foreach ($api->get_lists($api->user()['id']) as $l): ?>
                                            <option value="<?= $l['id'] ?>"><?= $l['title']; ?> [<?= $l['total']; ?>]</option>
                                        <? endforeach; ?>
                                    </select>

                                    <a href="/lists#add" class="input-group-addon btn btn-default">Добавить список</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label">Номер с которого звоним</label>
                            <div class="col-sm-10">
                                <div class="input-group">
                                    <select name="phone_id" class="form-control select2" data-plugin="select2" data-width="100%">
                                        <option value=""></option>
                                        <? foreach ($api->get_personal_phone_numbers($api->user()['id']) as $p): ?>
                                            <option value="<?= $p['id'] ?>">+<?= $p['phone_number']; ?></option>
                                        <? endforeach; ?>
                                    </select>

                                    <a href="/payment" target="_blank" class="input-group-addon btn btn-default">Купить номер</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label">Название рассылки</label>
                            <div class="col-sm-10">
                                <input name="title" type="text" class="form-control" value="" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="row">
                            <label class="col-sm-2 control-label">Старт в</label>
                            <div class="col-sm-7">
                                <input name="date_start" type="text" id="taskFormDatetime" class="form-control" data-plugin="datetimepicker" data-options="{ format:'YYYY-MM-DD HH:mm', minDate: '<?= date("Y-m-d H:00", strtotime(date("Y-m-d H:i:s")." +1 hour")); ?>', locale: 'ru' }" value="" required autocomplete="off">
                            </div>
                            <div class="col-sm-3">
                                <div class="checkbox checkbox-primary">
                                    <input id="taskFormDatetimeNow" type="checkbox" name="date_start" value="now"/>
                                    <label for="taskFormDatetimeNow">Начать сейчас</label>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div id="calculator" data-calculator="task" class="col-sm-4 text-right"></div>
            </div>
        </div>
        <div class="form-group">
            <div class="row">
                <div class="col-sm-5 col-sm-offset-1">
                    <label>
                        Озвучить звонок своим голосом
                        <input type="checkbox" name="robo_voice" value="1" data-switchery data-secondaryColor="#188ae2">
                        Озвучить звонок роботом
                    </label>
                </div>
                <div class="col-sm-5">
                    <label>Получать ответ кнопкой
                        <input type="checkbox" name="voice_action" value="1" data-switchery data-secondaryColor="#188ae2">
                        Получать ответ голосом
                    </label>

                </div>

                <div class="col-sm-10 col-sm-offset-1">
                    <br/>
                    <small>
                        При озвучивании звонка при помощи нашего робота, возможна персонализация приветствий с подстановкой имён клиентов (если это поле есть в списке рассылки).

                        Вы так же можете принимать команды от клиентов не только с помощью нажатий на клавиатуре, но и с помощью нашего сервиса распознавания голоса.
                    </small>
                </div>
            </div>
        </div>


        <div id="task-scripts-container" class="form-group">
            <div class="task-script" data-task-script-id="1">

                <div class="row">
                    <div class="col-sm-1 task-script-title">
                        <div>
                            Скрипт#<span class="task-script-id">1</span>
                        </div>
                    </div>
                    <div class="col-sm-10 task-script-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <button class="btn btn-default btn-block" type="button" data-action-type="greeting" onclick="app.taskConstructor.modalOpen(this);">
                                    <i class="fa fa-play"></i>
                                    Приветствие
                                </button>
                            </div>
                            <div class="col-sm-3">
                                <button class="btn btn-default btn-block" type="button" data-action-type="message" onclick="app.taskConstructor.modalOpen(this);">
                                    <i class="fa fa-play"></i>
                                    Основное сообщение
                                </button>
                            </div>
                            <div class="col-sm-3">

                                <div class="task-script-phone-label text-center">
                                    <h6>Действие по нажатию кнопки:</h6>
                                    <h6 style="display: none;">Действие по произношению слова:</h6>
                                </div>

                                <div class="task-script-phone">

                                    <div class="task-script-button-actions">
                                        <? for ($i = 1; $i <= 10; $i++): ?>
                                            <button class="btn btn-default" type="button" data-action-type="button_actions" data-action-id="<?= ($i % 10) ?>" onclick="app.taskConstructor.modalOpen(this);"><?= ($i % 10) ?></button>
                                        <? endfor; ?>
                                    </div>
                                    <div class="task-script-voice-actions">
                                        <? for ($i = 1; $i <= 5; $i++): ?>
                                            <button class="btn btn-default" type="button" data-action-type="voice_actions" data-action-id="Слово <?= ($i) ?>" onclick="app.taskConstructor.modalOpen(this);">Слово <?= ($i) ?></button>
                                        <? endfor; ?>
                                    </div>

                                </div>

                            </div>
                            <div class="col-sm-3">
                                <img src="/assets/images/arrow_down.png" class="img-responsive task-script-goodbye-cap" style="margin: 100px 0; display: none;"/>
                                <button class="btn btn-default btn-block" type="button" data-action-type="goodbye" onclick="app.taskConstructor.modalOpen(this);">
                                    <i class="fa fa-play"></i>
                                    Завершение разговора
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

        <div class="row">
            <div class="col-sm-10 col-sm-offset-1">
                <button class="btn btn-default btn-block large add-task-script" type="button">
                    <i class="fa fa-plus"></i>
                    Добавить скрипт
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-10 col-sm-offset-1">


                <div class="text-center">
                    <div role="alert" class="alert alert-warning alert-dismissible form-ajax-message-wrapper">
                        <button aria-label="Close" data-dismiss="alert" class="close" type="button">
                            <span aria-hidden="true">×</span></button>
                        <span class="form-ajax-message"></span>
                    </div>

                    <button class="btn btn-primary large" type="button" onclick="app.taskConstructor.prepareSaveTask(1);">
                        <i class="fa fa-save"></i>
                        Сохранить и активировать
                    </button>
                    <button class="btn btn-primary large" type="button" onclick="app.taskConstructor.prepareSaveTask(0);">
                        <i class="fa fa-save"></i>
                        Сохранить
                    </button>
                </div>
            </div>
        </div>


    </div>
</div>


<div class="modal fade audiomodal" id="modalAudio" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Аудиозапись сообщения</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">
                    Запись звука поддерживается в Chrome и Firefox. Используйте эти современные браузеры.
                    <br>
                    Максимальный размер файла: 5Мб
                    <br>
                    Максимально допустимая длина аудиозаписи по вашему тарифу: <?= $api->get_tariff('max_audio_duration', API::TARIFF_TYPE_TASK) ?> секунд.
                </p>


                <div class="form-group row">
                    <div class="col-sm-6">
                        <label class="btn btn-primary btn-block recupload">
                            <i class="fa fa-upload"></i><span>Загрузить свой mp3-файл</span>
                            <input type="file" name="mp3" class="audioinput" style="display: none;" accept=".mp3" onchange="app.taskConstructor.addAudioFile(this);"/>
                        </label>
                    </div>
                    <div class="col-sm-6">
                        <button type="button" class="btn btn-primary btn-block recstart" onclick="app.recorder.startRecording();">
                            <i class="fa fa-microphone"></i><span>Захватить голос с микрофона</span></button>
                        <button type="button" class="btn btn-primary recstop" onclick="app.recorder.stopRecording();" style="display: none;">
                            <i class="fa fa-stop"></i><span>Стоп</span></button>
                        <button type="button" class="btn btn-primary reccancel" onclick="app.recorder.cancelRecording();" style="display: none;">
                            <i class="fa fa-close"></i><span>Отменить</span></button>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-sm-12">
                        <canvas width="400" height="100" class="analyzer"></canvas>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-sm-12">
                        <h4>Текущий файл:</h4>
                        <div class="reclist"></div>

                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalText" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Озвучить текст основного сообщения</h4>
            </div>
            <div class="modal-body">
                <p>
                    <small>
                        Максимально допустимая длина текста по вашему тарифу: <?= $api->get_tariff('max_message_length', API::TARIFF_TYPE_TASK) ?> символов.
                        <br/>
                        В приветсвии и прощании можно подставлять шаблон <code>{{first_name}}</code> - это будет заменено на имя пользователя в вашем списке контактов.
                        <br/>
                        Услуга кастомизации приветствий/прощаний платная.
                    </small>
                </p>
                <div class="form-group">
                    <label for="message01">Текст для озвучки</label>
                    <textarea id="message01" class="form-control" name="message" placeholder="Введите текст основного сообщения" maxlength="<?= $api->get_tariff('max_message_length', API::TARIFF_TYPE_TASK) ?>" data-plugin="maxlength" data-options="{  threshold: 10, warningClass: 'label label-warning', limitReachedClass: 'label label-danger', placement: 'bottom', message: '%charsTyped% / %charsTotal%' }"></textarea>
                </div>
                <div class="form-group">
                    <label for="speaker01">Голос</label>
                    <select id="speaker01" class="form-control" name="speaker">
                        <? foreach ($speakers as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                        <? endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalButtonActions" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Действие по нажатию кнопки</h4>
            </div>


            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-6">
                        <p>ДЕЙСТВИЕ:</p>


                        <div class="form-group">

                            <div class="radio">
                                <input id="radio010" type="radio" name="action" value="" checked/>
                                <label for="radio010">Ничего не делать</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio011" type="radio" name="action" value="sms"/>
                                <label for="radio011">Отправить смс
                                    <input type="text" name="sms" value="" class="form-control" maxlength="70" data-plugin="maxlength" data-options="{ threshold: 10, warningClass: 'label label-warning', limitReachedClass: 'label label-danger', placement: 'bottom', message: '%charsTyped% / %charsTotal%' }"/>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio012" type="radio" name="action" value="connect"/>
                                <label for="radio012">Соединить с оператором по номеру
                                    <input type="text" name="connect" class="form-control phone-input" value="" maxlength="12" data-plugin="maxlength" data-options="{ threshold: 10, warningClass: 'label label-warning', limitReachedClass: 'label label-danger', placement: 'bottom', message: '%charsTyped% / %charsTotal%' }"/>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio013" type="radio" name="action" value="record"/>
                                <label for="radio013">Записать ответ клиента</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio014" type="radio" name="action" value="vote"/>
                                <label for="radio014">Кнопка опроса</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <p>ЧТО СДЕЛАТЬ ПОСЛЕ:</p>

                        <div class="form-group">

                            <div class="radio">
                                <input id="radio015" type="radio" name="after_action" value="" checked/>
                                <label for="radio015">Перейти к след. скрипту</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="radio">
                                <input id="radio016" type="radio" name="after_action" value="goto"/>
                                <label for="radio016">Перейти к скрипту
                                    <select name="goto" class="form-control"></select>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio017" type="radio" name="after_action" value="replay"/>
                                <label for="radio017">Повторить аудиосообщение</label>
                            </div>
                        </div>
                        <div class="form-group">

                            <div class="radio">
                                <input id="radio018" type="radio" name="after_action" value="end"/>
                                <label for="radio018">Закончить звонок</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


        </div>

    </div>
</div>
<div class="modal fade" id="modalVoiceActions" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Действие по произношению ключевого слова</h4>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label for="">Введите ключевое слово для распознавания
                        <input type="text" name="keyword" value="" class="form-control" maxlength="70" data-plugin="maxlength" data-options="{ threshold: 10, warningClass: 'label label-warning', limitReachedClass: 'label label-danger', placement: 'bottom', message: '%charsTyped% / %charsTotal%' }"/>
                    </label>
                </div>
                <br/>
                <div class="row form-group">
                    <div class="col-sm-6">
                        <p>ДЕЙСТВИЕ:</p>
                        <div class="form-group">

                            <div class="radio">
                                <input id="radio001" type="radio" name="action" value="" checked/>
                                <label for="radio001">Ничего не делать</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio002" type="radio" name="action" value="sms"/>
                                <label for="radio002">Отправить смс
                                    <input type="text" name="sms" value="" class="form-control" maxlength="70" data-plugin="maxlength" data-options="{ threshold: 10, warningClass: 'label label-warning', limitReachedClass: 'label label-danger', placement: 'bottom', message: '%charsTyped% / %charsTotal%' }"/>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio003" type="radio" name="action" value="connect"/>
                                <label for="radio003">Соединить с оператором по номеру
                                    <input type="text" name="connect" class="form-control phone-input" value="" maxlength="12" data-plugin="maxlength" data-options="{ threshold: 10, warningClass: 'label label-warning', limitReachedClass: 'label label-danger', placement: 'bottom', message: '%charsTyped% / %charsTotal%' }"/>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio004" type="radio" name="action" value="record"/>
                                <label for="radio004">Записать ответ клиента</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio005" type="radio" name="action" value="vote"/>
                                <label for="radio005">Кнопка опроса</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <p>ЧТО СДЕЛАТЬ ПОСЛЕ:</p>

                        <div class="form-group">

                            <div class="radio">
                                <input id="radio006" type="radio" name="after_action" value="" checked/>
                                <label for="radio006">Перейти к след. скрипту</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio007" type="radio" name="after_action" value="goto"/>
                                <label for="radio007">Перейти к скрипту
                                    <select name="goto" class="form-control"></select>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="radio">
                                <input id="radio008" type="radio" name="after_action" value="replay"/>
                                <label for="radio008">Повторить аудиосообщение</label>
                            </div>
                        </div>
                        <div class="form-group">

                            <div class="radio">
                                <input id="radio009" type="radio" name="after_action" value="end"/>
                                <label for="radio009">Закончить звонок</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

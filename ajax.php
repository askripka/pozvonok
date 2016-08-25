<?php
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

require_once __DIR__.'/_protected/cpapi.php';
require_once __DIR__.'/_protected/classes/smscApi.php';
require_once __DIR__.'/_protected/classes/ipBlockerApi.php';
require_once __DIR__.'/_protected/classes/encodingDetector/encodingDetector.php';
require_once __DIR__.'/_protected/classes/csvParser.php';
require_once __DIR__.'/_protected/classes/exportData.php';

$api = new CPAPI();
$sms = new smscApi(API::SMSC_LOGIN, API::SMSC_PASSWORD);


//JS SCRIPT AUTO QUERIES
if ($_POST['get_tasks_progress']) {
    $tasks = $api->get_tasks($api->user()['id'], API::TASK_STATUS_ACTIVE);
    $result = array();
    foreach ($tasks as $task) {
        $result[$task['id']] = str_replace(',', '.', round(($task['total_calls'] / $task['total_list_amount']), 2));
    }
    exit(json_encode($result));
}

if ($_POST['get_task_progress']) {
    $task = $api->get_task($_POST['get_task_progress'], $api->user()['id']);
    $result = array();
    if ($task) {
        $result['value'] = intval(($task['total_calls'] / $task['total_list_amount']) * 100);
    }
    exit(json_encode($result));
}


//LOGOUT QUERIES
if ($_POST['logout']) {
    $api->logout();
    exit(json_encode(array('reload' => 1)));
}


if ($_POST['get_calculator']=='task') {

    try {
        if (!$api->user()) {
            throw new Exception('Пройдите авторизацию.');
        }

        $tariff = $api->get_tariff('', API::TARIFF_TYPE_TASK);
        if (!$tariff) {
            throw new Exception('Тариф не найден.');
        }

        if (!$_POST['list_id']) {
            throw new Exception('Список пользователей не указан.');
        }
        $list_count = $api->count_list_users($_POST['list_id']);
        if (!$list_count) {
            throw new Exception('Пустой список рассылки.');
        }

        $audio_duration = $actions_duration = 0;
        $actions_cost = $yandex_speech_customization_cost = 0;

        foreach ($_POST['scripts'] as $k => $script) {

            if ($_POST['robo_voice']) {
                $audio_duration += mb_strlen($script['greeting'].$script['message'].$script['goodbye'], 'UTF-8') / 32;
                if (preg_match('/{{.*}}/', $script['greeting'])) {
                    $yandex_speech_customization_cost += $tariff['speech_customization_cost']['value'];
                }
                if (preg_match('/{{.*}}/', $script['goodbye'])) {
                    $yandex_speech_customization_cost += $tariff['speech_customization_cost']['value'];
                }

            } else {

                if (file_exists(__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['greeting_mp3'])) {
                    $audio_duration += exec("ffprobe -i ".__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['greeting_mp3']." -show_entries format=duration -v quiet -of csv='p=0'");
                } elseif ($script['greeting_mp3_duration']) {
                    $audio_duration += $script['greeting_mp3_duration'];
                }

                if (file_exists(__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['message_mp3'])) {
                    $audio_duration += exec("ffprobe -i ".__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['message_mp3']." -show_entries format=duration -v quiet -of csv='p=0'");
                } elseif ($script['message_mp3_duration']) {
                    $audio_duration += $script['message_mp3_duration'];
                }

                if (file_exists(__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['goodbye_mp3'])) {
                    $audio_duration += exec("ffprobe -i ".__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['goodbye_mp3']." -show_entries format=duration -v quiet -of csv='p=0'");
                } elseif ($script['goodbye_mp3_duration']) {
                    $audio_duration += $script['goodbye_mp3_duration'];
                }


            }

            if ($_POST['voice_action'] && $script['voice_actions']) {

                $action_duration = 0;
                $action_cost = 0;

                foreach ($script['voice_actions'] as $v) {

                    if ($v['action']['connect'] && $action_duration < 60) {
                        $action_duration = 60;
                        $action_cost = $tariff['voice_action_cost']['value'] + $tariff['action_connect_cost']['value'];
                    } elseif ($v['action']['record'] && $action_duration < 30) {
                        $action_duration = 30;
                        $action_cost = $tariff['voice_action_cost']['value'] + $tariff['action_record_cost']['value'];
                    } elseif ($v['action']['sms'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['voice_action_cost']['value'] + $tariff['action_sms_cost']['value'];
                    } elseif ($v['action']['vote'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['voice_action_cost']['value'] + $tariff['action_vote_cost']['value'];
                    }
                }

                $actions_duration += $action_duration;
                $actions_cost += $action_cost;

            } elseif (!$_POST['voice_action'] && $script['button_actions']) {

                $action_duration = 0;
                $action_cost = 0;

                foreach ($script['button_actions'] as $v) {

                    if ($v['action']['connect'] && $action_duration < 60) {
                        $action_duration = 60;
                        $action_cost = $tariff['action_connect_cost']['value'];
                    } elseif ($v['action']['record'] && $action_duration < 30) {
                        $action_duration = 30;
                        $action_cost = $tariff['action_record_cost']['value'];
                    } elseif ($v['action']['sms'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['action_sms_cost']['value'];
                    } elseif ($v['action']['vote'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['action_vote_cost']['value'];
                    }
                }

                $actions_duration += $action_duration;
                $actions_cost += $action_cost;

            }

        }
        $call_total_cost = (($audio_duration + $actions_duration) * ($tariff['minute_cost']['value'] / 60) * $list_count + ($actions_cost + $yandex_speech_customization_cost) * $list_count) * 0.6;
        exit(json_encode(array(
            'result' => 1, 'message' =>
                "
                <h4>Калькулятор рассылки</h4>
                <span>Общая длительность речевых файлов: <strong>".round($audio_duration)."сек</strong></span><br>
                <span>Общая длительность действий: <strong>".$actions_duration."сек</strong></span><br>
                <span>Общая длительность скриптов: <strong>".round($audio_duration + $actions_duration)."сек</strong></span><br>
                <span>Рекоменд. сумма для обзвона <strong>".$list_count."</strong> ".API::plural($list_count, 'контактов', 'контакта', 'контактов').": <strong>".round($call_total_cost / 100)." ".API::CURRENCY."</strong></span><br>
                ---<br>
                <span class='".($api->user()['balance'] < $call_total_cost ? 'danger' : '')."'>Сумма на вашем счету: <strong>".($api->user()['balance'] / 100)." ".API::CURRENCY."</strong></span>
                "
        )));

    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}




if ($_POST['get_calculator']=='responder') {

    try {
        if (!$api->user()) {
            throw new Exception('Пройдите авторизацию.');
        }

        $tariff = $api->get_tariff('', API::TARIFF_TYPE_RESPONDER);
        if (!$tariff) {
            throw new Exception('Тариф не найден.');
        }

        $audio_duration = $actions_duration = 0;
        $actions_cost = $yandex_speech_customization_cost = 0;

        foreach ($_POST['scripts'] as $k => $script) {

            if ($_POST['robo_voice']) {
                $audio_duration += mb_strlen($script['greeting'].$script['message'].$script['goodbye'], 'UTF-8') / 32;
                if (preg_match('/{{.*}}/', $script['greeting'])) {
                    $yandex_speech_customization_cost += $tariff['speech_customization_cost']['value'];
                }
                if (preg_match('/{{.*}}/', $script['goodbye'])) {
                    $yandex_speech_customization_cost += $tariff['speech_customization_cost']['value'];
                }

            } else {

                if (file_exists(__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['greeting_mp3'])) {
                    $audio_duration += exec("ffprobe -i ".__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['greeting_mp3']." -show_entries format=duration -v quiet -of csv='p=0'");
                } elseif ($script['greeting_mp3_duration']) {
                    $audio_duration += $script['greeting_mp3_duration'];
                }

                if (file_exists(__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['message_mp3'])) {
                    $audio_duration += exec("ffprobe -i ".__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['message_mp3']." -show_entries format=duration -v quiet -of csv='p=0'");
                } elseif ($script['message_mp3_duration']) {
                    $audio_duration += $script['message_mp3_duration'];
                }

                if (file_exists(__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['goodbye_mp3'])) {
                    $audio_duration += exec("ffprobe -i ".__DIR__.'/'.API::DOWNLOADS_DIR.'/'.$script['goodbye_mp3']." -show_entries format=duration -v quiet -of csv='p=0'");
                } elseif ($script['goodbye_mp3_duration']) {
                    $audio_duration += $script['goodbye_mp3_duration'];
                }


            }

            if ($_POST['voice_action'] && $script['voice_actions']) {

                $action_duration = 0;
                $action_cost = 0;

                foreach ($script['voice_actions'] as $v) {

                    if ($v['action']['connect'] && $action_duration < 60) {
                        $action_duration = 60;
                        $action_cost = $tariff['voice_action_cost']['value'] + $tariff['action_connect_cost']['value'];
                    } elseif ($v['action']['record'] && $action_duration < 30) {
                        $action_duration = 30;
                        $action_cost = $tariff['voice_action_cost']['value'] + $tariff['action_record_cost']['value'];
                    } elseif ($v['action']['sms'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['voice_action_cost']['value'] + $tariff['action_sms_cost']['value'];
                    } elseif ($v['action']['vote'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['voice_action_cost']['value'] + $tariff['action_vote_cost']['value'];
                    }
                }

                $actions_duration += $action_duration;
                $actions_cost += $action_cost;

            } elseif (!$_POST['voice_action'] && $script['button_actions']) {

                $action_duration = 0;
                $action_cost = 0;

                foreach ($script['button_actions'] as $v) {

                    if ($v['action']['connect'] && $action_duration < 60) {
                        $action_duration = 60;
                        $action_cost = $tariff['action_connect_cost']['value'];
                    } elseif ($v['action']['record'] && $action_duration < 30) {
                        $action_duration = 30;
                        $action_cost = $tariff['action_record_cost']['value'];
                    } elseif ($v['action']['sms'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['action_sms_cost']['value'];
                    } elseif ($v['action']['vote'] && $action_duration < 5) {
                        $action_duration = 5;
                        $action_cost += $tariff['action_vote_cost']['value'];
                    }
                }

                $actions_duration += $action_duration;
                $actions_cost += $action_cost;

            }

        }
        $call_total_cost = (($audio_duration + $actions_duration) * ($tariff['minute_cost']['value'] / 60) + ($actions_cost + $yandex_speech_customization_cost));
        exit(json_encode(array(
            'result' => 1, 'message' =>
                "
                <h4>Калькулятор рассылки</h4>
                <span>Общая длительность речевых файлов: <strong>".round($audio_duration)."сек</strong></span><br>
                <span>Общая длительность действий: <strong>".$actions_duration."сек</strong></span><br>
                <span>Общая длительность скриптов: <strong>".round($audio_duration + $actions_duration)."сек</strong></span><br>
                <span>Приблизительная стоимость входящего контакта: <strong>".round($call_total_cost / 100)." ".API::CURRENCY."</strong></span><br>
                <span>Минимальная сумма для работа ответчика: <strong>".round($tariff['min_account_balance']['value'] / 100)." ".API::CURRENCY."</strong></span><br>
                ---<br>
                <span class='".($api->user()['balance'] < $tariff['min_account_balance']['value'] ? 'danger' : '')."'>Сумма на вашем счету: <strong>".($api->user()['balance'] / 100)." ".API::CURRENCY."</strong></span>
                "
        )));

    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}



//не более 60 запросов за 1 минуту с 1 IP
if (IPBlocker::is_spammer(60, 60)) {
    exit(json_encode(array(
        'message' => 'Вы слишком часто делали запросы, попробуйте позже.'
    )));
}

if ($_POST['timezone']) {
    $_POST['timezone'] = preg_replace('/[^a-zA-Z\/]/', '', $_POST['timezone']);

    $api->update_account(array(
        'id' => $api->user()['id'],
        'timezone' => $_POST['timezone'],
    ));
}


if ($_POST['register']) {
    unset($_POST['register']);


    if ($_POST['first_name'] && $_POST['last_name'] && $_POST['phone'] && $_POST['email']) {
        $password = $api->generate_password();
        $id = $api->insert_account(array(
            'phone' => $_POST['phone'],
            'email' => $_POST['email'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'balance' => 0,
            'status' => API::ACCOUNT_STATUS_ACTIVE,
        ));

        if (!$id) {
            exit(json_encode(array('message' => $api->get_error())));
        }

        //Отправить СМС с паролем входа
        $r = $sms->send_sms($_POST['phone'], "Позвоночный: Пароль для входа в личный кабинет ".$password);
//        var_dump($r);

        //Отравить на имейл
        $api->send_email_smtp($_POST['email'], "Позвоночный: Данные для входа в личный кабинет", array(
            'Адрес' => 'https://cp.pozvon.ru/',
            'Логин' => 'Ваш номер телефона, указанный при регистрации',
            'Пароль' => $password,
        ));

        //Отправить уведомление АДМИНАМ
        $api->send_email_smtp($api->get_settings('admin-email'), "Новая регистрация клиента", $_POST);

        exit(json_encode(array(
            'redirect' => '/login',
            'message' => 'Регистрация прошла успешно. Пароль для входа в личный кабинет отправлен на ваш телефон и почту.',
        )));

    } else {
        exit(json_encode(array('message' => 'Заполните все поля формы.')));
    }

}


if ($_POST['login']) {
    if ($_POST['phone'] && $_POST['password']) {
        if ($api->login($_POST['phone'], $_POST['password'])) {
            exit(json_encode(array(
                'reload' => 1
            )));
        } else {
            exit(json_encode(array('message' => $api->get_error())));
        }
    } else {
        exit(json_encode(array('message' => 'Введите свой номер телефона и пароль.')));
    }
}

if ($_POST['recover_password'] && $_POST['phone']) {
    $_SESSION['recover_password_attempts']++;

    if ($_SESSION['recover_password_attempts'] < 3) {
        $acc = $api->get_account('', $_POST['phone']);

        if (!$acc) {
            exit(json_encode(array('message' => 'Пользователь с таким номером телефона не зарегистрован.')));
        }
        $password = $api->generate_password();

        $result = $api->update_account(array(
            'id' => $acc['id'],
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ));

        if (!$result) {
            exit(json_encode(array('message' => 'Возникла ошибка при обновлении пароля. '.$api->get_error())));
        }

        //Отправить СМС с паролем входа
        $r = $sms->send_sms($_POST['phone'], "Позвоночный: Пароль для входа в личный кабинет ".$password);

        //Отравить на имейл
        $api->send_email_smtp($_POST['email'], "Позвоночный: Данные для входа в личный кабинет", array(
            'Адрес' => 'https://cp.pozvon.ru/',
            'Логин' => 'Ваш номер телефона, указанный при регистрации',
            'Пароль' => $password,
        ));

        exit(json_encode(array(
            'result' => 1,
            'reload' => 1,
            'message' => 'Новый пароль выслан на Ваш номер '.$_POST['phone'].' и почту '.$acc['email']
        )));


    } else {
        exit(json_encode(array('message' => 'Вы предпринимали слишком много попыток восстановления пароля. Ваш аккаунт временно заблокирован.')));
    }

}


if ($_POST['edit_password']) {
    if (!$api->user()) {
        exit(json_encode(array('message' => 'Доступ запрещен. Сначала войдите или авторизируйтесь.')));
    }


    if ($_POST['password'] != $_POST['password_сonfirm']) {
        exit(json_encode(array('message' => 'Пароли не совпадают!')));
    }

    if (strlen($_POST['password']) < 6 || strlen($_POST['password']) > 20 || strpos($_POST['password'], 0x20) !== FALSE) {
        exit(json_encode(array('message' => 'Пароль должен быть не меньше 6ти символов и не больше 20, без пробелов.')));
    }

    $r = $api->update_account(array(
        'id' => $_SESSION['uid'],
        'password' => password_hash(mb_strtoupper($_POST['password']), PASSWORD_DEFAULT),
    ));

    if ($r) {
        exit(json_encode(array(
            'reload' => 1,
            'result' => 1,
            'message' => 'Пароль успешно обновлен.'
        )));
    } else {
        exit(json_encode(array(
            'message' => 'Ошибка при обновлении пароля.'
        )));
    }
}

if ($_POST['delete_task']) {
    try {
        $task = $api->get_task($_POST['delete_task'], $api->user()['id']);
        if (!$task) {
            throw new Exception('Рассылка не найдена.');
        }

        if ($task['status'] != API::TASK_STATUS_SCHEDULED) {
            exit(json_encode(array('reload' => 1, 'message' => 'Можно удалять только неактивные рассылки.')));
        }

        $r = $api->delete_task($task['id']);
        if (!$r) {
            throw new Exception('Не удалось удалить рассылку. '.$api->get_error());
        }

        exit(json_encode(array('redirect' => '/')));

    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}

if ($_POST['delete_list']) {
    try {
        $list = $api->get_list($_POST['delete_list'], $api->user()['id']);
        if (!$list) {
            throw new Exception('Список не найден.');
        }

        //Ищем используется ли гдето в тасках
        $in_tasks = $api->get_tasks($api->user()['id'], array(
            API::TASK_STATUS_SCHEDULED, API::TASK_STATUS_ACTIVE
        ), $list['id']);
        if ($in_tasks) {
            throw new Exception('Список используется в активных или запланированных рассылках.');
        }

        $r = $api->delete_list($list['id']);
        if (!$r) {
            throw new Exception('Не удалось удалить список. '.$api->get_error());
        }

        exit(json_encode(array('redirect' => '/lists')));

    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}


if ($_POST['create_list']) {
    unset($_POST['create_list']);

    try {

        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        if (!$_POST['title']) {
            throw new Exception('Введите название списка.');
        }

        if (!$_FILES['import']['name']) {
            throw new Exception('Выберите подходящий файл для импорта.');
        }

        if (!in_array($_FILES['import']['type'], array(
            'text/comma-separated-values',
            'text/csv',
            'text/plain',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ))
        ) {
            throw new Exception('Не поддерживаемый тип файла.');
        }

        $size_limit = 1048576 * 2; //Лимит 2МБ
        if ($_FILES['import']['size'] > $size_limit) {
            throw new Exception('Размер файла превышает '.($size_limit / 1024 / 1024).'Мб. Используйте файл меньшего размера');
        }

        if ($_FILES['import']['type'] == 'application/vnd.ms-excel') {
            exec("LANG=ru_RU.UTF-8; xls2csv '".$_FILES['import']['tmp_name']."' > '".$_FILES['import']['tmp_name']."csv'");
            $file = $_FILES['import']['tmp_name'].'csv';
            if (!is_readable($file)) {
                throw new Exception('Не удалось конвертировать xls в csv.');
            }

        } elseif ($_FILES['import']['type'] == 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet') {
            exec("LANG=ru_RU.UTF-8; xlsx2csv '".$_FILES['import']['tmp_name']."' > '".$_FILES['import']['tmp_name']."csv'");
            $file = $_FILES['import']['tmp_name'].'csv';
            if (!is_readable($file)) {
                throw new Exception('Не удалось конвертировать xls в csv.');
            }
        } else {
            $file = $_FILES['import']['tmp_name'];
        }

        $encoding = encodingDetector::detect(file_get_contents($file, null, null, null, 10000));
        $handle = fopen($file, "r");
        if (!$handle) {
            throw new Exception('Не удалось открыть файл.');
        }


        $delimiter = csvParser::get_delimiter($handle);
        $list_id = $api->insert_list(array(
            'account_id' => $api->user()['id'],
            'title' => $_POST['title'],
            'total' => 0,
        ));

        $row = 0;
        while ($str = fgets($handle, 1000)) {
            $phone = null;
            $first_name = null;

            //Построчная перекодировка при надобности
            if ($encoding != 'utf-8') {
                $str = iconv($encoding, 'UTF-8//TRANSLIT', $str);
            }

            //Разбор строки
            $data = str_getcsv($str, $delimiter);
            foreach ($data as $d) {
                if (preg_match('/[78]?(\d{10})/', preg_replace('/\D/', '', $d), $matches) && !$phone) {
                    if ($matches[1]) {
                        $phone = '7'.$matches[1];
                    }
                } elseif (preg_match('/^[a-zа-яё]+$/ui', trim($d)) && !$first_name) {
                    $first_name = $d;
                } elseif ($phone && $first_name) {
                    break;
                }
            }

            //Загоняем в БД
            if ($phone) {
                $r = $api->insert_list_user(array(
                    'list_id' => $list_id,
                    'phone' => $phone,
                    'first_name' => $first_name,
                ));

                if ($r) {
                    $row++;
                }
            }
        }
        fclose($handle);

        if ($row) {
            $api->update_list(array(
                'id' => $list_id,
                'total' => $row,
            ));
            exit(json_encode(array(
                'reload' => 1,
                'message' => 'Список создан. Успешно импортировано '.$row.' строк. <a href="?'.time().'#add">Добавить еще список</a> или <a href="/?'.time().'#add">Планировать рассылку</a>'
            )));
        } else {
            $api->delete_list($list_id);
            exit(json_encode(array('message' => 'Импортировано 0 строк.')));
        }

    } catch (Exception $e) {
        if ($list_id) {
            $api->delete_list($list_id);
        }
        $m = $e->getMessage() ? $e->getMessage() : 'Не удалось загрузить файл и прочитать файл.';
        exit(json_encode(array('message' => $m)));
    }

}

if ($_GET['play']) {
    if (!$api->user()) {
        exit('Прослушивание доступно только для авторизированных пользователей.');
    }

    if (!$_REQUEST['text']) {
        exit('Нет текста для озвучивания.');
    }

    $filepath = "https://tts.voicetech.yandex.net/generate?text=".urlencode($_GET['text'])."&robot=false&format=mp3&lang=ru-RU&speaker=".$_GET['speaker']."&emotion=".$_GET['emotion']."&key=".API::YANDEX_SPEECH_API_KEY;
    if ($handle = fopen($filepath, "rb")) {
        header("Content-Type: audio/mpeg");
        header("Cache-Control: no-cache");
        header("Content-Transfer-Encoding: binary");
        while ($d = fread($handle, 1024)) {
            echo $d;
        }
        fclose($handle);
    } else {
        exit('Не удалось озвучить текст. Возможно превышен размер текста либо превышен лимит запросов к SPEECH API.');
    }

}


if ($_GET['task_export'] && $_GET['task_id']) {
    if (!$api->user()) {
        exit('Доступно только для авторизированных пользователей.');
    }

    $task = $api->get_task($_GET['task_id'], $api->user()['id'], $api->user()['timezone']);
    if (!$task) {
        exit('Рассылка не найдена.');
    }

    $exporter = new ExportDataExcel('browser', $task['title'].'_детализация.xls');
    $exporter->initialize();
    $exporter->addRow(array("Телефон", "Дата звонка", "Статус", "Длительность"));
    $total = $api->count_task_logs($task['id']);

    for ($i = 0; $i < $total; $i += 1000) {
        $logs = $api->get_task_logs_with_phones($task['id'], null, null, $i, $i + 1000);
        foreach ($logs as $log) {
            if ($log['status'] == API::ROBOPHONE_TASK_CALL_STATUS_QUEUED) {
                $log['status'] = "в очереди";
            } elseif ($log['status'] == API::ROBOPHONE_TASK_CALL_STATUS_SUCCESS) {
                $log['status'] = "успешно";
            } elseif ($log['status'] == API::ROBOPHONE_TASK_CALL_STATUS_FAILED) {
                $log['status'] = "занято";
            }
            $exporter->addRow(array(
                $log['phone'], $log['call_datetime'], $log['status'], intval($log['call_duration'])
            ));
        }
    }

    $exporter->finalize();
    exit();

}


if($_GET['responder_export'] && $_GET['responder_id']){
    if (!$api->user()) {
        exit('Доступно только для авторизированных пользователей.');
    }

    $responder = $api->get_responder($_GET['responder_id'], $api->user()['id'], $api->user()['timezone']);
    if (!$responder) {
        exit('ответчик не найден.');
    }


    $exporter = new ExportDataExcel('browser', $responder['title'].'_детализация.xls');
    $exporter->initialize();
    $exporter->addRow(array("Телефон", "Дата звонка", "Статус", "Длительность"));
    $total = $api->count_responder_logs($responder['id']);

    //@TODO Correct Responder Stat Export
    for ($i = 0; $i < $total; $i += 1000) {
        $logs = $api->get_task_logs_with_phones($task['id'], null, null, $i, $i + 1000);
        foreach ($logs as $log) {
            if ($log['status'] == API::ROBOPHONE_TASK_CALL_STATUS_QUEUED) {
                $log['status'] = "в очереди";
            } elseif ($log['status'] == API::ROBOPHONE_TASK_CALL_STATUS_SUCCESS) {
                $log['status'] = "успешно";
            } elseif ($log['status'] == API::ROBOPHONE_TASK_CALL_STATUS_FAILED) {
                $log['status'] = "занято";
            }
            $exporter->addRow(array(
                $log['phone'], $log['call_datetime'], $log['status'], intval($log['call_duration'])
            ));
        }
    }

    $exporter->finalize();
    exit();

}


if ($_GET['list_export'] && $_GET['list_id']) {
    if (!$api->user()) {
        exit('Доступно только для авторизированных пользователей.');
    }

    $list = $api->get_list($_GET['list_id'], $api->user()['id']);
    if (!$list) {
        exit('Список не найден.');
    }

    $exporter = new ExportDataExcel('browser', $list['title'].'.xls');
    $exporter->initialize();
    $exporter->addRow(array("Телефон", "Имя"));
    $total = $api->count_list_users($list['id']);

    for ($i = 0; $i < $total; $i += 1000) {
        $users = $api->get_list_users($list['id'], $i, $i + 1000);
        foreach ($users as $user) {
            $exporter->addRow(array($user['phone'], $user['first_name']));
        }
    }

    $exporter->finalize();
    exit();

}


if ($_POST['task_audio_upload']) {

    try {

        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }


        if ($_FILES['mp3']['error']) {
            throw new Exception('Не удалось загрузить файл. Код ошибки: '.$_FILES['mp3']['error']);
        }

        $size_limit = 1048576 * 5;
        if ($_FILES['mp3']['size'] > $size_limit) {
            throw new Exception('Размер файла превышает допустимый: '.($size_limit / 1024 / 1024).'Мб.');
        }

        if (!in_array($_FILES['mp3']['type'], array(
            'audio/mpeg',
            'audio/x-mpeg',
            'audio/mp3',
            'audio/x-mp3',
            'audio/mpeg3',
            'audio/x-mpeg3',
            'audio/mpg',
            'audio/x-mpg',
            'audio/x-mpegaudio',
        ))
        ) {
            throw new Exception('Не поддерживаемый тип файла: '.$_FILES['mp3']['type']);
        }

        $duration_sec = exec("ffprobe -i ".$_FILES['mp3']['tmp_name']." -show_entries format=duration -v quiet -of csv='p=0'");
        if ($duration_sec - 1 >= $api->get_tariff('max_audio_duration', API::TARIFF_TYPE_TASK)) {
            throw new Exception("Длительность аудиофайла в скрипте#{$_POST['task_script_id']}, дорожке {$_POST['task_action_id']} превышает максимально разрешенную длительность ".$api->get_tariff('max_audio_duration', API::TARIFF_TYPE_TASK)." сек.");
        }

        $_FILES['mp3']['name'] = 'record.mp3';
        $mp3_file = $api->upload_file('mp3', array('mp3'), $size_limit, 1);

        if (!$mp3_file) {
            throw new Exception($api->get_error());
        }

        exit(json_encode(array(
            'filename' => $mp3_file,
        )));


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}


if ($_POST['task_start']) {
    try {
        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        $task = $api->get_task($_POST['task_start'], $api->user()['id']);
        if (!$task) {
            throw new Exception('Рассылка не найдена.');
        }

        if ($task['status'] != API::TASK_STATUS_PAUSED) {
            throw new Exception('Невозможно запустить так как рассылка не стоит на паузе.');
        }


        if ($api->user()['balance'] < $api->get_tariff('min_account_balance', API::TARIFF_TYPE_TASK)) {
            throw new Exception('Невозможно запустить рассылку - сумма баланса на счете недостаточна! Минимальная сумма для старта '.($api->get_tariff('min_account_balance', API::TARIFF_TYPE_TASK) / 100).' '.API::CURRENCY);
        }

        $r = $api->update_task(array(
            'id' => $task['id'],
            'status' => API::TASK_STATUS_SCHEDULED,
        ));

        if (!$r) {
            throw new Exception($api->get_error());
        }

        exit(json_encode(array('reload' => 1)));


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}

if ($_POST['task_pause']) {
    try {
        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        $task = $api->get_task($_POST['task_pause'], $api->user()['id']);
        if (!$task) {
            throw new Exception('Рассылка не найдена.');
        }

        if ($task['status'] != API::TASK_STATUS_SCHEDULED) {
            throw new Exception('Рассылка уже запущена или неактивна.');
        }

        $r = $api->update_task(array(
            'id' => $task['id'],
            'status' => API::TASK_STATUS_PAUSED,
        ));

        if (!$r) {
            throw new Exception($api->get_error());
        }

        exit(json_encode(array('reload' => 1)));


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}


if ($_POST['task_save']) {

    try {

        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        if (!$_POST['list_id']) {
            throw new Exception('Не выбран список телефонов для рассылки.');
        }

        if (!$_POST['title']) {
            throw new Exception('Введите название рассылки.');
        }

        if ($_POST['schedule'] && !$_POST ['date_start']) {
            throw new Exception('Введите дату старта рассылки.');
        }

        if (!$_POST['scripts']) {
            throw new Exception('Ни один скрипт не настроен.');
        }

        if (!$_POST['scripts'][1]) {
            foreach ($_POST['scripts'] as $k => $v) {
                if ($_POST['robo_voice'] && !$v['message']) {
                    throw new Exception('Введите текст основного блока для озвучки.');
                } elseif (!$_POST['robo_voice'] && !$v['message_mp3']) {
                    throw new Exception('Загрузите аудиодорожку основного сообщения.');
                }
            }
        }


        if ($_POST['date_start'] == 'now') {
            $_POST['date_start'] = date("Y-m-d H:i:s");
        } else {
            $dt = new DateTime($_POST['date_start'], new DateTimeZone($api->user()['timezone']));
            $_POST['date_start'] = $dt->setTimezone(new DateTimeZone('UTC'))->format("Y-m-d H:i:s");
        }

        unset($_POST['call_duration_statistics']);

        if ($_POST['schedule']) {

            if ($api->user()['balance'] < $api->get_tariff('min_account_balance', API::TARIFF_TYPE_TASK)) {
                $_POST['status'] = API::TASK_STATUS_NOMONEY;
            } else {
                $_POST['status'] = API::TASK_STATUS_SCHEDULED;
            }


        } else {
            $_POST['status'] = API::TASK_STATUS_PAUSED;
        }

        if ($_POST['id']) {
            //UPDATE
            $r = $api->update_task($_POST);
            if ($r) {
                $scripts = $api->get_task_scripts($_POST['id']);

                $scripts_result = true;
                foreach ($_POST['scripts'] as $script_id=>$script) {

                    $script['script_id'] = $script_id;
                    $script['task_id'] = $_POST['id'];

                    if ($scripts[$script['script_id']]) {
                        if (!$api->update_task_script($script)) {
                            $scripts_result = false;
                        }

                    } else {

                        if (!$api->insert_task_script($script)) {
                            $scripts_result = false;
                        }
                    }
                }
            }


        } else {
            //CREATE
            $_POST['account_id'] = $api->user()['id'];

            $r = $api->insert_task($_POST);

            if ($r) {
                $scripts_result = true;
                foreach ($_POST['scripts'] as $k => $script) {
                    $script['task_id'] = $r;
                    $script['script_id'] = $k;

                    if (!$api->insert_task_script($script)) {
                        $scripts_result = false;
                    }

                }
            }

        }

        if (!$scripts_result || !$r) {
            exit(json_encode(array('message' => $api->get_error())));
        }

        if ($_POST['schedule']) {
            if ($_POST['status'] == API::TASK_STATUS_NOMONEY) {
                exit(json_encode(array(
                    'success' => 1,
                    'message' => 'Рассылка успешно сохранена и поставлена на паузу. Невозможно запустить рассылку - сумма баланса на счете недостаточна! Минимальная сумма для старта '.($api->get_tariff('min_account_balance', API::TARIFF_TYPE_TASK) / 100).' '.API::CURRENCY
                )));
            } else {
                exit(json_encode(array('success' => 1, 'message' => 'Рассылка успешно сохранена и запланирована!')));
            }

        } else {
            exit(json_encode(array('success' => 1, 'message' => 'Рассылка успешно сохранена!')));
        }


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }
}


if ($_POST['responder_start']) {
    try {
        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        $responder = $api->get_responder($_POST['responder_start'], $api->user()['id']);
        if (!$responder) {
            throw new Exception('Ответчик не найден.');
        }

        if ($responder['status'] != API::RESPONDER_STATUS_ACTIVE && $responder['status'] != API::RESPONDER_STATUS_NOMONEY) {
            throw new Exception('Невозможно запустить ответчик.');
        }

        if ($api->user()['balance'] < $api->get_tariff('min_account_balance', API::TARIFF_TYPE_RESPONDER)) {
            throw new Exception('Невозможно запустить ответчик - сумма баланса на счете недостаточна! Минимальная сумма для старта '.($api->get_tariff('min_account_balance', API::TARIFF_TYPE_RESPONDER) / 100).' '.API::CURRENCY);
        }


        $r = $api->update_responder(array(
            'id' => $responder['id'],
            'status' => API::RESPONDER_STATUS_ACTIVE,
        ));

        if (!$r) {
            throw new Exception($api->get_error());
        }

        exit(json_encode(array('reload' => 1)));


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}

if ($_POST['responder_pause']) {
    try {
        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        $responder = $api->get_responder($_POST['responder_pause'], $api->user()['id']);
        if (!$responder) {
            throw new Exception('Ответчик не найден');
        }

        if ($responder['status'] != API::RESPONDER_STATUS_ACTIVE || $responder['status'] != API::RESPONDER_STATUS_NOMONEY) {
            throw new Exception('Невозможно поставить на паузу.');
        }

        $r = $api->update_responder(array(
            'id' => $responder['id'],
            'status' => API::RESPONDER_STATUS_PAUSED,
        ));

        if (!$r) {
            throw new Exception($api->get_error());
        }

        exit(json_encode(array('reload' => 1)));


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}



if ($_POST['responder_archivate']) {
    try {
        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        $responder = $api->get_responder($_POST['responder_archivate'], $api->user()['id']);
        if (!$responder) {
            throw new Exception('Ответчик не найден');
        }

        if ($responder['status'] == API::RESPONDER_STATUS_ARCHIVED) {
            throw new Exception('Ответчик уже заархивирован.');
        }

        $r = $api->update_responder(array(
            'id' => $responder['id'],
            'status' => API::RESPONDER_STATUS_ARCHIVED,
        ));

        if (!$r) {
            throw new Exception($api->get_error());
        }

        exit(json_encode(array('reload' => 1)));


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }

}



if ($_POST['responder_save']) {

    try {

        if (!$api->user()) {
            throw new Exception('Доступ запрещен. Сначала войдите или авторизируйтесь.');
        }

        if (!$_POST['title']) {
            throw new Exception('Введите название ответчика.');
        }

        if ($_POST['schedule'] && !$_POST ['date_start']) {
            throw new Exception('Введите дату старта ответчика.');
        }

        if ($_POST['schedule'] && !$_POST ['date_end']) {
            throw new Exception('Введите дату остановки ответчика.');
        }

        if (!$_POST['scripts']) {
            throw new Exception('Ни один скрипт не настроен.');
        }

        if (!$_POST['phone_id']) {
            throw new Exception('Телефон для приема входящих звонков не указан.');
        }


        $resp = $api->get_responders(null, array(API::RESPONDER_STATUS_PAUSED, API::RESPONDER_STATUS_ACTIVE, API::RESPONDER_STATUS_NOMONEY), $_POST['phone_id']);
        if ($resp) {
            throw new Exception('Голосовые ответчики с таким номером телефона уже существуют. Заархивируйте их либо выберите другой номер.');
        }


        if (!$_POST['scripts'][1]) {
            foreach ($_POST['scripts'] as $k => $v) {
                if ($_POST['robo_voice'] && !$v['message']) {
                    throw new Exception('Введите текст основного блока для озвучки.');
                } elseif (!$_POST['robo_voice'] && !$v['message_mp3']) {
                    throw new Exception('Загрузите аудиодорожку основного сообщения.');
                }
            }
        }


        if ($_POST['date_start'] == 'now') {
            $_POST['date_start'] = date("Y-m-d H:i:s");
        } else {
            $dt = new DateTime($_POST['date_start'], new DateTimeZone($api->user()['timezone']));
            $_POST['date_start'] = $dt->setTimezone(new DateTimeZone('UTC'))->format("Y-m-d H:i:s");
        }

        if ($_POST['date_end'] == 'unlimited') {
            $_POST['date_end'] = date("Y-m-d H:i:s");
        } else {
            $dt = new DateTime($_POST['date_end'], new DateTimeZone($api->user()['timezone']));
            $_POST['date_end'] = $dt->setTimezone(new DateTimeZone('UTC'))->format("Y-m-d H:i:s");
        }


        if ($_POST['schedule']) {

            if ($api->user()['balance'] < $api->get_tariff('min_account_balance', API::TARIFF_TYPE_RESPONDER)) {
                $_POST['status'] = API::RESPONDER_STATUS_NOMONEY;
            } else {
                $_POST['status'] = API::RESPONDER_STATUS_ACTIVE;
            }
        } else {
            $_POST['status'] = API::RESPONDER_STATUS_PAUSED;
        }

        if ($_POST['id']) {
            //UPDATE
            $r = $api->update_responder($_POST);
            if ($r) {
                $scripts = $api->get_responder_scripts($_POST['id']);

                $scripts_result = true;
                foreach ($_POST['scripts'] as $script_id => $script) {
                    $script['script_id'] = $script_id;
                    $script['responder_id'] = $_POST['id'];

                    if ($scripts[$script['script_id']]) {
                        if (!$api->update_responder_script($script)) {
                            $scripts_result = false;
                        }

                    } else {


                        if (!$api->insert_responder_script($script)) {
                            $scripts_result = false;
                        }
                    }
                }
            }


        } else {
            //CREATE
            $_POST['account_id'] = $api->user()['id'];

            $r = $api->insert_responder($_POST);

            if ($r) {
                $scripts_result = true;
                foreach ($_POST['scripts'] as $k => $script) {
                    $script['responder_id'] = $r;
                    $script['script_id'] = $k;

                    if (!$api->insert_responder_script($script)) {
                        $scripts_result = false;
                    }

                }
            }

        }

        if (!$scripts_result || !$r) {
            exit(json_encode(array('message' => $api->get_error())));
        }

        if ($_POST['schedule']) {

            if ($_POST['status'] == API::RESPONDER_STATUS_NOMONEY) {
                exit(json_encode(array(
                    'success' => 1,
                    'message' => 'Ответчик успешно сохранен и поставлен на пауза. Невозможно запустить ответчик - сумма баланса на счете недостаточна! Минимальная сумма для старта '.($api->get_tariff('min_account_balance', API::TARIFF_TYPE_RESPONDER) / 100).' '.API::CURRENCY
                )));
            } else {
                exit(json_encode(array('success' => 1, 'message' => 'Ответчик успешно сохранен и активирован!')));
            }

        } else {
            exit(json_encode(array('success' => 1, 'message' => 'Ответчик успешно сохранен!')));
        }


    } catch (Exception $e) {
        exit(json_encode(array('message' => $e->getMessage())));
    }
}




//@TODO BUY PP NUMBER


//@TODO SEND MONEY TO CLIENT
<?php

class CONFIG {
    /**
     * Время кеширования
     * 60 - 1 минута
     * 3600 - 1 час
     * 86400 - 1 день
     * 604800 - 1 неделя
     */
    const CACHE_LIFETIME = 60;

    
	const DB_HOST = "localhost";
	const DB_NAME = "admin_pozvon";
    const DB_USER = "admin_pozvon";
    const DB_PASS = "";
	
    const MEMCHACHED_HOST = "127.0.0.1";
    const MEMCHACHED_PORT = "11211";
	

	
    const SMTP_HOST = "";            //smtp сервер
    const SMTP_DEBUG = 0;                               //отображение информации дебаггера (0 - нет вообще)
    const SMTP_AUTH = TRUE;                             //сервер требует авторизации
    const SMTP_SECURE = "";                             // использовать "", "ssl" or "tls"
    const SMTP_PORT = 25;                               //порт (по-умолчанию - 25) 587
    const SMTP_USER = "";              //login
    const SMTP_PASS = "";                   //pass

    const MAIL_FROM_EMAIL = "";
    const MAIL_FROM_NAME = "";
    const MAIL_REPLY_TO_EMAIL = "";
    const MAIL_REPLY_TO_NAME = "";

    const TABLE_PREFIX = "tbl_";
    const DOWNLOADS_DIR = "downloads";
    const IMAGES_DOWNLOADS_DIR = "img/downloads";

    //-------------------------------------------------------------------------------

    const SMSC_LOGIN = "";
    const SMSC_PASSWORD = "";

	
   
	const YANDEX_SPEECH_API_KEY = "";
    const ROBOPHONE_TOKEN = "";

    const ACCOUNT_STATUS_ACTIVE = 1;
    const ACCOUNT_STATUS_BANNED = 2;

    const ACCOUNT_TYPE_DEFAULT = 1;
    const ACCOUNT_TYPE_PARTNER = 2;

    const TASK_STATUS_SCHEDULED = 0;
    const TASK_STATUS_ACTIVE = 1;
    const TASK_STATUS_COMPLETED = 2;
    const TASK_STATUS_PAUSED = 3;
    const TASK_STATUS_NOMONEY = 4;

    const RESPONDER_STATUS_ACTIVE = 1;
    const RESPONDER_STATUS_PAUSED = 2;
    const RESPONDER_STATUS_NOMONEY = 3;
    const RESPONDER_STATUS_ARCHIVED = 4;

    const TARIFF_TYPE_TASK = 1;
    const TARIFF_TYPE_RESPONDER = 2;

    const ROBOPHONE_TASK_CALL_STATUS_QUEUED = 0;
    const ROBOPHONE_TASK_CALL_STATUS_SUCCESS = 1;
    const ROBOPHONE_TASK_CALL_STATUS_FAILED = 2;

    const CURRENCY = 'RUB';
    const TEMPLATE = 'pozvonochniy';
    const ACCOUNT_DEFAULT_TIMEZONE = 'Europe/Moscow';

    //-------------------------------------------------------------------------------
    public static $week_days = array(
        1 => "Понедельник",
        2 => "Вторник",
        3 => "Среда",
        4 => "Четверг",
        5 => "Пятница",
        6 => "Суббота",
        7 => "Воскресенье",
    );
	
    public static $task_speaker = array(
        0 => 'jane',
        1 => 'ermil',
    );

    public static $task_emotion = array(
        0 => 'mixed',
        1 => 'good',
        2 => 'neutral',
        3 => 'evil',
    );

    public $transaction_types = array(
        1 => "Пополнение счета",
        2 => "Списание за звонки",
        3 => "Списание за допуслуги",
        4 => "Начисление дохода",
        5 => "Перевод средств со счета",
        6 => "Перевод средств на счет",
    );

   //-------------------------------------------------------------------------------
    public static $pages = array(
        "main" => array(),
        "login" => array(),
        "register" => array(),
        "pasrecover" => array(),
        "payment" => array(),
        "clients" => array(),
        "tasks" => array(),
        "task" => array(
            "parent" => "tasks",
        ),
        "responders" => array(),
        "responder" => array(
            "parent" => "responders",
        ),
        "lists" => array(),
        "list" => array(
            "parent" => "list",
        ),

        "404" => array(),

    );

    public static $elements = array();
}
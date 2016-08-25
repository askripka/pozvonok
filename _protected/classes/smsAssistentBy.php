<?php

/*
 * «SMS-ассистент». Рассылка СМС в Беларуси
 */

class smsAssistentBy {

    private $_login;
    private $_password;
    public $error_codes = array(
        -1 => 'недостаточно средств',
        -2 => 'неправильный логин или пароль (ошибка при аутентификации)',
        -3 => 'отсутствует текст сообщения',
        -4 => 'некорректное значение номера получателя',
        -5 => 'некорректное значение отправителя сообщения',
        -6 => 'отсутствует логин',
        -7 => 'отсутствует пароль',
        -10 => 'сервис временно недоступен',
        -11 => 'некорректное значение ID сообщения',
        -12 => 'другая ошибка',
        -13 => 'заблокировано',
        -14 => 'запрос не укладывается в ограничения по времени на отправку SMS',
    );
    public $statuses = array(
        'Queued' => 'В очереди',
        'Sent' => 'Отправлено',
        'Delivered' => 'Доставлено',
        'Expired' => 'Просрочено',
        'Rejected' => 'Отклонено',
        'Unknown' => 'Неизвестен',
        'Failed' => 'Не отправлено',
    );

    public function __construct($login, $password) {
        $this->_login = $login;
        $this->_password = $password;
    }

    public function send_sms($phone, $message, $sender = 'CMC') {
        $ch = curl_init('https://userarea.sms-assistent.by/api/v1/send_sms/plain');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        //      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // On dev server only!
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'user' => $this->_login,
            'password' => $this->_password,
            'recipient' => $phone,
            'message' => $message,
            'sender' => $sender,
        ));
        return curl_exec($ch);
    }

    public function get_status($id) {
        $ch = curl_init('https://userarea.sms-assistent.by/api/v1/statuses/plain');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        //      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // On dev server only!
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'user' => $this->_login,
            'password' => $this->_password,
            'id' => $id
        ));
        return curl_exec($ch);
    }

    public function get_balance() {
        $ch = curl_init('https://userarea.sms-assistent.by/api/v1/credits/plain');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        //      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // On dev server only!
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array(
            'user' => $this->_login,
            'password' => $this->_password,
        ));

        return curl_exec($ch);
    }


}
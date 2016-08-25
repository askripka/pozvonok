<?php
require_once __DIR__.'/api.php';

/**
 * Class CPAPI
 */
class CPAPI extends API {


    public static function generate_password($length = 6) {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        $size = strlen($chars) - 1;
        $result = '';
        $max = $length;
        while ($max--) {
            $result .= $chars[mt_rand(0, $size)];
        }
        return $result;
    }

    public static function curl_post($url, $data, $send_json = TRUE, $receive_json = TRUE, $access_token = API::ROBOPHONE_TOKEN) {

        echo "curl_post_start<br>";
        $time = microtime(1);

        if (!$data["access_token"] && $access_token) {
            $data["access_token"] = $access_token;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($send_json) {
            $data = json_encode($data);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: '.strlen($data)
                )
            );
        } else {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        echo "curl_post_end:".(microtime(1) - $time)."<br><br>";

        return $receive_json ? json_decode($response) : $response;
    }

    /**
     * @param $str
     * @param $speaker
     * @return bool|string filename
     */
    public function save_yandex_audio($str, $speaker) {

        try {

            if (mb_strlen($str, 'UTF-8') < 250) { //CACHE ON
                $filename = "yandex_".$speaker."_".self::clean_filename($str).".mp3";
                $cache = true;
            } else {
                $filename = "yandex_".$speaker."_record.mp3";
                $cache = false;
            }

            if (!$cache && file_exists($this->downloads_folder.'/'.$filename)) {
                $filename = $this->generate_unique_filename($this->downloads_folder, $filename);
            }

            if (!file_exists($this->downloads_folder.'/'.$filename)) {
                $r = file_put_contents($this->downloads_folder.'/'.$filename, fopen("https://tts.voicetech.yandex.net/generate?text=".urlencode($str)."&robot=false&format=mp3&lang=ru-RU&speaker=".$speaker."&emotion=good&key=".API::YANDEX_SPEECH_API_KEY, 'r'));
                if (!$r) {
                    throw new Exception('Не получилось сохранить файл "'.$filename.'" источник https://tts.voicetech.yandex.net/generate?text='.urlencode($str).'&robot=false&format=mp3&lang=ru-RU&speaker='.$speaker.'&emotion=good&key='.API::YANDEX_SPEECH_API_KEY);
                }

            }

            return $filename;

        } catch (Exception $e) {

            $this->set_error($e->getMessage());
            return FALSE;

        }


    }


    public static function get_timezones($region = null) {
        $result = array();

        $zones = DateTimeZone::listIdentifiers($region ? $region : DateTimeZone::ALL);
        $zones = self::prepare_zones($zones);

        foreach ($zones as $zone) {
            if ($zone['subcity']) {
                $zone['city'] = $zone['city'].'/'.$zone['subcity'];
            }

            if (!$zone['city']) {
                $zone['city'] = $zone['timezone'];
            }

            $result[$zone['continent']][] = $zone;
        }

        return $result;
    }

    private static function prepare_zones(array $timezones) {
        $list = array();
        foreach ($timezones as $zone) {
            $time = new DateTime(NULL, new DateTimeZone($zone));
            $p = $time->format('P');
            if ($p > 13) {
                continue;
            }
            $parts = explode('/', $zone);

            $list[$time->format('P')][] = array(
                'timezone' => $zone,
                'continent' => isset($parts[0]) ? $parts[0] : '',
                'city' => isset($parts[1]) ? $parts[1] : '',
                'subcity' => isset($parts[2]) ? $parts[2] : '',
                'p' => $p,
            );
        }

        ksort($list, SORT_NUMERIC);

        $zones = array();
        foreach ($list as $grouped) {
            $zones = array_merge($zones, $grouped);
        }

        return $zones;
    }

    public function generate_ref_code($length = 6) {

        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $size = strlen($chars) - 1;

        //100000 попыток сгенерить уникальный промокод
        for ($n = 0; $n < 100000; $n++) {
            $max = $length;
            $result = '';

            while ($max--) {
                $result .= $chars[mt_rand(0, $size)];
            }

            if (!$this->get_account(null, null, null, $result)) {
                break;
            }
        }

        return $result;
    }


    public function user() {

        if ($_SESSION['user'] && $_SESSION['user']['update']) {
            $_SESSION['user']['update'] = 0;
            $_SESSION['user'] = $this->get_account($_SESSION['user']['id']);
            $_SESSION['user']['timezone'] = $_SESSION['user']['timezone'] ? $_SESSION['user']['timezone'] : API::ACCOUNT_DEFAULT_TIMEZONE;

        } elseif (!$_SESSION['user']) {
            $_SESSION['user'] = array();
        }

        return $_SESSION['user'];
    }

    public function login($login, $password) {
        $acc = $this->get_account('', $login);

        if (!$acc || !password_verify(mb_strtoupper($password), $acc['password'])) {
            $this->set_error('Неверный номер телефона или пароль');
            return FALSE;
        }

        if ($acc['status'] == self::ACCOUNT_STATUS_BANNED) {
            $this->set_error("Аккаунт с таким номером телефона забанен");
            return FALSE;
        }

        $_SESSION['user'] = $acc;
        return TRUE;

    }

    public function logout() {
        unset($_SESSION['user']);
    }


    //ACCOUNTS
    public function get_accounts($limit1 = 0, $limit2 = 1000, $order_by = "id DESC", $partner_id = null, $timezone = NULL) {
        if ($partner_id) {
            $partner_id = intval($partner_id);
            $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."accounts WHERE `partner_id`={$partner_id} ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."accounts ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($result && $timezone) {
            foreach ($result as &$r) {
                $dt = new DateTime($r['date_registered'], new DateTimeZone('UTC'));
                $r['date_registered'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
            }
        }

        return $result;
    }

    public function get_account($id = null, $phone = null, $email = null, $ref_code = null, $timezone = NULL) {
        if ($id) {
            $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."accounts WHERE `id`='".intval($id)."'")->fetch(PDO::FETCH_ASSOC);
        } else {
            $where = "";
            if ($phone) {
                $where .= " AND `phone`='".preg_replace('/\D/', '', $phone)."'";
            }
            if ($email) {
                $where .= " AND `email`='".self::clean_varchar($email)."'";
            }
            if ($ref_code) {
                $where .= " AND `ref_code`='".self::clean_varchar($ref_code)."'";
            }
            $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."accounts WHERE 1 {$where} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        }

        if ($result && $timezone) {
            $dt = new DateTime($result['date_registered'], new DateTimeZone('UTC'));
            $result['date_registered'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
        }

        return $result;
    }

    public function insert_account($data) {
        $_SESSION['user']['update'] = 1;

        $r = $this->get_account('', $data['phone']);
        if ($r) {
            $this->set_error('Аккаунт с номером телефона '.$data['phone'].' уже зарегестрирован.');
            return FALSE;
        }

        $r = $this->get_account('', '', $data['email']);
        if ($r) {
            $this->set_error('Аккаунт с почтой '.$data['email'].' уже зарегестрирован.');
            return FALSE;
        }

        if (!$data['partner_id'] && $_COOKIE['r']) {
            $partner = $this->get_account(null, null, null, $_COOKIE['r']);
            $data['partner_id'] = $partner['id'] ? $partner['id'] : NULL;
        }


        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."accounts (
            `phone`,
            `email`,
            `first_name`,
            `last_name`,
            `password`,
            `balance`,
            `type`,
            `tariff`,
            `status`,
            `timezone`,
            `partner_id`,
            `ref_code`,
            `total_spent_calls`,
            `total_spent_services`,
            `total_partner_calls_reward`,
            `total_partner_services_reward`,
            `date_registered`
        ) VALUES (
            :phone,
            :email,
            :first_name,
            :last_name,
            :password,
            :balance,
            :type,
            :tariff,
            :status,
            :timezone,
            :partner_id,
            :ref_code,
            :total_spent_calls,
            :total_spent_services,
            :total_partner_calls_reward,
            :total_partner_services_reward,
            :date_registered
            )");
        $result = $stmt->execute(array(
            ':phone' => preg_replace('/\D/', '', $data['phone']),
            ':email' => self::clean_varchar($data['email']),
            ':first_name' => self::clean_varchar(mb_strtoupper(mb_substr($data['first_name'], 0, 1)).mb_strtolower(mb_substr($data['first_name'], 1))),
            ':last_name' => self::clean_varchar(mb_strtoupper(mb_substr($data['last_name'], 0, 1)).mb_strtolower(mb_substr($data['last_name'], 1))),
            ':password' => self::clean_varchar($data['password']),
            ':balance' => intval($data['balance']),
            ':type' => isset($data['type']) ? intval($data['type']) : self::ACCOUNT_TYPE_DEFAULT,
            ':tariff' => isset($data['tariff']) ? intval($data['tariff']) : NULL,
            ':status' => intval($data['status']),
            ':timezone' => preg_replace('/[^a-zA-Z\/]/', '', $data['timezone']),
            ':partner_id' => intval($data['partner_id']),
            ':ref_code' => $this->generate_ref_code(),
            ':total_spent_calls' => intval($data['total_spent_calls']),
            ':total_spent_services' => intval($data['total_spent_services']),
            ':total_partner_calls_reward' => intval($data['total_partner_calls_reward']),
            ':total_partner_services_reward' => intval($data['total_partner_services_reward']),
            ':date_registered' => date("Y-m-d H:i:s"),
        ));

        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            if ($e[0] == 23000) {
                $this->set_error('Аккаунт с этим номером телефона уже зарегестрирован.');
            } else {
                $this->set_error($e[2]);
            }
        }
        return $result;
    }

    public function update_account($data) {
        $_SESSION['user']['update'] = 1;

        $old_data = $this->get_account($data['id']);
        if (!$old_data) {
            $this->set_error('Такого аккаунта не существует');
            return FALSE;
        }
        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."accounts SET
            `phone`=:phone,
            `email`=:email,
            `first_name`=:first_name,
            `last_name`=:last_name,
            `password`=:password,
            `balance`=:balance,
            `type`=:type,
            `tariff`=:tariff,
            `status`=:status,
            `timezone`=:timezone,
            `partner_id`=:partner_id,
            `ref_code`=:ref_code,
            `total_spent_calls`=:total_spent_calls,
            `total_spent_services`=:total_spent_services,
            `total_partner_calls_reward`=:total_partner_calls_reward,
            `total_partner_services_reward`=:total_partner_services_reward,
            `date_registered`=:date_registered
            WHERE
                `id`=:id
            ");
        $result = $stmt->execute(array(
            ':id' => intval($data['id']),
            ':phone' => $old_data['phone'],
            ':email' => $data['email'] ? self::clean_varchar($data['email']) : $old_data['email'],
            ':first_name' => $data['first_name'] ? self::clean_varchar(mb_strtoupper(mb_substr($data['first_name'], 0, 1)).mb_strtolower(mb_substr($data['first_name'], 1))) : $old_data['first_name'],
            ':last_name' => $data['last_name'] ? self::clean_varchar(mb_strtoupper(mb_substr($data['last_name'], 0, 1)).mb_strtolower(mb_substr($data['last_name'], 1))) : $old_data['last_name'],
            ':password' => $data['password'] ? self::clean_varchar($data['password']) : $old_data['password'],
            ':balance' => isset($data['balance']) ? intval($data['balance']) : $old_data['balance'],
            ':type' => isset($data['type']) ? intval($data['type']) : $old_data['type'],
            ':tariff' => isset($data['tariff']) ? intval($data['tariff']) : $old_data['tariff'],
            ':status' => isset($data['status']) ? intval($data['status']) : $old_data['status'],
            ':timezone' => $data['timezone'] ? preg_replace('/[^a-zA-Z\/]/', '', $data['timezone']) : $old_data['timezone'],
            ':partner_id' => $data['partner_id'] ? intval($data['partner_id']) : $old_data['partner_id'],
            ':ref_code' => $data['ref_code'] ? self::clean_varchar($data['ref_code']) : $old_data['ref_code'],
            ':total_spent_calls' => isset($data['total_spent_calls']) ? intval($data['total_spent_calls']) : $old_data['total_spent_calls'],
            ':total_spent_services' => isset($data['total_spent_services']) ? intval($data['total_spent_services']) : $old_data['total_spent_services'],
            ':total_partner_calls_reward' => isset($data['total_partner_calls_reward']) ? intval($data['total_partner_calls_reward']) : $old_data['total_partner_calls_reward'],
            ':total_partner_services_reward' => isset($data['total_partner_services_reward']) ? intval($data['total_partner_services_reward']) : $old_data['total_partner_services_reward'],
            ':date_registered' => $data['date_registered'] ? date("Y-m-d H:i:s", strtotime($data['date_registered'])) : $old_data['date_registered'],
        ));
        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }


    public function delete_account($id) {
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."accounts WHERE id=:id")->execute(array(':id' => $id));
    }

    /**
     * Обновить баланс аккаунта в валюте сервиса
     * @param $account_id
     * @param $value плюсовое или минусовое
     * @return bool
     *
     */
    public function update_account_balance($account_id, $value) {
        $_SESSION['user']['update'] = 1;
        return $this->db->prepare("UPDATE ".self::TABLE_PREFIX."accounts SET `balance`=`balance`+:value WHERE id=:id")->execute(array(
            ':id' => intval($account_id), ':value' => intval($value),
        ));
    }


    public function get_tariff($tariff_param = '', $tariff_type = '') {
        if ($this->mc && $this->mc->get('tariff'.$tariff_type)) {
            return $tariff_param ? $this->mc->get('tariff'.$tariff_type)[$tariff_param]['value'] : $this->mc->get('tariff'.$tariff_type);
        }

        $where = "1";
        if ($tariff_type) {
            $where .= " AND `tariff_type`='".intval($tariff_type)."'";
        }
        $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."tariffs WHERE {$where}")->fetchAll(PDO::FETCH_ASSOC);

        $tariffs = array();
        foreach ($result as $r) {
            $tariffs[$r['name']] = $r;
        }

        if ($this->mc) {
            $this->mc->set('tariff'.$tariff_type, $tariffs, strtotime('+1 day 00:00:00'));
        }

        return $tariff_param ? $tariffs[$tariff_param]['value'] : $tariffs;
    }


    public function get_available_phone_numbers() {
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."available_phone_numbers")->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function get_personal_phone_numbers($account_id = null) {
        $where = "1";
        if ($account_id) {
            $where .= " AND `account_id`='".intval($account_id)."'";
        }

        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."personal_phone_numbers WHERE {$where}")->fetchAll(PDO::FETCH_ASSOC);
    }


    //TASKS
    public function count_tasks($account_id = null, $status = null) {
        $where = "";
        if (isset($account_id)) {
            $where .= " AND `account_id`='".intval($account_id)."'";
        }
        if (isset($status)) {
            $where .= " AND `status`='".intval($status)."'";
        }
        $r = $this->db->query("SELECT COUNT(*) as count FROM ".self::TABLE_PREFIX."tasks WHERE 1 {$where}")->fetch(PDO::FETCH_ASSOC);
        return $r['count'];
    }


    public function get_tasks($account_id = null, $status = null, $list_id = null, $limit1 = 0, $limit2 = 1000, $order_by = "t.id DESC", $timezone = NULL) {
        $where = "";
        if (isset($account_id)) {
            $where .= " AND t.account_id='".intval($account_id)."'";
        }
        if (isset($status)) {
            if (is_array($status)) {
                $where .= " AND t.status IN (".implode(',', $status).")";
            } else {
                $where .= " AND t.status='".intval($status)."'";
            }
        }
        if (isset($list_id)) {
            $list_id = intval($list_id);
            $where .= " AND t.list_id REGEXP '^{$list_id}$|^{$list_id},|,{$list_id}$|,{$list_id},';";
        }
        $result = $this->db->query("SELECT t.*, p.phone_number FROM ".self::TABLE_PREFIX."tasks t LEFT JOIN ".self::TABLE_PREFIX."personal_phone_numbers p ON (t.phone_id=p.id) WHERE 1 {$where} ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
        if (is_array($result)) {
            foreach ($result as &$r) {
                if ($r) {
                    $r['list_id'] = explode(',', $r['list_id']);
                    $r['call_duration_statistics'] = $r['call_duration_statistics'] ? unserialize($r['call_duration_statistics']) : '';

                    if ($r['date_start'] && $timezone) {
                        $dt = new DateTime($r['date_start'], new DateTimeZone('UTC'));
                        $r['date_start'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
                    }

                    if ($r['date_end'] && $timezone) {
                        $dt = new DateTime($r['date_end'], new DateTimeZone('UTC'));
                        $r['date_end'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
                    }

                }
            }
        }
        return $result;
    }

    public function get_task($id, $account_id = null, $timezone = null) {
        $where = "t.id='".intval($id)."'";
        if ($account_id) {
            $where .= " AND t.account_id='".intval($account_id)."'";
        }
        $result = $this->db->query("SELECT t.*, p.phone_number FROM ".self::TABLE_PREFIX."tasks t LEFT JOIN ".self::TABLE_PREFIX."personal_phone_numbers p ON (t.phone_id=p.id) WHERE {$where}")->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['list_id'] = explode(',', $result['list_id']);
            $result['call_duration_statistics'] = $result['call_duration_statistics'] ? unserialize($result['call_duration_statistics']) : '';

            if ($result['date_start'] && $timezone) {
                $dt = new DateTime($result['date_start'], new DateTimeZone('UTC'));
                $result['date_start'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
            }

            if ($result['date_end'] && $timezone) {
                $dt = new DateTime($result['date_end'], new DateTimeZone('UTC'));
                $result['date_end'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
            }
        }
        return $result;
    }

    public function insert_task($data) {

        if (is_array($data['list_id'])) {
            foreach ($data['list_id'] as &$l) {
                $l = intval($l);
            }
            $data['list_id'] = implode(',', $data['list_id']);
        } else {
            $data['list_id'] = $data['list_id'] ? intval($data['list_id']) : '';
        }

        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."tasks (
            `account_id`,
            `list_id`,
            `phone_id`,
            `title`,
            `robo_voice`,
            `speaker`,
            `voice_action`,
            `date_start`,
            `date_end`,
            `status`,
            `test`,
            `total_list_amount`,
            `total_calls`,
            `total_success_calls`,
            `average_call_duration`,
            `call_duration_statistics`
        ) VALUES (
            :account_id,
            :list_id,
            :phone_id,
            :title,
            :robo_voice,
            :speaker,
            :voice_action,
            :date_start,
            :date_end,
            :status,
            :test,
            :total_list_amount,
            :total_calls,
            :total_success_calls,
            :average_call_duration,
            :call_duration_statistics
            )");
        $result = $stmt->execute(array(
            ':account_id' => $data['account_id'] ? intval($data['account_id']) : NULL,
            ':list_id' => $data['list_id'],
            ':phone_id' => intval($data['phone_id']) ? intval($data['phone_id']) : NULL,
            ':title' => self::clean_varchar($data['title']),
            ':robo_voice' => intval($data['robo_voice']),
            ':speaker' => self::clean_filename($data['speaker']),
            ':voice_action' => intval($data['voice_action']),
            ':date_start' => $data['date_start'] ? date("Y-m-d H:i:s", strtotime($data['date_start'])) : NULL,
            ':date_end' => $data['date_end'] ? date("Y-m-d H:i:s", strtotime($data['date_end'])) : NULL,
            ':status' => intval($data['status']),
            ':test' => intval($data['test']),
            ':total_list_amount' => intval($data['total_list_amount']),
            ':total_calls' => intval($data['total_calls']),
            ':total_success_calls' => intval($data['total_success_calls']),
            ':average_call_duration' => intval($data['average_call_duration']),
            ':call_duration_statistics' => $data['call_duration_statistics'] ? serialize($data['call_duration_statistics']) : '',
        ));
        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function update_task($data) {
        $old_data = $this->get_task($data['id'], $this->user()['id']);
        if (!$old_data) {
            $this->set_error('Такой рассылки не существует');
            return FALSE;
        }

        if (is_array($data['list_id'])) {
            foreach ($data['list_id'] as &$l) {
                $l = intval($l);
            }
            $data['list_id'] = implode(',', $data['list_id']);
        } else {
            $data['list_id'] = $data['list_id'] ? intval($data['list_id']) : '';
        }


        if (is_array($old_data['list_id'])) {
            foreach ($old_data['list_id'] as &$l) {
                $l = intval($l);
            }
            $old_data['list_id'] = implode(',', $old_data['list_id']);
        } else {
            $old_data['list_id'] = $old_data['list_id'] ? intval($old_data['list_id']) : '';
        }

        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."tasks SET
            `account_id`=:account_id,
            `list_id`=:list_id,
            `phone_id`=:phone_id,
            `title`=:title,
            `robo_voice`=:robo_voice,
            `speaker`=:speaker,
            `voice_action`=:voice_action,
            `date_start`=:date_start,
            `date_end`=:date_end,
            `status`=:status,
            `test`=:test,
            `total_list_amount`=:total_list_amount,
            `total_calls`=:total_calls,
            `total_success_calls`=:total_success_calls,
            `average_call_duration`=:average_call_duration,
            `call_duration_statistics`=:call_duration_statistics
            WHERE
                `id`=:id
            ");
        $result = $stmt->execute(array(
            ':id' => intval($data['id']),
            ':account_id' => $data['account_id'] ? intval($data['account_id']) : $old_data['account_id'],
            ':list_id' => $data['list_id'] ? $data['list_id'] : $old_data['list_id'],
            ':phone_id' => intval($data['phone_id']) ? intval($data['phone_id']) : $old_data['phone_id'],
            ':title' => $data['title'] ? self::clean_varchar($data['title']) : $old_data['title'],
            ':robo_voice' => isset($data['robo_voice']) ? intval($data['robo_voice']) : $old_data['robo_voice'],
            ':speaker' => isset($data['speaker']) ? self::clean_filename($data['speaker']) : $old_data['speaker'],
            ':voice_action' => isset($data['voice_action']) ? intval($data['voice_action']) : $old_data['voice_action'],
            ':date_start' => isset($data['date_start']) ? date("Y-m-d H:i:s", strtotime($data['date_start'])) : $old_data['date_start'],
            ':date_end' => isset($data['date_end']) ? date("Y-m-d H:i:s", strtotime($data['date_end'])) : $old_data['date_end'],
            ':status' => isset($data['status']) ? intval($data['status']) : $old_data['status'],
            ':test' => isset($data['test']) ? intval($data['test']) : $old_data['test'],
            ':total_list_amount' => isset($data['total_list_amount']) ? intval($data['total_list_amount']) : $old_data['total_list_amount'],
            ':total_calls' => isset($data['total_calls']) ? intval($data['total_calls']) : $old_data['total_calls'],
            ':total_success_calls' => isset($data['total_success_calls']) ? intval($data['total_success_calls']) : $old_data['total_success_calls'],
            ':average_call_duration' => isset($data['average_call_duration']) ? intval($data['average_call_duration']) : $old_data['average_call_duration'],
            ':call_duration_statistics' => isset($data['call_duration_statistics']) ? serialize($data['call_duration_statistics']) : serialize($old_data['call_duration_statistics']),
        ));
        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function delete_task($id, $account_id = null) {
        if ($task = $this->get_task($id, $account_id)) {
            $this->delete_task_scripts($task['id']);
            return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."tasks WHERE id=:id")->execute(array(':id' => $id));
        } else {
            $this->set_error("Рассылка не найдена");
            return FALSE;
        }
    }



    //TASK SCRIPTS
    /**
     * @param $actions
     * @return array
     */
    public function validate_task_script_actions($actions) {
        $result = '';

        if ($actions) {
            $result = array();
            foreach ($actions as $key => $data) {
                $key = is_numeric($key) ? intval($key) : preg_replace('/[^a-zа-яёії]/miu', '', $key);
                if (!$key) {
                    continue;
                }

                if ($data['action']['sms']) {
                    $result[$key]['action']['sms'] = self::clean_varchar($data['action']['sms']);
                } elseif ($data['action']['connect']) {
                    $result[$key]['action']['connect'] = '+'.(preg_replace('/\D/', '', ($data['action']['connect'])));
                } elseif ($data['action']['replay']) {
                    $result[$key]['action']['replay'] = intval($data['action']['replay']);
                } elseif ($data['action']['record']) {
                    $result[$key]['action']['record'] = intval($data['action']['record']);
                } elseif ($data['action']['vote']) {
                    $result[$key]['action']['vote'] = intval($data['action']['vote']);
                }
            }

            foreach ($actions as $key => $data) {
                $key = is_numeric($key) ? intval($key) : preg_replace('/[^a-zа-яёії]/miu', '', $key);
                if (!$key) {
                    continue;
                }

                if ($data['after_action']['goto']) {
                    $result[$key]['after_action']['goto'] = intval($data['after_action']['goto']);
                } elseif ($data['after_action']['replay']) {
                    $result[$key]['after_action']['replay'] = intval($data['after_action']['replay']);
                } elseif ($data['after_action']['end']) {
                    $result[$key]['after_action']['end'] = intval($data['after_action']['end']);
                }
            }
        }


        return $result;
    }

    public function insert_task_script($data) {

        $data['button_actions'] = $this->validate_task_script_actions($data['button_actions']);
        $data['voice_actions'] = $this->validate_task_script_actions($data['voice_actions']);

        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."task_scripts (
                `task_id`, `script_id`, `greeting`, `message`, `goodbye`, `greeting_mp3`, `message_mp3`, `goodbye_mp3`, `button_actions`, `voice_actions`
            ) VALUES (
                :task_id, :script_id, :greeting, :message, :goodbye, :greeting_mp3, :message_mp3, :goodbye_mp3, :button_actions, :voice_actions
        )");

        $result = $stmt->execute(array(
            ':task_id' => intval($data['task_id']),
            ':script_id' => intval($data['script_id']),
            ':greeting' => self::clean_varchar($data['greeting']),
            ':message' => self::clean_varchar($data['message']),
            ':goodbye' => self::clean_varchar($data['goodbye']),
            ':greeting_mp3' => self::clean_filename($data['greeting_mp3']),
            ':message_mp3' => self::clean_filename($data['message_mp3']),
            ':goodbye_mp3' => self::clean_filename($data['goodbye_mp3']),
            ':button_actions' => serialize($data['button_actions']),
            ':voice_actions' => serialize($data['voice_actions']),
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }

        return $result;
    }

    public function update_task_script($data) {

        $data['button_actions'] = $this->validate_task_script_actions($data['button_actions']);
        $data['voice_actions'] = $this->validate_task_script_actions($data['voice_actions']);

        $old_data = $this->get_task_script($data['task_id'], $data['script_id']);
        if (!$old_data) {
            $this->set_error("Скрипт рассылки не найден");
            return FALSE;
        }

        if (self::clean_filename($data['greeting_mp3']) != self::clean_filename($old_data['greeting_mp3'])) {
            @unlink($this->downloads_folder.'/'.$old_data['greeting_mp3']);
        }
        if (self::clean_filename($data['message_mp3']) != self::clean_filename($old_data['message_mp3'])) {
            @unlink($this->downloads_folder.'/'.$old_data['message_mp3']);
        }
        if (self::clean_filename($data['goodbye_mp3']) != self::clean_filename($old_data['goodbye_mp3'])) {
            @unlink($this->downloads_folder.'/'.$old_data['goodbye_mp3']);
        }


        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."task_scripts
            SET `greeting`=:greeting, `message`=:message, `goodbye`=:goodbye, `greeting_mp3`=:greeting_mp3, `message_mp3`=:message_mp3, `goodbye_mp3`=:goodbye_mp3, `button_actions`=:button_actions,`voice_actions`=:voice_actions
            WHERE `task_id`=:task_id AND `script_id`=:script_id
        ");
        $result = $stmt->execute(array(
            ':task_id' => intval($data['task_id']),
            ':script_id' => intval($data['script_id']),
            ':greeting' => self::clean_varchar($data['greeting']),
            ':message' => self::clean_varchar($data['message']),
            ':goodbye' => self::clean_varchar($data['goodbye']),
            ':greeting_mp3' => self::clean_filename($data['greeting_mp3']),
            ':message_mp3' => self::clean_filename($data['message_mp3']),
            ':goodbye_mp3' => self::clean_filename($data['goodbye_mp3']),
            ':button_actions' => $data['button_actions'] ? serialize($data['button_actions']) : '',
            ':voice_actions' => $data['voice_actions'] ? serialize($data['voice_actions']) : '',
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }


    public function delete_task_script($task_id, $script_id) {
        $script = $this->get_task_script($task_id, $script_id);
        if (!$script) {
            $this->set_error("Скрипт рассылки не найден");
            return FALSE;
        }

        @unlink($this->downloads_folder.'/'.$script['greeting_mp3']);
        @unlink($this->downloads_folder.'/'.$script['message_mp3']);
        @unlink($this->downloads_folder.'/'.$script['goodbye_mp3']);
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."task_scripts WHERE task_id=:task_id AND script_id=:script_id ")->execute(array(
            ':task_id' => $task_id,
            ':script_id' => $script_id,
        ));

    }

    public function delete_task_scripts($task_id) {
        $scripts = $this->get_task_scripts($task_id);
        if (is_array($scripts)) {
            foreach ($scripts as $script) {
                @unlink($this->downloads_folder.'/'.$script['greeting_mp3']);
                @unlink($this->downloads_folder.'/'.$script['message_mp3']);
                @unlink($this->downloads_folder.'/'.$script['goodbye_mp3']);
            }
        }
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."task_scripts WHERE task_id=:task_id")->execute(array(
            ':task_id' => $task_id,
        ));
    }


    public function get_task_script($task_id, $script_id) {
        $r = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."task_scripts WHERE task_id=".intval($task_id)." AND script_id=".intval($script_id))->fetch(PDO::FETCH_ASSOC);
        if ($r['button_actions']) {
            $r['button_actions'] = unserialize($r['button_actions']);
        }
        if ($r['voice_actions']) {
            $r['voice_actions'] = unserialize($r['voice_actions']);
        }
        return $r;
    }

    public function get_task_scripts($task_id) {
        $results = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."task_scripts WHERE task_id='".intval($task_id)."' ORDER BY script_id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $results2 = array();

        if (is_array($results)) {

            foreach ($results as $r) {
                $results2[$r['script_id']] = $r;

                if ($r['button_actions']) {
                    $results2[$r['script_id']]['button_actions'] = unserialize($r['button_actions']);
                }
                if ($r['voice_actions']) {
                    $results2[$r['script_id']]['voice_actions'] = unserialize($r['voice_actions']);
                }
            }
        }
        return $results2 ? $results2 : $results;
    }


    //TASK LISTS
    public function count_lists($account_id = null) {
        if ($account_id) {
            $r = $this->db->query("SELECT COUNT(*) as count FROM ".self::TABLE_PREFIX."lists WHERE `account_id`='".intval($account_id)."'")->fetch(PDO::FETCH_ASSOC);
        } else {
            $r = $this->db->query("SELECT COUNT(*) as count FROM ".self::TABLE_PREFIX."lists")->fetch(PDO::FETCH_ASSOC);
        }
        return $r['count'];
    }

    public function get_lists($account_id = null, $limit1 = 0, $limit2 = 1000, $order_by = "id DESC") {
        if ($account_id) {
            return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."lists WHERE `account_id`='".intval($account_id)."' ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."lists ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    public function get_list($id, $account_id = null) {
        $where = "`id`=".intval($id);
        if ($account_id) {
            $where .= " AND `account_id`=".intval($account_id);
        }
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."lists WHERE {$where}")->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * `account_id`, `title`, `total`
     * @param $data
     * @return bool|string
     */
    public function insert_list($data) {
        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."lists (
            `account_id`,
            `title`,
            `total`
        ) VALUES (
            :account_id,
            :title,
            :total
            )");
        $result = $stmt->execute(array(
            ':account_id' => $data['account_id'] ? intval($data['account_id']) : NULL,
            ':title' => self::clean_varchar($data['title']),
            ':total' => intval($data['total']),
        ));
        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function update_list($data) {
        $old_data = $this->get_list($data['id']);
        if (!$old_data) {
            $this->set_error('Такого списка не существует');
            return FALSE;
        }
        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."lists SET
            `account_id`=:account_id,
            `title`=:title,
            `total`=:total
            WHERE
                `id`=:id
            ");
        $result = $stmt->execute(array(
            ':id' => intval($data['id']),
            ':account_id' => $data['account_id'] ? intval($data['account_id']) : $old_data['account_id'],
            ':title' => isset($data['title']) ? self::clean_varchar($data['title']) : $old_data['title'],
            ':total' => isset($data['total']) ? intval($data['total']) : $old_data['title'],
        ));
        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function delete_list($id) {
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."lists WHERE id=:id")->execute(array(':id' => $id));
    }


    //TASK LIST USERS
    /**
     * @param $list_id Array OR Integer
     * @return mixed
     */
    public function count_list_users($list_id) {
        if (is_array($list_id)) {
            $r = $this->db->query("SELECT COUNT(*) as count FROM ".self::TABLE_PREFIX."list_users WHERE `list_id` IN (".implode(',', $list_id).")")->fetch(PDO::FETCH_ASSOC);
        } else {
            $r = $this->db->query("SELECT COUNT(*) as count FROM ".self::TABLE_PREFIX."list_users WHERE `list_id`='".intval($list_id)."'")->fetch(PDO::FETCH_ASSOC);
        }


        return $r['count'];
    }

    public function get_list_users($list_id, $limit1 = 0, $limit2 = 1000, $order_by = "id DESC") {

        try {


            if (is_array($list_id)) {
                $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."list_users WHERE `list_id` IN (".implode(',', $list_id).") ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."list_users WHERE `list_id`='".intval($list_id)."' ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
            }

            if ($result) {
                foreach ($result as &$r) {
                    if ($r['extra_fields']) {
                        $r['extra_fields'] = unserialize($r['extra_fields']);
                    }
                }
            }

        } catch (Exception $e) {
            var_dump($e->getMessage());
        }

        return $result;
    }

    public function get_list_user($id) {
        $result = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."list_users WHERE `id`='".intval($id)."'")->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['extra_fields']) {
            $result['extra_fields'] = unserialize($result['extra_fields']);
        }
        return $result;
    }

    /**
     * `list_id`, `phone`, `first_name`, `extra_fields`
     * @param $data
     * @return bool|string
     */
    public function insert_list_user($data) {
        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."list_users (
            `list_id`,
            `phone`,
            `first_name`,
            `extra_fields`
        ) VALUES (
            :list_id,
            :phone,
            :first_name,
            :extra_fields
            )");
        $result = $stmt->execute(array(
            ':list_id' => $data['list_id'] ? intval($data['list_id']) : NULL,
            ':phone' => preg_replace('/\D/', '', $data['phone']),
            ':first_name' => self::clean_varchar($data['first_name']),
            ':extra_fields' => isset($data['extra_fields']) ? serialize($data['extra_fields']) : '',
        ));
        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }


    /**
     * Вставляет телефоны списками.
     * Максимум 100 000, иначе возможна ошибка переполенения памяти.
     * @param $data Двумерный массив
     * @return int Количество вставленных записей
     */
    public function insert_list_users($data) {

        $rows_inserted = 0;
        foreach (array_chunk($data, 1000) as $d) {

            $rows_sum = 0;
            $sql = "INSERT INTO ".self::TABLE_PREFIX."list_users (
            `list_id`,
            `phone`,
            `first_name`,
            `extra_fields`
        ) VALUES ";
            $sql_fields = array();
            $sql_values = array();

            foreach ($d as $n => $row) {
                $rows_sum++;
                $sql_fields[] = "(:list_id{$n}, :phone{$n}, :first_name{$n}, :extra_fields{$n})";
                $sql_values['list_id'.$n] = $row['list_id'] ? intval($row['list_id']) : NULL;
                $sql_values['phone'.$n] = preg_replace('/\D/', '', $row['phone']);
                $sql_values['first_name'.$n] = self::clean_varchar($row['first_name']);
                $sql_values['extra_fields'.$n] = isset($row['extra_fields']) ? serialize($row['extra_fields']) : '';
            }

            if ($sql_fields) {
                $sql .= implode(', ', $sql_fields);
                $stmt = $this->db->prepare($sql);
                $result = $stmt->execute($sql_values);
                if ($result) {
                    $rows_inserted += $rows_sum;
                } else {
                    $e = $stmt->errorInfo();
                    $this->set_error($e[2]);
                }
            }

        }

        return $rows_inserted;
    }

    public function update_list_user($data) {
        $old_data = $this->get_list_user($data['id']);
        if (!$old_data) {
            $this->set_error('Такого пользователя в списках не существует');
            return FALSE;
        }
        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."list_users SET
            `list_id`=:list_id,
            `phone`=:phone,
            `first_name`=:first_name,
            `extra_fields`=:extra_fields
            WHERE
                `id`=:id
            ");
        $result = $stmt->execute(array(
            ':id' => intval($data['id']),
            ':list_id' => $data['list_id'] ? intval($data['list_id']) : $old_data['list_id'],
            ':phone' => $data['phone'] ? preg_replace('/\D/', '', $data['phone']) : $old_data['phone'],
            ':first_name' => isset($data['first_name']) ? self::clean_varchar($data['first_name']) : $old_data['first_name'],
            ':extra_fields' => isset($data['extra_fields']) ? serialize($data['extra_fields']) : serialize($old_data['extra_fields']),
        ));
        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;

    }

    public function delete_list_user($id) {
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."list_users WHERE id=:id")->execute(array(':id' => $id));
    }


    //CRUD логов рассылки
    public function count_task_logs($task_id = null, $robophone_task_id = null) {
        $where = '';
        if ($task_id) {
            $where .= " AND `task_id`=".intval($task_id);
        }
        if ($robophone_task_id) {
            $where .= " AND `robophone_task_id`=".intval($robophone_task_id);
        }
        $r = $this->db->query("SELECT COUNT(*) as count FROM  ".self::TABLE_PREFIX."task_log WHERE 1 {$where}")->fetch(PDO::FETCH_ASSOC);
        return $r['count'];
    }

    public function get_task_logs($task_id = null, $robophone_task_id = null, $status = null, $limit1 = 0, $limit2 = 1000, $order_by = "id DESC") {
        $where = "";
        if ($task_id) {
            $where .= " AND `task_id`=".intval($task_id);
        }
        if ($robophone_task_id) {
            $where .= " AND `robophone_task_id`=".intval($robophone_task_id);
        }
        if (isset($status)) {
            $where .= " AND `status`=".intval($status);
        }
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."task_log WHERE 1 {$where} ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_task_logs_with_phones($task_id = null, $robophone_task_id = null, $status = null, $limit1 = 0, $limit2 = 1000, $order_by = "tl.id DESC") {
        $where = "";
        if ($task_id) {
            $where .= " AND tl.task_id=".intval($task_id);
        }
        if ($robophone_task_id) {
            $where .= " AND tl.robophone_task_id=".intval($robophone_task_id);
        }
        if (isset($status)) {
            $where .= " AND tl.status=".intval($status);
        }
        return $this->db->query("SELECT tl.*, lu.phone FROM ".self::TABLE_PREFIX."task_log tl LEFT JOIN ".self::TABLE_PREFIX."list_users lu ON tl.list_user_id = lu.id WHERE 1 {$where} ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_task_log($id) {
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."task_log WHERE `id`='".intval($id)."'")->fetch(PDO::FETCH_ASSOC);
    }

    public function insert_task_log($data) {
        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."task_log (
            `task_id`,
            `robophone_task_id`,
            `list_user_id`,
            `status`,
            `call_datetime`,
            `call_duration`,
            `script_answers`
        ) VALUES (
            :task_id,
            :robophone_task_id,
            :list_user_id,
            :status,
            :call_datetime,
            :call_duration,
            :script_answers
        )");
        $result = $stmt->execute(array(
            ':task_id' => $data['task_id'] ? intval($data['task_id']) : NULL,
            ':robophone_task_id' => $data['robophone_task_id'] ? intval($data['robophone_task_id']) : NULL,
            ':list_user_id' => $data['list_user_id'] ? intval($data['list_user_id']) : NULL,
            ':status' => intval($data['status']),
            ':call_datetime' => isset($data['call_datetime']) ? date("Y-m-d H:i:s", strtotime($data['call_datetime'])) : NULL,
            ':call_duration' => isset($data['call_duration']) ? intval($data['call_duration']) : NULL,
            ':script_answers' => is_array($data['script_answers']) && $data['script_answers'] ? serialize($data['script_answers']) : NULL,
        ));
        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;

    }

    public function update_task_log($data) {
        $old_data = $this->get_task_log($data['id']);
        if (!$old_data) {
            $this->set_error('Такого лога не существует');
            return FALSE;
        }
        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."task_log SET
            `task_id`=:task_id,
            `robophone_task_id`=:robophone_task_id,
            `list_user_id`=:list_user_id,
            `status`=:status,
            `call_datetime`=:call_datetime,
            `call_duration`=:call_duration,
            `script_answers`=:script_answers
            WHERE
                `id`=:id
            ");

        $result = $stmt->execute(array(
            ':id' => intval($data['id']),
            ':task_id' => $data['task_id'] ? intval($data['task_id']) : $old_data['task_id'],
            ':robophone_task_id' => $data['robophone_task_id'] ? intval($data['robophone_task_id']) : $old_data['robophone_task_id'],
            ':list_user_id' => $data['list_user_id'] ? intval($data['list_user_id']) : $old_data['list_user_id'],
            ':status' => isset($data['status']) ? intval($data['status']) : $old_data['status'],
            ':call_datetime' => isset($data['call_datetime']) ? date("Y-m-d H:i:s", strtotime($data['call_datetime'])) : $old_data['call_datetime'],
            ':call_duration' => isset($data['call_duration']) ? intval($data['call_duration']) : $old_data['call_duration'],
            ':script_answers' => is_array($data['script_answers']) && $data['script_answers'] ? serialize($data['script_answers']) : ($old_data['script_answers'] ? serialize($old_data['script_answers']) : NULL),
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function delete_task_log($id) {
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."task_log WHERE id=:id")->execute(array(':id' => $id));
    }

    /**
     * Получить все айдишники роботасков для выбранной рассылки
     * @param $task_id
     * @return array
     */
    public function get_robophone_task_ids($task_id) {
        $r = $this->db->query("SELECT `robophone_task_id` FROM ".self::TABLE_PREFIX."task_log WHERE `task_id`=".intval($task_id)." GROUP BY `robophone_task_id`")->fetchAll(PDO::FETCH_COLUMN);
        return $r;
    }


    /**
     * GET TRANSACTIONS
     * @param null $account_id
     * @param null $type
     * @param null $date_from
     * @param null $date_to
     * @param int $limit1
     * @param int $limit2
     * @return array
     */
    public function get_transactions($account_id = NULL, $type = NULL, $date_from = NULL, $date_to = NULL, $limit1 = 0, $limit2 = 1000) {
        $where = "1";
        if (isset($account_id)) {
            $account_id = intval($account_id);
            $where .= " AND `account_id`={$account_id}";
        }
        if (isset($type)) {
            $type = intval($type);
            $where .= " AND `type`={$type}";
        }
        if (isset($date_from) && isset($date_to)) {
            $date_from = date("Y-m-d H:i:s", strtotime($date_from));
            $date_to = date("Y-m-d H:i:s", strtotime($date_to));
            $where .= " AND (`datetime` BETWEEN '{$date_from}' AND '{$date_to}')";
        }
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."transactions WHERE {$where} ORDER BY `id` DESC LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_transactions_sum($account_id = NULL, $type = NULL, $date_from = NULL, $date_to = NULL) {
        $where = "1";
        if (isset($account_id)) {
            $account_id = intval($account_id);
            $where .= " AND `account_id`={$account_id}";
        }
        if (isset($type)) {
            $type = intval($type);
            $where .= " AND `type`={$type}";
        }
        if (isset($date_from) && isset($date_to)) {
            $date_from = date("Y-m-d H:i:s", strtotime($date_from));
            $date_to = date("Y-m-d H:i:s", strtotime($date_to));
            $where .= " AND (`datetime` BETWEEN '{$date_from}' AND '{$date_to}')";
        }
        $r = $this->db->query("SELECT SUM(`value`) as `sum` FROM ".self::TABLE_PREFIX."transactions WHERE {$where}")->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            return $r['sum'];
        } else {
            return $r;
        }

    }

    public function get_transaction($id) {
        $id = intval($id);
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."transactions WHERE `id`={$id}")->fetch(PDO::FETCH_ASSOC);
    }


    public function insert_transaction($data) {
        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."transactions
        (`account_id`, `type`, `value`, `datetime`, `comment`) VALUES (:account_id, :type, :value, :datetime, :comment)");
        $result = $stmt->execute(array(
            ':account_id' => intval($data['account_id']),
            ':type' => intval($data['type']),
            ':value' => intval($data['value']),
            ':datetime' => date("Y-m-d H:i:s"),
            ':comment' => self::clean_varchar($data['comment']),
        ));

        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }


    public function count_responders($account_id = null, $status = null) {
        $where = "";
        if (isset($account_id)) {
            $where .= " AND `account_id`='".intval($account_id)."'";
        }
        if (isset($status)) {
            $where .= " AND `status`='".intval($status)."'";
        }
        $r = $this->db->query("SELECT COUNT(*) as count FROM ".self::TABLE_PREFIX."responders WHERE 1 {$where}")->fetch(PDO::FETCH_ASSOC);
        return $r['count'];
    }


    public function get_responders($account_id = null, $status = null, $phone_id = null, $limit1 = 0, $limit2 = 1000, $order_by = "r.id DESC", $timezone = NULL) {
        $where = "";
        if (isset($account_id)) {
            $where .= " AND r.account_id='".intval($account_id)."'";
        }
        if (isset($phone_id)) {
            $where .= " AND r.phone_id='".intval($phone_id)."'";
        }
        if (isset($status)) {
            if (is_array($status)) {
                $where .= " AND r.status IN (".implode(',', $status).")";
            } else {
                $where .= " AND r.status='".intval($status)."'";
            }
        }


        $result = $this->db->query("SELECT r.*, p.phone_number FROM ".self::TABLE_PREFIX."responders r LEFT JOIN ".self::TABLE_PREFIX."personal_phone_numbers p ON (r.phone_id=p.id) WHERE 1 {$where} ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
        if (is_array($result)) {
            foreach ($result as &$r) {
                if ($r) {
                    $r['days'] = explode(',', $r['days']);
                    $r['hours'] = explode(',', $r['hours']);

                    if ($r['date_start'] && $timezone) {
                        $dt = new DateTime($r['date_start'], new DateTimeZone('UTC'));
                        $r['date_start'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
                    }

                    if ($r['date_end'] && $timezone) {
                        $dt = new DateTime($r['date_end'], new DateTimeZone('UTC'));
                        $r['date_end'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
                    }

                }
            }
        }
        return $result;
    }

    public function get_responder($id, $account_id = null, $timezone = null) {
        $where = "r.id='".intval($id)."'";
        if ($account_id) {
            $where .= " AND r.account_id='".intval($account_id)."'";
        }
        $result = $this->db->query("SELECT r.*, p.phone_number FROM ".self::TABLE_PREFIX."responders r LEFT JOIN ".self::TABLE_PREFIX."personal_phone_numbers p ON (r.phone_id=p.id) WHERE {$where}")->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $result['days'] = explode(',', $result['days']);
            $result['hours'] = explode(',', $result['hours']);

            if ($result['date_start'] && $timezone) {
                $dt = new DateTime($result['date_start'], new DateTimeZone('UTC'));
                $result['date_start'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
            }

            if ($result['date_end'] && $timezone) {
                $dt = new DateTime($result['date_end'], new DateTimeZone('UTC'));
                $result['date_end'] = $dt->setTimezone(new DateTimeZone($timezone))->format("Y-m-d H:i:s");
            }
        }
        return $result;
    }

    public function insert_responder($data) {

        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."responders (
          `account_id`, `phone_id`, `date_start`, `date_end`, `days`, `hours`, `title`, `robo_voice`, `voice_action`, `speaker`, `status`, `test`
        ) VALUES (
           :account_id, :phone_id, :date_start, :date_end, :days, :hours, :title, :robo_voice, :voice_action, :speaker, :status, :test
        )");
        $result = $stmt->execute(array(
            ':account_id' => $data['account_id'] ? intval($data['account_id']) : NULL,
            ':phone_id' => intval($data['phone_id']) ? intval($data['phone_id']) : NULL,
            ':date_start' => $data['date_start'] ? date("Y-m-d H:i:s", strtotime($data['date_start'])) : NULL,
            ':date_end' => $data['date_end'] ? date("Y-m-d H:i:s", strtotime($data['date_end'])) : NULL,
            ':days' => isset($data['days']) ? (is_array($data['days']) ? implode(',', $data['days']) : intval($data['days'])) : NULL,
            ':hours' => isset($data['hours']) ? (is_array($data['hours']) ? implode(',', $data['hours']) : intval($data['hours'])) : NULL,
            ':title' => self::clean_varchar($data['title']),
            ':robo_voice' => intval($data['robo_voice']),
            ':voice_action' => intval($data['voice_action']),
            ':speaker' => self::clean_filename($data['speaker']),
            ':status' => intval($data['status']),
            ':test' => intval($data['test']),
        ));
        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function update_responder($data) {
        $old_data = $this->get_responder($data['id'], $this->user()['id']);
        if (!$old_data) {
            $this->set_error('Такого ответчика не существует.');
            return FALSE;
        }

        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."responders SET
            `account_id`=:account_id,
            `phone_id`=:phone_id,
            `date_start`=:date_start,
            `date_end`=:date_end,
            `days`=:days,
            `hours`=:hours,
            `title`=:title,
            `robo_voice`=:robo_voice,
            `voice_action`=:voice_action,
            `speaker`=:speaker,
            `status`=:status,
            `test`=:test
            WHERE
                `id`=:id
            ");
        $result = $stmt->execute(array(
            ':id' => intval($data['id']),
            ':account_id' => $data['account_id'] ? intval($data['account_id']) : $old_data['account_id'],
            ':phone_id' => intval($data['phone_id']) ? intval($data['phone_id']) : $old_data['phone_id'],
            ':date_start' => isset($data['date_start']) ? date("Y-m-d H:i:s", strtotime($data['date_start'])) : $old_data['date_start'],
            ':date_end' => isset($data['date_end']) ? date("Y-m-d H:i:s", strtotime($data['date_end'])) : $old_data['date_end'],
            ':days' => isset($data['days']) ? (is_array($data['days']) ? implode(',', $data['days']) : intval($data['days'])) : $old_data['days'],
            ':hours' => isset($data['hours']) ? (is_array($data['hours']) ? implode(',', $data['hours']) : intval($data['hours'])) : $old_data['hours'],
            ':title' => $data['title'] ? self::clean_varchar($data['title']) : $old_data['title'],
            ':robo_voice' => isset($data['robo_voice']) ? intval($data['robo_voice']) : $old_data['robo_voice'],
            ':voice_action' => isset($data['voice_action']) ? intval($data['voice_action']) : $old_data['voice_action'],
            ':speaker' => isset($data['speaker']) ? self::clean_filename($data['speaker']) : $old_data['speaker'],
            ':status' => isset($data['status']) ? intval($data['status']) : $old_data['status'],
            ':test' => isset($data['test']) ? intval($data['test']) : $old_data['test'],
        ));
        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function delete_responder($id, $account_id = null) {
        if ($responder = $this->get_responder($id, $account_id)) {
            $this->delete_responder_scripts($responder['id']);
            return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."responders WHERE id=:id")->execute(array(':id' => $id));
        } else {
            $this->set_error("Голосовой ответчик не найден.");
            return FALSE;
        }
    }


    //RESPONDER SCRIPTS
    /**
     * @param $actions
     * @return array
     */
    public function validate_responder_script_actions($actions) {
        $result = '';

        if ($actions) {
            $result = array();
            foreach ($actions as $key => $data) {
                $key = is_numeric($key) ? intval($key) : preg_replace('/[^a-zа-яёії]/miu', '', $key);
                if (!$key) {
                    continue;
                }

                if ($data['action']['sms']) {
                    $result[$key]['action']['sms'] = self::clean_varchar($data['action']['sms']);
                } elseif ($data['action']['connect']) {
                    $result[$key]['action']['connect'] = '+'.(preg_replace('/\D/', '', ($data['action']['connect'])));
                } elseif ($data['action']['replay']) {
                    $result[$key]['action']['replay'] = intval($data['action']['replay']);
                } elseif ($data['action']['record']) {
                    $result[$key]['action']['record'] = intval($data['action']['record']);
                } elseif ($data['action']['vote']) {
                    $result[$key]['action']['vote'] = intval($data['action']['vote']);
                }
            }

            foreach ($actions as $key => $data) {
                $key = is_numeric($key) ? intval($key) : preg_replace('/[^a-zа-яёії]/miu', '', $key);
                if (!$key) {
                    continue;
                }

                if ($data['after_action']['goto']) {
                    $result[$key]['after_action']['goto'] = intval($data['after_action']['goto']);
                } elseif ($data['after_action']['replay']) {
                    $result[$key]['after_action']['replay'] = intval($data['after_action']['replay']);
                } elseif ($data['after_action']['end']) {
                    $result[$key]['after_action']['end'] = intval($data['after_action']['end']);
                }
            }
        }


        return $result;
    }

    public function insert_responder_script($data) {

        $data['button_actions'] = $this->validate_responder_script_actions($data['button_actions']);
        $data['voice_actions'] = $this->validate_responder_script_actions($data['voice_actions']);

        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."responder_scripts (
                `responder_id`, `script_id`, `greeting`, `message`, `goodbye`, `greeting_mp3`, `message_mp3`, `goodbye_mp3`, `button_actions`, `voice_actions`
            ) VALUES (
                :responder_id, :script_id, :greeting, :message, :goodbye, :greeting_mp3, :message_mp3, :goodbye_mp3, :button_actions, :voice_actions
        )");

        $result = $stmt->execute(array(
            ':responder_id' => intval($data['responder_id']),
            ':script_id' => intval($data['script_id']),
            ':greeting' => self::clean_varchar($data['greeting']),
            ':message' => self::clean_varchar($data['message']),
            ':goodbye' => self::clean_varchar($data['goodbye']),
            ':greeting_mp3' => self::clean_filename($data['greeting_mp3']),
            ':message_mp3' => self::clean_filename($data['message_mp3']),
            ':goodbye_mp3' => self::clean_filename($data['goodbye_mp3']),
            ':button_actions' => serialize($data['button_actions']),
            ':voice_actions' => serialize($data['voice_actions']),
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }

        return $result;
    }

    public function update_responder_script($data) {

        $data['button_actions'] = $this->validate_responder_script_actions($data['button_actions']);
        $data['voice_actions'] = $this->validate_responder_script_actions($data['voice_actions']);

        $old_data = $this->get_responder_script($data['responder_id'], $data['script_id']);
        if (!$old_data) {
            $this->set_error("Ответчик не найден");
            return FALSE;
        }

        if (self::clean_filename($data['greeting_mp3']) != self::clean_filename($old_data['greeting_mp3'])) {
            @unlink($this->downloads_folder.'/'.$old_data['greeting_mp3']);
        }
        if (self::clean_filename($data['message_mp3']) != self::clean_filename($old_data['message_mp3'])) {
            @unlink($this->downloads_folder.'/'.$old_data['message_mp3']);
        }
        if (self::clean_filename($data['goodbye_mp3']) != self::clean_filename($old_data['goodbye_mp3'])) {
            @unlink($this->downloads_folder.'/'.$old_data['goodbye_mp3']);
        }


        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."responder_scripts
            SET `greeting`=:greeting, `message`=:message, `goodbye`=:goodbye, `greeting_mp3`=:greeting_mp3, `message_mp3`=:message_mp3, `goodbye_mp3`=:goodbye_mp3, `button_actions`=:button_actions,`voice_actions`=:voice_actions
            WHERE `responder_id`=:responder_id AND `script_id`=:script_id
        ");
        $result = $stmt->execute(array(
            ':responder_id' => intval($data['responder_id']),
            ':script_id' => intval($data['script_id']),
            ':greeting' => self::clean_varchar($data['greeting']),
            ':message' => self::clean_varchar($data['message']),
            ':goodbye' => self::clean_varchar($data['goodbye']),
            ':greeting_mp3' => self::clean_filename($data['greeting_mp3']),
            ':message_mp3' => self::clean_filename($data['message_mp3']),
            ':goodbye_mp3' => self::clean_filename($data['goodbye_mp3']),
            ':button_actions' => $data['button_actions'] ? serialize($data['button_actions']) : '',
            ':voice_actions' => $data['voice_actions'] ? serialize($data['voice_actions']) : '',
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }


    public function delete_responder_script($responder_id, $script_id) {
        $script = $this->get_responder_script($responder_id, $script_id);
        if (!$script) {
            $this->set_error("Скрипт ответчика не найден");
            return FALSE;
        }

        @unlink($this->downloads_folder.'/'.$script['greeting_mp3']);
        @unlink($this->downloads_folder.'/'.$script['message_mp3']);
        @unlink($this->downloads_folder.'/'.$script['goodbye_mp3']);
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."responder_scripts WHERE responder_id=:responder_id AND script_id=:script_id ")->execute(array(
            ':responder_id' => $responder_id,
            ':script_id' => $script_id,
        ));

    }

    public function delete_responder_scripts($responder_id) {
        $scripts = $this->get_responder_scripts($responder_id);
        if (is_array($scripts)) {
            foreach ($scripts as $script) {
                @unlink($this->downloads_folder.'/'.$script['greeting_mp3']);
                @unlink($this->downloads_folder.'/'.$script['message_mp3']);
                @unlink($this->downloads_folder.'/'.$script['goodbye_mp3']);
            }
        }
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."responder_scripts WHERE responder_id=:responder_id")->execute(array(
            ':responder_id' => $responder_id,
        ));
    }


    public function get_responder_script($responder_id, $script_id) {
        $r = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."responder_scripts WHERE responder_id=".intval($responder_id)." AND script_id=".intval($script_id))->fetch(PDO::FETCH_ASSOC);
        if ($r['button_actions']) {
            $r['button_actions'] = unserialize($r['button_actions']);
        }
        if ($r['voice_actions']) {
            $r['voice_actions'] = unserialize($r['voice_actions']);
        }
        return $r;
    }

    public function get_responder_scripts($responder_id) {
        $results = $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."responder_scripts WHERE responder_id='".intval($responder_id)."' ORDER BY script_id ASC")->fetchAll(PDO::FETCH_ASSOC);

        if (is_array($results)) {
            $results2 = array();

            foreach ($results as $r) {
                $results2[$r['script_id']] = $r;

                if ($r['button_actions']) {
                    $results2[$r['script_id']]['button_actions'] = unserialize($r['button_actions']);
                }
                if ($r['voice_actions']) {
                    $results2[$r['script_id']]['voice_actions'] = unserialize($r['voice_actions']);
                }
            }
        }
        return $results2 ? $results2 : $results;
    }


    //CRUD логов рассылки
    public function count_responder_logs($responder_id = null, $robophone_responder_id = null) {
        $where = '';
        if ($responder_id) {
            $where .= " AND `responder_id`=".intval($responder_id);
        }
        if ($robophone_responder_id) {
            $where .= " AND `robophone_responder_id`=".intval($robophone_responder_id);
        }
        $r = $this->db->query("SELECT COUNT(*) as count FROM  ".self::TABLE_PREFIX."responder_log WHERE 1 {$where}")->fetch(PDO::FETCH_ASSOC);
        return $r['count'];
    }

    public function get_responder_logs($responder_id = null, $robophone_responder_id = null, $status = null, $limit1 = 0, $limit2 = 1000, $order_by = "id DESC") {
        $where = "";
        if ($responder_id) {
            $where .= " AND `responder_id`=".intval($responder_id);
        }
        if ($robophone_responder_id) {
            $where .= " AND `robophone_responder_id`=".intval($robophone_responder_id);
        }
        if (isset($status)) {
            $where .= " AND `status`=".intval($status);
        }
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."responder_log WHERE 1 {$where} ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_responder_logs_with_phones($responder_id = null, $robophone_responder_id = null, $status = null, $limit1 = 0, $limit2 = 1000, $order_by = "tl.id DESC") {
        $where = "";
        if ($responder_id) {
            $where .= " AND tl.responder_id=".intval($responder_id);
        }
        if ($robophone_responder_id) {
            $where .= " AND tl.robophone_responder_id=".intval($robophone_responder_id);
        }
        if (isset($status)) {
            $where .= " AND tl.status=".intval($status);
        }
        return $this->db->query("SELECT tl.*, lu.phone FROM ".self::TABLE_PREFIX."responder_log tl LEFT JOIN ".self::TABLE_PREFIX."list_users lu ON tl.list_user_id = lu.id WHERE 1 {$where} ORDER BY {$order_by} LIMIT {$limit1}, {$limit2}")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get_responder_log($id) {
        return $this->db->query("SELECT * FROM ".self::TABLE_PREFIX."responder_log WHERE `id`='".intval($id)."'")->fetch(PDO::FETCH_ASSOC);
    }

    public function insert_responder_log($data) {
        $stmt = $this->db->prepare("INSERT INTO ".self::TABLE_PREFIX."responder_log (
            `responder_id`,
            `robophone_responder_id`,
            `phone_id`,
            `status`,
            `call_datetime`,
            `call_duration`,
            `script_answers`
        ) VALUES (
            :responder_id,
            :robophone_responder_id,
            :phone_id,
            :status,
            :call_datetime,
            :call_duration,
            :script_answers
        )");
        $result = $stmt->execute(array(
            ':responder_id' => $data['responder_id'] ? intval($data['responder_id']) : NULL,
            ':robophone_responder_id' => $data['robophone_responder_id'] ? intval($data['robophone_responder_id']) : NULL,
            ':phone_id' => $data['phone_id'] ? intval($data['phone_id']) : NULL,
            ':status' => intval($data['status']),
            ':call_datetime' => isset($data['call_datetime']) ? date("Y-m-d H:i:s", strtotime($data['call_datetime'])) : NULL,
            ':call_duration' => isset($data['call_duration']) ? intval($data['call_duration']) : NULL,
            ':script_answers' => is_array($data['script_answers']) && $data['script_answers'] ? serialize($data['script_answers']) : NULL,
        ));
        if ($result) {
            return $this->db->lastInsertId();
        } else {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;

    }

    public function update_responder_log($data) {
        $old_data = $this->get_responder_log($data['id']);
        if (!$old_data) {
            $this->set_error('Такого лога не существует');
            return FALSE;
        }
        $stmt = $this->db->prepare("UPDATE ".self::TABLE_PREFIX."responder_log SET
            `responder_id`=:responder_id,
            `robophone_responder_id`=:robophone_responder_id,
            `phone_id`=:phone_id,
            `status`=:status,
            `call_datetime`=:call_datetime,
            `call_duration`=:call_duration,
            `script_answers`=:script_answers
            WHERE
                `id`=:id
            ");

        $result = $stmt->execute(array(
            ':id' => intval($data['id']),
            ':responder_id' => $data['responder_id'] ? intval($data['responder_id']) : $old_data['responder_id'],
            ':robophone_responder_id' => $data['robophone_responder_id'] ? intval($data['robophone_responder_id']) : $old_data['robophone_responder_id'],
            ':phone_id' => $data['phone_id'] ? intval($data['phone_id']) : $old_data['phone_id'],
            ':status' => isset($data['status']) ? intval($data['status']) : $old_data['status'],
            ':call_datetime' => isset($data['call_datetime']) ? date("Y-m-d H:i:s", strtotime($data['call_datetime'])) : $old_data['call_datetime'],
            ':call_duration' => isset($data['call_duration']) ? intval($data['call_duration']) : $old_data['call_duration'],
            ':script_answers' => is_array($data['script_answers']) && $data['script_answers'] ? serialize($data['script_answers']) : ($old_data['script_answers'] ? serialize($old_data['script_answers']) : NULL),
        ));

        if (!$result) {
            $e = $stmt->errorInfo();
            $this->set_error($e[2]);
        }
        return $result;
    }

    public function delete_responder_log($id) {
        return $this->db->prepare("DELETE FROM ".self::TABLE_PREFIX."responder_log WHERE id=:id")->execute(array(':id' => $id));
    }
}
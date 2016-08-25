<?php

class SmartResponder {

    public $api_key;
    public $api_id;
	public $delivery_id;
    public $format = "json";
    public $subscribers_url = "http://api.smartresponder.ru/subscribers.html";

    public function __construct($key, $id, $did='') {
        $this->api_key = $key;
        $this->api_id = $id;
		$this->delivery_id = $did;
    }

    private function query($url, $fields) {
        $ch = curl_init("http://api.smartresponder.ru/subscribers.html");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge(array(
            "api_key" => $this->api_key,
            "api_id" => $this->api_id,
			"delivery_id" => $this->delivery_id,
            "format" => $this->format,
        ), $fields));
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }


    public function get_subscribers($id="", $search="") {
        return $this->query($this->subscribers_url, array(
            "action" => "list",
            "id" => $id,
            "search" => $search,
        ));
    }

    /**
     * @param $email
     * @param $fields
        delivery_id	ID рассылки
        track_id	ID трека
        group_id	ID группы
        first_name	имя
        middle_name	отчество
        last_name	фамилия
        birth_year	год рождения
        birth_month	месяц рождения
        birth_day	день рождения
        sex	        пол	m - мужчина, w - женщина, пустая строка - если не известен
        country_id	ID страны
        city	    город
        address	    адрес
        company	    компания
        homepage	URl
        phones	    телефон
        extra_[код]	дополнительное поле
     * @return mixed
     */
    public function create_subscriber($email, $fields) {
        return $this->query($this->subscribers_url,
            array_merge(
                array(
                    "action" => "create",
                    "email" => $email,
                ),
                $fields
            ));
    }

    /**
     * @param $id ID подписчика
     * @param $fields
        delivery_id	ID рассылки
        track_id	ID трека
        group_id	ID группы
        first_name	имя
        middle_name	отчество
        last_name	фамилия
        birth_year	год рождения
        birth_month	месяц рождения
        birth_day	день рождения
        sex	        пол	m - мужчина, w - женщина, пустая строка - если не известен
        country_id	ID страны
        city	    город
        address	    адрес
        company	    компания
        homepage	URl
        phones	    телефон
        extra_[код]	дополнительное поле
     * @return mixed
     */
    public function update_subscriber($id, $fields=array()) {
        return $this->query($this->subscribers_url,
            array_merge(
                array(
                    "action" => "update",
                    "id" => $id,
                ),
                $fields
            ));
    }

    public function delete_subscriber($id) {
        return $this->query($this->subscribers_url, array(
            "action" => "delete",
            "id" => $id,
        ));
    }

    public function check_subscriber($email){
        return $this->query($this->subscribers_url, array(
            "action"=>"check_email",
            "email"=>$email
        ));
    }

    public function add_subscriber_to_delivery($email, $did) {
        return $this->query($this->subscribers_url, array(
            "action" => "link_with_delivery",
//            "search[email]" =>$email,
            "id" => $uid,
            "delivery_id"=>$did,
        ));

    }

    public function add_subscriber_to_group($uid, $gid) {
        return $this->query($this->subscribers_url, array(
            "action" => "link_with_group",
            "id" => $uid,
            "group_id"=>$gid,
        ));

    }

}
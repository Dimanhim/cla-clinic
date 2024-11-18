<?php

namespace app\components;

use app\components\RnovaApi;

class ApiComponent
{
    private $result = [
        'error' => 0,
        'data' => [],
    ];

    private $api;

    const STATUS_ID_WRITED = 1;     // записан
    const STATUS_ID_WAIT   = 2;     // ожидает
    const STATUS_ID_BUSY   = 3;     // на приеме  upcoming

    /**
     *
     */
    public function __construct($test = false)
    {
        $apiKey = $test ? $_ENV['MIS_API_KEY_TEST'] : $_ENV['MIS_API_KEY'];
        $this->api = new RnovaApi($_ENV['MIS_REQUEST_API_URL'], $apiKey);
    }

    public function getClinics($params = [])
    {
        $data = $this->api->getRequest('getClinics', $params);
        return $this->getResult($data);
    }

    public function getServices($params = [])
    {
        $data = $this->api->getRequest('getServices', $params);
        return $this->getResult($data);
    }

    public function getUsers($params = [])
    {
        $data = $this->api->getRequest('getUsers', $params);
        return $this->getResult($data);
    }

    public function getAppointments($params = [])
    {
        $data = $this->api->getRequest('getAppointments', $params);
        return $this->getResult($data);
    }

    public function getPatient($params = [])
    {
        return $this->api->getRequest('getPatient', $params);
    }

    public function createAppointment($params = [])
    {
        $data = $this->api->getRequest('createAppointment', $params);
        return $this->getResult($data);
    }

    public function confirmAppointment($params = [])
    {
        $data = $this->api->getRequest('confirmAppointment', $params);
        return $this->getResult($data);
    }

    public function cancelAppointment($params = [])
    {
        $data = $this->api->getRequest('cancelAppointment', $params);
        return $this->getResult($data);
    }





    /**
     * @param null $message
     * @param int $code
     */
    private function addError($message = null, $code = 400)
    {
        if($message) {
            $this->result['error'] = 1;
            $this->result['data'] = ['code' => $code, 'desc' => $message];
            $this->setLog('error', $this->result, 'errors-log');
        }
    }

    /**
     * @return mixed
     */
    private function hasErrors()
    {
        return $this->result['error'];
    }

    /**
     * @param array $data
     */
    private function setResult($data = [])
    {
        if(!$this->hasErrors()) {
            $this->result['data'] = $data;
        }
    }

    public function getResult($data)
    {
        $this->setResult($data);
        if(isset($data['message']) and $data['message'] and isset($data['data']) and isset($data['data']['code'])) {
            $this->setLog(null, $data);
        }
        return $this->result['data'];

    }

    private function setLog($name = '', $subject = [], $fileName = 'api-errors') {
        file_put_contents($fileName.'.txt', date('d.m.Y H:i:s')." {$name} - ".print_r($subject, true)."\n", FILE_APPEND);
    }
}

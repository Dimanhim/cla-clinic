<?php

namespace app\components;

class RnovaApi
{
    public $_errors = [];
    public $_data = [
        'error' => 0,
        'message' => null,
        'data' => [],
    ];

    protected $request_url = null;
    protected $api_key = null;

    protected $time_start;
    protected $time_end;
    protected $time_now;

    /**
     *
     */
    public function __construct($mis_request_api_url, $mis_api_key)
    {
        $this->request_url = $mis_request_api_url;
        $this->api_key = $mis_api_key;
        $this->time_start = date('d.m.Y').' 00:00';
        $this->time_end = date('d.m.Y').' 23:59';
        $this->time_now = date('d.m.Y H:i');
    }

    /**
     * @param $method
     * @param array $params
     * @param null $version
     * @return array
     */
    public function getRequest($method, $params = [], $version = null)
    {
        return $this->apiRequest($method, $params, $version);
    }

    /**
     * @param $json
     * @return array
     */
    protected function getResponse($json)
    {
        if($json and ($data = json_decode($json, true)) and isset($data['data']) and isset($data['error'])) {
            $this->_addError($data['error']);
            $this->_addData($data['data']);
        }
        elseif($json) {
            $this->_addData($json);
        }
        return $this->_data;
    }

    /**
     * @param $method
     * @param $version
     * @return string|null
     */
    private function getFullUrl($method, $version)
    {
        $url = $this->request_url;
        if($version) {
            $url .= $version .'/';
        }
        $url .= $method . '?api_key='.$this->api_key;
        return $url;
    }

    /**
     * @param $method
     * @param array $params
     * @param $version
     * @return array
     */
    private function apiRequest($method, $params = [], $version)
    {
        $url = $this->getFullUrl($method, $version);
        $curl = curl_init();
        $build_query = http_build_query($params);

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $build_query,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic a3V0c2FldmEuZGFyaWFfYXBpMS5nbWFpbC5jb206Rk03WDJGOVJTWFRFV0w3NQ==',
                'Content-Type: application/x-www-form-urlencoded',
                'Cookie: PHPSESSID=eglk0r28e3ccle8gg03ob9igs2'
            ),
        ));

        $response = curl_exec($curl);

        $info = curl_getinfo($curl);
        if($info['http_code'] != 200) {
            \Yii::info($info);
        }
        curl_close($curl);
        return $this->getResponse($response);
    }



    public function _addData($data) {
        if($data) {
            if(isset($data['code']) and isset($data['desc'])) {
                $this->_data['message'] = implode(' ', $data);
            }
            $this->_data['data'] = $data;
        }
    }

    public function _hasErrors()
    {
        return !empty($this->_errors);
    }

    public function _addError($message)
    {
        if($message) {
            $this->_errors[] = $message;
        }
    }

    public function _errorSummary()
    {
        if($this->_errors) return implode(' ', $this->_errors);
        return false;
    }
}

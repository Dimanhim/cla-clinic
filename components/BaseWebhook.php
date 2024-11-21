<?php

namespace app\components;

class BaseWebhook
{
    use ActionWebHook;

    public $logFileName;

    private $fieldIds = [
        'leads' => [
            'doctor' => [690989, 512391],
            'clinic' => [690991, 457485],
            'consultation_type' => [690993, 172013],
            'gender' => [693533],
            'consultation_date' => [693535],
            'consultation_time' => [693537],
            'consultation_datetime' => [681425],
            'phone' => [19193],
            'mis_id' => [702329],
            'custom_datetime' => [704837],
            'custom_duration' => [704839],
        ],
        'contacts' => [
            'fio' => [172333],
            'surname' => [698335],
            'name' => [698337],
            'fathername' => [698339],
            'city' => [172345],
            'country' => [172335],
            'gender' => [493129],
            'birthday' => [327131],
            'phone' => [19193],
            'email' => [19195],
        ],
    ];

    private $_pipeline_statuses = [
        32970937 => 'Лид на консультацию',
        34872328 => 'Взят в работу',
        47045824 => 'Не квалифицирован',
        32970940 => 'Квалифицирован',
        47291131 => 'Запись SMM',
        32970943 => 'Записан на консультацию',
        47045827 => '7 дней до консультации',
        47693434 => '1 день до консультации',
        47271865 => '1Д Запись подтверждена',
        58878902 => 'Запись МИС',
        47045830 => 'Визит МИС',
    ];

    private $_mis_statuses = [
        1 => 'Записан',
        2 => 'Ожидает',
        3 => 'На приеме',
        4 => 'Завершен',
        5 => 'Отменен',
    ];

    public $result = [
        'error' => 0,
        'message' => null,
        'data' => [],
    ];

    private $_allowed_methods = [
        //'create-appointment',
        'update-appointment',
        'update-status-lead',
        //'create-mis-appointment',
        //'update-mis-appointment',
    ];

    private $_response;
    private $webhookKey;
    private $params;
    private $api;
    private $amo;
    private $requestBody;
    private $method;
    private $isTest = false;
    private $_log_prefix = '';
    private $_create_amo_contacts = 0;
    private $_contact = [
        'id' => null,
        'contact_name' => null,
        'contact_phone' => null,
        'contact_email' => null,
        'contact_gender' => null,
        'contact_birthday' => null,
    ];
    private $_contact_full = [];

    private $_app_created_filename = 'app_created.json';
    private $_app_updated_filename = 'app_updated.json';
    private $_update_hook_filename = 'hook_update.json';
    private $_app_created = [];
    private $_app_updated = [];
    private $_update_hook = [];
    private $_canceled_appointment_id = null;


    // чистый лид из веб-хука
    private $_lead_values = [];

    // обработанный лид из АМО веб-хука
    // для логов
    private $_lead = [];

    private $_appointment = [];

    public function __construct($method, $webhookKey = null, $test = 0)
    {
        $this->isTest = $test;
        $this->method = $method;
        $this->webhookKey = $webhookKey;
        //$this->requestBody = \Yii::$app->request->getRawBody();
        $this->requestBody = \Yii::$app->request->bodyParams;

        $this->setParams();

        $this->validateWebHook();

        if($this->hasErrors()) {
            return false;
        }

        $this->amo = new AmoCrmWorkspace($_ENV['AMO_CLIENT_ID'], $_ENV['AMO_SECRET_KEY'], $_ENV['AMO_REDIRECT_URL'], $_ENV['AMO_TEMPORARY_CODE']);

        //$this->api = new ApiComponent($this->isTest);

        //return false;
        return $this->getPostAction();
    }

    public function setParams()
    {
        $this->params = \Yii::$app->webhookConfig->getConfig();
        /*$paramsFileName = $this->isTest ? 'params_test.php' : 'params.php';
        $this->_log_prefix = $this->isTest ? 'test_' : '';
        $this->params = include ($paramsFileName);*/
    }

    /**
     * @return bool
     */
    public function validateWebHook()
    {
        if(!$this->method) {
            $this->addError('Не указан метод');
            return false;
        }

        if(!in_array($this->method, $this->_allowed_methods)) {
            $this->addError('Метод не существует');
            return false;
        }

        if(!$this->webhookKey) {
            $this->addError('Не указан ключ запроса');
            return false;
        }

        if($this->webhookKey != $_ENV['WEBHOOK_KEY']) {
            $this->addError('Неверный ключ запроса');
            return false;
        }
    }

    public function getPostAction()
    {
        $this->setLog('hook', $this->method, '--hook.txt');
        switch ($this->method) {
            //case 'create-appointment' : return $this->actionCreateAppointment();
            //    break;
            case 'update-appointment' : return $this->actionUpdateAppointment();
                break;
            case 'update-status-lead' : return $this->actionUpdateStatusLead();
                break;
            //case 'create-mis-appointment' : return $this->actionCreateMisAppointment();
            //    break;
            //case 'update-mis-appointment' : return $this->actionUpdateMisAppointment();
            //    break;
        }
        return false;
    }



















    public function setData($data)
    {
        $this->result['data'] = $data;
    }

    /**
     * @param null $message
     * @param int $code
     */
    public function addError($message = null, $logging = false)
    {
        if($message) {
            $this->result['error'] = 1;
            $this->result['message'] = $message;
            if($logging) {
                $this->setLog($message, '', 'trace-log');
            }
            //$this->setLog('error', $this->result, 'errors-log');
        }
    }

    public function getErrorMessage()
    {
        return $this->result['message'];
    }

    /**
     * @param null $message
     * @param int $code
     */
    public function addMessage($message = null)
    {
        if($message) {
            $this->setLog('', $message, 'trace-log');
        }
    }

    /**
     * @return mixed
     */
    public function hasErrors()
    {
        return $this->result['error'];
    }

    /**
     *
     */
    public function deleteErrors()
    {
        $this->result['error'] = 0;
        $this->result['data'] = [];
    }

    /**
     * @param $response
     * @return mixed
     */
    public function getResult()
    {
        //$this->removeAppUpdatedRelation();

        return $this->result['data'];

    }

    /**
     * @param string $name
     * @param array $subject
     * @param string $fileName
     */
    private function setLog($name = '', $subject = [], $fileName = 'info-log') {
        file_put_contents($this->_log_prefix.$fileName.'.txt', date('d.m.Y H:i:s')." {$name} - ".print_r($subject, true)."\n", FILE_APPEND);
    }
}

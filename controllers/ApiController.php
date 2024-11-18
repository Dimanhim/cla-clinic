<?php

namespace app\controllers;

use app\components\BaseWebhook;
use Yii;
use yii\filters\ContentNegotiator;
use yii\helpers\Html;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\Cors;

/**
 *
 */
class ApiController extends Controller
{
    public $webHook;

    public $data = [
        'error' => 0,
        'message' => null,
        'data' => [],
    ];

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            [
                'class' => ContentNegotiator::className(),
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'corsFilter' => [
                'class' => Cors::class,
            ],
        ];
    }

    /**
     * @param null $method
     * @param null $key
     * @param int $test
     * @return mixed
     */
    public function actionHandle($method = null, $key = null, $test = 0)
    {

        $this->webHook = new BaseWebhook($method, $key, $test);

        $result = $this->webHook->getResult();
        if($this->webHook->hasErrors()) {
            $this->addError($this->webHook->getErrorMessage());
        }


        return $this->response($result);
    }


    /**
     * @return bool
     */
    public function hasErrors()
    {
        return $this->data['error'];
    }

    public function addError($message = null, $logging = false)
    {
        if($message) {
            $this->data['error'] = 1;
            $this->data['message'][] = $message;
        }
        if($logging) {
            \Yii::$app->infoLog->add('error message', $message);
        }
    }

    public function addData($data = [])
    {
        $this->data['data'] = $data;
    }

    private function response($data = [])
    {
        if($data) {
            $this->data['data'][] = $data;
        }

        $this->response->data = $this->data;
        return $this->response->data;
    }




}

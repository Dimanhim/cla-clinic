<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\components\AmoCrmWorkspace;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        /*if (Yii::$app->user->isGuest) {
            return $this->redirect(['login']);
        }*/

        /*$amo = new AmoCrmWorkspace($_ENV['AMO_CLIENT_ID'], $_ENV['AMO_SECRET_KEY'], $_ENV['AMO_REDIRECT_URL'], $_ENV['AMO_TEMPORARY_CODE']);
        echo "<pre>";
        print_r($amo);
        echo "</pre>";
        exit;*/

        /*$config = Yii::$app->webhookConfig->getConfig();

        echo "<pre>";
        print_r($config);
        echo "</pre>";
        exit;*/
        return $this->render('index');
    }

    public function actionRedirect()
    {

        return $this->render('redirect');
    }

    public function actionInfo()
    {
        //return true;
        $client = new AmoCrmWorkspace($_ENV['AMO_CLIENT_ID'], $_ENV['AMO_SECRET_KEY'], $_ENV['AMO_REDIRECT_URL'], $_ENV['AMO_TEMPORARY_CODE'], true);
        $client->setAccessToken();
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        $this->layout = 'blanc';

        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}

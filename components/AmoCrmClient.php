<?php

namespace app\components;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Helpers\CustomFieldHelper;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRM;
use AmoCRM\Client\AmoCRMApiClient;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class AmoCrmClient
{
    const TOKEN_PATH = 'amo_access.json';

    private $clientId;
    private $secretKey;
    private $redirectUrl;
    private $temporaryCode;

    private $subDomain = 'drkocharian';

    public $provider = [
        'sub_domain'  => 'drkocharian',
        'base_domain' => 'drkocharian.amocrm.ru',
    ];
    public $client;
    public $apiClientContact;
    public $apiClient;

    /**
     * AmoCrmClient constructor.
     * @param null $clientId
     * @param null $clientSecret
     * @param null $redirectUrl
     * @param null $temporaryCode
     */
    public function __construct($clientId = null, $clientSecret = null, $redirectUrl = null, $temporaryCode = null)
    {
        $this->clientId = $clientId;
        $this->secretKey = $clientSecret;
        $this->redirectUrl = $redirectUrl;
        $this->temporaryCode = $temporaryCode;

        if($this->apiClientContact = new AmoCRMApiClient($this->clientId, $this->secretKey, $this->redirectUrl)) {
            // закомментировать для создания файла amo_access.json
            $this->apiClient = $this->getApiClient();
        }
    }

    /**
     * @return string
     */
    public function getBaseDomain()
    {
        return $this->subDomain . '.amocrm.ru';
    }

    /**
     *
     */
    public function setAccessToken()
    {
        $link = 'https://' . $this->subDomain . '.amocrm.ru/oauth2/access_token';

        /** Соберем данные для запроса */
        $data = [
            'client_id' => $this->clientId,
            'client_secret' => $this->secretKey,
            'grant_type' => 'authorization_code',
            'code' => $this->temporaryCode,
            'redirect_uri' => $this->redirectUrl,
        ];

        /**
         * Нам необходимо инициировать запрос к серверу.
         * Воспользуемся библиотекой cURL (поставляется в составе PHP).
         * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
         */
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
        $response = json_decode($out, true);
        \Yii::$app->infoLog->add('out response', $response);

        if($code >= 200 and $code <= 204) {
            if(
                isset($response['access_token']) &&
                isset($response['refresh_token']) &&
                isset($response['token_type']) &&
                isset($response['expires_in'])

            )
            {
                self::saveToken(
                    [
                        'accessToken' => $response['access_token'],
                        'refreshToken' => $response['refresh_token'],
                        'expires' => $response['token_type'],
                        'expires_in' => $response['expires_in'],
                        'baseDomain' => $this->getBaseDomain(),
                    ]
                );
            }
        }
    }

    /**
     * @param $accessToken
     */
    public function saveToken($accessToken)
    {
        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['expires_in'])
            && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'expires_in' => $accessToken['expires_in'],
                'baseDomain' => $accessToken['baseDomain'],
            ];
            file_put_contents(self::TOKEN_PATH, json_encode($data));
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    /**
     * @return AccessToken
     */
    function getToken()
    {
        $accessTokenFilePath = self::TOKEN_PATH;
        if (!file_exists($accessTokenFilePath)) {
            exit('Access token file not found');
        }

        $accessToken = json_decode(file_get_contents($accessTokenFilePath), true);

        if (
            isset($accessToken)
            && isset($accessToken['accessToken'])
            && isset($accessToken['refreshToken'])
            && isset($accessToken['expires'])
            && isset($accessToken['expires_in'])
            && isset($accessToken['baseDomain'])
        ) {
            return new AccessToken([
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'expires_in' => $accessToken['expires_in'],
                'baseDomain' => $accessToken['baseDomain'],
            ]);
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    /**
     * @return AmoCRMApiClient
     */
    public function getApiClient()
    {
        $apiClient = $this->apiClientContact;

        // создаем интерфейс accessToken в соответствующем классе из хранилища токенов
        $accessToken = $this->getToken();

        $apiClient->setAccessToken($accessToken)
            ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
            ->onAccessTokenRefresh(
                function (AccessTokenInterface $accessToken, string $baseDomain) {
                    self::saveToken(
                        [
                            'accessToken' => $accessToken->getToken(),
                            'refreshToken' => $accessToken->getRefreshToken(),
                            'expires' => $accessToken->getExpires(),
                            'baseDomain' => $baseDomain,
                        ]
                    );
                }
            );
        // устанавливаем access_token в апи клиента
        $apiClient->setAccessToken($accessToken);
        $apiClient->setAccountBaseDomain($this->getBaseDomain());
        return $apiClient;
    }
}

// drkocharian.amocrm.ru
// https://github.com/amocrm/amocrm-api-php#%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B

ТЕСТ
Веб-хук на создание сделки в АМО    - https://people-yii.vps.madeformed.ru/api/handle/?method=create-appointment&key=qou35MGsm5di
Веб-хук на обновление сделки в АМО  - https://people-yii.vps.madeformed.ru/api/handle/?method=update-appointment&key=qou35MGsm5di
Веб-хук на создание визита в АМО    - https://people-yii.vps.madeformed.ru/api/handle/?method=create-mis-appointment&key=qou35MGsm5di
Веб-хук на обновление визита в АМО  - https://people-yii.vps.madeformed.ru/api/handle/?method=update-mis-appointment&key=qou35MGsm5di

БОЕВОЙ
Веб-хук на создание сделки в АМО    - https://amo.dr-flebolog.ru/?method=create-appointment&key=qou35MGsm5di
Веб-хук на обновление сделки в АМО  - https://amo.dr-flebolog.ru/?method=update-appointment&key=qou35MGsm5di
Веб-хук на создание визита в АМО    - https://amo.dr-flebolog.ru/?method=create-mis-appointment&key=qou35MGsm5di
Веб-хук на обновление визита в АМО  - https://amo.dr-flebolog.ru/?method=update-mis-appointment&key=qou35MGsm5di

статус сделки изменен - https://people-yii.vps.madeformed.ru/api/handle/?method=update-status-lead&key=qou35MGsm5di&test=1
сделка изменена       - https://people-yii.vps.madeformed.ru/api/handle/?method=update-appointment&key=qou35MGsm5di&test=1
создан визит          - https://people-yii.vps.madeformed.ru/api/handle/?method=create-mis-appointment&key=qou35MGsm5di&test=1
статус визита изменен - https://people-yii.vps.madeformed.ru/api/handle/?method=update-mis-appointment&key=qou35MGsm5di&test=1



https://github.com/amocrm/amocrm-api-php#%D0%BF%D1%80%D0%B8%D0%BC%D0%B5%D1%80%D1%8B

1. Создаем интеграцию в АМО, грузим логотип, указываем для перенаправления https://api.dr-flebolog.ru/redirect
2. Копируем ключи и доступы
   2.1. Секретный ключ, вводим в .env AMO_SECRET_KEY
   2.2. ID интеграции, вводим в .env AMO_CLIENT_ID
   2.3. Код авторизации (20 минут), вводим в .env AMO_TEMPORARY_CODE
   2.4. Редирект урл - https://example.ru/redirect.php, вводим в .env AMO_TEMPORARY_CODE
3. Запускаем
https://www.amocrm.ru/oauth?client_id=eb653d9c-687a-4748-abc8-aadaa939e8e1&state=12345678987654321&mode=popup
где XXX - AMO_CLIENT_ID
Открывается поле выбора юзера, выбираем и нас редиректит на сайт, где в урле будет code, что то с ним делаем
3. Запускаем
$client = new AmoCrmWorkspace($_ENV['AMO_CLIENT_ID'], $_ENV['AMO_SECRET_KEY'], $_ENV['AMO_REDIRECT_URL'], $_ENV['AMO_TEMPORARY_CODE']);
$client->setAccessToken();
только имеем ввиду, что в конструкторе отработает функция getAccessToken, вернет ошибку и завершит скрипт, ее нужно отключить

в

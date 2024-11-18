<?php

namespace app\components;

class ApiHelper
{

    /**
     * @param $requestData
     * @return bool|mixed
     */
    public static function getDataFromApi($requestData)
    {
        if($requestData and isset($requestData['error']) and !$requestData['error'] and isset($requestData['data']) and $requestData['data']) {
            return $requestData['data'];
        }
        return false;
    }
}

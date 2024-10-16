<?php

namespace app\components;

use yii\base\Component;

class InfoLog extends Component
{
    public static function add($name = '', $value, $fileName = 'info-log.txt')
    {
        file_put_contents($fileName, date('d.m.Y H:i:s').' '.$name.' - '.print_r($value, true)."\n", FILE_APPEND);
    }
}

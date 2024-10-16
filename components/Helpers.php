<?php

namespace app\components;

use Yii;

class Helpers
{
    /**
     * @param $phone
     * @param bool $plus
     * @return mixed|string
     */
    public static function phoneFormat($phone, $plus = true)
    {
        $pattern = '/[^0-9]/';
        $str = preg_replace($pattern, '', $phone);
        return ($plus and $phone) ? "+".$str : $str;
    }

    public static function setPhoneFormat($phone)
    {
        $phone = self::phoneFormat($phone);
        $phoneBody = substr($phone, -10);
        return '+7'.$phoneBody;
    }

    public static function getSecondsInTime($time)
    {
        $seconds = 0;
        $arr = explode(':', $time);
        $seconds += $arr[0] * 60 * 60;
        $seconds += $arr[1] * 60;
        return $seconds;
    }
    public static function getTimeAsString($time)
    {
        if($time) {
            $hours = floor($time / 60 / 60);
            $diff = $time - $hours * 60 * 60;
            $minutes = floor($diff / 60);
            return str_pad($hours, 2, 0, STR_PAD_LEFT).':'.str_pad($minutes, 2, 0, STR_PAD_LEFT);
        }
        return 0;
    }

    public static function getFileInputOptions()
    {
        return [
            'options' => [
                'accept' => 'image/*',
                'multiple' => true
            ],
            'pluginOptions' => [
                'language' => 'ru',
                'browseLabel' => 'Выбрать',
                //'showPreview' => false,
                //'showUpload' => false,
                //'showRemove' => false,
            ]
        ];
    }

}

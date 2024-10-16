<?php

namespace app\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap4\BootstrapAsset',
    ];

    /**
     *
     */
    public function init()
    {
        $this->css = static::getCss();
        $this->js = static::getJs();
    }

    /**
     * @return array
     */
    public static function getCss()
    {
        return [
            'css/bootstrap-icons.css',
            'css/font-awesome.min.css',
            'css/jquery-ui.min.css',
            'css/chosen.css',
            'css/jquery.fancybox.min.css',
            'css/toastr.min.css',
            'css/site.css?v='.mt_rand(1000,10000),
        ];
    }

    /**
     * @return array
     */
    public static function getJs()
    {
        return [
            'js/jquery-ui.min.js',
            'js/bootstrap.min.js',
            'js/chosen.jquery.min.js',
            'js/jquery.fancybox.min.js',
            'js/inputmask.js',
            'js/jquery.inputmask.js',
            'js/toastr.min.js',
            'js/functions.js?v='.mt_rand(1000,10000),
            'js/common.js?v='.mt_rand(1000,10000),
        ];
    }
}

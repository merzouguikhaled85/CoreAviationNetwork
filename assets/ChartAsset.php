<?php

namespace app\assets;

use yii\web\AssetBundle;

/** Loads the chart configuration after its Chart.js 2 dependency. */
class ChartAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.bundle.min.js',
        'js/chart-js-config.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}

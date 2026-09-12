<?php
if (file_exists(__DIR__ . '/maintenance.flag')) {
    http_response_code(503);
    header('Retry-After: 3600');

    $maintenanceFile = __DIR__ . '/maintenance.html';

    if (file_exists($maintenanceFile)) {
        readfile($maintenanceFile);
    } else {
        echo 'Site under maintenance. Please try again later.';
    }

    exit;
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/runtime/logs/php_errors.log');
// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');


require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();

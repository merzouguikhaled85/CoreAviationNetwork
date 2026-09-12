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
/*
 * ENVIRONNEMENT D'EXÉCUTION : la production est le comportement sécurisé par
 * défaut. Le poste local peut activer explicitement le développement avec
 * CAN_APP_ENV=dev et CAN_APP_DEBUG=1, sans modifier ce fichier versionné.
 */
$localEnvironmentFile = dirname(__DIR__) . '/config/env-local.php';
$localEnvironmentValues = is_file($localEnvironmentFile)
    ? require $localEnvironmentFile
    : [];

$applicationEnvironment = getenv('CAN_APP_ENV')
    ?: ($localEnvironmentValues['appEnv'] ?? 'prod');
$debugEnvironmentValue = getenv('CAN_APP_DEBUG');
$debugEnvironmentValue = $debugEnvironmentValue !== false
    ? $debugEnvironmentValue
    : ($localEnvironmentValues['appDebug'] ?? false);
$debugEnabled = $debugEnvironmentValue !== false
    && filter_var($debugEnvironmentValue, FILTER_VALIDATE_BOOLEAN);

defined('YII_ENV') or define('YII_ENV', $applicationEnvironment);
defined('YII_DEBUG') or define(
    'YII_DEBUG',
    $applicationEnvironment === 'dev' && $debugEnabled
);

ini_set('display_errors', YII_DEBUG ? '1' : '0');
ini_set('display_startup_errors', YII_DEBUG ? '1' : '0');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('log_errors', 1);
// Le dossier runtime se trouve à la racine du projet, pas sous web/.
ini_set('error_log', dirname(__DIR__) . '/runtime/logs/php_errors.log');


require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

$config = require __DIR__ . '/../config/web.php';

(new yii\web\Application($config))->run();

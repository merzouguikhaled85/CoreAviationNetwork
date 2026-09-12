<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
$environment = require __DIR__ . '/env.php';
$config = [
    'id' => 'basic',
    'name' => 'Core Aviation Network',
    'basePath' => dirname(__DIR__),
      'homeUrl' => ['site/index'],
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        /*
         * VERSIONNEMENT DES ASSETS : Yii ajoute la date de modification aux URL
         * CSS/JavaScript. Après une correction du rafraîchissement, le navigateur
         * charge donc le nouveau fichier au lieu d'utiliser une copie en cache.
         */
        'assetManager' => [
            'appendTimestamp' => true,
        ],
        'request' => [
            /*
             * La clé n'est plus écrite dans ce fichier versionné. En local, elle
             * vient de config/env-local.php ; en production, la variable système
             * CAN_COOKIE_VALIDATION_KEY est prioritaire.
             */
            'cookieValidationKey' => $environment['cookieValidationKey'],
           'enableCsrfValidation' => false, // Disable CSRF (Not recommended for production)

        ],
        'session' => [
            'class' => 'yii\web\Session',
            'timeout' => 1800,  // 1-hour session timeout
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'loginUrl' => ['site/login'], // Redirect to login page if not authenticated
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        /*
         * L'ancien exemple SMTP commenté a été supprimé, car un commentaire Git
         * contenant un mot de passe reste un secret exposé dans le dépôt.
         */
  'mailer' => [
        /*
         * MAILER RÉSILIENT : limite les attentes SMTP et empêche une panne du
         * serveur de messagerie d'interrompre les opérations métier utilisateur.
         */
        'class' => \app\components\ResilientMailer::class,
        /*
         * RÉSILIENCE SMTP : l'envoi d'un e-mail ne doit pas immobiliser une
         * inscription ou une transition métier jusqu'à la limite PHP de 30 s.
         * Le mailer journalise l'indisponibilité et retourne false après 4 s.
         */
        'smtpTimeout' => 4.0,
        'viewPath' => '@app/mail',
        'useFileTransport' => false, // Set this to false to send real emails
        'messageConfig' => [
            'from' => 'donotreply@coreaviationnetwork.com', // Default "From" address
        ],
        'transport' => [
            /* Le DSN réel est chargé depuis la configuration externe. */
            'dsn' => $environment['mailerDsn'],
        ],
    ],
    
         'notificationManager' => [
        'class' => 'app\components\NotificationManager',
    ],
    
        
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'awaiting-request-response' => 'app\controllers\AwaitingRequestResponseController',
                'mro-aircraft-certificates' => 'mro-aircraft-certificates/index',
                'mro-aircraft-certificates/create' => 'mro-aircraft-certificates/create',
                'mro-aircraft-certificates/view/<id:\d+>' => 'mro-aircraft-certificates/view',
                'mro-aircraft-certificates/update/<id:\d+>' => 'mro-aircraft-certificates/update',
                'mro-aircraft-certificates/delete/<id:\d+>' => 'mro-aircraft-certificates/delete',
                // Admin Disputes
                'admin-disputes' => 'admin-disputes/index', // Route to index action
                'admin-disputes/<id:\d+>/delete' => 'admin-disputes/delete', // Route to delete action
                'admin-disputes/<id:\d+>/reply' => 'admin-disputes/reply', // Route to reply action

                'disputes' => 'ao-mro-dispute/index',
                // DISPUTE ENCODED ID 2026: accept the signed alphanumeric identifier.
                'dispute/view/<id:[A-Za-z0-9]+>' => 'ao-mro-dispute/view',
                'dispute/create' => 'ao-mro-dispute/create',
                'dispute/update/<id:\d+>' => 'ao-mro-dispute/update',
                'dispute/close/<id:\d+>' => 'ao-mro-dispute/close',
                'dispute/remove/<id:\d+>' => 'ao-mro-dispute/remove',
                'ao-mro-dispute/fetch-details/<requestId:\d+>' => 'ao-mro-dispute/fetch-details',

                'certificate-types' => 'certificate-types/index',
                'certificate-types/view/<id:\d+>' => 'certificate-types/view',
                'certificate-types/create' => 'certificate-types/create',
                'certificate-types/update/<id:\d+>' => 'certificate-types/update',
                'certificate-types/delete/<id:\d+>' => 'certificate-types/delete',
                 // Define rules for AO Notifications Preferences
                 'ao-notifications-preferences' => 'ao-notifications-preferences/index',
                 'ao-notifications-preferences/create' => 'ao-notifications-preferences/create',
                 'ao-notifications-preferences/view/<id:\d+>' => 'ao-notifications-preferences/view',
                 'ao-notifications-preferences/update/<id:\d+>' => 'ao-notifications-preferences/update',
                 'ao-notifications-preferences/delete/<id:\d+>' => 'ao-notifications-preferences/delete',
                 
                'requests/view-reports/<id:\d+>' => 'requests/view-reports',
                'requests/download/<id:\d+>' => 'requests/download',

                'currency' => 'currency/index',
                'currency/create' => 'currency/create',
                'currency/view/<id:\d+>' => 'currency/view',
                'currency/update/<id:\d+>' => 'currency/update',
                'currency/delete/<id:\d+>' => 'currency/delete',
                'ao-appointments' => 'ao-appointments/index', 

                'mro-appointments' => 'mro-appointments/index',

                'mro-applications' => 'mro-applications/index',
                'mro-applications/<action:\w+>' => 'mro-applications/<action>',
                'mro-applications/<action:\w+>/<id:\d+>' => 'mro-applications/<action>',
                // MRO airports
                'mro-airports' => 'mro-airports/index',
                'mro-airports/view/<id:\d+>' => 'mro-airports/view',
                'mro-airports/create' => 'mro-airports/create',
                'mro-airports/update/<id:\d+>' => 'mro-airports/update',
                'mro-airports/delete/<id:\d+>' => 'mro-airports/delete',

                'conversations' => 'conversations/index',
                'conversations/<id:\d+>' => 'conversations/view',
                'conversations/create' => 'conversations/create',
                
                // MRO Requests
                'mro-applications/closed-requests' => 'mro-applications/closed-requests',
                'mro-requests' => 'mro-requests/index',
                'mro-requests/contact/<id:\d+>' => 'mro-requests/contact',
                'mro-requests/apply/<id:\d+>' => 'mro-requests/apply',
                'mro-requests/applyupdate/<id:\d+>' => 'mro-requests/applyupdate',

                'mro-requests/recommend-mro/<id:\d+>' => 'mro-requests/recommend-mro',

                'mro-notifications-preferences' => 'mro-notifications-preferences/index',
                'mro-notifications-preferences/<action:(create|view|update|delete)>/<id:\d+>' => 'mro-notifications-preferences/<action>',
                
                'requests/closed-requests' => 'requests/closed-requests',
                'requests/open-requests' => 'requests/open-requests',
                'requests/new-requests' => 'requests/new-requests',
                'requests' => 'requests/index',
                'requests/<id:\d+>' => 'requests/view',
                'requests/create' => 'requests/create',
                'requests/update/<id:\d+>' => 'requests/update',
                'requests/delete/<id:\d+>' => 'requests/delete',
                'requests/<id:\d+>/check-applications' => 'requests/check-applications', // Add this rule
                'requests/<id:\d+>/load-po' => 'requests/load-po', // Add this rule
                'requests/<id:\d+>/view-po' => 'requests/view-po', // Add this rule

                
                'reset-password-mro/<token:\w+>' => 'site/reset-password-mro',

                'send-password-reset/<id:\d+>' => 'site/send-password-reset',
                'reset-password/<token:[\w\-]+>' => 'site/reset-password',

                'mro-certificates' => 'mro-certificates/index',
                'mro-certificates/view/<id:\d+>' => 'mro-certificates/view',
                'mro-certificates/create' => 'mro-certificates/create',
                'mro-certificates/update/<id:\d+>' => 'mro-certificates/update',
                'mro-certificates/delete/<id:\d+>' => 'mro-certificates/delete',
                
                'ao-aircrafts' => 'ao-aircrafts/index', // Default action
                'ao-aircrafts/create' => 'ao-aircrafts/create',
                'ao-aircrafts/update/<id:\d+>' => 'ao-aircrafts/update',
                'ao-aircrafts/delete/<id:\d+>' => 'ao-aircrafts/delete',
                'ao-aircrafts/view/<id:\d+>' => 'ao-aircrafts/view',

                'dashboard/home' => 'dashboard/home',

                'certificates' => 'certificates/index',
                'certificate/create' => 'certificates/create',
                'certificate/update/<id:\d+>' => 'certificates/update',
                'certificate/view/<id:\d+>' => 'certificates/view',
                'certificate/delete/<id:\d+>' => 'certificates/delete',
                
                // Aircraft Model CRUD
                'aircraft-model' => 'aircraft-model/index',
                'aircraft-model/create' => 'aircraft-model/create',
                'aircraft-model/update/<id:\d+>' => 'aircraft-model/update',
                'aircraft-model/delete/<id:\d+>' => 'aircraft-model/delete',
                'aircraft-model/<id:\d+>' => 'aircraft-model/view',

                'airports/get-cities' => 'airports/get-cities',

                'countries' => 'country/index',
                'country/view/<id:\d+>' => 'country/view',
                'country/update/<id:\d+>' => 'country/update',
                'country/delete/<id:\d+>' => 'country/delete',
                'country/create' => 'country/create',

                'cities' => 'city/index',
                'cities/view/<id:\d+>' => 'cities/view', // View a city
                'cities/update/<id:\d+>' => 'cities/update', // Update a city
                'cities/delete/<id:\d+>' => 'cities/delete', // Delete a city
                'cities/create' => 'cities/create', // Create a city

                'airports' => 'airports/index',
                'airport/view/<id:\d+>' => 'airports/view',
                'airport/update/<id:\d+>' => 'airports/update',
                'airport/delete/<id:\d+>' => 'airports/delete',
                'airport/create' => 'airports/create',

                'aircrafts' => 'aircrafts/index',
                'aircrafts/view/<id:\d+>' => 'aircrafts/view',
                'aircrafts/update/<id:\d+>' => 'aircrafts/update',
                'aircrafts/delete/<id:\d+>' => 'aircrafts/delete',
                'aircrafts/create' => 'aircrafts/create',

                'mro-profile/index' => 'mro-profile/index',
                'mro-profile/view/<id:\d+>' => 'mro-profile/view',
                'mro-profile/create' => 'mro-profile/create',
                'mro-profile/update/<id:\d+>' => 'mro-profile/update',
                'mro-profile/delete/<id:\d+>' => 'mro-profile/delete',

                // Route patterns for AO profiles
                'ao-profile' => 'ao-profile/index',
                'ao-profile/create' => 'ao-profile/create',
                'ao-profile/update/<id:\d+>' => 'ao-profile/update',
                'ao-profile/view/<id:\d+>' => 'ao-profile/view',
                'ao-profile/delete/<id:\d+>' => 'ao-profile/delete',
                
                'advert' => 'advert/index',
                'advert/create' => 'advert/create',
                'advert/update/<id:\d+>' => 'advert/update',
                'advert/view/<id:\d+>' => 'advert/view',
                'advert/delete/<id:\d+>' => 'advert/delete',

                // Public advertising media kit
                'advertising' => 'site/advertising',



            ],
        ],

    ],
    'params' => $params,

    /*
     * HOME ROUTE 2026:
     * the root URL and /site/index share the same controller action and design.
     */
    'defaultRoute' => 'site/index',
    
];
if (YII_DEBUG) {
    $config['components']['db']['enableLogging'] = true;
    $config['components']['db']['enableProfiling'] = true;
}
if (YII_ENV_DEV) {
    
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '197.244.32.229'], // add your Ip here
        ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        'allowedIPs' => ['127.0.0.1', '::1', '197.244.32.229'], // add your Ip here
    ];
}

return $config;

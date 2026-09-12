<?php

/**
 * Configuration de la connexion à la base de données.
 *
 * Les identifiants ne sont volontairement plus présents dans ce fichier suivi
 * par Git. Le chargeur `env.php` utilise les variables du serveur en production
 * et le fichier ignoré `env-local.php` pendant le développement local.
 */
$environment = require __DIR__ . '/env.php';

return [
    'class' => 'yii\db\Connection',
    'dsn' => $environment['dbDsn'],
    'username' => $environment['dbUsername'],
    'password' => $environment['dbPassword'],
    'charset' => 'utf8',

    // Options de cache du schéma à activer uniquement en production.
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];

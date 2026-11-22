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

    /*
     * SESSION MYSQL EN UTC : NOW(), CURRENT_TIMESTAMP et la lecture des colonnes
     * TIMESTAMP ne dépendent plus du fuseau du serveur MySQL.
     */
    'on afterOpen' => static function ($event) {
        $event->sender->createCommand("SET time_zone = '+00:00'")->execute();
    },

    // Options de cache du schéma à activer uniquement en production.
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];

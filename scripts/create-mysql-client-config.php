<?php

/**
 * Génère un fichier temporaire utilisable par mysql et mysqldump.
 *
 * Aucun secret n'est envoyé sur la sortie standard : seul le nom de la base
 * est retourné au script Bash. Le fichier temporaire est créé avec le mode 600.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Ce script est réservé à la ligne de commande.\n");
    exit(1);
}

if ($argc !== 2 || trim((string) $argv[1]) === '') {
    fwrite(STDERR, "Chemin du fichier temporaire MySQL manquant.\n");
    exit(1);
}

defined('YII_ENV') or define('YII_ENV', 'prod');
defined('YII_ENV_DEV') or define('YII_ENV_DEV', false);

$environment = require dirname(__DIR__) . '/config/env.php';
$dsn = (string) ($environment['dbDsn'] ?? '');

if (strpos($dsn, 'mysql:') !== 0) {
    fwrite(STDERR, "Le rollback automatique exige une connexion MySQL.\n");
    exit(1);
}

$dsnOptions = [];
foreach (explode(';', substr($dsn, strlen('mysql:'))) as $option) {
    if (strpos($option, '=') === false) {
        continue;
    }

    [$name, $value] = explode('=', $option, 2);
    $dsnOptions[trim($name)] = trim($value);
}

$databaseName = $dsnOptions['dbname'] ?? '';
if ($databaseName === '') {
    fwrite(STDERR, "Le nom de la base est absent du DSN MySQL.\n");
    exit(1);
}

$quoteOption = static function (string $value): string {
    if (preg_match('/[\r\n\0]/', $value)) {
        throw new RuntimeException('Une option MySQL contient un caractère interdit.');
    }

    return '"' . addcslashes($value, "\\\"") . '"';
};

$lines = [
    '[client]',
    'user=' . $quoteOption((string) ($environment['dbUsername'] ?? '')),
    'password=' . $quoteOption((string) ($environment['dbPassword'] ?? '')),
    'host=' . $quoteOption((string) ($dsnOptions['host'] ?? 'localhost')),
];

if (!empty($dsnOptions['port'])) {
    $lines[] = 'port=' . (int) $dsnOptions['port'];
}

if (!empty($dsnOptions['unix_socket'])) {
    $lines[] = 'socket=' . $quoteOption((string) $dsnOptions['unix_socket']);
}

$targetFile = (string) $argv[1];
if (file_put_contents($targetFile, implode(PHP_EOL, $lines) . PHP_EOL, LOCK_EX) === false) {
    fwrite(STDERR, "Impossible d'écrire le fichier temporaire MySQL.\n");
    exit(1);
}

if (!chmod($targetFile, 0600)) {
    @unlink($targetFile);
    fwrite(STDERR, "Impossible de sécuriser le fichier temporaire MySQL.\n");
    exit(1);
}

fwrite(STDOUT, $databaseName);

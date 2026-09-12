<?php

/**
 * Exemple de configuration locale, sans aucune donnée réelle.
 *
 * Copier ce fichier sous le nom `env-local.php`, puis remplacer uniquement les
 * valeurs sur le poste concerné. Le fichier créé est exclu de Git par le
 * `.gitignore` et ne doit jamais être joint à un ticket ou envoyé par e-mail.
 */
return [
    'cookieValidationKey' => 'replace-with-a-long-random-local-key',
    'mailerDsn' => 'smtp://user:encoded-password@smtp.example.com:465',
    'dbDsn' => 'mysql:host=localhost;dbname=your_database',
    'dbUsername' => 'your_local_username',
    'dbPassword' => 'your_local_password',
];

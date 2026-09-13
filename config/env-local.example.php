<?php

/**
 * Exemple de configuration locale, sans aucune donnée réelle.
 *
 * Copier ce fichier sous le nom `env-local.php`, puis remplacer uniquement les
 * valeurs sur le poste concerné. Le fichier créé est exclu de Git par le
 * `.gitignore` et ne doit jamais être joint à un ticket ou envoyé par e-mail.
 */
return [
    // Activer ces valeurs uniquement sur un poste de développement local.
    'appEnv' => 'dev',
    'appDebug' => true,
    'cookieValidationKey' => 'replace-with-a-long-random-local-key',
    'mailerDsn' => 'smtp://user:encoded-password@smtp.example.com:465',
    'dbDsn' => 'mysql:host=localhost;dbname=your_database',
    'dbUsername' => 'your_local_username',
    'dbPassword' => 'your_local_password',

    /*
     * Clés factices officielles réservées au développement local. En production,
     * les remplacer dans env-local.php par les vraies clés du widget Cloudflare.
     */
    'turnstileSiteKey' => '1x00000000000000000000AA',
    'turnstileSecretKey' => '1x0000000000000000000000000000000AA',
    'turnstileExpectedHostname' => 'localhost',

    /*
     * En local, conserver une liste vide : REMOTE_ADDR sera utilise. En production,
     * recopier toutes les plages IPv4 et IPv6 publiees officiellement par Cloudflare.
     * Ne jamais utiliser 0.0.0.0/0 ou ::/0, car les en-tetes deviendraient falsifiables.
     */
    'cloudflareTrustedProxies' => [],
];

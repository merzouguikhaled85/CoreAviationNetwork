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

    /*
     * ENVOI DES E-MAILS - deux environnements distincts :
     *
     * Windows local : SMTP Microsoft 365, port 587 avec STARTTLS automatique.
     * Encoder les caracteres reserves des identifiants dans le DSN :
     * @ devient %40 et % devient %25. Ne jamais versionner un vrai mot de passe.
     *
     * Production can.coreaviationnetwork.com sur GoDaddy cPanel Linux :
     * remplacer uniquement la valeur mailerDsn par 'sendmail://default'.
     * Ce transport utilise le service mail local de GoDaddy ; aucun identifiant
     * Microsoft 365 n'est transmis par le site. L'expediteur reste configure
     * dans config/web.php, mailer.messageConfig.from.
     *
     * Le journal de production montrait un timeout vers smtp.office365.com:587.
     * smtp://localhost:25 provoquait ensuite une erreur de certificat STARTTLS.
     * Sendmail a permis l'envoi du formulaire Early Access en production.
     * Il ne faut pas utiliser Sendmail sur ce poste Windows.
     *
     * Si CAN_MAILER_DSN est defini sur le serveur, il est prioritaire sur ce fichier.
     * SPF du domaine : conserver l'autorisation du relais GoDaddy (secureserver.net).
     */
    'mailerDsn' => 'smtp://user%40example.com:encoded-password@smtp.office365.com:587',
    // GoDaddy cPanel Linux uniquement : 'mailerDsn' => 'sendmail://default',
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

    // 1: Early Access uniquement. 0: ouvre les inscriptions AO/MRO et Advertising.
    'prelaunchMode' => '1',

    /*
     * En local, conserver une liste vide : REMOTE_ADDR sera utilise. En production,
     * recopier toutes les plages IPv4 et IPv6 publiees officiellement par Cloudflare.
     * Ne jamais utiliser 0.0.0.0/0 ou ::/0, car les en-tetes deviendraient falsifiables.
     */
    'cloudflareTrustedProxies' => [],
];

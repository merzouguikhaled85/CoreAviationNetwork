<?php

/*
 * CONFIGURATION EXTERNE : params.php peut être chargé par l'application Web
 * comme par la console. Le chargeur central garantit que les secrets Turnstile
 * proviennent des variables serveur ou du fichier env-local.php ignoré par Git.
 */
$environment = require __DIR__ . '/env.php';

return [
    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',
    'bsVersion' => '5.x',
    'urlIdSecret' => 'CAN_2026_UrlIdSecret_f83a9d71c2b44e1a9f6e7d8c5b3a1209_7Kq9Lm4Xp2Vr8Ts6',

    /*
     * Cle dediee a l'empreinte des jetons de reinitialisation. Elle est derivee
     * du secret serveur existant, sans reutiliser directement ce secret dans le
     * service et reste disponible pour les applications Web et console.
     */
    'passwordResetTokenKey' => hash_hmac(
        'sha256',
        'password-reset-token-v2',
        $environment['cookieValidationKey']
    ),

    /*
     * DESTINATAIRE DES TICKETS SUPPORT :
     * la variable d'environnement permet de changer la boite de reception sans
     * modifier le code. L'adresse existante sert uniquement de repli local.
     */
    'supportEmail' => getenv('CAN_SUPPORT_EMAIL') ?: 'donotreply@coreaviationnetwork.com',

    /*
     * CLOUDFLARE TURNSTILE :
     * les vraies cles restent dans l'environnement du serveur. En developpement,
     * les cles de test officielles Cloudflare permettent de valider le parcours sur
     * localhost sans introduire un secret de production dans le depot.
     */
    'turnstileSiteKey' => $environment['turnstileSiteKey']
        ?: (YII_ENV_DEV ? '1x00000000000000000000AA' : ''),
    'turnstileSecretKey' => $environment['turnstileSecretKey']
        ?: (YII_ENV_DEV ? '1x0000000000000000000000000000000AA' : ''),
    'turnstileExpectedHostname' => $environment['turnstileExpectedHostname'],

    // Feature flag central pour la phase Early Access.
    'prelaunchMode' => filter_var(
        $environment['prelaunchMode'],
        FILTER_VALIDATE_BOOLEAN
    ),



];

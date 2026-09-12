<?php

/**
 * Point d'accès unique à la configuration sensible de l'application.
 *
 * En production, les variables d'environnement du serveur sont prioritaires.
 * Sur un poste de développement, le fichier `env-local.php` fournit les mêmes
 * valeurs sans être versionné. Cette séparation évite de placer les mots de
 * passe directement dans `web.php` ou `db.php`.
 */
$localFile = __DIR__ . '/env-local.php';
$localValues = is_file($localFile) ? require $localFile : [];

if (!is_array($localValues)) {
    throw new RuntimeException(
        'Le fichier config/env-local.php doit retourner un tableau PHP.'
    );
}

/**
 * Lit d'abord la variable système, puis sa valeur locale de développement.
 * Une exception explicite est levée si une valeur obligatoire manque, ce qui
 * évite de démarrer silencieusement avec une configuration non sécurisée.
 */
$readEnvironmentValue = static function (
    string $environmentName,
    string $localKey,
    bool $allowEmpty = false
) use ($localValues): string {
    $systemValue = getenv($environmentName);
    $value = $systemValue !== false
        ? $systemValue
        : ($localValues[$localKey] ?? null);

    if (!is_string($value) || (!$allowEmpty && trim($value) === '')) {
        throw new RuntimeException(sprintf(
            'Configuration manquante : définissez %s ou la clé %s dans config/env-local.php.',
            $environmentName,
            $localKey
        ));
    }

    return $value;
};

return [
    'cookieValidationKey' => $readEnvironmentValue(
        'CAN_COOKIE_VALIDATION_KEY',
        'cookieValidationKey'
    ),
    'mailerDsn' => $readEnvironmentValue('CAN_MAILER_DSN', 'mailerDsn'),
    'dbDsn' => $readEnvironmentValue('CAN_DB_DSN', 'dbDsn'),
    'dbUsername' => $readEnvironmentValue('CAN_DB_USERNAME', 'dbUsername'),

    // Le mot de passe vide reste autorisé pour l'installation MySQL locale.
    // En production, CAN_DB_PASSWORD doit contenir un mot de passe robuste.
    'dbPassword' => $readEnvironmentValue(
        'CAN_DB_PASSWORD',
        'dbPassword',
        true
    ),
];

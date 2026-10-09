# Notifications AOG avec Firebase

Les notifications sont en anglais. Une nouvelle demande AOG déclenche un envoi à **tous les navigateurs et appareils inscrits de chaque MRO ciblé**, et non au seul dernier appareil utilisé. Un appareil supplémentaire ne remplace aucune inscription existante. Les demandes urgentes ou ordinaires et les modifications de statut ne déclenchent pas ce push.

Le ciblage reprend les préférences existantes : accès à l'aéroport, couverture du type de certificat et du modèle d'avion, présence du document d'assurance, puis préférence pour les avions certifiés ou non certifiés. Le consentement push est propre à chaque navigateur et constitue un canal supplémentaire. Les e-mails et notifications de la plateforme restent disponibles.

## Préparer Firebase

Le projet Web et la clé VAPID publique sont enregistrés dans `config/firebase.php`. Aucune initialisation Analytics n'est utilisée.

1. Dans la [console Firebase](https://console.firebase.google.com/), ouvrir le projet `lexical-drake-379620`.
2. Vérifier dans **Project settings → Cloud Messaging** que la paire Web Push correspond à la clé VAPID configurée. Activer la **FCM Registration API** si elle ne l'est pas déjà.
3. Dans **Project settings → Service accounts**, générer une clé privée du compte de service. Le compte doit disposer de l'autorisation d'envoi FCM du projet. Ne pas coller le fichier JSON dans un chat, ni le versionner.
4. Transférer le fichier JSON via un canal privé dans `/home/gav7w8zzh8vm/.config/can/firebase-service-account.json`, en dehors de `public_html`, puis appliquer des permissions `600` au fichier et `700` au dossier contenant le secret.

La bibliothèque serveur utilise PHP cURL et OpenSSL, OAuth2 et l'API HTTP v1 de FCM. Elle refuse une clé stockée dans le projet ou dans `public_html`. Les points d'accès OAuth/FCM sont fixes. La clé privée et les jetons ne sont pas inclus dans les messages de diagnostic.

## Activer sur GoDaddy

L'intégration reste **désactivée par défaut**. Déployer le code, préparer la clé privée, puis migrer la base avant l'activation.

Depuis le terminal cPanel :

```sh
cd ~/public_html/can
php yii migrate --interactive=0
```

Cette commande applique également toute autre migration encore en attente : vérifier la liste affichée et disposer d'une sauvegarde de la base avant une migration de production. Les deux tables de cette intégration sont `push_subscription` et `aog_push_job`.

Ajouter ces entrées au tableau existant de `config/env-local.php`, sans remplacer les autres paramètres :

```php
'firebasePushEnabled' => '1',
'firebaseServiceAccountPath' => '/home/gav7w8zzh8vm/.config/can/firebase-service-account.json',
'firebasePublicUrl' => 'https://can.coreaviationnetwork.com',
```

Les variables `CAN_FIREBASE_PUSH_ENABLED`, `CAN_FIREBASE_SERVICE_ACCOUNT_PATH` et `CAN_FIREBASE_PUBLIC_URL` sont prioritaires si configurées côté serveur. Le même fichier local permet à PHP Web et à la tâche cron de lire les mêmes valeurs. L'URL publique doit utiliser HTTPS et désigner la racine effective du site.

Tester la commande avec le chemin de PHP correspondant à la version utilisée par le site :

```sh
cd ~/public_html/can
php yii aog-push/send 20
```

Dans **cPanel → Cron Jobs**, programmer cette commande chaque minute, si le plan GoDaddy le permet :

```sh
cd /home/gav7w8zzh8vm/public_html/can && /CHEMIN/ABSOLU/DE/PHP yii aog-push/send 20 >> runtime/logs/aog-push-cron.log 2>&1
```

Remplacer `/CHEMIN/ABSOLU/DE/PHP` par le binaire CLI vérifié sur l'hébergement. Le verrou empêche deux exécutions simultanées sur ce serveur. Le lot est configurable entre 1 et 200 appareils par passage. Avec 20 appareils par minute, une demande visant 60 appareils peut demander plusieurs passages : ajuster le lot à la charge et aux limites d'exécution PHP du plan. Les erreurs temporaires sont réessayées jusqu'à cinq tentatives, en respectant `Retry-After`. Les messages expirent au plus tard à l'échéance de réponse, avec une durée maximale d'une heure après création.

## Activer chaque appareil

1. Se connecter avec le compte MRO concerné sur chaque navigateur/appareil.
2. Ouvrir le menu du profil puis **Enable AOG notifications**.
3. Autoriser les notifications du navigateur. Le site ne demande pas cette permission automatiquement.
4. Répéter sur le PC, le téléphone, la tablette et les autres navigateurs utilisés.

Un compte peut avoir plusieurs jetons FCM. Au premier plan, le site affiche un lien vers les demandes. En arrière-plan, le navigateur affiche la notification. Le message contient uniquement un texte générique et ouvre la liste des demandes ; il ne contient aucun détail confidentiel de l'avion ou du client.

Chaque navigateur doit prendre en charge le Web Push et le site doit être servi en HTTPS. Sur iPhone/iPad à partir d'iOS/iPadOS 16.4, installer l'application Web sur l'écran d'accueil et autoriser les notifications depuis cette application. Le manifeste `push/manifest` fournit le mode `standalone`, et l'interface explique cette étape si le navigateur ne permet pas encore les notifications. Les réglages de l'appareil, le réseau et Firebase déterminent la réception ; le serveur ne peut pas garantir une livraison immédiate ni forcer une autorisation. Référence : [WebKit — Web Push sur iOS et iPadOS](https://webkit.org/blog/13878/web-push-for-web-apps-on-ios-and-ipados/).

**Disable AOG notifications** désinscrit uniquement l'appareil courant. La déconnexion désactive également cet appareil, sans couper les autres. Une révocation globale des sessions ou un changement du mot de passe invalide les anciennes inscriptions lors de l'envoi. Un changement de compte sur le même navigateur ne permet pas de recevoir les messages du compte précédent. Une inscription est renouvelée lors de l'ouverture du site avec le consentement enregistré ; elle expire après 60 jours sans renouvellement.

## Vérifier et désactiver

Vérification locale :

```sh
php -d extension=pdo_sqlite vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap tests/_bootstrap.php tests/unit/components/AogPushTest.php
php vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap tests/_bootstrap.php tests/unit/components/FirebaseMessagingSenderTest.php
node tests/unit/js/aog-push.test.js
```

Les tests utilisent une base SQLite en mémoire et un transport simulé : aucun push réel n'est envoyé.

Vérification après activation : inscrire deux appareils avec un MRO éligible, créer une demande AOG de test, puis contrôler la réception sur les deux appareils. Vérifier également un MRO sans accès à l'aéroport et la désactivation sur un seul appareil. Les travaux passent de `pending` à `sent`, `failed` ou `discarded` dans `aog_push_job`. `sent` signifie que FCM a accepté le message, pas que l'utilisateur l'a lu. Les jetons `UNREGISTERED` sont désactivés ; une erreur de contenu `INVALID_ARGUMENT` ne désactive pas automatiquement l'inscription. Un arrêt après l'acceptation FCM mais avant la mise à jour SQL peut occasionner une répétition ; le tag de notification limite les doublons visibles pour une même demande.

Pour désactiver : mettre `firebasePushEnabled` à `'0'` (ou la variable serveur correspondante), puis retirer la tâche cron. Aucun changement n'est nécessaire pour conserver les canaux e-mail et plateforme.

Références officielles : [configuration Web](https://firebase.google.com/docs/cloud-messaging/web/get-started), [réception des messages](https://firebase.google.com/docs/cloud-messaging/web/receive-messages), [API HTTP v1](https://firebase.google.com/docs/cloud-messaging/send/v1-api), [codes d'erreur FCM](https://firebase.google.com/docs/cloud-messaging/error-codes).

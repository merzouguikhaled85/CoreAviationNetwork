# Déploiement automatique sur GoDaddy

Ce document décrit l'initialisation unique du serveur et les secrets GitHub
nécessaires au workflow `.github/workflows/deploy-production.yml`.

Le code complet est installé dans `~/public_html/can`, tandis que le Document
Root du sous-domaine `can.coreaviationnetwork.com` doit pointer précisément vers
`public_html/can/web`. Cette séparation empêche l'accès web direct à `config`,
`vendor`, `runtime`, `migrations` et aux autres sources internes de Yii2.

## 1. Préparer l'authentification SSH GoDaddy

Cette offre Web Hosting cPanel accepte la connexion SSH avec le mot de passe du
compte, mais refuse l'authentification finale par clé malgré les clés marquées
comme autorisées dans cPanel. Le workflow utilise donc `sshpass` et le secret
GitHub `PROD_SSH_PASSWORD` pour fournir le mot de passe sans interaction.

Ce compromis donne au workflow les droits du compte cPanel. Le dépôt doit rester
privé, l'environnement `production` doit être limité à `main`, et le mot de passe
doit être remplacé immédiatement s'il apparaît dans un journal ou un fichier.

## 2. Créer l'environnement GitHub

Dans le dépôt GitHub, ouvrir `Settings > Environments`, créer l'environnement
`production`, puis ajouter ces secrets :

- `PROD_SSH_HOST` : adresse IP ou nom du serveur GoDaddy ;
- `PROD_SSH_PORT` : généralement `22` ;
- `PROD_SSH_USER` : utilisateur cPanel ;
- `PROD_SSH_PASSWORD` : mot de passe cPanel utilisé également par SSH ;
- `PROD_SSH_KNOWN_HOSTS` : ligne de clé d'hôte vérifiée du serveur.

La protection de l'environnement peut demander une approbation manuelle avant
chaque déploiement. Elle est recommandée pour la branche de production.

## 3. Initialiser une seule fois la copie Git sur GoDaddy

Sauvegarder d'abord les fichiers et la base. Depuis SSH, vérifier si le dossier
est déjà un dépôt avec `git status`. Si ce n'est pas le cas, initialiser la copie
de production avec prudence avant d'activer le workflow. Le fichier privé
`config/env-local.php` doit être créé directement sur GoDaddy et confirmé comme
ignoré par `git check-ignore config/env-local.php`.

La copie serveur doit ensuite avoir `origin` configuré vers :

```text
https://github.com/merzouguikhaled85/CoreAviationNetwork.git
```

Le dépôt étant actuellement accessible par URL HTTPS, le serveur peut récupérer
`main` sans stocker de jeton GitHub. S'il devient privé, une seconde clé de
déploiement GitHub en lecture seule devra être configurée côté dépôt.

## 4. Vérification manuelle avant activation

Depuis la racine `~/public_html/can`, exécuter une première fois :

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php yii migrate/new
php yii migrate
php yii cache/flush-schema db
```

Après cette validation, chaque push sur `main` déclenchera automatiquement la
mise à jour Git, Composer, les migrations et le nettoyage du cache de schéma.




///

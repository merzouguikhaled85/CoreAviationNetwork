# Déploiement automatique sur GoDaddy

Ce document décrit l'initialisation unique du serveur et les secrets GitHub
nécessaires au workflow `.github/workflows/deploy-production.yml`.

Le code complet est installé dans `~/public_html/can`, tandis que le Document
Root du sous-domaine `can.coreaviationnetwork.com` doit pointer précisément vers
`public_html/can/web`. Cette séparation empêche l'accès web direct à `config`,
`vendor`, `runtime`, `migrations` et aux autres sources internes de Yii2.

## 1. Créer une clé dédiée à GitHub Actions

La clé utilisée par MobaXterm ne doit pas être réutilisée. Depuis un terminal
local, créer une clé RSA dédiée, sans phrase secrète car le runner GitHub doit
pouvoir l'utiliser sans intervention humaine :

```bash
ssh-keygen -t rsa -b 4096 -m PEM -f can_github_actions -N ""
```

Importer uniquement `can_github_actions.pub` dans cPanel, puis l'autoriser dans
`SSH Access > Manage SSH Keys`. Le fichier `can_github_actions` est la clé privée
à enregistrer dans GitHub ; il ne doit jamais être ajouté au dépôt.

## 2. Créer l'environnement GitHub

Dans le dépôt GitHub, ouvrir `Settings > Environments`, créer l'environnement
`production`, puis ajouter ces secrets :

- `PROD_SSH_HOST` : adresse IP ou nom du serveur GoDaddy ;
- `PROD_SSH_PORT` : généralement `22` ;
- `PROD_SSH_USER` : utilisateur cPanel ;
- `PROD_SSH_PRIVATE_KEY` : contenu complet de `can_github_actions` ;
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

#!/usr/bin/env bash

# Arrête le déploiement dès qu'une commande échoue, qu'une variable est absente
# ou qu'une commande d'un pipeline retourne une erreur. Une migration défaillante
# ne pourra donc pas être présentée comme un déploiement réussi.
set -Eeuo pipefail

APP_DIR="${CAN_APP_DIR:-$HOME/public_html/can}"
PHP_BIN="${CAN_PHP_BIN:-php}"
COMPOSER_BIN="${CAN_COMPOSER_BIN:-composer}"

# Les commandes Yii du déploiement doivent toujours s'exécuter en production,
# même si le compte SSH possède accidentellement des variables locales.
export CAN_APP_ENV="prod"
export CAN_APP_DEBUG="0"

echo "[CAN] Début du déploiement dans ${APP_DIR}"

# Vérifie les éléments indispensables avant de modifier le code de production.
if [[ ! -d "$APP_DIR/.git" ]]; then
    echo "[CAN] ERREUR : le dossier de production n'est pas encore un dépôt Git."
    exit 1
fi

if [[ ! -f "$APP_DIR/config/env-local.php" ]]; then
    echo "[CAN] ERREUR : config/env-local.php est absent sur GoDaddy."
    exit 1
fi

cd "$APP_DIR"

# Empêche une modification manuelle d'un fichier suivi d'être écrasée. Les
# fichiers non suivis tels que env-local.php et les pièces jointes sont permis.
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "[CAN] ERREUR : des modifications Git non validées existent sur le serveur."
    git status --short
    exit 1
fi

# Le verrou système évite deux mises à jour simultanées lorsque plusieurs
# exécutions GitHub seraient déclenchées à quelques secondes d'intervalle.
if command -v flock >/dev/null 2>&1; then
    exec 9>"$HOME/.core-aviation-network-deploy.lock"
    if ! flock -n 9; then
        echo "[CAN] ERREUR : un autre déploiement est déjà en cours."
        exit 1
    fi
fi

# Seule une avance rapide est acceptée. En cas de divergence sur le serveur,
# l'automatisation s'arrête sans réécrire brutalement l'historique de production.
git fetch --prune origin main
git checkout main
git merge --ff-only origin/main

# Installe exactement les versions verrouillées dans composer.lock, exclut les
# outils de développement et optimise l'autoload pour la production.
export COMPOSER_ALLOW_SUPERUSER=1
"$COMPOSER_BIN" install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

# Les migrations sont non interactives afin que GitHub Actions ne reste jamais
# bloqué sur une question. Yii mémorise chaque migration appliquée dans la table
# migration et n'exécute que celles qui sont encore absentes.
"$PHP_BIN" yii migrate --interactive=0

# Le nettoyage du schéma évite que Yii conserve l'ancienne structure des tables.
# Une impossibilité de vider ce cache est signalée sans invalider le déploiement,
# car le cache finira également par expirer selon sa durée configurée.
if ! "$PHP_BIN" yii cache/flush-schema db; then
    echo "[CAN] AVERTISSEMENT : le cache du schéma n'a pas pu être vidé."
fi

# Vide aussi les caches applicatifs susceptibles de conserver un fragment de
# liste ou une ancienne représentation après la mise à jour du dépôt.
if ! "$PHP_BIN" yii cache/flush-all --interactive=0; then
    echo "[CAN] AVERTISSEMENT : les caches applicatifs n'ont pas tous pu être vidés."
fi

# INVALIDATION OPCACHE SUR HÉBERGEMENT PARTAGÉ : le processus PHP utilisé par
# SSH n'emploie généralement pas le même cache mémoire que PHP-FPM/LSAPI. Un
# opcache_reset() lancé en console serait donc trompeur. Mettre à jour la date
# des seuls fichiers PHP applicatifs force leur revalidation par le processus
# web, sans toucher à vendor, aux pièces jointes ou aux données métier.
PHP_SOURCE_DIRS=(
    commands
    components
    config
    controllers
    mail
    migrations
    models
    views
    widgets
    web
)

for php_source_dir in "${PHP_SOURCE_DIRS[@]}"; do
    if [[ -d "$php_source_dir" ]]; then
        find "$php_source_dir" -type f -name '*.php' -exec touch {} +
    fi
done

DEPLOYED_REVISION="$(git rev-parse --short HEAD)"
echo "[CAN] Déploiement terminé avec succès : ${DEPLOYED_REVISION}"

#!/usr/bin/env bash

# Toute erreur interrompt le déploiement et déclenche le retour arrière.
set -Eeuo pipefail
umask 077

APP_DIR="${CAN_APP_DIR:-$HOME/public_html/can}"
PHP_BIN="${CAN_PHP_BIN:-php}"
COMPOSER_BIN="${CAN_COMPOSER_BIN:-composer}"
PERSISTENT_DIR="${CAN_PERSISTENT_DIR:-$HOME/.core-aviation-network-persistent}"
UPLOAD_DIR="$APP_DIR/web/uploads"
MAINTENANCE_FLAG="$APP_DIR/web/maintenance.flag"
DEPLOY_ID="$(date +%Y%m%d%H%M%S)-$$"
DEPLOY_SNAPSHOT_DIR="$PERSISTENT_DIR/deploy-$DEPLOY_ID"
PERSISTENT_UPLOAD_DIR="$DEPLOY_SNAPSHOT_DIR/uploads"
DATABASE_BACKUP="$DEPLOY_SNAPSHOT_DIR/database.sql"
DATABASE_TABLES_BEFORE="$DEPLOY_SNAPSHOT_DIR/tables-before.txt"
PUBLIC_URL="${CAN_PUBLIC_URL:-https://can.coreaviationnetwork.com/}"
MYSQL_CONFIG=""
DATABASE_NAME=""
PREVIOUS_REVISION=""
CODE_UPDATED=0
MIGRATIONS_STARTED=0
ROLLBACK_RUNNING=0

# Les commandes Yii doivent toujours utiliser la configuration de production.
export CAN_APP_ENV="prod"
export CAN_APP_DEBUG="0"

cleanup_secrets() {
    if [[ -n "$MYSQL_CONFIG" && -f "$MYSQL_CONFIG" ]]; then
        rm -f "$MYSQL_CONFIG"
    fi
}

rollback_deployment() {
    local original_status="$1"
    local failed_line="$2"
    local failed_command="$3"
    local rollback_failed=0

    # Évite qu'une erreur pendant la restauration rappelle récursivement ce bloc.
    trap - ERR
    set +e

    if [[ "$ROLLBACK_RUNNING" -eq 1 ]]; then
        exit "$original_status"
    fi
    ROLLBACK_RUNNING=1

    echo "[CAN] ÉCHEC ligne ${failed_line} : ${failed_command}"
    echo "[CAN] Démarrage du retour arrière."
    mkdir -p "$(dirname "$MAINTENANCE_FLAG")"
    touch "$MAINTENANCE_FLAG"

    if ! cd "$APP_DIR"; then
        rollback_failed=1
    fi

    # La base est restaurée dès qu'une migration a pu commencer. Cela évite de
    # dépendre uniquement de safeDown(), qui peut supprimer des données.
    if [[ "$MIGRATIONS_STARTED" -eq 1 ]]; then
        if [[ -s "$DATABASE_BACKUP" && -n "$DATABASE_NAME" && -f "$MYSQL_CONFIG" ]]; then
            echo "[CAN] Restauration de la base MySQL."
            if mysql --defaults-extra-file="$MYSQL_CONFIG" "$DATABASE_NAME" \
                < "$DATABASE_BACKUP"; then
                # Le dump recrée les anciennes tables, mais ne connaît pas une
                # nouvelle table créée par une migration partiellement exécutée.
                # Toute table absente avant le déploiement est donc retirée.
                local tables_after="$DEPLOY_SNAPSHOT_DIR/tables-after-rollback.txt"
                mysql \
                    --defaults-extra-file="$MYSQL_CONFIG" \
                    --batch \
                    --skip-column-names \
                    "$DATABASE_NAME" \
                    -e "SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME" \
                    > "$tables_after" || rollback_failed=1

                if [[ "$rollback_failed" -eq 0 && -s "$DATABASE_TABLES_BEFORE" ]]; then
                    while IFS= read -r table_name; do
                        if ! grep -Fqx -- "$table_name" "$DATABASE_TABLES_BEFORE"; then
                            escaped_table="${table_name//\`/\`\`}"
                            echo "[CAN] Suppression de la table ajoutée : ${table_name}"
                            mysql \
                                --defaults-extra-file="$MYSQL_CONFIG" \
                                "$DATABASE_NAME" \
                                -e "SET FOREIGN_KEY_CHECKS=0; DROP TABLE IF EXISTS \`${escaped_table}\`; SET FOREIGN_KEY_CHECKS=1;" \
                                || rollback_failed=1
                        fi
                    done < "$tables_after"
                fi
            else
                rollback_failed=1
            fi
        else
            echo "[CAN] ERREUR : la sauvegarde MySQL du rollback est indisponible."
            rollback_failed=1
        fi
    fi

    # Le serveur revient exactement au commit qui fonctionnait avant le merge.
    if [[ "$CODE_UPDATED" -eq 1 && -n "$PREVIOUS_REVISION" ]]; then
        echo "[CAN] Restauration du code ${PREVIOUS_REVISION}."
        git reset --hard "$PREVIOUS_REVISION" || rollback_failed=1

        "$COMPOSER_BIN" install \
            --no-dev \
            --prefer-dist \
            --no-interaction \
            --no-progress \
            --optimize-autoloader || rollback_failed=1
    fi

    # Les pièces jointes sont recopiées sans supprimer un éventuel fichier
    # apparu pendant l'opération.
    if [[ -d "$PERSISTENT_UPLOAD_DIR" ]]; then
        mkdir -p "$UPLOAD_DIR"
        cp -a "$PERSISTENT_UPLOAD_DIR/." "$UPLOAD_DIR/" || rollback_failed=1
    fi

    if [[ "$rollback_failed" -eq 0 ]]; then
        "$PHP_BIN" yii cache/flush-schema db >/dev/null 2>&1 || true
        "$PHP_BIN" yii cache/flush-all --interactive=0 >/dev/null 2>&1 || true
        "$PHP_BIN" yii help >/dev/null 2>&1 || rollback_failed=1
    fi

    cleanup_secrets

    if [[ "$rollback_failed" -eq 0 ]]; then
        rm -f "$MAINTENANCE_FLAG"
        if curl --fail --silent --show-error --location --max-time 30 \
            "$PUBLIC_URL" >/dev/null; then
            echo "[CAN] Retour arrière terminé. L'ancienne version est rétablie."
        else
            touch "$MAINTENANCE_FLAG"
            rollback_failed=1
        fi
    fi

    if [[ "$rollback_failed" -ne 0 ]]; then
        echo "[CAN] ÉCHEC DU RETOUR ARRIÈRE : la maintenance reste active."
        echo "[CAN] Sauvegarde à examiner : ${DEPLOY_SNAPSHOT_DIR}"
    fi

    exit "$original_status"
}

echo "[CAN] Début du déploiement dans ${APP_DIR}"

# Les contrôles préalables s'exécutent avant toute modification de production.
for command_name in git mysql mysqldump curl; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "[CAN] ERREUR : la commande ${command_name} est indisponible."
        exit 1
    fi
done

if ! command -v "$PHP_BIN" >/dev/null 2>&1; then
    echo "[CAN] ERREUR : PHP est indisponible (${PHP_BIN})."
    exit 1
fi

if ! command -v "$COMPOSER_BIN" >/dev/null 2>&1; then
    echo "[CAN] ERREUR : Composer est indisponible (${COMPOSER_BIN})."
    exit 1
fi

if [[ ! -d "$APP_DIR/.git" ]]; then
    echo "[CAN] ERREUR : le dossier de production n'est pas un dépôt Git."
    exit 1
fi

if [[ ! -f "$APP_DIR/config/env-local.php" ]]; then
    echo "[CAN] ERREUR : config/env-local.php est absent sur GoDaddy."
    exit 1
fi

REQUIRED_FILES=(
    assets/AppAsset.php
    web/index.php
    web/maintenance.html
    scripts/create-mysql-client-config.php
)

for required_file in "${REQUIRED_FILES[@]}"; do
    if [[ ! -f "$APP_DIR/$required_file" ]]; then
        echo "[CAN] ERREUR : ${required_file} est absent."
        exit 1
    fi
done

cd "$APP_DIR"

# La production doit rester attachée à main pour que la révision précédente
# puisse être restaurée sans ambiguïté.
CURRENT_BRANCH="$(git symbolic-ref --quiet --short HEAD || true)"
if [[ "$CURRENT_BRANCH" != "main" ]]; then
    echo "[CAN] ERREUR : la production doit être sur main (branche actuelle : ${CURRENT_BRANCH:-détachée})."
    exit 1
fi

# Une modification suivie faite manuellement sur GoDaddy ne doit jamais être
# écrasée silencieusement par le déploiement.
if ! git diff --quiet || ! git diff --cached --quiet; then
    echo "[CAN] ERREUR : des modifications Git non validées existent sur le serveur."
    git status --short
    exit 1
fi

# Empêche deux déploiements de modifier simultanément le code ou la base.
if command -v flock >/dev/null 2>&1; then
    exec 9>"$HOME/.core-aviation-network-deploy.lock"
    if ! flock -n 9; then
        echo "[CAN] ERREUR : un autre déploiement est déjà en cours."
        exit 1
    fi
fi

PREVIOUS_REVISION="$(git rev-parse HEAD)"
mkdir -p "$DEPLOY_SNAPSHOT_DIR"
printf '%s\n' "$PREVIOUS_REVISION" > "$DEPLOY_SNAPSHOT_DIR/previous-revision.txt"
cp -p config/env-local.php "$DEPLOY_SNAPSHOT_DIR/env-local.php"
chmod 600 "$DEPLOY_SNAPSHOT_DIR/env-local.php"

# À partir de ce point, toute erreur conserve la maintenance ou restaure
# automatiquement la dernière version fonctionnelle.
trap 'rollback_deployment "$?" "$LINENO" "$BASH_COMMAND"' ERR
touch "$MAINTENANCE_FLAG"
echo "[CAN] Mode maintenance activé."

# Les uploads sont sauvegardés hors du dossier public et hors du dépôt Git.
mkdir -p "$PERSISTENT_UPLOAD_DIR"
if [[ -d "$UPLOAD_DIR" ]]; then
    cp -a "$UPLOAD_DIR/." "$PERSISTENT_UPLOAD_DIR/"
fi

# Le mot de passe MySQL n'est jamais placé dans la ligne de commande ni affiché.
MYSQL_CONFIG="$(mktemp)"
DATABASE_NAME="$("$PHP_BIN" scripts/create-mysql-client-config.php "$MYSQL_CONFIG")"
if [[ -z "$DATABASE_NAME" ]]; then
    echo "[CAN] ERREUR : le nom de la base MySQL est vide."
    false
fi

# La liste initiale permet de détecter une table laissée par une migration
# interrompue avant son inscription dans la table Yii migration.
mysql \
    --defaults-extra-file="$MYSQL_CONFIG" \
    --batch \
    --skip-column-names \
    "$DATABASE_NAME" \
    -e "SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME" \
    > "$DATABASE_TABLES_BEFORE"

if [[ ! -s "$DATABASE_TABLES_BEFORE" ]]; then
    echo "[CAN] ERREUR : aucune table MySQL n'a été trouvée avant le déploiement."
    false
fi

echo "[CAN] Sauvegarde cohérente de la base MySQL."
mysqldump \
    --defaults-extra-file="$MYSQL_CONFIG" \
    --single-transaction \
    --quick \
    --skip-lock-tables \
    --no-tablespaces \
    --default-character-set=utf8mb4 \
    "$DATABASE_NAME" \
    > "$DATABASE_BACKUP"

if [[ ! -s "$DATABASE_BACKUP" ]]; then
    echo "[CAN] ERREUR : la sauvegarde MySQL est vide."
    false
fi
chmod 600 "$DATABASE_BACKUP"

# Seule une avance rapide est acceptée afin de préserver l'historique de main.
git fetch --prune origin main
git checkout main
git merge --ff-only origin/main
CODE_UPDATED=1

mkdir -p "$UPLOAD_DIR"
cp -a "$PERSISTENT_UPLOAD_DIR/." "$UPLOAD_DIR/"

mkdir -p runtime/logs web/assets "$UPLOAD_DIR"
chmod 775 runtime runtime/logs web/assets "$UPLOAD_DIR"

if [[ ! -w runtime || ! -w runtime/logs || ! -w web/assets || ! -w "$UPLOAD_DIR" ]]; then
    echo "[CAN] ERREUR : runtime, web/assets ou web/uploads n'est pas accessible en écriture."
    false
fi

# Composer reconstruit les dépendances uniquement depuis le verrou validé.
export COMPOSER_ALLOW_SUPERUSER=1
"$COMPOSER_BIN" validate --no-check-publish
"$COMPOSER_BIN" install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader
"$COMPOSER_BIN" check-platform-reqs --no-dev

"$PHP_BIN" -l assets/AppAsset.php >/dev/null
"$PHP_BIN" -l web/index.php >/dev/null
"$PHP_BIN" yii help >/dev/null

# Dès que cette variable vaut 1, toute erreur provoque la restauration du dump.
MIGRATIONS_STARTED=1
"$PHP_BIN" yii migrate --interactive=0

if ! "$PHP_BIN" yii cache/flush-schema db; then
    echo "[CAN] AVERTISSEMENT : le cache du schéma n'a pas pu être vidé."
fi

if ! "$PHP_BIN" yii cache/flush-all --interactive=0; then
    echo "[CAN] AVERTISSEMENT : les caches applicatifs n'ont pas tous été vidés."
fi

# Sur cPanel, PHP CLI et le processus Web ne partagent pas toujours OPcache.
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

# La plateforme est rouverte seulement après tous les contrôles locaux.
rm -f "$MAINTENANCE_FLAG"

# Une erreur HTTP après la réouverture déclenche encore le rollback et remet
# immédiatement la page de maintenance à disposition des utilisateurs.
curl \
    --fail \
    --silent \
    --show-error \
    --location \
    --retry 2 \
    --retry-delay 3 \
    --max-time 30 \
    "$PUBLIC_URL" \
    >/dev/null

trap - ERR
cleanup_secrets

echo "[CAN] Déploiement terminé avec succès : ${DEPLOYED_REVISION}"
echo "[CAN] Sauvegarde de rollback conservée : ${DEPLOY_SNAPSHOT_DIR}"

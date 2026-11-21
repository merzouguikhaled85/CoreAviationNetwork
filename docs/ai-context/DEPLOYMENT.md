# Deployment

Detailed procedures already exist: `docs/deployment-godaddy.md`, `docs/firebase-aog-notifications.md`, `docs/AUDIT_TRAIL.md`. This file summarises them.

## Pipeline **[V]** `.github/workflows/deploy-production.yml`, `scripts/deploy-production.sh`
- Trigger: push to `main` or manual dispatch; single concurrent deployment; GitHub environment `production`.
- GitHub Actions connects by SSH (password via `sshpass`, known_hosts pinned; secrets `PROD_SSH_*`) and runs `scripts/deploy-production.sh` on GoDaddy.
- Script: pre-checks (git, mysql, mysqldump, curl; clean tree; on `main`) → lock → **maintenance flag** → DB dump + uploads backup → `git pull` → `composer install` from lock → `php yii migrate` → fix asset permissions → restore `web/uploads/.htaccess` → remove maintenance.
- **Automatic rollback** on error: `git reset --hard` to previous revision, restore DB dump (drops tables created by the failed migration), restore uploads; maintenance stays on if rollback fails.
- Post-deploy checks: HTTP probe of subdomain and critical published assets.
- MySQL client credentials for the script: `scripts/create-mysql-client-config.php` (generates option file outside Git).

## Hosting modes
1. Recommended: subdomain document root → `public_html/can/web`.
2. Fallback: repo root is the document root; root `.htaccess` rewrites to `web/` and `web/index.php` fixes base URLs.

## Environment variables (names only)
`CAN_APP_ENV`, `CAN_APP_DEBUG`, `CAN_COOKIE_VALIDATION_KEY`, `CAN_MAILER_DSN`, `CAN_DB_DSN`, `CAN_DB_USERNAME`, `CAN_DB_PASSWORD`, `CAN_TURNSTILE_SITE_KEY`, `CAN_TURNSTILE_SECRET_KEY`, `CAN_TURNSTILE_EXPECTED_HOSTNAME`, `CAN_CLOUDFLARE_TRUSTED_PROXIES`, `CAN_FIREBASE_PUSH_ENABLED`, `CAN_FIREBASE_SERVICE_ACCOUNT_PATH`, `CAN_FIREBASE_PUBLIC_URL`, `CAN_PRELAUNCH_MODE`, `CAN_SUPPORT_EMAIL`. Template: `config/env-local.example.php`.

## Scheduled tasks
- `php yii aog-push/send 20` — only when Firebase push enabled (cron frequency per `docs/firebase-aog-notifications.md`).
- No other cron found (no appointment reminders, no overdue escalation, no cleanup of `request_change_event`/`aog_push_job`).

## Local development
`config/env-local.php` with `appEnv=dev`, `appDebug=1`; Vagrant/Docker files from the Yii template exist but are **not verified** as working.

## Rules
Never deploy without explicit authorization. Migrations must be reversible and safe on the live DB (the core schema is not under migration control — always inspect the real table first).

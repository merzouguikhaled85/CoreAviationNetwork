# Architecture

## Stack **[V]** `composer.json`, `composer.lock`, `config/*`
- PHP `>=7.4` (production version **[A]** not pinned in repo — verify on GoDaddy)
- Yii2 Basic template `~2.0.45`, Bootstrap 5 (`yii2-bootstrap5`), jQuery UI, Kartik Select2 + DateTimePicker
- Symfony Mailer (`yii2-symfonymailer`) wrapped by `components/ResilientMailer.php` (4 s SMTP timeout, errors swallowed/logged)
- MySQL (charset `utf8`, not `utf8mb4`) — `config/db.php`
- FileCache, PHP sessions (30 min idle, 12 h absolute)
- Firebase Cloud Messaging (HTTP v1, service account) for AOG web push — `components/FirebaseMessagingSender.php`
- Cloudflare: proxy, Turnstile CAPTCHA, `CF-Connecting-IP` / `CF-IPCountry` trusted only from configured CIDRs
- Front end: server-rendered PHP views, vanilla JS + jQuery (`web/js/*`), Chart.js (`assets/ChartAsset.php`), Remix Icon / Font Awesome
- Tests: Codeception + PHPUnit (`tests/`)

## Layout
```
public_html/            ← repo root AND (in one hosting mode) the Apache document root
  .htaccess             ← rewrites everything to web/, blocks source dirs (see SECURITY.md)
  web/index.php         ← entry script; env from CAN_APP_ENV / config/env-local.php (default prod)
  config/               ← web.php, console.php, env.php (secrets loader), params.php, db.php
  controllers/ (45)     ← all business logic lives here ("fat controllers")
  models/ (50)          ← ActiveRecord + form models, thin
  components/ (22)      ← cross-cutting services (audit, push, mailer, URL id signing, sessions)
  commands/             ← console: aog-push/send (cron), hello (template)
  views/ (37 dirs)      ← PHP templates; layouts in views/layouts
  mail/                 ← email templates
  migrations/ (14)      ← ONLY recent tables; the core schema has no migrations
  tests/, docs/, scripts/, .github/workflows/
```

## Request lifecycle (web)
`.htaccess` → `web/index.php` → `config/web.php` bootstraps `log`, `auditTrail` (`AuditTrailBootstrap`), `sessionSecurity` (`SessionSecurityBootstrap`) → controller `behaviors()` (`AccessControl` + `VerbFilter`) → action.

## Cross-cutting components **[V]**
| Component | Role |
|---|---|
| `AuditTrailBootstrap` + `AuditService` + `AuditDataRedactor` + `AuditRequestContext` + `CloudflareAuditGeoIpProvider` | Global ActiveRecord insert/update/delete audit into `audit_log`, with redaction, request id, IP/country |
| `SessionSecurityBootstrap` + `RevokesSessionsOnPasswordChange` | `auth_version` per profile; bump → all sessions logged out |
| `RequestChangeBehavior` + `RequestChangePublisher` | Writes `request_change_event` rows on AR changes to request-related tables; used by AJAX list refresh (`RequestSyncController`, `web/js/request-sync.js`) |
| `AogPushBehavior` + `AogPushService` + `FirebaseMessagingSender` + `commands/AogPushController` | Queues `aog_push_job` for eligible MRO subscriptions on AOG requests; cron sends |
| `UrlIdHelper` | Signs integer IDs in URLs (base36 id + 8-hex HMAC) using `params['urlIdSecret']` |
| `NotificationActionUrl` | Normalises in-app notification action URLs |
| `ResilientMailer` | SMTP with short timeout, never breaks business operations |
| `PasswordResetTokenService`, `PasswordResetEmail` | Hashed, expiring reset tokens |
| `CsvCellSanitizer` | CSV-injection protection for exports |
| `MroRequestCounters` | **uncommitted** new file (work in progress at discovery time) |
| `NotificationManager` | **unused** (no references) |

## Patterns in use
- Role check = `Yii::$app->session->get('user_type')` inside `AccessControl` `matchCallback` or in action bodies.
- Ownership check = compare record `ao_id`/`mro_id` with session (inconsistent — see SECURITY.md).
- In-app notification = `Yii::$app->runAction('notification/save-notification', [...])` (the action refuses direct HTTP calls).
- Email = `Yii::$app->mailer->compose('<template>', [...])`, gated by per-user notification preferences.
- Uploads saved under `web/uploads/` with random names.
- Comments in code are mostly French; UI and identifiers English.

## Frontend ↔ backend
Classic form posts with CSRF; some AJAX JSON endpoints (`notification/*`, `request-sync/changes`, `push/*`, cascading dropdowns like `*/get-cities`, `*/search-airports`). No REST API, no SPA.

# CLAUDE.md — Core Aviation Network (CAN)

Yii2 (Basic template) + MySQL marketplace linking **Aircraft Operators (AO)** and **MRO** maintenance organisations: request → MRO quote → PO → work → CRS → closure. Hosted on GoDaddy (Apache/cPanel) behind Cloudflare. Code comments are in French; UI/identifiers in English.

## Read first
| Need | File |
|---|---|
| What the product is | `docs/ai-context/PROJECT_OVERVIEW.md` |
| Stack, folders, components | `docs/ai-context/ARCHITECTURE.md` |
| Tables, statuses, time handling | `docs/ai-context/DATABASE.md` |
| Business rules (priority, matching, workflow) | `docs/ai-context/BUSINESS_RULES.md` |
| Feature status + open questions | `docs/ai-context/FEATURES.md` |
| State diagrams | `docs/ai-context/WORKFLOWS.md` |
| Routes & access | `docs/ai-context/API_REFERENCE.md` |
| Security controls & open findings | `docs/ai-context/SECURITY.md` |
| Views, CSS, JS | `docs/ai-context/UI_UX.md` |
| CI/CD, env vars, cron | `docs/ai-context/DEPLOYMENT.md` |
| Debt backlog | `docs/ai-context/TECHNICAL_DEBT.md` |
| Decisions | `docs/ai-context/DECISIONS.md` |
| History of KB updates | `docs/ai-context/CHANGELOG.md` |
Older operational docs: `docs/AUDIT_TRAIL.md`, `docs/deployment-godaddy.md`, `docs/firebase-aog-notifications.md`.

## Key facts
- Roles: `admin` / `ao` / `mro`, each its own table (`admin_profiles`, `ao_profiles`, `mro_profiles`). Role is read from `Yii::$app->session->get('user_type')`; ids from `session->get('ao_id'|'mro_id'|'admin_id')`. No RBAC.
- Request statuses: `created → answered → po_loaded → work_accepted → work_started → report_submitted → closed` (+ legacy `update_request*`, unused `cancelled`).
- Priority `aog|urgent|routine`; `response_due_at_utc` is computed only in `Requests::beforeSave()` (UTC). ETA/ETD are server-local naive datetimes.
- Change orders live in `request_change*` (`RequestChangesController`) — the reference implementation for role + ownership + status guards.
- **Core tables have no migrations.** Inspect the live schema before any DB change.
- Secrets come from `CAN_*` env vars or git-ignored `config/env-local.php`. Never write secrets into code or docs.

## Working rules
1. Read the relevant docs above, then the actual source, before proposing changes.
2. Preserve existing behaviour unless explicitly asked; smallest coherent change; reuse patterns (`UrlIdHelper`, `findOwned*` helpers, `notification/save-notification` via `runAction`, `ResilientMailer`, preference-gated notifications).
3. Every action needs: `AccessControl` role check + per-record ownership check + status guard for transitions. Signed IDs are not authorization.
4. Encode output (`Html::encode`), keep CSRF, whitelist upload types.
5. DB changes only via reversible migrations, with compatibility impact explained.
6. Run tests when possible (`vendor/bin/codecept run`); note that core-workflow tests are missing.
7. After each task, update the affected `docs/ai-context/*` files and add a `CHANGELOG.md` entry; record architectural choices in `DECISIONS.md`.
8. Never deploy (push to `main` triggers production deploy) without explicit authorization.
9. If docs and code disagree, investigate and report; code = current behaviour, not necessarily the intended spec.

Last full discovery: 2026-11-21 (HEAD `6f2aec0`).

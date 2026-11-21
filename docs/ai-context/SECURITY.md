# Security

Findings from static review on 2026-11-21 (HEAD `6f2aec0`). Not runtime-tested (no PHP/DB available during discovery). Each item states its evidence. Severity: Critical / High / Medium / Low.

## Existing controls (verified, keep them)
- Secrets externalised: `config/env.php` reads `CAN_*` env vars or git-ignored `config/env-local.php`; app refuses to start if mandatory values are missing.
- CSRF enabled globally; cookies `HttpOnly`, `SameSite=Strict`, `Secure` in prod.
- Session hardening: strict mode, 30 min idle, 12 h absolute, role-qualified identity id (`admin:12`), auth key = HMAC(id|hash|auth_version), password change revokes sessions (`SessionSecurityBootstrap`).
- Security headers (nosniff, X-Frame-Options, Referrer-Policy, Permissions-Policy, HSTS in prod).
- Login rate limiting + Cloudflare Turnstile; banned / unverified checks.
- Trusted-proxy handling of Cloudflare headers (spoofing-resistant).
- Password hashing with `Yii::$app->security` (bcrypt).
- Hashed, expiring reset / confirmation tokens.
- `.htaccess` blocks source dirs, dotfiles, backups/logs; `web/uploads/.htaccess` denies script execution.
- Audit trail with redaction; CSV-injection sanitizer for exports.
- Query building via Yii parameter binding; no raw SQL string concatenation found.
- Newer modules (request changes, notifications, appointments, MRO applications, support) have explicit role + ownership + status guards.

## Findings (prioritised)

### S-1 Critical — Unauthenticated write access to two admin controllers
> **Status 2026-11-21: FIXED (not yet tested at runtime, not committed).** `CertificateTypesController`: AccessControl added — `get-certificate-type-id` stays public (GET only; used by `site/become-mro` and MRO forms), every other action admin-only. `TermsController`: `access` moved out of `verbs.actions`; all actions admin-only.
- `controllers/CertificateTypesController.php`: `behaviors()` has only `VerbFilter`, **no AccessControl**. Any visitor can list/create/update/delete certificate types — which drive MRO matching (BR-3).
- `controllers/TermsController.php`: the `access` config is **nested inside `verbs.actions`**, so AccessControl is never attached. Any visitor can edit/delete the Terms & Conditions.
- Fix: proper `AccessControl` with admin `matchCallback` (smallest change).

### S-2 High — Broken object-level authorization (IDOR) in legacy AO actions
> **Status 2026-11-21: FIXED in `RequestsController` (not yet tested at runtime, not committed).** Central helpers `findOwnedRequest / findOwnedApplication / findOwnedPoRecord / findOwnedReport / canViewRequest` added; every AO action (incl. dead routable ones `*1`, `view-old`) now checks `requests.ao_id` against the session AO; `requests/view` stays readable by owner AO, admin, and MROs who applied or to whom the request is offered. `create`/`update` also enforce aircraft ownership and prevent `ao_id` reassignment. **Follow-up (same day): also FIXED** — `AoMroDisputeController` (update/close/delete restricted to the party that opened the dispute; create recomputes ao_id/mro_id/po/created_by/status server-side; fetch-details limited to parties), AO/MRO notification-preference controllers (`findModel` scoped to session owner; owner re-imposed after `load()`), `MroRequestsController` (apply requires the request to be offered to the MRO; applyupdate limited to own quote; contact/recommend-mro require the MRO to be concerned; recommended MRO must be in the proposed list; mro_id/request_id re-imposed after `load()`), `requests/deny` now POST-only. MRO eligibility centralised in `components/MroRequestEligibility.php`.

`RequestsController` only requires `@` (any logged-in AO **or MRO**). `findModel()` has no owner check. Actions with **no ownership check** found:
`view-old` (raw numeric id), `check-applications`, `deny`, `view-po`, `download-po`, `view-po-document`, `delete`, `download` (CRS file), `submit-description`, `approve-quote`, `accept-crs` (raw id, no decode). Also `actionCreate` does not check the role or that `aircraft_id` belongs to the AO.
- Signed IDs (`UrlIdHelper`) are **not authorization**: MROs legitimately receive signed request IDs; and the signing secret is committed (S-3). Example impact: an MRO who can see a request can delete it or approve its quote/close it.
- Same pattern: `AoMroDisputeController::actionUpdate/Close/Delete` (no owner check), `AoNotificationsPreferencesController` & `MroNotificationsPreferencesController` `view/update/delete` (raw ids, no owner check), `MroRequestsController::actionRecommendMro` (raw id), MRO `apply` does not re-check eligibility/status.
- Fix: one `findOwnedRequest()` helper per controller (pattern already exists in `MroApplicationsController::findOwnedRequest`, `AoAppointmentsController::findOwnedAppointment`) + role check.

### S-3 High — Signing secret committed to Git
`config/params.php` contains a literal `urlIdSecret`. Anyone with repo access can forge signed IDs. Fix: move to `env.php` (`CAN_URL_ID_SECRET`) and rotate (rotation invalidates existing links/bookmarks — plan it).

### S-4 High — Unrestricted file type on several uploads, served from same origin
`Requests.attachment` (create/update) and PO upload (`RequestsController::actionLoadPo`, `saveAs('uploads/'.$filename)`) have **no extension/MIME whitelist**; files land in public `web/uploads/` with the client extension. `.htaccess` blocks PHP, but `.html`/`.svg`/`.js` would be served on the app origin → stored XSS / phishing. Other uploads (certificates, MRO quotes, change orders, support) do whitelist.
Also: all uploads in `web/uploads/` are **publicly downloadable without login** if the random filename is known (POs, CRS, insurance, certificates are business-confidential). `docs/deployment-godaddy.md` already notes this.
Fix: whitelist (pdf/png/jpg) + `checkExtensionByMimeType`; medium term move confidential files outside webroot and serve via authorised actions.

### S-5 Medium — Stored XSS / HTML injection
- `views/requests/approve-quote.php:29` echoes `$report->mro_quote` (MRO-controlled) unencoded to the AO.
- Email templates echo user data unencoded (e.g. `mail/ao_application_accepted.php` → `Description`, usernames; `mail/application_denied.php`).
- `views/aircrafts/update.php:103-108` injects model fields into JS strings (admin-controlled; low).
Fix: `Html::encode()` / `Json::htmlEncode()`.

### S-6 Medium — Mass assignment of `status`
> Status 2026-11-21: preference `ao_id`/`mro_id`, quote `mro_id`/`request_id`, dispute parties and request `ao_id` are now re-imposed server-side. `Requests.status` on create is **still open**.
`Requests` declares `status` in a validation rule (`in` range) → it is a safe attribute; `actionCreate` uses `$request->status ?: 'created'`, so an AO can POST any status. Same on `AoNotificationsPreferences`: `ao_id` is a safe attribute (`required`/`integer` rules), so a POST can re-assign a preference row to another AO **[V]** (likely same for MRO preferences).

### S-7 Medium — Workflow transitions without state guards
`MroApplicationsController::actionAcceptPo`, `actionStartWork`, AO `accept-crs` / `approve-quote` do not verify the current status (and the legacy ones not the actor) before transitioning.

### S-8 Low — Repository hygiene
- `config/error_log` is tracked in Git (contains server paths) though blocked from web.
- Stray file `bject {` (a `less` help dump) at repo root, tracked.
- `config/__autocomplete.php`, `requirements.php`, `Vagrantfile`, `docker-compose.yml`, `tests/` deployed to production root (blocked by `.htaccess` but unnecessary).
- `params.php` keeps placeholder `adminEmail`/`senderEmail` `example.com`.

### S-9 Medium — Username collisions across three tables
`User::findByUsername` resolves admin → MRO → AO. Registration checks uniqueness **only in its own table** (`SiteController.php` ~l.652 for AO, ~l.921 for MRO) **[V]**, so an MRO can register an existing AO's username and the AO can then no longer log in (MRO is resolved first). Severity raised to **Medium** (account denial).

### S-10 Low — Third-party script without SRI
`assets/AppAsset.php` loads SweetAlert2 from jsDelivr without pinning/integrity.

### S-11 Info — DB charset `utf8`
3-byte UTF-8; emojis/some characters fail. Consider `utf8mb4` migration.

## Rules for future changes
Preserve CSRF, `Html::encode`, `AccessControl` + ownership helpers, signed IDs (as obfuscation only), `ResilientMailer` semantics, audit redaction. Never log/print secrets. Never commit `config/env-local.php`.

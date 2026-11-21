# Architecture Decision Log

Format: ID · date · status · decision · rationale · consequences · evidence.
Entries D-001…D-010 are **reconstructed from existing code/comments** (not newly made decisions).

| ID | Date (approx.) | Status | Decision | Rationale (from code comments) | Consequences | Evidence |
|---|---|---|---|---|---|---|
| D-001 | pre-2026 | Active | Three separate profile tables (admin/ao/mro) with one `User` identity wrapper | Original design | No RBAC; role stored in session; cross-table username uniqueness needed | `models/User.php` |
| D-002 | 2026-10 | Active | Role-qualified identity IDs (`ao:12`) + HMAC auth key incl. `auth_version` | Prevent cross-role session restore; revoke sessions on password change | Old numeric sessions only restored if session role matches | `models/User.php`, `m261008_190000` |
| D-003 | 2026 | Active | Signed URL identifiers (`UrlIdHelper`) | Prevent enumeration of business IDs | Not an authorization mechanism; secret currently in Git | `components/UrlIdHelper.php` |
| D-004 | 2026-09 | Active | Operational priority separate from workflow status; UTC deadline computed server-side only | Avoid tampering; never silently extend MRO deadline | Overdue display-only | `models/Requests.php`, `m260910_190000` |
| D-005 | 2026-09 | Active | Change orders as a separate versioned entity, not request statuses | Keep operational status stable; contractual traceability | Legacy `update_request*` coexists | `m260923_120000`, `RequestChangesController` |
| D-006 | 2026-09 | Active | Global AR audit trail, denormalised, no FKs | Historical snapshot, never block business writes | Some technical tables excluded | `docs/AUDIT_TRAIL.md` |
| D-007 | 2026-09 | Active | `request_change_event` feed for AJAX list freshness | Avoid returning content; cheap polling | Table grows unbounded (no purge) | `RequestChangeBehavior`, `RequestSyncController` |
| D-008 | 2026 | Active | Resilient mailer: SMTP failures never break business actions | GoDaddy SMTP instability | Emails can be silently lost (logged only) | `components/ResilientMailer.php` |
| D-009 | 2026-10 | Active, disabled by flag | AOG web push via FCM with DB job queue + cron | Shared hosting, no workers | Requires cron & service account | `m261009_200000`, `docs/firebase-aog-notifications.md` |
| D-010 | 2026-09 | Active | Secrets via env vars / git-ignored `env-local.php`; prod default env | Remove secrets from Git | `urlIdSecret` still pending migration | `config/env.php` |
| D-011 | 2026-11-21 | Active | Persistent AI knowledge base in `docs/ai-context/` + `CLAUDE.md` | Avoid re-discovering the project each session | Must be updated with every change | this commit |
| D-012 | 2026-11-21 | Active | Per-record ownership enforced in controllers through `findOwned*` helpers; signed IDs treated as obfuscation only. `requests/view` readable by owner AO, admin, MROs who applied or to whom the request is offered (criteria duplicated from `MroRequestsController::actionIndex`) | Fix IDOR S-2 with minimal change, no RBAC refactor | Eligibility logic now duplicated in 3 places (TD-5) — to centralise | `controllers/RequestsController.php` |
| D-013 | 2026-11-21 | Active | MRO lists show the connected MRO's own quote state (`New` / `Quote sent`), not the global request status; competitors' activity is hidden | Global `answered` was ambiguous and revealed competitor activity | DB statuses unchanged; same `apply` action creates or updates the quote | `views/mro-requests/_request-list.php`, `MroRequestsController::actionIndex` |

# Workflows

## W-1 Main maintenance request lifecycle (`requests.status`) **[V]**
```
            AO creates                 first MRO quote            AO uploads PO
 (none) ─────────────▶ created ─────────────────▶ answered ─────────────────▶ po_loaded
                         ▲   (all quotes cancelled/denied)   │                     │ MRO accepts PO
                         └───────────────────────────────────┘                     ▼
                                                                             work_accepted
                                                                                   │ MRO "Start work"
                                                                                   ▼
            AO accepts CRS / approves quote                MRO submits CRS   work_started
 closed ◀─────────────────────────────── report_submitted ◀───────────────────────┘
   │
   └─ AO feedback (feedback table)
```
Side-flows:
- **Appointments** between `work_accepted` and `work_started` (W-3).
- **Change orders** while `work_accepted`/`work_started` (W-2) — do not change `requests.status`.
- **Delete**: AO hard-deletes the request; MRO gets an in-app notification.
- Display-only status derivation: MRO lists show `po_loaded` when status is `answered` and a PO exists, and `report_submitted` when a report exists (`MroApplicationsController::actionIndex`).

Key code: `RequestsController` (AO side), `MroRequestsController` (MRO discovery & quoting), `MroApplicationsController` (MRO execution), `AoAppointmentsController`, `MroAppointmentsController`.

## W-2 Change order (avenant) **[V]** `RequestChangesController`
```
AO create ─▶ pending_mro ─MRO reject─▶ rejected (end)
                 │ MRO accept
                 ▼
         awaiting_updated_po ─AO upload PO─▶ pending_po_review ─MRO reject─▶ po_rejected ─AO re-upload─┐
                                                   │ MRO accept                                          │
                                                   ▼                                                     │
                                       awaiting_updated_quote ◀──────────────────────────────────────────┘(loop to pending_po_review)
                                                   │ MRO submit quote
                                                   ▼
                                       pending_quote_review ─AO reject─▶ quote_rejected ─MRO resubmit─▶ pending_quote_review
                                                   │ AO accept
                                                   ▼
                                               completed
```

## W-3 Appointment **[V]**
MRO `set-appointment` → `waiting response` → AO `confirm` (`confirmed`) | `cancel` (`canceled`) | `reschedule` (`reschedule`, `reschedule_appointment_date`) → MRO `accept-reschedule` or `remove-appointment`. Reminder template `mail/appointment_reminder.php` exists — **NEEDS VERIFICATION** whether any job sends it (no cron found).

## W-4 AOG push **[V]** `docs/firebase-aog-notifications.md`
MRO browser registers (`PushController::actionRegister`, `web/js/aog-push.js`, service worker `push/worker`) → `push_subscription`. On AOG request save, `AogPushBehavior` → `AogPushService` upserts `aog_push_job` per eligible subscription → cron `php yii aog-push/send 20` claims jobs (file lock), sends FCM, marks sent/failed/discarded. Disabled unless `CAN_FIREBASE_PUSH_ENABLED=1` and service account configured.

## W-5 Registration & login
Public: `site/become-ao`, `site/become-mro` (blocked → early-access redirect when prelaunch mode on) → verification email → `verify-email`/`verify-emailao` → login (`site/login`, Turnstile + rate limit) → role from profile table → session `user_type` + `<role>_id`.
Password reset: `site/request-password-reset` → hashed token (`PasswordResetTokenService`) → `site/reset-password/<token>`. Username reminder: `site/request-username-reset`.

## W-6 Early access
`POST early-access/join` → `prelaunch_subscriber` (`PENDING_CONFIRMATION`, hashed tokens, consent version) → confirm link → `CONFIRMED`; unsubscribe link; admin list/view/export CSV/resend.

## W-7 Support ticket
`SupportController::actionCreate` (POST, public widget `views/layouts/_support-widget.php`) → ticket + acknowledgement email via `actionDispatch` (token-hash idempotency) → admin assign/status/reply (`AdminSupportTicketsController`) with history.

## W-8 Dispute
AO/MRO `dispute/create` (request selector, auto-fill details via `fetch-details`) → admin `admin-disputes/reply` (email to both) → `close` → `resolved`. Admin can ban/unban parties from dispute screen.

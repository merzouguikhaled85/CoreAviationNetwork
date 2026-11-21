# Business Rules (as implemented)

Source code = evidence of **current behavior**, not necessarily the intended specification. Ambiguities are listed in FEATURES.md → "Open questions".

## BR-1 Operational priority **[V]** `models/Requests.php`
- Every new request must choose `aog`, `urgent` or `routine` (`operational_priority` required). Legacy rows with empty value are normalised to `routine` on update.
- `response_required_minutes`: integer 15–1440; **required only for AOG** (server rule), optional for Urgent, forced to `NULL` for Routine.
- `response_due_at_utc = gmdate(now + minutes)` computed in `beforeSave()`, **only** on insert or when priority/minutes change. Editing the description never extends the deadline. Not mass-assignable.
- Every priority/deadline change writes `request_priority_history` (actor from session only).
- Sorting everywhere: AOG → Urgent → Routine, then newest (`getOperationalPriorityOrderExpression`).
- The response countdown (remaining/overdue) is displayed in AO lists only while the request awaits MRO quotes (`created`/`answered`).
- **Overdue** is display-only: `response_due_at_utc < now` in views (`views/requests/_request-list.php`, `views/mro-requests/_request-list.php`, `views/dashboard/home.php`). No persisted flag, no escalation job.
- "Response time" (actual time to first MRO answer) is **not calculated** anywhere **[V]**.

## BR-2 Request creation **[V]** `RequestsController::actionCreate`
- AO id taken from session. Required: details, aircraft, registration, serial number, destination airport, ETA, ETD, priority.
- `required_certificates` is copied from the aircraft's `certificate_type.type` (string).
- `location` is copied from the destination airport name.
- ETD must be strictly after ETA. Stored as server-local `Y-m-d H:i:s`.
- Initial status `created`.

## BR-3 MRO eligibility / distribution **[V]**
An MRO **sees** a request in "New requests" (`MroRequestsController::actionIndex`) when ALL hold:
1. request status ∈ {`created`, `answered`};
2. MRO has a `certificates.type` equal to `requests.required_certificates`;
3. MRO serves the destination airport (`mroprofile_airport`);
4. MRO holds a `mro_aircraft_certificate` for the aircraft's `aircraft_model_id`;
5. no PO exists yet, **or** the PO was issued to this MRO.

On creation, notification (`RequestsController::actionCreate`) iterates **all MROs** and:
- "covered" = airport + certificate + aircraft model + **at least one insurance document** → email `notification_certified` / in-app, if `notify_for_certified_aircraft`.
- airport matches but not fully covered → `notification_non_certified` with reason, if `notify_for_non_certified_aircraft`.
- ⚠ Insurance is required for notification but **not** for visibility (rule mismatch).
- AOG requests additionally queue web-push jobs for eligible MRO subscriptions (`components/AogPushService.php`).

## BR-4 Quotation (MRO answer) **[V]** `MroRequestsController::actionApply/Applyupdate`
- Stored in `mro_request_apply` (Description, price, currency, attachment png/jpg/pdf). Attachment mandatory on first answer.
- First answer sets request status `answered`.
- MRO can cancel its answer (`MroApplicationsController::actionCancelApplication`); if no answers remain the request returns to `created`.
- AO can deny an answer (`RequestsController::actionDeny`) → email `application_denied`; status may return to `created`.

## BR-5 Purchase Order **[V]** `RequestsController::actionLoadPo`
- AO uploads a PO for a chosen answer → row in `ao_requests_applications` (`application_id`, `po`) → status `po_loaded`.
- The selected MRO is the application referenced by the **latest** PO row (`Requests::getSelectedMroApplication`).
- MRO accepts PO (`MroApplicationsController::actionAcceptPo`) → status `work_accepted`, email/in-app to AO per prefs.
- ⚠ No server-side guard that status is `po_loaded` before accept, nor `work_accepted` before start work.

## BR-6 Execution **[V]** `MroApplicationsController`
- `actionSetAppointment`: MRO proposes date → `appointment.status = 'waiting response'`. AO confirms/cancels/reschedules (`AoAppointmentsController`); MRO accepts reschedule or removes (`MroAppointmentsController`).
- `actionStartWork` → status `work_started` (email `work_started`).
- `actionSubmitReport` → `repair_report` (CRS + attachment) → status `report_submitted`.
- `actionProvideNewQuote`: MRO adds `mro_quote` + attachment on a report (once).

## BR-7 Closure **[V]** `RequestsController`
- AO accepts CRS (`actionAcceptCrs`) or approves quote (`actionApproveQuote`, `quote_approved = 1`) → status `closed`. Rejecting returns status to `answered` (approve-quote "no" path) — **NEEDS VERIFICATION** of intent.
- AO feedback (`actionProvideFeedback`): 4 ratings + text into `feedback`.
- `cancelled` exists in the enum but no action sets it **[V]** (delete is a hard delete instead).

## BR-8 Change orders / amendments ("avenants") **[V]** `RequestChangesController`, `models/RequestChange.php`
- Allowed only for the owning AO when request status ∈ {`work_accepted`, `work_started`}.
- Strict state machine with role + status guards:
  `pending_mro` → (MRO) `rejected` | `awaiting_updated_po` → (AO uploads PO) `pending_po_review` → (MRO) `po_rejected`↺ | `awaiting_updated_quote` → (MRO quote) `pending_quote_review` → (AO) `quote_rejected`↺ | `completed`.
- Versioned (`version`), documents kept in `request_change_document` (pdf/png/jpg ≤10 MB), stored under `web/uploads/request-changes/`.
- Legacy `update_request*` statuses remain in views/handlers but `RequestsController::actionUpdateRequest` now redirects to the change-order flow.

## BR-9 Accounts
- Login by username across 3 tables; banned users refused; non-admin must have `email_verified = 1` (`SiteController::actionLogin`).
- Login rate limit (cache-based, 429) + Cloudflare Turnstile when configured.
- Password change bumps `auth_version` → all sessions revoked.
- Admin can ban/unban AO/MRO (`AdminDisputesController`, `AwaitingRequestResponseController`) and toggle status (`*ProfileController::actionToggleStatus`).

## BR-10 Notifications
- Channels: email, in-app (`notifications`), web push (AOG only, MRO only).
- Each send is gated by the recipient's preference row; **no preference row ⇒ no notification** for most events.
- Mail failure never rolls back business action (`ResilientMailer`).

## BR-11 Disputes / support / feedback
- AO or MRO opens a dispute on a request; admin replies (`admin_response`); status open → resolved.
- Support tickets with admin assignment, history, email replies with idempotent dispatch tokens.

# Changelog (knowledge base & notable project changes)

## 2026-11-21 — « A4 page » text boxes for request details and MRO proposals
- New shared class `textarea.a4-text-box` in `web/css/form-system.css` (height min(600px, 70vh), min 420px, vertical scroll beyond, still resizable; mobile 60vh).
- Applied to: `views/requests/create.php` and `update.php` (Request Details), `views/mro-requests/apply.php` and `applyupdate.php` (MRO technical proposal / Description).
- DB check: `requests.request_details` and `mro_request_apply.Description` are TEXT (~65k chars) — an A4 page fits without truncation.

## 2026-11-21 — Conversation: WhatsApp-like theme in CAN colours (no green)
- `views/conversations/view.php`: final CSS override block (no HTML/JS behaviour change): compact navy header, light context bar, plain wallpaper with faint dots, white received / light-blue sent bubbles with tails on group start, floating time + tick, sticky day pill, flat composer with icon-only paperclip and round navy send button, mobile full-bleed.
- Fixed « centred » message text: `.msg-text` used `white-space: pre-wrap`, which rendered the PHP template indentation; now `normal` (nl2br keeps real line breaks).
- Removed green: fake « online » dot hidden, sync dot and status dot in sky blue.

## 2026-11-21 — Local DB: attachment migration applied
- Local database `globalmro` (phpMyAdmin): 4 nullable columns added to `conversations` and `m261121_170000_add_attachment_to_conversations` recorded in `migration` (equivalent to `php yii migrate` for this migration only). Verified via table structure.
- Noted: `m261009_200000_create_aog_push_tables` is NOT applied on the local DB (left untouched).
- Production: still needs `php yii migrate` during deployment (the deploy script runs it).

## 2026-11-21 — Conversation: request context, attachments, accessibility
- **Migration** `m261121_170000_add_attachment_to_conversations`: nullable `attachment_path`, `attachment_name`, `attachment_mime`, `attachment_size` on `conversations` (idempotent, reversible). **Must be run**: `php yii migrate`.
- **Storage**: files in `@runtime/chat-attachments` (not public, blocked by .htaccess, git-ignored), random names; served only by `ConversationsController::actionAttachment` (signed message id + `findAuthorizedChat`). Same pattern as support attachments.
- `models/Conversations.php`: scenario `reply` — file rule (pdf/png/jpg, real MIME check, 10 MB) + « text or file » rule; helpers `hasAttachment()` (guarded by `hasAttribute`, safe before migration), `isImageAttachment()`, `getAttachmentAbsolutePath()`, `getAttachmentSizeLabel()`.
- `ConversationsController::actionReply`: stores the attachment (`storeAttachment()`), clear error if migration missing; JSON error returns the first validation message.
- `views/conversations/view.php`: context bar (request link, registration · model, priority badge, status — MRO sees « Open for quotes » for created/answered), paperclip button + file preview/remove, client checks (type/size), AJAX send with FormData, aria-live announcement of new incoming messages only, labelled textarea/buttons, keyboard-focusable message region, visible focus.
- `views/conversations/_message_list.php`: attachment rendering (image thumbnail + download chip), screen-reader sender prefix, `<time datetime>`, day separators with role/label.
- `views/conversations/index.php`: preview « Attachment: name » for file-only messages.
- Tests: PHP lint; JS syntax (Node); jsdom scenarios (wrong type, >10 MB, file-only send via FormData with CSRF, preview reset, live announcement, no self-announcement, text send regression). Not tested against a real DB/browser. Not committed.
- Known limit: if the DB save fails after the file was stored, the file stays orphaned in runtime/chat-attachments.

## 2026-11-21 — Conversation: Enter to send, AJAX submit
- `ConversationsController::actionReply`: returns JSON (`success`, or 422 + `message`/`errors`) when the request is AJAX; classic POST keeps the redirect (no-JS fallback).
- `views/conversations/view.php`: Enter sends, Shift+Enter = new line (IME-safe); send via ActiveForm `beforeSubmit` (direct AJAX if yiiActiveForm absent); textarea cleared, list refreshed and scrolled immediately; double-send guard; errors via Toastr, text kept on failure.
- JS syntax checked with Node; behaviour tested in jsdom (Shift+Enter, Enter, empty field, server failure). Not tested in a real browser; not committed.
- Noted (not changed): `ConversationsController::behaviors()` AccessControl uses `'only' => ['home']`, so other actions rely solely on `findAuthorizedChat()` — to review.

## 2026-11-21 — Conversation view: low-risk fixes
- `views/conversations/_message_list.php`: « mine / received » now decided by `sender_type` + `sender_id` vs session role/id (was: username only, ambiguous across AO/MRO tables); fallback to username for rows without sender_type. Single ✓ « Sent » instead of fake ✓✓ read receipt.
- `views/conversations/view.php`: duplicate info removed (role and auto-refresh repeated in info bar / help text); error banner removed (Toastr shows it; it was unescaped); polling paused while the tab is hidden, immediate refresh when visible again.
- Not done (larger changes): Enter-to-send + AJAX send, request context header (registration/AOG/status), attachments, aria-live, global-loader hack cleanup.

## 2026-11-21 — MRO Quotes tab: view button disabled once a quote is selected
- `views/requests/_mro-quotes.php`: when a quote is selected (PO issued), the « view » action is rendered greyed and non-clickable (`.quote-action.is-disabled`, icon `bi-eye-slash`) on every row. UI only: `requests/view-application` itself is unchanged.

## 2026-11-21 — AO request lists: response countdown only while awaiting quotes
- `views/requests/_request-list.php`: « remaining / overdue » shown only for statuses `created` / `answered` (was: hidden only for closed/cancelled). Hidden in Open requests, for `po_loaded` and after closure; AOG/Urgent badge kept.

## 2026-11-21 — requests/view: « MRO Quotes » tab for the owner AO
- `RequestsController::actionView` passes `mroQuotes` (all `mro_request_apply` rows of the request) **only to the owner AO**; `null` for MRO/admin so competitors' quotes are never exposed.
- New partial `views/requests/_mro-quotes.php` (scoped CSS `.mro-quotes-panel`): MRO, description, price, currency (one query), attachment, actions view / accept (load-po) / contact / deny (POST) / MRO profile; accept & deny only while status is `created`/`answered`; selected quote (latest PO) highlighted.
- `views/requests/view.php`: new tab `MRO Quotes (n)` + panel `#request-quotes`, tab JS extended (`#quotes` hash supported).
- `requests/check-applications` unchanged (table markup now duplicated in two places — candidate for reuse of the partial).
- PHP lint OK; no runtime test; not committed.

## 2026-11-21 — MRO « New requests »: per-MRO quote state
- `MroRequestsController::actionIndex` passes `appliedRequestIds` (one query for the page: requests this MRO already quoted).
- `views/mro-requests/_request-list.php`: Status column shows the connected MRO's state — `New` (not yet quoted) / `Quote sent`; action button `Apply` (blue ✓) or `Update quote` (amber pencil). The global request status (`answered` may come from a competitor) is no longer shown to MROs.
- `views/mro-requests/index.php`: passes `appliedRequestIds`, styles `.btn-update-quote`, `.status-quote_sent`.
- `views/mro-requests/apply.php`: title `Update MRO Quotation`, status pill, button `Update Quote` and loading texts when a quote already exists.
- Decision: competitor activity hidden from MROs (see DECISIONS D-013). PHP lint OK; no runtime test; not committed.

## 2026-11-21 — UI: flash banner removed on MRO « New requests »
- `views/mro-requests/index.php`: Bootstrap flash banners removed; flashes are displayed only by Toastr in `views/layouts/main.php` (they were shown twice). 71 other views still render their own flash banners.

## 2026-11-21 — MroAircraftCertificatesController fixes
- Upload validated before writing to disk: PDF/DOC/DOCX only, max 10 MB (`MAX_CERTIFICATE_BYTES`, `storeCertificateFile()`), error shown as flash + field error.
- `mro_id` re-imposed after `load()` in create/update (mass-assignment IDOR).
- Update no longer wipes the existing certificate path when no new file is chosen (hidden empty `certificate` field from `fileInput()`).
- Context: a 56 MB upload exceeded `post_max_size` (12 MB) → PHP drops the body → CSRF 400 + yii2-debug memory exhaustion. The 10 MB limit does not help when the body exceeds post_max_size: global guard `OversizedPostGuard` prepared but NOT applied (awaiting approval).
- PHP lint OK; no runtime test; not committed.

## 2026-11-21 — Security fix S-1 (critical)
- `controllers/CertificateTypesController.php`: added AccessControl; public GET `get-certificate-type-id` (registration/MRO AJAX), all other actions admin-only.
- `controllers/TermsController.php`: AccessControl moved out of the VerbFilter config where it was ignored; admin-only.
- PHP lint OK; no runtime test; not committed to git.

## 2026-11-21 — Security fix S-2 follow-up (disputes, preferences, MRO quoting)
- New `components/MroRequestEligibility.php` (`isOfferedTo`, `hasApplied`, `isConcerned`): single implementation of the MRO « New requests » criteria for one request. `RequestsController::canViewRequest` now delegates to it (private duplicate removed).
- `controllers/AoMroDisputeController.php`: `isRequestAccessibleToActor`, `findOwnedDispute`; update/close/delete only by the creating party; update only changes `description`; create recomputes parties/PO/status server-side; fetch-details limited to parties.
- `controllers/AoNotificationsPreferencesController.php`, `controllers/MroNotificationsPreferencesController.php`: `currentOwnerId`, `loadForCurrentOwner`, owner-scoped `findModel`.
- `controllers/MroRequestsController.php`: `currentMroId`, `findRequestForCurrentMro`; apply/applyupdate/contact/recommend-mro guarded; mass-assignment of `mro_id`/`request_id` blocked; recommended MRO validated.
- `controllers/RequestsController.php`: `deny` restricted to POST.
- Behaviour change: an MRO can no longer edit its quote through `mro-requests/apply` once a PO exists or the request is no longer `created`/`answered`.
- PHP lint OK; no runtime test; not committed to git.

## 2026-11-21 — Security fix S-2 (IDOR) in RequestsController
- `controllers/RequestsController.php`: added ownership helpers (`currentAoId`, `requireAoId`, `findOwnedRequest`, `findOwnedApplication`, `findOwnedPoRecord`, `findOwnedReport`, `canViewRequest`, `isRequestOfferedToMro`) with French comments.
- Protected actions: view-old, view (shared read rule), create1, create (+aircraft ownership), update (+aircraft ownership, no ao_id takeover), update1, check-applications, deny, load-po, load-po1, view-po, download-po, view-po-document, delete, view-reports1, view-reports (+posted report must belong to the request), download, submit-description, accept-crs, approve-quote, provide-feedback, contact.
- Unauthorized access now returns 403 (other owner/role) or 404 (missing record) instead of acting.
- PHP lint OK; no runtime test (no DB available). Not committed to git.
- Docs updated: SECURITY.md (S-2 status), DECISIONS.md (D-012).

## 2026-11-21 — Discovery baseline
- Created `docs/ai-context/` (13 documents) and root `CLAUDE.md` from a full static discovery of HEAD `6f2aec0`.
- No application code modified.
- Noted uncommitted work in progress: `controllers/DashboardController.php`, `views/dashboard/home.php`, `components/MroRequestCounters.php`.

## Project history summary (from git log & migrations, before this baseline)
- 2026-11: prelaunch/early-access UX, GoDaddy/Outlook mail fixes, admin password change, request list fixes.
- 2026-10: session `auth_version`, AOG Firebase push.
- 2026-09: request-change infrastructure & workflow, operational priority (AOG/Urgent/Routine), support tickets, audit log, prelaunch subscribers, CI/CD to GoDaddy.
- 2024-05: `terms` table (oldest migration).

<!-- Add new entries at the top: date, summary, files touched, docs updated. -->

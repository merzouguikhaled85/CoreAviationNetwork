# Changelog (knowledge base & notable project changes)

## 2026-11-22 — Fix: provide-feedback crash when the MRO profile no longer exists
- `RepairReport::getMro()` is null-safe (missing application or MRO profile → null instead of a fatal error).
- `RequestsController::actionProvideFeedback`: `mro_id` taken from `mro_request_apply` (still valid if the MRO profile was deleted); ratings checked (1–5) before saving; `save()` result checked and logged; platform notification before e-mail, with `requestId`; mail wrapped in try/catch; notifications skipped when the MRO profile is gone.

## 2026-11-22 — Global loader redesign (`views/layouts/can-spinner.php`)
- No more full-screen splash on every page: a 3 px top progress bar (`#can-nav-progress`) starts on same-origin navigation; the compact overlay (CAN logo + ring + « Loading… ») only appears after 700 ms.
- Form submit: spinner inside the submit button (label from `data-loading-text`, default « Submitting… »), button disabled after the form data is built; starts only if the submit was not prevented (native submit + Yii ActiveForm `beforeSubmit`).
- Ignored: downloads (`download` attr, file extensions, download/export/attachment/uploads URLs), `target="_blank"`, mailto/tel/javascript, anchors, modifier-clicks, `data-no-loader`. Safety: everything resets after 12 s and on back/forward cache.
- Kept contracts: `#av-global-loader` + `.hidden` (Support widget, view-reports), `window.AV_DISABLE_GLOBAL_LOADER` (conversation view). New API `window.CANLoader.show()/hide()`.
- Accessibility: `role="status"`, `aria-live`, `aria-busy`; `prefers-reduced-motion` slows the ring.
- Brand text fixed (was « Aircraft Services Network »).
- No double loader: when a page already shows its own full-screen fixed loader (e.g. « Submitting your quote… » on `mro-requests/apply`), the CAN logo card replaces it immediately (page loader kept but made transparent via `.can-own-loader-replaced`) and shows the page's message; restored on hide. Submit buttons already handled by the page (disabled or with their own spinner) are left untouched.

## 2026-11-22 — Platform time zone forced to UTC (independent of the server)
- `config/web.php`, `config/console.php`: `'timeZone' => 'UTC'` + `formatter` (`timeZone`/`defaultTimeZone` = UTC).
- `config/db.php`: `on afterOpen` → `SET time_zone = '+00:00'` (NOW(), CURRENT_TIMESTAMP and TIMESTAMP reads in UTC).
- Labels: ETA/ETD (create/update views, `Requests`) and `Appointment::appointment_date` show « (UTC) ».
- Migration `m261122_200000_convert_datetimes_to_utc`: measures the former MySQL server offset and the php.ini offset on the server where it runs, shifts existing DATETIME/TIMESTAMP values accordingly (skips `*_utc` columns and `audit_log.created_at`; preserves ON UPDATE columns), logs every shift in `timezone_conversion_log`; `down()` reverts exactly. No-op when both offsets are 0.

## 2026-11-22 — AO signup: same 4-step onboarding as the MRO signup
- `views/site/become-ao.php` rewritten on the `become-mro.php` model (layout `auth`, no header/footer, same CSS/JS building blocks):
  - left navy panel: stepper (Account / Operator & contact / Aircraft / Review & submit) and a « Your aircraft » card (type, registration, serial, certificate) that fills in live;
  - step headers with icon tile, « Step n of 4 » eyebrow and title; icons in every label; closable error alert; submit button with `ri-send-plane-line`;
  - client validation per step; full re-check on submit (the first invalid step is reopened);
  - manufacturer → model list filtered client-side, `aircraft_model_id` resolved; country → cities fetch (`/site/get-cities?countryId=`);
  - review step with Edit links; terms required.
- `SiteController::actionBecomeAo` rewritten:
  - errors collected in `formErrors`, re-render on the first step to fix (`errorStep`), entries kept;
  - profile photo kept between submissions (`@runtime/ao-signup`, `kept_files[profile_photo]`);
  - profile + aircraft saved in one transaction, photo copied only after validation (removed on rollback);
  - default notification preferences via `AoNotificationsPreferences::ensureDefaultsFor`;
  - success flash, redirect to the site root; verification mail via `sendAoVerificationEmail()`;
  - kept-file helpers made generic (`$scope = 'mro' | 'ao'`).
- Checked in the built-in browser (1440 px and mobile): steps, aircraft card, review, server error (invalid model) re-rendered on step 3 with entries and photo kept; nothing saved.

## 2026-11-22 — MRO signup: standalone onboarding screen (no site header/footer)
- `SiteController::actionBecomeMro`: `$this->layout = 'auth'` (existing layout without navbar/footer; keeps Bootstrap, jQuery, Toastr, support bubble, spinner).
- `views/site/become-mro.php`: new presentation only (same fields, ids, names, JS behaviour and server flow):
  - left navy panel (360 px, sticky): CAN logo, vertical stepper with completed/active states, « Request matching » tracker (4 criteria, progress), « Already a partner? Sign in »;
  - right: single 640 px column, step eyebrow + title + lead, sections separated by thin dividers (no stacked cards), 44 px inputs with 8 px radius and focus ring, document rows with Ready/Missing status pill, review as a definition list with Edit, sticky action bar (Back / Continue);
  - top bar: « Step x of 4 » + progress, « Need help? » (mailto support);
  - mobile (< 992 px): side panel hidden, sticky top bar with logo and progress; single column below 576 px;
  - Inter font (Google Fonts, system-ui fallback); Bootstrap « valid » green ticks removed; overrides for `spur.css` absolute checkbox and Select2 sizing.
  - Remix icon before every field label (18: email, username, passwords, company, country, city, address, postal code, names, phone, photos, authority, manufacturer, model, airport), grey `.ob-label-icon`, `aria-hidden`.
  - Server error alert: close button (`#mro-alert-close`, aria-label « Close this message ») removes the alert; « Need help? » top-bar button removed (the site support bubble remains).
  - Submit button: send icon (`ri-send-plane-line`) before « Submit application »; step-4 match message now lists the missing items (« Still missing: base airport, … ») instead of pointing to the side panel (hidden on mobile).
  - Step headers: 48 px icon tile per step (user-add, building, shield-check, flight-takeoff), uppercase « STEP n OF 4 » eyebrow, 28 px bold title, divider under the header; on desktop the top bar shows only the progress bar (count kept for screen readers and on mobile).
- Verified in the app at 1440 × 900 and 375 × 812 (steps 1–4 filled with sample data, no submit). Not committed.

## 2026-11-22 — MRO signup: attachments kept after a server error
- Problem: after a server-side error the form came back with the entries but empty file fields (browsers cannot refill a file input).
- `SiteController::actionBecomeMro`: every valid file received (PDF ≤ 5 MB for the 3 documents, image ≤ 5 MB for photos) is copied to `@runtime/mro-signup/<session token>/` (not web-accessible, 0700), tracked in session key `mroSignupKeptFiles`. The re-rendered form shows it as kept; `kept_files[slot]` (1 = keep, 0 = removed/replaced) comes back with the next submit. On success the files are copied to `uploads/` (copy, so a failed transaction can be retried), then the temporary copies are deleted. A new visit (GET) discards them; folders older than 24 h are cleaned. New private helpers: `checkMroSignupFile`, `keepMroSignupFile`, `deleteMroSignupFile` (only under `@runtime/mro-signup`), `discardMroSignupFiles`.
- `views/site/become-mro.php`: hidden `kept_files[...]` per file zone, kept file shown as « name · size · kept from your previous submission » with Remove; step validation and eligibility count kept files; alert text updated.
- Verified in the app: submit with mismatched passwords → refused, nothing saved, 3 PDFs kept (eligibility 4/4), step 3 passes without re-selecting, Remove → Missing and blocked; new visit → temporary folder emptied; no file written to `uploads/`. Not committed.

## 2026-11-22 — MRO signup redesigned (4 steps) + server fixes
- Design validated on a canvas mockup (« CAN — MRO Signup redesign »).
- `views/site/become-mro.php` rewritten: 1 Account (professional email first, password show/hide, live rules) · 2 Company & base + Accountable contact · 3 Approvals & capability (one card per document — NAA approval, aircraft capability, liability insurance — with Missing/Ready chip) · 4 Coverage & review (airport, « You will receive requests for <aircraft> at <airport> », review with Edit links, terms). Side panel « Your eligibility » tracks the 4 matching criteria (airport, aircraft, NAA, insurance). Same fields, names and ids; same AJAX endpoints (cities, models, certificate type id, airport search). Previously visited steps are clickable. Server errors listed at the top; dependent lists (city, aircraft model, airport) re-filled server-side.
- `SiteController::actionBecomeMro` rewritten (same models, same `uploads/` storage, same verification e-mail, flashes and redirect):
  - everything validated **before** saving (before: the profile was saved first, so a failing certificate/airport left an incomplete MRO account);
  - profile, insurance, aircraft capability, NAA certificate and airport saved in one **transaction**; on failure: rollback + uploaded files deleted;
  - errors no longer `refresh()` the page (all input was lost): the form is re-rendered with the entries, opened on the first step to fix (`formErrors`, `errorStep` passed to the view); selected files must be chosen again (browser limitation, stated in the alert);
  - `VarDumper::dump(); die();` on aircraft-certificate validation removed;
  - server checks: PDF + 5 MB for the 3 documents, images for photos, authority, aircraft model and airport existence, terms;
  - verification e-mail moved to `sendMroVerificationEmail()` (HTML unchanged).
  - after a successful signup the MRO is redirected to the site root (`baseUrl . '/'`, i.e. http://localhost:8080/) instead of `site/landing` (AO signup unchanged).
- Verified in the app (built-in browser): step validations, fake PDFs → chips Ready, eligibility 4/4, review, terms gate; server submit without documents → refused, nothing saved, step 3 reopened with all entries kept. A real successful signup was not submitted. Not committed.

## 2026-11-22 — Notification preferences created at sign-up (all on)
- Context: the 4 sign-up paths (`SiteController` public AO/MRO sign-up, `AoProfileController`, `MroProfileController`) already created preferences inline (MRO with `notify_for_non_certified_aircraft = 0`); any other creation path, and older accounts, had none → no request notification. With `afterSave` those inline blocks would have created duplicates: they are replaced by `ensureDefaultsFor()` (finds the row created by `afterSave`). New MROs now also get « outside coverage » notifications (all on).
- `models/AoNotificationsPreferences.php`, `MroNotificationsPreferences.php`: `DEFAULTS` constant (everything on) + `ensureDefaultsFor($id)` (creates only if missing, never changes existing preferences, logs on failure).
- `models/AoProfile.php`, `MroProfile.php`: `afterSave($insert)` → `ensureDefaultsFor()` on profile creation — covers every sign-up path (public form, admin, etc.).
- `AoNotificationsPreferencesController`, `MroNotificationsPreferencesController::actionIndex`: use `ensureDefaultsFor()` (same defaults, one source); the `VarDumper::dump(); die();` on save error is replaced by a 500 error.
- **Migration** `m261122_190000_create_default_notification_preferences`: inserts « all on » rows only for existing AO/MRO profiles with no preferences (prints the counts; replayable; `safeDown()` removes nothing). **Must be run**: `php yii migrate`. Effect: those accounts start receiving request notifications.
- PHP lint OK (10 files); AO preferences page checked in the app (8/8 switches on, no error). Sign-up flow and migration not run; not committed.

## 2026-11-22 — Notification preferences: readable switches, all on by default
- Bug: `web/css/form-system.css` rule `.can-form-page input[type="checkbox"]` (20 × 20 px) overrode the page switches → each switch rendered as a plain circle, on and off looked the same.
- `views/ao-notifications-preferences/update.php`, `views/mro-notifications-preferences/update.php`: switch styles (track 2.75em × 1.5em with `!important` on width/height/flex — the form-system rule has three `:not()` and wins on specificity; white knob, grey/left = off, blue/right = on, focus outline). `form-system.css` unchanged.
- `MroNotificationsPreferencesController::actionIndex` (first visit creates the record): defaults now also `notify_for_certified_aircraft = 1` (was not set) and `notify_for_non_certified_aircraft = 1` (was 0). AO defaults were already all 1. Existing records are not changed.
- Noted, not changed: the MRO form has no switch for `notify_for_certified_aircraft`; a profile that never opened its preferences page has no record, so no request notifications are sent to it.
- Verified in the app (AO page, built-in browser): all 8 preferences checked, switches 44 × 24 px, blue/right. PHP lint OK; not committed.

## 2026-11-22 — « Work Started » e-mail on the shared template
- `mail/work_started.php`: shared frame + request card; selected MRO (latest PO), start date, agreed amount, next step (report + CRS); CTA to the request. Removed « Your Company Name » and the AO `first_name`-only greeting.
- `MroApplicationsController::actionStartWork`: subject `RequestMail::subject('Work started', $request)` (was the same « Work Started » for every request, so Gmail grouped them in one thread).
- PHP lint OK; rendered with sample data; not sent through SMTP; not committed.

## 2026-11-22 — AO request lists: real status + CRS step in the status badge
- `views/requests/_request-list.php`: for `work_started`, `report_submitted`, `closed`, `canceled` the badge showed only « Waiting for CRS » / « View CRS » (a closed request looked like « View CRS », a canceled one like « Waiting for CRS »). It now shows the real status (same labels as the status toasts: Work Started, Report Submitted, Closed, Canceled) with the CRS step on a second line (Waiting for CRS / View CRS; none for canceled, or closed without report). Still links to `view-reports`.
- Performance: one `RepairReport` query for the whole page instead of one per row.
- `web/css/list-system.css`: `.status-badge.has-next-step`, `.status-main`, `.status-next-step`.
- PHP lint OK; badge logic and rendering checked with sample data; not tested in a browser; not committed.

## 2026-11-22 — Request e-mails redesigned on the password-reset template, with full request data
- New shared template (inline styles, same look as `mail/password-reset-html.php`): `mail/partials/_frame.php` (blue header, eyebrow + priority badge, greeting, intro, CTA button + fallback link, note, footer, hidden preheader), `mail/partials/_request-card.php` (Request #, operator, priority, aircraft, registration · MSN, maintenance location, ETA/ETD local time, required certificates, response due, request text), `mail/partials/_details.php` (label/value block, tones info/success/warning).
- New `components/RequestMail.php`: contextual subject (`[AOG] New quote received · Request #235 · Dassault Falcon 7X G-EGVO`), signed absolute link to `requests/view`, display name (company name, else username), amount with currency name, excerpt.
- Rewritten: `mro_replies.php` (New / Updated Request Application — was wrongly saying « appointment request »), `ao_application_accepted.php` (PO accepted — values were not HTML-escaped), `report_submissions.php` (« Dear Aircraf Owner », « Your Company Name »), `notification_certified.php`, `notification_non_certified.php` (now same details as « covered » + coverage-gap reasons — product decision 2026-11-22; legacy manufacturer/model call still supported).
- Callers: `RequestsController` (8 compose calls: pass `request`, `aircraft`, `mro`, `reasonText`, `isUpdate`; « Request Updated » mails now carry full details), `MroRequestsController` (2, `isNew`), `MroApplicationsController` (2). Subjects built with `RequestMail::subject()`. Sending logic unchanged.
- Also moved to the shared template: `application_denied.php` (« Request Application Denied » → `[AOG] Quotation declined · Request #… `, was a list of raw IDs) and `application_rejected.php` (« Application Not Accepted » → « Quotation not selected »). The MRO sees its own quote + the request card; the selected MRO and its price are never shown (D-013).
- Checks: PHP lint (14 files); rendered with a Yii stub harness and screenshots (8 cases + legacy), HTML injection in request text escaped. Not sent through a real SMTP; not committed.

## 2026-11-22 — Requests: ETA / ETD picker without seconds
- `views/requests/create.php`, `update.php`: Kartik DateTimePicker format `yyyy-mm-dd hh:ii` (was `hh:ii:ss`, which copied the current seconds, e.g. 16:55:52). An existing value (update, or form re-rendered after an error) is displayed as `Y-m-d H:i`.
- Storage unchanged: `RequestsController` still normalises with `date('Y-m-d H:i:s', strtotime(...))` → seconds saved as `:00`. Client JS `parseDateValue` accepts the shorter value.
- PHP lint OK; not tested in a browser; not committed.

## 2026-11-22 — Notifications: `priority` field (replaces « AOG » text detection)
- **Migration** `m261122_160000_add_priority_to_notifications`: nullable `notifications.priority` (`aog` / `urgent` / `routine`), idempotent, reversible; backfills existing rows from the request number in the message (« request #123 » → `requests.operational_priority`, done in PHP for MySQL/MariaDB compatibility). **Must be run**: `php yii migrate`.
- `NotificationController::actionSaveNotification`: new optional `$requestId`; priority = the request's operational priority (explicit id, else parsed from the message). Ignored until the migration is applied (`hasAttribute` guard). `notification-list` returns `priority`.
- `RequestsController`: explicit `requestId` on the 4 « not fully covered » MRO notifications (their message has no request number).
- `models/Notification.php`: `priority` property + `in` rule.
- `views/layouts/header.php`: toast/sound use `priority` (fallback to « AOG » in text for old rows): AOG = red persistent toast + 3 beeps; Urgent = orange toast 15 s; others = blue 8 s. Bell dropdown: AOG / Urgent badge and red / orange left border.
- `MroApplicationsController`: explicit `requestId` on the 7 notifications whose message has no request number (PO accepted, appointment requested, work started, report submitted, update request approved / denied, new message).
- Still `priority = NULL` by design: `AwaitingRequestResponseController::actionNotifyMro / actionNotifyAo` (« Awaiting for your new CRS / response to CRS ») — the action only receives a profile id, no request.
- PHP lint OK (5 files); regex checked on real messages; jsdom (aog / urgent / routine / legacy / none). Migration not run; not committed.

## 2026-11-22 — Notifications: sound + mute button
- `views/layouts/header.php`: Web Audio sound on each new notification (no audio file): two-note chime for ordinary notifications, three short high beeps for AOG (« AOG » in the message). Played even when the toast is suppressed by the anti-duplicate rule.
- Mute button (speaker icon) in the bell dropdown header; preference in `localStorage` (`can.notifications.soundMuted`), synced across tabs; unmuting plays a short preview.
- One sound for several open tabs: shared 4 s lock in `localStorage` (`can.notifications.soundPlayedAt`).
- Browser autoplay rule: the audio context is unlocked on the first click/key press; a tab never touched stays silent.
- jsdom checks: normal chime, AOG beeps, 4 s lock, muted = silent, unmute preview. PHP lint OK. Not tested in a real browser; not committed.

## 2026-11-22 — Fix: MRO platform notifications no longer lost when SMTP is down
- Cause (logs 2026-11-22 13:06 / 14:13): in the MRO broadcast loops the e-mail was sent before `notification/save-notification`; with SMTP unreachable (DNS), each MRO retried, delays reached PHP's 30 s limit and the request was killed before the remaining notifications were saved.
- `controllers/RequestsController.php`: in the 4 MRO broadcast loops (new request ×2 paths, request update ×2 paths; covered and not-covered branches = 8 blocks) the platform notification block now runs **before** the e-mail block. Blocks moved as is (same lines, only order changed).
- `components/ResilientMailer.php`: circuit breaker — after the first SMTP failure in a request, later sends return `false` immediately (one extra `mail.smtp` warning).
- PHP lint OK; not tested end to end; not committed.

## 2026-11-22 — Notifications: opening one marks it as read
- `views/layouts/header.php`: « View » in the bell dropdown (unread items only) and a click on the new-notification toast call `notification/update-notification` (POST, CSRF, `keepalive`) then navigate; navigation never waits more than 1.5 s, and still happens if the request fails. Ctrl/Cmd/Shift + click (new tab) marks as read without leaving the page. A toast without a link just marks as read.
- `postNotification(url, keepalive)`: optional keepalive flag. No server change.
- PHP lint OK; JS syntax OK; jsdom check (toast click → POST update?id=… with keepalive → navigation). Not tested in a real browser; not committed.

## 2026-11-22 — Real-time alerts unified on Toastr (SweetAlert kept for confirmations)
- Audit: SweetAlert = 111 calls in 74 files, 71 of them confirmations (Cancel button) → kept; Toastr cannot block/confirm. Only `web/js/request-sync.js` used SweetAlert for information → moved to Toastr.
- `web/js/request-sync.js` (`displayStatusAlerts`): Toastr toasts; « View Request » link inside the toast; former centred blocking modal (po_loaded, update_request, work_accepted, report_submitted, closed) becomes a persistent toast (closed manually); grouped toast for several changes; `window.alert` fallback removed (list still refreshes if Toastr is missing); all text escaped.
- `views/layouts/header.php`: anti-duplicate between bell and request-sync — when a status toast and a bell toast arrive within 15 s, the status toast is kept (it has the link), the bell toast is removed or not shown; the bell still rings. Styles `.can-toast-action`, `.can-status-toast.is-important`.
- `views/layouts/main.php`, `auth.php`: Toastr pinned to `2.1.4` (was `latest`).
- Left in place: `.swal2-popup.can-status-*` styles in `web/css/list-system.css` (now unused).
- PHP lint OK; JS syntax OK; jsdom scenarios (bell then status, status then bell, grouped, duplicate event id, escaping). Not tested in a real browser; not committed.

## 2026-11-22 — Notifications: visual signal on arrival
- `views/layouts/header.php`: when the unread count (5 s polling) **increases**, the bell rings (shake + light red halo), the badge pulses 3 times, and a Toastr toast shows the latest unread message (escaped, truncated to 160 chars, « +n more » when several, click → notification action, same-origin only). Messages containing « AOG » use a red toast that stays until closed; others close after 8 s.
- The tab title is prefixed with the unread count, e.g. « (4) … ».
- No signal on page load (first poll = baseline). `prefers-reduced-motion` disables the animations.
- JS checked with Node + jsdom scenario; PHP lint OK; not tested in a real browser; not committed.

## 2026-11-22 — requests/view: tab icons, padding in « Request Informations »
- `views/requests/view.php`: Bootstrap Icons on the detail tabs — Overview `bi-grid-1x2`, MRO Quotes `bi-receipt`, Documents `bi-folder2-open`, Priority History `bi-clock-history` (aria-hidden).
- `web/css/requests-view-refresh.css`: tabs as inline-flex with 6px gap; `#request-description .request-info-textarea` gets 16px left/right padding (text was flush against the border). CSS cache version bumped to `?v=20261122-1`.
- Not tested in a browser; not committed.

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

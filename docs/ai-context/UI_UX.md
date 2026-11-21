# UI / UX

## Layouts **[V]** `views/layouts/`
- `main.php` (+ `header.php`, `footer.php`) — authenticated app shell with role-based navigation, notification bell, profile dropdown.
- `landing-main.php` (+ `landing-header.php`, `landing-footer.php`, `can-navbar.php`, `can-footer.php`) — public site / early access.
- `auth.php`, `login.php` — authentication pages.
- `error.php` + static `web/errors/error.html?code=` (Apache fallback for 400–504).
- `advertising.php` — media kit page.
- Partials: `_aog-push.php`, `_support-widget.php`, `_registration-choice.php`, `_favicon.php`, `can-spinner.php`.
- Maintenance page: `web/maintenance.html` (shown when `web/maintenance.flag` exists during deploys).

## Styling
- Bootstrap 5 + custom design system CSS: `web/css/can.css`, `list-system.css`, `detail-system.css`, `form-system.css`, `spur.css`, `dismissible-alerts.css`; page-specific "refresh" CSS (`mro-*-refresh.css`, `admin-audit.css`, `aog-push.css`, …).
- Aviation "instrument panel" visual vocabulary (priority badges, deadline countdowns).
- Ratings via jQuery bar-rating (`web/css/bars-*.css`, `jquery.barrating.js`).
- Leftover template/demo assets: `demo.css`, `examples.css/js`, `jquery-1.11.2.min.js`, `html5shiv.js`, `gulpfile.js` in `web/js` — candidates for cleanup.

## JavaScript **[V]** `web/js/`
`can.js` (global), `request-sync.js` (AJAX staleness polling), `operational-priority.js` (AOG/Urgent form logic + countdowns), `aog-push.js` (web push opt-in), `logout-confirmation.js` (SweetAlert2 from CDN), `prelaunch.js`, `advertising-media-kit.js`, `chart-js-config.js`.

## Conventions observed
- Lists: shared `_request-list.php` partials per role, pagination 10, search + priority filter.
- Flash messages: `session->setFlash` in controllers; displayed as **Toastr** notifications by `views/layouts/main.php` (all flash types mapped). Views must NOT render their own flash banner (duplicate). Legacy banners still exist in many views.
- Signed IDs in all newer links; some views still build numeric URLs (see SECURITY S-2).
- Date display: mostly `date()`/`Yii::$app->formatter` in server timezone; deadline countdown in JS from UTC timestamp.
- Language: UI English, code comments French.
- Long business texts (request details, MRO proposals) use `textarea.a4-text-box` (web/css/form-system.css): ~one A4 page visible, scroll beyond.

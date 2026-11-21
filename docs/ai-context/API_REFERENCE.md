# Routes & Endpoints

No REST API. Pretty URLs; explicit rules in `config/web.php` (`urlManager.rules`), otherwise default `controller/action`. IDs in most business URLs are **signed IDs** (`UrlIdHelper::encode`), e.g. `requests/view?id=1A2B3C4D5E`.

Access column: G = guest allowed, @ = any logged-in user, A/AO/MRO = role enforced in `behaviors()`. "own" = per-record ownership check verified in action.

## Public (SiteController, SupportController, PushController manifest/worker)
| Route | Method | Purpose |
|---|---|---|
| `/` , `site/index` | GET | Home / early-access landing |
| `POST early-access/join` | POST | Join waitlist (Turnstile) |
| `early-access/confirm/<token>` , `early-access/preferences/<token>` | GET | Confirm / unsubscribe |
| `site/login`, `site/logout`, `site/logout-all-devices` | GET/POST | Auth |
| `site/become-ao`, `site/become-mro`, `site/verify-email`, `site/verify-emailao` | | Registration |
| `site/request-password-reset`, `reset-password/<token>`, `reset-password-mro/<token>`, `site/request-username-reset` | | Recovery |
| `site/advertising`, `site/about`, `site/contact`, `site/terms`, `site/privacy-policy`, `site/cookie-policy` | GET | Content |
| `site/load-cities`, `site/load-airports`, `site/airport-search`, `site/get-cities`, `site/get-models-by-manufacturer`, `site/cities-by-country`, `site/client-location` | GET (AJAX) | Lookups |
| `support/create`, `support/dispatch` | POST | Support ticket |
| `certificate-types/get-certificate-type-id` | GET | Public JSON lookup (registration form). Other `certificate-types/*` and all `terms/*` are admin-only since S-1 fix |

## AO
| Route | Notes |
|---|---|
| `requests`, `requests/new-requests`, `requests/open-requests`, `requests/closed-requests` | own lists (`created/answered/po_loaded`, `work_accepted/work_started/…`, `closed`) |
| `requests/create`, `requests/update/<id>`, `POST requests/delete/<id>` | ⚠ delete/ownership see S-2 |
| `requests/view`, `requests/check-applications`, `requests/view-application`, `requests/deny` | quotes review |
| `requests/load-po`, `requests/view-po`, `requests/download-po`, `requests/view-po-document` | PO |
| `requests/view-reports`, `requests/download`, `requests/submit-description`, `requests/accept-crs`, `requests/approve-quote`, `requests/provide-feedback` | CRS & closure |
| `requests/update-request` → `request-changes/create/<requestId>` | change orders |
| `request-changes/{view,upload-po,accept-quote,reject-quote}/<id>` | AO side of W-2 |
| `ao-aircrafts/*`, `ao-appointments/{index,confirm,cancel,reschedule}`, `ao-notifications-preferences/*`, `ao-profile/update/<id>` | |
| `disputes`, `dispute/{create,view,update,close,remove}` | shared with MRO |
| `conversations`, `conversations/<id>`, `requests/contact/<id>` | chat |

## MRO
| Route | Notes |
|---|---|
| `mro-requests` | matched open requests (BR-3) |
| `mro-requests/apply/<id>`, `mro-requests/applyupdate/<id>`, `mro-requests/recommend-mro/<id>`, `mro-requests/contact/<id>` | quoting |
| `mro-applications`, `mro-applications/closed-requests`, `…/view-answer`, `…/cancel-application`, `…/view-po`, `…/accept-po`, `…/set-appointment`, `…/start-work`, `…/submit-report`, `…/view-reports`, `…/provide-new-quote`, `…/accept-update`, `…/deny-request`, `…/view-feedback`, `…/contact` | execution (`findOwnedApplication`) |
| `request-changes/{accept,reject,accept-po,reject-po,submit-quote}/<id>` | MRO side of W-2 |
| `mro-certificates/*`, `mro-aircraft-certificates/*`, `mro-insurance-documents/*`, `mro-airports/*`, `mro-appointments/*`, `mro-notifications-preferences/*`, `mro-profile/{update,view}` | compliance & profile |
| `POST push/register`, `POST push/unregister`, `push/worker`, `push/manifest` | AOG web push |

## Admin
`dashboard/home` (all roles, role-specific content), `ao-profile/index|toggle-status|send-password-reset`, `mro-profile/index|…`, `admin-disputes/*` (+ ban/unban), `awaiting-request-response/*` (ban/unban/notify), `admin-support-tickets/*`, `admin/audit-log`, `admin/audit-log/view/<id>`, `admin/audit-log/request/<requestId>`, `admin/prelaunch-subscribers` (+ export/resend), `admin/update-password`, `advert/*`, `country/*`, `city/*`/`cities/*`, `airports/*`, `aircrafts/*`, `aircraft-model/*`, `certificates/*`, `currency/*`.

## Shared JSON endpoints (@)
`notification/{notification-count,notification-list}` (GET), `notification/{mark-all-as-read,delete-all,update-notification,delete-notification}` (POST) — scoped to current user. `request-sync/changes` — returns staleness + signed ids only.
`notification/save-notification` is internal only (refuses direct HTTP).

## Console
`php yii aog-push/send [limit]` (cron), `php yii migrate`, `php yii hello` (template leftover).

## Route config anomalies **[V]**
- `'awaiting-request-response' => 'app\controllers\AwaitingRequestResponseController'` maps a URL to a class name, not a route — **NEEDS VERIFICATION** (probably works only through default routing).
- `cities/*` rules point to a non-existent `cities` controller (controller is `CityController` → `city/*`).
- Routable leftovers: `ao-profile/index1`, `mro-profile/index1`, `mro-airports/index1`, `requests/view-old`, `requests/view-reports1`.

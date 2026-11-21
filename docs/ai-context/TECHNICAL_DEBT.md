# Technical Debt & Improvement Opportunities

| # | Item | Evidence | Impact | Proposal [P] |
|---|---|---|---|---|
| TD-1 | Core schema not under migrations, no schema dump | `migrations/` has 14 recent files only | Cannot rebuild DB, tests can't create schema, FKs unknown | Baseline migration generated from production `SHOW CREATE TABLE` |
| TD-2 | Fat controllers | `RequestsController` 3,445 lines, `SiteController` 2,051, `MroApplicationsController` 1,238, `AoMroDisputeController` 1,095 | Hard to review, duplicated logic | Extract services (RequestWorkflowService, MroMatchingService, NotificationDispatcher) progressively |
| TD-3 | Ownership checks scattered & inconsistent | SECURITY S-2 | Security bugs | Shared trait `OwnsRecords` with `findOwnedRequest/Application` |
| TD-4 | Status transitions not centralised | `->status = '...'` in 4 controllers, string literals | Invalid transitions possible | `RequestWorkflow::transition($request, $to)` with allowed-transition map (pattern exists in `RequestChangesController::requireStatus`) |
| TD-5 | MRO matching duplicated and inconsistent | visibility query in `MroRequestsController::actionIndex` vs. notification loop in `RequestsController::actionCreate` (insurance only in one) | Wrong MROs notified / shown | Partly done 2026-11-21: `components/MroRequestEligibility` used for per-request checks; the list query in `MroRequestsController::actionIndex` and the notification loop in `RequestsController::actionCreate` still duplicate it |
| TD-6 | Synchronous notification loop over **all** MROs on request creation, with N+1 queries and SMTP calls (4 s timeout each) | `RequestsController::actionCreate` | Slow/timeout when MRO count grows | Query only eligible MROs; queue emails (DB queue + cron, like aog_push_job) |
| TD-7 | Dead/duplicate actions still routable | `actionIndex1` (AoProfile, MroProfile, MroAirports), `actionViewOld`, `actionViewReports1`; large commented-out blocks (e.g. old `actionSubmitReport` in `MroApplicationsController`) | Attack surface, confusion | Remove after confirming unused |
| TD-8 | Legacy `update_request*` statuses co-exist with change-order module | views, `MroApplicationsController::actionAcceptUpdate/DenyRequest` | Two amendment mechanisms | Decide retirement; migrate any rows still in those statuses |
| TD-9 | Naive local datetimes for ETA/ETD/appointments | `date('Y-m-d H:i:s', strtotime(...))` | Ambiguous across timezones | Store UTC, add user/airport timezone, convert in views |
| TD-10 | `Cities` model declares `public $id` and private attribute shadows; `Countries` private shadows | `models/Cities.php`, `models/Countries.php` | AR attribute bugs (id may not populate) | Remove property declarations |
| TD-11 | `certificate_types.aircraft_model_ids` stored as CSV; `requests.required_certificates` stored as type string; `request_certificates` junction apparently unused | models | No referential integrity | Normalise with junction tables |
| TD-12 | Unused component `NotificationManager`; template leftovers (`HelloController`, `ContactForm`, demo CSS/JS, `Vagrantfile`) | | Noise | Clean up |
| TD-13 | Low automated test coverage of core workflow | tests cover audit, push, prelaunch, auth, request-change model; none for request/quote/PO lifecycle | Regressions | Functional tests per workflow step once a schema baseline exists |
| TD-14 | Hard deletes of requests (cascade unknown) | `RequestsController::actionDelete` | Orphans, lost history | Use `cancelled` status / soft delete |
| TD-15 | DB charset `utf8` | `config/db.php` | Emoji/4-byte chars | Convert to `utf8mb4` |
| TD-16 | No timezone set in app config | `config/web.php` | Depends on server php.ini | Set `'timeZone' => 'UTC'` explicitly (after impact analysis on existing data) |
| TD-17 | Route rules inconsistent (`cities/*` → missing controller, class-name route) | `config/web.php` | 404s | Fix rules |
| TD-18 | Placeholder params (`adminEmail`, `senderEmail` = example.com) | `config/params.php` | Wrong sender if used | Configure or remove |
| TD-19 | Mixed `className()` (deprecated) and `::class` | many | Minor | Opportunistic |
| TD-20 | Uncommitted work at discovery time | `controllers/DashboardController.php`, `views/dashboard/home.php`, new `components/MroRequestCounters.php` | Risk of loss | Commit or stash intentionally |

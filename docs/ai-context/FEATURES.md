# Feature Inventory

Status: IMPLEMENTED · PARTIALLY IMPLEMENTED · PLANNED · NOT FOUND · NEEDS VERIFICATION

## Maintenance requests
| Feature | Status | Evidence / notes |
|---|---|---|
| Normal (Routine) requests | IMPLEMENTED | `Requests`, `RequestsController::actionCreate` |
| AOG priority | IMPLEMENTED | `operational_priority='aog'`, required response window, push |
| Urgent priority | IMPLEMENTED | optional response window |
| Line / Base / Scheduled / Engine maintenance, Avionics, NDT | **NOT FOUND** | No request type/category field. Terms appear only in marketing/support text |
| Request create / update / delete | IMPLEMENTED | delete is hard delete |
| Aircraft info (fleet, model, registration, S/N) | IMPLEMENTED | `aircrafts`, `aircraft_model`, `AoAircraftsController` |
| ETA / ETD | IMPLEMENTED | server-local naive datetime |
| UTC storage + user timezone conversion | PARTIALLY | only `response_due_at_utc` is UTC; no user timezone preference |
| Response deadlines | IMPLEMENTED | BR-1 |
| Overdue detection | PARTIALLY | display-only, no alert/escalation |
| Response-time calculation (KPI) | NOT FOUND | |
| Priority history | IMPLEMENTED | `request_priority_history`, `views/requests/_priority-history.php` |
| Live list refresh | IMPLEMENTED | `RequestSyncController`, `web/js/request-sync.js` |

## Quotation & workflow
| Feature | Status | Evidence |
|---|---|---|
| Distribution / matching to MROs | IMPLEMENTED | BR-3 |
| MRO responses (quotes) | IMPLEMENTED | `mro_request_apply` |
| Quote update | IMPLEMENTED | `actionApplyupdate` |
| Quote acceptance by AO | IMPLEMENTED (implicit) | acceptance = issuing a PO |
| PO management (upload, view, download) | IMPLEMENTED | `ao_requests_applications` |
| PO acceptance by MRO | IMPLEMENTED | `actionAcceptPo` |
| Appointment scheduling | IMPLEMENTED | `appointment` |
| Start Work / Work in Progress | IMPLEMENTED | `work_started` |
| CRS report submission & acceptance | IMPLEMENTED | `repair_report` |
| Work Accepted | IMPLEMENTED | status `work_accepted` = PO accepted by MRO (naming differs from "work accepted by AO") |
| Change orders (scope/PO/quote revision) | IMPLEMENTED | `request_change*` |
| MRO-to-MRO recommendation | IMPLEMENTED | `MroRequestsController::actionRecommendMro` |
| Feedback / ratings | IMPLEMENTED | `feedback` |
| Chat between AO and MRO | IMPLEMENTED | `chat`, `conversations` |
| Disputes | IMPLEMENTED | `disputes`, admin reply |
| Cancel request (status `cancelled`) | NOT FOUND | constant exists, never set |

## Administration
| Feature | Status | Evidence |
|---|---|---|
| User accounts (AO / MRO / Admin) | IMPLEMENTED | 3 profile tables |
| Self-registration + email verification | IMPLEMENTED (gated by prelaunch mode) | `SiteController::actionBecomeAo/BecomeMro/VerifyEmail*` |
| Companies / organizations as separate entity | NOT FOUND | company = profile; no multi-user company |
| Roles & permissions | PARTIALLY | 3 hard-coded roles via session; no RBAC, no sub-roles |
| Admin management of users (ban/unban/hide/reset) | IMPLEMENTED | |
| Admin password change | IMPLEMENTED | `AdminController::actionUpdatePassword` |
| Reference data (countries, cities, airports, models, cert types, currency) | IMPLEMENTED | admin CRUD controllers |
| MRO compliance docs (certificates, aircraft approvals, insurance) | IMPLEMENTED | |
| In-app notifications | IMPLEMENTED | `NotificationController` |
| Email workflows | IMPLEMENTED | 33 templates in `mail/` |
| Web push (AOG) | IMPLEMENTED, **disabled by default** | `firebasePushEnabled=0`; needs cron `yii aog-push/send` |
| Notification preferences | IMPLEMENTED | AO & MRO preference tables |
| Audit logs | IMPLEMENTED | `audit_log`, `AdminAuditLogController` |
| Advertising | PARTIALLY | adverts (photo/video, dates, status) shown in footer/pages; **no zones/placements**, no billing |
| Advertising media kit page | IMPLEMENTED | `site/advertising` |
| Payment & commission management | **NOT FOUND** | only mentioned in terms/privacy text |
| Support tickets | IMPLEMENTED | |
| Early-access / prelaunch waitlist | IMPLEMENTED | `prelaunch_subscriber`, admin export CSV |
| Dashboard (role-based KPIs) | IMPLEMENTED (in progress) | `DashboardController`, uncommitted `MroRequestCounters` |
| Terms / privacy / cookie pages | IMPLEMENTED | |

## Open questions (business rules to clarify)
1. Should Line/Base/Scheduled/Engine/Avionics/NDT become a request category, and should it drive MRO matching?
2. Is insurance mandatory for an MRO to **see/answer** requests, or only to be notified? (currently inconsistent)
3. Should Urgent require a response window like AOG?
4. Should ETA/ETD be stored in UTC and shown in each user's timezone (and is the input the airport's local time)?
5. What should happen when the deadline passes (escalation, admin alert, auto-reopen to more MROs)?
6. Can the AO accept more than one quote / issue several POs on one request? (code takes the latest PO row)
7. Meaning of "Work Accepted": MRO accepts PO (current) vs. AO accepts completed work.
8. When should a request be `cancelled` vs. deleted? Should deletion be allowed after a PO?
9. Payments/commission model (who pays, when, on what amount)?
10. Multi-user companies (several staff per AO/MRO)?

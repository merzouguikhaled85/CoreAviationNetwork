# Database

**Engine:** MySQL, connection `utf8` (3-byte) — `config/db.php`.
**Critical gap:** only 14 migrations exist (`migrations/`), all from 2024-05 (`terms`) and 2026-09/10. **The core business tables were created outside migrations** — there is no schema dump in the repo. Columns below for core tables are **inferred from model rules/labels and queries [A]**; types, nullability, indexes and FKs must be confirmed against the live DB (`SHOW CREATE TABLE`). **[P]** Commit a baseline schema migration.

## Core tables (no migration — inferred)
| Table | Model | Key columns (inferred) | Relations |
|---|---|---|---|
| `admin_profiles` | `AdminProfile` | admin_id, username, password (hash), email, first_name, last_name, contact_number, profile_photo, auth_version | — |
| `ao_profiles` | `AoProfile` | ao_id, username, password, email, status(active/banned/hidden), names, contact_number, company_name, profile_photo, email_verified, country_id, city_id, address, zip_code, verification_token, password_reset_token, auth_version | countries, cities |
| `mro_profiles` | `MroProfile` | mro_id, same identity fields as AO + website, youtube_video, company_photo, main_references | certificates, mro_aircraft_certificate, mro_insurance_documents, mroprofile_airport, mro_notifications_preferences |
| `requests` | `Requests` | request_id, ao_id, aircraft_id, request_details, aircraft_registration, serial_number, eta, etd, location (airport name copy), destination (airport_id), status, required_certificates (certificate **type string**), attachment, operational_priority, response_required_minutes, response_due_at_utc | ao_profiles, aircrafts, airports, request_certificates (via table), mro_request_apply |
| `mro_request_apply` | `MroRequestApply` | id, mro_id, request_id, Description, price, currency, attachment | = the **quotation / MRO answer** |
| `ao_requests_applications` | `AoRequestsApplications` | id, request_id, application_id (→ mro_request_apply.id), po (file), created_at, updated_at | = the **Purchase Order** |
| `repair_report` | `RepairReport` | repair_report_id, mro_request_apply_id, report, CRSed, CRS_attachment, AO_description, mro_quote, mro_attachment, quote_approved, created_at, updated_at | = **CRS report** + post-work quote |
| `appointment` | `Appointment` | appointment_date, mro_id, ao_id, request_id, ao_requests_applications_id, status, reschedule_appointment_date | |
| `chat` / `conversations` | `Chat` / `Conversations` | chat: chat_id, mro_id, ao_id, request_id · conversations: chat_id, timestamp, sender/receiver (see model) | |
| `disputes` | `Dispute` | dispute_id, request_id, description, status(open/resolved), timestamp, ao_id, mro_id, po, created_by(ao/mro), admin_response | |
| `feedback` | `Feedback` | feedback_id, request_id, ao_id, mro_id, rating, kept_to_agreed_schedule_rating, kept_to_agreed_cost_rating, overall_communication_rating, feedback_text, timestamp | |
| `notifications` | `Notification` | id, content, recipient_id, recipient_type(ao/mro/admin), status(null/read), priority(aog/urgent/routine/null — m261122_160000), created_at, actions | |
| `adverts` | `Advert` | advert_id, admin_id, advert_type(photo/video), content, timestamp, status(active/inactive), start_date, end_date, use_url, url | |
| `aircrafts` | `Aircrafts` | aircraft_id, manufacturer, model, serial_number, registration_number, ao_id, aircraft_model_id, certificate_type_id | |
| `aircraft_model` | `AircraftModel` | aircraft_model_id, manufacturer, model | |
| `certificate_types` | `CertificateTypes` | certificate_type_id, type, aircraft_model_ids (**CSV string**) | |
| `certificates` | `Certificates` | certificate_id, mro_id, type (string), certificate (file), certificate_type_id | |
| `mro_aircraft_certificate` | `MroAircraftCertificate` | aircraft_certificate_id, mro_id, aircraft_model_id, certificate | |
| `mro_insurance_documents` | `MroInsuranceDocuments` | id, mro_id, file_path, file_name, file_type, file_size, created_at | |
| `mroprofile_airport` | `MroprofileAirport` | id, mro_id, airport_id | MRO service coverage |
| `airports` | `Airports` | airport_id, airport_name, city_id, country_id, icao (+ city_name, country_name) | |
| `countries`, `cities` | `Countries`, `Cities` | country_id, country_name, country_code · city id, city_name, country_id | |
| `currency` | `Currency` | id, name, code, symbol | |
| `ao_notifications_preferences` | | id, ao_id, notify_by_email, notify_by_platform, notify_mro_replies, notify_appointment_requests, notify_po_acceptance, notify_maintenance_proposals, notify_mro_recommendations, notify_work_start, notify_report_submissions | |
| `mro_notifications_preferences` | | id, mro_id, notify_by_email, notify_by_platform, notify_by_both, notify_for_non_certified_aircraft, notify_for_certified_aircraft, notify_for_appointment_acceptance, notify_for_feedback | |
| `request_certificates` | (junction only) | request_id, certificate_id | **[A]** possibly unused — matching uses `requests.required_certificates` string |

## Tables with migrations **[V]**
| Migration | Tables / changes |
|---|---|
| `m240528_154637` | `terms` |
| `m260906_190000` | `request_change_event` (+ composite indexes on requests, mro_request_apply, ao_requests_applications, repair_report, appointment, conversations) |
| `m260910_190000` | `requests.operational_priority`, `response_required_minutes`, `response_due_at_utc`; `request_priority_history` |
| `m260912_*` (4) | `support_ticket`, acknowledgement cols, admin assignment, `support_ticket_history` (FK), `support_ticket_reply` (FK) |
| `m260913_*` (2) | `audit_log` (no FK by design) + indexes |
| `m260923_120000` | `request_change`, `request_change_document` (FKs to requests / mro_request_apply / request_change) |
| `m260926_*` (2) | `prelaunch_subscriber` + delivery tracking |
| `m261008_190000` | `auth_version` on admin/ao/mro profiles |
| `m261009_200000` | `push_subscription`, `aog_push_job` |
| `m261121_170000` | `conversations.attachment_path/_name/_mime/_size` (nullable; files in `@runtime/chat-attachments`) |

## Status vocabularies **[V]**
- `requests.status`: `created`, `answered`, `po_loaded`, `work_accepted`, `work_started`, `report_submitted`, `closed`, `cancelled`, legacy `update_request`, `update_request_accepted`, `update_request_denied` (`models/Requests.php`)
- `requests.operational_priority`: `aog`, `urgent`, `routine`
- `request_change.status`: `pending_mro`, `rejected`, `awaiting_updated_po`, `pending_po_review`, `po_rejected`, `awaiting_updated_quote`, `pending_quote_review`, `quote_rejected`, `completed`, `cancelled`
- `appointment.status`: `waiting response`, `confirmed`, `canceled`, `reschedule`
- `disputes.status`: `open`, `resolved`
- `*_profiles.status`: `active`, `banned`, `hidden`

## Date/time handling **[V]**
- **Everything is UTC** since 2026-11-22: app `timeZone` = UTC (web + console), formatter UTC, MySQL session `time_zone = '+00:00'` (config/db.php `on afterOpen`). Server settings (php.ini, MySQL global zone) no longer matter.
- `eta`, `etd`, `appointment_date`, `created_at`… are UTC values; forms label ETA/ETD « (UTC) ».
- Existing data converted by `m261122_200000_convert_datetimes_to_utc` (shifts logged in `timezone_conversion_log`).
- Push tables use Unix ints (timezone-free).

## Integrity notes
- Core tables: FK presence unknown **[A]**. Model relations are not backed by migrations.
- Deletes are **hard deletes** everywhere; no soft-delete columns found. History is retained via `audit_log` (before-delete snapshot), `request_priority_history`, `request_change_event`, `support_ticket_history`.
- Transactions: used in request-change flow and AOG push claims **[V]**; most legacy workflow actions save several records without a transaction.
- Usernames must be unique across three tables, but this is not enforced by the DB; `User::findByUsername` checks admin → mro → ao in order.

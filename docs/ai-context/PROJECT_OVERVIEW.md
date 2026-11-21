# Project Overview — Core Aviation Network (CAN)

> Discovery baseline: 2026-11-21, git HEAD `6f2aec0` (branch history starts 2026-09-12, 88 commits).
> Legend: **[V]** verified in source · **[A]** assumption · **[P]** proposal.

## What it is
CAN is a Yii2 web marketplace connecting **Aircraft Operators (AO)** with **MRO** (Maintenance, Repair & Overhaul) organizations. AOs post maintenance requests for their aircraft; eligible MROs (matched by airport, certificate type, aircraft-model approval and insurance) answer with quotations; the AO issues a Purchase Order (PO); the MRO accepts, schedules, performs the work and submits a CRS (Certificate of Release to Service) report; the AO accepts the CRS and leaves feedback. **[V]** `controllers/RequestsController.php`, `controllers/MroRequestsController.php`, `controllers/MroApplicationsController.php`

Production URL (from config default): `https://can.coreaviationnetwork.com` **[V]** `config/env.php`.
Hosting: GoDaddy shared hosting (cPanel, Apache), behind Cloudflare. **[V]** `docs/deployment-godaddy.md`, `config/web.php`

## Actors **[V]** `models/User.php`
| Role | Table | Session key |
|---|---|---|
| `admin` | `admin_profiles` | `admin_id` |
| `mro` | `mro_profiles` | `mro_id` |
| `ao` | `ao_profiles` | `ao_id` |

There is no shared `user` table and no Yii RBAC. One person = one company profile.

## Current product phase
An **Early Access / pre-launch** mode exists (`params['prelaunchMode']`) that redirects public registration to an early-access waiting list (`prelaunch_subscriber`). **[V]** `components/PrelaunchMode.php`, `controllers/SiteController.php`

## Main modules (detail in FEATURES.md)
- Maintenance requests with operational priority (AOG / Urgent / Routine) and UTC response deadlines
- MRO matching, notifications (email + in-app + Firebase web push for AOG)
- Quotation → PO → work → CRS → feedback workflow
- Change orders ("avenants") after PO acceptance (`request_change`)
- Appointments, chat/conversations, disputes, support tickets
- Admin back-office: reference data, users (ban/unban), adverts, audit log, prelaunch subscribers

## Not present in code (despite being business targets)
Maintenance categories (Line / Base / Scheduled / Engine / Avionics / NDT), payments and commissions, multi-user companies. See FEATURES.md.

## Documentation map
See `CLAUDE.md` at repo root.

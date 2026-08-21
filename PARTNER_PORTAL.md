# ManageTrans Partner Portal — Architecture & Current State

## AI Agent Reading Order

For Partner Portal work:

1. Read PARTNER_PORTAL.md FIRST.
2. Read AGENTS.md for general engineering rules.
3. Read PROJECT.md only when broader ManageTrans architecture is required.
4. Inspect implementation files only when the requested task requires details
   not documented here.

Do NOT recursively scan models/controllers/views/tests just to rediscover
architecture already documented here.

If code contradicts this document:
- runtime code wins
- report the discrepancy
- update this document during the current phase

## Current Branch / State

Partner Portal is **merged to production branch `main`**.

Merge:

- PR #1
- Merge commit: `9c2f40eaf8b53c3603787656e740df8a071f0822`

Status:

- Code merged: **YES**
- Production deployment: **NOT CONFIRMED** (unverified by repository inspection)
- Production migrations: **NOT CONFIRMED** (unverified by repository inspection)

Post-merge hardening:

Branch `fix/partner-portal-post-merge-hardening` addresses final correctness/security/UX issues discovered during repository audit.

Completed:

- Phase 1 — Data foundation
- Phase 2 — Partner authentication and user management
- Phase 3 — Manual Partner requests
- Phase 4 — Image/Textract Partner requests
- Phase 5 — Internal review + approval decision (REQ intake)
- Phase 6 — Operational integration (Trip creation from approved REQ)
- Phase 7 — Complete UI/UX improvement
- Phase 8 — Final QA and release ✅

Automated test baseline (post-merge):

- 264 passed
- 816 assertions

Phase 8 QA summary (concise):

- Full automated suite passed (`sqlite` `:memory:`; Firebase/AWS mocked)
- Partner auth/security, Manual/Image request E2E, Approve/Decline passed
- Approved REQ → normal Trip creation; Driver+Date grouping; duplicate conversion protection passed
- Driver API ownership, private image/path traversal, reports/lifecycle integration passed
- Manual browser/mobile QA passed at 375px / 768px / 1440px (Partner + internal flows)
- Accessibility/dark-mode runtime reviewed during that manual QA
- Production-shaped migration status verified; full migration replay not repeated during Phase 8 to preserve restored data

Phase 7 summary:

- Partner-facing portal UI polished (login, dashboard, requests, mobile journey)
- Internal Partner Requests inbox and read-only review detail
- Approve/Decline decision workflow; operational conversion via normal Trip create
- Trip create prefill from approved REQ; duplicate conversion guards
- Responsive patterns at 375 px / 768 px / 1440 px; dark-mode CSS variables
- Architecture correction preserved: approval does not create Trips

Architecture (Phase 7):

Automated tests:

- `DB_CONNECTION=sqlite`
- `DB_DATABASE=:memory:`

Firebase/AWS integrations must be mocked where relevant.

## Core Identity Model

Partner submission:

`REQ-000001`

Operational Trip:

`TRP-000001`

Rules:

- REQ is permanent Partner submission identity.
- TRP is operational Trip identity.
- One REQ may produce one or multiple TRPs.
- Partner-generated Trip: `partner_request_id` = originating `PartnerRequest.id`
- Normal internal Trip: `partner_request_id` = null
- REQ/TRP references are generated after DB insert from record IDs.
- Never generate references using count(), max(), user input, or timestamps.

## Domain Model

```
Partner
├── PartnerUsers
└── PartnerRequests
    ├── PartnerRequestItems
    └── Trips
        └── TripCrews
```

PartnerRequest is submission/decision history.

Trip is live operational state.

Approved PartnerRequest with zero Trips is a valid intermediate state.

Do NOT synchronize later operational Trip edits back into
PartnerRequestItem after approval.

## Partner Settings

Partner controls:

- `allow_manual_submission`
- `allow_image_submission`

Possible combinations:

- manual only
- image only
- both
- neither

New Request UX routes according to these settings.

## Authentication

Internal ManageTrans:

- `web` guard

Partner portal:

- `partner` guard + dedicated provider

Driver mobile API:

- Sanctum

Multiple PartnerUsers may belong to one Partner.

There is no public Partner self-registration.

Internal staff manage PartnerUsers.

Partner identity always comes from authenticated PartnerUser:

`Auth::guard('partner')->user()->partner_id`

Never trust posted `partner_id` for Partner ownership.

Partner guard logout/inactivity handling must not destroy an authenticated
internal web session in the same browser.

## Partner Visibility

PartnerUsers see company-level requests for their Partner.

`partner_user_id` records original submitter.

Partner may see:

- REQ reference
- submission method
- request status
- submitted information
- decline reason
- linked TRP references
- linked TRP trip date
- linked TRP operational status

Partner must NOT see:

- driver identity
- internal review fields
- internal activity logs
- internal notes
- issues
- expenses
- AWS/Textract errors
- filesystem paths

## Manual Submission

Partner manual input fields ONLY:

Required:

- `trip_date`
- `name`
- `from_location`
- `to_location`

Optional:

- `phone`
- `vessel_id`

Do NOT expose as Partner inputs:

- `pick_up_time`
- `phone_2`
- `address`
- `flight_number`
- `remarks`
- `sub_remark`
- `vessel_name_raw`
- `driver_id`

Existing internal values must survive Partner edits.

Partner manual Pending REQ:

- editable
- withdrawable

Image Pending REQ:

- withdrawable
- not editable

Non-Pending REQ:

- not Partner-editable

## Image Submission

Partner flow:

upload image → submit → Manage Trans reviews

Partner does NOT review Textract/OCR output.

Accepted:

- JPEG/JPG/PNG
- max 10MB

Partner source image storage:

- `Storage::disk('local')`
- private path conceptually: `partner-requests/{partner_id}/{uuid}.ext`

IMPORTANT:

- **Internal Trip OCR:** temporary local upload → parse → delete temp file
- **Partner Request OCR:** private historical source image → preserve with REQ permanently

Never treat Partner Request image as disposable OCR temp data.

Never expose:

- `source_image_path`
- private filesystem path
- public asset URL

Partner and internal staff receive image through authenticated streaming
endpoints.

## Image Extraction Failure

If image storage succeeds and REQ exists:

| Outcome | `extraction_status` |
| :--- | :--- |
| Textract success | `completed` |
| Textract returns no usable rows | `failed` (REQ + image preserved) |
| Textract/AWS exception | `failed` (REQ + image preserved) |

Technical AWS errors are logged internally only.

Storage failure before successful REQ creation: do not leave partial REQ.

Partner still receives a friendly successful-submission experience when OCR
fails after the request/image have been safely preserved.

## Vessel OCR Matching

Always retain:

- `vessel_name_raw`

Automatic mapping uses normalized EXACT match only:

- trim
- collapse whitespace
- case-insensitive compare

Never use:

- partial LIKE
- contains
- starts-with
- fuzzy matching

Unknown or ambiguous normalized match:

- `vessel_id` = null

Never automatically create Vessel from Partner OCR.

## Internal Partner Requests

Internal routes use normal `web` guard.

Internal Partner Requests are NOT under `/partner`.

Existing Trip permissions are reused:

| Action | Permissions |
| :--- | :--- |
| View inbox/detail | `view_trips` |
| Decline | `edit_trips` |
| Approve | `create_trips` |
| Create Trip from approved REQ | `create_trips` |

Admin permission bypass remains unchanged.

Sidebar includes **Partner Requests** with Pending count.

Inbox supports:

- Pending
- Approved
- Declined
- Withdrawn
- All

plus method / Partner / REQ search filters.

## Internal Review (Read-Only Decision)

Pending Partner Request detail is **read-only** for internal staff.

Display:

- REQ reference, Partner, Submitted By/At, Method, Status
- Partner-submitted crew/details (PartnerRequestItems as submission snapshot)
- source image for Image REQ (secure streaming endpoint)
- extraction status where useful internally

Actions on Pending:

- Back to Queue
- Approve
- Decline

Do **not** show editable operational fields on the REQ page:

- no Save Review
- no Driver/Vessel selectors
- no pickup-time editor
- no add/remove crew
- no Select2 operational editor

Approved / Declined / Withdrawn: read-only internally.

Approved with zero Trips shows **Approved — Awaiting Trip Creation** and a
**Create Trip** action (requires `create_trips`).

Approved with linked Trips shows TRP references and operational summary; no
Create Trip action.

## Approval (Decision Only)

`PartnerRequestApprovalService::approve()` performs decision/state work only:

- `DB::transaction()` + `lockForUpdate()`
- re-check Pending
- stale `request_version` check via `PartnerRequestReviewVersion`
- ensure no linked Trips yet
- set `status = approved`, `approved_at`, `approved_by`

Approval does **not**:

- validate operational crew completeness
- require pickup time / vessel / from-to
- group by Driver + Date
- create Trips or TripCrews
- send Driver notifications

On success, controller redirects to:

`GET /trips/create/from-partner-request/{partnerRequest}`

Approval can succeed even when PartnerRequestItems lack operational fields or
when Image extraction produced zero rows.

## REQ → TRP Conversion (Normal Trip Workflow)

Trip creation from an approved REQ uses the **same** internal Trip create/store
path as normal Trips.

Route:

`GET /trips/create/from-partner-request/{partnerRequest}`

Reuses `resources/views/trips/create.blade.php` with:

- hidden `partner_request_id`
- Partner locked to REQ partner (server-side; do not trust posted partner_id)
- source banner: Source Request `REQ-XXXXXX` with link back
- crew rows prefilled from PartnerRequestItems
- Image REQ: View Source Schedule link (secure; no filesystem paths)
- zero extracted rows: one empty crew row

POST flows through existing `TripController::store()`:

- optional `partner_request_id`
- inside transaction: lock REQ, re-check approved + `trips()->doesntExist()`
- force `partner_id = PartnerRequest.partner_id`
- existing Driver+Date grouping creates one Trip per group
- every generated Trip receives same `partner_request_id`
- duplicate conversion attempt returns friendly error; creates zero extra Trips
- assignment notifications only after commit via `TripAssignmentNotificationService`

When `partner_request_id` is absent, normal internal Trip behavior is unchanged.

## Decline

Pending only. Requires `decline_reason` (max 2000).

Sets:

- `declined`
- `declined_at`
- `declined_by`
- `decline_reason`

Creates 0 Trips. Items and image are preserved.

## Concurrency / Idempotency

Critical request mutations use:

- `DB::transaction()`
- `lockForUpdate()`

PartnerRequest must be re-queried INSIDE the transaction.

Protected paths:

- Partner manual edit
- Partner withdraw
- approve
- decline
- Trip store from approved REQ (duplicate conversion guard)

Approval guards:

- `status == pending`
- stale review fingerprint
- `trips()->exists() == false` (at approval time)

Trip store from REQ guards:

- REQ approved
- lock REQ inside transaction
- `trips()->doesntExist()` before creating

Double approval: no duplicate Trips. Double conversion: blocked with friendly message.

## Review Fingerprint

Stale internal review protection:

`App\Support\PartnerRequestReviewVersion`

Uses deterministic SHA-256 state fingerprint.

Includes PartnerRequest:

- `id`
- `status`
- `partner_updated_at`
- `updated_at`

Includes PartnerRequestItems ordered by ID:

- `id`
- `trip_date`
- `pick_up_time`
- `name`
- `phone`
- `phone_2`
- `address`
- `from_location`
- `to_location`
- `flight_number`
- `remarks`
- `sub_remark`
- `vessel_name_raw`
- `vessel_id`
- `driver_id`

Do NOT regress to timestamp-only optimistic locking.

## Operational Lineage — Phase 6

Partner-generated Trips behave as normal operational Trips while retaining REQ
lineage.

For `trip.partner_request_id != null`:

- `partner_id` is forced to originating REQ partner
- crafted Partner reassignment is ignored/rejected server-side
- internal Trip split preserves `partner_request_id`
- new split Trips preserve same Partner
- hard-delete is blocked
- cancellation is allowed
- REQ remains Approved after Trip status changes
- later operational edits do not modify PartnerRequestItems

Normal internal Trips (`partner_request_id` = null) remain unchanged.

## Trip Source Visibility

Internal Trip pages expose:

`TRP-XXXXXX`

For Partner-sourced Trips:

- Source Request: `REQ-XXXXXX` (internal link to Partner Request)

Trips index supports reference search:

- TRP reference
- REQ reference

Partner portal displays linked TRPs with:

- reference
- trip date
- operational status

Partner still receives no Driver/internal operational details.

## Driver API

Partner-generated approved Trips are ordinary Trip rows.

They participate in:

- `GET /api/home`
- `GET /api/schedule`
- `GET /api/trips`
- `GET /api/trips/{id}`

Additive API field:

- `trip_reference`

Legacy `/api/trips` compatibility:

Trip-level pickup/from/to/vessel assumptions are obsolete.

Current source:

`Trip` → `crews` → `vessel`

Legacy first-row keys are derived from first TripCrew when needed.

Full `crews` array remains available.

## Driver API Ownership

CRITICAL SECURITY RULE:

Authenticated Driver may only access/mutate Trips where:

`trip.driver_id == authenticated Driver.id`

This applies to:

- Trip detail
- status update
- crew update
- issues
- expenses

Cross-driver access must return inaccessible/404 behavior.

Never trust Trip ID alone under Sanctum authentication.

## Operational Notifications

Shared service:

`App\Services\TripAssignmentNotificationService`

Responsibilities:

- create driver Notification DB record
- send through `FirebaseNotificationService`
- fail safely on FCM errors

Used by:

- normal internal Trip creation
- bulk Trip creation
- Trip assignment
- applicable Trip split/update assignments
- Trip store from approved Partner Request

IMPORTANT TRANSACTION RULE:

Never send FCM before Trip DB transaction commits.

| Flow | Pattern |
| :--- | :--- |
| Store / update / bulk | collect Trip IDs inside transaction → commit → notify |
| Partner REQ approval | **zero** assignment notifications (no Trip yet) |
| Trip store from approved REQ | collect Trip IDs inside transaction → commit → notify |
| `assignDriver` | successful autocommit Trip update → notify |

Firebase failure must NOT roll back operational Trip creation or REQ approval.

Approved REQ with zero Trips sends zero Driver notifications.

| Trip state | Notifications |
| :--- | :--- |
| Unassigned | 0 assignment notifications |
| Later assignment | notification through normal assignment workflow |

## Reports / Operational Integration

Approved Partner Trips participate normally in:

- Trip Summary
- Driver Performance
- Trip Expenses
- dashboard Trip counts
- lifecycle/activity
- Trip Issues
- Trip Expenses
- Driver schedule/home/API

Pending/Declined/Withdrawn PartnerRequests create no Trip rows and therefore
must not appear in operational reporting/APIs/counts.

Approved PartnerRequests with zero linked Trips also must not appear in Driver
API, Trip reports, dashboard counts, expenses, issues, or Trip lifecycle until
actual Trip rows are created.

## Lifecycle

Partner-created Trips use existing:

`TripLifecyclePresenter`

No Partner-specific operational lifecycle.

Trip statuses remain:

- `unassigned`
- `assigned`
- `in_progress`
- `completed`
- `cancelled`

PartnerRequest approval status is independent of Trip operational status.

REQ stays Approved if linked TRP becomes:

- completed
- cancelled
- reassigned
- split

## Phase 7 — UI/UX ✅

Phase 7 presentation/usability improvement is complete across Phases 1–6.

Partner Request detail is read-only intake/decision. Operational conversion uses
the normal Trip create workflow after approval.

Primary areas delivered:

**Partner:** login, dashboard, New Request, manual/image flows, My Requests,
request detail with mobile status journey

**Internal:** Partner management, PartnerUsers, Partner Requests inbox,
read-only review, Approve/Decline, approved-awaiting-trip state, Trip create
prefill from approved REQ, REQ/TRP source display

Focus:

- consistent page hierarchy
- spacing
- typography
- responsive layouts
- mobile usability
- forms
- validation
- status presentation
- empty states
- confirmation/submission states
- accessible labels/controls
- searchable selects
- image preview experience
- responsive tables
- dark-mode compatibility
- touch targets

Do NOT change business rules/database architecture merely for UI redesign.

## Phase 8 — Final QA / Release ✅

Phase 8 is complete. Partner Portal is **READY FOR RELEASE REVIEW**.

Not merged. Not deployed. `main` is unchanged until explicit authorization.

Manual browser QA (passed): 375px / 768px / 1440px — Partner login/dashboard,
Manual/Image requests, My Requests, request states, internal queue/detail,
Approve/Decline, approved REQ → Trip Create, Partner Users, overflow, modals,
dark mode, accessibility/focus/touch. This was manual QA, not automated browser
evidence.

Remaining after this branch (out of Phase 8 implementation):

- draft PR when authorized
- staging/UAT
- merge to main only with authorization
- production deployment checklist

## Database Safety

Local/dev may contain restored production snapshot data.

NEVER run against it:

- `php artisan migrate:fresh`
- `php artisan migrate:refresh`
- `php artisan migrate:reset`
- `php artisan db:wipe`

When legitimate migrations exist:

- `php artisan migrate`

Automated tests remain isolated in sqlite `:memory:`.

## Important Files

**Partner portal:**

- `app/Http/Controllers/Partner/`
- `app/Models/PartnerUser.php`
- `app/Models/PartnerRequest.php`
- `app/Models/PartnerRequestItem.php`
- `resources/views/partner/`

**Partner OCR:**

- `app/Services/TextractService.php`
- `app/Services/PartnerScheduleParser.php`
- `app/Services/PartnerRequestImageExtractionService.php`

**Internal review:**

- `app/Http/Controllers/PartnerRequestReviewController.php`
- `app/Services/PartnerRequestApprovalService.php`
- `app/Support/PartnerRequestReviewVersion.php`
- `resources/views/partner-requests/`

**Operational integration:**

- `app/Models/Trip.php`
- `app/Models/TripCrew.php`
- `app/Http/Controllers/TripController.php`
- `app/Services/TripAssignmentNotificationService.php`

**Driver API:**

- `app/Http/Controllers/Api/`

**Routes:**

- `routes/web.php`
- `routes/api.php`

**Partner tests:**

- `tests/Feature/PartnerPortalPhase*.php`
- `tests/Feature/DriverTripApiSecurityTest.php`

## Agent Scope Discipline

For future Partner Portal tasks:

**READ PARTNER_PORTAL.md FIRST.**

Then inspect ONLY files directly relevant to the requested change.

Do not broadly search `app/`, `resources/`, `database/`, `tests/` unless
PARTNER_PORTAL.md does not contain the required information.

After each future phase:

update only the relevant state/contracts in PARTNER_PORTAL.md.

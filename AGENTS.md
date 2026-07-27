# AGENTS.md — AI Agent Workspace Instructions & Standards

Welcome AI Agent (Cursor, Copilot, Claude, GPT, etc.)!
This file defines mandatory workspace conventions for modifying or extending **ManageTrans**.

For full architecture, ER diagrams, permissions list, and API tables, see [PROJECT.md](./PROJECT.md).

---

## 1. Project Overview & Stack

* **Framework**: Laravel 12 (PHP 8.2+)
* **Database**: SQLite (default local), MySQL/PostgreSQL (prod)
* **Frontend**: Blade + Bootstrap (Velzon in `public/assets/`), Vite + Tailwind 4 (minimal)
* **Auth**: Session (`User` Admin/Staff) for web; Sanctum Bearer tokens (`Driver`) for mobile API
* **Integrations**: AWS Textract (OCR), Firebase Cloud Messaging (`kreait/firebase-php`)
* **Helpers**: `getSetting()`, `updateSetting()`, `getAppTimezone()`, `formatDate()` in `app/helpers.php`

---

## 2. Core Architecture

### 2.1 Trip parent–child model
* **`Trip`**: Dispatch header — `driver_id` (nullable), `partner_id` (nullable), `trip_date`, `title`, **`status`**.
* **`TripCrew`**: 1 Trip : N crews — `vessel_id`, pickup time, from/to locations, flight number, remarks, `sub_remark`, crew contact fields.
* **Status** is **only** on `trips.status`: `unassigned`, `assigned`, `in_progress`, `completed`, `cancelled`.
  * Constants live on `TripCrew` for compatibility; do not treat `trip_crews` as having an application status field.
* Title helper: `Trip::generateTripTitle($driverId, $tripDate)`.

### 2.2 Other core entities
* **`Driver`**: Mobile user; Internal/Outsourcing types; `total_kilometers`; FCM `notification_token`; documents, locations, daily activities.
* **`DailyActivity`**: Mileage/notes/images; every **10,000 km** → service reminder (in-app + FCM).
* **`Partner`**: Optional client on trips; `is_default` flag.
* **`Vessel`**: Linked through `trip_crews.vessel_id`, not directly on `trips`.
* **`TripExpense` / `TripExpenseType`**: Dynamic `input_types` (`amount`, `number`, `hours`, `text`, `image`).
* **`TripIssue` / `TripIssueType`**: Driver-reported issues.
* **`User`**: Web staff; role `1` Admin (all perms), `2` Staff (role matrix + overrides).
* **`settings` table**: No Eloquent model — use helpers only.

### 2.3 Eager loading (mandatory for lists)
```php
Trip::with(['driver', 'crews.vessel', 'partner']);
```
Load `tripExpenses.expenseType` / `tripIssues.issueType` when those are rendered.

---

## 3. Mandatory Coding Rules

### 3.1 Database & schema
* Never add or rely on status on `trip_crews` for app logic.
* Migrations must be reversible (`up` and `down`).
* Prefer nullable FKs where the UI allows unassigned trips (`driver_id`).

### 3.2 Audit trail
* Domain models use `App\Traits\LogsActivity`.
* Manual `ActivityLog` after driver API updates: `$model->saveQuietly()` first, then log, to avoid duplicate trait logs.

### 3.3 Web vs API routes
* **Web (`routes/web.php`)**: `auth` + `permission:slug` (e.g. `permission:view_trips`).
* **API (`routes/api.php`)**: `auth:sanctum` for driver endpoints.
* API JSON shape: `{ "success": true|false, "data": ..., "message": ... }`.

### 3.4 Permissions
* Middleware alias: `permission` → `CheckPermission`.
* Resolution: Admin → user override (`UserPermission`) → role default (`RolePermission`) → deny.
* Seed permissions manually: `php artisan db:seed --class=PermissionSeeder` (not in default `DatabaseSeeder`).
* Full permission list is in PROJECT.md.

### 3.5 Files & OCR
* Textract temp images: `Storage::disk('local')` under `temp/`; **delete after parse**.
* Public media (expenses, daily activities, photos): `public` disk → `asset('storage/...')`.

### 3.6 Notifications
* Use `FirebaseNotificationService` for driver push; also persist `Notification` rows when appropriate.
* Trigger points include trip assignment and 10,000 km milestones.

### 3.7 Scope discipline
* Match existing patterns (Velzon Blade, controller style, validation).
* Do not invent unrelated refactors or docs the user did not ask for.
* Do not commit unless the user explicitly requests a commit.

---

## 4. Useful Commands

```bash
php artisan migrate
php artisan db:seed --class=PermissionSeeder
composer test
composer run dev          # serve + queue + pail + Vite
```

---

## 5. Quick “do not break” checklist

- [ ] Status read/written on `trips.status` only
- [ ] Lists use eager loading (`driver`, `crews.vessel`, …)
- [ ] New web routes have `permission:...`
- [ ] New API routes use Sanctum + standard JSON envelope
- [ ] Activity logs not duplicated (`saveQuietly` when needed)
- [ ] Textract temp files cleaned up
- [ ] Migrations reversible

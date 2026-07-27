# AGENTS.md — AI Agent Workspace Instructions & Standards

Welcome AI Agent (Antigravity, Cursor, Copilot, Claude, GPT, etc.)!
This file defines mandatory workspace conventions, architecture rules, and implementation standards for modifying or extending the **ManageTrans** application.

---

## 📌 1. Project Overview & Stack Summary
* **Framework**: Laravel 12 (PHP 8.2+)
* **Database**: SQLite (default local), MySQL/PostgreSQL (prod)
* **Frontend**: Blade Templates, Bootstrap CSS, Vanilla JavaScript, Vite
* **Authentication**: Session-based for Web Staff, Laravel Sanctum Bearer Tokens for Mobile App Drivers
* **Integrations**: AWS Textract (OCR document parsing), Firebase Cloud Messaging (FCM Push Notifications)

---

## 🧱 2. System Architecture & Model Conventions

### 2.1 Core Entities & Relationships
* **Trip (`App\Models\Trip`)**:
  * Represents a dispatch assignment. Linked to `Driver` (nullable), `Partner` (nullable), and has many `TripCrew` records.
  * Status is stored directly in `trips.status` (`unassigned`, `assigned`, `in_progress`, `completed`, `cancelled`).
  * Has auto-generated title logic: `Trip::generateTripTitle($driverId, $tripDate)`.
* **TripCrew (`App\Models\TripCrew`)**:
  * Child model of `Trip` (1 Trip : N Crews). Contains vessel assignment (`vessel_id`), pickup time, pickup/drop location addresses, flight numbers, and remarks.
* **Driver (`App\Models\Driver`)**:
  * Mobile user account. Tracks `total_kilometers`, FCM `notification_token`, license documents, and live location (`DriverLocation`).
* **DailyActivity (`App\Models\DailyActivity`)**:
  * Tracks driver's daily mileage and notes. Automatically computes 10,000 km milestones for vehicle servicing.

---

## ⚠️ 3. Mandatory AI Coding Rules & Safeguards

### 3.1 Database & Schema Safety
* **Status Column**: Trip status belongs to `trips.status` (`unassigned`, `assigned`, `in_progress`, `completed`, `cancelled`). Do not add or read status from `trip_crews.status`.
* **Eager Loading**: Always eager-load related models (`Trip::with(['driver', 'crews.vessel'])`) in list controllers to avoid $N+1$ performance degradation.
* **Migrations**: Always write reversible migrations (`up` and `down` methods).

### 3.2 Audit Trail & Logging
* Application entities use `App\Traits\LogsActivity` to record user/driver actions (`created`, `updated`, `deleted`).
* If manually logging an `ActivityLog` (e.g. driver API action), call `$model->saveQuietly()` first to prevent duplicate standard log creation.

### 3.3 Web Routes vs. Mobile API Routes
* **Web Routes (`routes/web.php`)**:
  * Must be protected by `auth` middleware and appropriate permission middleware (`permission:view_trips`, `permission:edit_trips`, etc.).
* **API Routes (`routes/api.php`)**:
  * Driver endpoints must be protected by `auth:sanctum`.
  * Return standardized JSON responses: `{ "success": true/false, "data": ..., "message": ... }`.

### 3.4 Textract & File Handling
* Temporary OCR images must be saved locally via `Storage::disk('local')` and explicitly deleted after Textract parsing.
* Public assets (expenses, daily activities) must be uploaded to `public` disk and accessed via `asset('storage/...')`.

---

## 💻 4. Helpful Terminal Commands

```bash
# Run migrations
php artisan migrate

# Run tests
composer test

# Local development server with queue & Vite
composer run dev
```

For full details on project modules and API specifications, refer to [PROJECT.md](file:///Users/mohammedrabil/Desktop/manage-trans/PROJECT.md).

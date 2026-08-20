# ManageTrans — Transportation & Fleet Management

ManageTrans is a Laravel-based transportation management platform with:

* A **web admin panel** for dispatchers and staff (sessions + RBAC)
* A **mobile API** for drivers (Laravel Sanctum)
* **AWS Textract** for crew manifest OCR import
* **Firebase Cloud Messaging** for driver push notifications

## Key Features

* **Dashboard** — Operational overview for staff
* **Trips** — Parent trip + multiple crew legs; assign drivers/partners; cancel; status workflow (`unassigned` → `assigned` → `in_progress` → `completed` / `cancelled`)
* **OCR bulk import** — Upload manifests, review extraction, create trips in bulk
* **Drivers** — Internal/outsourcing types, documents, live map/GPS, mileage tracking
* **Daily activities** — Driver km/notes; 10,000 km service reminders
* **Vessels & partners** — Maritime vessels and client agencies
* **Expenses & issues** — Configurable types; driver submissions from the app
* **Reports** — Trip summary, expenses, driver performance
* **Staff & permissions** — Admin/Staff roles with granular permission overrides
* **Notifications** — In-app for staff and drivers; FCM for drivers
* **Activity logs** — Audit trail across domain models
* **Settings** — App name, branding, timezone, auth toggles
* **Public pages** — Privacy policy, contact
* **Installable app** — Works on phones and installs to the home screen or desktop, with an offline fallback page
* **Partner Portal** — Multi-user Partner login, manual/image transportation requests, internal review/approval, REQ→TRP traceability and operational Trip integration

## Tech Stack

| Layer | Stack |
| :--- | :--- |
| Backend | Laravel 12, PHP 8.2+ |
| Auth | Sessions (staff), Sanctum (drivers) |
| DB | SQLite (default) / MySQL / PostgreSQL |
| Frontend | Blade, Bootstrap (Velzon), Vite |
| Install | Web app manifest + service worker (installable, offline fallback) |
| Services | AWS Textract, Firebase FCM |

## Documentation

* [PROJECT.md](./PROJECT.md) — Architecture, ER overview, routes, API reference, permissions
* [AGENTS.md](./AGENTS.md) — Conventions for AI agents and contributors
* [PARTNER_PORTAL.md](./PARTNER_PORTAL.md) — Current Partner Portal architecture, contracts, and development state

## Installation

```bash
git clone <repository-url> manage-trans
cd manage-trans

composer install
npm install

cp .env.example .env
php artisan key:generate

# SQLite (default)
touch database/database.sqlite
# Or set DB_* in .env for MySQL/PostgreSQL

php artisan migrate
php artisan db:seed
php artisan db:seed --class=PermissionSeeder   # required for RBAC menus/routes
# php artisan db:seed --class=PartnerSeeder    # optional

npm run build
```

### Optional integrations

| Variable | Purpose |
| :--- | :--- |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_DEFAULT_REGION` | Textract OCR |
| `FIREBASE_CREDENTIALS_PATH` | Path to Firebase service account JSON (default: `storage/app/firebase-service-account.json`) |
| `FIREBASE_PROJECT_ID` | Firebase project id |

Default seeder creates a test web user (`test@example.com` via factory) plus settings and expense types. Run `PermissionSeeder` before relying on permission-gated pages.

## Running locally

```bash
composer run dev
```

Starts the PHP server, queue worker, Pail log viewer, and Vite.

## Installing it as an app

Staff can install ManageTrans like a native app — it opens full screen with its own home screen icon.

* **Android / desktop Chrome or Edge** — use the install button in the topbar, or the browser's own install prompt.
* **iPhone / iPad** — tap the install button and follow the Share → *Add to Home Screen* steps shown; Safari has no automatic prompt.

Installation requires HTTPS (`localhost` is exempt), so make sure production is served over TLS and that `/manifest.webmanifest` and `/sw.js` are reachable at the domain root.

After changing the logo in Settings, regenerate the home screen icons:

```bash
php artisan pwa:icons
```

Only static theme assets are cached offline. Pages themselves always come from the network, so nothing stale or session-specific is ever served; going offline shows a simple fallback page instead.

## Testing

```bash
composer test
```

## License

MIT — see [LICENSE](https://opensource.org/licenses/MIT) / project license file if present.

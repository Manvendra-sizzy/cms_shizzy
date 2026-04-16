# ShizzyCMS Project Guide

This file is a practical onboarding and maintenance guide for the whole project.

## 1) What This Project Is

`ShizzyCMS` is a Laravel 10 based internal operations platform that combines:

- CMS administration (users, org structure, integrations, logs)
- HRMS workflows (employees, attendance, leaves, payroll, reimbursements, documents)
- Projects management (clients, revenue, invoices, payments, reimbursements)
- Assets management (inventory, categories, assignment lifecycle)
- Systems management (system records, infrastructure resources, support scopes, logs)

It uses server-rendered Blade views and session-based auth (not an SPA).

## 2) Tech Stack

- Backend: PHP `^8.1`, Laravel `^10.10`
- Auth/API: Laravel Sanctum (available for API auth flows)
- PDF: `dompdf/dompdf`
- HTTP client: Guzzle
- Frontend build: Vite (`vite`, `laravel-vite-plugin`, `axios`)
- Database: MySQL-compatible schema via Laravel migrations

Core dependency files:

- `composer.json`
- `package.json`

## 3) Top-Level Directory Map

- `app/` - application code (controllers, models, services, middleware, module classes)
- `routes/` - route definitions (`web.php`, `api.php`, modular route files)
- `resources/views/` - Blade templates grouped by module (`hrms`, `projects`, `assets`, `systems`, `cms`)
- `database/migrations/` - schema history
- `database/seeders/` - seed data (roles, modules, users, etc.)
- `app/Console/Commands/` - custom Artisan commands
- `config/` - framework and app configuration
- `public/` - web root/static entry points
- `storage/` - logs, cache, uploaded/generated artifacts
- `tests/` - unit + feature tests

## 4) Architecture Overview

### Routing Model

Route loading is centralized in `app/Providers/RouteServiceProvider.php`:

- `routes/web.php` for auth/common pages
- `routes/api.php` for API endpoints
- module route files:
  - `routes/modules/admin.php`
  - `routes/modules/hrms_admin.php`
  - `routes/modules/employee.php`
  - `routes/modules/projects.php`
  - `routes/modules/assets.php`
  - `routes/modules/systems.php`

### Access Control

The app uses middleware aliases from `app/Http/Kernel.php`:

- `auth`, `guest`
- `role` (role-based page access)
- `module` (feature/module access gate)
- `system.access` (system-level permission checks)
- `cms.activity` (audit activity logging)

### Code Organization Pattern

- HTTP entry layer: controllers in `app/Http/Controllers/*`
- Domain/service logic: `app/Services/*`
- Data models: `app/Models/*` and `app/Models/Modules/*`
- Module metadata/config patterns: `app/Modules/*`
- Views: `resources/views/<module>/...`

## 5) Authentication and User Roles

Main auth endpoints are in `routes/web.php` and `App\Http\Controllers\AuthController`.

Key behavior:

- `/login`, `/logout`, `/dashboard`
- 2FA setup/challenge/settings routes under `/two-factor` and `/security/two-factor`
- Role-driven sectioning:
  - Admin area under `/admin/*`
  - HRMS admin under `/admin/hrms/*`
  - Employee self-service under `/employee/*`

Primary role constants are maintained on `App\Models\User`.

## 6) Module-by-Module Feature Map

### A) CMS / Admin (`/admin/*`)

Routes: `routes/modules/admin.php`

Main capabilities:

- Admin dashboard
- User management + admin reset of user 2FA
- Organization structure:
  - departments
  - teams
  - designations
- Operational tooling:
  - logs viewer
  - Zoho token generation
  - Zoho clients/invoices sync + invoice download

### B) HRMS Admin (`/admin/hrms/*`)

Routes: `routes/modules/hrms_admin.php`

Main capabilities:

- HR dashboard
- Employee lifecycle:
  - create/view
  - password reset
  - status changes
  - salary amendment + salary slips access
  - emergency contacts
  - uploaded documents
  - attendance lock management
- Documents management
- Leave policy management
- Leave approvals
- Reimbursement approvals (approve/reject/pay partial)
- Holiday calendar
- Payroll run creation + slip generation/download
- Attendance adjustments + attendance reporting

### C) Employee Portal (`/employee/*`)

Routes: `routes/modules/employee.php`

Main capabilities:

- Employee dashboard
- Profile + profile photo update
- Password update
- Leave apply + history/status
- Reimbursement apply + tracking
- Attendance punch in/out + history
- Downloads (documents/uploaded docs/salary slips)
- Policies and guidelines

### D) Projects (`/projects/*`)

Routes: `routes/modules/projects.php`

Main capabilities:

- Project listing and CRUD
- Client listing
- Category management
- Project status logs
- Team member management
- Finance radar and project finance view
- Revenue management:
  - streams
  - invoices
  - payments
  - reimbursements
- Zoho invoice open-link per project finance context

### E) Assets (`/assets/*`)

Routes: `routes/modules/assets.php`

Main capabilities:

- Asset categories CRUD
- Asset CRUD
- Assignment lifecycle:
  - assign
  - transfer
  - return

### F) Systems (`/systems/*`)

Routes: `routes/modules/systems.php`

Main capabilities:

- Systems CRUD
- Per-system documentation
- Development logs CRUD
- Support extension records
- Infrastructure resource catalog CRUD
- Support scope catalog CRUD

## 7) Data Model Overview (High-Level)

The migration history in `database/migrations/` shows these major domains:

- **User/Auth**
  - users, password resets, sanctum tokens, TOTP columns
- **CMS Access**
  - cms_modules, cms_user_modules
  - cms_roles, cms_user_roles, mapping tables
  - cms_activity_logs
- **HRMS**
  - employee_profiles, emergency contacts, change logs, uploaded docs
  - leave_policies, leave_requests
  - attendance_days, holidays, attendance reminders
  - payroll_runs, salary_slips, salary_components, salary_histories
  - reimbursement requests
  - hrms_policies_guidelines
- **Projects**
  - project_clients, project_categories, projects
  - project_status_logs, team members
  - revenue streams, invoices, payments, reimbursements
  - zoho_clients, zoho_invoices
- **Assets**
  - asset_categories, assets, asset_assignments
- **Systems**
  - support_scopes
  - infrastructure_resources
  - systems
  - system-infrastructure pivot
  - support_extensions
  - system_documentations
  - system_development_logs

## 8) Key Services and Background Logic

Important HRMS services live in `app/Services/HRMS/`:

- `AttendanceComplianceService`
- `AttendanceLeaveSummaryService`
- `CalendarService`
- `PayrollBreakdownService`

Custom console commands in `app/Console/Commands/` include:

- `hrms:attendance:compliance`
- `cms:logs:prune`
- `cms:telegram:daily-attendance`
- plus test/utility notification and leave recalculation commands

Scheduled in `app/Console/Kernel.php`:

- prune activity logs daily
- run attendance compliance daily
- send Telegram attendance digest daily

## 9) Local Development Setup

From project root:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
npm run dev
```

If `.env` already exists, skip copying it.

## 10) Common Development Workflows

### Add a New Feature to an Existing Module

1. Add route in the relevant `routes/modules/*.php`
2. Create/update controller in `app/Http/Controllers/<Module>/`
3. Implement domain logic in `app/Services/` (if complex)
4. Update models/migrations as needed
5. Add Blade view under `resources/views/<module>/`
6. Add/adjust tests in `tests/Feature` or `tests/Unit`

### Add a New Database Change

```bash
php artisan make:migration add_x_to_y_table
php artisan migrate
```

Then update model fillables/casts/relations and related forms/controllers.

### Run Tests and Code Quality

```bash
php artisan test
./vendor/bin/phpunit
./vendor/bin/pint
```

Use one test command path consistently in CI.

## 11) Debugging and Operational Tips

- Route/middleware issues: inspect `app/Providers/RouteServiceProvider.php` and `app/Http/Kernel.php`
- Access denied cases: verify `role`, `module`, and `system.access` middleware requirements
- Missing view/template: confirm path under `resources/views/...`
- Data inconsistency: inspect latest related migration + model casts/relations
- Scheduled jobs not running: verify server cron triggers Laravel scheduler:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## 12) Important Existing Documents

- `README.md` - base project intro and quick run info
- `PROPORTIONATE_LEAVE_CALCULATION.md` - leave balance business logic reference

## 13) Suggested Next Improvements (Optional)

- Expand this guide with sequence diagrams for leave/payroll flows
- Add a per-module ERD section (table-by-table)
- Add API docs if `/api` grows beyond auth scaffold usage
- Add CI/CD section with environment-specific deployment notes

---

If you want, this guide can be split into smaller docs (`docs/hrms.md`, `docs/projects.md`, `docs/architecture.md`) for easier long-term maintenance.

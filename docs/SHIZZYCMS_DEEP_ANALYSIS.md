# ShizzyCMS Deep Technical Analysis

## 1. Executive Summary

ShizzyCMS is a Laravel 10 server-rendered operations platform with six business surfaces: Admin/CMS, HRMS Admin, Employee Portal, Projects, Assets, and Systems. The core architecture is route-module driven, with middleware-based access controls (`role`, `module`, `system.access`) and mostly controller-centric business logic. HRMS and Projects contain the heaviest workflows (attendance, leave, payroll, reimbursements, invoicing). The implementation is functional and feature-rich, but has several structural risks: fat controllers, partial service extraction, inconsistent data modeling (especially HR org fields), permissive access logic in some paths, and migration patterns that can be destructive if mishandled.  

Overall: **usable in production with caution**, but future development should prioritize transactional safety, authorization hardening, and flow decomposition into services/actions.

---

## 2. Verified Tech Stack and Runtime Architecture

- **Backend:** Laravel 10 (`laravel/framework ^10.10`), PHP `^8.1` (`composer.json`)
- **Frontend:** Blade templates + Vite (`package.json`)
- **Auth:** Session auth + role routing; Sanctum scaffold present in API (`routes/api.php`)
- **DB:** MySQL-style schema through migrations (`database/migrations`)
- **Third-party libs:** Dompdf, Guzzle (`composer.json`)
- **Integrations:** Zoho Books OAuth/API, Telegram bot, SMTP mail (`config/services.php`, `config/mail.php`)
- **Execution style:** synchronous request/response dominates; queue default is `sync` (`config/queue.php`)

Runtime flow is standard Laravel:

1. Request enters route map (`RouteServiceProvider`)
2. Middleware chain (`web`, then aliases like `auth/role/module/system.access/cms.activity`)
3. Controller action executes (often with heavy logic)
4. Eloquent models persist/fetch data
5. Blade view renders (or file/PDF response returned)

---

## 3. Full Directory and Responsibility Map

- `app/Http/Controllers/` - primary application behavior; large business logic concentration
- `app/Http/Middleware/` - access control and audit logging
- `app/Services/` - partial domain extraction (HRMS summaries/compliance, Zoho, Telegram)
- `app/Modules/` - modular domain models/contracts/providers for HRMS/Projects/Assets/Systems/CMS
- `app/Models/` - shared platform models (`User`, CMS roles/modules, Zoho mirrors, org support models)
- `routes/` - root + API + module route files
- `resources/views/` - module-specific Blade interfaces
- `database/migrations/` - schema and some data backfills/seeds
- `database/seeders/` - base seed behavior
- `app/Console/Commands/` - automation/ops commands
- `app/Console/Kernel.php` - scheduler definitions

---

## 4. Route Architecture and Entry Points

### Route loading

`app/Providers/RouteServiceProvider.php` loads:

- `routes/api.php` with `api` middleware and `/api` prefix
- `routes/web.php` with `web` middleware
- each file in `routes/modules/*.php` with `web` middleware

### Core entry routes

- Auth/login/2FA: `routes/web.php`
- Module roots:
  - `/admin/*` (`routes/modules/admin.php`)
  - `/admin/hrms/*` (`routes/modules/hrms_admin.php`)
  - `/employee/*` (`routes/modules/employee.php`)
  - `/projects/*` (`routes/modules/projects.php`)
  - `/assets/*` (`routes/modules/assets.php`)
  - `/systems/*` (`routes/modules/systems.php`)

### API reality

- API currently minimal (`/api/user` with Sanctum auth) in `routes/api.php`
- This is not an API-first app; it is predominantly server-rendered.

---

## 5. Authentication, Roles, Permissions, and Middleware Flow

### Auth + 2FA flow

Implemented in `app/Http/Controllers/AuthController.php`:

- Login via email or lowercase codename
- Employee gate checks status (`inactive`, `former`, attendance lock)
- TOTP setup/challenge flow with session-based pending user id
- Trusted device cookie/cache handling
- Post-login redirect to admin/employee dashboard by role

### Middleware gates

- `EnsureRole` (`app/Http/Middleware/EnsureRole.php`) checks route role list
- `EnsureModuleAccess` (`app/Http/Middleware/EnsureModuleAccess.php`) calls `User::hasModule()`
- `EnsureSystemAccess` (`app/Http/Middleware/EnsureSystemAccess.php`) enforces system-specific visibility
- `LogCmsActivity` logs most authenticated actions to `cms_activity_logs`

### Permission model behavior (important)

- Modules are assigned via `cms_user_modules`
- Role scope extensions come from `cms_roles`, `cms_user_roles`, and related pivots
- `User::hasSystemAccess()` grants broad access to non-developer role holders; only developer role is narrowed by assignment (`app/Models/User.php`)

### Access control findings

- Strong: route groups consistently use role/module middleware
- Gap: employee portal routes do not require `module:hrms` unlike admin HRMS routes
- Gap: object-level policy checks are mostly route/middleware based; controller-level `authorize()`/Policies are limited

---

## 6. Module-by-Module Deep Analysis

## Admin/CMS

- **Routes:** `routes/modules/admin.php`
- **Controllers:** `AdminDashboardController`, `AdminUsersController`, `OrganizationStructureController`, `ToolsLogsController`, `Zoho*Controller`, `AuthController` (admin 2FA reset)
- **Models:** `User`, `CmsActivityLog`, `CmsModule`, `ZohoClient`, `ZohoInvoice`, org models
- **Services:** `ZohoTokenService`, `ZohoClientSyncService`, `ZohoInvoiceSyncService`, `ZohoBooksService`
- **Views:** `resources/views/cms/admin/*`
- **Flow:** admin logs in -> dashboard -> users/org/tools -> optional Zoho sync/download operations
- **CRUD:** users create + 2FA reset; org structure CRUD; logs read; Zoho sync trigger
- **Tables:** users, cms activity/modules/roles tables, zoho tables, org tables
- **Permissions:** `auth + role:admin + cms.activity`
- **Reports/downloads:** Zoho invoice PDF download
- **Risk:** `ZohoIntegrationController` flashes sensitive form fields (client secret/refresh token) back to session old input
- **Business purpose:** central governance + integrations + identity management

## HRMS Admin

- **Routes:** `routes/modules/hrms_admin.php`
- **Controllers:** `HREmployeesController`, `HRPayrollController`, `HRLeaveApprovalsController`, `HRAttendance*`, `HRReimbursementApprovalsController`, `HRDocumentsController`, others
- **Models:** `EmployeeProfile`, `LeaveRequest`, `LeavePolicy`, `AttendanceDay`, `PayrollRun`, `SalarySlip`, `ReimbursementRequest`, plus related logs/docs
- **Services:** `AttendanceLeaveSummaryService`, `AttendanceComplianceService`, `CalendarService`
- **Views:** `resources/views/hrms/hr/*`
- **Flow:** HR dashboard -> manage employees/leaves/attendance/payroll/reimbursements
- **CRUD:** broad full lifecycle; status/lock unlock/salary amendments included
- **Tables:** employee_profiles + many HRMS tables (leave/attendance/payroll/docs/reimbursements/history/logs)
- **Permissions:** `auth + role:admin + module:hrms + cms.activity`
- **Reports/downloads/PDF:** salary slip PDF, HR document download, attendance summaries
- **Risk highlights:**
  - employee creation flow has no wrapping transaction
  - `employee_id` generation uses max+1 pattern (race risk)
  - payroll generation is very heavy and synchronous
  - leave approval validates split sum but not paid-allowance constraints
- **Business purpose:** full HR backoffice processing and approvals

## Employee Portal

- **Routes:** `routes/modules/employee.php`
- **Controllers:** `EmployeeDashboardController`, `EmployeeAttendanceController`, `EmployeeLeavesController`, `EmployeeReimbursementsController`, `EmployeeDownloadsController`, profile/password/policy controllers
- **Models:** employee + leave + attendance + reimbursements + salary slips + docs
- **Services:** `CalendarService`, `AttendanceLeaveSummaryService`, `TelegramBotService`
- **Views:** `resources/views/hrms/employee/*`
- **Flow:** employee dashboard -> attendance/leave/reimbursements/downloads/profile/password
- **CRUD:** create leave/reimbursement, update profile photo/password, read own records
- **Tables:** attendance_days, leave_requests, employee_reimbursement_requests, salary_slips, docs tables
- **Permissions:** `auth + role:employee + cms.activity`
- **Downloads:** salary slips/docs/uploaded docs
- **Risk highlights:**
  - module gate absent for employee routes
  - geofence relies on client-sent coordinates (spoofable)
  - public file endpoint used heavily for receipts/images
- **Business purpose:** employee self-service operations

## Projects

- **Routes:** `routes/modules/projects.php`
- **Controllers:** `ProjectsController`, `ProjectFinancesController`, `ProjectRevenueController`, `ProjectStatusController`, `ProjectTeamController`, categories/clients controllers
- **Models:** `Project`, revenue stream/invoice/payment/reimbursement models, status/team/client models
- **Services/contracts:** Zoho invoice gateway, employee/Zoho client directories
- **Views:** `resources/views/projects/*`
- **Flow:** create project -> assign managers/team -> track status -> finance streams and radar
- **CRUD:** project/category/team/status/stream/invoice/payment/reimbursement (some UI surface partial)
- **Tables:** projects, project_status_logs, project_team_members, project_revenue_* tables, project_reimbursements, project_categories
- **Permissions:** `auth + module:projects + cms.activity`
- **Reports/downloads:** finance radar + Zoho invoice open/download handoff
- **Risk highlights:**
  - `ProjectFinancesController::openZohoInvoice` typehint references `ZohoInvoice` without import
  - revenue/invoice/payment routes exist but several are not exposed in current finance UI
  - project code generation also uses max+1 pattern
- **Business purpose:** project execution and revenue observability

## Assets

- **Routes:** `routes/modules/assets.php`
- **Controllers:** `AssetsController`, `AssetCategoriesController`, `AssetAssignmentsController`
- **Models:** `Asset`, `AssetCategory`, `AssetAssignment`
- **Services:** none substantial (controller-centric)
- **Views:** `resources/views/assets/*`
- **Flow:** create inventory -> assign -> transfer -> return
- **CRUD:** asset categories + assets full CRUD, assignment lifecycle actions
- **Tables:** asset_categories, assets, asset_assignments
- **Permissions:** `auth + module:assets + cms.activity`
- **Downloads/reports:** none major
- **Risk:** assignment state relies on application checks; no DB-level "single open assignment" constraint
- **Business purpose:** equipment/inventory control

## Systems

- **Routes:** `routes/modules/systems.php`
- **Controllers:** `SystemsController`, `SystemDocumentationController`, `SystemDevelopmentLogsController`, `SystemSupportExtensionsController`, infra/scope controllers
- **Models:** `System`, `SystemDocumentation`, `SystemDevelopmentLog`, `SupportExtension`, `InfrastructureResource`, `SupportScope`
- **Services:** mostly controller-model interactions (no deep service layer)
- **Views:** `resources/views/systems/*`
- **Flow:** create system linked to project -> maintain docs/logs/support timeline
- **CRUD:** systems + docs + logs + support extensions + infra/scope masters
- **Tables:** systems and related support/doc/log/pivot masters
- **Permissions:** `auth + module:systems + cms.activity`, then `system.access` on `/{system}` subtree only
- **Risk highlights:**
  - infra/support master routes are outside `system.access` constraint
  - visibility semantics rely on role-scope assumptions in `User`
- **Business purpose:** operational system catalog and lifecycle record

---

## 7. Database Domain Map and Table Relationships

### Core identity and access

- `users`
- `cms_modules`, `cms_user_modules` (module gating)
- `cms_roles`, `cms_user_roles`, `cms_user_role_projects`, `cms_user_role_systems` (role scopes)
- `cms_activity_logs` (audit trail)

### HRMS domain

- Employee core: `employee_profiles` (1:1 with users)
- Leave: `leave_policies`, `leave_requests`
- Attendance: `attendance_days`, `holidays`, `employee_attendance_reminders`
- Payroll: `payroll_runs`, `salary_slips`, `salary_histories`, `salary_components`
- Reimbursements: `employee_reimbursement_requests`
- Employee ops: emergency contacts/change logs/uploaded docs/policies guidelines/team pivot

### Projects domain

- `project_clients` (legacy), `project_categories`, `projects`
- `project_status_logs`, `project_team_members`
- Revenue chain: `project_revenue_streams` -> `project_revenue_invoices` -> `project_revenue_payments`
- `project_reimbursements`
- Zoho mirrors: `zoho_clients`, `zoho_invoices`

### Assets domain

- `asset_categories`, `assets`, `asset_assignments`

### Systems domain

- `systems`
- `support_scopes`, `infrastructure_resources`
- `system_infrastructure_resources` pivot
- `system_documentations`, `system_development_logs`, `support_extensions`

### Schema smells

- Many create migrations call `Schema::dropIfExists()` in `up()` (high-risk migration style)
- `employee_profiles` has parallel org modeling (`department/designation` text + ids + team pivot)
- mixed `join_date` and `joining_date` usage
- `zoho_invoices.project_id` is not FK to `projects.id` (semantic project code binding)
- string status fields without DB-level constraints

---

## 8. Request Lifecycle Examples for Key Features

## 1) Login + role redirect

- **Route:** `POST /login` (`login.submit`)
- **Controller:** `AuthController@login`
- **Validation:** `LoginRequest`
- **Logic:** credential attempt (email/codename), employee status checks, 2FA branch
- **Models/tables:** `users`, `employee_profiles`
- **Response:** redirect to `twofactor.*` or `admin.dashboard` / `employee.dashboard`

## 2) 2FA setup and challenge

- **Routes:** `/two-factor/setup`, `/two-factor`
- **Controller:** `showTwoFactorSetup`, `completeTwoFactorSetup`, `showTwoFactorChallenge`, `verifyTwoFactorChallenge`
- **Validation:** `code` digits validation, optional password in settings enable flow
- **Logic:** TOTP secret cache, QR creation, decrypt/verify, trusted device cookie+cache
- **Models/tables:** `users` (`two_factor_secret`, `two_factor_enabled_at`)
- **Response:** redirect to role dashboard

## 3) Employee creation (HR)

- **Route:** `POST /admin/hrms/employees`
- **Controller:** `HREmployeesController@store`
- **Validation:** inline `$request->validate(...)` with files and org ids
- **Logic:** create user -> generate employee_id -> upload files -> create profile -> team sync -> create salary history
- **Models/tables:** `users`, `employee_profiles`, `employee_profile_team`, `salary_histories`
- **Response:** redirect to employee profile with temp password

## 4) Leave application (employee)

- **Route:** `POST /employee/leaves`
- **Controller:** `EmployeeLeavesController@store`
- **Validation:** policy/date/half-day/reason
- **Logic:** working-day calculation via `CalendarService`, create pending leave, send admin mail + Telegram
- **Models/tables:** `leave_requests`, `leave_policies`, `users`
- **Response:** redirect to leaves index

## 5) Leave approval (HR)

- **Route:** `POST /admin/hrms/leave-approvals/{leaveRequest}/approve`
- **Controller:** `HRLeaveApprovalsController@approve`
- **Validation:** allocation array
- **Logic:** expected working days check -> allocation sum check -> approve with `approval_allocations`
- **Models/tables:** `leave_requests`
- **Response:** back with status

## 6) Attendance punch in/out (employee)

- **Routes:** `/employee/attendance/punch-in`, `/employee/attendance/punch-out`
- **Controller:** `EmployeeAttendanceController`
- **Validation:** lat/lng numeric
- **Logic:** working-day + time window + geofence + create/update attendance row
- **Models/tables:** `attendance_days`, `employee_profiles`
- **Response:** back with success/error

## 7) Attendance manual adjustment (HR)

- **Route:** `POST /admin/hrms/attendance-adjustments` (+ bulk)
- **Controller:** `HRAttendanceAdjustmentsController@store/bulkStore`
- **Validation:** employee/date/status
- **Logic:** status-based mutation in `applyAdjustment()` touching attendance and leave records
- **Models/tables:** `attendance_days`, `leave_requests`, `leave_policies`
- **Response:** redirect to adjustment page

## 8) Payroll run and slip generation (HR)

- **Routes:** `POST /admin/hrms/payroll`, `POST /admin/hrms/payroll/{run}/slips`
- **Controller:** `HRPayrollController@store/generateSlips`
- **Validation:** period + slips payload
- **Logic:** period summary via `AttendanceLeaveSummaryService`, fixed earning split, deductions, slip persistence, reimbursement attach, HTML artifact write, email notify
- **Models/tables:** `payroll_runs`, `salary_slips`, `employee_reimbursement_requests`, `attendance_days`, `leave_requests`
- **Response:** redirect to payroll run view

## 9) Reimbursement approval/pay process (HR)

- **Routes:** approve/reject/pay in `admin.hrms.reimbursement_approvals.*`
- **Controller:** `HRReimbursementApprovalsController`
- **Validation:** payment mode, amount, notes
- **Logic:** status transitions `pending -> approved/rejected -> partially_paid/paid`
- **Models/tables:** `employee_reimbursement_requests`
- **Response:** back with status

## 10) Project creation and status timeline

- **Route:** `POST /projects`
- **Controller:** `ProjectsController@store`
- **Validation:** client/category/type/billing/managers
- **Logic:** generate project code, map internal vs zoho client, create project, insert initial status log
- **Models/tables:** `projects`, `project_status_logs`, `zoho_clients`
- **Response:** redirect to project show

## 11) Project finance radar report

- **Route:** `GET /projects/finance-radar`
- **Controller:** `ProjectFinancesController@radar`
- **Validation:** month token sanitization
- **Logic:** eager load all non-internal projects + nested relations, compute per-project and global metrics, compare with Zoho invoice gateway result
- **Models/tables:** project revenue tables + zoho mirrors
- **Response:** radar blade with totals/alerts

## 12) Asset assignment lifecycle

- **Routes:** `POST /assets/{asset}/assign|transfer|return`
- **Controller:** `AssetAssignmentsController`
- **Validation:** employee/date/remarks
- **Logic:** enforce current assignment checks, mutate assignment rows, update asset status
- **Models/tables:** `asset_assignments`, `assets`
- **Response:** back with status/errors

## 13) Systems documentation + logs

- **Routes:** `/systems/{system}/documentation` and `/systems/{system}/development-logs`
- **Controllers:** `SystemDocumentationController`, `SystemDevelopmentLogsController`
- **Validation:** text/date/type/status payloads
- **Logic:** update single documentation record; create/delete log entries
- **Models/tables:** `system_documentations`, `system_development_logs`
- **Response:** back to system detail

## 14) Zoho sync + invoice download

- **Routes:** admin zoho sync endpoints and invoice download routes
- **Controllers:** `ZohoClientsController`, `ZohoInvoicesController`, `ProjectFinancesController`
- **Validation:** mostly service-level and simple request validation
- **Logic:** fetch pages via Zoho API + upsert mirror rows + download invoice PDF bytes
- **Models/tables:** `zoho_clients`, `zoho_invoices`
- **Response:** redirects with status or PDF response

---

## 9. Services, Jobs, Commands, Events, Notifications, and Scheduler

### Services

- HRMS:
  - `AttendanceLeaveSummaryService` (core leave/attendance/payroll math)
  - `AttendanceComplianceService` (warnings + lock logic)
  - `CalendarService` (working day logic)
  - `PayrollBreakdownService` (exists; low visible usage)
- Integrations:
  - `ZohoBooksService`, `ZohoTokenService`, sync services
  - `TelegramBotService`

### Console commands and scheduler

- Scheduled (`app/Console/Kernel.php`):
  - `cms:logs:prune`
  - `hrms:attendance:compliance`
  - `cms:telegram:daily-attendance`
- Manual:
  - `hrms:recalculate-leaves`
  - `cms:telegram:test`
  - `cms:send-test-email`

### Events/listeners

- `ProjectStatusChanged` -> `SyncProjectStatusTimeline` (`app/Providers/EventServiceProvider.php`)
- Listener writes audit log + optional Telegram status notification

### Mail

- Synchronous mail sends from controllers (leave/reimbursement/payroll notifications)
- Mailables use Queueable trait but not asynchronous by default due sync queue config

---

## 10. Integrations and External Dependencies

### Zoho

- Config from env in `config/services.php`
- OAuth token generation helper exists in admin tools
- Sync performed manually via admin-triggered routes
- Invoice PDF retrieval uses API bytes response

### Telegram

- Bot token + chat id in services config
- Notifications for leave/reimbursement/project status and daily digest

### Mail/SMTP

- Standard Laravel mail configuration
- Used for admin and employee operational notifications

---

## 11. Validation, Security, and Access-Control Review

### Positive controls

- Widespread route middleware usage
- CSRF-protected Blade forms
- Core forms validate payloads
- Ownership checks in key employee download/reimbursement views
- Activity logging middleware in protected areas

### Security findings

- `GET /files/{path}` is unauthenticated and broadly serves public-disk files (`PublicFilesController` + `routes/web.php`)
- Auth redirect constant (`RouteServiceProvider::HOME = /home`) does not align with existing route map
- 2FA remember-device flag partially undermined by unconditional `Auth::loginUsingId(..., true)` after challenge/setup
- System access logic grants broad visibility to non-developer role holders (`User::hasSystemAccess`)
- Sensitive Zoho form inputs are flashed back to session in OAuth helper UI

### Validation weaknesses

- Heavy inline controller validation, minimal FormRequest extraction
- Leave allocation sum is validated, but allowance sufficiency/entitlement enforcement is not strict in approval flow
- Some update flows have inconsistent uniqueness checks across controllers

---

## 12. Performance and Maintainability Review

### Performance risks

- Payroll preview/generation loops all employees with per-employee summary queries and synchronous email/file work
- Attendance compliance does repeated query loops per employee/day
- Finance radar aggregates large relation graphs in memory
- Many index pages use `get()` where pagination/chunking would scale better

### Maintainability risks

- Multiple fat controllers (HR payroll, employees, attendance adjustments, project finances)
- Duplicate query/calculation patterns across HRMS views/controllers
- Inconsistent module conventions (some module providers are placeholders)
- Legacy plus new schema paths coexist (client vs zoho client, join_date vs joining_date, single team id vs team pivot)

---

## 13. Incomplete, Suspicious, or Dead Code Findings

- `ProjectFinancesController` references `ZohoInvoice` type without import
- Project clients create/edit views exist, but routes/controller for those actions are absent (legacy leftovers)
- Revenue invoice/payment/reimbursement routes exist, but current finance view primarily exposes streams; some edit pages appear weakly reachable
- `hrms:recalculate-leaves` computes and logs but does not persist recalculations
- down migration for `salary_slip_id` addition drops FK but leaves column
- migration pattern with `dropIfExists` inside many `up()` methods is fragile and can be dangerous during migration replay/recovery

---

## 14. High-Risk Areas Before Future Development

1. Payroll generation flow (`HRPayrollController`)
2. Employee onboarding transaction boundary (`HREmployeesController@store`)
3. Public file serving endpoint exposure
4. Migration safety patterns (`dropIfExists` in `up`)
5. Project finance controller complexity and missing import issue
6. Role/module/system access semantics in `User`
7. Attendance adjustment data mutation behavior
8. Synchronous integration-heavy flows (mail/PDF/Zoho)
9. Schema consistency between HR org fields and pivots
10. Lack of uniform FormRequest/Policy architecture

---

## 15. Recommended Refactor Plan

### Phase 1 (Safety and correctness)

- Add transactions to multi-write critical flows (employee creation, payroll slip generation chunks)
- Fix missing import in `ProjectFinancesController`
- Harden `/files/{path}` with signed URLs or auth/authorization middleware
- Align redirect home behavior with actual route
- Fix migration down-method issues and add migration guardrails

### Phase 2 (Architecture cleanup)

- Extract action/service classes for payroll, onboarding, attendance adjustments, project finance metrics
- Introduce FormRequest classes for high-volume controllers
- Introduce Policies for object-level authorization (projects/assets/systems/reimbursements)
- Normalize status fields using enums/value objects

### Phase 3 (Scale and DX)

- Queue mail and heavy report/notification tasks
- Add indexes and constraints for concurrency-sensitive flows
- Add repository/query objects for radar/payroll summaries
- Reduce duplicated constants and stream-type logic into shared domain classes

---

## 16. Recommended Documentation Structure

- `docs/architecture/overview.md` - runtime architecture + module boundaries
- `docs/architecture/routing-and-middleware.md` - route map and permission flow
- `docs/domains/hrms.md` - HRMS entities, workflows, edge cases
- `docs/domains/projects.md` - project + finance model and radar logic
- `docs/domains/assets.md` and `docs/domains/systems.md`
- `docs/integrations/zoho.md` and `docs/integrations/telegram.md`
- `docs/operations/scheduler-commands.md`
- `docs/security/access-control-review.md`
- `docs/development/safe-change-guide.md`

---

## 17. Appendix: Important Files to Read First

### Top 10 files new developers should read first

1. `app/Providers/RouteServiceProvider.php`
2. `app/Http/Kernel.php`
3. `app/Http/Controllers/AuthController.php`
4. `app/Models/User.php`
5. `routes/modules/hrms_admin.php`
6. `app/Http/Controllers/HRMS/HRPayrollController.php`
7. `app/Services/HRMS/AttendanceLeaveSummaryService.php`
8. `app/Http/Controllers/Projects/ProjectFinancesController.php`
9. `app/Services/Zoho/ZohoBooksService.php`
10. `database/migrations` (chronological pass, especially 2026 series)

### Top 10 biggest risks/weak areas

1. Fat controller architecture in critical flows
2. Lack of transactions in multi-step writes
3. Public file endpoint access model
4. Destructive migration patterns
5. Mixed/legacy schema semantics in HRMS
6. Sync heavy operations in request cycle
7. Access model subtlety in systems role scope
8. Partial feature surfaces and orphaned views/routes
9. Inconsistent validation extraction strategy
10. Weak DB-level guarantees for race-prone identifiers

### Top 10 most valuable next improvements

1. Transactional hardening of onboarding and payroll
2. Policy-based authorization expansion
3. Secure file access via signed/authorized links
4. Queue integration for mail and heavy notifications
5. Introduce FormRequests and domain actions
6. Consolidate HR schema fields (`joining_date`, org fields)
7. Replace max+1 code generation with robust sequence strategy
8. Refactor finance radar to optimized query layer
9. Clean legacy/dead project client UI paths
10. Add architecture tests for route-method-view consistency

### One-paragraph mental model

ShizzyCMS is a middleware-gated, route-modular Laravel monolith where Admin configures users/org/integrations, HRMS executes employee operations (attendance/leave/payroll/reimbursements), Employees self-serve those workflows, Projects tracks delivery and finance streams (including Zoho-linked invoices), Assets handles inventory assignment transitions, and Systems maintains project-linked technical assets/documentation/support lifecycle; business behavior is mostly implemented directly in controllers with selective service extraction, so safe evolution depends on understanding route groups, middleware gates, and the specific controller-model write paths for each workflow.

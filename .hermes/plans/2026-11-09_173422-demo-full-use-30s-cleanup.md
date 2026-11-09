# Plan: Full demo use with 30-second cleanup

## Goal
Let testers use all relevant app features without registration, store demo records only in their isolated demo workspace, and erase that workspace when they exit or 30 seconds after exit is requested.

## Current context / assumptions
- Laravel project path: `D:\student-task-priority`; prior implementation uses MySQL database `student_task_priority`, Blade views, authenticated routes, and per-user Eloquent relations.
- Existing demo-mode plan: `.hermes/plans/2026-11-09_172812-guest-demo-mode.md`. This plan refines its scope; implementer should read it and inspect the current code before changing anything.
- “Semua fitur” means the normal tester-facing features: dashboard, task/course create/read/update/delete, task status, manual priority, filters, and AHP weight settings. Exclude account management, login/logout for real accounts, password changes, and any administrator-only functions from demo mode.
- “Setelah keluar atau hitung 30 detik setelah keluar bakal hilang semua” is interpreted as: pressing “Keluar mode uji” logs the browser out of demo access immediately, then retains the isolated workspace for a 30-second grace window before deletion. Any attempt to start demo again in the same browser during that grace window begins a fresh workspace; it must not restore prior tester data. The old workspace is erased at the 30-second deadline regardless of activity.
- Browser close/crash is different from pressing exit: workspace data can only be deleted after an exit signal or a server-side expiry/cleanup process. Plan includes an idle TTL safety net; ask user before execution if they intend 30 seconds after closing browser too (not reliably detectable without client heartbeat).
- “No database” means no application domain rows (`users`, `courses`, `tasks`, `priority_settings`) and preferably no DB session rows. Use a dedicated file/cache session store or a signed/encrypted cookie workspace, not Laravel's configured DB session driver for demo endpoints.

## Architecture / proposed approach
Use a server-side, session-keyed DemoWorkspace store isolated from Eloquent, with explicit demo-only controllers/routes so existing authenticated controllers remain untouched. On exit, immediately revoke demo access and set an immutable `purge_at = now + 30 seconds`; a scheduled Artisan cleanup command deletes expired workspaces, while every demo request also checks the expiry and purges stale state inline, so correctness does not depend only on scheduler timing. Use a dedicated non-database session/cache store for the demo identity and workspace; ensure a new entry never resumes a prior workspace.

## Step-by-step tasks

### 1. Inspect existing demo-plan and live route boundaries (2–5 min)
- Read `.hermes/plans/2026-11-09_172812-guest-demo-mode.md` and inspect `routes/web.php`, `bootstrap/app.php`, `config/session.php`, `config/cache.php`, `app/Console/Kernel.php` or Laravel 13 schedule bootstrap (`routes/console.php`), demo/auth controllers/services/views if already added, and existing `tests/Feature`.
- Run read-only baseline:
  ```bat
  cd /d D:\student-task-priority
  php artisan route:list --except-vendor
  php artisan test
  ```
- Expected: route list shows current protected normal pages; test suite passes. Record actual test count and existing demo changes; do not overwrite prior work.

### 2. Define workspace repository and expiry rules with RED tests (5 min)
- Add `tests/Feature/DemoExpiryTest.php` first.
- Write tests asserting: workspace initially valid; exit sets `purge_at` exactly 30 seconds ahead and revokes access immediately; at 29 seconds the old record can be swept but access is still revoked and new entry starts clean; at 30 seconds cleanup removes old workspace; multiple tester workspace IDs never collide.
- Use `Carbon::setTestNow()` / `travel()` to avoid real 30-second sleeps. Test against the dedicated non-DB store fake, not the project's live MySQL.
- Run `php artisan test --filter=DemoExpiryTest`; expected RED because expiry/store code is not implemented yet.

### 3. Implement isolated DemoWorkspace repository (5 min)
- Add `app/Services/DemoWorkspaceStore.php` with explicit operations only: `createFresh($id, $payload, $now)`, `find($id)`, `markExited($id, $purgeAt)`, `deleteExpired($now)`, and `delete($id)`.
- Back it with a dedicated filesystem-backed Laravel cache store outside MySQL (`storage/framework/cache/demo-workspaces`) or another existing non-DB store. Use unique cryptographically random workspace IDs; do not use predictable integer IDs or store participant identity.
- Store `status` (`active` or `exited`), `created_at`, `purge_at` nullable, and `data` payload. On exit, set status `exited`, purge_at `now()->addSeconds(30)`. Do not extend the purge deadline on page access.
- Implement only workspace persistence in this service. It must not reference Eloquent models, `DB`, or Auth user creation.
- Rerun `php artisan test --filter=DemoExpiryTest`; expected GREEN after repository implementation.

### 4. Add demo middleware and enforce expiry on every demo request (5 min)
- Add `app/Http/Middleware/EnsureActiveDemoWorkspace.php`.
- Middleware loads `demo_workspace_id` from the dedicated demo session/store; require status `active`. If missing or expired, delete stale data and redirect to `route('demo.start')` with a notice. If status is `exited`, immediately deny demo routes even though data is retained for the grace window.
- Keep normal `auth` middleware and all current protected routes unchanged.
- Register middleware alias in `bootstrap/app.php` only if route alias syntax is used. Avoid global middleware changes.
- Add tests for direct URL access after exit and after expiry; expected 302 to `demo.start`, and no demo data in returned page.

### 5. Isolate session identity from database session storage (5 min)
- Inspect actual `SESSION_DRIVER` and session configuration. Do not assume `.env` value or framework defaults.
- Add a dedicated demo session cookie/driver/store using the least invasive supported Laravel mechanism. Prefer signed/encrypted cookie-held demo workspace ID plus filesystem-backed workspace payload; mark cookie `HttpOnly`, `SameSite=Lax`, and use secure setting when HTTPS. Ensure no `sessions` table writes are triggered by demo-only endpoints if “no DB” is strict.
- If changing global `SESSION_DRIVER` would disrupt logged-in users, do not change it globally; make the demo entry/session architecture isolated (e.g. dedicated demo cookie middleware). Document any unavoidable framework session write, stop and ask user before violating strict no-database behavior.
- Tests: demo GET/POST must leave counts unchanged in `users`, `courses`, `tasks`, `priority_settings`, and `sessions` tables; verify with SQLite test DB or a safe MySQL read-only count snapshot before/after.

### 6. Map demo features to explicit routes and controller actions (5 min)
- Read existing app form field names and validation rules. Add dedicated demo controller(s), e.g. `app/Http/Controllers/DemoController.php`, `DemoTaskController.php`, `DemoCourseController.php`, `DemoSettingsController.php`; use one controller only if it stays readable.
- Implement demo equivalents for dashboard, courses CRUD, tasks CRUD, task status toggle, manual priority, course/status filters, and AHP settings. Reuse the same validation constraints and `PriorityCalculator` service where behavior matches, but serialize records into workspace data arrays rather than using Eloquent.
- Define demo-only named routes in `routes/web.php` under `/demo` and `EnsureActiveDemoWorkspace`: `demo.dashboard`, `demo.courses.index/store/update/destroy`, `demo.tasks.index/create/store/edit/update/destroy/status/priority`, and `demo.settings.update`; no demo route should invoke normal DB-mutating controllers.
- For each action, add failing HTTP feature test first; run targeted test (RED), implement route/controller/service, then targeted test (GREEN).

### 7. Provide full-feature demo interface (5 min)
- Add/adjust dedicated views `resources/views/demo/dashboard.blade.php`, `resources/views/demo/tasks/index.blade.php`, `resources/views/demo/tasks/form.blade.php`, `resources/views/demo/courses/index.blade.php`, and optionally `resources/views/demo/settings/index.blade.php`.
- Add a clear persistent banner: “MODE UJI — data sementara; tidak masuk database.” All links, form actions, and navigation must use only `demo.*` routes.
- Add a “Keluar mode uji” POST form to every demo page. Add a confirm dialog explaining: “Keluar sekarang akan mengunci sesi demo. Data dihapus otomatis dalam 30 detik.”
- On login and registration views, link to `route('demo.start')` with label “Masuk tanpa akun”; state that demo changes are temporary and will not transfer into a registered account.
- Use same look and responsive navigation as existing warm-editorial pages, but avoid touching unrelated real-user layout except adding the entry CTA.

### 8. Implement exit grace period and cleanup command (5 min)
- On `POST /demo/exit`, mark workspace exited with `purge_at=now()->addSeconds(30)`, clear/revoke demo cookie/session immediately, and redirect to login/landing. The tester cannot continue editing from the exited context.
- Add `app/Console/Commands/PurgeExpiredDemoWorkspaces.php` and register it in Laravel 13 `routes/console.php` or the project's existing scheduler registration. Command deletes records with `purge_at <= now()` from the non-DB store only.
- Schedule cleanup every minute; since scheduler precision is minute-granularity, also purge lazily during every demo start/request. Exact deletion can occur at the first cleanup tick after the deadline (up to about 60 seconds late) unless scheduler sub-minute scheduling is available and reliable.
- Add tests with frozen time for: exit revokes immediately, workspace exists before 30 seconds but inaccessible, cleanup deletes at/after deadline, and next demo start gets a new ID and clean fixture set.

### 9. Test “all features” and data isolation (5 min)
- Add focused feature tests for course add/edit/delete, task add/edit/delete, status, manual priority while preserving auto score, filters, settings weight sum validation, empty-course prerequisite, and no cross-workspace visibility.
- Add route test ensuring real dashboard and CRUD routes remain behind `auth`; demo entry does not call `Auth::login` and does not populate `users`.
- After each test group, assert application table counts unchanged for demo mutations. Ensure standard real-user auth/task/course tests remain green.
- Run `php artisan test`; expected all old and new tests pass. If app `sessions` uses DB, assert demo-specific requests do not add a session row before saying “no database writes.”

### 10. Blade compilation and timed cleanup verification (2–5 min)
Run:
```bat
cd /d D:\student-task-priority
php artisan view:clear
php artisan test
php artisan view:cache
php artisan route:list --path=demo
```
Expected: all tests pass; Blade cache says “Blade templates cached successfully”; route list contains only intended demo endpoints and exit path. Use time-travel tests for 29/30-second behavior; do not wait 30 seconds in a real test.

### 11. Final review and run instructions (2–5 min)
- Inspect `git diff --check` and changed files. Confirm normal authenticated routes were not weakened and no demo controller has a DB write path.
- Verify demo route behavior in a bounded local browser run at desktop and mobile widths; verify clicking exit redirects immediately and old cookie cannot reopen the workspace.
- Verify MySQL application table counts before and after demo use. Do not use test database counts as proof of MySQL behavior.
- Stop the temporary dev server and confirm it exited. Report the cache/session cleanup timing limit honestly and do not claim cleanup occurs exactly at 30 seconds unless an actual sub-minute scheduler/TTL store guarantees it.

## Tests / validation (TDD per task)
For each code slice: write one or more failing tests, run `php artisan test --filter=<name>` to confirm RED, implement the smallest code, rerun to confirm GREEN, then run full `php artisan test`. Use Carbon time travel for expiry logic. Use an isolated fake filesystem/cache store in tests; never mutate real participant records or use `migrate:fresh` against MySQL. Test live MySQL only with read-only table-count queries before/after demo entry, CRUD, exit, and expiry. Do not commit unless requested.

## Risks, tradeoffs, and open questions
- Exactly 30-second deletion requires a store with TTL precision or a sub-minute scheduler. Standard Laravel scheduler once per minute may delete later than 30 seconds; lazy cleanup only runs when a request arrives. Strict exactness should use filesystem/cache expiration supported at 30-second granularity or a dedicated short-interval worker. Recommended behavior: revoke access immediately at exit, set purge deadline +30s, delete on first subsequent expiry check; scheduler is backup only.
- Browser closed without pressing exit cannot be detected exactly. A short idle TTL/heartbeat could approximate, but “30 seconds after browser close” is not reliable and can erase data while a user is still testing during a long pause. Confirm whether 30s applies only after explicit exit (recommended) or after inactivity/close as well.
- If “all features” includes email/password/account management, file upload, or saving test data to a registered user, this changes security and persistence scope. Current plan excludes these and prohibits transferring demo records to real accounts.
- File-backed session/cache data is technically persisted on disk, though not in MySQL. If “not saved” means no persistent storage anywhere, use encrypted cookie-held data, but browser cookie size will limit feature-rich demo state. Preferred approach is ephemeral server-side cache with hard 30-second TTL, no DB, and clear disclosure.
- Multiple testers using one browser profile share its cookie/session. For simultaneous testers on one workstation, use separate browser profiles/incognito windows or unique demo-session cookies.
- Sample records need to be synthetic. Never insert names, emails, or real coursework data of article participants into demo fixtures.
- Users need a clear warning that demo data is not retained for the article study; any study measurement/feedback collection is a separate consent and data-governance feature, not included here.

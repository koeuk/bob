# Admin Panel — Implementation Plan

Plan for building the admin panel described in [admin.md](admin.md).

Current state (2026-04-24): nothing in the admin panel is built. The project has only the auth/settings skeleton. This doc sequences the work so each phase unblocks the next.

---

## Phase 0 — Prerequisites

Not code. Blocks everything else.

- [x] **Copy `.env`** and run `php artisan key:generate`. ✅
- [x] **Composer + npm deps installed.** ✅
- [x] **Decide: role model.** → Option A (single `role` enum on `users`, already in place). ✅
- [x] **Decide: admin SPA location.** → sub-app inside `resources/js/admin/`. ✅
- [x] **Full-project framework pivot** (overrides [admin.md](admin.md)):
  - **Vue 3** everywhere. React is removed entirely.
  - **Inertia-Vue** (`@inertiajs/vue3`) for data — no REST API SPA. Same pattern for admin and main app.
  - Session auth (Fortify), **not** Sanctum tokens. Admin protected by web middleware + role check.
  - UI: Tailwind + radix-vue (shadcn-vue style, hand-rolled primitives).
  - Charts: Chart.js via `vue-chartjs`.
  - Admin pages live under `resources/js/pages/admin/` alongside main app pages — unified structure.
  - [x] Torn down the earlier admin REST SPA (`resources/js/admin/`, `routes/api.php`, `admin.blade.php`) — to be rebuilt as Inertia pages.

---

## Phase 1 — Database foundation

⚠️ **BLOCKED**: PHP has `pdo_mysql` only; SQLite driver missing. Run `sudo apt install php8.4-sqlite3` OR provide MySQL creds. Nothing in this phase has started.

Migrations the admin panel depends on directly. No domain content tables yet.

- [ ] `personal_access_tokens` (Sanctum) — `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` then migrate.
- [ ] `bans` — user_id, banned_by, reason, expires_at (nullable = permanent), created_at.
- [ ] `reports` — reporter_id, reportable_id, reportable_type (polymorphic: Post/Comment/User), reason, status (pending/reviewed/resolved/dismissed), resolution_note, reviewed_by, reviewed_at, timestamps.
- [ ] `activity_logs` — admin_id, action, target_type, target_id, before (json), after (json), ip, user_agent, created_at.
- [ ] `pages` — slug (unique), title, body, status, updated_by, timestamps.
- [ ] `settings` — key (unique), value (json), group, updated_by, timestamps.
- [ ] *(conditional on Phase 0 decision)* `roles`, `permissions`, `role_permissions`.

Deliverable: `php artisan migrate:fresh` runs clean.

---

## Phase 2 — Domain tables for moderation targets

Admin panel needs something to moderate. Bare minimum for admin MVP:

- [ ] `posts` (+ `post_media` if images matter for moderation)
- [ ] `comments`
- [ ] `likes` (polymorphic — per [database-design.md](../database-design.md))

Defer `friendships`, `conversations`, `messages`, `notifications` — not moderated in the admin MVP. Add them when building the public app.

---

## Phase 3 — Eloquent models

- [ ] `User` — extend with: `bans()`, `reports()`, `isAdmin()`, `isModerator()`, `hasRole()`.
- [ ] `Ban`, `Report`, `ActivityLog`, `Page`, `Setting`.
- [ ] `Post`, `Comment`, `Like` (polymorphic `likeable`).
- [ ] Factories + seeders for each (needed for tests + dev data).

---

## Phase 4 — Auth & authorization

- [ ] **Sanctum install** — `config/auth.php` guards, `HasApiTokens` on `User`.
- [ ] **Role middleware** — `CheckRole` middleware registered as `role:admin|super_admin`. Reads the `role` enum on `users`.
- [ ] **Gates/Policies** — `PostPolicy`, `CommentPolicy`, `ReportPolicy`, `UserPolicy` — admin bypass via `Gate::before`.
- [ ] **Admin login flow** — `POST /api/admin/auth/login`: validates credentials, checks role, issues token with abilities based on role.
- [ ] **Activity logging** — trait or observer that writes to `activity_logs` on every admin-initiated mutation.

---

## Phase 5 — Admin API

Create `routes/api.php`, wire in `bootstrap/app.php`. All under `/api/admin` prefix, `auth:sanctum` + `role:*` middleware.

Build controllers in this order (each one shippable on its own):

1. [ ] **Auth** — login, logout, current user.
2. [ ] **Users** — list (paginated, searchable), show, update, delete, ban/unban, role assign (super_admin only), activity.
3. [ ] **Reports** — list, show, review/resolve/dismiss actions.
4. [ ] **Bans** — list, active, create, remove.
5. [ ] **Posts** — list, show, delete, flag.
6. [ ] **Comments** — list, delete.
7. [ ] **Dashboard** — stats, charts (time-series aggregates), recent-activity.
8. [ ] **Pages** — CRUD.
9. [ ] **Settings** — get all grouped, update (super_admin only).
10. [ ] **Activity logs** — paginated list.
11. [ ] *(conditional)* **Roles / permissions** — only if Phase 0 chose Option B.

Each controller: request validation class, resource response, policy check.

---

## Phase 6 — Admin as Inertia-Vue pages (not a separate SPA)

Lives alongside the main app under `resources/js/pages/admin/`. No axios, no Vue Router, no Pinia for auth. Shared Inertia props give us the current user.

- [ ] Admin routes in `routes/admin.php`, required from `routes/web.php`. Prefix `/admin`, middleware `['auth', 'verified', 'role:...']`.
- [ ] Rewrite all 10 admin controllers from JSON responses to `Inertia::render('admin/...', [...])`.
- [ ] Admin layout: `resources/js/layouts/admin-layout.vue` (sidebar + header, session user from `usePage()`).
- [ ] TanStack Table (Vue) for data grids.
- [ ] Chart.js + `vue-chartjs` for dashboard charts.
- [ ] Use Inertia `useForm` for all mutations — no fetch/axios.

---

## Phase 7 — Admin SPA features

Same order as Phase 5 controllers, each screen talking to its API:

1. [ ] Login page.
2. [ ] Dashboard — stat cards + two charts (Recharts) + recent reports + recent activity.
3. [ ] Users list + detail (ban modal, role-assign dropdown, activity log tab).
4. [ ] Reports queue + detail (resolve/dismiss with action).
5. [ ] Bans list.
6. [ ] Posts list + detail.
7. [ ] Comments list.
8. [ ] Static pages list + editor.
9. [ ] App settings.
10. [ ] Activity logs.

---

## Phase 8 — Testing

Pest feature tests per controller. Minimum per endpoint:

- [ ] Unauthenticated → 401.
- [ ] Authenticated non-admin → 403.
- [ ] Admin happy path → 200 + expected shape.
- [ ] Super-admin-only endpoints reject plain admin.
- [ ] Activity log row written for every mutation.

---

## Phase 9 — Polish (defer until core works)

- [ ] Bulk actions (select-many → delete/ban).
- [ ] CSV export for users, reports.
- [ ] Global search endpoint.
- [ ] WebSocket (Laravel Reverb) for real-time report/signup notifications.
- [ ] Responsive tablet layout pass.

---

## Suggested order of execution

Phase 0 → 1 → 3 (models for Phase 1 tables) → 4 → 5 (Auth + Users + Reports + Bans — the minimum useful admin) → 6 → 7 (login + dashboard + users + reports first) → 2 + rest of 3 → remaining Phase 5/7 → 8 → 9.

This gets a **usable admin panel for user/report moderation** live before the public-facing social features exist — useful for seeding and QA.

---

## Open questions

- Does the admin panel need i18n? Not mentioned in [admin.md](admin.md).
- What does "Export" really cover — CSV server-side streaming, or client-side from loaded rows?
- Are moderators allowed to delete posts, or only flag? The doc isn't explicit.
- Ban expiry — cron job to auto-lift, or lazy check on login?

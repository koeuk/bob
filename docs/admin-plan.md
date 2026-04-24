# Admin Panel — Implementation Plan

Plan for building the admin panel described in [admin.md](admin.md).

Current state (2026-04-24): Phases 0–7 implemented as Inertia-Vue. Phases 8 (tests) and 9 (polish) remain.

---

## Phase 0 — Prerequisites

Not code. Blocks everything else.

- [x] **Copy `.env`** and run `php artisan key:generate`. ✅
- [x] **Composer + npm deps installed.** ✅
- [x] **Decide: role model.** → Option A (single `role` enum on `users`, already in place). ✅
- [x] **Decide: admin SPA location.** → Inertia-Vue pages under `resources/js/pages/admin/`. ✅
- [x] **Full-project framework pivot** (overrides [admin.md](admin.md)):
  - **Vue 3** everywhere. React is removed entirely.
  - **Inertia-Vue** (`@inertiajs/vue3`) for data — no REST API SPA. Same pattern for admin and main app.
  - Session auth (Fortify), **not** Sanctum tokens. Admin protected by web middleware + role check.
  - UI: Tailwind + radix-vue (shadcn-vue style, hand-rolled primitives).
  - Charts: Chart.js via `vue-chartjs`.
  - Admin pages live under `resources/js/pages/admin/` alongside main app pages — unified structure.
  - [x] Torn down the earlier admin REST SPA (`resources/js/admin/`, `routes/api.php`, `admin.blade.php`).

---

## Phase 1 — Database foundation

- [x] `personal_access_tokens` (Sanctum).
- [x] `bans`.
- [x] `reports` (polymorphic).
- [x] `activity_logs`.
- [x] `pages`.
- [x] `settings`.

---

## Phase 2 — Domain tables for moderation targets

- [x] `posts` (+ soft deletes).
- [x] `comments` (+ soft deletes).
- [x] `likes` (polymorphic).

---

## Phase 3 — Eloquent models

- [x] `User` — `bans()`, `reportsFiled()`, `activityLogs()`, `isAdmin()`, `isModerator()`, `isSuperAdmin()`, `hasRole()`, `isBanned()`.
- [x] `Ban`, `Report`, `ActivityLog`, `Page`, `Setting`.
- [x] `Post`, `Comment`, `Like` (polymorphic `likeable`).
- [x] Factories for `User`, `Post`, `Comment`, `Ban`, `Report`, `Page`.
- [x] `DatabaseSeeder` seeds super_admin + moderator + dev fixture data.

---

## Phase 4 — Auth & authorization

- [x] Session auth via Fortify (no Sanctum tokens for admin).
- [x] **Role middleware** — `CheckRole` registered as alias `role:…`. Redirects unauthenticated users, 403s wrong role, auto-logs-out banned users.
- [x] **Gates/Policies** — `PostPolicy`, `CommentPolicy`, `ReportPolicy`, `UserPolicy`. `AuthServiceProvider` registers a `Gate::before` hook granting super_admin all abilities.
- [x] **Activity logging** — `ActivityLog::record()` called from every admin-initiated mutation.

---

## Phase 5 / 6 — Admin controllers (Inertia)

All admin routes in [routes/admin.php](../routes/admin.php), required from `routes/web.php`. Prefix `/admin`, middleware `['auth','verified','role:moderator,admin,super_admin']`. Super-admin-only endpoints (`settings.*`, role assignment) layered with `role:super_admin,admin`.

- [x] **Dashboard** — stats, 30-day signup/posts series, recent reports, recent activity.
- [x] **Users** — index (paginated, searchable, role/banned filters), show (profile, bans, reports against, activity), update, destroy, ban/unban, role assign.
- [x] **Reports** — index (tabs by status + counts), show, review/resolve/dismiss.
- [x] **Bans** — index (active filter), store, destroy (lift).
- [x] **Posts** — index, show (with comments + reports), destroy (soft), flag.
- [x] **Comments** — index, destroy.
- [x] **Pages** — index, create, store, edit, update, destroy.
- [x] **Settings** — index (grouped), update (super_admin/admin only).
- [x] **Activity logs** — index (action + admin filters).

Each controller uses request validation, `Inertia::render` for GET, `redirect()->back()->with('status', …)` for mutations. `HandleInertiaRequests` shares `flash.status` / `flash.error`.

---

## Phase 7 — Admin Inertia-Vue pages

- [x] [resources/js/layouts/admin-layout.vue](../resources/js/layouts/admin-layout.vue) — top bar with logo + pill nav + search/notifications/user, rounded icon rail sidebar. 2026-trending aesthetic: rounded cards, orange accent, soft shadows, sans-serif.
- [x] Dashboard ([pages/admin/dashboard.vue](../resources/js/pages/admin/dashboard.vue)) — pending-reports hero, figures, Chart.js bar chart, active-now card, recent reports + activity lists.
- [x] Users: [index.vue](../resources/js/pages/admin/users/index.vue), [show.vue](../resources/js/pages/admin/users/show.vue) — table with filters + inline actions, profile with ban modal + role dropdown.
- [x] Reports: [index.vue](../resources/js/pages/admin/reports/index.vue), [show.vue](../resources/js/pages/admin/reports/show.vue) — status tabs + queue, detail with resolve/dismiss forms.
- [x] Bans: [index.vue](../resources/js/pages/admin/bans/index.vue) — stats + active filter, lift inline.
- [x] Posts: [index.vue](../resources/js/pages/admin/posts/index.vue), [show.vue](../resources/js/pages/admin/posts/show.vue) — card grid + moderation actions.
- [x] Comments: [index.vue](../resources/js/pages/admin/comments/index.vue) — list + delete.
- [x] Pages: [index.vue](../resources/js/pages/admin/pages/index.vue), [edit.vue](../resources/js/pages/admin/pages/edit.vue) (shared create/edit).
- [x] Settings: [index.vue](../resources/js/pages/admin/settings/index.vue) — grouped key/value editor.
- [x] Activity logs: [index.vue](../resources/js/pages/admin/activity-logs/index.vue) — filterable list.

All pages use Inertia `useForm` / `router` for mutations — no fetch/axios. TanStack Table deferred; current tables are plain grids (adequate for MVP).

---

## Phase 8 — Testing *(not yet implemented)*

Pest feature tests per controller. Minimum per endpoint:

- [ ] Unauthenticated → redirects to login.
- [ ] Authenticated non-admin → 403.
- [ ] Admin happy path → Inertia response + expected shape.
- [ ] Super-admin-only endpoints reject plain admin.
- [ ] Activity log row written for every mutation.

---

## Phase 9 — Polish *(not yet implemented)*

- [ ] Bulk actions (select-many → delete/ban).
- [ ] CSV export for users, reports.
- [ ] Global search endpoint.
- [ ] WebSocket (Laravel Reverb) for real-time report/signup notifications.
- [ ] TanStack Vue Table swap-in for richer sorting/column controls.
- [ ] Click-outside / focus-trap for inline dropdowns.
- [ ] Responsive tablet layout pass.

---

## Open questions

- Does the admin panel need i18n? Not mentioned in [admin.md](admin.md).
- What does "Export" really cover — CSV server-side streaming, or client-side from loaded rows?
- Are moderators allowed to delete posts, or only flag? Current impl: moderators can delete (Phase 4 policies).
- Ban expiry — cron job to auto-lift, or lazy check on login? Current impl: lazy check via `isBanned()` at middleware time; historical bans keep `expires_at` in the past.

# Refactor & UX Plan — bob

Living document. Update the status boxes as phases land.

## Context

The backend serves **two** frontends from one codebase:

- **Inertia/Vue app** — `routes/web.php`, `routes/admin.php`, `routes/settings.php`
- **React SPA** (`bob-frontend` repo) — `routes/api.php`, Sanctum bearer tokens

That split produced the core problem: the same business rule is implemented
2–4 times, differing only in how the response is formatted.

### What is NOT broken (leave alone)

- **Migrations** — 19 clean one-per-table `create_*` files, already consolidated.
- **Role denormalization** — `users.role` mirrors the Spatie role and is kept in
  sync by `RoleAttachedEvent` / `RoleDetachedEvent` listeners in
  `AppServiceProvider`. Verified empirically; not a drift bug.
- **Policies / permissions / Scribe annotations** — in good shape.

### The actual mess: controller duplication

4,290 LOC of controllers. Same domain, implemented repeatedly:

| Domain | web | Api | Admin | Api/Admin |
|--------|----:|----:|------:|----------:|
| Posts | 117 | 321 | 136 | 176 |
| Users | – | 117 | 231 | 284 |
| Comments | 65 | 126 | 111 | 132 |
| Reports | 66 | 91 | 99 | 145 |

`assignRole` in `Admin/UsersController` vs `Api/Admin/UsersController` is
byte-for-byte identical except the return statement. Every fix must be made
twice, and the copies will drift.

---

## Phase 0 · Safety net — ✅ DONE

`pdo_sqlite` is not installed, so the suite (configured for sqlite `:memory:`)
errored on all 40 DB-touching tests. Rather than block on sudo, tests now run
against a dedicated **MySQL** database.

- `phpunit.xml` → `DB_CONNECTION=mysql`, `DB_DATABASE=bob_test`
- `UserFactory::PASSWORD` constant replaces the magic `'12345678'`; stale
  starter-kit tests hard-coded `'password'` and so could never authenticate
- `ExampleTest` asserted `/` returns 200, but `/` redirects guests to login

**Result: 41 passed, 0 failed.**

If `php8.4-sqlite3` is ever installed, prefer the faster in-memory sqlite —
see the commented block in `phpunit.xml`.

## Phase 1 · Dedup controller logic — ✅ DONE

All five steps landed, each as its own commit. Every admin business rule now
lives in exactly one Action under `app/Actions/`, with the controllers reduced
to request parsing and response formatting.

| Step | Domain | Controllers before → after |
|------|--------|---------------------------|
| 1 | Users | 515 → 317 |
| 2 | Reports | 244 → 166 |
| 3 | Comments | 243 → ~150 |
| 4 | Bans / Pages / Likes / Settings / ActivityLogs | 792 → ~480 |
| 5 | Posts (admin) | 312 → ~200 |

Coverage grew from 41 to 70 passing tests.

### Bugs and drifts found while extracting

- **Authorization gap (security).** The JSON bans endpoint never called
  `authorize('ban')`, so an admin could ban a user they do not outrank —
  a super admin, for instance. The gate now lives inside `Bans\IssueBan`,
  so every surface gets it. Covered by a regression test.
- **Settings guard** was enforced on the API but not the Inertia panel;
  `SaveSettings::assertAllowed()` now applies to both.
- **Avatar deletion never worked.** The old file was removed using
  `$user->avatar`, which the model accessor rewrites into a `/storage/...`
  URL, so it never matched a disk path — every replacement orphaned a file.
  Now uses `getRawOriginal('avatar')`.
- **API/panel feature drift** (unified to the superset): the JSON API did not
  select `avatar`, and could not upload an avatar or set a password.

### Not duplication — feature drift (left alone deliberately)

`App\Http\Controllers\PostsController` (Inertia, 117 LOC) and
`Api\PostsController` (321 LOC) are *not* copies. The Inertia one is an older,
much simpler implementation: no images, no feelings, no visibility, no
sharing, no reaction types, no `update`, and it does not send `PostLiked`
notifications. Bringing it to parity — or retiring the Inertia user-facing
app now that the React SPA exists — is a product decision, not a refactor.
**Flagged for a decision before Phase 5.**


**Step 1 · Users — ✅ DONE.** 8 Actions under `app/Actions/Users/`. Controllers
went 515 → 317 LOC and the logic now lives once. Two drifts found and unified
to the superset (additive, backward-compatible):

- the JSON API did not select `avatar`, so it could never render one
- the JSON API could not upload an avatar or change a password; the admin panel could

Also fixed a latent bug while extracting: the old avatar was deleted using
`$user->avatar`, which the model accessor rewrites into a `/storage/...` URL,
so it never matched a disk path and silently orphaned files. The Action now
deletes via `getRawOriginal('avatar')`.

Covered by `tests/Feature/Admin/UsersActionsTest.php` (8 characterization
tests). Suite: **49 passed**.


Pattern: one invokable Action per business rule. Controllers keep only
request parsing and response formatting.

```
app/Actions/Users/AssignRole.php          <- the ONE implementation
  Admin/UsersController@assignRole        -> back()->with('status', ...)
  Api/Admin/UsersController@assignRole    -> response()->json(...)
```

Order — smallest risk first, each its own commit so any step is revertable:

1. **Users** — assignRole, ban, unban, store, update, destroy (515 LOC pair)
2. **Reports** — review, resolve, dismiss (244)
3. **Comments** (243)
4. **Bans / Pages / Likes / Settings / ActivityLogs** (mechanical)
5. **Posts** — worst case, 4 implementations (750 LOC)

Each step: extract → repoint both controllers → `php artisan test` → smoke-test
both UIs → commit.

## Phase 2 · Moderation flow

Make `report → review → resolve/dismiss → ban` an explicit state machine
instead of ad-hoc status writes duplicated per controller.

## Phase 3 · Admin UI/UX pass

Consistency sweep across `/admin` (Users, Reports, Posts, Comments, Bans,
Settings): unified filter bar, empty/loading states, destructive-action
confirmations, consistent row actions.

## Phase 4 · Auth / onboarding

Align login · register · Google OAuth · 2FA · verification across the Inertia
app and the React SPA, which currently diverge.

## Phase 5 · React app UX

Feed, post detail, profile, chat, notifications — building on the 12 bugs
already fixed in the frontend audit.

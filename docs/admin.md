# Admin Panel

## Overview

A separate React SPA for platform administrators and moderators. Accesses dedicated admin API endpoints with role-based access control.

## Tech

- **Framework:** React 19 + TypeScript
- **UI:** Tailwind CSS + shadcn/ui (or similar admin component library)
- **Tables:** TanStack Table for data grids
- **Charts:** Recharts or Chart.js for analytics
- **Auth:** Sanctum Token + role/permission middleware
- **Routing:** React Router v7

## Auth & Authorization

### Login Flow
```
Admin → Login → POST /api/admin/auth/login
      → Validate credentials + check role (admin/moderator/super_admin)
      → Return token with admin abilities
      → Redirect to dashboard
```

### Role Hierarchy

| Role | Permissions |
|------|------------|
| **super_admin** | Everything — manage admins, app settings, danger zone |
| **admin** | Manage users, posts, reports, bans, pages, view logs |
| **moderator** | Review reports, moderate posts/comments, temporary bans |

### Middleware
```php
// Admin routes protected by:
Route::middleware(['auth:sanctum', 'role:admin|super_admin'])->group(...)
Route::middleware(['auth:sanctum', 'role:super_admin'])->group(...)  // sensitive
```

## API Endpoints

All prefixed with `/api/admin/`

### Auth
```
POST   /auth/login               → Admin login
POST   /auth/logout              → Logout
GET    /auth/user                → Current admin info + permissions
```

### Dashboard
```
GET    /dashboard/stats          → Overview stats (users, posts, reports)
GET    /dashboard/charts         → Time-series data for charts
GET    /dashboard/recent-activity → Recent admin actions
```

### User Management
```
GET    /users                    → List users (paginated, searchable, filterable)
GET    /users/{id}               → User detail (profile, posts, reports against them)
PUT    /users/{id}               → Edit user
DELETE /users/{id}               → Delete user
POST   /users/{id}/ban           → Ban user (temporary/permanent)
DELETE /users/{id}/ban           → Unban user
PUT    /users/{id}/role          → Assign role (super_admin only)
GET    /users/{id}/activity      → User activity log
```

### Post Management
```
GET    /posts                    → List all posts (filterable by status, type, date)
GET    /posts/{id}               → Post detail with comments
DELETE /posts/{id}               → Delete post
PUT    /posts/{id}/flag          → Flag post
```

### Comment Management
```
GET    /comments                 → List comments (filterable)
DELETE /comments/{id}            → Delete comment
```

### Report Management
```
GET    /reports                  → List reports (filterable by status, type)
GET    /reports/{id}             → Report detail
PUT    /reports/{id}/review      → Mark as reviewed
PUT    /reports/{id}/resolve     → Resolve (with action taken)
PUT    /reports/{id}/dismiss     → Dismiss report
```

### Ban Management
```
GET    /bans                     → List all bans
GET    /bans/active              → Currently active bans
POST   /bans                     → Create ban
DELETE /bans/{id}                → Remove ban
```

### Static Pages
```
GET    /pages                    → List pages
POST   /pages                    → Create page
GET    /pages/{id}               → Get page
PUT    /pages/{id}               → Update page
DELETE /pages/{id}               → Delete page
```

### App Settings (super_admin)
```
GET    /settings                 → All settings grouped
PUT    /settings                 → Update settings
```

### Activity Logs
```
GET    /activity-logs            → Admin action logs (paginated, filterable)
```

### Roles & Permissions (super_admin)
```
GET    /roles                    → List roles
GET    /roles/{id}/permissions   → Role permissions
PUT    /roles/{id}/permissions   → Update role permissions
GET    /admins                   → List admin users
POST   /admins/{id}/role        → Assign admin role
DELETE /admins/{id}/role        → Remove admin role
```

## Dashboard Widgets

```
┌─────────────────────────────────────────────────────────┐
│  Dashboard                                              │
├──────────┬──────────┬──────────┬──────────┬────────────┤
│  Total   │  New     │  Active  │  Pending │  Posts     │
│  Users   │  Today   │  Now     │  Reports │  Today     │
│  12,450  │  +128    │  1,203   │  23      │  +856      │
├──────────┴──────────┴──────────┴──────────┴────────────┤
│                                                         │
│  [User Growth Chart - 30 days]    [Posts Chart - 7 days]│
│                                                         │
├─────────────────────────┬───────────────────────────────┤
│  Recent Reports         │  Recent Admin Activity        │
│  ┌─────────────────┐    │  ┌─────────────────────────┐  │
│  │ spam - post #123│    │  │ Admin banned user #456  │  │
│  │ nudity - img #45│    │  │ Mod resolved report #78 │  │
│  │ harassment - ... │    │  │ Admin deleted post #901 │  │
│  └─────────────────┘    │  └─────────────────────────┘  │
└─────────────────────────┴───────────────────────────────┘
```

## Project Structure

```
src/
├── main.tsx
├── App.tsx
├── router.tsx
│
├── api/
│   ├── client.ts                → Axios + admin token
│   ├── auth.ts
│   ├── dashboard.ts
│   ├── users.ts
│   ├── posts.ts
│   ├── comments.ts
│   ├── reports.ts
│   ├── bans.ts
│   ├── pages.ts
│   ├── settings.ts
│   ├── roles.ts
│   └── activity-logs.ts
│
├── pages/
│   ├── login.tsx
│   ├── dashboard.tsx
│   ├── users/
│   │   ├── list.tsx             → Users table
│   │   └── detail.tsx           → User detail + actions
│   ├── posts/
│   │   ├── list.tsx             → Posts table
│   │   └── detail.tsx           → Post detail
│   ├── comments/
│   │   └── list.tsx
│   ├── reports/
│   │   ├── list.tsx             → Reports queue
│   │   └── detail.tsx           → Report review
│   ├── bans/
│   │   └── list.tsx
│   ├── pages/
│   │   ├── list.tsx             → Static pages
│   │   └── edit.tsx             → Page editor
│   ├── settings/
│   │   └── index.tsx            → App settings
│   ├── roles/
│   │   └── index.tsx            → Roles & permissions
│   └── activity-logs/
│       └── index.tsx            → Audit log
│
├── components/
│   ├── layout/
│   │   ├── admin-layout.tsx     → Sidebar + header
│   │   ├── admin-sidebar.tsx    → Navigation
│   │   └── admin-header.tsx     → Top bar
│   ├── data-table/
│   │   ├── data-table.tsx       → Reusable table
│   │   ├── columns.tsx          → Column definitions
│   │   ├── filters.tsx          → Filter controls
│   │   └── pagination.tsx       → Pagination
│   ├── charts/
│   │   ├── user-growth.tsx
│   │   ├── posts-chart.tsx
│   │   └── reports-chart.tsx
│   ├── modals/
│   │   ├── ban-user-modal.tsx
│   │   ├── delete-confirm.tsx
│   │   └── report-action.tsx
│   └── ui/                      → Shared components
│
├── stores/
│   └── auth-store.ts
│
├── hooks/
│   ├── use-auth.ts
│   └── use-permissions.ts       → Check role/permission
│
├── lib/
│   ├── utils.ts
│   └── permissions.ts           → Permission constants
│
└── types/
    └── index.ts
```

## Sidebar Navigation

```
Dashboard
─────────────
Users
  └── All Users
  └── Banned Users
Content
  └── Posts
  └── Comments
Moderation
  └── Reports
  └── Bans
Content Management
  └── Static Pages
─────────────
Settings (super_admin)
  └── App Settings
  └── Roles & Permissions
  └── Activity Logs
```

## Key Features

| Feature | Description |
|---------|-------------|
| **Data Tables** | Sortable, filterable, searchable with pagination |
| **Bulk Actions** | Select multiple items → delete, ban, etc. |
| **Real-time Updates** | WebSocket for new reports, user signups |
| **Export** | CSV/Excel export for user data, reports |
| **Search** | Global search across users, posts, reports |
| **Dark Mode** | Admin panel theme toggle |
| **Responsive** | Works on tablet for on-the-go moderation |
| **Audit Trail** | Every admin action logged with before/after |

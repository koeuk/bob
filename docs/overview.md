# Bob - Social Media Platform Overview

## Vision

A Facebook-like social media platform supporting multiple clients from a single Laravel API backend.

## Platforms

| Platform | Type | Auth | Description |
|----------|------|------|-------------|
| [Web](./web.md) | Inertia.js (SSR) | Session/Cookie | Main website with full features |
| [Frontend SPA](./frontend-spa.md) | React SPA | Sanctum Token | Standalone single-page app |
| [Mobile](./mobile.md) | React Native / Flutter | Sanctum Token | iOS & Android apps |
| [Admin](./admin.md) | React SPA | Sanctum Token + Role | Admin dashboard for moderation |

## Tech Stack

### Backend (Shared)
- **Framework:** Laravel 12 (PHP 8.2+)
- **Auth:** Laravel Fortify + Sanctum
- **Roles:** Spatie Laravel Permission
- **Database:** MySQL
- **Queue:** Laravel Queue (jobs, notifications)
- **Storage:** Laravel Filesystem (local / S3)
- **Real-time:** Laravel Reverb / Pusher (WebSocket)
- **Push Notifications:** Firebase Cloud Messaging (FCM)

### API Structure
```
routes/
├── web.php          → Inertia web routes (session auth)
├── api.php          → Public API v1 (sanctum token auth)
├── api_admin.php    → Admin API (sanctum + role middleware)
├── channels.php     → WebSocket broadcast channels
└── console.php      → Artisan commands
```

### API Versioning
```
/api/v1/posts
/api/v1/users
/api/v1/conversations
...
```

## Core Features

| Feature | Web | SPA | Mobile | Admin |
|---------|-----|-----|--------|-------|
| Register / Login | x | x | x | x |
| Two-Factor Auth | x | x | x | x |
| News Feed | x | x | x | - |
| Create Post | x | x | x | - |
| Like / React | x | x | x | - |
| Comment / Reply | x | x | x | - |
| Share | x | x | x | - |
| Stories | x | x | x | - |
| Messaging | x | x | x | - |
| Friends / Follow | x | x | x | - |
| Notifications | x | x | x | x |
| Push Notifications | - | - | x | - |
| Profile / Settings | x | x | x | - |
| Search | x | x | x | - |
| Bookmarks | x | x | x | - |
| Manage Users | - | - | - | x |
| Manage Posts | - | - | - | x |
| Reports / Moderation | - | - | - | x |
| Ban Users | - | - | - | x |
| App Settings | - | - | - | x |
| Static Pages | - | - | - | x |
| Activity Logs | - | - | - | x |
| Analytics | - | - | - | x |

## Database

See [database-design.md](../database-design.md) for full schema (38 tables).

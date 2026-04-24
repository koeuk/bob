# API Platform

## Overview

The Laravel HTTP API is the single backend contract consumed by every non-Inertia client: the [mobile app](./mobile.md), the [admin dashboard](./admin.md), and any standalone web SPA. This document describes the API surface itself — auth, conventions, endpoints, real-time channels — independent of any specific client implementation.

For the Inertia-based web app (which does not use this API), see [web.md](./web.md).

## Base URL

```
https://<host>/api/v1
```

All endpoints are prefixed with `/api/v1/`. Admin-only endpoints live under `/api/v1/admin/` and are declared in `routes/api_admin.php`.

## Tech

- **Framework:** Laravel 12 (PHP 8.2+)
- **Auth:** Laravel Fortify + Sanctum
- **Roles:** Spatie Laravel Permission (admin endpoints)
- **Real-time:** Laravel Reverb / Pusher (WebSocket broadcast)
- **Push:** Firebase Cloud Messaging (mobile devices)
- **Queue:** Laravel Queue (notifications, jobs)
- **Storage:** Laravel Filesystem (local / S3)

## Auth Flow

Two authentication modes are supported, both via **Laravel Sanctum**.

### Token Mode (Mobile & Cross-Origin Clients)

Used by the mobile app and any client hosted on a different origin.

```
1. POST /api/v1/auth/login       → { token: "xxx", user: {...} }
2. Client stores token securely  (Keychain / Keystore / memory)
3. All requests send:            Authorization: Bearer xxx
4. On 401 → redirect to login
```

### SPA Session Mode (Same-Origin Browsers)

Used by a browser SPA served from the same top-level domain as the API (subdomain is OK). Cookies carry the session — no token is stored in JS.

```
1. GET  /sanctum/csrf-cookie     → sets XSRF-TOKEN cookie
2. POST /api/v1/auth/login       → session cookie + user payload
3. Subsequent requests send cookie + X-XSRF-TOKEN header automatically
```

Clients pick one mode; the server accepts both on the same routes.

## Conventions

- **Format:** JSON request/response. `Accept: application/json` is required.
- **Errors:** Laravel's standard validation envelope — `{ message, errors: { field: [...] } }` with HTTP 422 for validation, 401 for unauth, 403 for forbidden, 404 for not found.
- **Pagination:** cursor-based on feeds/lists, `?cursor=...&per_page=20`; offset pagination on admin listings.
- **Filtering:** query params (`?type=image&user_id=42`); complex filters documented per endpoint.
- **Media:** multipart/form-data on create endpoints; URLs returned in responses.
- **Rate limiting:** per-user and per-IP throttles on auth + write endpoints.

## API Endpoints

### Auth

```
POST   /auth/register             → Register
POST   /auth/login                → Login → returns token + user
POST   /auth/logout               → Logout → revoke token
POST   /auth/forgot-password      → Send reset email
POST   /auth/reset-password       → Reset password
POST   /auth/verify-email         → Verify email
POST   /auth/two-factor/enable    → Enable 2FA
POST   /auth/two-factor/confirm   → Confirm 2FA
POST   /auth/two-factor/disable   → Disable 2FA
POST   /auth/two-factor/challenge → Verify 2FA code on login
GET    /auth/user                 → Get current user
```

### Posts

```
GET    /posts                    → Feed (paginated, filterable)
GET    /posts/{id}               → Single post
POST   /posts                    → Create post (multipart for media)
PUT    /posts/{id}               → Update post
DELETE /posts/{id}               → Delete post
POST   /posts/{id}/like          → Like/react
DELETE /posts/{id}/like          → Remove like
POST   /posts/{id}/share         → Share post
POST   /posts/{id}/bookmark      → Bookmark
DELETE /posts/{id}/bookmark      → Remove bookmark
```

### Comments

```
GET    /posts/{id}/comments      → List comments (paginated)
POST   /posts/{id}/comments      → Create comment
PUT    /comments/{id}            → Update comment
DELETE /comments/{id}            → Delete comment
POST   /comments/{id}/like       → Like comment
DELETE /comments/{id}/like       → Remove like
```

### Stories

```
GET    /stories                  → Stories feed
POST   /stories                  → Create story
DELETE /stories/{id}             → Delete story
POST   /stories/{id}/view        → Mark as viewed
```

### Users & Profiles

```
GET    /users/{id}               → User profile
PUT    /user/profile             → Update own profile
PUT    /user/password            → Change password
GET    /users/{id}/posts         → User's posts
GET    /users/{id}/friends       → User's friends
```

### Friends

```
GET    /friends                  → My friends
GET    /friends/requests         → Pending requests
POST   /friends/request/{id}     → Send request
POST   /friends/accept/{id}      → Accept
POST   /friends/reject/{id}      → Reject
DELETE /friends/{id}             → Remove friend
```

### Follow

```
POST   /follow/{id}              → Follow user
DELETE /follow/{id}              → Unfollow
GET    /followers                → My followers
GET    /following                → Who I follow
```

### Messaging

```
GET    /conversations                    → List conversations
POST   /conversations                    → Create conversation
GET    /conversations/{id}               → Get conversation with messages
POST   /conversations/{id}/messages      → Send message
PUT    /conversations/{id}/read          → Mark as read
```

### Notifications

```
GET    /notifications            → List (paginated)
PUT    /notifications/read-all   → Mark all as read
PUT    /notifications/{id}/read  → Mark one as read
```

### Search

```
GET    /search?q=term&type=users|posts|tags  → Search
```

### Block

```
POST   /block/{id}               → Block user
DELETE /block/{id}               → Unblock
GET    /blocked                  → Blocked users list
```

### Devices (Mobile)

```
POST   /devices                  → Register device + FCM token
PUT    /devices/{id}             → Update FCM token
DELETE /devices/{id}             → Remove device
```

### Media Upload

```
POST   /upload                   → Upload media (chunked for large files)
```

## Real-time

Broadcast channels are declared in `routes/channels.php` and authorized via Sanctum.

- **Transport:** Laravel Reverb (or Pusher) over WebSocket
- **Auth:** client calls `/broadcasting/auth` with its Sanctum token/cookie
- **Channels:**
  - `private-conversation.{id}` → new messages, typing whispers
  - `private-user.{id}` → personal notifications
  - `presence-online` → online/offline status

## Route Files

```
routes/
├── api.php          → Public API v1 (sanctum)
├── api_admin.php    → Admin API (sanctum + role middleware)
├── channels.php     → WebSocket broadcast channels
└── web.php          → Inertia web routes (session auth — not this API)
```

## Client Comparison

| Client | Auth Mode | Transport | Doc |
|--------|-----------|-----------|-----|
| Web (Inertia) | Session (not API) | HTTP | [web.md](./web.md) |
| Mobile | Token (Bearer) | HTTP + WebSocket + FCM | [mobile.md](./mobile.md) |
| Admin | Token + role gate | HTTP + WebSocket | [admin.md](./admin.md) |
| Third-party SPA | Token or Sanctum SPA | HTTP + WebSocket | — |

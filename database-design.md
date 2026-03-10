# Database Design - Social Media Platform

## Existing Tables (8)

> Updated: `users` table extended with profile fields and role column.

### 1. `users` (extended)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| name | string | Full name |
| email | string (unique) | Email address |
| email_verified_at | timestamp (nullable) | |
| password | string | Hashed password |
| two_factor_secret | text (nullable) | 2FA secret |
| two_factor_recovery_codes | text (nullable) | 2FA recovery codes |
| two_factor_confirmed_at | timestamp (nullable) | |
| role | enum | `user`, `moderator`, `admin`, `super_admin` (default: `user`) |
| avatar | string (nullable) | Profile picture path |
| cover_photo | string (nullable) | Cover image path |
| bio | text (nullable) | About me |
| date_of_birth | date (nullable) | Birthday |
| gender | enum (nullable) | `male`, `female`, `other` |
| location | string (nullable) | City/Country |
| website | string (nullable) | Personal link |
| phone | string (nullable) | Phone number |
| remember_token | string (nullable) | |
| created_at | timestamp | |
| updated_at | timestamp | |

> Profile fields merged directly into `users` — no separate `user_profiles` table needed.
>
> Role column replaces Spatie's 4 role/permission tables with a simple enum.

### 2-8. Standard Laravel Tables

| # | Table | Purpose |
|---|-------|---------|
| 2 | password_reset_tokens | Password reset flow |
| 3 | sessions | Active user sessions |
| 4 | cache | Application cache |
| 5 | cache_locks | Cache locking |
| 6 | jobs | Queue jobs |
| 7 | job_batches | Batch job tracking |
| 8 | failed_jobs | Failed job logging |

---

## New Tables (10)

### 9. `posts`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| user_id | bigint (FK → users) | Author |
| content | text (nullable) | Post text content |
| type | enum | `post`, `story` |
| visibility | enum | `public`, `friends`, `only_me` |
| shared_post_id | bigint (FK → posts, nullable) | Original post if this is a share |
| expires_at | timestamp (nullable) | For stories (24h auto-expire) |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp (nullable) | Soft delete |

> **Shares** = a post with `shared_post_id` pointing to the original post + optional `content` as caption.
>
> **Stories** = a post with `type = story` + `expires_at` set to 24h from creation.
>
> No separate `shares` or `stories` tables needed.

### 10. `post_media`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| post_id | bigint (FK → posts) | Related post |
| file_path | string | Storage path |
| file_type | enum | `image`, `video` |
| sort_order | int (default: 0) | Order in gallery |
| created_at | timestamp | |

### 11. `comments`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| user_id | bigint (FK → users) | Commenter |
| post_id | bigint (FK → posts) | Related post |
| parent_id | bigint (FK → comments, nullable) | For nested/reply comments |
| content | text | Comment text |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp (nullable) | Soft delete |

### 12. `likes`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| user_id | bigint (FK → users) | Who liked/bookmarked |
| likeable_id | bigint | Post or Comment ID |
| likeable_type | string | `App\Models\Post` or `App\Models\Comment` |
| type | enum | `like`, `love`, `haha`, `wow`, `sad`, `angry`, `bookmark` |
| created_at | timestamp | |

> **Polymorphic** — handles likes on both posts and comments.
>
> **Bookmarks** = `type = bookmark` on a post. No separate `bookmarks` table needed.
>
> **Unique constraint** on `(user_id, likeable_id, likeable_type, type)` — one reaction per type per user per item.

### 13. `friendships`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| sender_id | bigint (FK → users) | Who initiated |
| receiver_id | bigint (FK → users) | Target user |
| type | enum | `friend`, `follow`, `block` |
| status | enum | `pending`, `accepted` |
| created_at | timestamp | |
| updated_at | timestamp | |

> **One table for all relationships:**
> - **Friend request:** `type = friend`, `status = pending` → `accepted`
> - **Follow:** `type = follow`, `status = accepted` (instant, no approval needed)
> - **Block:** `type = block`, `status = accepted` (instant)
>
> **Unique constraint** on `(sender_id, receiver_id, type)`

### 14. `conversations`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| type | enum | `direct`, `group` |
| name | string (nullable) | Group chat name |
| avatar | string (nullable) | Group chat image |
| created_by | bigint (FK → users) | Creator |
| created_at | timestamp | |
| updated_at | timestamp | |

### 15. `conversation_user`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| conversation_id | bigint (FK → conversations) | |
| user_id | bigint (FK → users) | |
| role | enum | `member`, `admin` |
| joined_at | timestamp | |
| last_read_at | timestamp (nullable) | For unread count |

> **Unique constraint** on `(conversation_id, user_id)`

### 16. `messages`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| conversation_id | bigint (FK → conversations) | |
| user_id | bigint (FK → users) | Sender |
| body | text (nullable) | Message text |
| type | enum | `text`, `image`, `video`, `file` |
| file_path | string (nullable) | Attachment path |
| created_at | timestamp | |
| updated_at | timestamp | |
| deleted_at | timestamp (nullable) | Soft delete |

### 17. `notifications`

| Column | Type | Description |
|--------|------|-------------|
| id | uuid (PK) | Primary key |
| user_id | bigint (FK → users) | Recipient |
| sender_id | bigint (FK → users) | Who triggered it |
| type | string | `like`, `comment`, `share`, `friend_request`, `follow`, `message` |
| notifiable_id | bigint | Related item ID |
| notifiable_type | string | Related item model |
| read_at | timestamp (nullable) | When read |
| created_at | timestamp | |

### 18. `personal_access_tokens` (Laravel Sanctum)

| Column | Type | Description |
|--------|------|-------------|
| id | bigint (PK) | Primary key |
| tokenable_id | bigint | User ID |
| tokenable_type | string | `App\Models\User` |
| name | string | Token name (`mobile`, `web-spa`, `admin`) |
| token | string (unique) | Hashed token |
| abilities | text (nullable) | JSON permissions |
| last_used_at | timestamp (nullable) | |
| expires_at | timestamp (nullable) | |
| created_at | timestamp | |
| updated_at | timestamp | |

> Built-in with **Laravel Sanctum** — handles API auth for mobile, SPA & admin.

---

## Architecture Overview

```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│   Web App    │  │  Frontend    │  │  Mobile App  │  │ Admin Panel  │
│  (Inertia)   │  │    (SPA)     │  │ (iOS/Android)│  │   (SPA)      │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                 │                 │
       │   Session       │   Sanctum      │   Sanctum      │  Sanctum
       │   Auth          │   Token        │   Token        │  Token +
       │                 │                │                │  Role Check
       │                 │                │                │
       └────────────┬────┴────────┬───────┴────────┬──────┘
                    │             │                │
              ┌─────▼─────────────▼────────────────▼──────┐
              │            Laravel API Backend             │
              │                                            │
              │  Routes:                                   │
              │  ├── /web/*        → Inertia (session)     │
              │  ├── /api/v1/*     → User API (token)      │
              │  └── /api/admin/*  → Admin API (token+role)│
              │                                            │
              │  Middleware:                                │
              │  ├── auth:sanctum  → API authentication    │
              │  ├── role:admin    → Admin-only routes     │
              │  └── throttle      → Rate limiting         │
              └────────────────────┬───────────────────────┘
                                   │
                            ┌──────▼──────┐
                            │  Database   │
                            │  (MySQL)    │
                            └─────────────┘
```

---

## Relationships Diagram

```
users
 ├── has many → posts
 ├── has many → comments
 ├── has many → likes (includes bookmarks)
 ├── has many → notifications
 ├── has many → friendships (friends + follows + blocks)
 ├── has many → conversations (through conversation_user)
 ├── has many → messages
 └── has many → personal_access_tokens

posts
 ├── belongs to → users
 ├── belongs to → posts (shared_post_id, self-referencing for shares)
 ├── has many → post_media
 ├── has many → comments
 └── has many → likes (polymorphic)

comments
 ├── belongs to → users
 ├── belongs to → posts
 ├── has many → comments (self-referencing replies)
 └── has many → likes (polymorphic)

conversations
 ├── belongs to many → users (through conversation_user)
 └── has many → messages
```

---

## Summary

| # | Table | Purpose |
|---|-------|---------|
| — | **Existing (8)** | |
| 1 | users (extended) | Auth + profile + role |
| 2 | password_reset_tokens | Password reset |
| 3 | sessions | Session storage |
| 4 | cache | App cache |
| 5 | cache_locks | Cache locking |
| 6 | jobs | Queue |
| 7 | job_batches | Batch jobs |
| 8 | failed_jobs | Failed jobs |
| — | **New (10)** | |
| 9 | posts | Posts + shares + stories |
| 10 | post_media | Media attachments |
| 11 | comments | Comments + nested replies |
| 12 | likes | Reactions + bookmarks (polymorphic) |
| 13 | friendships | Friends + follows + blocks |
| 14 | conversations | Chat threads |
| 15 | conversation_user | Chat participants |
| 16 | messages | Chat messages |
| 17 | notifications | All notifications |
| 18 | personal_access_tokens | API auth (Sanctum) |

**Total: 18 tables (8 existing + 10 new)**

---

## What Was Merged / Removed

| Removed | Merged Into |
|---------|-------------|
| user_profiles | → `users` table (added columns) |
| shares | → `posts.shared_post_id` |
| stories | → `posts` with `type = story` + `expires_at` |
| story_views | → removed (add later if needed) |
| bookmarks | → `likes` with `type = bookmark` |
| followers | → `friendships` with `type = follow` |
| blocks | → `friendships` with `type = block` |
| tags, post_tag | → parse hashtags from content |
| mentions | → parse @mentions from content |
| roles, permissions, role_has_permissions, model_has_roles | → `users.role` column |
| devices | → removed (add later for push notifications) |
| reports | → removed (add later for moderation) |
| user_bans | → removed (add later for moderation) |
| admin_activity_logs | → removed (use log files) |
| settings | → removed (use config files) |
| pages | → removed (use static files or CMS) |

---

## Platform Auth Strategy

| Platform | Auth Method | Package |
|----------|------------|---------|
| Web (Inertia) | Session/Cookie | Laravel Fortify |
| Frontend (SPA) | Sanctum Token | Laravel Sanctum |
| Mobile (iOS/Android) | Sanctum Token | Laravel Sanctum |
| Admin Panel | Sanctum Token + `users.role` check | Laravel Sanctum |

# Frontend SPA Platform

## Overview

A standalone React single-page application that consumes the Laravel API. Separate from the Inertia web app — can be hosted on a different domain or CDN.

## Tech

- **Framework:** React 19 + TypeScript
- **Routing:** React Router v7
- **State:** Zustand or TanStack Query (server state)
- **Styling:** Tailwind CSS 4
- **Auth:** Sanctum API Token
- **Real-time:** Laravel Echo + WebSocket
- **Build:** Vite

## Auth Flow

```
User → Login Form → POST /api/v1/auth/login
     → API returns { token, user }
     → Store token in memory (or httpOnly cookie via Sanctum SPA mode)
     → All requests include: Authorization: Bearer {token}
     → Refresh token on 401
```

### Sanctum SPA Mode (Recommended)

If SPA is on the same domain (subdomain):
```
1. GET  /sanctum/csrf-cookie     → Get CSRF token
2. POST /api/v1/auth/login       → Login (session-based via cookie)
3. All subsequent requests auto-authenticated via cookie
```

### Token Mode

If SPA is on a different domain:
```
1. POST /api/v1/auth/login       → Returns { token: "xxx", user: {...} }
2. Store token in memory
3. Add header: Authorization: Bearer xxx
```

## API Endpoints

All endpoints prefixed with `/api/v1/`

### Auth
```
POST   /auth/register            → Register
POST   /auth/login               → Login → returns token + user
POST   /auth/logout              → Logout → revoke token
POST   /auth/forgot-password     → Send reset email
POST   /auth/reset-password      → Reset password
POST   /auth/verify-email        → Verify email
POST   /auth/two-factor/enable   → Enable 2FA
POST   /auth/two-factor/confirm  → Confirm 2FA
POST   /auth/two-factor/disable  → Disable 2FA
POST   /auth/two-factor/challenge → Verify 2FA code on login
GET    /auth/user                → Get current user
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
POST   /stories/{id}/view       → Mark as viewed
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
POST   /friends/request/{id}    → Send request
POST   /friends/accept/{id}     → Accept
POST   /friends/reject/{id}     → Reject
DELETE /friends/{id}             → Remove friend
```

### Follow
```
POST   /follow/{id}             → Follow user
DELETE /follow/{id}             → Unfollow
GET    /followers               → My followers
GET    /following               → Who I follow
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
POST   /block/{id}              → Block user
DELETE /block/{id}              → Unblock
GET    /blocked                 → Blocked users list
```

## Project Structure

```
src/
├── main.tsx
├── App.tsx
├── router.tsx
│
├── api/
│   ├── client.ts                → Axios instance + interceptors
│   ├── auth.ts                  → Auth endpoints
│   ├── posts.ts                 → Posts endpoints
│   ├── comments.ts              → Comments endpoints
│   ├── users.ts                 → Users endpoints
│   ├── friends.ts               → Friends endpoints
│   ├── messages.ts              → Messaging endpoints
│   ├── notifications.ts         → Notifications endpoints
│   └── stories.ts               → Stories endpoints
│
├── stores/
│   ├── auth-store.ts            → Auth state + token
│   ├── notification-store.ts    → Notification count
│   └── chat-store.ts            → Active chat state
│
├── hooks/
│   ├── use-auth.ts
│   ├── use-posts.ts             → TanStack Query hooks
│   ├── use-comments.ts
│   ├── use-friends.ts
│   ├── use-messages.ts
│   └── use-notifications.ts
│
├── pages/
│   ├── home.tsx                 → Feed
│   ├── login.tsx
│   ├── register.tsx
│   ├── post-detail.tsx
│   ├── profile.tsx
│   ├── friends.tsx
│   ├── messages.tsx
│   ├── chat.tsx
│   ├── notifications.tsx
│   ├── search.tsx
│   ├── bookmarks.tsx
│   ├── stories.tsx
│   └── settings/
│       ├── profile.tsx
│       ├── password.tsx
│       └── two-factor.tsx
│
├── components/
│   ├── layout/
│   │   ├── header.tsx
│   │   ├── sidebar.tsx
│   │   └── mobile-nav.tsx
│   ├── post/
│   │   ├── post-card.tsx
│   │   ├── post-form.tsx
│   │   ├── like-button.tsx
│   │   └── share-button.tsx
│   ├── comment/
│   │   ├── comment-list.tsx
│   │   └── comment-item.tsx
│   ├── story/
│   │   ├── story-bar.tsx
│   │   └── story-viewer.tsx
│   ├── chat/
│   │   ├── message-list.tsx
│   │   └── message-input.tsx
│   └── ui/                      → Shared UI components
│
├── lib/
│   ├── utils.ts
│   └── echo.ts                  → Laravel Echo setup
│
└── types/
    └── index.ts                 → Shared TypeScript types
```

## Key Differences from Web (Inertia)

| Aspect | Web (Inertia) | Frontend SPA |
|--------|---------------|-------------|
| Rendering | SSR via Inertia | Client-side only |
| Auth | Session/Cookie | API Token (Sanctum) |
| Routing | Server-driven (Inertia) | Client-side (React Router) |
| Data fetching | Props from controller | API calls (fetch/axios) |
| Hosting | Same server as Laravel | CDN / separate host |
| SEO | Better (SSR) | Needs extra work (meta tags) |
| Deploy | Deploy with Laravel | Deploy independently |

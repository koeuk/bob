# Web Platform (Inertia.js)

## Overview

The main website — server-rendered React pages via Inertia.js. Same codebase as the current Laravel starter kit.

## Tech

- **Rendering:** Inertia.js with SSR
- **Frontend:** React 19 + TypeScript
- **Styling:** Tailwind CSS 4 + Radix UI
- **Auth:** Session/Cookie via Laravel Fortify
- **Build:** Vite 7

## Auth Flow

```
User → Login Form → POST /login → Fortify validates
     → Session created → Redirect to /dashboard
     → All requests include session cookie
```

- No API tokens needed — uses standard Laravel session auth
- CSRF protection via Inertia
- Two-factor authentication supported

## Routes

```
GET  /                          → Welcome page
GET  /dashboard                 → News feed / dashboard

# Posts
GET  /posts                     → Feed (paginated)
GET  /posts/{post}              → Single post view
POST /posts                     → Create post
PUT  /posts/{post}              → Edit post
DEL  /posts/{post}              → Delete post

# Comments
POST /posts/{post}/comments     → Add comment
PUT  /comments/{comment}        → Edit comment
DEL  /comments/{comment}        → Delete comment

# Likes
POST /posts/{post}/like         → Like/react to post
POST /comments/{comment}/like   → Like/react to comment

# Shares
POST /posts/{post}/share        → Share post

# Stories
GET  /stories                   → View stories
POST /stories                   → Create story
DEL  /stories/{story}           → Delete story

# Friends
GET  /friends                   → Friends list
POST /friends/request/{user}    → Send friend request
POST /friends/accept/{user}     → Accept request
POST /friends/reject/{user}     → Reject request
DEL  /friends/{user}            → Remove friend

# Follow
POST /follow/{user}             → Follow user
DEL  /follow/{user}             → Unfollow user

# Messaging
GET  /messages                  → Conversations list
GET  /messages/{conversation}   → Chat view
POST /messages/{conversation}   → Send message

# Profile
GET  /profile/{user}            → View profile
GET  /settings/profile          → Edit profile (existing)
GET  /settings/password         → Change password (existing)
GET  /settings/appearance       → Theme (existing)
GET  /settings/two-factor       → 2FA (existing)

# Search
GET  /search                    → Search users, posts, tags

# Notifications
GET  /notifications             → All notifications

# Bookmarks
GET  /bookmarks                 → Saved posts
POST /bookmarks/{post}          → Bookmark post
DEL  /bookmarks/{post}          → Remove bookmark

# Block
POST /block/{user}              → Block user
DEL  /block/{user}              → Unblock user
```

## Pages Structure

```
resources/js/pages/
├── welcome.tsx                  (existing)
├── dashboard.tsx                (update → news feed)
│
├── posts/
│   ├── index.tsx                → Feed
│   ├── show.tsx                 → Single post
│   └── components/
│       ├── post-card.tsx
│       ├── post-form.tsx
│       ├── comment-section.tsx
│       ├── comment-item.tsx
│       ├── like-button.tsx
│       └── share-button.tsx
│
├── stories/
│   ├── index.tsx                → Stories viewer
│   └── create.tsx               → Create story
│
├── friends/
│   ├── index.tsx                → Friends list
│   └── requests.tsx             → Pending requests
│
├── messages/
│   ├── index.tsx                → Conversations list
│   └── show.tsx                 → Chat view
│
├── profile/
│   ├── show.tsx                 → User profile
│   └── components/
│       ├── profile-header.tsx
│       ├── profile-posts.tsx
│       └── profile-friends.tsx
│
├── search/
│   └── index.tsx                → Search results
│
├── notifications/
│   └── index.tsx                → Notifications page
│
├── bookmarks/
│   └── index.tsx                → Saved posts
│
├── settings/                    (existing, extend)
│   ├── profile.tsx
│   ├── password.tsx
│   ├── appearance.tsx
│   └── two-factor.tsx
│
└── auth/                        (existing)
    ├── login.tsx
    ├── register.tsx
    └── ...
```

## Real-time Features

Using Laravel Echo + Reverb/Pusher:

- **New notifications** → badge update in header
- **New messages** → real-time chat
- **Typing indicator** → in messaging
- **Online status** → presence channel

```typescript
// Example: listening for new messages
Echo.private(`conversation.${id}`)
  .listen('MessageSent', (e) => {
    addMessage(e.message);
  })
  .listenForWhisper('typing', (e) => {
    showTypingIndicator(e.user);
  });
```

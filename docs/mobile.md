# Mobile Platform (iOS & Android)

## Overview

Native mobile apps consuming the same Laravel API. Shares all `/api/v1/*` endpoints with the Frontend SPA.

## Tech Options

| Option | Pros | Cons |
|--------|------|------|
| **React Native** | Share logic with React web, large ecosystem | Performance overhead |
| **Flutter** | Fast, great UI, single codebase | Dart language, separate from web |
| **Native (Swift/Kotlin)** | Best performance, platform APIs | Two codebases |

> **Recommended:** React Native (Expo) — maximizes code sharing with the React web apps.

## Auth Flow

```
User → Login Screen → POST /api/v1/auth/login
     → Store token in secure storage (Keychain/Keystore)
     → All requests: Authorization: Bearer {token}
     → On 401: redirect to login
```

- Use **SecureStore** (Expo) or **Keychain/Keystore** for token — never AsyncStorage
- Biometric login (Face ID / fingerprint) as optional unlock
- Remember device via `devices` table

## API Endpoints

Same as [Frontend SPA](./frontend-spa.md#api-endpoints) — all `/api/v1/*` routes.

### Mobile-Specific Endpoints

```
# Device Registration (for push notifications)
POST   /api/v1/devices              → Register device + FCM token
PUT    /api/v1/devices/{id}         → Update FCM token
DELETE /api/v1/devices/{id}         → Remove device

# Media Upload (optimized for mobile)
POST   /api/v1/upload               → Upload media (chunked for large files)
```

## Push Notifications

Using **Firebase Cloud Messaging (FCM)** for both iOS and Android.

### Flow
```
1. App starts → get FCM token from Firebase
2. POST /api/v1/devices → send FCM token to backend
3. Backend event (new like, comment, message) triggers notification
4. Laravel job → sends push via FCM
5. App receives push → show system notification
6. User taps → deep link to relevant screen
```

### Notification Types

| Event | Title | Body | Deep Link |
|-------|-------|------|-----------|
| New Like | "{name} liked your post" | Post preview | /posts/{id} |
| New Comment | "{name} commented" | Comment preview | /posts/{id} |
| New Share | "{name} shared your post" | — | /posts/{id} |
| Friend Request | "{name} sent you a request" | — | /friends/requests |
| Friend Accepted | "{name} accepted your request" | — | /profile/{id} |
| New Message | "{name}" | Message preview | /messages/{id} |
| Story Mention | "{name} mentioned you" | — | /stories |

## App Structure (React Native)

```
src/
├── App.tsx
├── navigation/
│   ├── root-navigator.tsx       → Auth vs Main stack
│   ├── main-tabs.tsx            → Bottom tab navigator
│   ├── feed-stack.tsx           → Feed → Post Detail
│   ├── messages-stack.tsx       → Conversations → Chat
│   ├── notifications-stack.tsx
│   └── profile-stack.tsx
│
├── screens/
│   ├── auth/
│   │   ├── login.tsx
│   │   ├── register.tsx
│   │   ├── forgot-password.tsx
│   │   └── two-factor.tsx
│   ├── feed/
│   │   ├── feed.tsx             → News feed
│   │   └── post-detail.tsx
│   ├── stories/
│   │   ├── story-viewer.tsx
│   │   └── create-story.tsx
│   ├── messages/
│   │   ├── conversations.tsx
│   │   └── chat.tsx
│   ├── notifications/
│   │   └── notifications.tsx
│   ├── profile/
│   │   ├── profile.tsx
│   │   └── edit-profile.tsx
│   ├── friends/
│   │   ├── friends.tsx
│   │   └── requests.tsx
│   ├── search/
│   │   └── search.tsx
│   ├── bookmarks/
│   │   └── bookmarks.tsx
│   └── settings/
│       ├── settings.tsx
│       ├── password.tsx
│       └── two-factor.tsx
│
├── components/
│   ├── post/
│   │   ├── post-card.tsx
│   │   ├── post-form.tsx
│   │   ├── like-button.tsx
│   │   └── share-sheet.tsx      → Native share sheet
│   ├── comment/
│   │   ├── comment-list.tsx
│   │   └── comment-item.tsx
│   ├── story/
│   │   ├── story-bar.tsx
│   │   └── story-viewer.tsx
│   ├── chat/
│   │   ├── message-bubble.tsx
│   │   └── message-input.tsx
│   └── ui/
│       ├── avatar.tsx
│       ├── button.tsx
│       ├── input.tsx
│       └── loading.tsx
│
├── api/
│   ├── client.ts                → Axios + token interceptor
│   ├── auth.ts
│   ├── posts.ts
│   ├── comments.ts
│   ├── users.ts
│   ├── friends.ts
│   ├── messages.ts
│   ├── notifications.ts
│   ├── stories.ts
│   └── devices.ts               → Device registration
│
├── stores/
│   ├── auth-store.ts
│   ├── notification-store.ts
│   └── chat-store.ts
│
├── hooks/
│   ├── use-auth.ts
│   ├── use-push-notifications.ts → FCM setup
│   ├── use-deep-linking.ts
│   └── use-biometric.ts
│
├── services/
│   ├── secure-storage.ts        → Token storage (Keychain/Keystore)
│   ├── push-notification.ts     → FCM service
│   ├── image-picker.ts          → Camera & gallery
│   └── socket.ts                → WebSocket connection
│
├── lib/
│   └── utils.ts
│
└── types/
    └── index.ts
```

## Navigation Structure

```
Root Navigator
├── Auth Stack (when not logged in)
│   ├── Login
│   ├── Register
│   ├── Forgot Password
│   └── Two-Factor Challenge
│
└── Main Tabs (when logged in)
    ├── Feed Tab
    │   ├── Feed (with Stories bar at top)
    │   ├── Post Detail
    │   └── User Profile
    ├── Search Tab
    │   └── Search Results
    ├── Create Post (modal)
    ├── Messages Tab
    │   ├── Conversations List
    │   └── Chat Screen
    ├── Notifications Tab
    │   └── Notification Detail → deep link
    └── Profile Tab
        ├── My Profile
        ├── Edit Profile
        ├── Friends
        ├── Bookmarks
        └── Settings
            ├── Password
            └── Two-Factor
```

## Mobile-Specific Features

| Feature | Implementation |
|---------|---------------|
| Push Notifications | FCM + `devices` table |
| Image/Video Upload | Camera + Gallery picker, compressed before upload |
| Offline Support | Cache feed locally, queue actions when offline |
| Deep Linking | `bob://posts/123`, `bob://profile/456` |
| Biometric Auth | Face ID / Fingerprint to unlock app |
| Pull to Refresh | Native pull-to-refresh on all lists |
| Infinite Scroll | Cursor-based pagination on feed |
| Share Sheet | Native OS share for posts |
| Photo Viewer | Pinch-to-zoom, swipe gallery |
| Video Player | Inline autoplay (muted) in feed |

## Offline Strategy

```
Online:  API call → update cache → render
Offline: Read from cache → queue mutations → sync when online
```

- Cache feed, profiles, conversations locally
- Queue post creation, likes, comments when offline
- Sync when connection restored
- Show "offline" banner

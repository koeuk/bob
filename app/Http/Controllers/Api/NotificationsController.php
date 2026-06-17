<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20);

        // Collect friend_request IDs from the current page
        $frIds = $notifications->getCollection()
            ->filter(fn ($n) => ($n->data['type'] ?? '') === 'friend_request')
            ->pluck('data.friend_request_id')
            ->filter()
            ->values();

        $frStatuses = $frIds->isNotEmpty()
            ? FriendRequest::whereIn('id', $frIds)->pluck('status', 'id')
            : collect();

        // Attach current friend request status into each notification's data
        $notifications->getCollection()->transform(function ($n) use ($frStatuses) {
            if (($n->data['type'] ?? '') === 'friend_request') {
                $status = $frStatuses->get($n->data['friend_request_id'] ?? '');
                $n->data = array_merge($n->data, ['friend_request_status' => $status ?? 'cancelled']);
            }
            return $n;
        });

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $user->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}

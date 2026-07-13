<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Users
 */
class UsersController extends Controller
{
    /**
     * Get user profile
     *
     * Public profile for a user, including their posts. Viewer-specific fields
     * (e.g. friendship status) are included when a bearer token is provided.
     *
     * @unauthenticated
     * @urlParam user string required The user UUID. Example: 019e1791-7e47-71c8-9da2-4a2e7fbd0c6f
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user('sanctum');

        $posts = $user->posts()
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->with(['user:id,uuid,name,avatar', 'sharedPost.user:id,uuid,name,avatar'])
            ->withCount(['comments', 'likes', 'shares'])
            ->latest()
            ->get();

        $postIds = $posts->pluck('id')->all();

        $myLikes = ($viewer && !empty($postIds))
            ? Like::where('user_id', $viewer->id)
                ->where('likeable_type', Post::class)
                ->whereIn('likeable_id', $postIds)
                ->get(['likeable_id', 'type'])
                ->keyBy('likeable_id')
            : collect();

        $reactionSummaries = empty($postIds) ? collect() : Like::where('likeable_type', Post::class)
            ->whereIn('likeable_id', $postIds)
            ->selectRaw('likeable_id, type, count(*) as count')
            ->groupBy('likeable_id', 'type')
            ->get()
            ->groupBy('likeable_id')
            ->map(fn ($rows) => $rows->pluck('count', 'type')->toArray());

        $posts->transform(function (Post $p) use ($myLikes, $reactionSummaries) {
            $like = $myLikes->get($p->id);
            $p->liked_by_me = (bool) $like;
            $p->my_reaction = $like?->type;
            $p->reactions_summary = $reactionSummaries->get($p->id, []);
            return $p;
        });

        // Accepted friends of this user
        $friendRequests = FriendRequest::where('status', 'accepted')
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
            })
            ->with([
                'sender:id,uuid,name,avatar,last_active_at',
                'receiver:id,uuid,name,avatar,last_active_at',
            ])
            ->get();

        $friends = $friendRequests->map(function ($fr) use ($user) {
            $friend = $fr->sender_id === $user->id ? $fr->receiver : $fr->sender;
            $friend->is_online = $friend->last_active_at
                && $friend->last_active_at->gt(now()->subMinutes(5));
            return $friend;
        })->values();

        // Friendship status between viewer and this profile user
        $friendship = null;
        if ($viewer && $viewer->id !== $user->id) {
            $fr = FriendRequest::where(function ($q) use ($viewer, $user) {
                $q->where('sender_id', $viewer->id)->where('receiver_id', $user->id);
            })->orWhere(function ($q) use ($viewer, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $viewer->id);
            })->first();

            if ($fr) {
                $friendship = [
                    'id'        => $fr->id,
                    'status'    => $fr->status,
                    'sent_by_me' => $fr->sender_id === $viewer->id,
                ];
            }
        }

        return response()->json([
            'user'       => [
                'uuid'      => $user->uuid,
                'name'      => $user->name,
                'avatar'    => $user->avatar,
                'cover'     => $user->cover,
                'role'      => $user->role,
                'joined_at' => $user->created_at,
                'is_online' => $user->last_active_at && $user->last_active_at->gt(now()->subMinutes(5)),
            ],
            'posts'      => $posts,
            'friends'    => $friends,
            'friendship' => $friendship,
        ]);
    }
}

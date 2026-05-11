<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function show(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user('sanctum');

        $posts = $user->posts()
            ->where('visibility', 'public')
            ->with('user:id,uuid,name,avatar')
            ->withCount(['comments', 'likes'])
            ->latest()
            ->get();

        // Accepted friends of this user
        $friendRequests = FriendRequest::where('status', 'accepted')
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                  ->orWhere('receiver_id', $user->id);
            })
            ->with([
                'sender:id,uuid,name,avatar',
                'receiver:id,uuid,name,avatar',
            ])
            ->get();

        $friends = $friendRequests->map(function ($fr) use ($user) {
            return $fr->sender_id === $user->id ? $fr->receiver : $fr->sender;
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
                'uuid'       => $user->uuid,
                'name'       => $user->name,
                'avatar'     => $user->avatar,
                'role'       => $user->role,
                'joined_at'  => $user->created_at,
            ],
            'posts'      => $posts,
            'friends'    => $friends,
            'friendship' => $friendship,
        ]);
    }
}

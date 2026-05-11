<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FriendRequest;
use App\Models\User;
use App\Notifications\FriendRequestAccepted;
use App\Notifications\FriendRequestReceived;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendRequestsController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_uuid' => 'required|string|exists:users,uuid',
        ]);

        $sender   = $request->user();
        $receiver = User::where('uuid', $validated['receiver_uuid'])->firstOrFail();

        if ($receiver->id === $sender->id) {
            return response()->json(['message' => 'Cannot add yourself.'], 422);
        }

        $existing = FriendRequest::where(function ($q) use ($sender, $receiver) {
            $q->where('sender_id', $sender->id)->where('receiver_id', $receiver->id);
        })->orWhere(function ($q) use ($sender, $receiver) {
            $q->where('sender_id', $receiver->id)->where('receiver_id', $sender->id);
        })->first();

        if ($existing) {
            // Already friends or pending — don't allow a new request
            if (in_array($existing->status, ['accepted', 'pending'])) {
                return response()->json([
                    'message' => 'Friend request already exists.',
                    'id'      => $existing->id,
                    'status'  => $existing->status,
                ], 422);
            }
            // Declined — delete so a fresh request can be sent
            $existing->delete();
        }

        $friendRequest = FriendRequest::create([
            'sender_id'   => $sender->id,
            'receiver_id' => $receiver->id,
            'status'      => 'pending',
        ]);

        $receiver->notify(new FriendRequestReceived($sender, $friendRequest));

        return response()->json(['ok' => true, 'id' => $friendRequest->id], 201);
    }

    public function accept(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $friendRequest = FriendRequest::where('id', $id)
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendRequest->update(['status' => 'accepted']);

        $friendRequest->sender->notify(new FriendRequestAccepted($user));

        return response()->json(['ok' => true]);
    }

    public function decline(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $friendRequest = FriendRequest::where('id', $id)
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendRequest->update(['status' => 'declined']);

        return response()->json(['ok' => true]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $friendRequest = FriendRequest::where('id', $id)
            ->where('sender_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $friendRequest->delete();

        return response()->json(['ok' => true]);
    }
}

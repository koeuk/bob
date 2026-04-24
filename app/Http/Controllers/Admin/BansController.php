<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BansController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ban::query()->with(['user:id,uuid,name,email', 'bannedBy:id,uuid,name']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->latest()->paginate($request->integer('per_page', 25))
        );
    }

    public function active(Request $request): JsonResponse
    {
        $bans = Ban::active()
            ->with(['user:id,uuid,name,email', 'bannedBy:id,uuid,name'])
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($bans);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_uuid' => ['required', 'uuid', 'exists:users,uuid'],
            'reason' => ['required', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = User::where('uuid', $data['user_uuid'])->firstOrFail();

        $ban = Ban::create([
            'user_id' => $user->id,
            'banned_by' => $request->user()->id,
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $user->tokens()->delete();

        ActivityLog::record('ban.create', $user, null, $ban->only(['reason', 'expires_at']));

        return response()->json(['ban' => $ban], 201);
    }

    public function destroy(Ban $ban): JsonResponse
    {
        $ban->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('ban.remove', $ban->user);

        return response()->json(['message' => 'Ban removed.']);
    }
}

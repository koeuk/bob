<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->select(['id', 'uuid', 'name', 'email', 'role', 'created_at', 'email_verified_at']);

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->trim()->value()) {
            $query->where('role', $role);
        }

        if ($request->boolean('banned')) {
            $query->whereHas('bans', fn ($q) => $q->active());
        }

        $users = $query->latest()->paginate($request->integer('per_page', 25));

        return response()->json($users);
    }

    public function show(User $user): JsonResponse
    {
        $user->load([
            'bans' => fn ($q) => $q->active()->with('bannedBy:id,uuid,name'),
        ])->loadCount(['posts', 'comments', 'reportsFiled']);

        return response()->json([
            'user' => $user,
            'reports_against' => $user->id
                ? \App\Models\Report::where('reportable_type', User::class)
                    ->where('reportable_id', $user->id)
                    ->latest()
                    ->limit(20)
                    ->get()
                : [],
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $before = $user->only(array_keys($data));
        $user->update($data);

        ActivityLog::record('user.update', $user, $before, $user->only(array_keys($data)));

        return response()->json(['user' => $user]);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->isSuperAdmin() && ! request()->user()->isSuperAdmin()) {
            return response()->json(['message' => 'Cannot delete super admin.'], 403);
        }

        ActivityLog::record('user.delete', $user, $user->only(['name', 'email', 'role']));
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function ban(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $ban = Ban::create([
            'user_id' => $user->id,
            'banned_by' => $request->user()->id,
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        $user->tokens()->delete();

        ActivityLog::record('user.ban', $user, null, $ban->only(['reason', 'expires_at']));

        return response()->json(['ban' => $ban], 201);
    }

    public function unban(User $user): JsonResponse
    {
        $user->bans()->active()->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('user.unban', $user);

        return response()->json(['message' => 'User unbanned.']);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,moderator,admin,super_admin'],
        ]);

        $before = ['role' => $user->role];
        $user->update(['role' => $data['role']]);

        ActivityLog::record('user.role_assign', $user, $before, ['role' => $data['role']]);

        return response()->json(['user' => $user]);
    }

    public function activity(User $user): JsonResponse
    {
        $logs = ActivityLog::where(function ($q) use ($user) {
            $q->where('admin_id', $user->id)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('target_type', User::class)->where('target_id', $user->id);
                });
        })
            ->latest('created_at')
            ->paginate(30);

        return response()->json($logs);
    }
}

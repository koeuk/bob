<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Admin: Users
 *
 * Requires `moderator`, `admin`, or `super_admin` role.
 * Creating/updating/deleting users requires `admin+`. Role assignment requires `super_admin`.
 */
class UsersController extends Controller
{
    /**
     * List users
     *
     * @queryParam filter[search] string Search by name or email. Example: jane
     * @queryParam filter[role] string Exact role. Example: moderator
     * @queryParam filter[banned] boolean Only show banned users. Example: 1
     * @queryParam sort string Sort field (prefix `-` for descending). One of: `name`, `email`, `created_at`. Example: -created_at
     * @queryParam per_page int Results per page (default 25). Example: 25
     * @queryParam page int Page number. Example: 1
     *
     * @response 200 {
     *   "current_page": 1,
     *   "data": [{ "id": 1, "uuid": "...", "name": "Jane", "email": "jane@example.com", "role": "user", "posts_count": 5 }],
     *   "total": 44
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $users = QueryBuilder::for(User::class)
            ->select(['id', 'uuid', 'name', 'email', 'created_at', 'email_verified_at'])
            ->withCount(['posts', 'comments'])
            ->with(['roles', 'bans' => fn ($q) => $q->active()])
            ->allowedFilters(...[
                AllowedFilter::callback('search', function ($q, $value) {
                    $q->where(function ($inner) use ($value) {
                        $inner->where('name', 'like', "%{$value}%")
                            ->orWhere('email', 'like', "%{$value}%");
                    });
                }),
                AllowedFilter::callback('role', function ($q, $value) {
                    $q->whereHas('roles', fn ($r) => $r->where('name', $value));
                }),
                AllowedFilter::callback('banned', function ($q, $value) {
                    if ($value) {
                        $q->whereHas('bans', fn ($b) => $b->active());
                    }
                }),
            ])
            ->allowedSorts(...['name', 'email', 'created_at'])
            ->defaultSort('-created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        $users->getCollection()->each->append('role');

        return response()->json($users);
    }

    /**
     * Create user
     *
     * Requires `admin+`. Creating `admin`/`super_admin` roles requires `super_admin`.
     *
     * @bodyParam name string required Example: Jane Doe
     * @bodyParam email string required Example: jane@example.com
     * @bodyParam password string required Minimum 8 characters. Example: secret123
     * @bodyParam role string required One of: `user`, `moderator`, `admin`, `super_admin`. Example: user
     *
     * @response 201 { "id": 50, "uuid": "...", "name": "Jane Doe", "email": "jane@example.com", "role": "user" }
     * @response 403 { "message": "Only super admins can create admin-level users." }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:user,moderator,admin,super_admin'],
        ]);

        if (in_array($data['role'], ['admin', 'super_admin'], true) && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can create admin-level users.');
        }

        $role = $data['role'];
        unset($data['role']);

        $user = User::create($data);
        $user->assignRole($role);

        ActivityLog::record('user.create', $user, null, ['name' => $user->name, 'email' => $user->email, 'role' => $role]);

        return response()->json($user->append('role'), 201);
    }

    /**
     * Get user
     *
     * Returns the user with their bans, report counts, and recent activity.
     *
     * @urlParam user string required User UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     *
     * @response 200 {
     *   "user": { "id": 23, "uuid": "...", "name": "...", "role": "user", "posts_count": 14, "comments_count": 17 },
     *   "reports_against": [],
     *   "activity": []
     * }
     */
    public function show(User $user): JsonResponse
    {
        $user->load([
            'roles',
            'bans' => fn ($q) => $q->with('bannedBy:id,uuid,name')->latest(),
        ])->loadCount(['posts', 'comments', 'reportsFiled']);
        $user->append('role');

        $reportsAgainst = Report::where('reportable_type', User::class)
            ->where('reportable_id', $user->id)
            ->with('reporter:id,uuid,name')
            ->latest()
            ->limit(20)
            ->get();

        $activity = ActivityLog::where(function ($q) use ($user) {
            $q->where('admin_id', $user->id)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('target_type', User::class)->where('target_id', $user->id);
                });
        })
            ->with('admin:id,uuid,name')
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'user' => $user,
            'reports_against' => $reportsAgainst,
            'activity' => $activity,
        ]);
    }

    /**
     * Update user
     *
     * Requires `admin+`. Can only update `name` and `email`.
     *
     * @urlParam user string required User UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @bodyParam name string Display name. Example: Jane Doe
     * @bodyParam email string Email address. Example: jane@example.com
     *
     * @response 200 { "id": 23, "name": "Jane Doe", "email": "jane@example.com" }
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ]);

        $before = $user->only(array_keys($data));
        $user->update($data);

        ActivityLog::record('user.update', $user, $before, $user->only(array_keys($data)));

        return response()->json($user->fresh());
    }

    /**
     * Delete user
     *
     * Requires `admin+`. Deleting a `super_admin` requires `super_admin`.
     *
     * @urlParam user string required User UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     *
     * @response 200 { "message": "User deleted." }
     * @response 403 { "message": "Cannot delete super admin." }
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->isSuperAdmin() && ! $request->user()->isSuperAdmin()) {
            abort(403, 'Cannot delete super admin.');
        }

        ActivityLog::record('user.delete', $user, $user->only(['name', 'email', 'role']));
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    /**
     * Ban user
     *
     * Creates a ban and immediately revokes all of the user's tokens.
     *
     * @urlParam user string required User UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @bodyParam reason string required Reason for the ban (max 2,000 chars). Example: Repeated spam.
     * @bodyParam expires_at string ISO 8601 datetime for temporary ban. Leave null for permanent. Example: 2026-12-31T23:59:59
     *
     * @response 201 { "id": 10, "uuid": "...", "user_id": 23, "reason": "Repeated spam.", "expires_at": null }
     */
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

        $user->tokens()?->delete();

        ActivityLog::record('user.ban', $user, null, $ban->only(['reason', 'expires_at']));

        return response()->json($ban, 201);
    }

    /**
     * Unban user
     *
     * Expires all active bans immediately.
     *
     * @urlParam user string required User UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     *
     * @response 200 { "message": "User unbanned." }
     */
    public function unban(User $user): JsonResponse
    {
        $user->bans()->active()->update(['expires_at' => now()->subSecond()]);

        ActivityLog::record('user.unban', $user);

        return response()->json(['message' => 'User unbanned.']);
    }

    /**
     * Assign role
     *
     * Requires `super_admin`.
     *
     * @urlParam user string required User UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @bodyParam role string required One of: `user`, `moderator`, `admin`, `super_admin`. Example: moderator
     *
     * @response 200 { "id": 23, "role": "moderator" }
     * @response 403 { "message": "Only super admins can change roles." }
     */
    public function assignRole(Request $request, User $user): JsonResponse
    {
        if (! $request->user()->isSuperAdmin()) {
            abort(403, 'Only super admins can change roles.');
        }

        $data = $request->validate([
            'role' => ['required', 'in:user,moderator,admin,super_admin'],
        ]);

        $before = ['role' => $user->role];
        $user->syncRoles([$data['role']]);

        ActivityLog::record('user.role_assign', $user, $before, ['role' => $data['role']]);

        return response()->json($user->load('roles')->append('role'));
    }
}

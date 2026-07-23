<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\Users\AssignUserRole;
use App\Actions\Bans\IssueBan;
use App\Actions\Users\CreateUser;
use App\Actions\Users\DeleteUser;
use App\Actions\Users\ListUsers;
use App\Actions\Users\ShowUser;
use App\Actions\Users\UnbanUser;
use App\Actions\Users\UpdateUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function index(Request $request, ListUsers $listUsers): JsonResponse
    {
        return response()->json($listUsers->handle($request));
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
     * @bodyParam avatar file Optional avatar image (max 2MB).
     *
     * @response 201 { "id": 50, "uuid": "...", "name": "Jane Doe", "email": "jane@example.com", "role": "user" }
     * @response 403 { "message": "Only super admins can create admin-level users." }
     */
    public function store(Request $request, CreateUser $createUser): JsonResponse
    {
        $data = $request->validate(CreateUser::rules());

        $user = $createUser->handle($data, $request->user(), $request->file('avatar'));

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
    public function show(User $user, ShowUser $showUser): JsonResponse
    {
        return response()->json($showUser->handle($user));
    }

    /**
     * Update user
     *
     * Requires `admin+`.
     *
     * @urlParam user string required User UUID. Example: 019e178b-4ffc-72ad-a045-31cb5d618db2
     * @bodyParam name string Display name. Example: Jane Doe
     * @bodyParam email string Email address. Example: jane@example.com
     * @bodyParam password string New password (min 8 characters). Example: secret123
     * @bodyParam avatar file Replacement avatar image (max 2MB).
     * @bodyParam remove_avatar boolean Set to true to clear the current avatar. Example: false
     *
     * @response 200 { "id": 23, "name": "Jane Doe", "email": "jane@example.com" }
     */
    public function update(Request $request, User $user, UpdateUser $updateUser): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate(UpdateUser::rules($user));

        $updateUser->handle(
            $user,
            $data,
            $request->file('avatar'),
            (bool) $request->input('remove_avatar'),
        );

        // append('role') to match store() and assignRole() — clients read
        // `role` off this response to re-render the row.
        return response()->json($user->fresh()->append('role'));
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
    public function destroy(Request $request, User $user, DeleteUser $deleteUser): JsonResponse
    {
        $this->authorize('delete', $user);

        $deleteUser->handle($user);

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
    public function ban(Request $request, User $user, IssueBan $issueBan): JsonResponse
    {
        $this->authorize('ban', $user);

        $data = $request->validate(IssueBan::rules());

        $ban = $issueBan->handle($user, $data, $request->user(), 'user.ban');

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
    public function unban(Request $request, User $user, UnbanUser $unbanUser): JsonResponse
    {
        $this->authorize('unban', $user);

        $unbanUser->handle($user);

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
    public function assignRole(Request $request, User $user, AssignUserRole $assignRole): JsonResponse
    {
        AssignUserRole::assertAllowed($request->user());

        $data = $request->validate(AssignUserRole::rules());

        $assignRole->handle($user, $data['role'], $request->user());

        return response()->json($user->load('roles')->append('role'));
    }
}

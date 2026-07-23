<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\User;

class AssignUserRole
{
    /** Validation rules shared by every surface that assigns a role. */
    public static function rules(): array
    {
        return [
            'role' => ['required', 'in:user,moderator,admin,super_admin'],
        ];
    }

    /**
     * Guard the action. Call this *before* validating so a non-super-admin
     * gets a 403 rather than a 422, matching the original behaviour.
     */
    public static function assertAllowed(User $actor): void
    {
        if (! $actor->isSuperAdmin()) {
            abort(403, 'Only super admins can change roles.');
        }
    }

    public function handle(User $user, string $role, User $actor): User
    {
        self::assertAllowed($actor);

        $before = ['role' => $user->role];

        // The users.role column is kept in sync by the RoleAttached/Detached
        // listeners registered in AppServiceProvider.
        $user->syncRoles([$role]);

        ActivityLog::record('user.role_assign', $user, $before, ['role' => $role]);

        return $user;
    }
}

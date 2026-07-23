<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateUser
{
    /** Validation rules shared by every surface that creates a user. */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:user,moderator,admin,super_admin'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }

    public function handle(array $data, User $actor, ?UploadedFile $avatar = null): User
    {
        // Creating accounts is an admin+ action. The gate lives here (as in
        // IssueBan) so both the Inertia and JSON surfaces are covered —
        // previously UserPolicy::create had no call sites at all and a
        // moderator could mint further moderator accounts.
        Gate::forUser($actor)->authorize('create', User::class);

        if (in_array($data['role'], ['admin', 'super_admin'], true) && ! $actor->isSuperAdmin()) {
            abort(403, 'Only super admins can create admin-level users.');
        }

        $attrs = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ];

        if ($avatar) {
            $attrs['avatar'] = $avatar->store('avatars', 'public');
        }

        // Atomic: without this a failing assignRole() (e.g. the role has not
        // been seeded) leaves a persisted user with no role, no audit entry,
        // and the email permanently taken.
        return DB::transaction(function () use ($attrs, $data) {
            $user = User::create($attrs);
            $user->assignRole($data['role']);

            ActivityLog::record('user.create', $user, null, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $data['role'],
            ]);

            return $user;
        });
    }
}

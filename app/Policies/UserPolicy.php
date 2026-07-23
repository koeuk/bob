<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isModerator() || $user->id === $target->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->id === $target->id || $user->outranks($target);
    }

    /**
     * Self-targeting is refused for the destructive abilities.
     *
     * outranks() returns true for a super_admin against ANY target — including
     * themselves — and Gate::before grants super_admins everything, so without
     * this guard the sole super_admin could delete or ban their own account.
     * Since assignRole requires isSuperAdmin(), that would leave no account
     * able to promote anyone: an unrecoverable lockout.
     */
    public function delete(User $user, User $target): bool
    {
        return $user->id !== $target->id && $user->outranks($target);
    }

    public function ban(User $user, User $target): bool
    {
        return $user->id !== $target->id
            && $user->isModerator()
            && $user->outranks($target);
    }

    public function unban(User $user, User $target): bool
    {
        return $user->isModerator() && $user->outranks($target);
    }

    public function assignRole(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}

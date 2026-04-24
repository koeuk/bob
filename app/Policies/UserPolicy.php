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

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->id === $target->id;
    }

    public function delete(User $user, User $target): bool
    {
        if ($target->isSuperAdmin()) {
            return $user->isSuperAdmin();
        }

        return $user->isAdmin();
    }

    public function ban(User $user, User $target): bool
    {
        return $user->isModerator() && ! $target->isAdmin();
    }

    public function assignRole(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}

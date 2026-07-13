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

    public function delete(User $user, User $target): bool
    {
        return $user->outranks($target);
    }

    public function ban(User $user, User $target): bool
    {
        return $user->isModerator() && $user->outranks($target);
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

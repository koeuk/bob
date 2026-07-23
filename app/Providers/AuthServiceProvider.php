<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            if (! $user->isSuperAdmin()) {
                return null;
            }

            // The blanket bypass must not authorize self-destruction: a
            // super_admin deleting or banning their own account would strand
            // role management (assignRole requires isSuperAdmin()). Returning
            // null defers to UserPolicy, which refuses self-targeting.
            $target = $arguments[0] ?? null;

            if ($target instanceof User
                && $target->id === $user->id
                && in_array($ability, ['delete', 'ban'], true)) {
                return null;
            }

            return true;
        });
    }
}

<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Keep the denormalized users.role column in sync with spatie roles:
        // whenever a role is attached/detached, mirror the primary role name
        // into the column so the flat value never drifts from the pivot.
        Event::listen([RoleAttachedEvent::class, RoleDetachedEvent::class], function ($event): void {
            $model = $event->model;

            if (! $model instanceof User) {
                return;
            }

            $model->unsetRelation('roles');
            $names = $model->getRoleNames();

            // Pick the highest-ranked *known* role rather than
            // getRoleNames()->first(), which reads from an unordered query and
            // so mirrored an arbitrary role for multi-role users. Restricting
            // to known names also keeps the write inside the column's enum —
            // a custom role would otherwise throw AFTER the pivot committed,
            // leaving the mirror stale (the exact desync it exists to prevent).
            $mirror = collect(User::ROLE_HIERARCHY)
                ->first(fn (string $role) => $names->contains($role)) ?? 'user';

            $model->forceFill(['role' => $mirror])->saveQuietly();
        });
    }
}

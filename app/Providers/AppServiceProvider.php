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

            if ($model instanceof User) {
                $model->unsetRelation('roles');
                $model->forceFill(['role' => $model->getRoleNames()->first() ?? 'user'])->saveQuietly();
            }
        });
    }
}

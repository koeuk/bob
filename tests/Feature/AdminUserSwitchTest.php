<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/**
 * The admin↔user switch: admins work in /admin but flip to the user-facing
 * Inertia app (/dashboard, /feed) via the account dropdown. Both surfaces must
 * stay reachable for a privileged account, and /admin must stay closed to
 * everyone else.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('lets an admin reach both the admin panel and the user app', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    $this->actingAs($admin)->get('/dashboard')->assertOk();
    $this->actingAs($admin)->get('/feed')->assertOk();
});

it('lets a moderator switch to the admin panel', function () {
    $moderator = userWithRole('moderator');

    $this->actingAs($moderator)->get('/admin/dashboard')->assertOk();
    $this->actingAs($moderator)->get('/dashboard')->assertOk();
});

it('keeps the admin panel closed to a regular user', function () {
    $user = userWithRole('user');

    $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
});

it('still serves the user app to a regular user', function () {
    $user = userWithRole('user');

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get('/feed')->assertOk();
    $this->actingAs($user)->get('/posts/mine')->assertOk();
});

it('sends guests to login rather than the app', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->get('/admin/dashboard')->assertRedirect(route('login'));
});

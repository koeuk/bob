<?php

use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Characterization tests for the shared app/Actions/Users/* logic, exercised
 * through the JSON admin API. Both the Inertia admin panel and this API call
 * the same Actions, so covering one surface pins the behaviour of both.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function actingAsRole(string $role): User
{
    $admin = User::factory()->create();
    $admin->assignRole($role);
    Sanctum::actingAs($admin);

    return $admin;
}

it('lists users with role and avatar exposed', function () {
    actingAsRole('admin');
    User::factory()->count(3)->create()->each->assignRole('user');

    $this->getJson('/api/admin/users')
        ->assertOk()
        ->assertJsonStructure(['data' => [['uuid', 'name', 'email', 'avatar', 'role', 'posts_count']]]);
});

it('bans a user, revokes their tokens and logs the action', function () {
    actingAsRole('admin');
    $target = User::factory()->create();
    $target->assignRole('user');
    $target->createToken('test');

    $this->postJson("/api/admin/users/{$target->uuid}/ban", [
        'reason' => 'Repeated spam.',
    ])->assertCreated();

    expect(Ban::where('user_id', $target->id)->exists())->toBeTrue()
        ->and($target->tokens()->count())->toBe(0)
        ->and(ActivityLog::where('action', 'user.ban')->exists())->toBeTrue();
});

it('unbans a user by expiring active bans', function () {
    $admin = actingAsRole('admin');
    $target = User::factory()->create();
    $target->assignRole('user');
    Ban::create(['user_id' => $target->id, 'banned_by' => $admin->id, 'reason' => 'x', 'expires_at' => null]);

    $this->postJson("/api/admin/users/{$target->uuid}/unban")->assertOk();

    expect($target->bans()->active()->count())->toBe(0)
        ->and(ActivityLog::where('action', 'user.unban')->exists())->toBeTrue();
});

it('forbids role assignment for non super admins', function () {
    actingAsRole('admin');
    $target = User::factory()->create();
    $target->assignRole('user');

    $this->postJson("/api/admin/users/{$target->uuid}/role", ['role' => 'moderator'])
        ->assertForbidden();

    expect($target->fresh()->role)->toBe('user');
});

it('lets a super admin assign a role and keeps the users.role column in sync', function () {
    actingAsRole('super_admin');
    $target = User::factory()->create();
    $target->assignRole('user');

    $this->postJson("/api/admin/users/{$target->uuid}/role", ['role' => 'moderator'])
        ->assertOk();

    $fresh = $target->fresh();
    expect($fresh->getRawOriginal('role'))->toBe('moderator')
        ->and($fresh->getRoleNames()->first())->toBe('moderator');
});

it('returns 403 before validating when a non super admin sends an invalid role', function () {
    actingAsRole('admin');
    $target = User::factory()->create();
    $target->assignRole('user');

    // Guard must run before validation, otherwise this would be a 422.
    $this->postJson("/api/admin/users/{$target->uuid}/role", ['role' => 'not-a-role'])
        ->assertForbidden();
});

it('updates a user and records the change', function () {
    actingAsRole('super_admin');
    $target = User::factory()->create(['name' => 'Old Name']);
    $target->assignRole('user');

    $this->patchJson("/api/admin/users/{$target->uuid}", ['name' => 'New Name'])
        ->assertOk();

    expect($target->fresh()->name)->toBe('New Name')
        ->and(ActivityLog::where('action', 'user.update')->exists())->toBeTrue();
});

it('deletes a user and logs it', function () {
    actingAsRole('super_admin');
    $target = User::factory()->create();
    $target->assignRole('user');

    $this->deleteJson("/api/admin/users/{$target->uuid}")->assertOk();

    expect(User::find($target->id))->toBeNull()
        ->and(ActivityLog::where('action', 'user.delete')->exists())->toBeTrue();
});

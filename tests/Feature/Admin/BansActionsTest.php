<?php

use App\Models\Ban;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Covers app/Actions/Bans/*. The important case is the policy check: the JSON
 * bans endpoint used to skip authorize('ban'), so an admin could ban a
 * super_admin they do not outrank. The gate now lives inside IssueBan.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    Sanctum::actingAs($this->admin);
});

it('creates a ban for a lower-ranked user and revokes their tokens', function () {
    $target = User::factory()->create();
    $target->assignRole('user');
    $target->createToken('t');

    $this->postJson('/api/admin/bans', [
        'user_uuid' => $target->uuid,
        'reason' => 'Spam',
    ])->assertCreated();

    expect(Ban::where('user_id', $target->id)->exists())->toBeTrue()
        ->and($target->tokens()->count())->toBe(0);
});

it('refuses to ban a user the actor does not outrank', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $this->postJson('/api/admin/bans', [
        'user_uuid' => $superAdmin->uuid,
        'reason' => 'nope',
    ])->assertForbidden();

    expect(Ban::where('user_id', $superAdmin->id)->exists())->toBeFalse();
});

it('lifts a single ban by expiring it', function () {
    $target = User::factory()->create();
    $target->assignRole('user');
    $ban = Ban::create([
        'user_id' => $target->id,
        'banned_by' => $this->admin->id,
        'reason' => 'x',
        'expires_at' => null,
    ]);

    $this->deleteJson("/api/admin/bans/{$ban->uuid}")->assertOk();

    expect($target->bans()->active()->count())->toBe(0);
});

it('blocks non-admins from saving settings', function () {
    $moderator = User::factory()->create();
    $moderator->assignRole('moderator');
    Sanctum::actingAs($moderator);

    $this->patchJson('/api/admin/settings', [
        'settings' => [['key' => 'site_name', 'value' => 'hacked']],
    ])->assertForbidden();
});

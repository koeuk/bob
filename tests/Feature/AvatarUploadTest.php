<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

/**
 * Does avatar upload actually work, and is the replaced file cleaned up?
 * Covers all three upload paths: admin-edits-user, self-service profile,
 * and the JSON "update me" endpoint.
 */
beforeEach(function () {
    Storage::fake('public');
    $this->seed(RolePermissionSeeder::class);
});

it('stores an avatar when an admin creates a user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);

    $this->post('/api/admin/users', [
        'name' => 'With Avatar',
        'email' => 'avatar@example.com',
        'password' => 'secret123',
        'role' => 'user',
        'avatar' => UploadedFile::fake()->image('me.jpg'),
    ])->assertCreated();

    $stored = User::where('email', 'avatar@example.com')->first()->getRawOriginal('avatar');

    expect($stored)->not->toBeNull();
    Storage::disk('public')->assertExists($stored);
});

it('replaces an avatar and deletes the previous file', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);

    $target = User::factory()->create();
    $target->assignRole('user');

    // First upload.
    $this->patch("/api/admin/users/{$target->uuid}", [
        'avatar' => UploadedFile::fake()->image('first.jpg'),
    ])->assertOk();
    $first = $target->fresh()->getRawOriginal('avatar');
    Storage::disk('public')->assertExists($first);

    // Second upload should replace it and remove the original.
    $this->patch("/api/admin/users/{$target->uuid}", [
        'avatar' => UploadedFile::fake()->image('second.jpg'),
    ])->assertOk();
    $second = $target->fresh()->getRawOriginal('avatar');

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertExists($second);
    Storage::disk('public')->assertMissing($first);
});

it('clears the avatar and removes the file when remove_avatar is set', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    Sanctum::actingAs($admin);

    $target = User::factory()->create();
    $target->assignRole('user');

    $this->patch("/api/admin/users/{$target->uuid}", [
        'avatar' => UploadedFile::fake()->image('a.jpg'),
    ])->assertOk();
    $path = $target->fresh()->getRawOriginal('avatar');

    $this->patch("/api/admin/users/{$target->uuid}", ['remove_avatar' => 1])->assertOk();

    expect($target->fresh()->getRawOriginal('avatar'))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('stores an avatar from the self-service profile screen', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->patch('/settings/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('self.jpg'),
    ]);

    $stored = $user->fresh()->getRawOriginal('avatar');

    expect($stored)->not->toBeNull();
    Storage::disk('public')->assertExists($stored);
});

it('replaces the avatar from the profile screen without orphaning the old file', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)->patch('/settings/profile', [
        'name' => $user->name, 'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('one.jpg'),
    ]);
    $first = $user->fresh()->getRawOriginal('avatar');

    $this->actingAs($user)->patch('/settings/profile', [
        'name' => $user->name, 'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('two.jpg'),
    ]);
    $second = $user->fresh()->getRawOriginal('avatar');

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertExists($second);
    Storage::disk('public')->assertMissing($first);
});

it('stores an avatar through the json update-me endpoint', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    Sanctum::actingAs($user);

    $this->post('/api/auth/me', [
        'avatar' => UploadedFile::fake()->image('api.jpg'),
    ])->assertOk();

    $stored = $user->fresh()->getRawOriginal('avatar');

    expect($stored)->not->toBeNull();
    Storage::disk('public')->assertExists($stored);
});

it('replaces the avatar via update-me without orphaning the old file', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    Sanctum::actingAs($user);

    $this->post('/api/auth/me', ['avatar' => UploadedFile::fake()->image('v1.jpg')])->assertOk();
    $first = $user->fresh()->getRawOriginal('avatar');

    Sanctum::actingAs($user->fresh());
    $this->post('/api/auth/me', ['avatar' => UploadedFile::fake()->image('v2.jpg')])->assertOk();
    $second = $user->fresh()->getRawOriginal('avatar');

    expect($second)->not->toBe($first);
    Storage::disk('public')->assertExists($second);
    Storage::disk('public')->assertMissing($first);
});

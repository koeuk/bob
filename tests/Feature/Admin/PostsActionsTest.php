<?php

use App\Models\ActivityLog;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

/** Covers app/Actions/Posts/* through the JSON admin API. */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    Sanctum::actingAs($this->admin);
});

it('lists posts with engagement counts', function () {
    Post::factory()->create(['user_id' => $this->admin->id]);

    $this->getJson('/api/admin/posts')
        ->assertOk()
        ->assertJsonStructure(['data' => [['uuid', 'body', 'status', 'comments_count', 'likes_count', 'reports_count']]]);
});

it('creates a post attributed to the acting admin by default', function () {
    $this->postJson('/api/admin/posts', [
        'body' => 'Admin post.',
        'status' => 'active',
    ])->assertCreated();

    expect(Post::first()->user_id)->toBe($this->admin->id)
        ->and(ActivityLog::where('action', 'post.create')->exists())->toBeTrue();
});

it('can author a post on behalf of another user', function () {
    $author = User::factory()->create();

    $this->postJson('/api/admin/posts', [
        'body' => 'On behalf.',
        'status' => 'active',
        'user_uuid' => $author->uuid,
    ])->assertCreated();

    expect(Post::first()->user_id)->toBe($author->id);
});

it('reassigns a post author on update and logs the previous state', function () {
    $post = Post::factory()->create(['user_id' => $this->admin->id, 'body' => 'before']);
    $newAuthor = User::factory()->create();

    $this->patchJson("/api/admin/posts/{$post->uuid}", [
        'body' => 'after',
        'user_uuid' => $newAuthor->uuid,
        'reason' => 'cleanup',
    ])->assertOk();

    $fresh = $post->fresh();
    expect($fresh->body)->toBe('after')
        ->and($fresh->user_id)->toBe($newAuthor->id)
        ->and(ActivityLog::where('action', 'post.update')->first()->before['body'])->toBe('before');
});

it('flags a post without touching its body', function () {
    $post = Post::factory()->create(['user_id' => $this->admin->id, 'body' => 'keep', 'status' => 'active']);

    $this->patchJson("/api/admin/posts/{$post->uuid}/flag", ['status' => 'hidden'])->assertOk();

    $fresh = $post->fresh();
    expect($fresh->status)->toBe('hidden')
        ->and($fresh->body)->toBe('keep')
        ->and(ActivityLog::where('action', 'post.flag')->exists())->toBeTrue();
});

it('rejects an invalid status', function () {
    $post = Post::factory()->create(['user_id' => $this->admin->id]);

    $this->patchJson("/api/admin/posts/{$post->uuid}/flag", ['status' => 'nope'])
        ->assertStatus(422);
});

it('deletes a post and logs it', function () {
    $post = Post::factory()->create(['user_id' => $this->admin->id]);

    $this->deleteJson("/api/admin/posts/{$post->uuid}")->assertOk();

    expect(Post::find($post->id))->toBeNull()
        ->and(ActivityLog::where('action', 'post.delete')->exists())->toBeTrue();
});

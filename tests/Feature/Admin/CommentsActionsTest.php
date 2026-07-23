<?php

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

/**
 * Characterization tests for app/Actions/Comments/*, exercised through the
 * JSON admin API. The Inertia admin panel calls the same Actions.
 */
beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    Sanctum::actingAs($this->admin);

    $this->post = Post::factory()->create(['user_id' => User::factory()->create()->id]);
});

it('lists comments with like and report counts', function () {
    Comment::create(['user_id' => $this->admin->id, 'post_id' => $this->post->id, 'body' => 'hello']);

    $this->getJson('/api/admin/comments')
        ->assertOk()
        ->assertJsonStructure(['data' => [['uuid', 'body', 'likes_count', 'reports_count']]]);
});

it('creates a comment attributed to the acting admin by default', function () {
    $this->postJson('/api/admin/comments', [
        'post_uuid' => $this->post->uuid,
        'body' => 'Admin comment.',
    ])->assertCreated();

    $comment = Comment::first();
    expect($comment->user_id)->toBe($this->admin->id)
        ->and($comment->parent_id)->toBeNull()
        ->and(ActivityLog::where('action', 'comment.create')->exists())->toBeTrue();
});

it('can attribute a comment to another user and nest it under a parent', function () {
    $author = User::factory()->create();
    $parent = Comment::create(['user_id' => $author->id, 'post_id' => $this->post->id, 'body' => 'parent']);

    $this->postJson('/api/admin/comments', [
        'post_uuid' => $this->post->uuid,
        'body' => 'reply',
        'user_uuid' => $author->uuid,
        'parent_uuid' => $parent->uuid,
    ])->assertCreated();

    $reply = Comment::where('body', 'reply')->first();
    expect($reply->user_id)->toBe($author->id)
        ->and($reply->parent_id)->toBe($parent->id);
});

it('updates a comment body and logs the previous value', function () {
    $comment = Comment::create(['user_id' => $this->admin->id, 'post_id' => $this->post->id, 'body' => 'before']);

    $this->patchJson("/api/admin/comments/{$comment->uuid}", [
        'body' => 'after',
        'reason' => 'cleanup',
    ])->assertOk();

    expect($comment->fresh()->body)->toBe('after')
        ->and(ActivityLog::where('action', 'comment.update')->first()->before)->toBe(['body' => 'before']);
});

it('deletes a comment and logs it', function () {
    $comment = Comment::create(['user_id' => $this->admin->id, 'post_id' => $this->post->id, 'body' => 'bye']);

    $this->deleteJson("/api/admin/comments/{$comment->uuid}")->assertOk();

    expect(Comment::find($comment->id))->toBeNull()
        ->and(ActivityLog::where('action', 'comment.delete')->exists())->toBeTrue();
});
